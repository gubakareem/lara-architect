<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Builds FixProposal from Workspace issues. UI requests proposals — it never generates code itself.
 *
 * Phase 3: Controlled Change available for Safe proposals (applyEnabled).
 */
final class FixProposalService
{
    public function propose(WorkspaceSnapshot $snapshot, string $issueId, string $projectRoot = ''): ?FixProposal
    {
        $issue = $snapshot->issueById($issueId);

        if ($issue === null) {
            return null;
        }

        return match ($issue->title) {
            'Direct Model Usage' => $this->extractServiceProposal($issue, $projectRoot),
            'Controller Depends on Repository' => $this->extractServiceProposal($issue, $projectRoot, forRepository: true),
            'Validation in Controller' => $this->moveValidationProposal($issue),
            'Infrastructure Leak into Controller' => $this->extractRepositoryProposal($issue),
            default => $this->unsupportedProposal($issue),
        };
    }

    private function extractServiceProposal(WorkspaceIssue $issue, string $projectRoot, bool $forRepository = false): FixProposal
    {
        $module = $this->moduleFromIssue($issue) ?? 'Product';
        $controllerPath = $issue->path !== '' ? $issue->path : 'app/Http/Controllers/'.$module.'Controller.php';
        $servicePath = 'app/Services/'.$module.'Service.php';

        $before = $this->readSnippet($projectRoot, $controllerPath)
            ?? "return {$module}::create(\$data);";

        $afterHint = str_replace(
            ["{$module}::create", 'Product::create'],
            ['$this->'.lcfirst($module).'Service->create', '$this->productService->create'],
            $before,
        );

        if ($afterHint === $before) {
            $afterHint = preg_replace(
                '/\b([A-Z][A-Za-z0-9_]*)::create\(/',
                '$this->'.lcfirst($module).'Service->create(',
                $before,
            ) ?? $before;
        }

        $serviceStub = <<<PHP
<?php

namespace App\Services;

use App\Models\\{$module};

class {$module}Service
{
    public function create(array \$data): {$module}
    {
        return {$module}::create(\$data);
    }
}
PHP;

        $risk = $issue->safeFix ? FixRisk::Safe : FixRisk::Assisted;
        $confidence = $risk === FixRisk::Safe
            ? FixConfidence::high()
            : FixConfidence::medium([
                'Rule is deterministic',
                'Generated service follows preset',
                'Controller wiring still needs review',
            ]);

        $title = "Extract {$module}Service";
        $changeSet = ChangeSet::of([
            FileChange::make($controllerPath, FileChangeType::Modified, $before, $afterHint),
            FileChange::make($servicePath, FileChangeType::Created, null, $serviceStub),
        ]);
        $verification = VerificationPlan::defaultPlan();

        $modelLabel = $module.' Model';
        $serviceLabel = $module.'Service';

        return new FixProposal(
            id: FixProposalId::of('fix:'.md5((string) $issue->id.'|extract-service')),
            issueId: $issue->id,
            title: $title,
            description: $forRepository
                ? "Introduce {$module}Service so the controller no longer depends on the repository directly."
                : "Move product creation / persistence orchestration from the controller into {$module}Service.",
            risk: $risk,
            confidence: $confidence,
            changeSet: $changeSet,
            verification: $verification,
            reasoning: new FixProposalReasoning(
                rule: 'controller_should_not_access_models',
                principle: 'controllers_orchestrate_only',
                benefits: ['Removes direct model dependency', 'Improves testability', 'Matches architecture rules'],
            ),
            summary: FixProposalSummary::make(
                title: $title,
                intent: 'Extract business logic into service',
                expectedOutcome: 'Reduce controller responsibility',
                changeSet: $changeSet,
                verification: $verification,
            ),
            architectureImpact: ArchitectureImpact::graph(
                beforeNodes: ['Controller', $modelLabel],
                beforeEdges: [new ArchitectureEdge('Controller', $modelLabel)],
                afterNodes: ['Controller', $serviceLabel, $modelLabel],
                afterEdges: [
                    new ArchitectureEdge('Controller', $serviceLabel),
                    new ArchitectureEdge($serviceLabel, $modelLabel),
                ],
                results: [
                    'Layer violation resolved',
                    'Test boundary improved',
                    'Dependency direction improved',
                ],
                verificationReason: 'The proposal changes dependency direction and the service boundary.',
            ),
            applyEnabled: $risk->allowsApply(),
        );
    }

    private function moveValidationProposal(WorkspaceIssue $issue): FixProposal
    {
        $module = $this->moduleFromIssue($issue) ?? 'Product';
        $requestPath = 'app/Http/Requests/'.$module.'StoreRequest.php';
        $title = "Generate {$module} Form Request";
        $changeSet = ChangeSet::of([
            FileChange::make(
                $issue->path !== '' ? $issue->path : 'app/Http/Controllers/'.$module.'Controller.php',
                FileChangeType::Modified,
                'Validator::make($request->all(), [...])->validate();',
                '// type-hint '.$module.'StoreRequest $request',
            ),
            FileChange::make(
                $requestPath,
                FileChangeType::Created,
                null,
                "// Generated {$module}StoreRequest (preview)",
            ),
        ]);
        $verification = VerificationPlan::defaultPlan();

        return new FixProposal(
            id: FixProposalId::of('fix:'.md5((string) $issue->id.'|form-request')),
            issueId: $issue->id,
            title: $title,
            description: 'Move validation rules into a Form Request so the controller stays a thin orchestrator.',
            risk: FixRisk::Safe,
            confidence: FixConfidence::high([
                'Rule is deterministic',
                'Form Request generation follows preset',
                'Zero intended behavior change for valid input',
            ]),
            changeSet: $changeSet,
            verification: $verification,
            reasoning: new FixProposalReasoning(
                rule: 'controller_should_not_validate_inline',
                principle: 'controllers_orchestrate_only',
                benefits: ['Reusable validation', 'Cleaner controllers', 'Consistent error responses'],
            ),
            summary: FixProposalSummary::make(
                title: $title,
                intent: 'Move validation out of the controller',
                expectedOutcome: 'Thin orchestrator controllers with reusable Form Requests',
                changeSet: $changeSet,
                verification: $verification,
            ),
            architectureImpact: ArchitectureImpact::graph(
                beforeNodes: ['Controller', 'Inline Validator'],
                beforeEdges: [new ArchitectureEdge('Controller', 'Inline Validator')],
                afterNodes: ['Controller', $module.'StoreRequest'],
                afterEdges: [new ArchitectureEdge('Controller', $module.'StoreRequest')],
                results: [
                    'Validation boundary clarified',
                    'Controller stays thin',
                    'Reusable request object',
                ],
                verificationReason: 'The proposal moves validation into a dedicated Form Request boundary.',
            ),
            applyEnabled: FixRisk::Safe->allowsApply(),
        );
    }

    private function extractRepositoryProposal(WorkspaceIssue $issue): FixProposal
    {
        $module = $this->moduleFromIssue($issue) ?? 'Product';
        $title = "Extract {$module}Repository";
        $changeSet = ChangeSet::of([
            FileChange::make(
                'app/Repositories/'.$module.'Repository.php',
                FileChangeType::Created,
                null,
                "// Generated {$module}Repository (preview)",
            ),
        ]);
        $verification = VerificationPlan::defaultPlan();

        return new FixProposal(
            id: FixProposalId::of('fix:'.md5((string) $issue->id.'|repository')),
            issueId: $issue->id,
            title: $title,
            description: 'Push infrastructure / DB access behind a repository called from a service.',
            risk: FixRisk::Assisted,
            confidence: FixConfidence::medium([
                'Layer rule is deterministic',
                'Repository scaffold follows preset',
                'Call-site wiring needs review',
            ]),
            changeSet: $changeSet,
            verification: $verification,
            reasoning: new FixProposalReasoning(
                rule: 'controller_should_not_use_infrastructure',
                principle: 'infrastructure_behind_ports',
                benefits: ['Portability', 'Testability', 'Clear boundaries'],
            ),
            summary: FixProposalSummary::make(
                title: $title,
                intent: 'Hide infrastructure behind a repository',
                expectedOutcome: 'Controller/service depend on a port, not Eloquent directly',
                changeSet: $changeSet,
                verification: $verification,
            ),
            architectureImpact: ArchitectureImpact::graph(
                beforeNodes: ['Controller', 'Infrastructure'],
                beforeEdges: [new ArchitectureEdge('Controller', 'Infrastructure')],
                afterNodes: ['Controller', $module.'Service', $module.'Repository', 'Infrastructure'],
                afterEdges: [
                    new ArchitectureEdge('Controller', $module.'Service'),
                    new ArchitectureEdge($module.'Service', $module.'Repository'),
                    new ArchitectureEdge($module.'Repository', 'Infrastructure'),
                ],
                results: [
                    'Infrastructure leak contained',
                    'Portability improved',
                    'Test doubles become practical',
                ],
                verificationReason: 'The proposal introduces a repository port between services and infrastructure.',
            ),
            applyEnabled: false,
        );
    }

    private function unsupportedProposal(WorkspaceIssue $issue): FixProposal
    {
        $title = 'Manual design decision';
        $changeSet = ChangeSet::of([]);
        $verification = new VerificationPlan([]);

        return new FixProposal(
            id: FixProposalId::of('fix:'.md5((string) $issue->id.'|manual')),
            issueId: $issue->id,
            title: $title,
            description: $issue->explanation->recommendedFix,
            risk: FixRisk::Design,
            confidence: FixConfidence::low([
                'No deterministic automated fix for this issue yet',
            ]),
            changeSet: $changeSet,
            verification: $verification,
            reasoning: new FixProposalReasoning(
                rule: 'manual_architecture_decision',
                principle: 'explain_before_automating',
                benefits: $issue->explanation->benefits,
            ),
            summary: FixProposalSummary::make(
                title: $title,
                intent: 'Capture an architecture decision that needs a human',
                expectedOutcome: 'Clear next step without a false automated fix',
                changeSet: $changeSet,
                verification: $verification,
            ),
            architectureImpact: ArchitectureImpact::graph(
                beforeNodes: ['Current design'],
                beforeEdges: [],
                afterNodes: ['Improved design (manual)'],
                afterEdges: [],
                results: ['No automated mutation — design decision required'],
                verificationReason: 'No automated change set; verify after a human redesign.',
            ),
            applyEnabled: false,
        );
    }

    private function moduleFromIssue(WorkspaceIssue $issue): ?string
    {
        foreach ($issue->findings as $finding) {
            if ($finding->sourceFqcn !== null && preg_match('/\\\\(\w+)Controller$/', $finding->sourceFqcn, $m) === 1) {
                return $m[1];
            }
        }

        if (preg_match('/(\w+)Controller\.php$/', $issue->path, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    private function readSnippet(string $projectRoot, string $relativePath): ?string
    {
        if ($projectRoot === '') {
            return null;
        }

        $full = rtrim(str_replace('\\', '/', $projectRoot), '/').'/'.ltrim(str_replace('\\', '/', $relativePath), '/');
        if (! is_file($full)) {
            return null;
        }

        $contents = (string) file_get_contents($full);
        if (preg_match('/^.*::create\(.*$/m', $contents, $matches) === 1) {
            return trim($matches[0]);
        }

        return null;
    }
}
