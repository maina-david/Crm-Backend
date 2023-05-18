<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CampaignTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('campaign_types')->insert([
            [
                "name" => 'VOICEBROADCAST',
                "description" => 'voice broadcast campaign'
            ],
            [
                "name" => 'AGENTLED',
                "description" => 'Agent led campaign'
            ],
            [
                "name" => 'SMSCAMPAIGN',
                "description" => 'SMS campaign'
            ]
        ]);
    }
}
