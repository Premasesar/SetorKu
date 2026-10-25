<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel deposits (Transaksi Setoran Sampah) — PBI-04 / FR-004.
 *
 * Satu setoran = satu jenis sampah (tanpa DepositDetail).
 * Menyimpan snapshot price_per_unit dan total_value saat transaksi terjadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('waste_type_id')->constrained('waste_types')->restrictOnDelete();
            $table->foreignId('officer_id')->constrained('users')->restrictOnDelete();
            $table->decimal('weight', 10, 3);
            $table->decimal('price_per_unit', 12, 2);
            $table->decimal('total_value', 15, 2);
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
