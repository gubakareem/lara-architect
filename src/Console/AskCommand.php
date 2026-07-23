<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Console;

use Illuminate\Console\Command;
use KarimAshraf\LaraArchitect\Support\TeamConfig;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureQuestionService;

/**
 * Phase 13 — ask a deterministic architecture question against living knowledge.
 * Read-only. Routes to Rationale / Replay / Ownership / Standards / Learning.
 */
class AskCommand extends Command
{
    protected $signature = 'architect:ask
        {question?* : Architecture question (e.g. "why ProductService exists")}
        {--subject= : Subject / context override (file, class, or area)}
        {--format=console : Output format: console|json}';

    protected $description = 'Ask an architecture question (read-only · deterministic · sourced)';

    public function handle(ArchitectureQuestionService $questions): int
    {
        TeamConfig::apply();

        $parts = $this->argument('question');
        $raw = is_array($parts) ? trim(implode(' ', array_map('strval', $parts))) : '';
        if ($raw === '') {
            $this->components->error('Provide a question, e.g. php artisan architect:ask "why ProductService exists"');

            return self::FAILURE;
        }

        $subject = (string) ($this->option('subject') ?? '');
        $answer = $questions->ask(base_path(), $raw, $subject);

        if ($this->option('format') === 'json') {
            $this->line(json_encode($answer->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');

            return self::SUCCESS;
        }

        if ($answer->question->isChangeRequest) {
            $this->components->warn('Questions do not change code. Use Guidance → Proposal → Controlled Change.');
        }

        $this->newLine();
        $this->components->info($answer->question->normalized);
        $this->line('Intent: '.$answer->question->type->value.' → '.$answer->question->type->routesTo());
        $this->newLine();
        $this->line('<fg=green>Reason:</>');
        $this->line('  '.$answer->reason);
        $this->newLine();

        if ($answer->evidence !== []) {
            $this->line('<fg=green>Evidence:</>');
            foreach ($answer->evidence as $line) {
                $this->line('  - '.$line);
            }
            $this->newLine();
        }

        if ($answer->decision !== '') {
            $this->line('<fg=green>Decision:</>');
            $this->line('  '.$answer->decision);
            $this->newLine();
        }

        $this->line('Confidence: '.$answer->confidence);
        if ($answer->sources !== []) {
            $this->line('<fg=green>Sources:</>');
            foreach ($answer->sources as $source) {
                $this->line('  ✓ '.$source->display());
            }
            $counts = $answer->sourceCounts();
            if ($counts !== []) {
                $parts = [];
                foreach ($counts as $type => $count) {
                    $parts[] = $count.' '.$type.($count === 1 ? '' : 's');
                }
                $this->line('Answer generated from: '.implode(' · ', $parts));
            }
        }

        return self::SUCCESS;
    }
}
