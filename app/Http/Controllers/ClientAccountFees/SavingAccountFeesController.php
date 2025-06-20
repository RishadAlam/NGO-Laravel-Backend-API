<?php

namespace App\Http\Controllers\ClientAccountFees;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\client\SavingAccountFee;

class SavingAccountFeesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (empty(request('saving_account_id'))) {
            return create_response(__('customValidations.common.somethingWentWrong'), null, 401, false);
        }

        $dateRange = Helper::getDateRange(request('date_range'));
        $fees = SavingAccountFee::where('saving_account_id', request('saving_account_id'))
            ->whereBetween('created_at', $dateRange)
            ->author('id', 'name')
            ->with('AccountFeesCategory:id,name,is_default')
            ->orderedBy('id', 'DESC')
            ->get();

        return create_response(null, $fees);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
