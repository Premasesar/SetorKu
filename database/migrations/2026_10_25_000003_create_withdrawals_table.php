<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel withdrawals (Transaksi Penarikan Saldo) — PBI-05 / FR-005.
 *
 * Mencatat nasabah, petugas pencatat (officer_id), nominal penarikan (amount > 0),
 * serta waktu transaksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('officer_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
