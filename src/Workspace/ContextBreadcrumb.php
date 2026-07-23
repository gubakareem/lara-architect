<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Builds Application > Http > Controllers > ProductController.php style trails.
 */
final class ContextBreadcrumb
{
    /**
     * @return list<array{label: string, type: string}>
     */
    public function fromPath(?string $relativePath, string $contextName, WorkspaceContextType $type): array
    {
        if ($type === WorkspaceContextType::Project) {
            return [
                ['label' => 'Application', 'type' => 'root'],
            ];
        }

        if ($type === WorkspaceContextType::Module) {
            return [
                ['label' => 'Application', 'type' => 'root'],
                ['label' => $contextName, 'type' => 'module'],
            ];
        }

        if ($relativePath === null || $relativePath === '') {
            return [
                ['label' => 'Application', 'type' => 'root'],
                ['label' => $contextName, 'type' => 'file'],
            ];
        }

        $normalized = str_replace('\\', '/', $relativePath);
        $normalized = preg_replace('#^app/#i', '', $normalized) ?? $normalized;
        $parts = array_values(array_filter(explode('/', $normalized), static fn (string $p): bool => $p !== ''));

        $crumbs = [
            ['label' => 'Application', 'type' => 'root'],
        ];

        foreach ($parts as $index => $part) {
            $isLast = $index === count($parts) - 1;
            $crumbs[] = [
                'label' => $isLast ? $part : $this->humanizeSegment($part),
                'type' => $isLast ? 'file' : 'segment',
            ];
        }

        return $crumbs;
    }

    private function humanizeSegment(string $segment): string
    {
        return match (strtolower($segment)) {
            'http' => 'HTTP',
            'dto', 'dtos' => 'DTOs',
            default => ucfirst($segment),
        };
    }
}
