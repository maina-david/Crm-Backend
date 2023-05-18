<?php

namespace App\Http\Controllers\company;

use App\Http\Controllers\Controller;
use App\Models\BlackList;
use App\Models\CallcenterHoliday;
use App\Models\CallcenterOffMusic;
use App\Models\CallcenterSetting;
use App\Models\CallcenterSettingAudioFile;
use App\Models\WorkingHours;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Prophecy\Call\CallCenter;

class CallcenterSettingController extends Controller
{
    public function setup_penality_sl(Request $request)
    {
        $company_id = $request->user()->company_id;
        $user_id = $request->user()->id;
        $penality = $request->penality;
        $service_level = $request->service_level;
        $existing_setting = CallcenterSetting::where("company_id", $company_id)->first();
        if ($existing_setting) {
            $update_setting = CallcenterSetting::find($existing_setting->id);
            $update_setting->max_penality = $penality;
            $update_setting->service_level = $service_level;
            $update_setting->save();
        } else {
            CallcenterSetting::create([
                'company_id' => $company_id,
                'max_penality' => $penality,
                'service_level' => $service_level
            ]);
        }
        return response()->json([
            'message' => 'successfully updated',
        ], 200);
    }

    public function switch_callcenter_off(Request $request)
    {
        $company_id = $request->user()->company_id;
        $user_id = $request->user()->id;
        $existing_setting = CallcenterSetting::where("company_id", $company_id)->first();
        if ($existing_setting) {
            $update_setting = CallcenterSetting::find($existing_setting->id);
            $update_setting->status = ($update_setting->status == "ACTIVE") ? "DEACTIVATED" : "ACTIVE";
            $update_setting->save();
        } else {
            CallcenterSetting::create([
                'company_id' => $company_id,
                'status' => "ACTIVE"
            ]);
        }
        return response()->json([
            'message' => 'successfully updated',
        ], 200);
    }

    public function get_callcenter_basic_settings(Request $request)
    {
        $company_id = $request->user()->company_id;
        $existing_setting = CallcenterSetting::where("company_id", $company_id)->first();
        return response()->json([
            'settings' => $existing_setting,
        ], 200);
    }

    public function add_to_blacklist(Request $request)
    {
        $company_id = $request->user()->company_id;
        $user_id = $request->user()->id;
        $phone_number = $request->phone_number;
        $is_duplicate = BlackList::where(["company_id" => $company_id, "phone_number" => $phone_number])->first();
        if (!$is_duplicate) {
            BlackList::create([
                "phone_number" => $phone_number,
                "company_id" => $company_id,
                "add_by" => $user_id
            ]);
            return response()->json([
                'message' => 'successfully added',
            ], 200);
        } else {
            throw ValidationException::withMessages(["number already blacklisted"]);
        }
    }

    public function remove_from_blacklist(Request $request)
    {
        $company_id = $request->user()->company_id;
        $black_list_id = $request->black_list_id;
        $is_duplicate = BlackList::where(["company_id" => $company_id, "id" => $black_list_id])->delete();
        return response()->json([
            'message' => 'successfully removed',
        ], 200);
    }

    public function get_blacklisted(Request $request)
    {
        $company_id = $request->user()->company_id;
        return BlackList::where(["company_id" => $company_id])->get();
    }

    public function add_callcenter_setting_audio(Request $request)
    {
        $validated_data = $request->validate([
            "name" => "required|string",
            "url" => "required|url"
        ]);
        $company_id = $request->user()->company_id;

        $check_duplicate = CallcenterSettingAudioFile::where([
            "name" => $request->name,
            "company_id" => $company_id
        ])->first();
        if ($check_duplicate) {
            throw ValidationException::withMessages(["you have a file with the same name"]);
        } else {
            CallcenterSettingAudioFile::create([
                "name" => $request->name,
                "url" => $request->url,
                "company_id" => $company_id
            ]);
            return response()->json([
                'message' => 'successfully added',
            ], 200);
        }
    }

    public function edit_callcenter_setting_audio_name(Request $request)
    {
        $validated_data = $request->validate([
            "file_id" => "required|exists:callcenter_setting_audio_files,id",
            "name" => "required|string"
        ]);
        $company_id = $request->user()->company_id;

        $check_duplicate = CallcenterSettingAudioFile::where([
            "name" => $request->name,
            "company_id" => $company_id
        ])->first();

        if ($check_duplicate) {
            if ($check_duplicate->id != $request->id)
                throw ValidationException::withMessages(["you have a file with the same name"]);
        }
        $data_update = CallcenterSettingAudioFile::find($request->file_id);
        $data_update->name = $request->name;
        $data_update->save();

        return response()->json([
            'message' => 'successfully updated',
        ], 200);
    }

    public function delete_callcenter_setting_audio(Request $request)
    {
        $validated_data = $request->validate([
            "file_id" => "required|exists:callcenter_setting_audio_files,id",
        ]);
        $company_id = $request->user()->company_id;
        $can_delete = CallcenterSettingAudioFile::where(["id" => $request->file_id, "company_id" => $company_id])->first();
        $is_on_holiday = CallcenterHoliday::where(["file_id" => $request->file_id, "company_id" => $company_id])->first();
        $is_on_working_hour = WorkingHours::where(["file_url" => $request->file_id, "company_id" => $company_id])->first();
        $is_on_callcenter_onoff = CallcenterOffMusic::where(["file_id" => $request->file_id, "company_id" => $company_id])->first();
        if ($can_delete && !$is_on_callcenter_onoff && !$is_on_holiday && !$is_on_working_hour) {
            CallcenterSettingAudioFile::where(["id" => $request->file_id])->delete();
            return response()->json([
                'message' => 'successfully removed',
            ], 200);
        } else if ($is_on_callcenter_onoff || $is_on_holiday || $is_on_working_hour) {
            // throw ValidationException::withMessages(["you can't delete file on use"]);
            return response()->json([
                'message' =>"you can't delete file on use",
            ], 422);
        } else {
            // throw ValidationException::withMessages(["file not found"]);
            return response()->json([
                'message' =>"file not found",
            ], 422);
        }
    }

    public function get_callcenter_setting_audio(Request $request)
    {
        $company_id = $request->user()->company_id;
        return CallcenterSettingAudioFile::where("company_id", $company_id)->get();
    }

    public function update_working_hour(Request $request)
    {
        $validated_data = $request->validate([
            "dates" => "required|array",
            "file_url" => "required|exists:callcenter_setting_audio_files,id",
            "from" => "required",
            "to" => "required"
        ]);
        $company_id = $request->user()->company_id;

        Carbon::macro('isTimeBefore', static function ($other) {
            return self::this()->format('Gis.u') < $other->format('Gis.u');
        });

        $is_the_time_okay = Carbon::parse($request->from)->isTimeBefore(Carbon::parse($request->to));
        if (!$is_the_time_okay) {
            throw ValidationException::withMessages(["the start time can't be later than the end time"]);
        }
        foreach ($request->dates as $key => $date) {
            $check_if_already_exist = WorkingHours::where(["date" => $date, "company_id" => $company_id])->first();
            if ($check_if_already_exist) {
                $update_working_hour = WorkingHours::find($check_if_already_exist->id);
                $update_working_hour->start_time = $request->from;
                $update_working_hour->end_time = $request->to;
                $update_working_hour->file_url = $request->file_url;
                $update_working_hour->save();
            } else {
                WorkingHours::create([
                    "date" => $date,
                    "start_time" => $request->from,
                    "end_time" => $request->to,
                    "file_url" => $request->file_url,
                    "company_id" => $company_id
                ]);
            }
        }
        return response()->json([
            'message' => 'successfully changed',
        ], 200);
    }

    public function get_working_hour(Request $request)
    {
        $company_id = $request->user()->company_id;
        return WorkingHours::with("file")->where("company_id", $company_id)->get();
    }

    public function add_contactcenter_holiday(Request $request)
    {
        $validated_data = $request->validate([
            "date" => "required|date",
            "name" => "required|string",
            "description" => "required|string",
            "file_id" => "required|exists:callcenter_setting_audio_files,id"
        ]);
        $company_id = $request->user()->company_id;

        $check_duplicate_dates = CallcenterHoliday::where(["date" => $request->date, "company_id" => $company_id])->first();
        if ($check_duplicate_dates) {
            throw ValidationException::withMessages(["you have already created holiday for the same day"]);
        }
        CallcenterHoliday::create([
            "date" => $request->date,
            "name" => $request->name,
            "description" => $request->description,
            "file_id" => $request->file_id,
            "company_id" => $company_id
        ]);
        return response()->json([
            'message' => 'successfully added',
        ], 200);
    }

    public function update_holiday(Request $request)
    {
        $validated_data = $request->validate([
            "id" => "required|exists:callcenter_holidays,id",
            "name" => "required|string",
            "description" => "required|string",
            "file_id" => "required|exists:callcenter_setting_audio_files,id"
        ]);
        $company_id = $request->user()->company_id;

        $holiday_data = CallcenterHoliday::find($request->id);
        $holiday_data->name = $request->name;
        $holiday_data->description = $request->description;
        $holiday_data->file_id = $request->file_id;
        $holiday_data->save();

        return response()->json([
            'message' => 'successfully updated',
        ], 200);
    }

    public function remove_holiday(Request $request)
    {
        $validated_data = $request->validate([
            "id" => "required|exists:callcenter_holidays,id",
        ]);
        $company_id = $request->user()->company_id;

        CallcenterHoliday::where("id", $request->id)->delete();

        return response()->json([
            'message' => 'successfully removed',
        ], 200);
    }

    public function get_holidays(Request $request)
    {
        $company_id = $request->user()->company_id;
        return CallcenterHoliday::with("file")->where("company_id", $company_id)->get();
    }

    public function callcenter_off_music(Request $request)
    {
        $validated_data = $request->validate([
            "file_id" => "required|exists:callcenter_setting_audio_files,id",
        ]);
        $company_id = $request->user()->company_id;
        $check_existing = CallcenterOffMusic::where("company_id", $company_id)->first();
        if ($check_existing) {
            $update_offmusic = CallcenterOffMusic::find($check_existing->id);
            $update_offmusic->file_id = $request->file_id;
            $update_offmusic->save();
        } else {
            CallcenterOffMusic::create([
                "company_id" => $company_id,
                "file_id" => $request->file_id
            ]);
        }
        return response()->json([
            'message' => 'successfully changed',
        ], 200);
    }

    public function get_callcenter_off(Request $request)
    {
        $company_id = $request->user()->company_id;
        return CallcenterOffMusic::with("file")->where("company_id", $company_id)->get();
    }
}
