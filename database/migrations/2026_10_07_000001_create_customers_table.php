<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel customers (Nasabah) — PBI-02 / FR-002.
 *
 * - user_id   : relasi 1-1 ke users (akun login berperan Nasabah), nullable & unique
 * - latitude/longitude : opsional
 * - balance   : saldo, default 0, TIDAK diisi dari form (diubah hanya oleh transaksi, BR-003)
 * - is_active : status aktif; "nonaktifkan" = is_active false (tanpa hard delete)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('address');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('balance', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
