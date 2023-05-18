<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class yesNoSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('yas_no_tables')->delete();
        $company_contact_types = array(
            array('name' => 'Yes'),
            array('name' => 'No'),
        );

        DB::table('yas_no_tables')->insert($company_contact_types);
    }
}
