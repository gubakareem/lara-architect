<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Architecture Vocabulary — internal naming consistency.
 * Maps “Extract Service” / “Move Logic to Service” → one concept.
 * Not Team Architecture Language yet (no team preferences).
 */
final class ArchitectureVocabulary
{
    public const SERVICE_EXTRACTION = 'service_extraction';

    public const REQUEST_VALIDATION = 'request_validation';

    public const REPOSITORY_PORT = 'repository_port';

    public const CONTROLLER_DEPENDENCY = 'controller_dependency';

    public const DIRECT_MODEL_USAGE = 'direct_model_usage';

    /** @var list<ArchitectureConcept> */
    private array $concepts;

    public function __construct()
    {
        $this->concepts = [
            new ArchitectureConcept(
                self::SERVICE_EXTRACTION,
                'Service Extraction',
                [
                    'extract service',
                    'extract service layer',
                    'move logic to service',
                    'service boundary',
                    'service boundary improvement',
                    'introduce service boundary',
                    'introduce service',
                ],
            ),
            new ArchitectureConcept(
                self::REQUEST_VALIDATION,
                'Request Validation',
                [
                    'add request validation',
                    'form request',
                    'move validation to form request',
                    'validation',
                ],
            ),
            new ArchitectureConcept(
                self::REPOSITORY_PORT,
                'Repository Port',
                [
                    'introduce repository',
                    'introduce repository port',
                    'repository',
                ],
            ),
            new ArchitectureConcept(
                self::CONTROLLER_DEPENDENCY,
                'Controller Dependency Removal',
                [
                    'remove controller dependencies',
                    'controller dependency',
                    'controller owns business logic',
                ],
            ),
            new ArchitectureConcept(
                self::DIRECT_MODEL_USAGE,
                'Direct Model Usage',
                [
                    'direct model usage',
                    'direct model',
                    'eloquent in controller',
                    'model in controller',
                ],
            ),
        ];
    }

    public function canonicalize(string $phrase): ArchitectureConcept
    {
        $normalized = $this->normalize($phrase);

        foreach ($this->concepts as $concept) {
            if ($this->normalize($concept->label) === $normalized || $concept->id === $normalized) {
                return $concept;
            }
            foreach ($concept->aliases as $alias) {
                if ($normalized === $this->normalize($alias) || str_contains($normalized, $this->normalize($alias))) {
                    return $concept;
                }
            }
        }

        return new ArchitectureConcept(
            id: 'custom:'.substr(sha1($normalized), 0, 8),
            label: trim($phrase) !== '' ? trim($phrase) : 'Unknown improvement',
            aliases: [],
        );
    }

    public function concept(string $id): ?ArchitectureConcept
    {
        foreach ($this->concepts as $concept) {
            if ($concept->id === $id) {
                return $concept;
            }
        }

        return null;
    }

    /**
     * @return list<ArchitectureConcept>
     */
    public function all(): array
    {
        return $this->concepts;
    }

    public function labelFor(string $phrase): string
    {
        return $this->canonicalize($phrase)->label;
    }

    public function is(string $phrase, string $conceptId): bool
    {
        return $this->canonicalize($phrase)->id === $conceptId;
    }

    private function normalize(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? $value));
    }
}
