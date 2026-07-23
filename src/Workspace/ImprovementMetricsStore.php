<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Persists Improvement Success Rate for product intelligence (not telemetry noise).
 */
final class ImprovementMetricsStore
{
    public function path(string $projectRoot): string
    {
        return rtrim(str_replace('\\', '/', $projectRoot), '/').'/storage/architect/metrics/improvement_success.json';
    }

    public function load(string $projectRoot): ImprovementSuccessRate
    {
        $path = $this->path($projectRoot);
        if (! is_file($path)) {
            return ImprovementSuccessRate::empty();
        }

        /** @var array<string, mixed> $data */
        $data = json_decode((string) file_get_contents($path), true) ?: [];

        return ImprovementSuccessRate::fromArray($data);
    }

    public function save(string $projectRoot, ImprovementSuccessRate $rate): void
    {
        $path = $this->path($projectRoot);
        $dir = dirname($path);
        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            return;
        }

        file_put_contents(
            $path,
            json_encode($rate->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );
    }
}
