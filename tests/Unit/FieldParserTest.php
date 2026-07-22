<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Unit;

use InvalidArgumentException;
use KarimAshraf\LaraArchitect\Generation\Field;
use KarimAshraf\LaraArchitect\Generation\FieldParser;
use PHPUnit\Framework\TestCase;

class FieldParserTest extends TestCase
{
    public function test_it_parses_names_types_and_modifiers(): void
    {
        $fields = FieldParser::parse('name:string, price:decimal:nullable, sku:string:unique, active:boolean');

        $this->assertCount(4, $fields);

        $this->assertSame('name', $fields[0]->name);
        $this->assertSame('string', $fields[0]->type);
        $this->assertFalse($fields[0]->nullable);

        $this->assertSame('price', $fields[1]->name);
        $this->assertSame('decimal', $fields[1]->type);
        $this->assertTrue($fields[1]->nullable);

        $this->assertTrue($fields[2]->unique);
        $this->assertSame('boolean', $fields[3]->type);
    }

    public function test_it_defaults_to_string_and_snake_cases_names(): void
    {
        $fields = FieldParser::parse('metaTitle');

        $this->assertSame('meta_title', $fields[0]->name);
        $this->assertSame('string', $fields[0]->type);
    }

    public function test_it_returns_empty_array_for_empty_input(): void
    {
        $this->assertSame([], FieldParser::parse(null));
        $this->assertSame([], FieldParser::parse('  '));
    }

    public function test_it_rejects_unknown_types(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown field type or modifier [banana]');

        FieldParser::parse('name:banana');
    }

    public function test_enum_fields_are_supported(): void
    {
        $fields = FieldParser::parse('status:enum:nullable');

        $this->assertTrue($fields[0]->isEnum());
        $this->assertSame('string', $fields[0]->enumBacking);
        $this->assertSame("\$table->string('status')->nullable();", $fields[0]->migrationColumn());
        $this->assertSame(['nullable'], $fields[0]->validationRules());
    }

    public function test_int_backed_enum_fields_are_supported(): void
    {
        $fields = FieldParser::parse('status:enum:int:nullable');

        $this->assertTrue($fields[0]->isIntEnum());
        $this->assertSame('int', $fields[0]->enumBacking);
        $this->assertSame("\$table->unsignedTinyInteger('status')->nullable();", $fields[0]->migrationColumn());
        $this->assertSame([
            ['name' => 'Inactive', 'value' => 0],
            ['name' => 'Active', 'value' => 1],
        ], $fields[0]->enumCases());
    }

    public function test_int_bool_and_bigint_are_type_aliases(): void
    {
        $fields = FieldParser::parse('parent_id:int, active:bool, views:bigint');

        $this->assertSame('integer', $fields[0]->type);
        $this->assertSame('boolean', $fields[1]->type);
        $this->assertSame('biginteger', $fields[2]->type);
    }

    public function test_int_after_enum_is_backing_not_integer_type(): void
    {
        $fields = FieldParser::parse('name:json,parent_id:int,status:enum:int,order:int');

        $this->assertSame('json', $fields[0]->type);
        $this->assertSame('integer', $fields[1]->type);
        $this->assertTrue($fields[2]->isIntEnum());
        $this->assertSame('integer', $fields[3]->type);
    }

    public function test_field_produces_migration_column_and_rules(): void
    {
        $field = new Field(name: 'price', type: 'decimal', nullable: true);

        $this->assertSame("\$table->decimal('price', 12, 2)->nullable();", $field->migrationColumn());
        $this->assertSame(['nullable', 'numeric'], $field->validationRules());
        $this->assertSame(['sometimes', 'nullable', 'numeric'], $field->validationRules(forUpdate: true));
        $this->assertSame('decimal:2', $field->cast());
        $this->assertSame('?float', $field->phpType());
    }
}
