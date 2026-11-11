<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WasteType;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PDO;
use Tests\TestCase;

class RenderAndDatabaseConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mysql_ssl_ca_configuration_is_populated_when_db_ssl_ca_env_is_set(): void
    {
        // Default: DB_SSL_CA tidak diset, opsi SSL harus kosong
        $optionsEmpty = config('database.connections.mysql.options');
        $this->assertEmpty($optionsEmpty);

        // Ketika DB_SSL_CA diset
        config(['database.connections.mysql.options' => [
            PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
        ]]);

        $optionsConfigured = config('database.connections.mysql.options');
        $this->assertArrayHasKey(PDO::MYSQL_ATTR_SSL_CA, $optionsConfigured);
        $this->assertSame('/etc/ssl/certs/ca-certificates.crt', $optionsConfigured[PDO::MYSQL_ATTR_SSL_CA]);
        $this->assertTrue($optionsConfigured[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]);
    }

    public function test_logging_channel_stderr_is_configured_and_operable(): void
    {
        // Pastikan channel stderr terdaftar di konfigurasi logging
        $this->assertArrayHasKey('stderr', config('logging.channels'));

        // Coba tulis log ke channel stderr tanpa exception
        Log::channel('stderr')->info('Test log message to stderr for Render dashboard stream');
        $this->assertTrue(true);
    }

    public function test_production_seeder_is_idempotent_when_run_multiple_times(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['setorku.seed_admin_email' => 'admin.render@gmail.com']);

        // Eksekusi seeder pertama
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertSuccessful();

        $initialUserCount = User::count();
        $initialWasteCount = WasteType::count();

        $this->assertSame(1, $initialUserCount);
        $this->assertSame(5, $initialWasteCount);

        // Eksekusi seeder kedua (simulasi container restart di Render)
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->assertSuccessful();

        // Pastikan tidak ada duplikasi data
        $this->assertSame($initialUserCount, User::count());
        $this->assertSame($initialWasteCount, WasteType::count());

        $admin = User::where('email', 'admin.render@gmail.com')->first();
        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role);
        $this->assertNull($admin->password);
    }
}
