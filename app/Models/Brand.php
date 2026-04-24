<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Brand extends Model
{
    use HasFactory;
    use HasSlug;     // Spatie: auto-generates unique slug from `name` on create/update
    use SoftDeletes; // delete() sets deleted_at; standard queries exclude soft-deleted rows

    /**
     * Mass-assignable columns.
     *
     * `slug` is in $fillable so Spatie HasSlug can write to it via fill().
     * `image` stores the relative path returned by $file->store() — NOT the full URL.
     */
    protected $fillable = [
        'name',
        'slug',
        'image',
        'status',
    ];

    /**
     * Configure Spatie slug generation.
     *
     * Hooks into the Eloquent `creating` and `updating` events.
     * If "Nike" exists, Spatie creates "nike-1", "nike-2", etc. automatically.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(255);
    }

    // ─── Relationships ─────────────────────────────────────────────────────────

    /**
     * A brand owns many products.
     * FK: products.brand_id → brands.id (nullOnDelete — safe for soft-delete workflows)
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
