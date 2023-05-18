<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('channels')->insert([
            [
                "name" => 'WhatsApp',
                "active" => true
            ],
            [
                "name" => 'Facebook',
                "active" => true
            ],
            [
                "name" => 'Instagram',
                "active" => true
            ],
            [
                "name" => 'Twitter',
                "active" => true
            ],
            [
                "name" => 'Email',
                "active" => true
            ],
            [
                "name" => 'SMS',
                "active" => true
            ]
        ]);
    }
}