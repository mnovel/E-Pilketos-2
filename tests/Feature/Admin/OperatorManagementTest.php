<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OperatorManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    // ==========================================
    // STORE
    // ==========================================

    public function test_admin_can_create_operator()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.operators.store'), [
            'name'                  => 'Budi Operator',
            'email'                 => 'budi@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'budi@test.com',
            'role'  => UserRole::OPERATOR->value,
        ]);

        $operator = User::where('email', 'budi@test.com')->first();
        $this->assertTrue($operator->hasRole('operator'));

        $this->assertDatabaseHas('activity_logs', ['action' => 'operator.created']);
    }

    public function test_email_must_be_unique()
    {
        $admin = $this->createAdmin();
        $this->createOperator();

        $existing = User::where('role', UserRole::OPERATOR)->first();

        $response = $this->actingAs($admin)->post(route('admin.operators.store'), [
            'name'                  => 'New Operator',
            'email'                 => $existing->email,
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_password_must_be_confirmed()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.operators.store'), [
            'name'                  => 'Budi',
            'email'                 => 'budi@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors('password');
    }

    // ==========================================
    // UPDATE
    // ==========================================

    public function test_admin_can_update_operator()
    {
        $admin = $this->createAdmin();
        $operator = $this->createOperator();

        $response = $this->actingAs($admin)->put(route('admin.operators.update', $operator), [
            'name'  => 'Updated Name',
            'email' => 'updated@test.com',
        ]);

        $response->assertRedirect();
        $operator->refresh();
        $this->assertEquals('Updated Name', $operator->name);
        $this->assertEquals('updated@test.com', $operator->email);
        $this->assertDatabaseHas('activity_logs', ['action' => 'operator.updated']);
    }

    public function test_cannot_update_non_operator_user()
    {
        $admin = $this->createAdmin();
        $voter = $this->createVoter();

        $response = $this->actingAs($admin)->put(route('admin.operators.update', $voter), [
            'name'  => 'Hacked',
            'email' => $voter->email,
        ]);

        $response->assertStatus(404);
    }

    // ==========================================
    // DESTROY
    // ==========================================

    public function test_admin_can_delete_operator_without_sessions()
    {
        $admin = $this->createAdmin();
        $operator = $this->createOperator();
        $operatorId = $operator->id;

        $response = $this->actingAs($admin)
            ->delete(route('admin.operators.destroy', $operator));

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $operatorId]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'operator.deleted']);
    }

    public function test_cannot_delete_operator_with_sessions()
    {
        $admin = $this->createAdmin();
        $operator = $this->createOperator();

        // Bikin session yang di-handle operator ini
        $election = Election::factory()->active()->create();
        ElectionSession::factory()->create([
            'election_id' => $election->id,
            'operator_id' => $operator->id,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.operators.destroy', $operator));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $operator->id]);
    }

    // ==========================================
    // RESET PASSWORD
    // ==========================================

    public function test_admin_can_reset_operator_password()
    {
        $admin = $this->createAdmin();
        $operator = $this->createOperator();

        $response = $this->actingAs($admin)
            ->post(route('admin.operators.reset-password', $operator), [
                'password'              => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertRedirect();
        $operator->refresh();
        $this->assertTrue(Hash::check('newpassword123', $operator->password));
        $this->assertDatabaseHas('activity_logs', ['action' => 'operator.password_reset']);
    }

    public function test_reset_password_requires_confirmation()
    {
        $admin = $this->createAdmin();
        $operator = $this->createOperator();

        $response = $this->actingAs($admin)
            ->post(route('admin.operators.reset-password', $operator), [
                'password'              => 'newpassword123',
                'password_confirmation' => 'different',
            ]);

        $response->assertSessionHasErrors('password');
    }

    // ==========================================
    // AUTHORIZATION
    // ==========================================

    public function test_operator_cannot_manage_other_operators()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->post(route('admin.operators.store'), [
            'name'                  => 'New Op',
            'email'                 => 'new@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertForbidden();
    }
}
