<?php

namespace Tests\Feature\Admin;

use App\Models\ClassRoom;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassRoomManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    // ==========================================
    // STORE
    // ==========================================

    public function test_admin_can_create_class()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.classes.store'), [
            'name'      => 'X-IPA-1',
            'tingkat'   => 'X',
            'jurusan'   => 'IPA',
            'rombel'    => '1',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('classes', [
            'name'    => 'X-IPA-1',
            'tingkat' => 'X',
        ]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'class.created']);
    }

    public function test_name_is_uppercased_on_create()
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('admin.classes.store'), [
            'name'    => 'x-ipa-1',
            'tingkat' => 'x',
            'jurusan' => 'ipa',
        ]);

        $this->assertDatabaseHas('classes', [
            'name'    => 'X-IPA-1',
            'tingkat' => 'X',
            'jurusan' => 'IPA',
        ]);
    }

    public function test_class_name_must_be_unique()
    {
        $admin = $this->createAdmin();
        ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $response = $this->actingAs($admin)->post(route('admin.classes.store'), [
            'name'    => 'X-IPA-1',
            'tingkat' => 'X',
        ]);

        $response->assertSessionHasErrors('name');
    }

    // ==========================================
    // UPDATE
    // ==========================================

    public function test_admin_can_update_class()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create(['name' => 'X-IPA-1', 'rombel' => '1']);

        $response = $this->actingAs($admin)->put(route('admin.classes.update', $class), [
            'name'    => 'X-IPA-1',
            'tingkat' => 'X',
            'rombel'  => '2',
        ]);

        $response->assertRedirect();
        $this->assertEquals('2', $class->fresh()->rombel);
        $this->assertDatabaseHas('activity_logs', ['action' => 'class.updated']);
    }

    public function test_can_update_class_with_same_name()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create(['name' => 'X-IPA-1']);

        $response = $this->actingAs($admin)->put(route('admin.classes.update', $class), [
            'name'    => 'X-IPA-1',
            'tingkat' => 'X',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    // ==========================================
    // DESTROY
    // ==========================================

    public function test_admin_can_delete_class_without_users()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();
        $classId = $class->id;

        $response = $this->actingAs($admin)
            ->delete(route('admin.classes.destroy', $class));

        $response->assertRedirect();
        $this->assertDatabaseMissing('classes', ['id' => $classId]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'class.deleted']);
    }

    public function test_cannot_delete_class_with_users()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create();

        // Bikin 1 user di kelas ini
        $this->createVoter($class);

        $response = $this->actingAs($admin)
            ->delete(route('admin.classes.destroy', $class));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('classes', ['id' => $class->id]);
    }

    // ==========================================
    // TOGGLE ACTIVE
    // ==========================================

    public function test_toggle_active_deactivates_class()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)
            ->post(route('admin.classes.toggle-active', $class));

        $response->assertRedirect();
        $this->assertFalse($class->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'class.toggled']);
    }

    public function test_toggle_active_activates_class()
    {
        $admin = $this->createAdmin();
        $class = ClassRoom::factory()->create(['is_active' => false]);

        $this->actingAs($admin)->post(route('admin.classes.toggle-active', $class));

        $this->assertTrue($class->fresh()->is_active);
    }

    // ==========================================
    // AUTHORIZATION
    // ==========================================

    public function test_operator_cannot_manage_classes()
    {
        $operator = $this->createOperator();

        $response = $this->actingAs($operator)->post(route('admin.classes.store'), [
            'name'    => 'X-IPA-99',
            'tingkat' => 'X',
        ]);

        $response->assertForbidden();
    }
}
