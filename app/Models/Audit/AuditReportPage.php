<?php

namespace App\Models\Audit;

use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToAuthorTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditReportPage extends Model
{
    use HasFactory, SoftDeletes, HelperScopesTrait, BelongsToAuthorTrait;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'creator_id'
    ];

    /**
     * Relation with CenterActionHistory Table
     */
    public function AuditReportPageActionHistory()
    {
        return $this->hasMany(AuditReportPageActionHistory::class);
    }

    /**
     * Relation with CenterActionHistory Table
     */
    public function AuditReportMeta()
    {
        return $this->hasMany(AuditReportMeta::class);
    }
}
