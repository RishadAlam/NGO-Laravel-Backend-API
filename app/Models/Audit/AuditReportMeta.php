<?php

namespace App\Models\Audit;

use App\Http\Traits\HelperScopesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Http\Traits\BelongsToAuthorTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditReportMeta extends Model
{
    use HasFactory, SoftDeletes, HelperScopesTrait, BelongsToAuthorTrait;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'meta_key',
        'meta_value',
        'page_no',
        'column_no',
        'creator_id'
    ];

    /**
     * Relation with CenterActionHistory Table
     */
    public function AuditReportMetaActionHistory()
    {
        return $this->hasMany(AuditReportMetaActionHistory::class);
    }
}
