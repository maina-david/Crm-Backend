<?php

namespace Database\Seeders;

use App\Models\SipList;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SIPSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $agent_sip = new SipList();
        $agent_sip->sip_id = "1000";
        $agent_sip->sip_id = "10@00#";
        $company_contact_types = array(
            array('lang_code' => 'EN', 'lang_name' => 'English'),
            array('lang_code' => 'FR', 'lang_name' => 'French'),
        );

        $sip_list_array = array();
        for ($i = 1000; $i < 10000; $i++) {
            $j = $i + 9000;
            $temp = str_split($j, 2);
            $password = $temp[0] . "@" . $temp[1] . "#";
            $sip_list_array[] = array('sip_id' => $j, 'password' => $password);
        }
        DB::table('sip_lists')->insert($sip_list_array);
        $users = User::get();
        foreach ($users as $key => $user) {
            if ($user->sip_id != null) {
                SipList::where("id", $user->sip_id)->update([
                    "user_id" => $user->id
                ]);
            }
        }
    }
}