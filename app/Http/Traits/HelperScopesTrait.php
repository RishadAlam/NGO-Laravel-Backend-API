<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Auth;

trait HelperScopesTrait
{
    /**
     * Field Relation Scope
     */
    public function scopeField($query, ...$arg)
    {
        $query->with("Field", function ($query) use ($arg) {
            $query->select(...$arg);
        });
    }

    /**
     * Center Relation Scope
     */
    public function scopeCenter($query, ...$arg)
    {
        $query->with("Center", function ($query) use ($arg) {
            $query->select(...$arg);
        });
    }

    /**
     * Category Relation Scope
     */
    public function scopeCategory($query, ...$arg)
    {
        $query->with("Category", function ($query) use ($arg) {
            $query->select(...$arg);
        });
    }

    /**
     * Author Relation Scope
     */
    public function scopeAuthor($query, ...$arg)
    {
        $query->with("Author", function ($query) use ($arg) {
            $query->select(...$arg);
        });
    }

    /**
     * Account Relation Scope
     */
    public function scopeAccount($query, ...$arg)
    {
        $query->with("Account", function ($query) use ($arg) {
            $query->select(...$arg);
        });
    }

    /**
     * ClientRegistration Relation Scope
     */
    public function scopeClientRegistration($query, ...$arg)
    {
        $query->with("ClientRegistration", function ($query) use ($arg) {
            $query->select(...$arg);
        });
    }

    /**
     * Data by user id
     */
    public function scopeCreatedBy($query, $id = null)
    {
        $query->where('creator_id', $id ?? Auth::id());
    }

    /**
     * Data by field id
     */
    public function scopeFieldID($query, $id)
    {
        $query->where('field_id', $id);
    }

    /**
     * Data by Center id
     */
    public function scopeCenterID($query, $id)
    {
        $query->where('center_id', $id);
    }

    /**
     * Data by Category id
     */
    public function scopeCategoryID($query, $id)
    {
        $query->where('category_id', $id);
    }

    /**
     * OrderBy
     */
    public function scopeOrderedBy($query, $key = 'id', $sort = 'DESC')
    {
        $query->orderBy($key, $sort);
    }

    /**
     * Active
     */
    public function scopeActive($query)
    {
        $query->where('status', true);
    }

    /**
     * Active
     */
    public function scopePending($query, $key = "is_approved")
    {
        $query->where($key, false);
    }

    /**
     * Active
     */
    public function scopeApprove($query, $key = "is_approved")
    {
        $query->where($key, true);
    }

    /**
     * Active
     */
    public function scopeToday($query, $key = "created_at")
    {
        $query->whereDate($key, date('Y-m-d'));
    }
}
