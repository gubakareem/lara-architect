<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Shared explainability contract — every intelligence type must explain itself.
 */
interface ExplainableInsight
{
    public function kind(): string;

    public function insight(): string;

    public function observed(): string;

    public function whyItMatters(): string;

    public function evidence(): InsightEvidence;

    public function confidence(): string;

    public function overTime(): InsightOverTime;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
