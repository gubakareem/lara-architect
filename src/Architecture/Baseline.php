<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture;

/**
 * Freezes the current set of lint violations so existing codebases can
 * adopt architect:lint immediately. Baselined violations are ignored;
 * only new ones fail the build. Fingerprints exclude line numbers so
 * unrelated edits that shift code do not resurrect baselined items.
 */
final class Baseline
{
    public function __construct(
        private readonly string $path,
        private readonly string $basePath = '',
    ) {}

    public function exists(): bool
    {
        return is_file($this->path);
    }

    /**
     * @param  list<Violation>  $violations
     * @return array{0: list<Violation>, 1: list<Violation>}
     */
    public function partition(array $violations): array
    {
        if (! $this->exists()) {
            return [$violations, []];
        }

        $raw = file_get_contents($this->path);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        $known = [];

        foreach (is_array($decoded) ? ($decoded['violations'] ?? []) : [] as $entry) {
            if (is_array($entry)) {
                $known[$this->fingerprintEntry($entry)] = true;
            }
        }

        $new = [];
        $baselined = [];

        foreach ($violations as $violation) {
            if (isset($known[$this->fingerprint($violation)])) {
                $baselined[] = $violation;
            } else {
                $new[] = $violation;
            }
        }

        return [$new, $baselined];
    }

    /**
     * @param  list<Violation>  $violations
     */
    public function write(array $violations): void
    {
        $directory = dirname($this->path);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $entries = array_map(fn (Violation $violation): array => [
            'rule' => (string) $violation->rule,
            'path' => $this->relativePath($violation->path),
            'message' => $violation->message,
        ], $violations);

        file_put_contents($this->path, json_encode([
            'generated_at' => gmdate('c'),
            'violations' => $entries,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    private function fingerprint(Violation $violation): string
    {
        return sha1((string) $violation->rule.'|'.$this->relativePath($violation->path).'|'.$violation->message);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function fingerprintEntry(array $entry): string
    {
        return sha1(($entry['rule'] ?? '').'|'.($entry['path'] ?? '').'|'.($entry['message'] ?? ''));
    }

    private function relativePath(string $path): string
    {
        if ($this->basePath === '') {
            return str_replace('\\', '/', $path);
        }

        $trimmed = str_replace([$this->basePath.DIRECTORY_SEPARATOR, $this->basePath.'/'], '', $path);

        return str_replace('\\', '/', $trimmed);
    }
}
