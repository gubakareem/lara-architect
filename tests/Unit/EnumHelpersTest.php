<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Unit;

use KarimAshraf\LaraArchitect\Enums\Concerns\EnumHelpers;
use KarimAshraf\LaraArchitect\Tests\Fixtures\PostStatus;
use KarimAshraf\LaraArchitect\Tests\TestCase;

enum ReviewState: string
{
    use EnumHelpers;

    case InReview = 'in_review';
    case Approved = 'approved';

    public function label(): string
    {
        return strtoupper($this->value);
    }
}

class EnumHelpersTest extends TestCase
{
    public function test_values_returns_all_case_values(): void
    {
        $this->assertSame(['draft', 'published'], PostStatus::values());
    }

    public function test_options_maps_values_to_headline_labels(): void
    {
        $this->assertSame(
            ['draft' => 'Draft', 'published' => 'Published'],
            PostStatus::options(),
        );
    }

    public function test_is_and_is_not_compare_cases(): void
    {
        $this->assertTrue(PostStatus::Draft->is(PostStatus::Draft));
        $this->assertFalse(PostStatus::Draft->is(PostStatus::Published));
        $this->assertTrue(PostStatus::Draft->isNot(PostStatus::Published));
        $this->assertFalse(PostStatus::Draft->isNot(PostStatus::Draft));
    }

    public function test_label_can_be_overridden_on_the_enum(): void
    {
        $this->assertSame('IN_REVIEW', ReviewState::InReview->label());
        $this->assertSame(
            ['in_review' => 'IN_REVIEW', 'approved' => 'APPROVED'],
            ReviewState::options(),
        );
    }

    public function test_is_case_magic_methods(): void
    {
        $this->assertTrue(PostStatus::Draft->isDraft());
        $this->assertFalse(PostStatus::Draft->isPublished());
    }
}
