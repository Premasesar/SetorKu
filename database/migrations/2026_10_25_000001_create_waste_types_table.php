<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel waste_types (Jenis Sampah & Harga) — PBI-03 / FR-003.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('unit', 20)->default('kg');
            $table->decimal('price_per_unit', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_types');
    }
};
