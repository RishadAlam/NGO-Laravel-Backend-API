<?php

namespace App\Models\center;

use App\Models\User;
use App\Models\field\Field;
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
     * Regular Collection Sheet.
     */
    public function scopeRegularCollectionSheet($query, $category_id, $field_id)
    {
        $query->fieldID($field_id)
            ->active()
            ->with(
                [
                    'SavingAccount' => function ($query) use ($category_id) {
                        $query->select(
                            'id',
                            'center_id',
                            'client_registration_id',
                            'acc_no',
                            'payable_deposit'
                        );
                        $query->ClientRegistration('id', 'name', 'image_uri');
                        $query->with([
                            'SavingCollection' => function ($query) use ($category_id) {
                                $query->author('id', 'name');
                                $query->select('id', 'saving_account_id', 'deposit', 'description', 'creator_id', 'created_at');
                                $query->pending();
                                // $query->today();
                                $query->categoryID($category_id);
                                $query->when(request('user_id'), function ($query) {
                                    $query->createdBy(request('user_id'));
                                });
                                $query->when(!Auth::user()->can('regular_saving_collection_list_view_as_admin'), function ($query) {
                                    $query->createdBy();
                                });
                            }
                        ]);
                    }
                ]
            );
    }
}
