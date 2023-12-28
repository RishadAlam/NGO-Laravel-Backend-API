<?php

namespace App\Models\client;

use App\Models\User;
use App\Models\field\Field;
use App\Models\center\Center;
use App\Models\category\Category;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavingAccount extends Model
{
    use HasFactory, SoftDeletes, HelperScopesTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'field_id',
        'center_id',
        'category_id',
        'client_registration_id',
        'acc_no',
        'start_date',
        'duration_date',
        'payable_installment',
        'payable_deposit',
        'payable_interest',
        'total_deposit_without_interest',
        'total_deposit_with_interest',
        'total_installment',
        'total_deposited',
        'total_withdrawn',
        'closing_balance',
        'closing_interest',
        'closing_balance_with_interest',
        'description',
        'status',
        'is_approved',
        'creator_id',
    ];

    /**
     * Relationship belongs to User model.
     *
     * @return response()
     */
    public function Author()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id')->withTrashed();
    }

    /**
     * Relation with SavingAccountActionHistory Table.
     */
    public function SavingAccountActionHistory()
    {
        return $this->hasMany(SavingAccountActionHistory::class);
    }

    /**
     * Relation with Nominee Table.
     */
    public function Nominees()
    {
        return $this->hasMany(Nominee::class);
    }

    /**
     * Relationship belongs to ClientRegistration model.
     *
     * @return response()
     */
    public function ClientRegistration()
    {
        return $this->belongsTo(ClientRegistration::class)->withTrashed();
    }

    /**
     * Relationship belongs to Field model.
     *
     * @return response()
     */
    public function Field()
    {
        return $this->belongsTo(Field::class)->withTrashed();
    }

    /**
     * Relationship belongs to Center model.
     *
     * @return response()
     */
    public function Center()
    {
        return $this->belongsTo(Center::class)->withTrashed();
    }

    /**
     * Relationship belongs to Category model.
     *
     * @return response()
     */
    public function Category()
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    /**
     * Nominee Relation Scope
     */
    public function scopeNominees($query, ...$arg)
    {
        return $query->with("Nominees", function ($query) use ($arg) {
            $query->select(...$arg);
        });
    }

    /**
     * Pending Saving Registration Forms Scope.
     */
    public function scopeFetchPendingForms($query)
    {
        return $query->Field('id', 'name')
            ->Center('id', 'name')
            ->Category('id', 'name', 'is_default')
            ->Author('id', 'name')
            ->ClientRegistration('id', 'acc_no', 'name', 'image_uri')
            ->Nominees('id', 'saving_account_id', 'name', 'father_name', 'husband_name', 'mother_name', 'nid', 'dob', 'occupation', 'relation', 'gender', 'primary_phone', 'secondary_phone', 'image', 'image_uri', 'signature', 'signature_uri', 'address')
            ->where('is_approved', false)
            ->filter()
            ->orderedBy();
    }

    /**
     * Filter Scope
     */
    public function scopeFilter($query)
    {
        $query->when(request('user_id'), function ($query) {
            $query->createdBy(request('user_id'));
        })
            ->when(!Auth::user()->can('pending_saving_acc_list_view_as_admin'), function ($query) {
                $query->createdBy();
            })
            ->when(request('field_id'), function ($query) {
                $query->fieldID(request('field_id'));
            })
            ->when(request('center_id'), function ($query) {
                $query->CenterID(request('center_id'));
            })
            ->when(request('category_id'), function ($query) {
                $query->CategoryID(request('category_id'));
            });
    }
}
