<?php

namespace App\Models\client;

use App\Models\User;
use App\Models\field\Field;
use App\Models\center\Center;
use App\Models\category\Category;
use Illuminate\Database\Eloquent\Model;
use App\Models\client\GuarantorRegistration;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\client\LoanAccountActionHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanAccount extends Model
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
        'loan_given',
        'payable_deposit',
        'payable_installment',
        'payable_interest',
        'total_payable_interest',
        'total_payable_loan_with_interest',
        'loan_installment',
        'interest_installment',
        'total_rec_installment',
        'total_deposited',
        'total_withdrawn',
        'total_loan_rec',
        'total_interest_rec',
        'closing_balance',
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
     * Relation with LoanAccountActionHistory Table
     */
    public function LoanAccountActionHistory()
    {
        return $this->hasMany(LoanAccountActionHistory::class);
    }

    /**
     * Relation with GuarantorRegistration Table
     */
    public function GuarantorRegistration()
    {
        return $this->hasMany(GuarantorRegistration::class);
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
