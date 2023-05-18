<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DIDListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('did_lists')->delete();
        $did_list = array(
            array('did' => '07796671443', 'carrier_id' => '1', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '07796671443', 'carrier_id' => '1', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '07796671443', 'carrier_id' => '2', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '07796671443', 'carrier_id' => '2', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '07796671443', 'carrier_id' => '1', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0938048040', 'carrier_id' => '3', 'created_at' => now(), 'updated_at' => now()),
        );

        DB::table('did_lists')->insert($did_list);
    }
}
