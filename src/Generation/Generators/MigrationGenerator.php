<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\GeneratedFile;
use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

class MigrationGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $columns = [];

        if ($blueprint->usesUuid) {
            $columns[] = "\$table->uuid('uuid')->unique();";
        }

        foreach ($blueprint->fields as $field) {
            $columns[] = $field->migrationColumn();
        }

        $contents = $this->stubs->render('migration', [
            ...$this->baseReplacements($blueprint),
            'columns' => $this->block($columns, 12),
            'softDeletes' => $blueprint->usesSoftDeletes ? "            \$table->softDeletes();\n" : '',
        ]);

        $filename = sprintf(
            '%s_create_%s_table.php',
            now()->format('Y_m_d_His'),
            $blueprint->table(),
        );

        return [
            new GeneratedFile(
                path: database_path('migrations/'.$filename),
                contents: $contents,
                description: 'Migration',
            ),
        ];
    }
}
