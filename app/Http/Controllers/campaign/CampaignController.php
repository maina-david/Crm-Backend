<?php

namespace App\Http\Controllers\campaign;

use App\Http\Controllers\Controller;
use App\Models\AgentLedCampaignSetting;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\CampaignGroup;
use App\Models\CampaignType;
use App\Models\CampaignWorkingHour;
use App\Models\CentralizedForm;
use App\Models\ContactForm;
use App\Models\FormAttribute;
use App\Models\FormAttributeOption;
use App\Models\Group;
use App\Models\SmsAccount;
use App\Models\SmsCampaignSetting;
use App\Models\SmsNumber;
use App\Models\SurveyResponse;
use App\Models\SurveyResponseData;
use App\Models\VoiceBroadcastSetting;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CampaignController extends Controller
{
    public function get_campaign_type()
    {
        return CampaignType::get();
    }


    public function create_campaign_once(Request $request)
    {
        // return $request;
        $validate_data = $request->validate([
            "campaign_type_id" => "required|exists:campaign_types,name",
            "name" => "required|string",
            "description" => "required|string"
        ]);

        ////check based on  campaign_type
        if ($request->campaign_type_id == "AGENTLED") {
            $validate_data = $request->validate([
                "queue_id" => "required|exists:queues,id",
                "did_id" => "required|exists:did_lists,id",
            ]);
        } else if ($request->campaign_type_id == "VOICEBROADCAST") {
            $validate_data = $request->validate([
                "audio_url" => "required|url",
                "did_id" => "required|exists:did_lists,id",
            ]);
        } else if ($request->campaign_type_id == "SMSCAMPAIGN") {
            $validate_data = $request->validate([
                "sms_message" => "required|string",
                "sender_id" => "required|exists:sms_numbers,id",
            ]);
        }

        $check_duplicate = Campaign::where(["name" => $request->name, "company_id" => $request->user()->id])->first();
        if ($check_duplicate) {
            throw ValidationException::withMessages(["You have already created a campaign with the same name"]);
        }

        $campaign_data = Campaign::create([
            "name" => $request->name,
            "description" => $request->description,
            "campaign_type_id" => $request->campaign_type_id,
            "company_id" => $request->user()->company_id,
            "status" => "ACTIVE"
        ]);

        if ($campaign_data->campaign_type_id == "VOICEBROADCAST") {
            $setting_exist = VoiceBroadcastSetting::where("campaign_id", $request->campaign_id)->first();

            if ($setting_exist) {
                $setting_exist->audio_url = $request->audio_url;
                $setting_exist->did = $request->did_id;
                $setting_exist->save();
            } else {
                VoiceBroadcastSetting::create([
                    "campaign_id" => $campaign_data->id,
                    "audio_url" => $request->audio_url,
                    "did" => $request->did_id
                ]);
            }
        } else if ($campaign_data->campaign_type_id == "AGENTLED") {
            $setting_exist = AgentLedCampaignSetting::where("campaign_id", $request->campaign_id)->first();

            if ($setting_exist) {
                $setting_exist->audio_url = $request->audio_url;
                $setting_exist->did = $request->did_id;
                $setting_exist->save();
            } else {
                AgentLedCampaignSetting::create([
                    "campaign_id" => $campaign_data->id,
                    "queue_id" => $request->queue_id,
                    "did" => $request->did_id
                ]);
            }
        } else if ($campaign_data->campaign_type_id == "SMSCAMPAIGN") {

            $setting_exist = SmsCampaignSetting::where("campaign_id", $request->campaign_id)->first();

            if ($setting_exist) {
                $setting_exist->sms_account_id = $request->sender_id;
                $setting_exist->sms_text = $request->sms_message;
                $setting_exist->save();
            } else {
                SmsCampaignSetting::create([
                    "campaign_id" => $campaign_data->id,
                    "sms_account_id" => $request->sender_id,
                    "sms_text" => $request->sms_message
                ]);
            }
        }

        return response()->json([
            'message' => 'Successfully added'
        ], 200);
    }

    public function create_campaign(Request $request)
    {
        $validate_data = $request->validate([
            "campaign_type_id" => "required|exists:campaign_types,name",
            "name" => "required|string",
            "description" => "required|string"
        ]);

        $check_duplicate = Campaign::where(["name" => $request->name, "company_id" => $request->user()->id])->first();
        if ($check_duplicate) {
            throw ValidationException::withMessages(["You have already created a campaign with the same name"]);
        }

        Campaign::create([
            "name" => $request->name,
            "description" => $request->description,
            "campaign_type_id" => $request->campaign_type_id,
            "company_id" => $request->user()->company_id,
            "status" => "ACTIVE"
        ]);

        return response()->json([
            'message' => 'Successfully added'
        ], 200);
    }

    public function get_campaigns(Request $request)
    {
        $company_id = $request->user()->company_id;
        return Campaign::with(["campaign_type", "groups", "survey_form"])->where("company_id", $company_id)->get();
    }

    public function get_campaigns_table()
    {
        return Campaign::with(["campaign_type", "groups", "survey_form"])->where("company_id", Auth::user()->company_id)->paginate();
    }

    public function update_campaign(Request $request)
    {
        $validate_data = $request->validate([
            "campaign_id" => "required|exists:campaigns,id",
            "name" => "required|string",
            "description" => "required|string"
        ]);

        $check_duplicate = Campaign::where(["name" => $request->name, "company_id" => $request->user()->id])->first();
        if ($check_duplicate) {
            if ($check_duplicate->id != $request->campaign_id)
                throw ValidationException::withMessages(["You have already created a campaign with the same name"]);
        }

        $campaign_update = Campaign::find($request->campaign_id);
        $campaign_update->name = $request->name;
        $campaign_update->description = $request->description;
        $campaign_update->save();

        return response()->json([
            'message' => 'Successfully updated'
        ], 200);
    }

    public function change_campaign_status(Request $request)
    {
        $validate_data = $request->validate([
            "campaign_id" => "required|exists:campaigns,id",
            "status" => "required"
        ]);

        $campaign_update = Campaign::find($request->campaign_id);
        if ($request->status == "START") {
            //check if DID and queue are set
            if ($campaign_update->campaign_type == "AGENTLED") {
                $agent_lend_camapign = AgentLedCampaignSetting::where("campaign_id", $request->campaign_id)->first();
                if (!$agent_lend_camapign) {
                    return response()->json(["Please add queue and did before starting the campaign!"], 422);
                }
            } else if ($campaign_update->campaign_type == "VOICEBROADCAST") {
                $voice_broadcast_setting = VoiceBroadcastSetting::where("campaign_id", $request->campaign_id)->first();
                if (!$voice_broadcast_setting) {
                    return response()->json(["Please add audio file and did before starting the campaign!"], 422);
                }
            } else if ($campaign_update->campaign_type == "SMSCAMPAIGN") {
                $sms_campaign_setting = SmsCampaignSetting::where("campaign_id", $request->campaign_id)->first();
                if (!$sms_campaign_setting) {
                    return response()->json(["Please add SMS text and sender id before starting the campaign!"], 422);
                }
            }
            $working_hour_check = CampaignWorkingHour::where("campaign_id", $request->campaign_id)->first();
            if (!$working_hour_check) {
                return response()->json(["Please setup working hour before starting the campaign!"], 422);
            }
        }
        $campaign_update->status = $request->status;
        $campaign_update->save();

        return response()->json([
            'message' => 'Successfully updated'
        ], 200);
    }

    public function get_campaign_working_hour(Request $request)
    {
        $validat_data = $request->validate([
            "campaign_id" => "required|exists:campaigns,id"
        ]);

        $company_id = $request->user()->company_id;
        return CampaignWorkingHour::where("campaign_id", $request->campaign_id)->get();
    }

    public function add_group_to_campaign(Request $request)
    {
        $validat_data = $request->validate([
            "campaign_id" => "required|exists:campaigns,id"
        ]);

        $company_id = $request->user()->company_id;
        $campaign_update = Campaign::find($request->campaign_id);
        if ($campaign_update->company_id != $company_id) {
            throw ValidationException::withMessages(["anuthorized"]);
        } else {
            $has_error = false;
            foreach ($request->group_ids as $key => $group) {
                $exist = CampaignGroup::where(["group_id" => $group, "campaign_id" => $request->campaign_id])->first();
                if (!$exist) {
                    $group_check = Group::find($group);
                    if ($group_check) {
                        if ($group_check->company_id == $company_id) {
                        } else {
                            throw ValidationException::withMessages(["anuthorized"]);
                        }
                    } else {
                        throw ValidationException::withMessages(["Group with group id " . $group . " not found"]);
                    }
                }
            }
            foreach ($request->group_ids as $key => $group) {
                CampaignGroup::create([
                    "campaign_id" => $request->campaign_id,
                    "group_id" => $group
                ]);
            }
        }
        return response()->json([
            'message' => 'Successfully updated'
        ], 200);
    }

    public function remove_group_from_campaign(Request $request)
    {
        $validat_data = $request->validate([
            "group_id" => "required|exists:groups,id",
            "campaign_id" => "required|exists:campaigns,id"
        ]);
        $company_id = $request->user()->company_id;

        $group_check = Group::find($request->group_id);
        $campaign_check = Group::find($request->campaign_id);

        if ($group_check->company_id != $company_id) {
            throw ValidationException::withMessages(["anuthorized"]);
        }
        if ($group_check->company_id != $company_id) {
            throw ValidationException::withMessages(["anuthorized"]);
        }

        CampaignGroup::where([
            "group_id" => $request->group_id,
            "campaign_id" => $request->campaign_id
        ])->delete();

        return response()->json([
            'message' => 'Successfully removed'
        ], 200);
    }

    public function add_campaign_working_hour(Request $request)
    {
        $request->validate([
            "campaign_id" => "required|exists:campaigns,id",
            "working_hour" => "required|array"
        ]);

        $campaign_check = Campaign::with("campaign_working_hour")->find($request->campaign_id);
        $company_id = Auth::user()->company_id;

        if ($campaign_check->company_id != $company_id) {
            throw ValidationException::withMessages(["Campaign doesn't belong to your company!"]);
        }

        if (!empty($campaign_check->campaign_working_hour)) {
            CampaignWorkingHour::where("campaign_id", $campaign_check->id)->delete();
        }

        foreach ($request->working_hour as $key => $working_hour) {
            CampaignWorkingHour::create([
                "campaign_id" => $campaign_check->id,
                "date" => $working_hour["date"],
                "starting_time" => $working_hour["starting_time"],
                "end_time" => $working_hour["end_time"]
            ]);
        }
        return response()->json([
            'message' => 'Successfully added ' . $campaign_check->name . ' working hours!'
        ], 200);
    }

    public function remove_campaign_working_hour(Request $request)
    {
        $validat_data = $request->validate([
            "campaign_id" => "required|exists:campaigns,id",
            "working_hour_id" => "required|exists:campaign_working_hours,id"
        ]);

        $campaign_check = Campaign::with("campaign_working_hour")->find($request->campaign_id);
        $company_id = $request->user()->company_id;

        if ($campaign_check->company_id != $company_id) {
            throw ValidationException::withMessages(["anuthorized"]);
        }

        CampaignWorkingHour::where("id", $request->working_hour_id)->delete();

        return response()->json([
            'message' => 'Successfully removed'
        ], 200);
    }

    public function campaign_setting_setup(Request $request)
    {
        $validat_data = $request->validate([
            "campaign_id" => "required|exists:campaigns,id",
        ]);
        $company_id = $request->user()->company_id;
        $campaign_data = Campaign::find($request->campaign_id);
        if ($campaign_data->company_id != $company_id) {
            throw ValidationException::withMessages(["anuthorized"]);
        }
        if ($campaign_data->campaign_type_id == "VOICEBROADCAST") {
            $validat_data = $request->validate([
                "audio_url" => "required|url",
                "did" => "required|exists:did_lists,id",
            ]);

            $setting_exist = VoiceBroadcastSetting::where("campaign_id", $request->campaign_id)->first();

            if ($setting_exist) {
                $setting_exist->audio_url = $request->audio_url;
                $setting_exist->did = $request->did;
                $setting_exist->save();
            } else {
                VoiceBroadcastSetting::create([
                    "campaign_id" => $request->campaign_id,
                    "audio_url" => $request->audio_url,
                    "did" => $request->did
                ]);
            }
        } else if ($campaign_data->campaign_type_id == "AGENTLED") {
            $validat_data = $request->validate([
                "queue_id" => "required|exists:queues,id",
                "did" => "required|exists:did_lists,id",
            ]);

            $setting_exist = AgentLedCampaignSetting::where("campaign_id", $request->campaign_id)->first();

            if ($setting_exist) {
                $setting_exist->audio_url = $request->audio_url;
                $setting_exist->did = $request->did;
                $setting_exist->save();
            } else {
                AgentLedCampaignSetting::create([
                    "campaign_id" => $request->campaign_id,
                    "queue_id" => $request->queue_id,
                    "did" => $request->did
                ]);
            }
        } else if ($campaign_data->campaign_type_id == "SMSCAMPAIGN") {
            $validat_data = $request->validate([
                "sender_id" => "required|exists:sms_numbers,id",
                "sms_text" => "required|text",
            ]);

            $setting_exist = AgentLedCampaignSetting::where("campaign_id", $request->campaign_id)->first();

            if ($setting_exist) {
                $setting_exist->sms_account_id = $request->sender_id;
                $setting_exist->sms_text = $request->sms_message;
                $setting_exist->save();
            } else {
                SmsCampaignSetting::create([
                    "campaign_id" => $campaign_data->id,
                    "sms_account_id" => $request->sender_id,
                    "sms_text" => $request->sms_message
                ]);
            }
        }

        return response()->json([
            'message' => 'Successfully set'
        ], 200);
    }

    public function upload_contact_campaign(Request $request)
    {
        $validat_data = $request->validate([
            "campaign_id" => "required|exists:campaigns,id"
        ]);

        $contact_to_add = array();
        foreach ($request->contacts as $key => $contact) {
            $contact_pre["campaign_id"] = $request->campaign_id;
            $contact_pre["name"] = $contact["name"];
            $contact_pre["phone_number"] = $contact["phone_number"];
            $contact_pre["status"] = "NOTCONTACTED";
            $contact_pre["created_at"] = now();
            $contact_pre["updated_at"] = now();

            $contact_to_add[] = $contact_pre;
        }
        // return $contact_to_add;
        CampaignContact::insert($contact_to_add);

        return response()->json([
            'message' => 'Successfully uploaded'
        ], 200);
    }

    public function select_contact_for_campaign(Request $request)
    {
        $validat_data = $request->validate([
            "contact_form_id" => "required|exists:contact_forms,id",
            "selections" => "array|required"
        ]);

        $company_id = $request->user()->company_id;
        $contact_form = ContactForm::find($request->contact_form_id);

        if ($contact_form->company_id != $company_id) {
            throw ValidationException::withMessages(["anuthorized"]);
        }
        foreach ($request->selections as $selection) {
            $select = $selection["form_item_id"];
        }
    }

    public function add_question_camapign(Request $request)
    {
        $request->validate([
            "campaign_id" => "required|exists:campaigns,id",
            "form_id" => "required|exists:centralized_forms,id"
        ]);

        $centrlized_form = CentralizedForm::find($request->form_id);
        if ($centrlized_form->type != "SURVEYFROM") {
            return response()->json(["This form is not allowed to be used in campaigns!"], 422);
        }
        $campaign = Campaign::find($request->campaign_id);
        $campaign->update(["survey_form_id" => $request->form_id]);
        return response()->json(["successfuly added!"], 200);
    }

    public function get_sender_id(Request $request)
    {
        $company_id = Auth::user()->company_id;

        return SmsAccount::where("company_id", $company_id)->get();
    }

    public function get_queue_from_camapaign(Request $request)
    {
        $request->validate(["campaign_id" => "required|exists:campaigns,id"]);
        $group = Campaign::find($request->campaign_id)->groups[0];
        return $group->queues;
    }

    /**
     * It returns all the contacts associated with a campaign
     * 
     * @param Request request The request object.
     * 
     * @return A collection of CampaignContact objects
     */
    public function get_campaign_contact(Request $request)
    {
        $request->validate(["campaign_id" => "required|exists:campaigns,id"]);
        return CampaignContact::where("campaign_id", $request->campaign_id)->get();
    }

    public function get_campaign_contacts_table(Request $request)
    {
        $request->validate(["campaign_id" => "required|exists:campaigns,id"]);
        return CampaignContact::where("campaign_id", $request->campaign_id)->paginate();
    }

    public function filter_campain_contacts(Request $request)
    {
    }

    public function survey_submit(Request $request)
    {
        $request->validate([
            "campaign_contact_id" => "required|exists:campaign_contacts,id",
            "campaign_form_id" => "required|exists:centralized_forms,id",
            "survey_responses" => "required|array"
        ]);
        $campaign_contact = CampaignContact::with("campaign")->find($request->campaign_contact_id);

        if ($campaign_contact->campaign->company_id != Auth::user()->company_id) {
            return response()->json(["You don't have access right to the campaign"], 401);
        }

        try {
            DB::beginTransaction();
            $survey_response_entry = SurveyResponse::create([
                "campaign_id" => $campaign_contact->campaign_id,
                "campaign_contact_id" => $request->campaign_contact_id
            ]);
            // foreach ($request->survey_responses as  $survey_response) {
            //     $survey_response_request = new Request($survey_response);
            //     return $survey_response;
            //     $survey_response_request->validate([
            //         'form_item_id' => 'required|exists:form_attrs,id',
            //         'response' => 'required'
            //     ]);

            //     $form_item = FormAttribute::find($survey_response_request->form_item_id);
            //     if ($form_item->data_type == 'select' || $form_item->data_type == 'radio') {
            //         $survey_response_request->validate([
            //             "response" => "exists:form_attribute_options,id"
            //         ]);
            //         $option = FormAttributeOption::find($survey_response_request->response);
            //         SurveyResponseData::create([
            //             "survey_id" => $request->campaign_id,
            //             "survey_response_id" => $survey_response_entry->id,
            //             "survey_form_attr_id" => $survey_response_request->form_item_id,
            //             "value" => $option->id
            //         ]);
            //     } else if ($form_item->data_type == 'check') {
            //         foreach ($survey_response_request->response as $option) {
            //             $options = FormAttributeOption::find($option);
            //             SurveyResponseData::create([
            //                 "survey_id" => $request->campaign_id,
            //                 "survey_response_id" => $survey_response_entry->id,
            //                 "survey_form_attr_id" => $survey_response_request->form_item_id,
            //                 "value" => $options->id
            //             ]);
            //         }
            //     } else {
            //         SurveyResponseData::create([
            //             "survey_id" => $request->campaign_id,
            //             "survey_response_id" => $survey_response_entry->id,
            //             "survey_form_attr_id" => $survey_response_request->form_item_id,
            //             "value" => $request->response
            //         ]);
            //     }
            // }
            $form_items = FormAttribute::where("form_id", $request->campaign_form_id)->get();
            // return $request->survey_responses;
            foreach ($form_items as $key => $form_item) {
                $value = $request->survey_responses[$form_item->id];
                $form_item_id = $form_item->id;

                SurveyResponseData::create([
                    "survey_id" =>  $campaign_contact->campaign_id,
                    "survey_response_id" => $survey_response_entry->id,
                    "survey_form_attr_id" => $form_item_id,
                    "value" => $value
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        return response()->json(["successfuly added!"], 200);
    }
}
