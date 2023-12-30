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
        return $query->with("Field", function ($query) use ($arg) {
            $query->select(...$arg);
        });
    }

    /**
     * Center Relation Scope
     */
    public function scopeCenter($query, ...$arg)
    {
        return $query->with("Center", function ($query) use ($arg) {
            $query->select(...$arg);
        });
    }

    /**
     * Category Relation Scope
     */
    public function scopeCategory($query, ...$arg)
    {
        return $query->with("Category", function ($query) use ($arg) {
            $query->select(...$arg);
        });
    }

    /**
     * Author Relation Scope
     */
    public function scopeAuthor($query, ...$arg)
    {
        return $query->with("Author", function ($query) use ($arg) {
            $query->select(...$arg);
        });
    }

    /**
     * ClientRegistration Relation Scope
     */
    public function scopeClientRegistration($query, ...$arg)
    {
        return $query->with("ClientRegistration", function ($query) use ($arg) {
            $query->select(...$arg);
        });
    }

    /**
     * Data by user id
     */
    public function scopeCreatedBy($query, $id)
    {
        return $query->where('creator_id', $id ?? Auth::id());
    }

    /**
     * Data by field id
     */
    public function scopeFieldID($query, $id)
    {
        return $query->where('field_id', $id);
    }

    /**
     * Data by Center id
     */
    public function scopeCenterID($query, $id)
    {
        return $query->where('center_id', $id);
    }

    /**
     * Data by Category id
     */
    public function scopeCategoryID($query, $id)
    {
        return $query->where('category_id', $id);
    }

    /**
     * OrderBy
     */
    public function scopeOrderedBy($query, $key = 'id', $sort = 'DESC')
    {
        return $query->orderBy($key, $sort);
    }

    /**
     * Active
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Active
     */
    public function scopePending($query, $key = "is_approved")
    {
        return $query->where($key, false);
    }

    /**
     * Active
     */
    public function scopeToday($query, $key = "created_at")
    {
        return $query->whereDate($key, date('Y-m-d'));
    }
}
