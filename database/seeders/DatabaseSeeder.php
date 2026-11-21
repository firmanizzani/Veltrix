<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (
            [['DPK', 'Depok'], ['JKT', 'Jakarta'], ['BGR', 'Bogor']]
            as [$code, $city]
        ) {
            Branch::firstOrCreate(
                ['code' => $code],
                [
                    'name' => 'Veltrix Coffee — ' . $city,
                    'city' => $city,
                    'address' => 'Alamat cabang belum diisi',
                ],
            );
        }
    }
}
