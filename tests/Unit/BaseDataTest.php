<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Unit;

use KarimAshraf\LaraArchitect\Tests\Fixtures\AuthorData;
use KarimAshraf\LaraArchitect\Tests\Fixtures\PostData;
use KarimAshraf\LaraArchitect\Tests\Fixtures\PostStatus;
use PHPUnit\Framework\TestCase;

class BaseDataTest extends TestCase
{
    public function test_it_hydrates_from_snake_case_arrays(): void
    {
        $data = PostData::fromArray([
            'title' => 'Hello',
            'published' => true,
            'body' => 'World',
        ]);

        $this->assertSame('Hello', $data->title);
        $this->assertTrue($data->published);
        $this->assertSame('World', $data->body);
    }

    public function test_it_applies_defaults_for_missing_keys(): void
    {
        $data = PostData::fromArray(['title' => 'Hello']);

        $this->assertFalse($data->published);
        $this->assertNull($data->body);
    }

    public function test_it_hydrates_nested_data_objects(): void
    {
        $data = PostData::fromArray([
            'title' => 'Hello',
            'author' => ['full_name' => 'Karim Ashraf', 'email' => 'karim@example.com'],
        ]);

        $this->assertInstanceOf(AuthorData::class, $data->author);
        $this->assertSame('Karim Ashraf', $data->author->fullName);
    }

    public function test_to_array_uses_snake_case_keys_and_flattens_nested_objects(): void
    {
        $data = PostData::fromArray([
            'title' => 'Hello',
            'author' => ['full_name' => 'Karim Ashraf'],
        ]);

        $array = $data->toArray();

        $this->assertSame('Hello', $array['title']);
        $this->assertSame('Karim Ashraf', $array['author']['full_name']);
        $this->assertArrayHasKey('published', $array);
    }

    public function test_it_hydrates_backed_enums_from_strings(): void
    {
        $data = PostData::fromArray(['title' => 'Hello', 'status' => 'published']);

        $this->assertSame(PostStatus::Published, $data->status);
        $this->assertSame('published', $data->toArray()['status']);
    }

    public function test_to_filtered_array_drops_null_values(): void
    {
        $data = PostData::fromArray(['title' => 'Hello']);

        $filtered = $data->toFilteredArray();

        $this->assertArrayNotHasKey('body', $filtered);
        $this->assertArrayNotHasKey('author', $filtered);
        $this->assertSame('Hello', $filtered['title']);
    }
}
