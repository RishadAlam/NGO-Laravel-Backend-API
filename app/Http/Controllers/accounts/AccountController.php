<?php

namespace App\Http\Controllers\accounts;

use Illuminate\Http\Request;
use App\Models\accounts\Account;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\accounts\AccountChangeStatusRequest;
use App\Http\Requests\accounts\AccountStoreRequest;
use App\Http\Requests\accounts\AccountUpdateRequest;
use App\Models\accounts\AccountActionHistory;

class AccountController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:account_list_view')->only('index');
        $this->middleware('can:account_registration')->only('store');
        $this->middleware('can:account_data_update')->only(['update', 'change_status']);
        $this->middleware('can:account_soft_delete')->only('destroy');
    }

    /**
     * AccountActionHistory Common Function
     */
    private static function setActionHistory($id, $action, $histData){
        return [
            "account_id"        => $id,
            "author_id"         => auth()->id(),
            "name"              => auth()->user()->name,
            "image_uri"         => auth()->user()->image_uri,
            "action_type"       => $action,
            "action_details"    => $histData,
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accounts = Account::with('Author:id,name')
            ->with(['AccountActionHistory', 'AccountActionHistory.Author:id,name,image_uri'])
            ->get();

        return response(
            [
                'success'   => true,
                'data'      => $accounts
            ],
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AccountStoreRequest $request)
    {
        $data = (object) $request->validated();
        Account::create(
            [
                'name'          => $data->name,
                'acc_no'        => $data->acc_no,
                'acc_details'   => $data->acc_details
            ]
        );

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.accounts.successful'),
            ],
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AccountUpdateRequest $request, string $id)
    {
        $data       = (object) $request->validated();
        $account    = Account::find($id);
        $histData   = [];

        $account->name          !== $data->name ? $histData['name'] = "<p class='text-danger'>{$account->name}</p><p class='text-success'>{$data->name}</p>" : '';
        $account->acc_no        !== $data->acc_no ? $histData['acc_no'] = "<p class='text-danger'>{$account->acc_no}</p><p class='text-success'>{$data->acc_no}</p>" : '';
        $account->acc_details   !== $data->acc_details ? $histData['acc_details'] = "<p class='text-danger'>{$account->acc_details}</p><p class='text-success'>{$data->acc_details}</p>" : '';

        DB::transaction(function () use ($id, $data, $account, $histData) {
            $account->update(
                [
                    'name'          => $data->name,
                    'acc_no'        => $data->acc_no,
                    'acc_details'   => $data->acc_details
                ]
            );
            AccountActionHistory::create(self::setActionHistory($id, 'update', $histData));
        });

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.accounts.update')
            ],
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::transaction(function () use ($id) {
            Account::find($id)->delete();
            AccountActionHistory::create(self::setActionHistory($id, 'delete', []));
        });

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.accounts.delete')
            ],
            200
        );
    }

    /**
     * Change Status the specified Field
     */
    public function change_status(AccountChangeStatusRequest $request, string $id)
    {
        $status = $request->validated()['status'];
        $changeStatus = $status ? '<p class="text-danger">Deactive</p><p class="text-success">Active</p>' : '<p class="text-danger">Active</p><p class="text-success">Deactive</p>';
        DB::transaction(
            function () use ($id, $status, $changeStatus) {
                Account::find($id)->update(['status' => $status]);
                AccountActionHistory::create(self::setActionHistory($id, 'update', ['status' => $changeStatus]));
            }
        );

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.accounts.status')
            ],
            200
        );
    }
}
