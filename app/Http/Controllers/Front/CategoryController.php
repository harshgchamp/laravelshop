<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;

use App\Models\Category;
use App\Models\Product;
use App\Http\Resources\Admin\ProductResource;
use Inertia\Inertia;

class CategoryController extends Controller
{

    public function index(?Category $category)
    {
        $productsQuery = Product::query()
            ->where('published', true)
            ->when(
                $category?->id,
                fn($q) => $q->where('category_id', $category->id)
            );
 

        $products = $productsQuery
            ->select(['id', 'title', 'slug', 'price', 'image', 'category_id'])
            ->latest()
            ->paginate(10)
            ->withQueryString();
 

        $categories = Category::query()
            ->select('id', 'name', 'slug')
            ->withCount([
                'products as count' => fn($q) => $q->where('published', true)
            ])
            ->get()
            ->map(function ($cat) use ($category) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'count' => $cat->count,
                    'active' => $category?->id === $cat->id,
                ];
            })
            ->filter(fn($cat) => $cat['count'] > 0)
            ->values();

        return Inertia::render('Front/Category', [
            'products' => ProductResource::collection($products),
            'category' => $category?->only(['id', 'name', 'slug']),
            'filters' => [
                'categories' => $categories,
                'brands' => [],
            ],
        ]);
    }
}
