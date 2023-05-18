<?php

namespace App\Services;

use App\Models\DidList;
use App\Models\IVR;
use Illuminate\Validation\ValidationException;

class IVRService
{
    public function create_ivr($name, $description, $company_id)
    {
        $check_duplicate = IVR::where(["name" => $name, "company_id" => $company_id])->first();
        if ($check_duplicate) {
            throw ValidationException::withMessages(["You have an IVR with the same name"]);
        } else {
            $ivr = IVR::create([
                "name" => $name,
                "description" => $description,
                "company_id" => $company_id
            ]);

            return $ivr;
        }
    }

    public function update_ivr($id, $name, $description, $company_id)
    {
        $check_duplicate = IVR::where(["name" => $name, "company_id" => $company_id])->first();
        if ($check_duplicate) {
            if ($check_duplicate->id != $id)
                throw ValidationException::withMessages(["You have an IVR with the same name"]);
        }
        $ivr_to_edit = IVR::find($id);
        $ivr_to_edit->name = $name;
        $ivr_to_edit->description = $description;
        $ivr_to_edit->save();

        return $ivr_to_edit;
    }

    public function assign_ivr_to_did($ivr_id, $did_id, $company_id)
    {
        $did = DidList::find($did_id);
        $ivr = IVR::find($ivr_id);
        // return $did;
        if ($did->company_id == $company_id && $ivr->company_id == $company_id) {
            if ($did->ivr_id == null) {
                $did->ivr_id = $ivr_id;
                $did->save();
                return $did;
            } else {
                throw ValidationException::withMessages(["DID has been assigned already"]);
            }
        } else {
            abort(403, 'Unauthorised');
        }
    }

    public function delink_ivr_to_did($did_id, $company_id)
    {
        $did = DidList::find($did_id);
        // return $did;
        if ($did->company_id == $company_id) {
            if ($did->ivr == null) {
                throw ValidationException::withMessages(["DID has been vacated already"]);
            } else {
                $did->ivr_id = null;
                $did->save();
                return $did;
            }
        } else {
            abort(403, 'Unauthorised');
        }
    }
}
