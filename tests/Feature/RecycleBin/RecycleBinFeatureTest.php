<?php

namespace Tests\Feature\RecycleBin;

use App\Models\center\Center;
use App\Models\category\Category;
use App\Models\client\ClientRegistration;
use App\Models\client\LoanAccount;
use App\Models\client\SavingAccount;
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

    public function test_client_registration_restore_does_not_restore_child_accounts(): void
    {
        $user = $this->createUserWithPermissions(['recycle_bin_restore']);
        $field = Field::create([
            'name' => 'Restore Parent Field',
            'description' => 'Parent restore dependency field',
            'status' => true,
            'creator_id' => $user->id,
        ]);
        $center = Center::create([
            'field_id' => $field->id,
            'name' => 'Restore Parent Center',
            'description' => 'Parent restore dependency center',
            'status' => true,
            'creator_id' => $user->id,
        ]);
        $savingCategory = Category::create([
            'name' => 'restore_parent_saving_category',
            'group' => 'restore_parent_test',
            'saving' => true,
            'loan' => false,
            'status' => true,
            'is_default' => false,
            'creator_id' => $user->id,
        ]);
        $loanCategory = Category::create([
            'name' => 'restore_parent_loan_category',
            'group' => 'restore_parent_test',
            'saving' => false,
            'loan' => true,
            'status' => true,
            'is_default' => false,
            'creator_id' => $user->id,
        ]);
        $registration = $this->createClientRegistration(
            $field,
            $center,
            $user,
            'RB-REG-1001',
            'Recycle Parent Client'
        );
        $savingAccount = SavingAccount::create([
            'field_id' => $field->id,
            'center_id' => $center->id,
            'category_id' => $savingCategory->id,
            'client_registration_id' => $registration->id,
            'acc_no' => 'RB-SAV-1001',
            'start_date' => now()->toDateString(),
            'duration_date' => now()->addYear()->toDateString(),
            'payable_installment' => 12,
            'payable_deposit' => 500,
            'payable_interest' => 5,
            'is_approved' => true,
            'creator_id' => $user->id,
            'approved_by' => $user->id,
        ]);
        $loanAccount = LoanAccount::create([
            'field_id' => $field->id,
            'center_id' => $center->id,
            'category_id' => $loanCategory->id,
            'client_registration_id' => $registration->id,
            'creator_id' => $user->id,
            'approved_by' => $user->id,
            'loan_approved_by' => $user->id,
            'acc_no' => 'RB-LOAN-1001',
            'start_date' => now()->toDateString(),
            'duration_date' => now()->addYear()->toDateString(),
            'loan_given' => 1000,
            'payable_deposit' => 0,
            'payable_installment' => 10,
            'payable_interest' => 20,
            'total_payable_interest' => 200,
            'total_payable_loan_with_interest' => 1200,
            'loan_installment' => 100,
            'interest_installment' => 20,
            'total_rec_installment' => 0,
            'total_deposited' => 0,
            'total_withdrawn' => 0,
            'total_loan_rec' => 0,
            'total_interest_rec' => 0,
            'is_approved' => true,
            'is_loan_approved' => true,
            'approved_at' => now(),
            'is_loan_approved_at' => now(),
        ]);

        $savingAccount->delete();
        $loanAccount->delete();
        $registration->delete();

        Sanctum::actingAs($user);

        $this->withHeaders(['Accept-Language' => 'en'])
            ->postJson("/api/recycle-bin/client_registration/{$registration->id}/restore")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('client_registrations', [
            'id' => $registration->id,
            'deleted_at' => null,
        ]);
        $this->assertSoftDeleted('saving_accounts', ['id' => $savingAccount->id]);
        $this->assertSoftDeleted('loan_accounts', ['id' => $loanAccount->id]);
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

    public function test_staff_force_delete_requires_reassign_user_id(): void
    {
        $user = $this->createUserWithPermissions(['recycle_bin_force_delete']);
        $staff = User::factory()->create([
            'status' => true,
            'email_verified_at' => now(),
            'password' => 'password',
        ]);
        $staff->delete();

        Sanctum::actingAs($user);

        $this->withHeaders(['Accept-Language' => 'en'])
            ->deleteJson("/api/recycle-bin/staff/{$staff->id}/force")
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_staff_force_delete_reassigns_associated_records(): void
    {
        $actor = $this->createUserWithPermissions(['recycle_bin_force_delete']);
        $staffToDelete = User::factory()->create([
            'status' => true,
            'email_verified_at' => now(),
            'password' => 'password',
        ]);
        $replacementStaff = User::factory()->create([
            'status' => true,
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        $field = Field::create([
            'name' => 'Staff Reassign Field',
            'description' => 'Staff force delete reassign test',
            'status' => true,
            'creator_id' => $staffToDelete->id,
        ]);
        $center = Center::create([
            'field_id' => $field->id,
            'name' => 'Staff Reassign Center',
            'description' => 'Staff reassign center',
            'status' => true,
            'creator_id' => $staffToDelete->id,
        ]);
        $category = Category::create([
            'name' => 'staff_reassign_category',
            'group' => 'staff_reassign_test',
            'saving' => true,
            'loan' => false,
            'status' => true,
            'is_default' => false,
            'creator_id' => $staffToDelete->id,
        ]);
        $registration = $this->createClientRegistration(
            $field,
            $center,
            $staffToDelete,
            'RB-STF-1001',
            'Staff Reassign Client'
        );
        $savingAccount = SavingAccount::create([
            'field_id' => $field->id,
            'center_id' => $center->id,
            'category_id' => $category->id,
            'client_registration_id' => $registration->id,
            'acc_no' => 'RB-STF-SAV-1001',
            'start_date' => now()->toDateString(),
            'duration_date' => now()->addYear()->toDateString(),
            'payable_installment' => 12,
            'payable_deposit' => 500,
            'payable_interest' => 5,
            'is_approved' => true,
            'creator_id' => $staffToDelete->id,
            'approved_by' => $staffToDelete->id,
        ]);

        $staffToDelete->delete();

        Sanctum::actingAs($actor);

        $this->withHeaders(['Accept-Language' => 'en'])
            ->deleteJson(
                "/api/recycle-bin/staff/{$staffToDelete->id}/force?reassign_user_id={$replacementStaff->id}"
            )
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('users', ['id' => $staffToDelete->id]);
        $this->assertDatabaseHas('fields', [
            'id' => $field->id,
            'creator_id' => $replacementStaff->id,
        ]);
        $this->assertDatabaseHas('saving_accounts', [
            'id' => $savingAccount->id,
            'creator_id' => $replacementStaff->id,
            'approved_by' => $replacementStaff->id,
        ]);
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

    private function createClientRegistration(
        Field $field,
        Center $center,
        User $creator,
        string $accNo,
        string $name
    ): ClientRegistration {
        return ClientRegistration::create([
            'field_id' => $field->id,
            'center_id' => $center->id,
            'acc_no' => $accNo,
            'name' => $name,
            'father_name' => 'Father ' . $name,
            'husband_name' => '',
            'mother_name' => 'Mother ' . $name,
            'nid' => (string) fake()->unique()->numberBetween(1000000000000, 9999999999999),
            'dob' => now()->subYears(30)->toDateString(),
            'occupation' => 'worker',
            'religion' => 'islam',
            'gender' => 'male',
            'primary_phone' => fake()->unique()->numerify('017########'),
            'secondary_phone' => '',
            'image' => 'avatar.png',
            'image_uri' => 'https://example.com/avatar.png',
            'signature' => null,
            'signature_uri' => null,
            'share' => 0,
            'annual_income' => 0,
            'bank_acc_no' => '',
            'bank_check_no' => '',
            'present_address' => [
                'street_address' => 'Road 1',
                'city' => 'City',
                'word_no' => '1',
                'post_office' => 'Post Office',
                'police_station' => 'Police Station',
                'district' => 'District',
                'division' => 'Division',
            ],
            'permanent_address' => [
                'street_address' => 'Road 1',
                'city' => 'City',
                'word_no' => '1',
                'post_office' => 'Post Office',
                'police_station' => 'Police Station',
                'district' => 'District',
                'division' => 'Division',
            ],
            'is_approved' => true,
            'creator_id' => $creator->id,
            'approved_by' => $creator->id,
        ]);
    }
}
