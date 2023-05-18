<?php

namespace App\Services;

use App\Mail\DIDAddedMail;
use App\Mail\DIDRemovedMail;
use App\Models\AccessProfile;
use App\Models\DidList;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ChannelService
{
    protected $users;
    public function __construct()
    {
        $access_profile = AccessProfile::where("access_name", "Channel management")->get();
        $profile_ids = array();
        foreach ($access_profile as $key => $profile) {
            // $this->users = $profile->role_profile_id;
            $this->users = DB::select('SELECT * FROM `users` WHERE `id` IN (select user_id FROM user_access_profiles WHERE access_profile_id = ?) AND company_id=?', [$profile->role_profile_id, Auth::user()->company_id]);
        }
    }

    public function assign_phone_number($did_data)
    {
        $check_did_available = DidList::where(["id" => $did_data["id"], "allocation_status" => "FREE"])->first();
        if ($check_did_available) {
            $did_list_obj = DidList::find($check_did_available->id);
            $did_list_obj->allocation_status = "ALLOCATED";
            $did_list_obj->company_id = $did_data['company_id'];
            $did_list_obj->save();
            \App\Helpers\LogActivity::addToLog('New did added to your company DID number: ' . $check_did_available->did);
            foreach ($this->users as $key => $user) {
                Mail::to($user->email)->send(new DIDAddedMail($user, $check_did_available->did));
            }
            return $did_list_obj;
        } else {
            throw ValidationException::withMessages(["The number is in use"]);
        }
    }

    public function remove_phone_number($did_data)
    {
        $check_did_available = DidList::where(["id" => $did_data["id"], "allocation_status" => "ALLOCATED"])->first();
        if ($check_did_available) {
            $did_list_obj = DidList::find($check_did_available->id);
            $did_list_obj->allocation_status = "FREE";
            $did_list_obj->ivr_id = NULL;
            $did_list_obj->company_id = NULL;
            $did_list_obj->save();
            \App\Helpers\LogActivity::addToLog('DID removed from your company DID number: ' . $check_did_available->did);
            foreach ($this->users as $key => $user) {
                Mail::to($user->email)->send(new DIDRemovedMail($user, $check_did_available->did));
            }
            return $did_list_obj;
        } else {
            throw ValidationException::withMessages(["number removed already"]);
        }
    }
}
