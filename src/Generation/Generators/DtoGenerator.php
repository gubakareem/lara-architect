<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\Field;
use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

class DtoGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        // Required parameters must come before optional ones.
        $fields = $blueprint->fields;

        usort($fields, static fn (Field $a, Field $b) => $a->nullable <=> $b->nullable);

        $imports = [];

        if ($blueprint->usesEnums()) {
            foreach ($blueprint->enumFields() as $field) {
                $imports[] = 'use '.$blueprint->enumClass($field).';';
            }
        }

        sort($imports);

        $properties = array_map(
            static function (Field $field) use ($blueprint) {
                $type = $field->isEnum() && $blueprint->usesEnums()
                    ? ($field->nullable ? '?' : '').$blueprint->enumClassName($field)
                    : $field->phpType();

                $line = sprintf('public readonly %s $%s', $type, $field->camelName());

                return $field->nullable ? $line.' = null,' : $line.',';
            },
            $fields,
        );

        $contents = $this->stubs->render('dto', [
            ...$this->baseReplacements($blueprint),
            'namespace' => $blueprint->namespaceFor('dto'),
            'imports' => $imports === [] ? '' : implode("\n", $imports)."\n",
            'properties' => $this->block($properties),
        ]);

        return [
            $this->classFile(
                $blueprint->namespaceFor('dto'),
                $blueprint->model().'Data',
                $contents,
                'Data object',
            ),
        ];
    }
}
