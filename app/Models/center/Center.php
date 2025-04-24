<?php

namespace App\Models\center;

use App\Models\User;
use App\Helpers\Helper;
use App\Models\field\Field;
use App\Models\client\LoanAccount;
use App\Models\client\SavingAccount;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToFieldTrait;
use App\Http\Traits\BelongsToAuthorTrait;
use App\Models\center\CenterActionHistory;
use App\Models\Collections\LoanCollection;
use App\Models\Collections\SavingCollection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Center extends Model
{
    use HasFactory,
        SoftDeletes,
        BelongsToFieldTrait,
        HelperScopesTrait,
        BelongsToAuthorTrait;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'field_id',
        'description',
        'status',
        'creator_id'
    ];

    /**
     * Relation with CenterActionHistory Table
     */
    public function CenterActionHistory()
    {
        return $this->hasMany(CenterActionHistory::class);
    }

    /**
     * Relation with Saving Account Table
     */
    public function SavingAccount()
    {
        return $this->hasMany(SavingAccount::class)->withTrashed();
    }

    /**
     * Relation with loan Account Table
     */
    public function LoanAccount()
    {
        return $this->hasMany(LoanAccount::class)->withTrashed();
    }

    /**
     * Relation with Saving Collection Table
     */
    public function SavingCollection()
    {
        return $this->hasMany(SavingCollection::class)->withTrashed();
    }

    /**
     * Relation with Loan Collection Table
     */
    public function LoanCollection()
    {
        return $this->hasMany(LoanCollection::class)->withTrashed();
    }

    /**
     * Regular Saving Collection Sheet.
     */
    public function scopeSavingCollectionSheet($query, $category_id, $field_id, $user_id = null, $isRegular = true, $date = null)
    {
        $prefix = Helper::getPermissionPrefix($isRegular);
        $query->fieldID($field_id)
            ->active()
            ->with(
                [
                    'SavingAccount' => function ($query) use ($category_id, $field_id, $user_id, $isRegular, $date, $prefix) {
                        $query->approve();
                        $query->active();
                        $query->select(
                            'id',
                            'field_id',
                            'center_id',
                            'category_id',
                            'client_registration_id',
                            'acc_no',
                            'payable_deposit'
                        );
                        $query->fieldID($field_id);
                        $query->categoryID($category_id);
                        $query->whereHas('ClientRegistration', function ($q) {
                            $q->approve();
                            $q->whereNull('deleted_at');
                        })->with(['ClientRegistration' => function ($q) {
                            $q->approve();
                            $q->whereNull('deleted_at');
                            $q->select('id', 'name', 'image_uri');
                        }]);
                        $query->with([
                            'SavingCollection' => function ($query) use ($category_id, $field_id, $user_id, $isRegular, $date, $prefix) {
                                $query->author('id', 'name');
                                $query->account('id', 'name', 'is_default');
                                $query->select('id', 'saving_account_id', 'account_id', 'installment', 'deposit', 'description', 'is_approved', 'creator_id', 'created_at');
                                $query->pending();
                                $query->fieldID($field_id);
                                $query->categoryID($category_id);
                                $query->when($isRegular, function ($query) {
                                    $query->today();
                                });
                                $query->when(!$isRegular, function ($query) use ($date) {
                                    $query->whereDate('created_at', $date);
                                });
                                $query->when($user_id, function ($query) use ($user_id) {
                                    $query->createdBy($user_id);
                                });
                                $query->when(!Auth::user()->can("{$prefix}_saving_collection_list_view_as_admin"), function ($query) {
                                    $query->createdBy();
                                });
                            }
                        ]);
                    }
                ]
            );
    }

    /**
     * Regular Loan Collection Sheet.
     */
    public function scopeLoanCollectionSheet($query, $category_id, $field_id, $user_id = null, $isRegular = true, $date = null)
    {
        $prefix = Helper::getPermissionPrefix($isRegular);
        $query->fieldID($field_id)
            ->active()
            ->with(
                [
                    'LoanAccount' => function ($query) use ($category_id, $field_id, $user_id, $isRegular, $date, $prefix) {
                        $query->approve();
                        $query->active();
                        $query->select(
                            'id',
                            'field_id',
                            'center_id',
                            'category_id',
                            'client_registration_id',
                            'acc_no',
                            'payable_deposit',
                            'loan_installment',
                            'interest_installment',
                            'is_loan_approved'
                        );
                        $query->fieldID($field_id);
                        $query->categoryID($category_id);
                        $query->whereHas('ClientRegistration', function ($q) {
                            $q->approve();
                            $q->whereNull('deleted_at');
                        })->with(['ClientRegistration' => function ($q) {
                            $q->approve();
                            $q->whereNull('deleted_at');
                            $q->select('id', 'name', 'image_uri');
                        }]);
                        $query->with([
                            'LoanCollection' => function ($query) use ($category_id, $field_id, $user_id, $isRegular, $date, $prefix) {
                                $query->author('id', 'name');
                                $query->account('id', 'name', 'is_default');
                                $query->select('id', 'loan_account_id', 'account_id', 'installment', 'deposit', 'loan', 'interest', 'total', 'description', 'is_approved', 'creator_id', 'created_at');
                                $query->pending();
                                $query->fieldID($field_id);
                                $query->categoryID($category_id);
                                $query->when($isRegular, function ($query) {
                                    $query->today();
                                });
                                $query->when(!$isRegular, function ($query) use ($date) {
                                    $query->whereDate('created_at', $date);
                                });
                                $query->when($user_id, function ($query) use ($user_id) {
                                    $query->createdBy($user_id);
                                });
                                $query->when(!Auth::user()->can("{$prefix}_loan_collection_list_view_as_admin"), function ($query) {
                                    $query->createdBy();
                                });
                            }
                        ]);
                    }
                ]
            );
    }
}
