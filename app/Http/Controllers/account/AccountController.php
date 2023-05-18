<?php

namespace App\Http\Controllers\account;

use App\Helpers\AccessChecker;
use App\Helpers\AccountNumberGenratorHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\AccountFormAttrResource;
use App\Http\Resources\AccountLogResource;
use App\Http\Resources\AccountResource;
use App\Http\Resources\AccountResourceModified;
use App\Http\Resources\CampaignContactResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\ContactResourceModified;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\QueueLogResource;
use App\Http\Resources\TicketsResource;
use App\Models\Account;
use App\Models\AccountData;
use App\Models\AccountForm;
use App\Models\AccountFormAttr;
use App\Models\AccountLog;
use App\Models\AccountNumber;
use App\Models\AccountStage;
use App\Models\AccountStageData;
use App\Models\AccountType;
use App\Models\Campaign;
use App\Models\CampaignContact;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\QueueLog;
use App\Models\SocialChatAccount;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\ContactData;
use App\Models\ContactForm;
use App\Models\ContactFormAttr;

class AccountController extends Controller
{
    public function create_account_number(Request $request)
    {
        $validated_data = $request->validate([
            "name" => "required|string"
        ]);
        $company_id = $request->user()->company_id;
        $account_number = AccountNumber::where([
            "company_id" => $company_id,
            "name" => $request->name
        ])->first();
        if ($account_number) {
            throw ValidationException::withMessages(["Duplicate account numbers"]);
        }

        AccountNumber::create([
            "name" => $request->name,
            "prefix" => $request->prefix,
            "has_number" => ($request->has_number == "true") ? true : false,
            "has_character" => ($request->has_character == "true") ? true : false,
            "separator" => $request->separator,
            "company_id" => $company_id
        ]);
        return response()->json([
            'message' => 'Successfully added'
        ], 200);
    }

    public function update_account_number(Request $request)
    {
        $validated_data = $request->validate([
            "account_number_id" => "required|exists:account_numbers,id",
            "name" => "required|string"
        ]);
        $company_id = $request->user()->company_id;
        $account_number = AccountNumber::where([
            "company_id" => $company_id,
            "name" => $request->name
        ])->first();
        if ($account_number) {
            if ($account_number->company_id != $company_id) {
                throw ValidationException::withMessages(["Unauthorized"]);
            }
        }
        $account_number_update = AccountNumber::find($request->account_number_id);
        $account_number_update->name = $request->name;
        $account_number_update->prefix = $request->prefix;
        $account_number_update->has_number = ($request->has_number == "true") ? true : false;
        $account_number_update->has_character = ($request->has_character == "true") ? true : false;
        $account_number_update->separator = $request->separator;
        $account_number_update->save();

        return response()->json([
            'message' => 'Successfully updated'
        ], 200);
    }

    public function get_account_numbers(Request $request)
    {
        $company_id = $request->user()->company_id;
        return AccountNumber::with("account_types")
            ->where("company_id", $company_id)->get();
    }

    public function assign_account_number_to_account_type(Request $request)
    {
        $validated_data = $request->validate([
            "account_number_id" => "required|exists:account_numbers,id",
            "account_types" => "required|array"
        ]);
        $company_id = $request->user()->company_id;

        foreach ($request->account_types as $key => $account_type) {
            $account_type = AccountType::find($account_type);
            if (!$account_type) {
                throw ValidationException::withMessages(["Invalid account type"]);
            } else {
                if ($account_type->company_id != $company_id) {
                    throw ValidationException::withMessages(["Unauthorized"]);
                }
            }
        }

        foreach ($request->account_types as $key => $account_type) {
            $account_type_update = AccountType::find($account_type);
            $account_type_update->account_number_id = $request->account_number_id;
            $account_type_update->save();
        }

        return response()->json([
            'message' => 'Successfully updated'
        ], 200);
    }

    public function create_account(Request $request)
    {
        $account_type_id = $request->account_type_id;
        $account_form_id = $request->account_form_id;
        $company_id = $request->user()->company_id;

        $approve_access = AccessChecker::has_account_aprove_access($request->user()->id);
        $create_access = AccessChecker::has_account_create_access($request->user()->id);

        $access_group_check = AccessChecker::check_has_group_access_account_type($request->user()->id, $account_type_id);

        if (!$access_group_check) {
            throw ValidationException::withMessages(["Unathorized group check"]);
        }

        if (!$approve_access && !$create_access) {
            throw ValidationException::withMessages(["Unathorized"]);
        }

        $formdata = AccountFormAttr::with("account_form_attr_options")->where("account_form_id", $account_form_id)->get();

        $first_name = "";
        $maiden_name = "";
        $last_name = "";
        // return $request;
        foreach ($formdata as $key => $form_item) {
            if ($form_item->is_required) {
                if ($request->user_response[$form_item->id] == null) {
                    throw ValidationException::withMessages(["value form item " . $form_item->name . " cannot be empty"]);
                }
            }
            if ($request->user_response[$form_item->id] != null && $form_item->data_type == "number") {
                if (!is_numeric($request->user_response[$form_item->id])) {
                    throw ValidationException::withMessages(["the value for form item " . $form_item->name . " should be number"]);
                }
            } else if ($request->user_response[$form_item->id] != null && $form_item->data_type == "email") {
                if (!filter_var($request->user_response[$form_item->id], FILTER_VALIDATE_EMAIL)) {
                    throw ValidationException::withMessages(["the value for form item " . $form_item->name . " should be email"]);
                }
            }

            if ($request->user_response[$form_item->id] != null && $form_item->data_type == "firstname") {
                $first_name = $request->user_response[$form_item->id];
            }
            if ($request->user_response[$form_item->id] != null && $form_item->data_type == "maidenname") {
                $maiden_name = $request->user_response[$form_item->id];
            }
            if ($request->user_response[$form_item->id] != null && $form_item->data_type == "lastname") {
                $last_name = $request->user_response[$form_item->id];
            }
        }

        $account_type = AccountType::find($account_type_id);

        if ($account_type->account_number_id == null) {
            throw ValidationException::withMessages(["Please setup account number for this account type"]);
        }

        $is_account_number_generated = false;
        $account_number = "";
        while (!$is_account_number_generated) {
            $account_number = AccountNumberGenratorHelper::generate_account_number($account_type_id);
            $check_duplicate = Account::where(["account_number" => $account_number, "company_id" => $company_id])->first();
            if (!$check_duplicate) {
                $is_account_number_generated = true;
            }
        }
        if ($approve_access) {
            $account = Account::create([
                "account_number" => $account_number,
                "first_name" => $first_name,
                "middle_name" => $maiden_name,
                "last_name" => $last_name,
                "account_form_id" => $account_form_id,
                "account_type_id" => $account_type_id,
                "created_by" => $request->user()->id,
                "company_id" => $company_id
            ]);
            foreach ($formdata as $key => $form_item) {
                $value = $request->user_response[$form_item->id];
                if ($form_item->data_type == "password") {
                    $value = Hash::make($request->user_response[$form_item->id]);
                }
                $form_item_id = $form_item->id;
                if ($form_item->data_type == "checkbox") {
                    foreach ($value as $val) {
                        AccountData::create([
                            "account_id" => $account->id,
                            "account_form_attr_id" => $form_item_id,
                            "value" => $val
                        ]);
                    }
                } else {
                    AccountData::create([
                        "account_id" => $account->id,
                        "account_form_attr_id" => $form_item_id,
                        "value" => $value
                    ]);
                }
            }
            $this->log_account_change($account->id, "CREATE", $request->user()->id, null, now());
            return response()->json([
                'message' => 'Successfully created'
            ], 200);
        } else if ($create_access) {
            $account = AccountStage::create([
                "first_name" => $first_name,
                "middle_name" => $maiden_name,
                "last_name" => $last_name,
                "account_form_id" => $account_form_id,
                "account_type_id" => $account_type_id,
                "created_by" => $request->user()->id,
                "company_id" => $company_id,
                "approval_type" => "CREATE"
            ]);
            foreach ($formdata as $key => $form_item) {
                $value = $request->user_response[$form_item->id];
                if ($form_item->data_type == "password") {
                    $value = Hash::make($request->user_response[$form_item->id]);
                }
                $form_item_id = $form_item->id;
                if ($form_item->data_type == "checkbox") {
                    foreach ($value as $val) {
                        AccountStageData::create([
                            "account_id" => $account->id,
                            "account_form_attr_id" => $form_item_id,
                            "value" => $val
                        ]);
                    }
                } else {
                    AccountStageData::create([
                        "account_stage_id" => $account->id,
                        "account_form_attr_id" => $form_item_id,
                        "value" => $value
                    ]);
                }
            }
            return response()->json([
                'message' => 'Successfully created, Pending approval'
            ], 200);
        } else {
            throw ValidationException::withMessages(["Unathorized"]);
        }
    }

    /**
     * It returns the form attributes of an account type
     * 
     * @param Request request The request object.
     * 
     * @return The account type form is being returned.
     */
    public function account_type_form(Request $request)
    {
        $request->validate([
            'account_type_id' => 'required|exists:account_types,id'
        ]);

        $accountType = AccountType::find($request->account_type_id);

        if ($accountType->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Account type does not belong to your company!'], 401);
        }

        $formdata = AccountFormAttr::with("account_form_attr_options")
            ->where("account_form_id", $accountType->account_form_id)
            ->where(function ($query) {
                $query->where('data_type', '=', 'checkbox')
                    ->orwhere('data_type', '=', 'radio')
                    ->orwhere('data_type', '=', 'Radio')
                    ->orwhere('data_type', '=', 'select');
            })
            ->get();

        return response()->json(AccountFormAttrResource::collection($formdata), 200);
    }

    /**
     * It takes a campaign id and an array of form attributes and their selected options, and then it
     * migrates all the contacts that match the selected options to the campaign
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a success message and the number of campaign contacts uploaded.
     */
    public function migrate_campaign_contacts_by_filter(Request $request)
    {
        $request->validate([
            'campaign_id' => 'required|integer|exists:campaigns,id',
            'form_attributes' => 'required|array'
        ]);

        $campaign = Campaign::find($request->campaign_id);

        if ($campaign->company_id != Auth::user()->company_id) {
            return response()->json(['message' => 'Campaign does not belong to your company!'], 401);
        }

        $form_attribute_ids = array();
        $selected_options = array();
        foreach ($request->form_attributes as $key => $form_attribute) {
            $form_attribute_request = new Request($form_attribute);
            $form_attribute_request->validate([
                "id" => "required|integer|exists:account_form_attrs,id",
                "selected_options" => "required|array"
            ]);

            $form_attribute_ids[] = $form_attribute['id'];

            $selected_options[] = $form_attribute['selected_options'];
        }

        $options = call_user_func_array('array_merge', $selected_options);

        $accountsData = AccountData::whereIn('account_form_attr_id', $form_attribute_ids)
            ->whereIn('value', $options)
            ->get('account_id');

        $contacts = Contact::whereIn('account_id', $accountsData)->get();

        $campaign_contacts_count = 0;
        foreach ($contacts as $key => $contact) {
            $campaignContact = [
                'contact_id' => $contact->id,
                'full_name' => $contact->first_name . ' ' . ($contact->maiden_name ? $contact->maiden_name : '') . ' ' . ($contact->last_name ? $contact->last_name : '')
            ];

            $contactForm = ContactForm::find($contact->contact_form_id);
            if ($contactForm) {
                $phone_attr = ContactFormAttr::where([
                    'contact_form_id' => $contactForm->id,
                    'data_type' => 'phone'
                ])->first();

                if ($phone_attr) {
                    $contact_data = ContactData::where([
                        'contact_form_attr_id' => $phone_attr->id,
                        'contact_id' => $contact->id
                    ])->first();

                    if ($contact_data) {
                        $campaignContact['phone'] = $contact_data->value;
                    }
                }
            }

            $newCampaignContact = CampaignContact::updateOrCreate(
                [
                    'campaign_id' => $request->campaign_id,
                    'phone_number' => $campaignContact['phone']
                ],
                [
                    'account_id' => $campaignContact['contact_id'],
                    'name' => str_replace('  ', ' ', $campaignContact['full_name']),
                    'trail' => 0,
                    'status' => 'NOTCONTACTED'
                ]
            );
            $campaign_contacts_count++;
        }

        return response()->json([
            'success' => true,
            'message' => "$campaign_contacts_count campaign contacts uploaded!"
        ], 200);
    }

    /**
     * It gets the account detail
     * 
     * @param Request request The request object.
     * 
     * @return The account, contacts, account logs, tickets, call logs, and conversations are being
     * returned.
     */
    public function get_account_detail(Request $request)
    {
        $validate_date = $request->validate([
            "account_id" => "required|exists:accounts,id"
        ]);
        $account = Account::find($request->account_id);
        if ($account->company_id != Auth::user()->company_id) {
            return response()->json([
                'error' => true,
                'message' => 'Account does not belong to your company!'
            ], 401);
        }


        $account_form_attrs = AccountFormAttr::where(["account_form_id" => $account->account_form_id, "data_type" => "phone"])->get();

        $phone_numbers = array();
        foreach ($account_form_attrs as $key => $account_form_attr) {
            $phone_number = AccountData::where(["account_form_attr_id" => $account_form_attr->id, "account_id" => $account->id])->first();
            if ($phone_number) {
                $phone_numbers[$key] = $phone_number->value;
            }
        }
        $queuelogs = QueueLog::where("company_id", Auth::user()->company_id)
            ->whereIn("caller_id", $phone_numbers)
            ->latest()
            ->limit(5)
            ->get();

        $social_accts = $account->social_chat_accounts;
        $conversations = array();
        foreach ($social_accts as $key => $social_acct) {
            $conversations = Conversation::where(["channel_id" => $social_acct->channel_id, "customer_id" => $social_acct->social_account])->latest()->limit(5)->get();
        }

        return response()->json([
            'account' => new AccountResource($account),
            'contacts' => ContactResource::collection($account->contacts),
            'account_logs' => AccountLogResource::collection($account->account_logs),
            'tickets' => TicketsResource::collection($account->tickets),
            'call_logs' => QueueLogResource::collection($queuelogs),
            'conversations' => (!empty($conversations)) ? ConversationResource::collection($conversations) : "Conversation not found"
        ], 200);
    }

    /**
     * It gets the account detail, then gets the phone numbers of the account, then gets the call logs
     * of the account, then gets the social accounts of the account, then gets the conversations of the
     * account
     * 
     * @param Request request The request object.
     * 
     * @return <code>{
     *     "account": {
     *         "id": 1,
     *         "account_form_id": 1,
     *         "company_id": 1,
     *         "created_at": "2019-07-29T07:00:00.000000Z",
     *         "updated_at": "2019-07-29T07:00:
     */
    public function get_account_detail_modified(Request $request)
    {
        $validate_date = $request->validate([
            "account_id" => "required|exists:accounts,id"
        ]);
        $account = Account::find($request->account_id);
        if ($account->company_id != Auth::user()->company_id) {
            return response()->json([
                'error' => true,
                'message' => 'Account does not belong to your company!'
            ], 401);
        }


        $account_form_attrs = AccountFormAttr::where(["account_form_id" => $account->account_form_id, "data_type" => "phone"])->get();

        $phone_numbers = array();
        foreach ($account_form_attrs as $key => $account_form_attr) {
            $phone_number = AccountData::where(["account_form_attr_id" => $account_form_attr->id, "account_id" => $account->id])->first();
            if ($phone_number) {
                $phone_numbers[$key] = $phone_number->value;
            }
        }
        $queuelogs = QueueLog::where("company_id", Auth::user()->company_id)
            ->whereIn("caller_id", $phone_numbers)
            ->latest()
            ->limit(5)
            ->get();

        $social_accts = $account->social_chat_accounts;
        $conversations = array();
        foreach ($social_accts as $key => $social_acct) {
            $conversations = Conversation::where(["channel_id" => $social_acct->channel_id, "customer_id" => $social_acct->social_account])->latest()->limit(5)->get();
        }

        return response()->json([
            'account' => new AccountResourceModified($account),
            'contacts' => ContactResourceModified::collection($account->contacts),
            'account_logs' => AccountLogResource::collection($account->account_logs),
            'tickets' => TicketsResource::collection($account->tickets),
            'call_logs' => QueueLogResource::collection($queuelogs),
            'conversations' => (!empty($conversations)) ? ConversationResource::collection($conversations) : "Conversation not found"
        ], 200);
    }

    /**
     * It gets all the accounts that the user has access to
     * 
     * @param Request request The request object
     * 
     * @return A list of accounts that the user has access to.
     */
    public function get_accounts(Request $request)
    {
        $access_checker = new AccessChecker();
        $account_types = $access_checker->get_account_types_update($request->user()->id);
        // return $account_types;
        if (!empty($account_types)) {
            $query = Account::query();
            $query->whereIn("account_type_id", $account_types);

            return $query->paginate();
        }
    }

    /**
     * It returns all the accounts that are pending approval for the account types that the user has access
     * to
     * 
     * @param Request request The request object
     * 
     * @return A list of accounts that are pending approval.
     */
    public function get_account_pending(Request $request)
    {
        $access_checker = new AccessChecker();
        $account_types = $access_checker->get_account_types($request->user()->id);

        if (!empty($account_types)) {
            $query = AccountStage::query();
            $query->where("approved_by", null);
            foreach ($account_types as $key => $account_type) {
                if ($key == 0) {
                    $query = $query->Where("account_type_id", $account_type->id);
                } else {
                    $query = $query->orWhere("account_type_id", $account_type->id);
                }
            }
            return $query->paginate();
        }
    }

    /**
     * It gets the account detail of the account that is pending for approval
     * 
     * @param Request request The request object.
     * 
     * @return The account_original is the original account data that was submitted by the user. The
     * account_updated is the updated account data that was submitted by the user.
     */
    public function get_account_detail_pending(Request $request)
    {
        $validate_date = $request->validate([
            "account_id" => "required|exists:account_stages,id"
        ]);
        $account_original = null;

        $account_stage = AccountStage::find($request->account_id);
        if ($account_stage == "UPDATE") {
            $account_original = AccountData::with("account_form_attr")->where(["account_id" => $request->account_id])->get();
        }
        $account = AccountStageData::with("account_form_attrs")->where(["account_stage_id" => $request->account_id])->get();
        return response()->json([
            'account_original' => $account_original,
            'account_updated' => $account
        ], 200);
    }

    /**
     * This function updates an account
     * 
     * @param Request request The request object.
     */
    public function update_account(Request $request)
    {
        $validated_data = $request->validate([
            "account_id" => "required|exists:accounts,id"
        ]);
        $account_type_id = $request->account_type_id;
        $account_form_id = $request->account_form_id;
        $account_id = $request->account_id;
        $company_id = $request->user()->company_id;

        $approve_access = AccessChecker::has_account_aprove_access($request->user()->id);
        $create_access = AccessChecker::has_account_create_access($request->user()->id);

        $access_group_check = AccessChecker::check_has_group_access_account_type($request->user()->id, $account_type_id);

        if (!$approve_access && !$create_access) {
            throw ValidationException::withMessages(["Unathorized"]);
        }

        $formdata = AccountFormAttr::with("account_form_attr_options")->where("account_form_id", $account_form_id)->get();

        $first_name = "";
        $maiden_name = "";
        $last_name = "";

        foreach ($formdata as $key => $form_item) {
            if ($form_item->is_required) {
                if ($request->user_response[$form_item->id] == null) {
                    throw ValidationException::withMessages(["value form item " . $form_item->name . " cannot be empty"]);
                }
            }
            if ($request->user_response[$form_item->id] != null && $form_item->data_type == "number") {
                if (!is_numeric($request->user_response[$form_item->id])) {
                    throw ValidationException::withMessages(["the value for form item " . $form_item->name . " should be number"]);
                }
            } else if ($request->user_response[$form_item->id] != null && $form_item->data_type == "email") {
                if (!filter_var($request->user_response[$form_item->id], FILTER_VALIDATE_EMAIL)) {
                    throw ValidationException::withMessages(["the value for form item " . $form_item->name . " should be email"]);
                }
            }

            if ($request->user_response[$form_item->id] != null && $form_item->data_type == "firstname") {
                $first_name = $request->user_response[$form_item->id];
            }
            if ($request->user_response[$form_item->id] != null && $form_item->data_type == "maidenname") {
                $maiden_name = $request->user_response[$form_item->id];
            }
            if ($request->user_response[$form_item->id] != null && $form_item->data_type == "lastname") {
                $last_name = $request->user_response[$form_item->id];
            }
        }

        $account_type = AccountType::find($account_type_id);
        if ($account_type->account_number_id == null) {
            throw ValidationException::withMessages(["Please setup account number for this account type"]);
        }


        if ($approve_access) {
            $account_update = Account::find($account_id);
            $account_update->first_name = $first_name;
            $account_update->middle_name = $maiden_name;
            $account_update->last_name = $last_name;
            $account_update->account_form_id = $account_form_id;
            $account_update->account_type_id = $account_type_id;
            $account_update->updated_by = $request->user()->id;
            $account_update->company_id = $company_id;
            $account_update->save();

            foreach ($formdata as $key => $form_item) {
                $value = $request->user_response[$form_item->id];
                if ($form_item->data_type == "password") {
                    $value = Hash::make($request->user_response[$form_item->id]);
                }
                $form_item_id = $form_item->id;


                if ($form_item->data_type == "checkbox") {
                    AccountData::where([
                        "account_id" => $account_id,
                        "account_form_attr_id" => $form_item->id
                    ])->delete();
                    foreach ($value as $val) {
                        AccountData::create([
                            "account_id" => $account_id,
                            "account_form_attr_id" => $form_item_id,
                            "value" => $val
                        ]);
                    }
                } else {
                    $update_account_data = AccountData::where([
                        "account_id" => $account_id,
                        "account_form_attr_id" => $form_item->id
                    ])->first();
                    $account_data_update = AccountData::find($update_account_data->id);
                    $account_data_update->value = $value;
                    $account_data_update->save();
                }
            }

            $this->log_account_change($account_id, "update", $request->user()->id, null, now());
            return response()->json([
                'message' => 'Successfully Updated'
            ], 200);
        } else if ($create_access) {
            $account = AccountStage::create([
                "account_id" => $account_id,
                "first_name" => $first_name,
                "middle_name" => $maiden_name,
                "last_name" => $last_name,
                "account_form_id" => $account_form_id,
                "account_type_id" => $account_type_id,
                "created_by" => $request->user()->id,
                "company_id" => $company_id,
                "approval_type" => "UPDATE"
            ]);
            foreach ($formdata as $key => $form_item) {
                $value = $request->user_response[$form_item->id];
                if ($form_item->data_type == "password") {
                    $value = Hash::make($request->user_response[$form_item->id]);
                }
                $form_item_id = $form_item->id;

                AccountStageData::create([
                    "account_stage_id" => $account->id,
                    "account_form_attr_id" => $form_item_id,
                    "value" => $value
                ]);
            }
            return response()->json([
                'message' => 'Successfully updated, Pending approval'
            ], 200);
        }
    }

    /**
     * > This function logs the changes made to an account
     * 
     * @param account_id The id of the account that was changed
     * @param change_type 
     * @param done_by The user who made the change
     * @param approved_by The user who approved the change.
     * @param created_date The date the account was created
     */
    public function log_account_change($account_id, $change_type, $done_by, $approved_by, $created_date)
    {
        AccountLog::create([
            "account_id" => $account_id,
            "action" => $change_type,
            "changed_by" => $done_by,
            "approved_by" => $approved_by,
            "start_date" => $created_date
        ]);
    }

    /**
     * This function is used to approve or decline the account request
     * 
     * @param Request request The request object.
     */
    public function account_approve_request(Request $request)
    {
        $account_stage_id = $request->account_stage_id;
        $approve_access = AccessChecker::has_account_aprove_access($request->user()->id);
        $company_id = $request->user()->company_id;
        if (!$approve_access) {
            throw ValidationException::withMessages(["Unathorized"]);
        }

        $account_stage = AccountStage::find($account_stage_id);

        $access_group_check = AccessChecker::check_has_group_access_account_type($request->user()->id, $account_stage->account_type_id);

        if (!$access_group_check) {
            throw ValidationException::withMessages(["Unathorized"]);
        }

        if ($account_stage->approved_by != null) {
            throw ValidationException::withMessages(["Already approved"]);
        }


        if ($request->action == "APPROVE") {
            if ($account_stage->approval_type == "CREATE") {
                $is_account_number_generated = false;
                $account_number = "";
                while (!$is_account_number_generated) {
                    $account_number = AccountNumberGenratorHelper::generate_account_number($account_stage->account_type_id);
                    $check_duplicate = Account::where(["account_number" => $account_number, "company_id" => $company_id])->first();
                    if (!$check_duplicate) {
                        $is_account_number_generated = true;
                    }
                }

                $account = Account::create([
                    "account_number" => $account_number,
                    "first_name" => $account_stage->first_name,
                    "middle_name" => $account_stage->maiden_name,
                    "last_name" => $account_stage->last_name,
                    "account_form_id" => $account_stage->account_form_id,
                    "account_type_id" => $account_stage->account_type_id,
                    "created_by" => $account_stage->created_by,
                    "approved_by" => $request->user()->id,
                    "company_id" => $company_id
                ]);
                $account_stage_data = AccountStageData::where("account_stage_id", $account_stage_id)->get();
                // return $account_stage_data;
                foreach ($account_stage_data as $key => $form_item) {
                    AccountData::create([
                        "account_id" => $account->id,
                        "account_form_attr_id" => $form_item->account_form_attr_id,
                        "value" => $form_item->value
                    ]);
                }
                $this->log_account_change($account->id, "CREATE", $account_stage->created_by,  $request->user()->id, $account_stage->created_at);
                $account_stage->approved_by = $request->user()->id;
                $account_stage->save();
                return response()->json([
                    'message' => 'Successfully approved'
                ], 200);
            } else {
                $account_id = $account_stage->account_id;
                $account_update = Account::find($account_id);
                $account_update->first_name = $account_stage->first_name;
                $account_update->middle_name = $account_stage->maiden_name;
                $account_update->last_name = $account_stage->last_name;
                $account_update->account_form_id = $account_stage->account_form_id;
                $account_update->account_type_id = $account_stage->account_type_id;
                $account_update->updated_by = $account_stage->created_by;
                $account_update->approved_by = $request->user()->id;
                $account_update->save();
                $account_stage_data = AccountStageData::where("account_stage_id", $account_stage_id)->get();
                $formdata = AccountData::where("account_id", $account_id)->delete();
                foreach ($account_stage_data as $key => $form_item) {
                    AccountData::create([
                        "account_id" => $account_id,
                        "account_form_attr_id" => $form_item->account_form_attr_id,
                        "value" => $form_item->value
                    ]);
                }

                $this->log_account_change($account_id, "UPDATE", $account_stage->created_by,  $request->user()->id, $account_stage->created_at);
                $account_stage->approved_by = $request->user()->id;
                $account_stage->save();
                return response()->json([
                    'message' => 'Successfully approved'
                ], 200);
            }
        } else if ($request->action == "DECLINE") {
            $account_stage_decline = AccountStage::find($account_stage_id);
            $account_stage_decline->approval_type = "DECLINED";
            $account_stage_decline->approved_by = $request->user()->id;
            $account_stage_decline->save();

            $account_id = $account_stage->account_id;
            if ($account_id != null)
                $this->log_account_change($account_id, "DECLINED", $account_stage->created_by,  $request->user()->id, $account_stage->created_at);
            return response()->json([
                'message' => 'Successfully declined'
            ], 200);
        }
    }

    /**
     * It associates a social chat account to an account
     * 
     * @param Request request 
     * 
     * @return A JSON response with a message of "Successfully associated"
     */
    public function associate_account_to_social(Request $request)
    {
        $request->validate([
            "channel_id" => "required|exists:channels,id",
            "account_id" => "required|exists:accounts,id",
            "socail_chat_id" => "required|string",
            "social_chat_username" => "string"
        ]);

        SocialChatAccount::create([
            "account_id" => $request->account_id,
            "socail_chat_id" => $request->socail_chat_id,
            "social_chat_username" => $request->social_chat_username,
            "channel_id" => $request->channel_id,
        ]);

        return response()->json([
            'message' => 'Successfully associated'
        ], 200);
    }
}
