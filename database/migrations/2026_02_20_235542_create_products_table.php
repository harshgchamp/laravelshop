<?php

use App\Models\Category;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {

            $table->id();

            $table->string('title', 200);
            $table->string('slug')->unique();

            $table->unsignedInteger('quantity')->default(0);

            $table->longText('description')->nullable();
            $table->string('image', 100)->nullable();

            $table->boolean('published')->default(false);
            $table->boolean('in_stock')->default(0);

            $table->decimal('price', 10, 2)->unsigned();

            $table->decimal('discount_price', 10, 2)->unsigned()->nullable();

            // Category
            $table->foreignId('category_id')
                ->constrained()
                ->restrictOnDelete();

            // Audit fields
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('deleted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
