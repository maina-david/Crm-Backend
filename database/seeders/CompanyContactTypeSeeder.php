<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyContactTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('company_contact_types')->delete();
        $company_contact_types = array(
            array('name' => 'BILLINGCONTACT','created_at'=>now(),'updated_at'=>now()),
            array('name' => 'SUPPORTCONTACT','created_at'=>now(),'updated_at'=>now()),
        );

        DB::table('company_contact_types')->insert($company_contact_types);
    }
}
