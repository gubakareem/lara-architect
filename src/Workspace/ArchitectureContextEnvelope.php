<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Final pre-AI contract — constitution between core and future consumers.
 *
 * Analyzer owns facts from code. Memory owns history. Humans own intent.
 * AI owns explanation only.
 *
 * Evidence before intelligence · Intent before automation · Memory before AI.
 * AI speaks from architectural memory — never replaces it.
 */
final readonly class ArchitectureContextEnvelope
{
    public const SCHEMA_VERSION = '1.0';

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $identity
     * @param  array<string, mixed>  $evidence
     * @param  array<string, mixed>  $decisions
     * @param  array<string, mixed>  $history
     * @param  array<string, mixed>  $guidance
     * @param  list<string>  $allowedQuestions  Typed intents (ArchitectureQuestionType values)
     * @param  array<string, mixed>  $boundary  can_explain / can_modify are the ownership line
     * @param  array<string, mixed>|null  $brief
     */
    public function __construct(
        public array $context,
        public array $identity,
        public array $evidence,
        public array $decisions,
        public array $history,
        public array $guidance,
        public array $allowedQuestions,
        public array $boundary,
        public ?array $brief = null,
        public string $schemaVersion = self::SCHEMA_VERSION,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'kind' => 'architecture_context_envelope',
            'context' => $this->context,
            'identity' => $this->identity,
            'evidence' => $this->evidence,
            'decisions' => $this->decisions,
            'history' => $this->history,
            'guidance' => $this->guidance,
            'brief' => $this->brief,
            'allowed_questions' => $this->allowedQuestions,
            'boundary' => $this->boundary,
        ];
    }
}
