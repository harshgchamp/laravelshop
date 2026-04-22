<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'title' => [
                'required',
                'string',
                'max:200',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique(Product::class, 'slug')->ignore($product?->id),
            ],

            'quantity' => [
                'required',
                'integer',
                'min:0',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'published' => [
                'sometimes',
                'boolean',
            ],
            'image' => [
                'nullable',
                'image',
                'max:2048',
            ],

            'in_stock' => [
                'required',
                'integer',
                'min:0',
            ],
            'price' => [
                'required',
                'numeric',
                'min:1',
            ],
            'discount_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'category_id' => [
                'required',
                'exists:categories,id',
            ],
        ];
    }
}
