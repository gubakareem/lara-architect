<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use KarimAshraf\LaraArchitect\Generation\Field;
use KarimAshraf\LaraArchitect\Generation\GeneratedFile;
use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;
use KarimAshraf\LaraArchitect\Generation\StubRenderer;

/**
 * Generates a backed enum for every `enum` field (string by default, or int
 * when declared as `status:enum:int`), plus translation entries in
 * lang/{locale}/enums.php for configured locales.
 */
class EnumGenerator extends BaseGenerator
{
    public function __construct(
        StubRenderer $stubs,
        private readonly Filesystem $files,
    ) {
        parent::__construct($stubs);
    }

    public function generate(ModuleBlueprint $blueprint): array
    {
        $files = [];
        $translationEntries = [];

        foreach ($blueprint->enumFields() as $field) {
            $class = $blueprint->enumClassName($field);
            $fqcn = $blueprint->enumClass($field);

            $files[] = $this->classFile(
                $blueprint->namespaceFor('enum'),
                $class,
                $this->stubs->render('enum', [
                    ...$this->baseReplacements($blueprint),
                    'namespace' => $blueprint->namespaceFor('enum'),
                    'class' => $class,
                    'backing' => $field->enumBacking,
                    'methodDocs' => $this->methodDocs($field),
                    'cases' => $this->casesBlock($field),
                ]),
                'Enum',
            );

            $translationEntries[$fqcn] = [
                'class' => $class,
                'cases' => $field->enumCases(),
            ];
        }

        foreach ($this->locales() as $locale) {
            $files[] = $this->translationFile($locale, $translationEntries);
        }

        return $files;
    }

    /**
     * @return list<string>
     */
    private function locales(): array
    {
        $locales = config('lara-architect.enums.locales', ['en']);

        return array_values(array_unique(array_filter($locales)));
    }

    private function methodDocs(Field $field): string
    {
        $lines = array_map(
            static fn (array $case): string => ' * @method bool is'.$case['name'].'()',
            $field->enumCases(),
        );

        return "/**\n".implode("\n", $lines)."\n */";
    }

    private function casesBlock(Field $field): string
    {
        $lines = [];

        foreach ($field->enumCases() as $case) {
            $value = is_int($case['value'])
                ? (string) $case['value']
                : "'".$case['value']."'";

            $lines[] = '    case '.$case['name'].' = '.$value.';';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, array{class: string, cases: list<array{name: string, value: string|int}>}>  $entries
     */
    private function translationFile(string $locale, array $entries): GeneratedFile
    {
        $path = lang_path($locale.'/enums.php');

        return new GeneratedFile(
            path: $path,
            contents: $this->renderMergedTranslations($path, $entries, $locale),
            description: 'Enum translations ('.$locale.')',
            merge: true,
        );
    }

    /**
     * @param  array<string, array{class: string, cases: list<array{name: string, value: string|int}>}>  $entries
     */
    private function renderMergedTranslations(string $path, array $entries, string $locale): string
    {
        /** @var array<string, array<string|int, string>> $existing */
        $existing = [];

        if ($this->files->exists($path)) {
            $loaded = include $path;

            if (is_array($loaded)) {
                $existing = $loaded;
            }
        }

        $metaByFqcn = $entries;

        foreach ($entries as $fqcn => $meta) {
            $labels = [];

            foreach ($meta['cases'] as $case) {
                $labels[$case['value']] = $existing[$fqcn][$case['value']]
                    ?? $this->defaultLabel($case['name'], $case['value'], $locale);
            }

            $existing[$fqcn] = $labels;
        }

        return $this->renderTranslationSource($existing, $metaByFqcn);
    }

    /**
     * @param  array<string, array<string|int, string>>  $map
     * @param  array<string, array{class: string, cases: list<array{name: string, value: string|int}>}>  $metaByFqcn
     */
    private function renderTranslationSource(array $map, array $metaByFqcn): string
    {
        $uses = [];
        $blocks = [];

        foreach ($map as $fqcn => $labels) {
            $short = class_basename($fqcn);
            $uses[] = 'use '.$fqcn.';';

            $caseLines = [];

            if (isset($metaByFqcn[$fqcn])) {
                foreach ($metaByFqcn[$fqcn]['cases'] as $case) {
                    $caseLines[] = sprintf(
                        '        %s::%s->value => %s,',
                        $short,
                        $case['name'],
                        var_export($labels[$case['value']] ?? $this->defaultLabel($case['name'], $case['value'], 'en'), true),
                    );
                }
            } else {
                foreach ($labels as $value => $label) {
                    $caseLines[] = sprintf(
                        '        %s => %s,',
                        var_export($value, true),
                        var_export($label, true),
                    );
                }
            }

            $blocks[] = sprintf(
                "    %s::class => [\n%s\n    ],",
                $short,
                implode("\n", $caseLines),
            );
        }

        $uses = array_values(array_unique($uses));
        sort($uses);

        return "<?php\n\n".implode("\n", $uses)."\n\nreturn [\n".implode("\n\n", $blocks)."\n];\n";
    }

    private function defaultLabel(string $caseName, string|int $value, string $locale): string
    {
        if ($locale === 'ar') {
            return match (strtolower($caseName)) {
                'active' => 'نشط',
                'inactive' => 'غير نشط',
                'draft' => 'مسودة',
                'archived' => 'مؤرشف',
                'available' => 'متاح',
                'unavailable' => 'غير متاح',
                'published' => 'منشور',
                default => Str::headline((string) $value),
            };
        }

        return Str::headline((string) $value);
    }
}
