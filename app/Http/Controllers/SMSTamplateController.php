<?php

namespace App\Http\Controllers;

use AfricasTalking\SDK\SMS;
use App\Models\SmsAccount;
use App\Models\SmsTemplate;
use App\Services\PhoneFormatterService;
use Auth;
use Illuminate\Http\Request;

/* It's a controller that handles all the SMS template related operations */
class SMSTamplateController extends Controller
{
    /**
     * It creates a new sms template
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with the message "Template created successfully!"
     */
    public function create_sms_template(Request $request)
    {
        $request->validate([
            "name" => "required|string|max:255",
            "sms_text" => "required|string"
        ]);

        $check_duplicate = SmsTemplate::where(["name" => $request->name, "company_id" => Auth::user()->company_id])->first();
        if ($check_duplicate) {
            return response()->json(["You have a template with the same name!"], 422);
        }
        SmsTemplate::create([
            "company_id" => Auth::user()->company_id,
            "name" => $request->name,
            "sms_text" => $request->sms_text,
        ]);
        return response()->json(["Template created successfully!"], 200);
    }

    /**
     * > This function deletes a sms template from the database
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a message and a status code.
     */
    public function delete_sms_template(Request $request)
    {
        $request->validate([
            "template_id" => "required|exists:sms_templates,id",
        ]);

        $template = SmsTemplate::find($request->template_id);
        if($template->company_id==Auth::user()->company_id){
            return response()->json(["unauthorized"], 401);
        }
        $template->delete();
        return response()->json(["template deleted successfully!"], 200);
    }

    /**
     * It updates an SMS template
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with the message "Template updated successfully!"
     */
    public function update_sms_template(Request $request)
    {
        $request->validate([
            "template_id" => "required|exists:sms_templates,id",
            "name" => "required|string|max:255",
            "sms_text" => "required|string"
        ]);

        $check_duplicate = SmsTemplate::where(["name" => $request->name, "company_id" => Auth::user()->company_id])->first();
        if ($check_duplicate) {
            if ($check_duplicate->id != $request->template_id)
                return response()->json(["You have a template with the same name!"], 422);
        }

        $sms_template = SmsTemplate::find($request->template_id);
        if ($sms_template->company_id != Auth::user()->company_id) {
            return response(["You are not authorized to access this template!"], 200);
        }
        $sms_template->update([
            "name" => $request->name,
            "sms_text" => $request->sms_text
        ]);
        return response()->json(["Template updated successfully!"], 200);
    }

    /**
     * It returns all the sms templates for the company that the user is logged in to
     * 
     * @return A collection of SmsTemplate objects.
     */
    public function get_sms_template()
    {
        return SmsTemplate::where("company_id", Auth::user()->company_id)->get();
    }

    /**
     * It returns the details of a specific SMS template
     * 
     * @param Request request The request object.
     * 
     * @return The sms template detail
     */
    public function get_sms_template_detail(Request $request)
    {
        $request->validate([
            "template_id" => "required|exists:sms_templates,id"
        ]);
        return SmsTemplate::find($request->template_id);
    }

    /**
     * It takes a phone number and a text message as input, formats the phone number, sends the text
     * message and returns a response
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a message and a status code.
     */
    public function send_sms_template(Request $request)
    {
        $request->validate([
            "sms_text" => "required|string",
            "phone_number" => "required"
        ]);

        $sms_account = SmsAccount::where("company_id", Auth::user()->company_id)->first();
        if (!$sms_account) {
            return response()->json(["You don't have sms account configured, Please contact your system admin!"], 422);
        }
        $phone = PhoneFormatterService::format_phone($request->phone_number);
        $sms_sent = sendsms($sms_account->id, $request->phone_number, $request->sms_text);
        if (!$sms_sent) {
            return response()->json(["Unable to send the text please try again!"], 422);
        }
        return response()->json(["SMS sent successfully!"], 200);
    }
}