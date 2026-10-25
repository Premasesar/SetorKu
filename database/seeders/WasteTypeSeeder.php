<?php

namespace Database\Seeders;

use App\Models\WasteType;
use Illuminate\Database\Seeder;

/**
 * Seeder master jenis sampah contoh — PBI-03.
 */
class WasteTypeSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Plastik PET', 'unit' => 'kg', 'price_per_unit' => 3500.00],
            ['name' => 'Kardus',      'unit' => 'kg', 'price_per_unit' => 2000.00],
            ['name' => 'Kertas',      'unit' => 'kg', 'price_per_unit' => 1500.00],
            ['name' => 'Botol Kaca',  'unit' => 'kg', 'price_per_unit' => 1000.00],
            ['name' => 'Kaleng',      'unit' => 'kg', 'price_per_unit' => 4000.00],
        ];

        foreach ($items as $item) {
            WasteType::firstOrCreate(
                ['name' => $item['name']],
                ['unit' => $item['unit'], 'price_per_unit' => $item['price_per_unit']],
            );
        }
    }
}
