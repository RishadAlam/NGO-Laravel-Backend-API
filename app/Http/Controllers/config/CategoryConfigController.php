<?php

namespace App\Http\Controllers\config;

use App\Http\Controllers\Controller;
use App\Models\category\CategoryConfig;
use Illuminate\Http\Request;

class CategoryConfigController extends Controller
{
    /**
     * Get all categories configuration
     */
    public function get_all_categories_config(){
        $configs = CategoryConfig::with("Category:id,name")->get();
        return $configs;
    }
}
