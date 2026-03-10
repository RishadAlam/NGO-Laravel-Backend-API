<?php

namespace Tests\Feature\RecycleBin;

use App\Models\field\Field;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RecycleBinFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_delete_moves_field_to_recycle_bin(): void
    {
        $user = $this->createUserWithPermissions(['field_soft_delete', 'recycle_bin_view']);
        $field = Field::create([
            'name' => 'Field for Soft Delete',
            'description' => 'Soft delete test field',
            'creator_id' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $this->withHeaders(['Accept-Language' => 'en'])
            ->deleteJson("/api/fields/{$field->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('fields', ['id' => $field->id]);
    }

    public function test_recycle_bin_folder_listing_returns_module_summary(): void
    {
        $user = $this->createUserWithPermissions(['recycle_bin_view']);
        $field = Field::create([
            'name' => 'Recycle Bin Folder Listing Field',
            'description' => 'List test field',
            'creator_id' => $user->id,
        ]);
        $field->delete();

        Sanctum::actingAs($user);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/recycle-bin/folders');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $folders = collect($response->json('data.folders'));
        $matched = $folders->firstWhere('type', 'field');

        $this->assertNotNull($matched);
        $this->assertSame(1, $matched['total_items']);
        $this->assertNotEmpty($matched['last_deleted_at']);
    }

    public function test_recycle_bin_items_listing_returns_deleted_records(): void
    {
        $user = $this->createUserWithPermissions(['recycle_bin_view']);
        $field = Field::create([
            'name' => 'Recycle Bin Item Listing Field',
            'description' => 'List test field',
            'creator_id' => $user->id,
        ]);
        $field->delete();

        Sanctum::actingAs($user);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/recycle-bin/items?type=field&all=1');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $items = collect($response->json('data.items'));
        $matched = $items->firstWhere('id', $field->id);

        $this->assertNotNull($matched);
        $this->assertSame('field', $matched['type']);
        $this->assertSame($field->name, $matched['display_name']);
    }

    public function test_recycle_bin_restore_restores_soft_deleted_record(): void
    {
        $user = $this->createUserWithPermissions(['recycle_bin_restore']);
        $field = Field::create([
            'name' => 'Restore Me',
            'description' => 'Restore test field',
            'creator_id' => $user->id,
        ]);
        $field->delete();

        Sanctum::actingAs($user);

        $this->withHeaders(['Accept-Language' => 'en'])
            ->postJson("/api/recycle-bin/field/{$field->id}/restore")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('fields', [
            'id' => $field->id,
            'deleted_at' => null,
        ]);
    }

    public function test_recycle_bin_force_delete_permanently_removes_record(): void
    {
        $user = $this->createUserWithPermissions(['recycle_bin_force_delete']);
        $field = Field::create([
            'name' => 'Force Delete Me',
            'description' => 'Force delete test field',
            'creator_id' => $user->id,
        ]);
        $field->delete();

        Sanctum::actingAs($user);

        $this->withHeaders(['Accept-Language' => 'en'])
            ->deleteJson("/api/recycle-bin/field/{$field->id}/force")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('fields', ['id' => $field->id]);
    }

    public function test_user_without_recycle_bin_view_cannot_access_recycle_bin_list(): void
    {
        $user = $this->createUserWithPermissions([]);
        Sanctum::actingAs($user);

        $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/recycle-bin/folders')
            ->assertStatus(403);

        $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/recycle-bin/items?type=field')
            ->assertStatus(403);
    }

    public function test_user_without_recycle_bin_restore_cannot_restore_record(): void
    {
        $user = $this->createUserWithPermissions(['recycle_bin_view']);
        $field = Field::create([
            'name' => 'No Restore Permission Field',
            'description' => 'Unauthorized restore test',
            'creator_id' => $user->id,
        ]);
        $field->delete();

        Sanctum::actingAs($user);

        $this->withHeaders(['Accept-Language' => 'en'])
            ->postJson("/api/recycle-bin/field/{$field->id}/restore")
            ->assertStatus(403);
    }

    public function test_user_without_recycle_bin_force_delete_cannot_force_delete_record(): void
    {
        $user = $this->createUserWithPermissions(['recycle_bin_view']);
        $field = Field::create([
            'name' => 'No Force Delete Permission Field',
            'description' => 'Unauthorized force delete test',
            'creator_id' => $user->id,
        ]);
        $field->delete();

        Sanctum::actingAs($user);

        $this->withHeaders(['Accept-Language' => 'en'])
            ->deleteJson("/api/recycle-bin/field/{$field->id}/force")
            ->assertStatus(403);
    }

    public function test_recycle_bin_rejects_withdrawal_type_filter(): void
    {
        $user = $this->createUserWithPermissions(['recycle_bin_view']);
        Sanctum::actingAs($user);

        $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/recycle-bin/items?type=account_withdrawal')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_recycle_bin_staff_items_include_image_uri_from_legacy_image_field(): void
    {
        $user = $this->createUserWithPermissions(['recycle_bin_view']);
        $staff = User::factory()->create([
            'name' => 'Deleted Staff',
            'email' => 'deleted-staff@example.com',
            'status' => true,
            'image' => 'legacy-staff.jpg',
            'image_uri' => null,
            'password' => 'password',
        ]);
        $staff->delete();

        Sanctum::actingAs($user);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/recycle-bin/items?type=staff&all=1');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $items = collect($response->json('data.items'));
        $matched = $items->firstWhere('id', $staff->id);

        $this->assertNotNull($matched);
        $this->assertNotNull($matched['image_uri'] ?? null);
        $this->assertStringContainsString('/storage/staff/legacy-staff.jpg', $matched['image_uri']);
    }

    private function createUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create([
            'status' => true,
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        foreach ($permissions as $permissionName) {
            Permission::updateOrCreate(
                ['name' => $permissionName],
                [
                    'group_name' => 'recycle_bin',
                    'guard_name' => 'web',
                ]
            );
        }

        if (!empty($permissions)) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }
}
