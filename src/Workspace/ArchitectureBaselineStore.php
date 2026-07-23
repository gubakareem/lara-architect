<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

final class ArchitectureBaselineStore
{
    public function path(string $projectRoot): string
    {
        return rtrim(str_replace('\\', '/', $projectRoot), '/').'/storage/architect/baseline.json';
    }

    public function save(string $projectRoot, ArchitectureBaseline $baseline): void
    {
        $path = $this->path($projectRoot);
        $dir = dirname($path);
        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            return;
        }

        file_put_contents(
            $path,
            json_encode($baseline->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );
    }

    public function latest(string $projectRoot): ?ArchitectureBaseline
    {
        $path = $this->path($projectRoot);
        if (! is_file($path)) {
            return null;
        }

        /** @var array<string, mixed> $data */
        $data = json_decode((string) file_get_contents($path), true) ?: [];

        return $data === [] ? null : ArchitectureBaseline::fromArray($data);
    }

    /**
     * Capture once if missing — first Memory of “how it was.”
     */
    public function ensure(string $projectRoot, WorkspaceSnapshot $snapshot, ArchitectureMemory $memory): ArchitectureBaseline
    {
        $existing = $this->latest($projectRoot);
        if ($existing !== null) {
            return $existing;
        }

        $baseline = ArchitectureBaseline::fromSnapshot($snapshot);
        $this->save($projectRoot, $baseline);
        $memory->record($projectRoot, ArchitectureEventType::BaselineCaptured, $snapshot->context->name, $baseline->toArray());

        return $baseline;
    }
}
