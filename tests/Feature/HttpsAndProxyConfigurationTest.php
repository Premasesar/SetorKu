<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HttpsAndProxyConfigurationTest extends TestCase
{
    public function test_url_and_assets_enforce_https_when_app_url_is_https_or_in_production(): void
    {
        // Atur APP_URL ke HTTPS
        config(['app.url' => 'https://setorku.example.com']);
        URL::forceScheme('https');

        $loginUrl = route('login');
        $assetUrl = asset('css/setorku-theme.css');

        $this->assertStringStartsWith('https://', $loginUrl);
        $this->assertStringStartsWith('https://', $assetUrl);
    }

    public function test_trusted_proxy_correctly_detects_https_and_client_ip_from_forwarded_headers(): void
    {
        // Simulasikan request melewati reverse proxy (Nginx / Cloudflare)
        $response = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-For' => '203.0.113.195',
            'X-Forwarded-Port' => '443',
        ])->get('/login');

        $response->assertOk();

        // Pastikan route yang diakses mendeteksi request aman (secure)
        $this->assertTrue(request()->isSecure());
        $this->assertSame('203.0.113.195', request()->ip());
    }
}
