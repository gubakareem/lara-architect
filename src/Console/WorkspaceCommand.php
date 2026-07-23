<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Console;

use Illuminate\Console\Command;
use KarimAshraf\LaraArchitect\Architecture\EngineFactory;
use KarimAshraf\LaraArchitect\Support\TeamConfig;
use KarimAshraf\LaraArchitect\Workspace\FixProposalService;
use KarimAshraf\LaraArchitect\Workspace\WorkspaceContext;
use KarimAshraf\LaraArchitect\Workspace\WorkspaceService;
use Throwable;

/**
 * Analyze → WorkspaceSnapshot → explain / copy context / propose fix (Preview contract).
 */
class WorkspaceCommand extends Command
{
    protected $signature = 'architect:workspace
        {--context= : Focus a file path or class basename (e.g. ProductController)}
        {--explain= : Issue id to explain (from --format=json output)}
        {--propose= : Issue id to build a FixProposal preview (no apply)}
        {--copy-context : Print portable architecture context for AI / PRs / chat}
        {--path=* : Paths to scan, relative to the project root}
        {--format=console : Output format: console|json}';

    protected $description = 'Open an Architecture Workspace snapshot for the current context';

    public function handle(WorkspaceService $workspace, FixProposalService $proposals): int
    {
        TeamConfig::apply();

        try {
            $engine = EngineFactory::engine([
                'layers' => (array) config('lara-architect.lint.layers', []),
                'dependencies' => (array) config('lara-architect.lint.dependencies', []),
                'thresholds' => (array) config('lara-architect.lint.thresholds', []),
                'pack' => (string) config('lara-architect.lint.pack', 'laravel'),
            ]);

            $paths = $this->option('path') ?: config('lara-architect.lint.paths', ['app']);
            $contextOption = $this->option('context');
            $context = is_string($contextOption) && $contextOption !== ''
                ? $this->resolveContext($contextOption)
                : WorkspaceContext::project(basename(base_path()));

            $analysis = $engine->analyze(base_path(), array_values((array) $paths));
            $snapshot = $workspace->snapshot(
                project: base_path(),
                context: $context,
                analysis: $analysis,
            );

            if ($this->option('copy-context')) {
                $text = $workspace->copyArchitectureContext($snapshot);
                if ($this->option('format') === 'json') {
                    $this->line(json_encode(['schema_version' => $snapshot->schemaVersion, 'text' => $text], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');
                } else {
                    $this->line($text);
                }

                return self::SUCCESS;
            }

            $proposeId = $this->option('propose');
            if (is_string($proposeId) && $proposeId !== '') {
                $proposal = $proposals->propose($snapshot, $proposeId, base_path());
                if ($proposal === null) {
                    $this->components->error("Unknown issue [{$proposeId}].");

                    return self::FAILURE;
                }

                if ($this->option('format') === 'json') {
                    $this->line(json_encode($proposal->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');

                    return self::SUCCESS;
                }

                $this->renderProposal($proposal->toArray());

                return self::SUCCESS;
            }

            $explainId = $this->option('explain');
            if (is_string($explainId) && $explainId !== '') {
                $explained = $workspace->explain($snapshot, $explainId);
                if ($explained === null) {
                    $this->components->error("Unknown issue [{$explainId}].");

                    return self::FAILURE;
                }

                if ($this->option('format') === 'json') {
                    $this->line(json_encode($explained, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');

                    return self::SUCCESS;
                }

                $this->renderExplanation($explained);

                return self::SUCCESS;
            }

            if ($this->option('format') === 'json') {
                $this->line(json_encode($snapshot->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');

                return self::SUCCESS;
            }

            $this->renderConsole($snapshot->toArray());

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function resolveContext(string $context): WorkspaceContext
    {
        if (str_contains($context, '/') || str_contains($context, '\\') || str_ends_with($context, '.php')) {
            $path = str_replace('\\', '/', $context);
            $name = pathinfo($path, PATHINFO_FILENAME);

            return WorkspaceContext::file($name, $path);
        }

        if (str_ends_with($context, 'Controller') || class_basename($context) !== $context) {
            $name = class_basename($context);

            return WorkspaceContext::file($name, 'app/Http/Controllers/'.$name.'.php');
        }

        return WorkspaceContext::module($context);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function renderConsole(array $payload): void
    {
        /** @var array{id?: string, health?: array{band?: string, score?: float|null}, today?: array{new_issues?: int, safe_fixes?: int, estimated_minutes?: int}} $workspace */
        $workspace = $payload['workspace'] ?? [];
        /** @var array{band?: string, score?: float|null} $health */
        $health = $workspace['health'] ?? [];
        /** @var array{type?: string, name?: string, path?: string|null, id?: string} $context */
        $context = $payload['context'] ?? [];
        /** @var array{new_issues?: int, safe_fixes?: int, estimated_minutes?: int} $today */
        $today = $workspace['today'] ?? [];

        $this->components->info(sprintf(
            'Lara Architect · Workspace (schema %s)',
            (string) ($payload['schema_version'] ?? '?'),
        ));
        $this->components->twoColumnDetail(
            'Health',
            sprintf('%s%s', $health['band'] ?? '—', isset($health['score']) ? ' · '.(string) $health['score'].'%' : ''),
        );
        $this->newLine();
        $this->line('Today');
        $this->components->twoColumnDetail('Issues in context', (string) ($today['new_issues'] ?? 0));
        $this->components->twoColumnDetail('Safe fixes', (string) ($today['safe_fixes'] ?? 0));
        $this->components->twoColumnDetail('Estimated', ($today['estimated_minutes'] ?? 1).' min');
        $this->newLine();
        $this->line('Current context');
        $contextPath = isset($context['path']) && is_string($context['path']) && $context['path'] !== ''
            ? ' ('.$context['path'].')'
            : '';
        $this->components->twoColumnDetail(
            (string) ($context['type'] ?? 'project'),
            trim((string) ($context['name'] ?? '').$contextPath),
        );

        /** @var list<array<string, mixed>> $issues */
        $issues = $payload['issues'] ?? [];
        $this->newLine();
        if ($issues === []) {
            $this->components->info('No issues in this context.');

            return;
        }

        $this->components->warn(sprintf('%d issue(s):', count($issues)));
        foreach ($issues as $issue) {
            $this->components->twoColumnDetail(
                (string) ($issue['title'] ?? 'Issue'),
                sprintf('%s:%s', $issue['path'] ?? '?', $issue['line'] ?? '?'),
            );
            $this->line('  '.$this->dim('id: '.(string) ($issue['id'] ?? '')));
        }

        $this->newLine();
        $this->line('Actions: --explain=<id> · --propose=<id> · --copy-context · Apply (later)');
    }

    /**
     * @param  array<string, mixed>  $proposal
     */
    private function renderProposal(array $proposal): void
    {
        $this->components->info((string) ($proposal['title'] ?? 'Fix proposal'));
        $this->components->twoColumnDetail('Risk', (string) ($proposal['risk_label'] ?? $proposal['risk'] ?? '—'));
        /** @var array{level?: string} $confidence */
        $confidence = $proposal['confidence'] ?? [];
        $this->components->twoColumnDetail('Confidence', (string) ($confidence['level'] ?? '—'));
        $this->newLine();
        $this->line((string) ($proposal['description'] ?? ''));
        $this->newLine();
        $this->line('<fg=yellow>Changes</>');
        /** @var list<array{path?: string, type?: string, before?: ?string, after?: ?string}> $changes */
        $changes = $proposal['changes'] ?? [];
        foreach ($changes as $change) {
            $mark = match ($change['type'] ?? '') {
                'created' => '+',
                'deleted' => '-',
                default => 'M',
            };
            $this->line(sprintf('  %s %s', $mark, $change['path'] ?? '?'));
            if (! empty($change['before']) || ! empty($change['after'])) {
                if (! empty($change['before'])) {
                    $this->line('    - '.$change['before']);
                }
                if (! empty($change['after'])) {
                    $first = strtok((string) $change['after'], "\n") ?: (string) $change['after'];
                    $this->line('    + '.$first);
                }
            }
        }
        $this->newLine();
        $this->line('<fg=yellow>Verification (pending)</>');
        /** @var array{checks?: list<array{name?: string, status?: string}>} $verification */
        $verification = $proposal['verification'] ?? [];
        foreach ($verification['checks'] ?? [] as $check) {
            $this->line('  ○ '.($check['name'] ?? 'check'));
        }
        $this->newLine();
        /** @var array{apply_enabled?: bool, apply_label?: string} $policy */
        $policy = $proposal['policy'] ?? [];
        $this->components->warn(
            ($policy['apply_enabled'] ?? false)
                ? 'Apply enabled'
                : 'Apply disabled — Review → Verify → Apply (later). Preview only.',
        );
    }

    /**
     * @param  array<string, mixed>  $explained
     */
    private function renderExplanation(array $explained): void
    {
        /** @var array<string, mixed> $issue */
        $issue = $explained['issue'] ?? [];
        /** @var array{why?: string, benefits?: list<string>, recommended_fix?: string, impact?: string} $explanation */
        $explanation = $explained['explanation'] ?? [];

        $this->components->info((string) ($issue['title'] ?? 'Issue'));
        $this->newLine();
        $this->line('<fg=yellow>Why</>');
        $this->line((string) ($explanation['why'] ?? ''));
        $this->newLine();
        $this->line('<fg=yellow>Impact</>');
        $this->line((string) ($explanation['impact'] ?? ($issue['impact'] ?? '')));
        $this->newLine();
        $this->line('<fg=yellow>Benefits</>');
        foreach ($explanation['benefits'] ?? [] as $benefit) {
            $this->line('  ✓ '.$benefit);
        }
        $this->newLine();
        $this->line('<fg=yellow>Fix</>');
        $this->line((string) ($explanation['recommended_fix'] ?? ''));
    }

    private function dim(string $text): string
    {
        return "<fg=gray>{$text}</>";
    }
}
