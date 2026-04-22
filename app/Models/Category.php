<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;
    use SoftDeletes; // Adds deleted_at column support — delete() becomes soft, forceDelete() hard-deletes
    use HasSlug;     // Spatie: auto-generates slug from $slugOptions on `creating` and `updating` events

    /**
     * Mass-assignable columns.
     * NEVER add sensitive columns (e.g. is_admin) here — they could be set via a crafted request.
     * `slug` is included so Spatie can write the generated value via fill().
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'status',
    ];

    /**
     * Configure Spatie's slug generation.
     *
     * WHY use Spatie HasSlug instead of manual Str::slug() in the controller?
     *  - It hooks into Eloquent model events — slug is generated/updated automatically.
     *  - Unique suffix handling (slug-1, slug-2) is built in — no custom counter query needed.
     *  - Works for both create AND update when the name changes.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')       // derive slug from the `name` column
            ->saveSlugsTo('slug')             // write the result to the `slug` column
            ->slugsShouldBeNoLongerThan(255); // match the DB column length
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * A category owns many products.
     * The FK `products.category_id` RESTRICT prevents deleting a category that has products.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
