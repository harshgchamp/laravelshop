<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds brand_id FK to products so each product can be associated with a brand.
 *
 * WHY nullable?
 *  - Existing products don't have a brand assigned. Making it required would fail
 *    on migrate if the products table has rows. Nullable preserves existing data.
 *  - nullOnDelete(): if a brand is soft-deleted (deleted_at set), the FK still exists.
 *    If a brand is FORCE-deleted (hard delete), this prevents orphaned rows by setting
 *    brand_id = null on related products instead of blocking the delete (RESTRICT).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('brand_id')
                ->nullable()
                ->after('category_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Brand::class);
        });
    }
};
