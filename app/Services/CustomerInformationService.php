<?php

namespace App\Services;

use App\Http\Resources\AccountPopupResource;
use App\Http\Resources\CallIntegratoinResource;
use App\Http\Resources\CampaignContactResource;
use App\Http\Resources\ContactPopupResource;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\EscalationFormResource;
use App\Http\Resources\SocialAccountResource;
use App\Http\Resources\TicketResource;
use App\Models\Account;
use App\Models\AccountData;
use App\Models\AccountFormAttr;
use App\Models\AccountType;
use App\Models\AccountTypeGroup;
use App\Models\CallLog;
use App\Models\CallPopupIntegrationSetting;
use App\Models\CampaignContact;
use App\Models\CDRTable;
use App\Models\CentralizedForm;
use App\Models\Contact;
use App\Models\ContactData;
use App\Models\ContactFormAttr;
use App\Models\ContactSocialAcct;
use App\Models\Conversation;
use App\Models\QueueLog;
use App\Models\SipList;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserGroup;
use Auth;

class CustomerInformationService
{
    /**
     * It gets the call information of a customer by searching for the phone number in the contact
     * forms of the account types that the user has access to
     * 
     * @param phone_number The phone number of the caller
     * @param company_id The company id of the company that the user is logged in to.
     * @param user_id The user id of the user who is logged in
     * 
     * @return an array of data.
     */
    public function get_call_information($phone_number)
    {
        $customer_data = array();
        $groups = UserGroup::where("user_id", Auth::user()->id)->pluck('group_id');
        $potential_account_types = AccountTypeGroup::whereIn("group_id", $groups)->pluck('accounttype_id');
        // return $potential_account_types;
        /* Initializing the variables. */
        $customer_data["formated_contact"] = null;
        $customer_data["contact"] = null;
        $customer_data["formated_account"] = null;
        $customer_data["account"] = null;
        $customer_data["social_accounts"] = null;
        $customer_data["social_interaction"] = null;
        $customer_data["recent_campaign"] = null;
        $customer_data['tickets'] = null;
        $customer_data["call_log"] = null;
        $customer_data["call_type"] = null;

        /* Checking if the user has access to any account types. If the user has access to any account
        types, then it will loop through each account type and check if the user has a contact with
        the phone number that is being passed in. If the user has a contact with the phone number,
        then it will return the contact and account information. */
        if (count($potential_account_types) > 0) {
            foreach ($potential_account_types as $potential_account_type) {
                $account_type = AccountType::find($potential_account_type);
                $contact_forms = ContactFormAttr::where(["contact_form_id" => $account_type->contact_form_id, "data_type" => "phone"])->get();
                $contact_data = $this->_get_phone_from_form($contact_forms, $phone_number);
                $account_forms = AccountFormAttr::where(["account_form_id" => $account_type->account_form_id, "data_type" => "phone"])->get();
                $account_data = $this->_get_phone_acct_form($account_forms, $phone_number);
                // return $account_data;
                if ($account_data) {
                    $account = Account::find($account_data->account_id);
                    ///////Account Data
                    $customer_data["formated_account"] = new AccountPopupResource($account);
                    $customer_data["account"]["account_id"] = $account->id;
                }
                if ($contact_data) {
                    $contact = Contact::find($contact_data->contact_id);
                    $account_return = Account::find($contact->account_id);

                    ///////Contact Data
                    $customer_data["formated_contact"] = new ContactPopupResource($contact);
                    $customer_data["contact"]["contact_id"] = $contact->id;
                    ///////Account Data
                    $customer_data["formated_account"] = new AccountPopupResource($account_return);
                    $customer_data["account"]["account_id"] = $account_return->id;
                    /////social accounts
                    $customer_data["social_accounts"] = SocialAccountResource::collection(ContactSocialAcct::where("contact_id", $contact->id)->get());
                    $social_accounts = ContactSocialAcct::where("contact_id", $contact->id)->pluck("social_account");
                    ///////socail interaction data
                    $customer_data["social_interaction"] = ConversationResource::collection(Conversation::where(["company_id" => Auth::user()->company_id])->whereIn("customer_id", $social_accounts)->latest()->take(5)->get());
                    ///////campaign data
                    $customer_data["recent_campaign"] = CampaignContactResource::collection(CampaignContact::where("contact_id", $contact->id)->latest()->take(5)->get());
                }
            }
        }

        /* Getting the call history of the customer. */
        $call_history = CallLog::where(["caller_id" => $phone_number, "company_id" => Auth::user()->company_id])->orderBy('created_at', 'DESC')->latest()->take(5)->get();
        ////////ticket data
        $customer_data['tickets'] = TicketResource::collection(Ticket::where(["contact" => $phone_number, "company_id" => Auth::user()->company_id])->latest()->take(5)->get());
        ////////call log data
        $customer_data["call_log"] = $call_history;
        ////////call type
        if (count($call_history) > 0) {
            $customer_data["call_type"] = $call_history[0]->call_type;
            $queue_log = QueueLog::where("call_id", $call_history[0]->call_id)->first();
            if ($queue_log) {
                $customer_data["call_type"] = $call_history[0]->call_type;
                $call_integration = CallPopupIntegrationSetting::where([
                    "scope" => $queue_log->queue_id
                ])->first();
                if ($call_integration) {
                    $customer_data["integration"] = new CallIntegratoinResource($call_integration);
                } else {
                    $call_integration = CallPopupIntegrationSetting::where([
                        "company_id" => $queue_log->company_id
                    ])->first();
                    if ($call_integration) {
                        $customer_data["integration"] = new CallIntegratoinResource($call_integration);
                    }
                }
                if (!$call_integration) {
                    $return_cdr = CDRTable::where("call_id", $call_history[0]->call_id);
                    $call_integration = CallPopupIntegrationSetting::where([
                        "scope" => $queue_log->queue_id
                    ])->first();
                    if ($call_integration) {
                        $customer_data["integration"] = new CallIntegratoinResource($call_integration);
                    } else {
                        $call_integration = CallPopupIntegrationSetting::where([
                            "company_id" => $queue_log->company_id
                        ])->first();
                        if ($call_integration) {
                            $customer_data["integration"] = new CallIntegratoinResource($call_integration);
                        }
                    }
                }
                // if ($call_integration) {
                //     return $return_custome_crm;
                // }
            }

            if (preg_match('/^[0-9]{4}+$/', $phone_number)) {
                $customer_data["sip"] = $phone_number;
                $sip = SipList::where("sip_id", $phone_number)->first();
                $user = User::where("sip_id", $sip->id)->first();
                $customer_data["agent_name"] = $user->name;
                $customer_data["call_type"] = "SIPCALL";
            } else if (preg_match('/^[0-9]{5}+$/', $phone_number)) {
                $customer_data["sip"] = $phone_number;
                $sip = SipList::where("sip_id", $phone_number)->first();
                $user = User::where("sip_id", $sip->id)->first();
                $customer_data["agent_name"] = $user->name;
                $customer_data["call_type"] = "SIPCALL";
            }
            if ($call_history[0]->call_type == "INBOUND" || $call_history[0]->call_type == "AGENT_LED") {
                $queue_log = QueueLog::where("call_id", $call_history[0]->call_id)->with("queue")->first();
                // $customer_data["queue"] = $queue_log->queue->name;
            }

            /* Checking if the call type is an agent campaign and if the campaign contact id is not null. If
            it is, then it will get the campaign contact and the campaign. It will then add the campaign
            name and description to the customer data. If the survey form id is not null, then it will
            get the form and add it to the customer data. */
            if ($call_history[0]->call_type == "AGENT_CAMPAIGN" && $call_history[0]->campaign_contact_id != null) {
                $campaign_contact = CampaignContact::with("campaign")->find($call_history[0]->campaign_contact_id);
                $campaign = $campaign_contact->campaign;
                ////////campaign data
                $customer_data['campaign']["name"] = $campaign->name;
                $customer_data['campaign']["description"] = $campaign->description;
                $customer_data['campaign']["campaign_contact_id"] = $call_history[0]->campaign_contact_id;
                // return $campaign;
                if ($campaign->survey_form_id != null) {
                    $form = new EscalationFormResource(CentralizedForm::find($campaign->survey_form_id));
                    $customer_data['campaign']["form"] = $form;
                }
            }
        } else {
            $customer_data["call_type"] = "SIPCALL";
        }
        return $customer_data;
    }

    /**
     * It takes an array of contact forms and a phone number and returns the first contact form that
     * has a contact with that phone number
     * 
     * @param contact_forms This is an array of contact forms that are associated with the user.
     * @param phone_number The phone number that the user is trying to register with.
     * 
     * @return A contact object.
     */
    private function _get_phone_from_form($contact_forms, $phone_number)
    {
        $contact = "";
        foreach ($contact_forms as $contact_form) {
            $contact = ContactData::where(["contact_form_attr_id" => $contact_form->id, "value" => $phone_number])->first();
            if ($contact) {
                return $contact;
            }
        }
    }


    public function _get_phone_acct_form($account_form, $phone_number)
    {
        $contact = "";
        foreach ($account_form as $contact_form) {
            $contact = AccountData::where(["account_form_attr_id" => $contact_form->id, "value" => $phone_number])->first();
            if ($contact) {
                return $contact;
            }
        }
    }

    /**
     * It takes an array of objects and returns a string of the form "(group_id=1 OR group_id=2 OR
     * group_id=3)"
     * 
     * @param user_groups This is an array of objects that contain the group_id of the user.
     * 
     * @return The query string is being returned.
     */
    private function _change_group_to_query($user_groups)
    {
        $query_string = "";
        foreach ($user_groups as $key => $user_group) {
            if ($query_string == "") {
                $query_string .= " (group_id=" . $user_group->group_id;
            } else {
                $query_string .= " OR group_id=" . $user_group->group_id;
            }
        }
        if ($query_string != "") {
            $query_string .= ") ";
        }
        return $query_string;
    }
}