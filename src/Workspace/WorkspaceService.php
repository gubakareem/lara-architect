<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

use KarimAshraf\LaraArchitect\Architecture\AnalysisResult;
use KarimAshraf\LaraArchitect\Architecture\ArchitectureEngine;
use KarimAshraf\LaraArchitect\Architecture\Hotspot;
use KarimAshraf\LaraArchitect\Architecture\LayerRegistry;
use KarimAshraf\LaraArchitect\Architecture\Packs\LaravelRulePack;
use KarimAshraf\LaraArchitect\Architecture\Violation;

/**
 * Builds WorkspaceSnapshot from the engine. Framework-agnostic — safe for CLI, HTTP, and future adapters.
 *
 * Flow: Finding → Issue (+ IssueExplanation) → Action
 */
final class WorkspaceService
{
    public function __construct(
        private readonly IssueCatalog $catalog = new IssueCatalog,
        private readonly ContextIntelligence $intelligence = new ContextIntelligence,
    ) {}

    /**
     * @param  list<string>  $paths
     */
    public function snapshot(
        string $project,
        ?WorkspaceContext $context = null,
        ?AnalysisResult $analysis = null,
        ?ArchitectureEngine $engine = null,
        array $paths = ['app'],
        ?LayerRegistry $layers = null,
    ): WorkspaceSnapshot {
        $analysis ??= ($engine ?? ArchitectureEngine::create())->analyze($project, $paths);
        $context ??= WorkspaceContext::project(basename(str_replace('\\', '/', $project)));
        $layers ??= (new LaravelRulePack)->layers();

        $relative = $this->relativePathFn($project);
        $findings = $this->findingsForContext($analysis, $context, $relative);
        $issues = array_map(fn (Finding $finding): WorkspaceIssue => $this->issueFromFinding($finding), $findings);
        $issues = $this->prioritizeIssues($issues);
        $context = $context->withCounts(count($issues), $this->suggestionCount($issues));
        $health = WorkspaceHealth::fromIssues($issues);
        $intelligence = $this->intelligence->build($analysis, $context, $layers, $relative);

        $safeFixes = count(array_filter($issues, static fn (WorkspaceIssue $i): bool => $i->safeFix));

        return new WorkspaceSnapshot(
            id: WorkspaceId::of('ws:'.md5($project.'|'.(string) $context->id)),
            project: $project,
            context: $context,
            health: $health,
            issues: $issues,
            actions: $this->workspaceActions($issues),
            metrics: [
                'files_scanned' => $analysis->filesScanned,
                'violation_count' => count($analysis->violations),
                'hotspot_count' => count($analysis->hotspots),
                'finding_count' => count($findings),
                'layers' => $analysis->layerCounts,
            ],
            today: [
                'new_issues' => count($issues),
                'safe_fixes' => $safeFixes,
                'estimated_minutes' => max(1, (int) ceil(count($issues) * 1.5)),
            ],
            related: $intelligence['related'],
            neighborhood: $intelligence['neighborhood'],
        );
    }

    /**
     * Vertical slice: return explanation payload for one issue id.
     *
     * @return array<string, mixed>|null
     */
    public function explain(WorkspaceSnapshot $snapshot, string $issueId): ?array
    {
        $issue = $snapshot->issueById($issueId);

        if ($issue === null) {
            return null;
        }

        return [
            'schema_version' => $snapshot->schemaVersion,
            'issue' => $issue->toArray(),
            'explanation' => $issue->explanation->toArray(),
            'findings' => array_map(
                static fn (Finding $finding): array => $finding->toArray(),
                $issue->findings,
            ),
        ];
    }

    /**
     * Portable text for AI chat, PRs, and discussions — without putting AI in core.
     */
    public function copyArchitectureContext(WorkspaceSnapshot $snapshot): string
    {
        $health = $snapshot->health;
        $lines = [
            'Lara Architect · Architecture Context',
            'Context: '.$snapshot->context->type->value.' · '.$snapshot->context->name,
            'Health: '.$health->band.($health->score !== null ? ' ('.$health->score.'%)' : ''),
            '',
        ];

        if ($snapshot->issues === []) {
            $lines[] = 'No issues in this context.';

            return implode("\n", $lines);
        }

        $lines[] = 'Violations / issues:';
        foreach ($snapshot->issues as $issue) {
            $lines[] = '- '.$issue->title;
            foreach ($issue->findings as $finding) {
                $lines[] = '  finding: '.$finding->summary;
            }
            $lines[] = '  recommended: '.$issue->explanation->recommendedFix;
            $fixes = [];
            foreach ($issue->actions as $action) {
                if (str_starts_with((string) $action->id, 'generate_')) {
                    $fixes[] = $action->label;
                }
            }
            if ($fixes !== []) {
                $lines[] = '  available fixes: '.implode(', ', $fixes);
            }
            $lines[] = '';
        }

        return rtrim(implode("\n", $lines))."\n";
    }

    /**
     * @param  callable(string): string  $relative
     * @return list<Finding>
     */
    private function findingsForContext(AnalysisResult $analysis, WorkspaceContext $context, callable $relative): array
    {
        $findings = [];

        foreach ($analysis->violations as $index => $violation) {
            if (! $this->violationMatchesContext($violation, $context, $relative)) {
                continue;
            }
            $findings[] = $this->findingFromViolation($violation, $index, $relative);
        }

        foreach ($analysis->hotspots as $index => $hotspot) {
            if (! $this->hotspotMatchesContext($hotspot, $context, $relative)) {
                continue;
            }
            $findings[] = $this->findingFromHotspot($hotspot, $index, $relative);
        }

        return $findings;
    }

    /**
     * @param  callable(string): string  $relative
     */
    private function violationMatchesContext(Violation $violation, WorkspaceContext $context, callable $relative): bool
    {
        if ($context->type === WorkspaceContextType::Project) {
            return true;
        }

        if ($context->type === WorkspaceContextType::File && $context->path !== null) {
            $path = str_replace('\\', '/', $relative($violation->path));
            $needle = str_replace('\\', '/', $context->path);

            return str_ends_with($path, $needle)
                || str_contains($path, $needle)
                || str_ends_with($path, basename($needle));
        }

        if ($context->type === WorkspaceContextType::Module) {
            return str_contains($violation->path, $context->name)
                || str_contains((string) $violation->source?->fqcn, $context->name);
        }

        return true;
    }

    /**
     * @param  callable(string): string  $relative
     */
    private function hotspotMatchesContext(Hotspot $hotspot, WorkspaceContext $context, callable $relative): bool
    {
        if ($context->type === WorkspaceContextType::Project) {
            return true;
        }

        if ($context->type === WorkspaceContextType::File && $context->path !== null) {
            $path = str_replace('\\', '/', $relative($hotspot->path));
            $needle = str_replace('\\', '/', $context->path);

            return str_ends_with($path, $needle) || str_contains($path, $needle);
        }

        if ($context->type === WorkspaceContextType::Module) {
            return str_contains($hotspot->path, $context->name);
        }

        return true;
    }

    /**
     * @param  callable(string): string  $relative
     */
    private function findingFromViolation(Violation $violation, int $index, callable $relative): Finding
    {
        $path = $relative($violation->path);
        $summary = sprintf(
            '%s depends on %s',
            $violation->source !== null ? $violation->source->fqcn : 'unknown',
            $violation->target !== null ? $violation->target->fqcn : 'unknown',
        );

        return new Finding(
            id: FindingId::of(sprintf('finding:violation:%s:%d:%d', $path, $violation->line, $index)),
            kind: 'violation',
            summary: $summary,
            path: $path,
            line: $violation->line,
            rule: (string) $violation->rule,
            sourceFqcn: $violation->source?->fqcn,
            targetFqcn: $violation->target?->fqcn,
            rawMessage: $violation->message,
        );
    }

    /**
     * @param  callable(string): string  $relative
     */
    private function findingFromHotspot(Hotspot $hotspot, int $index, callable $relative): Finding
    {
        $path = $relative($hotspot->path);

        return new Finding(
            id: FindingId::of('finding:hotspot:'.$path.':'.$index),
            kind: 'hotspot',
            summary: $hotspot->message,
            path: $path,
            line: 0,
            rule: 'hotspot',
            rawMessage: $hotspot->message,
        );
    }

    private function issueFromFinding(Finding $finding): WorkspaceIssue
    {
        $entry = $this->catalog->forFinding($finding);

        return new WorkspaceIssue(
            id: IssueId::of('issue:'.(string) $finding->id),
            title: $entry->title,
            severity: $finding->kind === 'hotspot' ? 'info' : 'warning',
            explanation: $entry->explanation,
            safeFix: $entry->safeFix,
            findings: [$finding],
            actions: $entry->actions,
            path: $finding->path,
            line: $finding->line,
        );
    }

    /**
     * Highest-impact improvement first — guide attention, don't dump a flat list.
     *
     * @param  list<WorkspaceIssue>  $issues
     * @return list<WorkspaceIssue>
     */
    private function prioritizeIssues(array $issues): array
    {
        usort($issues, static function (WorkspaceIssue $a, WorkspaceIssue $b): int {
            $weight = $b->explanation->impact->sortWeight() <=> $a->explanation->impact->sortWeight();
            if ($weight !== 0) {
                return $weight;
            }

            $kindA = $a->findings[0]->kind ?? '';
            $kindB = $b->findings[0]->kind ?? '';

            return strcmp($kindA, $kindB);
        });

        if ($issues === []) {
            return [];
        }

        $issues[0] = $issues[0]->withPrimary(true);

        return $issues;
    }

    /**
     * @param  list<WorkspaceIssue>  $issues
     * @return list<WorkspaceAction>
     */
    private function workspaceActions(array $issues): array
    {
        $actions = [
            WorkspaceAction::make(
                'explain',
                'Explain',
                WorkspaceActionState::Executable,
                available: $issues !== [],
            ),
            WorkspaceAction::make(
                'copy_context',
                'Copy Architecture Context',
                WorkspaceActionState::Executable,
                available: true,
            ),
        ];

        if ($issues !== []) {
            $actions[] = WorkspaceAction::make(
                'preview',
                'Preview Fix',
                WorkspaceActionState::Previewable,
                available: false,
                payload: ['status' => 'phase_4'],
            );
        }

        $hasSafe = array_filter($issues, static fn (WorkspaceIssue $i): bool => $i->safeFix) !== [];
        if ($hasSafe) {
            $actions[] = WorkspaceAction::make(
                'fix_safe',
                'Fix Safe Issues',
                WorkspaceActionState::Available,
                available: false,
                payload: ['status' => 'phase_5'],
            );
        }

        return $actions;
    }

    /**
     * @param  list<WorkspaceIssue>  $issues
     */
    private function suggestionCount(array $issues): int
    {
        $count = 0;
        foreach ($issues as $issue) {
            foreach ($issue->actions as $action) {
                if (str_starts_with((string) $action->id, 'generate_')) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * @return callable(string): string
     */
    private function relativePathFn(string $project): callable
    {
        $base = rtrim(str_replace('\\', '/', $project), '/');

        return static function (string $path) use ($base): string {
            $normalized = str_replace('\\', '/', $path);
            if (str_starts_with($normalized, $base.'/')) {
                return substr($normalized, strlen($base) + 1);
            }

            return $normalized;
        };
    }
}
