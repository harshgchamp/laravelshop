<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    use HasFactory;
    // migration has softDeletes() — delete() sets deleted_at, record stays in DB
    use HasSlug;
    use SoftDeletes;     // auto-generates slug from `title` on create/update

    /**
     * Mass-assignable columns.
     *
     * WHY are created_by / updated_by / deleted_by in $fillable?
     *  - Spatie HasSlug writes `slug` via fill() internally, and the ProductObserver
     *    sets the audit fields via direct property assignment (not fill), so technically
     *    they don't need to be in $fillable. They're listed here for explicit clarity:
     *    these columns are intentionally writable by the application layer.
     *
     * WHY NOT put `deleted_at` in $fillable?
     *  - SoftDeletes manages `deleted_at` internally — you never mass-assign it.
     */
    protected $fillable = [
        'title',
        'slug',
        'category_id',
        'description',
        'published',
        'quantity',
        'in_stock',
        'price',
        'discount_price',
        'image',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Configure Spatie's slug generation.
     *
     * generateSlugsFrom('title') → slug is derived from the product title.
     * uniqueSlugs: if "laptop" exists, Spatie creates "laptop-1", "laptop-2", etc.
     *
     * WHY NOT allowSlugIfRelatedModelDoesNotExist()?
     *  - Not needed here; products always belong to a category.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(255);
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * A product belongs to one category.
     * FK: products.category_id → categories.id (RESTRICT on delete)
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
