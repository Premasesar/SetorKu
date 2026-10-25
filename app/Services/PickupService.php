<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PickupRequest;

/**
 * Layanan siklus hidup permintaan penjemputan sampah — PBI-07..PBI-09.
 */
class PickupService
{
    /**
     * Buat pengajuan penjemputan baru oleh nasabah dengan status awal selalu 'menunggu'.
     *
     * @param  array{requested_date: string, address: string, latitude: float|string, longitude: float|string, notes?: ?string}  $data
     */
    public function submitRequest(Customer $customer, array $data): PickupRequest
    {
        $pickup = new PickupRequest([
            'requested_date' => $data['requested_date'],
            'address' => $data['address'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'notes' => $data['notes'] ?? null,
        ]);

        $pickup->customer_id = $customer->id;
        $pickup->status = PickupRequest::STATUS_MENUNGGU;
        $pickup->save();

        return $pickup;
    }
}
