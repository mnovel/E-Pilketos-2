<?php

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    // ==========================================
    // LABEL
    // ==========================================

    #[DataProvider('labelProvider')]
    public function test_label_returns_correct_string(UserRole $role, string $expectedLabel)
    {
        $this->assertEquals($expectedLabel, $role->label());
    }

    public static function labelProvider(): array
    {
        return [
            'admin'    => [UserRole::ADMIN,    'Administrator'],
            'operator' => [UserRole::OPERATOR, 'Operator'],
            'voter'    => [UserRole::VOTER,    'Pemilih'],
        ];
    }

    // ==========================================
    // COLOR
    // ==========================================

    #[DataProvider('colorProvider')]
    public function test_color_returns_correct_bootstrap_class(UserRole $role, string $expectedColor)
    {
        $this->assertEquals($expectedColor, $role->color());
    }

    public static function colorProvider(): array
    {
        return [
            'admin'    => [UserRole::ADMIN,    'primary'],
            'operator' => [UserRole::OPERATOR, 'info'],
            'voter'    => [UserRole::VOTER,    'success'],
        ];
    }

    // ==========================================
    // ICON
    // ==========================================

    #[DataProvider('iconProvider')]
    public function test_icon_returns_correct_bootstrap_icon(UserRole $role, string $expectedIcon)
    {
        $this->assertEquals($expectedIcon, $role->icon());
    }

    public static function iconProvider(): array
    {
        return [
            'admin'    => [UserRole::ADMIN,    'bi-shield-check'],
            'operator' => [UserRole::OPERATOR, 'bi-person-badge'],
            'voter'    => [UserRole::VOTER,    'bi-person'],
        ];
    }

    // ==========================================
    // HELPERS
    // ==========================================

    public function test_is_admin_returns_true_only_for_admin()
    {
        $this->assertTrue(UserRole::ADMIN->isAdmin());
        $this->assertFalse(UserRole::OPERATOR->isAdmin());
        $this->assertFalse(UserRole::VOTER->isAdmin());
    }

    public function test_is_operator_returns_true_only_for_operator()
    {
        $this->assertFalse(UserRole::ADMIN->isOperator());
        $this->assertTrue(UserRole::OPERATOR->isOperator());
        $this->assertFalse(UserRole::VOTER->isOperator());
    }

    public function test_is_voter_returns_true_only_for_voter()
    {
        $this->assertFalse(UserRole::ADMIN->isVoter());
        $this->assertFalse(UserRole::OPERATOR->isVoter());
        $this->assertTrue(UserRole::VOTER->isVoter());
    }

    public function test_can_operate_device_returns_true_for_admin_and_operator()
    {
        $this->assertTrue(UserRole::ADMIN->canOperateDevice());
        $this->assertTrue(UserRole::OPERATOR->canOperateDevice());
        $this->assertFalse(UserRole::VOTER->canOperateDevice());
    }

    public function test_requires_verification_returns_true_only_for_voter()
    {
        $this->assertFalse(UserRole::ADMIN->requiresVerification());
        $this->assertFalse(UserRole::OPERATOR->requiresVerification());
        $this->assertTrue(UserRole::VOTER->requiresVerification());
    }

    // ==========================================
    // STATIC
    // ==========================================

    public function test_values_returns_all_case_values()
    {
        $values = UserRole::values();

        $this->assertIsArray($values);
        $this->assertContains('admin', $values);
        $this->assertContains('operator', $values);
        $this->assertContains('voter', $values);
        $this->assertCount(3, $values);
    }

    public function test_options_returns_value_to_label_mapping()
    {
        $options = UserRole::options();

        $this->assertEquals('Administrator', $options['admin']);
        $this->assertEquals('Operator', $options['operator']);
        $this->assertEquals('Pemilih', $options['voter']);
    }
}
