<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
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
                Rule::unique(Product::class, 'slug'),
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'description' => [
                'nullable',
                'string',
            ],
            'image' => [
                'nullable',
                'image',
                'max:2048',
            ],

            'published' => [
                'sometimes',
                'boolean',
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
