<?php

namespace Tests\Feature\Audit;

use Tests\TestCase;
use App\Models\User;
use App\Models\field\Field;
use App\Models\center\Center;
use App\Models\category\Category;
use App\Models\client\LoanAccount;
use Laravel\Sanctum\Sanctum;
use App\Models\client\SavingAccount;
use App\Models\client\ClientRegistration;
use Spatie\Permission\Models\Permission;
use App\Models\Collections\LoanCollection;
use App\Models\Collections\SavingCollection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InternalAuditReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_audit_report_requires_permission(): void
    {
        $user = User::factory()->create([
            'status' => true,
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        Sanctum::actingAs($user);

        $response = $this
            ->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/audit/internal-report');

        $response->assertStatus(403);
    }

    public function test_internal_audit_report_returns_aggregated_totals_by_category(): void
    {
        $authorizedUser = User::factory()->create([
            'status' => true,
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        Permission::create([
            'name' => 'internal_audit_report_view',
            'group_name' => 'internal_audit_report',
            'guard_name' => 'web',
        ]);
        $authorizedUser->givePermissionTo('internal_audit_report_view');

        $field = Field::create([
            'name' => 'Field A',
            'description' => 'Field A description',
            'status' => true,
            'creator_id' => $authorizedUser->id,
        ]);

        $center = Center::create([
            'field_id' => $field->id,
            'name' => 'Center A',
            'description' => 'Center A description',
            'status' => true,
            'creator_id' => $authorizedUser->id,
        ]);

        $savingCategoryA = Category::create([
            'name' => 'saving_cat_a',
            'group' => 'test',
            'saving' => true,
            'loan' => false,
            'status' => true,
            'is_default' => false,
            'creator_id' => $authorizedUser->id,
        ]);
        $savingCategoryB = Category::create([
            'name' => 'saving_cat_b',
            'group' => 'test',
            'saving' => true,
            'loan' => false,
            'status' => true,
            'is_default' => false,
            'creator_id' => $authorizedUser->id,
        ]);
        $loanCategoryA = Category::create([
            'name' => 'loan_cat_a',
            'group' => 'test',
            'saving' => false,
            'loan' => true,
            'status' => true,
            'is_default' => false,
            'creator_id' => $authorizedUser->id,
        ]);
        $loanCategoryB = Category::create([
            'name' => 'loan_cat_b',
            'group' => 'test',
            'saving' => false,
            'loan' => true,
            'status' => true,
            'is_default' => false,
            'creator_id' => $authorizedUser->id,
        ]);

        $clientOne = $this->createClientRegistration($field, $center, $authorizedUser, 'A-1001', 'Client One');
        $clientTwo = $this->createClientRegistration($field, $center, $authorizedUser, 'A-1002', 'Client Two');
        $clientThree = $this->createClientRegistration($field, $center, $authorizedUser, 'A-1003', 'Client Three');

        $savingAccount = SavingAccount::create([
            'field_id' => $field->id,
            'center_id' => $center->id,
            'category_id' => $savingCategoryA->id,
            'client_registration_id' => $clientOne->id,
            'acc_no' => $clientOne->acc_no,
            'start_date' => now()->toDateString(),
            'duration_date' => now()->addYear()->toDateString(),
            'payable_installment' => 12,
            'payable_deposit' => 500,
            'payable_interest' => 5,
            'is_approved' => true,
            'creator_id' => $authorizedUser->id,
            'approved_by' => $authorizedUser->id,
        ]);

        SavingCollection::create([
            'field_id' => $field->id,
            'center_id' => $center->id,
            'category_id' => $savingCategoryA->id,
            'client_registration_id' => $clientOne->id,
            'saving_account_id' => $savingAccount->id,
            'creator_id' => $authorizedUser->id,
            'approved_by' => $authorizedUser->id,
            'acc_no' => $savingAccount->acc_no,
            'installment' => 1,
            'deposit' => 100,
            'description' => 'Approved savings deposit',
            'is_approved' => true,
        ]);
        SavingCollection::create([
            'field_id' => $field->id,
            'center_id' => $center->id,
            'category_id' => $savingCategoryA->id,
            'client_registration_id' => $clientOne->id,
            'saving_account_id' => $savingAccount->id,
            'creator_id' => $authorizedUser->id,
            'approved_by' => $authorizedUser->id,
            'acc_no' => $savingAccount->acc_no,
            'installment' => 1,
            'deposit' => 50,
            'description' => 'Approved savings deposit',
            'is_approved' => true,
        ]);
        SavingCollection::create([
            'field_id' => $field->id,
            'center_id' => $center->id,
            'category_id' => $savingCategoryA->id,
            'client_registration_id' => $clientOne->id,
            'saving_account_id' => $savingAccount->id,
            'creator_id' => $authorizedUser->id,
            'acc_no' => $savingAccount->acc_no,
            'installment' => 1,
            'deposit' => 999,
            'description' => 'Pending savings deposit',
            'is_approved' => false,
        ]);

        $loanAccountOne = LoanAccount::create([
            'field_id' => $field->id,
            'center_id' => $center->id,
            'category_id' => $loanCategoryA->id,
            'client_registration_id' => $clientTwo->id,
            'creator_id' => $authorizedUser->id,
            'approved_by' => $authorizedUser->id,
            'loan_approved_by' => $authorizedUser->id,
            'acc_no' => $clientTwo->acc_no,
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
            'total_rec_installment' => 3,
            'total_deposited' => 0,
            'total_withdrawn' => 0,
            'total_loan_rec' => 300,
            'total_interest_rec' => 50,
            'is_approved' => true,
            'is_loan_approved' => true,
            'approved_at' => now(),
            'is_loan_approved_at' => now(),
        ]);

        $loanAccountTwo = LoanAccount::create([
            'field_id' => $field->id,
            'center_id' => $center->id,
            'category_id' => $loanCategoryA->id,
            'client_registration_id' => $clientThree->id,
            'creator_id' => $authorizedUser->id,
            'approved_by' => $authorizedUser->id,
            'loan_approved_by' => $authorizedUser->id,
            'acc_no' => $clientThree->acc_no,
            'start_date' => now()->toDateString(),
            'duration_date' => now()->addYear()->toDateString(),
            'loan_given' => 2000,
            'payable_deposit' => 0,
            'payable_installment' => 10,
            'payable_interest' => 20,
            'total_payable_interest' => 400,
            'total_payable_loan_with_interest' => 2400,
            'loan_installment' => 200,
            'interest_installment' => 40,
            'total_rec_installment' => 5,
            'total_deposited' => 0,
            'total_withdrawn' => 0,
            'total_loan_rec' => 1000,
            'total_interest_rec' => 100,
            'is_approved' => true,
            'is_loan_approved' => true,
            'approved_at' => now(),
            'is_loan_approved_at' => now(),
        ]);

        LoanAccount::create([
            'field_id' => $field->id,
            'center_id' => $center->id,
            'category_id' => $loanCategoryA->id,
            'client_registration_id' => $clientThree->id,
            'creator_id' => $authorizedUser->id,
            'acc_no' => 'A-9999',
            'start_date' => now()->toDateString(),
            'duration_date' => now()->addYear()->toDateString(),
            'loan_given' => 9999,
            'payable_deposit' => 0,
            'payable_installment' => 10,
            'payable_interest' => 20,
            'total_payable_interest' => 1000,
            'total_payable_loan_with_interest' => 10999,
            'loan_installment' => 100,
            'interest_installment' => 10,
            'total_loan_rec' => 0,
            'total_interest_rec' => 0,
            'is_approved' => true,
            'is_loan_approved' => false,
        ]);

        LoanCollection::create([
            'field_id' => $field->id,
            'center_id' => $center->id,
            'category_id' => $loanCategoryA->id,
            'client_registration_id' => $clientTwo->id,
            'loan_account_id' => $loanAccountOne->id,
            'creator_id' => $authorizedUser->id,
            'approved_by' => $authorizedUser->id,
            'acc_no' => $loanAccountOne->acc_no,
            'installment' => 1,
            'deposit' => 30,
            'loan' => 25,
            'interest' => 5,
            'total' => 60,
            'description' => 'Approved loan collection',
            'is_approved' => true,
        ]);
        LoanCollection::create([
            'field_id' => $field->id,
            'center_id' => $center->id,
            'category_id' => $loanCategoryA->id,
            'client_registration_id' => $clientThree->id,
            'loan_account_id' => $loanAccountTwo->id,
            'creator_id' => $authorizedUser->id,
            'approved_by' => $authorizedUser->id,
            'acc_no' => $loanAccountTwo->acc_no,
            'installment' => 1,
            'deposit' => 20,
            'loan' => 10,
            'interest' => 10,
            'total' => 40,
            'description' => 'Approved loan collection',
            'is_approved' => true,
        ]);
        LoanCollection::create([
            'field_id' => $field->id,
            'center_id' => $center->id,
            'category_id' => $loanCategoryA->id,
            'client_registration_id' => $clientTwo->id,
            'loan_account_id' => $loanAccountOne->id,
            'creator_id' => $authorizedUser->id,
            'acc_no' => $loanAccountOne->acc_no,
            'installment' => 1,
            'deposit' => 888,
            'loan' => 0,
            'interest' => 0,
            'total' => 888,
            'description' => 'Pending loan collection',
            'is_approved' => false,
        ]);

        Sanctum::actingAs($authorizedUser);

        $response = $this
            ->withHeaders(['Accept-Language' => 'en'])
            ->getJson('/api/audit/internal-report');

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $response->assertJsonPath('data.totals.totalSavingsDepositAll', 150);
        $response->assertJsonPath('data.totals.totalLoanSavingsAll', 50);
        $response->assertJsonPath('data.totals.totalAllSavingsCombined', 200);
        $response->assertJsonPath('data.totals.totalLoanRemainingAll', 1700);
        $response->assertJsonPath('data.totals.profitLoss', 1500);

        $savingsByCategory = collect($response->json('data.savingsByCategory'))->keyBy('categoryId');
        $this->assertCount(2, $savingsByCategory);
        $this->assertSame(150, $savingsByCategory[$savingCategoryA->id]['totalSavingsDeposit']);
        $this->assertSame(0, $savingsByCategory[$savingCategoryB->id]['totalSavingsDeposit']);

        $loanSavingsByCategory = collect($response->json('data.loanSavingsByCategory'))->keyBy('categoryId');
        $this->assertCount(2, $loanSavingsByCategory);
        $this->assertSame(50, $loanSavingsByCategory[$loanCategoryA->id]['totalLoanSavings']);
        $this->assertSame(0, $loanSavingsByCategory[$loanCategoryB->id]['totalLoanSavings']);

        $loansByCategory = collect($response->json('data.loansByCategory'))->keyBy('categoryId');
        $this->assertCount(2, $loansByCategory);

        $loanCategoryAReport = $loansByCategory[$loanCategoryA->id];
        $this->assertSame(3000, $loanCategoryAReport['totalLoanGivenActual']);
        $this->assertSame(1300, $loanCategoryAReport['totalLoanRecovery']);
        $this->assertSame(1700, $loanCategoryAReport['totalLoanRemaining']);
        $this->assertSame(3000, $loanCategoryAReport['totalLoanGivenCalculated']);
        $this->assertFalse($loanCategoryAReport['loanMismatch']);
        $this->assertSame(600, $loanCategoryAReport['totalInterestActual']);
        $this->assertSame(150, $loanCategoryAReport['totalInterestRecovery']);
        $this->assertSame(450, $loanCategoryAReport['totalInterestRemaining']);
        $this->assertSame(600, $loanCategoryAReport['totalInterestCalculated']);
        $this->assertFalse($loanCategoryAReport['interestMismatch']);

        $loanCategoryBReport = $loansByCategory[$loanCategoryB->id];
        $this->assertSame(0, $loanCategoryBReport['totalLoanGivenActual']);
        $this->assertSame(0, $loanCategoryBReport['totalLoanGivenCalculated']);
        $this->assertFalse($loanCategoryBReport['loanMismatch']);
        $this->assertSame(0, $loanCategoryBReport['totalInterestActual']);
        $this->assertSame(0, $loanCategoryBReport['totalInterestCalculated']);
        $this->assertFalse($loanCategoryBReport['interestMismatch']);
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
