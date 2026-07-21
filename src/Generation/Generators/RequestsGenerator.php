<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\Field;
use KarimAshraf\LaraArchitect\Generation\GeneratedFile;
use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * Generates Store and Update form requests. Unique fields get a proper
 * Rule::unique() that ignores the bound model on update, and enum fields
 * are validated with Rule::enum() against the generated enum class.
 */
class RequestsGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $namespace = $blueprint->namespaceFor('request').'\\'.$blueprint->pluralModel();

        return [
            $this->requestFile($blueprint, $namespace, forUpdate: false),
            $this->requestFile($blueprint, $namespace, forUpdate: true),
        ];
    }

    private function requestFile(ModuleBlueprint $blueprint, string $namespace, bool $forUpdate): GeneratedFile
    {
        $hasUnique = array_filter($blueprint->fields, static fn (Field $f) => $f->unique) !== [];
        $usesEnums = $blueprint->usesEnums();

        $imports = [];

        if ($hasUnique || $usesEnums) {
            $imports[] = 'use Illuminate\\Validation\\Rule;';
        }

        if ($usesEnums) {
            foreach ($blueprint->enumFields() as $field) {
                $imports[] = 'use '.$blueprint->enumClass($field).';';
            }
        }

        sort($imports);

        $rules = array_map(
            fn (Field $field) => sprintf(
                "'%s' => %s,",
                $field->name,
                $this->ruleSource($blueprint, $field, $forUpdate),
            ),
            $blueprint->fields,
        );

        $contents = $this->stubs->render($forUpdate ? 'update-request' : 'store-request', [
            ...$this->baseReplacements($blueprint),
            'namespace' => $namespace,
            'imports' => $imports === [] ? '' : implode("\n", $imports)."\n",
            'rules' => $this->block($rules, 12),
        ]);

        $class = ($forUpdate ? 'Update' : 'Store').$blueprint->model().'Request';

        return $this->classFile($namespace, $class, $contents, ($forUpdate ? 'Update' : 'Store').' request');
    }

    private function ruleSource(ModuleBlueprint $blueprint, Field $field, bool $forUpdate): string
    {
        $rules = array_map(
            static fn (string $rule) => "'".$rule."'",
            $field->validationRules($forUpdate),
        );

        if ($field->isEnum()) {
            $rules[] = $blueprint->usesEnums()
                ? sprintf('Rule::enum(%s::class)', $blueprint->enumClassName($field))
                : "'string'";
        }

        if ($field->unique) {
            $unique = sprintf("Rule::unique('%s', '%s')", $blueprint->table(), $field->name);

            if ($forUpdate) {
                $unique .= sprintf("->ignore(\$this->route('%s'))", $blueprint->modelVariable());
            }

            $rules[] = $unique;
        }

        return '['.implode(', ', $rules).']';
    }
}
