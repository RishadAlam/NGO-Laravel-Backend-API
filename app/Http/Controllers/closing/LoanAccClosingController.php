<?php

namespace App\Http\Controllers\closing;

use App\Models\AppConfig;
use Illuminate\Http\Request;
use App\Models\client\LoanAccount;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\category\CategoryConfig;
use App\Models\client\LoanAccountClosing;
use App\Http\Requests\ClientAccClosing\StoreLoanAccountClosingRequest;

class LoanAccClosingController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('permission:pending_req_to_delete_loan_acc_list_view|pending_req_to_delete_loan_acc_list_view_as_admin')->only('index');
        $this->middleware('can:client_loan_account_delete')->only('store');
        $this->middleware('can:pending_req_to_delete_loan_acc_approval')->only('approved');
        $this->middleware('can:pending_req_to_delete_loan_acc_delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $closing = LoanAccountClosing::pendingClosings()->get();
        return create_response(null, $closing);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLoanAccountClosingRequest $request)
    {
        $data = (object) $request->validated();

        if ($data->total_balance < 0) {
            return create_validation_error_response(__('customValidations.accounts.insufficient_balance'), 'balance');
        }

        $isExits = LoanAccountClosing::where('loan_account_id', $data->account_id)->first();
        if (!empty($isExits)) {
            return create_validation_error_response(__('customValidations.common.request_already_exist'));
        }

        $account = LoanAccount::with('Category:id,name,is_default')->find($data->account_id);
        if ($account->total_loan_remaining > 0) {
            return create_validation_error_response(__('customValidations.client.loan.loan_err'), 'total_loan_remaining');
        }
        if ($account->total_interest_remaining > 0) {
            return create_validation_error_response(__('customValidations.client.loan.interest_err'), 'total_interest_remaining');
        }

        return DB::transaction(function () use ($data, $account) {
            $isApproved = AppConfig::get_config('loan_account_closing_approval');
            LoanAccountClosing::create(LoanAccountClosing::setFieldMap($data, $account, true, $isApproved));

            if ($isApproved) {
                LoanAccountClosing::handleApprovedAccountClosing($account, $data);
                return create_response(__('customValidations.client.loan.delete'));
            } else {
                return create_response(__('customValidations.client.loan.delete_request'));
            }
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $account = LoanAccount::approve()
            ->clientRegistration('id', 'name')
            ->find($id, ['id', 'client_registration_id', 'category_id', 'balance', 'loan_given', 'payable_installment', 'total_payable_interest', 'total_payable_loan_with_interest', 'total_rec_installment', 'total_loan_rec', 'total_interest_rec', 'total_loan_remaining', 'total_interest_remaining']);

        if (empty($account)) {
            return create_validation_error_response(__('customValidations.client.loan.not_found'));
        }

        $categoryConf = CategoryConfig::categoryID($account->category_id)
            ->first(['loan_acc_closing_fee', 'l_col_fee_acc_id']);

        return create_response(
            null,
            [
                'id'                                => $account->id,
                'name'                              => $account->ClientRegistration->name,
                'balance'                           => $account->balance,
                'loan_given'                        => $account->loan_given,
                'total_loan_rec'                    => $account->total_loan_rec,
                'total_loan_remaining'              => $account->total_loan_remaining,
                'total_installment'                 => $account->payable_installment,
                'total_rec_installment'             => $account->total_rec_installment,
                'total_interest'                    => $account->total_payable_interest,
                'total_interest_rec'                => $account->total_interest_rec,
                'total_interest_remaining'          => $account->total_interest_remaining,
                'closing_fee'                       => $categoryConf->loan_acc_closing_fee ?? 0,
                'closing_fee_acc_id'                => $categoryConf->l_col_fee_acc_id,
            ],
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        LoanAccountClosing::find($id)->delete();

        return create_response(__('customValidations.client.closing.delete'));
    }

    /**
     * Approved the specified resource from storage.
     */
    public function approved(string $id)
    {
        return DB::transaction(function () use ($id) {
            $closing = LoanAccountClosing::find($id);
            $account = LoanAccount::with('Category:id,name,is_default')->find($closing->loan_account_id);

            if ($account->total_loan_remaining > 0) {
                return create_validation_error_response(__('customValidations.client.loan.loan_err'), 'total_loan_remaining');
            }
            if ($account->total_interest_remaining > 0) {
                return create_validation_error_response(__('customValidations.client.loan.interest_err'), 'total_interest_remaining');
            }

            LoanAccountClosing::handleApprovedAccountClosing($account, (object) ['withdrawal_account_id' => $closing->account_id]);

            $closing->approved_by = auth()->id();
            $closing->is_approved = true;
            $closing->save();

            return create_response(__('customValidations.client.loan.delete'));
        });
    }
}
