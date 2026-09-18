<?php

namespace Tests\Feature\Collections;

use App\Models\accounts\Account;
use App\Models\AppConfig;
use App\Models\category\Category;
use App\Models\center\Center;
use App\Models\client\ClientRegistration;
use App\Models\client\LoanAccount;
use App\Models\client\SavingAccount;
use App\Models\Collections\LoanCollection;
use App\Models\Collections\SavingCollection;
use App\Models\field\Field;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/** Approval must credit a collection once, including retries and mixed batches. */
class CollectionApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $collector;

    private User $approver;

    private Account $cash;

    private Field $field;

    private Center $center;

    private array $accounts = [];

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        if (! $app->environment('testing')
            || $app['config']->get('database.default') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.driver') !== 'sqlite'
            || $app['config']->get('database.connections.sqlite.database') !== ':memory:'
            || ! empty($app['config']->get('database.connections.sqlite.url'))
            || $app['config']->get('mail.default') !== 'array'
            || $app['config']->get('cache.default') !== 'array'
            || $app['config']->get('session.driver') !== 'array') {
            throw new RuntimeException('Refusing to run outside isolated testing / SQLite memory / array mail, cache, session.');
        }

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withHeaders(['Accept-Language' => 'en']);
        $this->collector = User::factory()->create(['status' => true, 'email_verified_at' => now(), 'password' => 'synthetic-test-password']);
        $this->approver = User::factory()->create(['status' => true, 'email_verified_at' => now(), 'password' => 'synthetic-test-password']);
        $read = ['internal_audit_report_view'];
        foreach (['saving', 'loan'] as $type) {
            foreach (['regular', 'pending'] as $scope) {
                $read[] = "{$scope}_{$type}_collection_list_view_as_admin";
            }
        }
        $all = array_merge($read, [
            'permission_to_do_saving_collection', 'permission_to_do_loan_collection',
            'regular_saving_collection_approval', 'pending_saving_collection_approval',
            'regular_loan_collection_approval', 'pending_loan_collection_approval',
        ]);
        foreach ($all as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['group_name' => 'release_verification']);
        }
        $this->collector->givePermissionTo(array_merge($read, ['permission_to_do_saving_collection', 'permission_to_do_loan_collection']));
        $this->approver->givePermissionTo(array_merge($read, ['pending_saving_collection_approval', 'pending_loan_collection_approval']));
        Sanctum::actingAs($this->collector);

        $this->field = Field::create(['name' => 'Synthetic verification field', 'creator_id' => $this->collector->id]);
        $this->center = Center::create(['field_id' => $this->field->id, 'name' => 'Synthetic verification center', 'creator_id' => $this->collector->id]);
        $this->cash = Account::create(['name' => 'Synthetic verification cash', 'acc_no' => 'TEST-CASH', 'creator_id' => $this->collector->id]);
        foreach (['saving', 'loan'] as $type) {
            AppConfig::create(['meta_key' => "{$type}_collection_approval", 'meta_value' => false]);
            $category = Category::create([
                'name' => 'synthetic_'.$type, 'group' => 'verification',
                'saving' => $type === 'saving', 'loan' => $type === 'loan',
                'status' => true, 'is_default' => false, 'creator_id' => $this->collector->id,
            ]);
            $client = ClientRegistration::create([
                'field_id' => $this->field->id, 'center_id' => $this->center->id,
                'acc_no' => $type === 'saving' ? '910001' : '910002',
                'name' => 'Synthetic '.$type.' client', 'father_name' => 'Test Father', 'mother_name' => 'Test Mother',
                'nid' => $type === 'saving' ? '9100000000001' : '9100000000002',
                'dob' => '1990-01-01', 'occupation' => 'worker', 'religion' => 'islam', 'gender' => 'male',
                'primary_phone' => '01700000000', 'image' => 'synthetic.png', 'image_uri' => 'https://example.invalid/synthetic.png',
                'share' => 0, 'present_address' => [], 'permanent_address' => [],
                'is_approved' => true, 'approved_by' => $this->approver->id, 'approved_at' => now(), 'creator_id' => $this->collector->id,
            ]);
            $fields = [
                'field_id' => $this->field->id, 'center_id' => $this->center->id, 'category_id' => $category->id,
                'client_registration_id' => $client->id, 'acc_no' => $client->acc_no,
                'start_date' => now()->toDateString(), 'duration_date' => now()->addYear()->toDateString(),
                'payable_installment' => 10, 'payable_deposit' => 100,
                'is_approved' => true, 'approved_by' => $this->approver->id, 'approved_at' => now(), 'creator_id' => $this->collector->id,
            ];
            if ($type === 'saving') {
                $this->accounts[$type] = SavingAccount::create($fields);
            } else {
                $this->accounts[$type] = LoanAccount::create($fields + [
                    'loan_given' => 1000, 'total_payable_interest' => 100, 'total_payable_loan_with_interest' => 1100,
                    'loan_installment' => 100, 'interest_installment' => 10,
                    'is_loan_approved' => true, 'loan_approved_by' => $this->approver->id, 'is_loan_approved_at' => now(),
                ]);
            }
        }
    }

    public function test_saving_pending_approval_reconciles_balances_history_and_reports(): void
    {
        $this->verifyWorkflow('saving');
    }

    public function test_loan_pending_approval_reconciles_balances_history_and_reports(): void
    {
        $this->verifyWorkflow('loan');
    }

    public function test_saving_approval_replay_must_not_double_balances(): void
    {
        $collection = $this->verifyWorkflow('saving');
        $this->postJson('/api/collection/saving/approved', ['approvedList' => [$collection->id]]);
        $this->assertBalances('saving', true);
    }

    public function test_loan_approval_replay_must_not_double_balances(): void
    {
        $collection = $this->verifyWorkflow('loan');
        $this->postJson('/api/collection/loan/approved', ['approvedList' => [$collection->id]]);
        $this->assertBalances('loan', true);
    }

    public function test_saving_automatic_approval_reconciles_balances_and_reports(): void
    {
        $this->verifyAutomaticApproval('saving');
    }

    public function test_loan_automatic_approval_reconciles_balances_and_reports(): void
    {
        $this->verifyAutomaticApproval('loan');
    }

    public function test_previous_day_pending_queues_include_then_remove_approved_collections(): void
    {
        foreach (['saving', 'loan'] as $type) {
            $collection = $this->storePending($type);
            $collection->created_at = now()->subDay();
            $collection->save();
            $this->assertQueueCount($type, 1, 'pending');
            $this->assertQueueCount($type, 0, 'regular');
        }
        Sanctum::actingAs($this->approver);
        foreach (['saving', 'loan'] as $type) {
            $model = $this->collectionModel($type);
            $this->postJson("/api/collection/{$type}/approved", ['approvedList' => [$model::firstOrFail()->id]])->assertOk();
            $this->assertQueueCount($type, 0, 'pending');
        }
        $this->assertSame(710, (int) $this->cash->fresh()->balance);
    }

    public static function collectionTypes(): array
    {
        return [['saving'], ['loan']];
    }

    /** @dataProvider collectionTypes */
    public function test_overlapping_batches_credit_only_new_pending_collections(string $type): void
    {
        $first = $this->storePending($type);
        $second = $this->storePending($type);
        $third = $this->storePending($type);
        Sanctum::actingAs($this->approver);

        $this->postJson("/api/collection/{$type}/approved", ['approvedList' => [$first->id, $second->id]])->assertOk();
        $this->postJson("/api/collection/{$type}/approved", ['approvedList' => [$second->id, $third->id]])->assertOk();

        $this->assertCredits($type, 3);
        $this->assertHistoryCount($type, 3);
        $this->assertQueueCount($type, 0);
    }

    /** @dataProvider collectionTypes */
    public function test_duplicate_ids_in_one_request_credit_only_once(string $type): void
    {
        $collection = $this->storePending($type);
        Sanctum::actingAs($this->approver);
        $this->postJson("/api/collection/{$type}/approved", ['approvedList' => [$collection->id, $collection->id]])->assertOk();
        $this->assertCredits($type, 1);
    }

    /** @dataProvider collectionTypes */
    public function test_replay_preserves_first_approval_metadata_and_all_counters(string $type): void
    {
        $collection = $this->verifyWorkflow($type);
        $collection->forceFill(['approved_at' => now()->subHour()])->save();
        $original = $collection->fresh()->only(['approved_by', 'approved_at', 'updated_at']);
        $this->collector->givePermissionTo("pending_{$type}_collection_approval");
        Sanctum::actingAs($this->collector);

        $this->postJson("/api/collection/{$type}/approved", ['approvedList' => [$collection->id]])
            ->assertOk()->assertJsonPath('success', true);

        $this->assertEquals($original, $collection->fresh()->only(['approved_by', 'approved_at', 'updated_at']));
        $this->assertCredits($type, 1);
        $this->assertHistoryCount($type, 1);
        $this->assertAudit($type, true);
    }

    /** @dataProvider collectionTypes */
    public function test_replay_of_automatic_approval_does_not_credit_again(string $type): void
    {
        AppConfig::where('meta_key', "{$type}_collection_approval")->firstOrFail()->update(['meta_value' => true]);
        $collection = $this->storePending($type);
        $original = $collection->fresh()->only(['approved_by', 'approved_at']);
        Sanctum::actingAs($this->approver);

        $this->postJson("/api/collection/{$type}/approved", ['approvedList' => [$collection->id]])->assertOk();

        $this->assertCredits($type, 1);
        $this->assertEquals($original, $collection->fresh()->only(['approved_by', 'approved_at']));
    }

    /** @dataProvider collectionTypes */
    public function test_unknown_and_soft_deleted_ids_are_not_credited(string $type): void
    {
        $collection = $this->storePending($type);
        $collection->delete();
        Sanctum::actingAs($this->approver);

        $this->postJson("/api/collection/{$type}/approved", ['approvedList' => [$collection->id, 999999]])->assertOk();

        $this->assertCredits($type, 0);
        $this->assertFalse((bool) $collection->fresh()->is_approved);
    }

    /** @dataProvider collectionTypes */
    public function test_failure_during_cash_credit_rolls_back_approval_and_member_counters(string $type): void
    {
        $collection = $this->storePending($type);
        $second = $this->storePending($type);
        Sanctum::actingAs($this->approver);
        $this->withoutExceptionHandling();
        $injectedFailure = false;
        $cashWrites = 0;
        DB::listen(function ($query) use (&$injectedFailure, &$cashWrites) {
            if (str_starts_with($query->sql, 'update "accounts"')) {
                $cashWrites++;
            }
            if (! $injectedFailure && $cashWrites === 2) {
                $injectedFailure = true;
                throw new RuntimeException('Synthetic failure after cash update');
            }
        });

        try {
            $this->postJson("/api/collection/{$type}/approved", ['approvedList' => [$collection->id, $second->id]]);
            $this->fail('The injected cash-write failure must propagate');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic failure after cash update', $exception->getMessage());
        }

        $this->assertTrue($injectedFailure);
        $this->assertCredits($type, 0);
        $this->assertFalse((bool) $collection->fresh()->is_approved);
        $this->assertNull($collection->fresh()->approved_by);
        $this->assertNull($collection->fresh()->approved_at);
        $this->assertFalse((bool) $second->fresh()->is_approved);
        $this->assertNull($second->fresh()->approved_by);
        $this->assertNull($second->fresh()->approved_at);
    }

    private function assertCredits(string $type, int $count): void
    {
        $account = $this->accounts[$type]->fresh();
        $actual = [
            'member_balance' => (int) $account->balance,
            'member_deposits' => (int) $account->total_deposited,
            'installments' => (int) ($type === 'saving' ? $account->total_installment : $account->total_rec_installment),
            'cash_balance' => (int) $this->cash->fresh()->balance,
            'cash_deposits' => (int) $this->cash->fresh()->total_deposit,
        ];
        $expected = [
            'member_balance' => $count * ($type === 'saving' ? 500 : 100),
            'member_deposits' => $count * ($type === 'saving' ? 500 : 100),
            'installments' => $count,
            'cash_balance' => $count * ($type === 'saving' ? 500 : 210),
            'cash_deposits' => $count * ($type === 'saving' ? 500 : 210),
        ];
        if ($type === 'loan') {
            $actual += ['principal' => (int) $account->total_loan_rec, 'interest' => (int) $account->total_interest_rec];
            $expected += ['principal' => $count * 100, 'interest' => $count * 10];
        }
        $this->assertSame($expected, $actual);
    }

    private function verifyWorkflow(string $type)
    {
        $collection = $this->storePending($type);
        $this->assertFalse((bool) $collection->is_approved);
        $this->assertBalances($type, false);
        $this->assertHistoryCount($type, 0);
        $this->assertQueueCount($type, 1);
        $this->assertAudit($type, false);
        $this->postJson("/api/collection/{$type}/approved", ['approvedList' => [$collection->id]])->assertForbidden();
        $this->assertBalances($type, false);
        $this->assertFalse((bool) $collection->fresh()->is_approved);
        Sanctum::actingAs($this->approver);
        $this->postJson("/api/collection/{$type}/approved", ['approvedList' => [$collection->id]])->assertOk()->assertJsonPath('success', true);
        $this->assertTrue((bool) $collection->fresh()->is_approved);
        $this->assertSame($this->approver->id, (int) $collection->fresh()->approved_by);
        $this->assertBalances($type, true);
        $this->assertHistoryCount($type, 1);
        $this->assertQueueCount($type, 0);
        $this->assertAudit($type, true);

        return $collection;
    }

    private function verifyAutomaticApproval(string $type): void
    {
        AppConfig::where('meta_key', "{$type}_collection_approval")->firstOrFail()->update(['meta_value' => true]);
        $collection = $this->storePending($type);
        $this->assertTrue((bool) $collection->is_approved);
        $this->assertSame($this->collector->id, (int) $collection->approved_by);
        $this->assertBalances($type, true);
        $this->assertHistoryCount($type, 1);
        $this->assertQueueCount($type, 0);
        $this->assertAudit($type, true);
    }

    private function storePending(string $type)
    {
        $account = $this->accounts[$type];
        $payload = [
            'field_id' => $this->field->id, 'center_id' => $this->center->id, 'category_id' => $account->category_id,
            $type.'_account_id' => $account->id, 'client_registration_id' => $account->client_registration_id,
            'account_id' => $this->cash->id, 'acc_no' => $account->acc_no,
            'installment' => 1, 'deposit' => $type === 'saving' ? 500 : 100, 'description' => 'Synthetic verification only',
        ];
        if ($type === 'loan') {
            $payload += ['loan' => 100, 'interest' => 10, 'total' => 210];
        }
        $this->postJson("/api/collection/{$type}", $payload)->assertOk()->assertJsonPath('success', true);
        $model = $this->collectionModel($type);

        return $model::latest('id')->firstOrFail();
    }

    private function assertBalances(string $type, bool $approved): void
    {
        $account = $this->accounts[$type]->fresh();
        $deposit = $approved ? ($type === 'saving' ? 500 : 100) : 0;
        $cash = $approved ? ($type === 'saving' ? 500 : 210) : 0;
        $this->assertSame($deposit, (int) $account->balance, "{$type} client balance must reflect one collection only");
        $this->assertSame($deposit, (int) $account->total_deposited);
        $this->assertSame($cash, (int) $this->cash->fresh()->balance, 'Cash balance must reconcile with approved collections');
        $summary = $this->getJson("/api/client/registration/{$type}/short-summery/{$account->id}")->assertOk();
        $summary->assertJsonPath('data.balance', $deposit);
        if ($type === 'saving') {
            $this->assertSame($approved ? 1 : 0, (int) $account->total_installment);
        } else {
            $this->assertSame($approved ? 1 : 0, (int) $account->total_rec_installment);
            $this->assertSame($approved ? 100 : 0, (int) $account->total_loan_rec);
            $this->assertSame($approved ? 10 : 0, (int) $account->total_interest_rec);
            $summary->assertJsonPath('data.loan_remaining', $approved ? 900 : 1000);
            $summary->assertJsonPath('data.interest_remaining', $approved ? 90 : 100);
        }
    }

    private function assertHistoryCount(string $type, int $count): void
    {
        $id = $this->accounts[$type]->id;
        $this->getJson("/api/collection/{$type}?{$type}_account_id={$id}")->assertOk()->assertJsonCount($count, 'data');
    }

    private function assertQueueCount(string $type, int $count, string $scope = 'regular'): void
    {
        $response = $this->getJson("/api/collection/{$type}/{$scope}/collection-sheet")->assertOk();
        $category = collect($response->json('data.report'))->firstWhere('id', $this->accounts[$type]->category_id);
        $this->assertCount($count, $category[$type.'_collection']);
        if ($count === 1) {
            $this->assertSame($type === 'saving' ? 500 : 100, (int) $category[$type.'_collection'][0]['deposit']);
        }
        if ($scope === 'regular') {
            $categoryId = $this->accounts[$type]->category_id;
            $fieldId = $this->field->id;
            $sheet = $this->getJson("/api/collection/{$type}/regular/collection-sheet/{$categoryId}/{$fieldId}")->assertOk();
            $rows = collect($sheet->json('data.collections'))
                ->flatMap(fn ($center) => $center[$type.'_account'])
                ->flatMap(fn ($account) => $account[$type.'_collection']);
            $this->assertCount($count, $rows, 'Regular account collection-sheet rows must match category queue totals');
        }
    }

    private function assertAudit(string $type, bool $approved): void
    {
        $response = $this->getJson('/api/audit/internal-report')->assertOk();
        $key = $type === 'saving' ? 'totalSavingsDepositAll' : 'totalLoanSavingsAll';
        $response->assertJsonPath('data.totals.'.$key, $approved ? ($type === 'saving' ? 500 : 100) : 0);
        if ($type === 'loan') {
            $row = collect($response->json('data.loansByCategory'))->firstWhere('categoryId', $this->accounts[$type]->category_id);
            $this->assertSame($approved ? 100 : 0, $row['totalLoanRecovery']);
            $this->assertSame($approved ? 10 : 0, $row['totalInterestRecovery']);
            $this->assertFalse($row['loanMismatch']);
            $this->assertFalse($row['interestMismatch']);
        }
    }

    private function collectionModel(string $type): string
    {
        return $type === 'saving' ? SavingCollection::class : LoanCollection::class;
    }
}
