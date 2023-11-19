<?php

namespace Database\Seeders;

use Illuminate\Support\Arr;
use Illuminate\Database\Seeder;
use App\Models\category\Category;
use App\Models\category\CategoryConfig;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category1 = Category::create(
            [
                'name'          => "monthly_loan",
                'group'         => "মাসিক",
                'description'   => '',
                'saving'        => false,
                'loan'          => true,
                'status'        => true,
                'is_default'    => true,
            ]
        );
        $category2 = Category::create(
            [
                'name'          => "dps",
                'group'         => "মাসিক",
                'description'   => '',
                'saving'        => true,
                'loan'          => false,
                'status'        => true,
                'is_default'    => true,
            ]
        );

        CategoryConfig::create(['category_id' => $category1->id]);
        CategoryConfig::create(['category_id' => $category2->id]);

        for ($i = 0; $i < 10; $i++) {
            $category = Category::create(
                [
                    'name'          => fake()->unique()->name(),
                    'group'         => Arr::random(['Daily', 'Weekly', 'Half Month', 'Monthly', 'Yearly']),
                    'description'   => fake()->paragraph(),
                    'saving'        => fake()->numberBetween(0, 1),
                    'loan'          => fake()->numberBetween(0, 1),
                    'status'        => fake()->numberBetween(0, 1)
                ]
            );

            CategoryConfig::create(['category_id' => $category->id]);
        }
    }
}
