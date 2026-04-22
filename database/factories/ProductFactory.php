<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'title'          => $title,
            'slug'           => Str::slug($title),
            'quantity'       => fake()->numberBetween(1, 100),
            'description'    => fake()->optional()->paragraph(),
            'image'          => null,
            'published'      => false,
            'in_stock'       => 1,
            'price'          => fake()->randomFloat(2, 10, 500),
            'discount_price' => null,
            'category_id'    => Category::factory(),
            'created_by'     => User::factory(),
            'updated_by'     => null,
            'deleted_by'     => null,
        ];
    }
}
