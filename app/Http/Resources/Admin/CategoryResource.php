<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CategoryResource
 *
 * Transforms a Category Eloquent model into a plain array for Inertia / JSON responses.
 *
 * WHY use a Resource instead of returning the model directly?
 *  - Controls exactly which columns are exposed — prevents accidentally leaking
 *    soft-delete timestamps or internal flags to the frontend.
 *  - Centralises transformation logic (e.g. building the full image URL) in one place.
 *    If storage location changes (local → S3), only this file needs updating.
 *  - Allows conditional fields (whenLoaded) to prevent N+1 when relations are not needed.
 */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,

            // Slug is used for SEO-friendly URLs: /category/{slug}
            'slug' => $this->slug,

            'description' => $this->description,

            // Convert the stored relative path ("categories/abc.jpg") to a full public URL.
            // asset('storage/...') prepends APP_URL — works locally and on EC2/CloudFront.
            // Returns null when no image was uploaded — the Vue component shows a placeholder.
            'image' => $this->image ? asset('storage/'.$this->image) : null,

            // Boolean — active (true) or inactive (false) — drives visibility on the storefront.
            'status' => $this->status,

            // Human-readable date for the admin table. null-safe because created_at is
            // technically nullable in Eloquent even though the DB always sets it.
            'created_at' => $this->created_at?->format('Y-m-d'),
        ];
    }
}
