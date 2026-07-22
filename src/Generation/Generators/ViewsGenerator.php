<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\GeneratedFile;
use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * Blade views for web (non-API) modules: index, create, show, edit.
 */
class ViewsGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $dir = resource_path('views/'.$blueprint->viewPath());
        $views = ['index', 'create', 'show', 'edit'];
        $files = [];

        foreach ($views as $view) {
            $files[] = new GeneratedFile(
                path: $dir.'/'.$view.'.blade.php',
                contents: $this->stubs->render('views/'.$view, [
                    ...$this->baseReplacements($blueprint),
                    'title' => $blueprint->pluralModel(),
                ]),
                description: 'View ('.$view.')',
            );
        }

        return $files;
    }
}
