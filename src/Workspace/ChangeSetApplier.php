<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Writes ChangeSet files to disk. Core owns mutation — UI never writes code.
 */
final class ChangeSetApplier
{
    /**
     * @return list<string> Absolute paths written/deleted
     */
    public function apply(ChangeSet $changeSet, string $projectRoot): array
    {
        $root = rtrim(str_replace('\\', '/', $projectRoot), '/');
        $touched = [];

        foreach ($changeSet->files as $file) {
            $relative = ltrim(str_replace('\\', '/', $file->path), '/');
            $full = $root.'/'.$relative;

            match ($file->type) {
                FileChangeType::Created => $this->writeCreated($full, $file->after),
                FileChangeType::Modified => $this->writeModified($full, $file->before, $file->after),
                FileChangeType::Deleted => $this->deleteFile($full),
            };

            $touched[] = $full;
        }

        return $touched;
    }

    private function writeCreated(string $full, ?string $after): void
    {
        if ($after === null || $after === '') {
            throw new \RuntimeException('Created file is missing after content: '.$full);
        }

        $dir = dirname($full);
        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Unable to create directory: '.$dir);
        }

        if (file_put_contents($full, $after) === false) {
            throw new \RuntimeException('Unable to write file: '.$full);
        }
    }

    private function writeModified(string $full, ?string $before, ?string $after): void
    {
        if ($after === null) {
            throw new \RuntimeException('Modified file is missing after content: '.$full);
        }

        if (! is_file($full)) {
            $this->writeCreated($full, $after);

            return;
        }

        $contents = (string) file_get_contents($full);

        if ($before !== null && $before !== '' && str_contains($contents, $before)) {
            $contents = str_replace($before, $after, $contents);
        } elseif (str_starts_with(ltrim($after), '<?php')) {
            $contents = $after;
        } else {
            // Snippet-only after with no before match — append as comment marker for safety.
            $contents .= "\n\n// Lara Architect Controlled Change\n".$after."\n";
        }

        if (file_put_contents($full, $contents) === false) {
            throw new \RuntimeException('Unable to update file: '.$full);
        }
    }

    private function deleteFile(string $full): void
    {
        if (is_file($full) && ! unlink($full)) {
            throw new \RuntimeException('Unable to delete file: '.$full);
        }
    }
}
