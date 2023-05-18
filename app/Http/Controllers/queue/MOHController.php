<?php

namespace App\Http\Controllers\queue;

use App\Http\Controllers\Controller;
use App\Models\MohFile;
use App\Models\MusicOnHold;
use App\Models\Queue;
use App\Services\MOHService;
use App\Services\QueueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MOHController extends Controller
{
    public function create_moh(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            "name" => "required|string",
            "description" => "required|string",
        ]);
        $moh_to_add = new MusicOnHold();
        $moh_to_add->name = $request->name;
        $moh_to_add->description = $request->description;

        $moh = (new MOHService)->create_moh($moh_to_add, $company_id);
        return response()->json([
            'message' => 'successfully saved',
            'moh' => $moh
        ], 200);
    }

    public function update_moh(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            "name" => "required|string",
            "description" => "required|string",
            "id" => "required|exists:music_on_holds,id"
        ]);
        $moh_to_add = new MusicOnHold();
        $moh_to_add->id = $request->id;
        $moh_to_add->name = $request->name;
        $moh_to_add->description = $request->description;

        $moh = (new MOHService)->update_moh($moh_to_add, $company_id);
        return response()->json([
            'message' => 'successfully updated',
            'moh' => $moh
        ], 200);
    }


    public function create_moh_file_bulk(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            "moh_id" => "required|integer|exists:music_on_holds,id",
        ]);
        $moh_files = $request->moh_files;
        foreach ($moh_files as $key => $moh_file) {
            $moh_file_request = new Request($moh_file);
            $moh_file_request->validate([
                "name" => "required|string",
                "file_url" => "required|url",
                "sequence" => "required|integer"
            ]);
        }

        $error = array();
        $has_error = false;
        try {
            DB::beginTransaction();
            foreach ($moh_files as $key => $moh_file) {
                $duplicate_moh = MohFile::where(["name" => $moh_file["name"], "moh_id" => $request->moh_id])->first();
                if ($duplicate_moh) {
                    $has_error = true;
                    $error[$key]["data"] =  $moh_file["name"];
                    $error[$key]["message"] = "duplicate name";
                    throw ValidationException::withMessages($error);
                } else {
                    MohFile::create([
                        "name" => $moh_file["name"],
                        "file_url" => $moh_file["file_url"],
                        "sequence" => $moh_file["sequence"],
                        "moh_id" => $request->moh_id,
                        "company_id" => $company_id
                    ]);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => ($has_error) ? 'Please check your data' : 'added successfully',
                'has_eror' => $has_error,
                "error_message" => $error
            ], ($has_error) ? 422 : 200);
        }
        return response()->json([
            'message' => ($has_error) ? 'Please check your data' : 'added successfully',
            'has_eror' => $has_error,
            "error_message" => $error
        ], ($has_error) ? 422 : 200);
    }

    public function rename_moh_file(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            "moh_file_id" => "required|integer|exists:moh_files,id",
            "name" => "required|string"
        ]);

        $existing_moh_file = MohFile::find($request->moh_file_id);
        if ($existing_moh_file->company_id == $company_id) {
            $check_duplicate = MohFile::where(["name" => $request->name, "moh_id" => $existing_moh_file->moh_id])->first();
            if ($check_duplicate) {
                if ($check_duplicate->id != $existing_moh_file->id) {
                    return response()->json([
                        'message' => 'file with the same name exists',
                    ], 422);
                }
            }
            $existing_moh_file->name = $request->name;
            $existing_moh_file->save();
            return response()->json([
                'message' => 'successfully updated',
                'moh_file' => $existing_moh_file
            ], 200);
        } else {
            return response()->json([
                'message' => 'unauthorized',
            ], 403);
        }
    }

    public function remove_moh_file(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            "moh_file_id" => "required|integer|exists:moh_files,id"
        ]);

        $moh_file = MohFile::find($request->moh_file_id);
        if ($moh_file->company_id == $company_id) {
            $moh_file->delete();
        } else {
            return response()->json([
                'message' => 'unauthorized',
            ], 403);
        }
        return response()->json([
            'message' => 'successfully removed'
        ], 200);
    }

    public function re_order_files(Request $request)
    {
        $company_id = $request->user()->company_id;
        $validated_data = $request->validate([
            "moh_id" => "required|integer|exists:music_on_holds,id"
        ]);
        try {
            DB::beginTransaction();
            foreach ($request->moh_files as $key => $moh_file) {
                $duplicate_moh = MohFile::find($moh_file["file_id"]);
                $duplicate_moh->sequence = $moh_file["sequence"];
                $duplicate_moh->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => "some error occured"
            ], 422);
        }
        return response()->json([
            'message' => 'successfully rearanged'
        ], 200);
    }

    public function get_all_moh()
    {
        $company_id = (Request())->user()->company_id;
        $music_on_holds = MusicOnHold::where("company_id", $company_id)->get();
        foreach ($music_on_holds as $key => $music_on_hold) {
            $music_on_holds[$key]["moh_files"] = MohFile::where('moh_id', $music_on_hold->id)->orderBy('sequence')->get();
        }

        return $music_on_holds;
    }
}
