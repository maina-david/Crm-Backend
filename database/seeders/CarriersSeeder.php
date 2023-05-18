<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CarriersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('carriers')->delete();
        $carriers = array(
            array('name' => 'Safaricom Kenya', 'country_code' => 'KE', 'created_at' => now(), 'updated_at' => now()),
            array('name' => 'Telkom Kenya', 'country_code' => 'KE', 'created_at' => now(), 'updated_at' => now()),
            array('name' => 'Airtel Kenya', 'country_code' => 'KE', 'created_at' => now(), 'updated_at' => now()),
            array('name' => 'Ethiotelecom', 'country_code' => 'ET', 'created_at' => now(), 'updated_at' => now()),
            array('name' => 'Safaricom Ethiopia plc', 'country_code' => 'ET', 'created_at' => now(), 'updated_at' => now()),
        );

        DB::table('carriers')->insert($carriers);
    }
}
