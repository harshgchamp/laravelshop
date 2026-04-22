<?php

namespace App\Http\Resources\Front;

use App\Helper\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $products = $this->resource[0] ?? collect();
        $cartItems = $this->resource[1] ?? [];

        $total = 0;

        $carts = $products->map(function ($product) use ($cartItems, &$total) {
            $cart = $cartItems[$product->id] ?? null;
            $quantity = $cart['quantity'] ?? 1;
            $price = $product->discount_price ?? $product->price;

            $total += (float) $price * (int) $quantity;

            return [
                'id' => $product->id,
                'title' => $product->title,
                'price' => $price,
                'quantity' => $quantity,
                'image' => $product->image ? asset('storage/'.$product->image) : null,
            ];
        });

        return [
            'count' => Cart::getCount(),
            'total' => $total,
            'items' => $cartItems,
            'products' => $carts,
        ];
    }
}
