<?php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Http\Resources\CampaignContactResource;
use App\Models\AccountData;
use App\Models\AccountFormAttrOption;
use App\Models\AccountType;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\FormAttribute;
use App\Models\SurveyResponse;
use App\Models\SurveyResponseData;
use Auth;
use DB;
use Illuminate\Http\Request;
use Nette\Utils\Json;

class CrmReportController extends Controller
{
    /**
     * It returns the name of the account type, the number of accounts and the number of contacts
     * associated with that account type
     * 
     * @return The name of the account type and the number of accounts and contacts associated with
     * that account type.
     */
    public function general_account_report()
    {
        return AccountType::where("company_id", Auth::user()->company_id)->withCount("accounts", "contacts")->get("name");
    }

    /**
     * It takes an account_form_item_id, and returns a report of the number of times each option has
     * been selected for that item
     * 
     * @param Request request The request object.
     * 
     * @return An array of the number of times each option was selected for a given account form item.
     */
    public function account_type_report(Request $request)
    {
        $account_form_item_id = $request->account_form_item_id;
        // $report_data = AccountData::where("account_form_attr_id", $account_form_item_id)->get()->groupBy("value");
        $report_data = AccountData::select('value', DB::raw('count(*) as total'))
            ->groupBy('value')
            ->where("account_form_attr_id", $account_form_item_id)
            ->get();
        $options = AccountFormAttrOption::where("account_form_attr_id", $account_form_item_id)->get();
        $report = array();
        foreach ($report_data as $rep) {
            foreach ($options as $option) {
                $report[$option->option_name] = 0;
                if ($option->id == $rep->value) {
                    $report[$option->option_name] = $rep->total;
                }
            }
        }
        return $report;
    }

    /**
     * It returns the cumulative and detailed data of a campaign
     * 
     * @param Request request The request object.
     * 
     * @return The cumulative data and detail data of the campaign.
     */
    public function campaign_report(Request $request)
    {
        $request->validate([
            "campaign_id" => "required|exists:campaigns,id"
        ]);
        $campaign = Campaign::find($request->campaign_id);
        if ($campaign->company_id != Auth::user()->company_id) {
            return response()->json(["You don't have access to this campaign!"], 401);
        }
        $campaign_contact_cumulative = CampaignContact::where("campaign_id", $request->campaign_id)
            ->selectRaw('count(campaign_contacts.id) as total')
            ->selectRaw("desposition")
            ->groupBy(['desposition'])
            ->get();
        $campaign_contact = CampaignContact::where("campaign_id", $request->campaign_id)
            ->get();
        return response()->json(["cumulative_data" => $campaign_contact_cumulative, "detail_data" => $campaign_contact], 200);
    }

    /**
     * It returns a paginated list of all the contacts that were called in a campaign, along with a
     * cumulative count of the disposition of each contact
     * 
     * @param Request request The request object
     * 
     * @return The cumulative data and detail data of the campaign.
     */
    public function campaign_report_table(Request $request)
    {
        $request->validate([
            "campaign_id" => "required|exists:campaigns,id"
        ]);
        $campaign = Campaign::find($request->campaign_id);
        if ($campaign->company_id != Auth::user()->company_id) {
            return response()->json(["You don't have access to this campaign!"], 401);
        }
        $campaign_contact_cumulative = CampaignContact::where("campaign_id", $request->campaign_id)
            ->selectRaw('count(campaign_contacts.id) as total')
            ->selectRaw("desposition")
            ->groupBy(['desposition'])
            ->get();
        $campaign_contact = CampaignContact::where("campaign_id", $request->campaign_id)
            ->paginate();
        return response()->json(["cumulative_data" => $campaign_contact_cumulative, "detail_data" => $campaign_contact], 200);
    }

    /**
     * It returns a list of questions and answers for a survey campaign
     * 
     * @param Request request The request object.
     */
    public function survey_report(Request $request)
    {
        $request->validate([
            "campaign_id" => "required|exists:campaigns,id"
        ]);
        $campaign = Campaign::find($request->campaign_id);
        if ($campaign->company_id != Auth::user()->company_id) {
            return response()->json(["You don't have access to this campaign!"], 401);
        }

        if ($campaign->campaign_type_id != "AGENTLED" && $campaign->survey_form_id == null) {
            return response()->json(["The campaign you choose isnot survey!"], 422);
        }
        $survey_questions_with_option = FormAttribute::where("form_id", $campaign->survey_form_id)->whereIn("data_type", ["radio", "select", "check"])->get();
        $detail_report = CampaignContact::where("campaign_id", $request->campaign_id)
            ->get();
        return response()->json(["questions" => $survey_questions_with_option, "detail_report" => $detail_report]);
    }

    /**
     * It returns a paginated list of all the contacts that have been called in a campaign, along with
     * the survey questions and their options
     * 
     * @param Request request The request object.
     * 
     * @return The survey questions and the detail report of the campaign.
     */
    public function survey_report_table(Request $request)
    {
        $request->validate([
            "campaign_id" => "required|exists:campaigns,id"
        ]);
        $campaign = Campaign::find($request->campaign_id);
        if ($campaign->company_id != Auth::user()->company_id) {
            return response()->json(["You don't have access to this campaign!"], 401);
        }

        if ($campaign->campaign_type_id != "AGENTLED" || $campaign->survey_form_id == null) {
            return response()->json(["The campaign you choose isnot survey!"], 422);
        }
        $survey_questions_with_option = FormAttribute::where("form_id", $campaign->survey_form_id)->whereIn("data_type", ["radio", "select", "check"])->get();
        return $detail_report = CampaignContact::with("survey_response_data")->where("campaign_id", $request->campaign_id)
            ->paginate();
        return CampaignContactResource::collection($detail_report);
        return response()->json(["questions" => $survey_questions_with_option, "detail_report" => CampaignContactResource::collection($detail_report)]);
    }

    public function survey_question_detail(Request $request)
    {
        $request->validate([
            "campaign_id" => "required|exists:campaigns,id",
            "form_item_id" => "required|exists:form_attributes,id"
        ]);
        $campaign = Campaign::find($request->campaign_id);
        if ($campaign->company_id != Auth::user()->company_id) {
            return response()->json(["You don't have access to this campaign!"], 401);
        }

        if ($campaign->campaign_type_id != "AGENTLED" || $campaign->survey_form_id == null) {
            return response()->json(["The campaign you choose isnot survey!"], 422);
        }
        // $survey_response=SurveyResponseData::where(["survey_id"=>$request->campaign_id,"survey_form_attr_id"=>$request->form_item_id])
        // ->groupBy("value")
        // ->join()
    }
}
