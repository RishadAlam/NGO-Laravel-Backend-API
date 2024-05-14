<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HelperScopesTrait;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use App\Models\Collections\LoanCollection;
use App\Models\Collections\SavingCollection;
use Spatie\Permission\Traits\HasPermissions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use SoftDeletes, HasRoles, HasPermissions, HasApiTokens, HasFactory, Notifiable, HelperScopesTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'phone',
        'status',
        'image',
        'image_uri'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Relation with UserActionHistory Table
     */
    public function UserActionHistory()
    {
        return $this->hasMany(UserActionHistory::class);
    }

    /**
     * Relation with Saving Collection Table
     */
    public function SavingCollection()
    {
        return $this->hasMany(SavingCollection::class, 'creator_id');
    }

    /**
     * Relation with Loan Collection Table
     */
    public function LoanCollection()
    {
        return $this->hasMany(LoanCollection::class, 'creator_id');
    }

    /**
     * Today Top Collectionist
     */
    public static function currentDayTopCollectionist()
    {
        return static::active()
            ->with(
                [
                    'SavingCollection' => function ($query) {
                        $query->today()
                            ->selectRaw('SUM(deposit) as deposit, creator_id')
                            ->groupBy('creator_id');
                    },
                    'LoanCollection' => function ($query) {
                        $query->today()
                            ->selectRaw('SUM(total) as total, creator_id')
                            ->groupBy('creator_id');
                    },
                ]
            )
            ->when(!Auth::user()->can('view_dashboard_as_admin'), function ($query) {
                $query->whereId(Auth::user()->id);
            })
            ->get(['id', 'name', 'email', 'image_uri'])->filter(function ($user) {
                return !empty($user->SavingCollection[0]) || !empty($user->LoanCollection[0]);
            })
            ->map(function ($user) {
                $amount = ($user->SavingCollection->isEmpty() ? 0 : $user->SavingCollection[0]->deposit)
                    + ($user->LoanCollection->isEmpty() ? 0 : $user->LoanCollection[0]->total);

                return (object) [
                    'id' => $user->id,
                    'name' => $user->name,
                    'image_uri' => $user->image_uri,
                    'email' => $user->email,
                    'amount' => $amount,
                ];
            })
            ->sortByDesc('amount')
            ->values();
    }
}
