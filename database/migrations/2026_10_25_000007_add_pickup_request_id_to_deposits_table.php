<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom pickup_request_id (nullable, FK, index) ke tabel deposits — PBI-09.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->foreignId('pickup_request_id')
                ->nullable()
                ->after('officer_id')
                ->constrained('pickup_requests')
                ->restrictOnDelete();

            $table->index('pickup_request_id', 'deposits_pickup_request_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropForeign(['pickup_request_id']);
            $table->dropIndex('deposits_pickup_request_id_index');
            $table->dropColumn('pickup_request_id');
        });
    }
};
