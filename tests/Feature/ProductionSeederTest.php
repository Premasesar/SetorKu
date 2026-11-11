<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PickupRequest;
use App\Models\User;
use App\Models\WasteType;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_seeder_does_not_create_dummy_credentials_when_seed_admin_email_is_dummy(): void
    {
        // Simulasikan environment production
        $this->app->detectEnvironment(fn () => 'production');
        config(['setorku.seed_admin_email' => 'admin@banksampah.test']);

        // Di production, artisan db:seed butuh flag --force
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

        // Tidak boleh ada akun admin dummy atau akun demo
        $this->assertNull(User::where('email', 'admin@banksampah.test')->first());
        $this->assertNull(User::where('email', 'petugas@banksampah.test')->first());
        $this->assertNull(User::where('email', 'petugas2@banksampah.test')->first());
        $this->assertNull(User::where('email', 'nasabah1@banksampah.test')->first());

        // Tidak ada user yang dibuat jika SEED_ADMIN_EMAIL placeholder
        $this->assertSame(0, User::count());
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, PickupRequest::count());

        // Namun master jenis sampah tetap di-seed untuk operasional
        $this->assertGreaterThan(0, WasteType::count());
    }

    public function test_production_seeder_creates_admin_from_seed_admin_email_without_password(): void
    {
        // Simulasikan environment production
        $this->app->detectEnvironment(fn () => 'production');
        config(['setorku.seed_admin_email' => 'owner.setorku@gmail.com']);

        // Di production, artisan db:seed butuh flag --force
        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);

        $admin = User::where('email', 'owner.setorku@gmail.com')->first();
        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role);

        // Keamanan: di production, password HARUS null (hanya login via Google OAuth, tidak ada kredensial default)
        $this->assertNull($admin->password);

        // Tidak ada user selain admin yang dibuat
        $this->assertSame(1, User::count());
        $this->assertNull(User::where('email', 'petugas@banksampah.test')->first());
        $this->assertSame(0, PickupRequest::count());
    }

    public function test_local_or_testing_seeder_still_creates_full_demo_dataset(): void
    {
        // Default environment testing
        $this->assertSame('testing', app()->environment());
        config(['setorku.seed_admin_email' => 'admin@banksampah.test']);

        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@banksampah.test')->first();
        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role);
        $this->assertNotNull($admin->password); // Ada password default untuk dev/testing

        $this->assertNotNull(User::where('email', 'petugas@banksampah.test')->first());
        $this->assertNotNull(User::where('email', 'petugas2@banksampah.test')->first());
        $this->assertSame(11, Customer::count());
        $this->assertSame(12, PickupRequest::count());
        $this->assertGreaterThan(0, WasteType::count());
    }
}
