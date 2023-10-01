<?php

namespace App\Http\Controllers\staffs;

use App\Http\Controllers\Controller;
use App\Http\Requests\staffs\ChangeStatusRequest;
use App\Http\Requests\StaffStoreRequest;
use App\Http\Requests\StaffUpdateRequest;
use App\Models\User;
use App\Models\UserActionHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('can:staff_list_view')->only('index');
        $this->middleware('can:staff_permissions_view')->only('show');
        $this->middleware('can:staff_registration')->only('store');
        $this->middleware('can:staff_data_update')->only('update');
        $this->middleware('can:staff_soft_delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::with('roles:id,name,is_default')
            ->with('permissions:id,name,group_name')
            ->with(['UserActionHistory', 'UserActionHistory.Author:id,name,image_uri'])
            ->get(['id', 'name', 'email', 'phone', 'image', 'image_uri', 'email_verified_at as verified_at', 'status']);

        $responseData = [];
        foreach ($users as $key => $user) {
            $responseData[$key] = (object) [
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'phone'             => $user->phone,
                'image'             => $user->image,
                'image_uri'         => $user->image_uri,
                'verified_at'       => $user->verified_at,
                'status'            => $user->status,
                'role_id'           => $user->roles[0]->id ?? null,
                'role_name'         => $user->roles[0]->name ?? null,
                'role_is_default'   => $user->roles[0]->is_default ?? false,
                'permissions'       => $user->permissions,
                'action_history'    => $user->UserActionHistory,
            ];
        }

        return response(
            [
                'success'   => true,
                'data'      => $responseData
            ],
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StaffStoreRequest $request)
    {
        $staffData = (object) $request->validated();
        $staff = User::create([
            'name' => $staffData->name,
            'email' => $staffData->email,
            'phone' => $staffData->phone,
            'password' => Hash::make(123)
        ]);
        $staff->assignRole($staffData->role);

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.staff.successful'),
                'id'        => $staff->id
            ],
            200
        );
    }

    /**
     * Change Status the specified user
     */
    public function change_status(ChangeStatusRequest $request, string $id)
    {
        $status = $request->validated()['status'];
        $changeStatus = $status ? 'Deactive => Active.' : 'Active => Deactive.';
        DB::transaction(
            function () use ($id, $status, $changeStatus) {
                User::find($id)->update(['status' => $status]);
                UserActionHistory::create([
                    "user_id" => $id,
                    "author_id" => auth()->user()->id,
                    "name" => auth()->user()->name,
                    "image_uri" => auth()->user()->image_uri,
                    "action_type" => 'update',
                    "action_details" => ['status' => $changeStatus],
                ]);
            }
        );

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.staff.status')
            ],
            200
        );
    }

    /**
     * Display a list of user permissions
     */
    public function get_user_permissions(string $id)
    {
        $permissions             = [];
        $collectionOfPermissions = User::find($id)->permissions;
        foreach ($collectionOfPermissions as $permission) {
            $permissions[] = (object) [
                'id'            => $permission->id,
                'name'          => $permission->name,
                'group_name'    => $permission->group_name
            ];
        }
        return response(
            [
                'success'   => true,
                'data'      => $permissions
            ],
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StaffUpdateRequest $request, string $id)
    {
        $staffData  = (object) $request->validated();
        $staff      = User::with('roles:id,name')->find($id);
        $histData   = [];
        $staff->name    !== $staffData->name ? $histData['name'] = "{$staff->name} => {$staffData->name}" : '';
        $staff->email   !== $staffData->email ?$histData['email'] = "{$staff->email} => {$staffData->email}" : '';
        $staff->phone   !== $staffData->phone ?$histData['phone'] = "{$staff->phone} => {$staffData->phone}" : '';

        if ($staff->roles[0]->id !== $staffData->role) {
            $role       = Role::find($staffData->role, ['id', 'name']);
            $histData[] = "{$staff->roles[0]->name} => {$role->name}";
        }

        DB::transaction(function () use ($id, $staffData, $staff, $histData) {
            $staff->update([
                'name'  => $staffData->name,
                'email' => $staffData->email,
                'phone' => $staffData->phone
            ]);

            if (isset($staff->roles[0]->id)) {
                if ($staffData->role !== $staff->roles[0]->id) {
                    $staff->syncRoles($staff->roles[0]->id, $staffData->role);
                }
            } else {
                $staff->assignRole($staffData->role);
            }

            UserActionHistory::create([
                "user_id"           => $id,
                "author_id"         => auth()->user()->id,
                "name"              => auth()->user()->name,
                "image_uri"         => auth()->user()->image_uri,
                "action_type"       => 'update',
                "action_details"    => $histData,
            ]);
        });

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.staff.update'),
                'id'        => $staff->id
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
            User::find($id)->delete();
            UserActionHistory::create([
                "user_id"           => $id,
                "author_id"         => auth()->user()->id,
                "name"              => auth()->user()->name,
                "image_uri"         => auth()->user()->image_uri,
                "action_type"       => 'delete',
                "action_details"    => [],
            ]);
        });

        return response(
            [
                'success'   => true,
                'message'   => __('customValidations.staff.delete')
            ],
            200
        );
    }
}
