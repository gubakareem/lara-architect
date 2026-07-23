<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * First-class architecture education catalog.
 * Curated WHY — not AI. Language models may enhance copy later; they do not own the knowledge.
 */
final class IssueCatalog
{
    public function forFinding(Finding $finding): IssueCatalogEntry
    {
        $targetLayer = $this->deniedLayerFromMessage($finding->rawMessage !== '' ? $finding->rawMessage : $finding->summary);

        return match ($targetLayer) {
            'Model' => new IssueCatalogEntry(
                title: 'Direct Model Usage',
                explanation: new IssueExplanation(
                    why: 'Controllers should coordinate requests, not own persistence or business rules. Keep Eloquent usage behind a service (and usually a repository).',
                    impact: ImprovementImpact::high(architecture: 'high', testing: 'high', complexity: 'medium'),
                    benefits: ['Better testing', 'Reusable logic', 'Clear separation'],
                    recommendedFix: $this->generateFixSummary('Service', $finding->sourceFqcn),
                    recommendedActions: ['explain', 'preview', 'generate_service'],
                ),
                safeFix: false,
                actions: [
                    WorkspaceAction::make('explain', 'Explain', WorkspaceActionState::Executable),
                    WorkspaceAction::make('preview', 'Preview Fix', WorkspaceActionState::Previewable, available: true, payload: ['status' => 'phase_2']),
                    WorkspaceAction::make('generate_service', 'Generate Service', WorkspaceActionState::Previewable, available: false, payload: ['pattern' => 'service', 'status' => 'phase_5']),
                ],
            ),
            'Repository' => new IssueCatalogEntry(
                title: 'Controller Depends on Repository',
                explanation: new IssueExplanation(
                    why: 'Controllers should not reach the repository directly. Inject a service that owns the use-case; the service may use the repository.',
                    impact: ImprovementImpact::high(architecture: 'high', testing: 'high', complexity: 'medium'),
                    benefits: ['Clear application layer', 'Easier policy/auth placement', 'Testability'],
                    recommendedFix: $this->generateFixSummary('Service', $finding->sourceFqcn),
                    recommendedActions: ['explain', 'preview', 'generate_service'],
                ),
                safeFix: false,
                actions: [
                    WorkspaceAction::make('explain', 'Explain', WorkspaceActionState::Executable),
                    WorkspaceAction::make('preview', 'Preview Fix', WorkspaceActionState::Previewable, available: true, payload: ['status' => 'phase_2']),
                    WorkspaceAction::make('generate_service', 'Generate Service', WorkspaceActionState::Previewable, available: false, payload: ['pattern' => 'service', 'status' => 'phase_5']),
                ],
            ),
            'Infrastructure' => new IssueCatalogEntry(
                title: 'Infrastructure Leak into Controller',
                explanation: new IssueExplanation(
                    why: 'Facades like DB and low-level Illuminate Database usage couple HTTP to infrastructure. Push queries behind a repository or query object.',
                    impact: ImprovementImpact::high(architecture: 'high', testing: 'high', complexity: 'high'),
                    benefits: ['Portability', 'Testability', 'Clear boundaries'],
                    recommendedFix: 'Introduce a repository or query and call it from a service.',
                    recommendedActions: ['explain', 'preview', 'generate_repository'],
                ),
                safeFix: false,
                actions: [
                    WorkspaceAction::make('explain', 'Explain', WorkspaceActionState::Executable),
                    WorkspaceAction::make('preview', 'Preview Fix', WorkspaceActionState::Previewable, available: true, payload: ['status' => 'phase_2']),
                    WorkspaceAction::make('generate_repository', 'Generate Repository', WorkspaceActionState::Previewable, available: false, payload: ['pattern' => 'repository', 'status' => 'phase_5']),
                ],
            ),
            'Validation' => new IssueCatalogEntry(
                title: 'Validation in Controller',
                explanation: new IssueExplanation(
                    why: 'Validation belongs in Form Requests (or dedicated validators), not inline in controllers. Controllers stay thin orchestrators.',
                    impact: ImprovementImpact::medium(architecture: 'medium', testing: 'high', complexity: 'low'),
                    benefits: ['Reusable rules', 'Cleaner controllers', 'Consistent error responses'],
                    recommendedFix: 'Move validation into a Form Request and type-hint it on the action method.',
                    recommendedActions: ['explain', 'preview', 'generate_request'],
                ),
                safeFix: true,
                actions: [
                    WorkspaceAction::make('explain', 'Explain', WorkspaceActionState::Executable),
                    WorkspaceAction::make('preview', 'Preview Fix', WorkspaceActionState::Previewable, available: true, payload: ['status' => 'phase_2']),
                    WorkspaceAction::make('generate_request', 'Generate Form Request', WorkspaceActionState::Previewable, available: false, payload: ['pattern' => 'requests', 'status' => 'phase_5']),
                ],
            ),
            default => $this->defaultEntry($finding),
        };
    }

    private function defaultEntry(Finding $finding): IssueCatalogEntry
    {
        if ($finding->kind === 'hotspot') {
            return new IssueCatalogEntry(
                title: 'Hotspot',
                explanation: new IssueExplanation(
                    why: 'This file crossed a complexity or size threshold. Large units are harder to test and tend to accumulate architecture debt.',
                    impact: ImprovementImpact::low(architecture: 'low', testing: 'medium', complexity: 'high'),
                    benefits: ['Easier reviews', 'Clearer responsibilities', 'Safer changes'],
                    recommendedFix: 'Split or extract collaborators (service, action, private methods) until the hotspot clears.',
                    recommendedActions: ['explain'],
                ),
                safeFix: false,
                actions: [
                    WorkspaceAction::make('explain', 'Explain', WorkspaceActionState::Executable),
                ],
            );
        }

        return new IssueCatalogEntry(
            title: $this->fallbackTitle($finding->summary),
            explanation: new IssueExplanation(
                why: 'This dependency violates the declared architecture layers. Keep each layer’s responsibilities clear so the codebase stays navigable as it grows.',
                impact: ImprovementImpact::medium(),
                benefits: ['Predictable structure', 'Safer refactors', 'Team alignment'],
                recommendedFix: 'Adjust the dependency so it follows your layer rules (often by introducing an intermediate service or moving code).',
                recommendedActions: ['explain', 'preview'],
            ),
            safeFix: false,
            actions: [
                WorkspaceAction::make('explain', 'Explain', WorkspaceActionState::Executable),
                WorkspaceAction::make('preview', 'Preview Fix', WorkspaceActionState::Previewable, available: true, payload: ['status' => 'phase_2']),
            ],
        );
    }

    private function deniedLayerFromMessage(string $message): ?string
    {
        if (preg_match('/must not depend on (\w+)/i', $message, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/cannot depend on \[([^\]]+)\]/i', $message, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function generateFixSummary(string $pattern, ?string $sourceFqcn): string
    {
        $module = $this->guessModule($sourceFqcn);

        return $module !== null
            ? "Generate a {$module}{$pattern} and depend on it from the controller instead of the forbidden layer."
            : "Generate a {$pattern} layer and depend on it from the controller instead of the forbidden layer.";
    }

    private function guessModule(?string $sourceFqcn): ?string
    {
        if ($sourceFqcn === null) {
            return null;
        }

        if (preg_match('/\\\\(\w+)Controller$/', $sourceFqcn, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

    private function fallbackTitle(string $message): string
    {
        $trimmed = trim($message);

        return $trimmed !== '' ? $trimmed : 'Architecture violation';
    }
}
