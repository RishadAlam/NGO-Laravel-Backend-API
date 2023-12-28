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
     * Creators Data
     */
    public function scopeCreatedBy($query, $id)
    {
        return $query->where('creator_id', $id ?? Auth::id());
    }

    /**
     * OrderBy
     */
    public function scopeOrderedBy($query, $key = 'id', $sort = 'DESC')
    {
        return $query->orderBy($key, $sort);
    }
}
