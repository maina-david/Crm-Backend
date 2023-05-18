<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('language_lists')->delete();
        $company_contact_types = array(
            array('lang_code' => 'EN', 'lang_name' => 'English'),
            array('lang_code' => 'FR', 'lang_name' => 'French'),
        );

        DB::table('language_lists')->insert($company_contact_types);
    }
}
