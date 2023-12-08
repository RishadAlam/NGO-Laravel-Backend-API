<?php

namespace App\Models\client;

use App\Models\User;
use App\Models\field\Field;
use App\Models\center\Center;
use App\Models\client\Nominee;
use App\Models\category\Category;
use Illuminate\Database\Eloquent\Model;
use App\Models\client\ClientRegistration;
use App\Models\client\NomineeRegistration;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\client\SavingAccountActionHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SavingAccount extends Model
{
    use HasFactory, SoftDeletes;

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
     * Relationship belongs to User model
     *
     * @return response()
     */
    public function Author()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id')->withTrashed();
    }

    /**
     * Relation with SavingAccountActionHistory Table
     */
    public function SavingAccountActionHistory()
    {
        return $this->hasMany(SavingAccountActionHistory::class);
    }

    /**
     * Relation with Nominee Table
     */
    public function Nominees()
    {
        return $this->hasMany(Nominee::class);
    }

    /**
     * Relationship belongs to ClientRegistration model
     *
     * @return response()
     */
    public function ClientRegistration()
    {
        return $this->belongsTo(ClientRegistration::class)->withTrashed();
    }

    /**
     * Relationship belongs to Field model
     *
     * @return response()
     */
    public function Field()
    {
        return $this->belongsTo(Field::class)->withTrashed();
    }

    /**
     * Relationship belongs to Center model
     *
     * @return response()
     */
    public function Center()
    {
        return $this->belongsTo(Center::class)->withTrashed();
    }

    /**
     * Relationship belongs to Category model
     *
     * @return response()
     */
    public function Category()
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }
}
