<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * BrandResource — transforms a Brand Eloquent model into a serialisable array.
 *
 * WHY use a Resource instead of returning the model directly?
 *  - Prevents leaking deleted_at, internal flags, or raw storage paths to the frontend.
 *  - Centralises the image URL transformation: if storage moves from local disk to S3,
 *    only this file needs updating — every page that receives brand data benefits.
 *  - whenLoaded() on relationships prevents N+1 queries in collection responses.
 */
class BrandResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,

            // Slug used for SEO-friendly storefront URLs (e.g. /brands/nike)
            'slug' => $this->slug,

            // Convert relative storage path ("brands/logo.jpg") to a full public URL.
            // asset('storage/...') prepends APP_URL — resolves correctly on localhost and EC2.
            // Returns null if no logo was uploaded — Vue shows a placeholder instead.
            'image' => $this->image ? asset('storage/'.$this->image) : null,

            // Boolean — drives storefront visibility. Cast from DB tinyint to PHP bool.
            'status' => (bool) $this->status,

            // Date only (no time) for the admin table — null-safe for Eloquent edge cases.
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
