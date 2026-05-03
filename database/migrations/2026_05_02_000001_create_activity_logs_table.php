<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Who performed the action — nullable so logs survive if the user is deleted
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Machine-readable event name: 'user.created_by_admin', 'role.assigned'
            $table->string('event');

            // Polymorphic reference to the affected record
            // subject_type = App\Models\User, subject_id = 5
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            // Freeform JSON bag — stores event-specific context
            // e.g. {"name":"John","role":"editor"} or {"from":"editor","to":"admin"}
            $table->json('properties')->nullable();

            // IPv4 + IPv6 max = 45 chars
            $table->string('ip_address', 45)->nullable();

            $table->timestamps();

            // Two most common query patterns for an audit trail
            $table->index(['subject_type', 'subject_id']);
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
