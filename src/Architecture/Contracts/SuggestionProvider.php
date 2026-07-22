<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture\Contracts;

use KarimAshraf\LaraArchitect\Architecture\AnalysisResult;
use KarimAshraf\LaraArchitect\Architecture\Suggestion;

/**
 * Reserved extension point for actionable suggestions (v1.6+).
 */
interface SuggestionProvider
{
    /**
     * @return list<Suggestion>
     */
    public function suggest(AnalysisResult $result): array;
}
