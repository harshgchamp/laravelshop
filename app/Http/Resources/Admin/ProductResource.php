<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ProductResource
 *
 * Transforms a Product Eloquent model (with optional eager-loaded relations)
 * into a consistent array structure for Inertia page props.
 *
 * Design decisions:
 *  - `category` uses whenLoaded() — avoids triggering an extra query when the relation
 *    was not eager-loaded (e.g. in the edit form, we don't load category since we only
 *    need category_id for the select-box default).
 *  - Price/discount are cast to float so the Vue component never receives a string "9.99".
 *  - `created_at` is ISO-8601 (full datetime) so the admin can see exact timestamps,
 *    unlike CategoryResource which shows just the date.
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,

            // Cast numeric strings from the DB to PHP floats for type-safe frontend use
            'price' => (float) $this->price,
            'discount_price' => $this->discount_price ? (float) $this->discount_price : null,

            'quantity' => (int) $this->quantity,

            // in_stock is boolean (0/1 tinyint) — cast so Vue receives true/false, not "0"/"1"
            'in_stock' => (bool) $this->in_stock,

            // published controls storefront visibility — cast for the same reason as in_stock
            'published' => (bool) $this->published,

            // Full ISO-8601 timestamp so the admin table can show "2025-04-22 14:30"
            'created_at' => $this->created_at?->toDateTimeString(),

            // FK for pre-selecting the category in the edit form dropdown
            'category_id' => $this->category_id,

            // Full public URL or null — built the same way as CategoryResource
            'image' => $this->image ? asset('storage/'.$this->image) : null,

            // whenLoaded(): if category was eager-loaded with ->with('category'), nest its
            // resource here. If NOT loaded, this key is omitted entirely from the output —
            // no extra query is fired. Prevents N+1 in the index listing.
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
