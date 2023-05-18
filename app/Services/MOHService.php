<?php

namespace App\Services;

use App\Models\MusicOnHold;
use Illuminate\Validation\ValidationException;

class MOHService
{
    public function create_MOH(MusicOnHold $moh, $company_id)
    {
        $check_duplicate = MusicOnHold::where(["name" => $moh->name, "company_id" => $company_id])->first();
        if ($check_duplicate)
            throw ValidationException::withMessages(["You have MOH with the same name."]);
        $moh_added = MusicOnHold::create([
            "name" => $moh->name,
            "description" => $moh->description,
            "company_id" => $company_id
        ]);
        return $moh_added;
    }

    public function update_moh(MusicOnHold $moh, $company_id)
    {
        $check_duplicate = MusicOnHold::where(["name" => $moh->name, "company_id" => $company_id])->first();
        if ($check_duplicate)
            if ($check_duplicate->id != $moh->id)
                throw ValidationException::withMessages(["You have MOH with the same name."]);

        $moh_added = MusicOnHold::find($moh->id);
        $moh_added->name = $moh->name;
        $moh_added->description = $moh->description;
        $moh_added->save();
        return $moh_added;
    }
}
