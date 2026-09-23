<?php

namespace Tests\Unit\Enums;

use App\Enums\ElectionStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ElectionStatusTest extends TestCase
{
    // ==========================================
    // LABEL
    // ==========================================

    #[DataProvider('labelProvider')]
    public function test_label_returns_correct_string(ElectionStatus $status, string $expectedLabel)
    {
        $this->assertEquals($expectedLabel, $status->label());
    }

    public static function labelProvider(): array
    {
        return [
            'draft'     => [ElectionStatus::DRAFT,     'Draft'],
            'active'    => [ElectionStatus::ACTIVE,    'Berlangsung'],
            'closed'    => [ElectionStatus::CLOSED,    'Ditutup'],
            'published' => [ElectionStatus::PUBLISHED, 'Dipublikasi'],
        ];
    }

    // ==========================================
    // COLOR
    // ==========================================

    #[DataProvider('colorProvider')]
    public function test_color_returns_correct_bootstrap_class(ElectionStatus $status, string $expectedColor)
    {
        $this->assertEquals($expectedColor, $status->color());
    }

    public static function colorProvider(): array
    {
        return [
            'draft'     => [ElectionStatus::DRAFT,     'secondary'],
            'active'    => [ElectionStatus::ACTIVE,    'success'],
            'closed'    => [ElectionStatus::CLOSED,    'warning'],
            'published' => [ElectionStatus::PUBLISHED, 'primary'],
        ];
    }

    // ==========================================
    // ICON
    // ==========================================

    #[DataProvider('iconProvider')]
    public function test_icon_returns_correct_bootstrap_icon(ElectionStatus $status, string $expectedIcon)
    {
        $this->assertEquals($expectedIcon, $status->icon());
    }

    public static function iconProvider(): array
    {
        return [
            'draft'     => [ElectionStatus::DRAFT,     'bi-file-earmark'],
            'active'    => [ElectionStatus::ACTIVE,    'bi-broadcast'],
            'closed'    => [ElectionStatus::CLOSED,    'bi-stop-circle'],
            'published' => [ElectionStatus::PUBLISHED, 'bi-megaphone-fill'],
        ];
    }

    // ==========================================
    // HELPERS
    // ==========================================

    public function test_is_draft_returns_true_only_for_draft()
    {
        $this->assertTrue(ElectionStatus::DRAFT->isDraft());
        $this->assertFalse(ElectionStatus::ACTIVE->isDraft());
        $this->assertFalse(ElectionStatus::CLOSED->isDraft());
        $this->assertFalse(ElectionStatus::PUBLISHED->isDraft());
    }

    public function test_is_active_returns_true_only_for_active()
    {
        $this->assertFalse(ElectionStatus::DRAFT->isActive());
        $this->assertTrue(ElectionStatus::ACTIVE->isActive());
        $this->assertFalse(ElectionStatus::CLOSED->isActive());
        $this->assertFalse(ElectionStatus::PUBLISHED->isActive());
    }

    public function test_is_closed_returns_true_only_for_closed()
    {
        $this->assertFalse(ElectionStatus::DRAFT->isClosed());
        $this->assertFalse(ElectionStatus::ACTIVE->isClosed());
        $this->assertTrue(ElectionStatus::CLOSED->isClosed());
        $this->assertFalse(ElectionStatus::PUBLISHED->isClosed());
    }

    public function test_is_published_returns_true_only_for_published()
    {
        $this->assertFalse(ElectionStatus::DRAFT->isPublished());
        $this->assertFalse(ElectionStatus::ACTIVE->isPublished());
        $this->assertFalse(ElectionStatus::CLOSED->isPublished());
        $this->assertTrue(ElectionStatus::PUBLISHED->isPublished());
    }

    public function test_is_editable_returns_true_only_for_draft()
    {
        $this->assertTrue(ElectionStatus::DRAFT->isEditable());
        $this->assertFalse(ElectionStatus::ACTIVE->isEditable());
        $this->assertFalse(ElectionStatus::CLOSED->isEditable());
        $this->assertFalse(ElectionStatus::PUBLISHED->isEditable());
    }

    public function test_is_voting_returns_true_only_for_active()
    {
        $this->assertFalse(ElectionStatus::DRAFT->isVoting());
        $this->assertTrue(ElectionStatus::ACTIVE->isVoting());
        $this->assertFalse(ElectionStatus::CLOSED->isVoting());
        $this->assertFalse(ElectionStatus::PUBLISHED->isVoting());
    }

    public function test_is_final_returns_true_for_closed_and_published()
    {
        $this->assertFalse(ElectionStatus::DRAFT->isFinal());
        $this->assertFalse(ElectionStatus::ACTIVE->isFinal());
        $this->assertTrue(ElectionStatus::CLOSED->isFinal());
        $this->assertTrue(ElectionStatus::PUBLISHED->isFinal());
    }

    // ==========================================
    // STATIC
    // ==========================================

    public function test_values_returns_all_case_values()
    {
        $values = ElectionStatus::values();

        $this->assertIsArray($values);
        $this->assertContains('draft', $values);
        $this->assertContains('active', $values);
        $this->assertContains('closed', $values);
        $this->assertContains('published', $values);
        $this->assertCount(4, $values);
    }

    public function test_options_returns_value_to_label_mapping()
    {
        $options = ElectionStatus::options();

        $this->assertIsArray($options);
        $this->assertEquals('Draft', $options['draft']);
        $this->assertEquals('Berlangsung', $options['active']);
        $this->assertEquals('Ditutup', $options['closed']);
        $this->assertEquals('Dipublikasi', $options['published']);
    }
}
