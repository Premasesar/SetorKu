<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration PBI-15: Login & Pendaftaran dengan Akun Google.
 *
 * - Menambahkan kolom google_id (nullable, unique) pada tabel users.
 * - Mengubah kolom password pada tabel users menjadi nullable (untuk akun Google / petugas tanpa password lokal).
 * - Memastikan kolom email memiliki unique index setelah pengecekan duplikat.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasIndex('users', ['email'], 'unique')) {
            $duplicate = DB::table('users')
                ->selectRaw('LOWER(TRIM(email)) as normalized_email, COUNT(*) as total')
                ->groupByRaw('LOWER(TRIM(email))')
                ->havingRaw('COUNT(*) > 1')
                ->first();

            if ($duplicate !== null) {
                throw new RuntimeException(
                    "Terdapat email duplikat pada tabel users ({$duplicate->normalized_email}). Bersihkan duplikat terlebih dahulu."
                );
            }

            Schema::table('users', function (Blueprint $table) {
                $table->unique('email');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->whereNull('password')->update(['password' => '']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn('google_id');
            $table->string('password')->nullable(false)->change();
        });
    }
};
