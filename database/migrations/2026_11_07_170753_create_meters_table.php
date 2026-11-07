<?php

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
        Schema::create('meters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('service_type');
            $table->string('name');
            $table->unsignedTinyInteger('zone_count')->default(1);
            $table->string('serial_number')->nullable();
            $table->date('installed_at');
            $table->date('removed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meters');
    }
};
