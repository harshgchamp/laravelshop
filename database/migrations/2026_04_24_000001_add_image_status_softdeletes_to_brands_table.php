<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the brands table with image, status, and soft-delete support.
 *
 * WHY a new migration instead of editing the original?
 *  - The original migration is part of the commit history. Editing it would require
 *    every developer to run migrate:fresh — breaking existing data in shared environments.
 *  - A new migration is additive: it applies cleanly on top of the existing table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            // Brand logo — stored as a relative path, resolved to full URL in BrandResource
            $table->string('image')->nullable()->after('slug');

            // Controls storefront visibility (active = true, hidden = false)
            $table->boolean('status')->default(true)->after('image');

            // Soft delete: sets deleted_at instead of removing the row
            // Allows data recovery and audit without hard-deleting records
            $table->softDeletes()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['image', 'status']);
            $table->dropSoftDeletes();
        });
    }
};
