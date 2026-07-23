<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Append-only Architecture Event Stream on disk.
 * Path: storage/architect/events/stream.jsonl
 */
final class ArchitectureEventStore
{
    public function streamPath(string $projectRoot): string
    {
        return rtrim(str_replace('\\', '/', $projectRoot), '/').'/storage/architect/events/stream.jsonl';
    }

    public function append(string $projectRoot, ArchitectureEvent $event): void
    {
        $path = $this->streamPath($projectRoot);
        $dir = dirname($path);
        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            return;
        }

        file_put_contents(
            $path,
            json_encode($event->toArray(), JSON_UNESCAPED_SLASHES)."\n",
            FILE_APPEND | LOCK_EX,
        );
    }

    /**
     * @return list<ArchitectureEvent>
     */
    public function all(string $projectRoot, ?int $limit = null): array
    {
        $path = $this->streamPath($projectRoot);
        if (! is_file($path)) {
            return [];
        }

        $events = [];
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return [];
        }

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            /** @var array<string, mixed>|null $decoded */
            $decoded = json_decode($line, true);
            if (! is_array($decoded) || ! isset($decoded['type'])) {
                continue;
            }

            try {
                $events[] = ArchitectureEvent::fromArray($decoded);
            } catch (\ValueError) {
                continue;
            }
        }
        fclose($handle);

        if ($limit !== null && $limit > 0 && count($events) > $limit) {
            return array_slice($events, -$limit);
        }

        return $events;
    }

    /**
     * @return list<ArchitectureEvent>
     */
    public function forContext(string $projectRoot, string $context, ?int $limit = null): array
    {
        $filtered = array_values(array_filter(
            $this->all($projectRoot),
            static fn (ArchitectureEvent $event): bool => strcasecmp($event->context, $context) === 0,
        ));

        if ($limit !== null && $limit > 0 && count($filtered) > $limit) {
            return array_slice($filtered, -$limit);
        }

        return $filtered;
    }
}
