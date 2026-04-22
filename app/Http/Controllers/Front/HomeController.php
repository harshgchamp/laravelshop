<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Http\Resources\Admin\ProductResource;

use App\Http\Resources\Admin\CategoryResource;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $products = Product::with('category:id,name,slug')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categories = Category::get();

        // dd(CategoryResource::collection($categories)->response()->getData(true));
 

        return Inertia::render('Front/Home', [
            'categories' => CategoryResource::collection($categories),
            'products' => ProductResource::collection($products),
        ]);
    }
}
