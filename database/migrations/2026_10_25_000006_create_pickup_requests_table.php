<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel pickup_requests (Pengajuan & Penjadwalan Penjemputan Sampah) — PBI-07 / PBI-08 / PBI-09.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->date('requested_date');
            $table->text('address');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('menunggu');
            $table->text('rejection_reason')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->foreignId('officer_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('scheduled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['status', 'scheduled_date']);
            $table->index(['officer_id', 'status', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_requests');
    }
};
