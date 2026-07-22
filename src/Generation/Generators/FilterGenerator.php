<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\Field;
use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * Generates an ArchitectQueryFilter with sensible filter methods per field:
 * a search() across text fields, like-matches for strings, exact matches
 * for booleans/integers/enums and min/max ranges for numeric fields.
 */
class FilterGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $contents = $this->stubs->render('filter', [
            ...$this->baseReplacements($blueprint),
            'namespace' => $blueprint->namespaceFor('filter'),
            'methods' => $this->methods($blueprint),
        ]);

        return [
            $this->classFile(
                $blueprint->namespaceFor('filter'),
                $blueprint->model().'Filter',
                $contents,
                'Query filter',
            ),
        ];
    }

    private function methods(ModuleBlueprint $blueprint): string
    {
        $blocks = [];

        if ($searchable = $this->searchableFields($blueprint)) {
            $blocks[] = $this->searchMethod($searchable);
        }

        foreach ($blueprint->fields as $field) {
            $block = match (true) {
                $field->type === 'boolean' => $this->booleanMethod($field),
                $field->isEnum() => $this->exactMethod($field),
                in_array($field->type, ['integer', 'biginteger', 'foreignid'], true) => $this->exactMethod($field),
                in_array($field->type, ['decimal', 'float'], true) => $this->rangeMethods($field),
                in_array($field->type, ['date', 'datetime'], true) => $this->dateRangeMethods($field),
                default => null,
            };

            if ($block !== null) {
                $blocks[] = $block;
            }
        }

        return implode("\n\n", $blocks);
    }

    /**
     * @return list<Field>
     */
    private function searchableFields(ModuleBlueprint $blueprint): array
    {
        return array_values(array_filter(
            $blueprint->fields,
            static fn (Field $field) => in_array($field->type, ['string', 'text'], true),
        ));
    }

    /**
     * @param  list<Field>  $fields
     */
    private function searchMethod(array $fields): string
    {
        $conditions = [];

        foreach ($fields as $index => $field) {
            $conditions[] = sprintf(
                "            \$query->%s('%s', 'like', \"%%{\$value}%%\")",
                $index === 0 ? 'where' : 'orWhere',
                $field->name,
            );
        }

        return <<<PHP
            public function search(string \$value): void
            {
                \$this->builder->where(function (\$query) use (\$value) {
        {$this->joinConditions($conditions)}
                });
            }
        PHP;
    }

    private function exactMethod(Field $field): string
    {
        return <<<PHP
            public function {$field->camelName()}(string \$value): void
            {
                \$this->builder->where('{$field->name}', \$value);
            }
        PHP;
    }

    private function booleanMethod(Field $field): string
    {
        return <<<PHP
            public function {$field->camelName()}(string \$value): void
            {
                \$this->builder->where('{$field->name}', filter_var(\$value, FILTER_VALIDATE_BOOLEAN));
            }
        PHP;
    }

    private function rangeMethods(Field $field): string
    {
        return <<<PHP
            public function {$field->camelName()}Min(string \$value): void
            {
                \$this->builder->where('{$field->name}', '>=', (float) \$value);
            }

            public function {$field->camelName()}Max(string \$value): void
            {
                \$this->builder->where('{$field->name}', '<=', (float) \$value);
            }
        PHP;
    }

    private function dateRangeMethods(Field $field): string
    {
        return <<<PHP
            public function {$field->camelName()}From(string \$value): void
            {
                \$this->builder->whereDate('{$field->name}', '>=', \$value);
            }

            public function {$field->camelName()}To(string \$value): void
            {
                \$this->builder->whereDate('{$field->name}', '<=', \$value);
            }
        PHP;
    }

    /**
     * @param  list<string>  $conditions
     */
    private function joinConditions(array $conditions): string
    {
        return implode(";\n", $conditions).';';
    }
}
