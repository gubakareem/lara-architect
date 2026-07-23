<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * GoF Abstract Factory: families of related objects without binding to
 * concrete classes. Distinct from the Eloquent `factory` pattern
 * (Database\\Factories), which only builds model test data.
 */
class AbstractFactoryGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $namespace = $blueprint->namespaceFor('abstract-factory').'\\'.$blueprint->pluralModel();
        $replacements = [
            ...$this->baseReplacements($blueprint),
            'namespace' => $namespace,
        ];

        return [
            $this->classFile($namespace, $blueprint->model().'ComponentFactory', $this->stubs->render('abstract-factory/factory', $replacements), 'Abstract factory'),
            $this->classFile($namespace, 'Standard'.$blueprint->model().'ComponentFactory', $this->stubs->render('abstract-factory/standard-factory', $replacements), 'Standard factory'),
            $this->classFile($namespace, 'Premium'.$blueprint->model().'ComponentFactory', $this->stubs->render('abstract-factory/premium-factory', $replacements), 'Premium factory'),
            $this->classFile($namespace, $blueprint->model().'Notifier', $this->stubs->render('abstract-factory/notifier', $replacements), 'Notifier product'),
            $this->classFile($namespace, $blueprint->model().'Serializer', $this->stubs->render('abstract-factory/serializer', $replacements), 'Serializer product'),
            $this->classFile($namespace, 'Standard'.$blueprint->model().'Notifier', $this->stubs->render('abstract-factory/standard-notifier', $replacements), 'Standard notifier'),
            $this->classFile($namespace, 'Premium'.$blueprint->model().'Notifier', $this->stubs->render('abstract-factory/premium-notifier', $replacements), 'Premium notifier'),
            $this->classFile($namespace, 'Standard'.$blueprint->model().'Serializer', $this->stubs->render('abstract-factory/standard-serializer', $replacements), 'Standard serializer'),
            $this->classFile($namespace, 'Premium'.$blueprint->model().'Serializer', $this->stubs->render('abstract-factory/premium-serializer', $replacements), 'Premium serializer'),
            $this->classFile($namespace, $blueprint->model().'ComponentClient', $this->stubs->render('abstract-factory/client', $replacements), 'Factory client'),
        ];
    }
}
