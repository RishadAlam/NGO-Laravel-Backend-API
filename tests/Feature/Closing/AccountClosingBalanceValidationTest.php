<?php

namespace Tests\Feature\Closing;

use App\Models\accounts\Account;
use App\Models\accounts\IncomeCategory;
use App\Models\AppConfig;
use App\Models\category\Category;
use App\Models\category\CategoryConfig;
use App\Models\center\Center;
use App\Models\client\AccountFeesCategory;
use App\Models\client\ClientRegistration;
use App\Models\client\LoanAccount;
use App\Models\client\LoanAccountClosing;
use App\Models\client\SavingAccount;
use App\Models\client\SavingAccountClosing;
use App\Models\field\Field;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccountClosingBalanceValidationTest extends TestCase
{
    use RefreshDatabase;

    private const BALANCE_ERROR = 'The account main balance must be zero after all deductions before closing.';

    private User $user;

    private Field $field;

    private Center $center;

    private Account $feeAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'status' => true,
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        $permissions = [
            'client_saving_account_delete',
            'client_loan_account_delete',
            'pending_req_to_delete_saving_acc_approval',
            'pending_req_to_delete_loan_acc_approval',
        ];

        foreach ($permissions as $permissionName) {
            Permission::create([
                'name' => $permissionName,
                'group_name' => 'client_account_closing',
                'guard_name' => 'web',
            ]);
        }

        $this->user->givePermissionTo($permissions);

        Sanctum::actingAs($this->user);

        $this->field = Field::create([
            'name' => 'Closing Validation Field',
            'description' => 'Field used for account closing validation tests',
            'creator_id' => $this->user->id,
        ]);

        $this->center = Center::create([
            'field_id' => $this->field->id,
            'name' => 'Closing Validation Center',
            'description' => 'Center used for account closing validation tests',
            'creator_id' => $this->user->id,
        ]);

        $this->feeAccount = Account::create([
            'name' => 'Closing Fee Account',
            'acc_no' => 'CLOSING-FEE-001',
            'creator_id' => $this->user->id,
        ]);

        AccountFeesCategory::create([
            'name' => 'closing_fee',
            'is_default' => true,
            'creator_id' => $this->user->id,
        ]);

        IncomeCategory::create([
            'name' => 'closing_fee',
            'is_default' => true,
            'creator_id' => $this->user->id,
        ]);
    }

    public function test_saving_closing_rejects_forged_zero_payload_when_persisted_balance_after_fee_is_not_zero(): void
    {
        $account = $this->createSavingAccount(balance: 150, closingFee: 100);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->postJson('/api/closing/saving', $this->savingClosingPayload($account, closingFee: 150));

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.balance', self::BALANCE_ERROR);

        $this->assertDatabaseMissing('saving_account_closings', [
            'saving_account_id' => $account->id,
        ]);
        $this->assertDatabaseHas('saving_accounts', [
            'id' => $account->id,
            'deleted_at' => null,
        ]);
    }

    public function test_loan_closing_rejects_forged_zero_payload_when_persisted_balance_after_fee_is_not_zero(): void
    {
        $account = $this->createLoanAccount(balance: 50, closingFee: 100);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->postJson('/api/closing/loan', $this->loanClosingPayload($account, closingFee: 50));

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.balance', self::BALANCE_ERROR);

        $this->assertDatabaseMissing('loan_account_closings', [
            'loan_account_id' => $account->id,
        ]);
        $this->assertDatabaseHas('loan_accounts', [
            'id' => $account->id,
            'deleted_at' => null,
        ]);
    }

    public function test_pending_saving_closing_is_revalidated_and_rejected_when_live_balance_changes_before_approval(): void
    {
        $account = $this->createSavingAccount(balance: 100, closingFee: 100);

        $this->withHeaders(['Accept-Language' => 'en'])
            ->postJson('/api/closing/saving', $this->savingClosingPayload($account, closingFee: 100))
            ->assertOk()
            ->assertJsonPath('success', true);

        $closing = SavingAccountClosing::where('saving_account_id', $account->id)->firstOrFail();
        $account->increment('total_deposited', 1);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->putJson("/api/closing/saving/approved/{$closing->id}");

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.balance', self::BALANCE_ERROR);

        $this->assertDatabaseHas('saving_accounts', [
            'id' => $account->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('saving_account_closings', [
            'id' => $closing->id,
            'is_approved' => false,
            'approved_by' => null,
        ]);
        $this->assertDatabaseCount('saving_withdrawals', 0);
    }

    public function test_pending_loan_closing_is_revalidated_and_rejected_when_live_balance_changes_before_approval(): void
    {
        $account = $this->createLoanAccount(balance: 100, closingFee: 100);

        $this->withHeaders(['Accept-Language' => 'en'])
            ->postJson('/api/closing/loan', $this->loanClosingPayload($account, closingFee: 100))
            ->assertOk()
            ->assertJsonPath('success', true);

        $closing = LoanAccountClosing::where('loan_account_id', $account->id)->firstOrFail();
        $account->increment('total_deposited', 1);

        $response = $this->withHeaders(['Accept-Language' => 'en'])
            ->putJson("/api/closing/loan/approved/{$closing->id}");

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.balance', self::BALANCE_ERROR);

        $this->assertDatabaseHas('loan_accounts', [
            'id' => $account->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('loan_account_closings', [
            'id' => $closing->id,
            'is_approved' => false,
            'approved_by' => null,
        ]);
        $this->assertDatabaseCount('loan_saving_withdrawals', 0);
    }

    public function test_exact_zero_saving_balance_closes_immediately_without_main_balance_withdrawal(): void
    {
        $account = $this->createSavingAccount(balance: 100, closingFee: 100);
        $this->enableImmediateClosing('saving_account_closing_approval');

        $this->withHeaders(['Accept-Language' => 'en'])
            ->postJson('/api/closing/saving', $this->savingClosingPayload($account, closingFee: 100))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('saving_withdrawals', 0);
        $this->assertSoftDeleted('saving_accounts', ['id' => $account->id]);
    }

    public function test_exact_zero_loan_balance_closes_immediately_without_main_balance_withdrawal(): void
    {
        $account = $this->createLoanAccount(balance: 100, closingFee: 100);
        $this->enableImmediateClosing('loan_account_closing_approval');

        $this->withHeaders(['Accept-Language' => 'en'])
            ->postJson('/api/closing/loan', $this->loanClosingPayload($account, closingFee: 100))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('loan_saving_withdrawals', 0);
        $this->assertSoftDeleted('loan_accounts', ['id' => $account->id]);
    }

    private function createSavingAccount(int $balance, int $closingFee): SavingAccount
    {
        $category = $this->createCategory('saving', $closingFee);
        $client = $this->createClientRegistration('SAV-'.$balance);

        return SavingAccount::create([
            'field_id' => $this->field->id,
            'center_id' => $this->center->id,
            'category_id' => $category->id,
            'client_registration_id' => $client->id,
            'acc_no' => $client->acc_no,
            'start_date' => now()->toDateString(),
            'duration_date' => now()->addYear()->toDateString(),
            'payable_installment' => 12,
            'payable_deposit' => 100,
            'payable_interest' => 0,
            'total_deposited' => $balance,
            'total_withdrawn' => 0,
            'is_approved' => true,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
            'creator_id' => $this->user->id,
        ]);
    }

    private function createLoanAccount(int $balance, int $closingFee): LoanAccount
    {
        $category = $this->createCategory('loan', $closingFee);
        $client = $this->createClientRegistration('LOAN-'.$balance);

        return LoanAccount::create([
            'field_id' => $this->field->id,
            'center_id' => $this->center->id,
            'category_id' => $category->id,
            'client_registration_id' => $client->id,
            'creator_id' => $this->user->id,
            'approved_by' => $this->user->id,
            'loan_approved_by' => $this->user->id,
            'acc_no' => $client->acc_no,
            'start_date' => now()->toDateString(),
            'duration_date' => now()->addYear()->toDateString(),
            'loan_given' => 1000,
            'payable_deposit' => 100,
            'payable_installment' => 10,
            'payable_interest' => 10,
            'total_payable_interest' => 100,
            'total_payable_loan_with_interest' => 1100,
            'loan_installment' => 100,
            'interest_installment' => 10,
            'total_rec_installment' => 10,
            'total_deposited' => $balance,
            'total_withdrawn' => 0,
            'total_loan_rec' => 1000,
            'total_interest_rec' => 100,
            'is_approved' => true,
            'is_loan_approved' => true,
            'approved_at' => now(),
            'is_loan_approved_at' => now(),
        ]);
    }

    private function createCategory(string $type, int $closingFee): Category
    {
        $category = Category::create([
            'name' => $type.'_closing_validation',
            'group' => 'account_closing_validation',
            'saving' => $type === 'saving',
            'loan' => $type === 'loan',
            'status' => true,
            'is_default' => false,
            'creator_id' => $this->user->id,
        ]);

        CategoryConfig::create([
            'category_id' => $category->id,
            's_reg_fee_acc_id' => $this->feeAccount->id,
            's_col_fee_acc_id' => $this->feeAccount->id,
            'l_reg_fee_acc_id' => $this->feeAccount->id,
            'l_col_fee_acc_id' => $this->feeAccount->id,
            's_with_fee_acc_id' => $this->feeAccount->id,
            'ls_with_fee_acc_id' => $this->feeAccount->id,
            'saving_acc_closing_fee' => $type === 'saving' ? $closingFee : 0,
            'loan_acc_closing_fee' => $type === 'loan' ? $closingFee : 0,
        ]);

        return $category;
    }

    private function createClientRegistration(string $accNo): ClientRegistration
    {
        return ClientRegistration::create([
            'field_id' => $this->field->id,
            'center_id' => $this->center->id,
            'acc_no' => $accNo,
            'name' => 'Closing Validation Client',
            'father_name' => 'Father Name',
            'mother_name' => 'Mother Name',
            'nid' => '1000000000001',
            'dob' => now()->subYears(30)->toDateString(),
            'occupation' => 'worker',
            'religion' => 'islam',
            'gender' => 'male',
            'primary_phone' => '01700000000',
            'image' => 'avatar.png',
            'image_uri' => 'https://example.com/avatar.png',
            'share' => 0,
            'present_address' => [],
            'permanent_address' => [],
            'is_approved' => true,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
            'creator_id' => $this->user->id,
        ]);
    }

    private function savingClosingPayload(SavingAccount $account, int $closingFee): array
    {
        return [
            'account_id' => $account->id,
            'balance' => 0,
            'interest' => 0,
            'total_balance' => 0,
            'closing_fee' => $closingFee,
            'closing_fee_acc_id' => $this->feeAccount->id,
            'description' => 'Close saving account',
        ];
    }

    private function loanClosingPayload(LoanAccount $account, int $closingFee): array
    {
        return [
            'account_id' => $account->id,
            'balance' => 0,
            'total_balance' => 0,
            'closing_fee' => $closingFee,
            'closing_fee_acc_id' => $this->feeAccount->id,
            'description' => 'Close loan account',
        ];
    }

    private function enableImmediateClosing(string $configKey): void
    {
        AppConfig::create([
            'meta_key' => $configKey,
            'meta_value' => true,
        ]);
    }
}
