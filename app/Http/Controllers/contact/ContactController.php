<?php

namespace App\Http\Controllers\contact;

use App\Helpers\AccessChecker;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactLogResource;
use App\Http\Resources\ContactResource;
use App\Http\Resources\ContactResourceModified;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\QueueLogResource;
use App\Http\Resources\TicketsResource;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Contact;
use App\Models\AgentBreak;
use App\Models\ContactData;
use App\Models\ContactFormAttr;
use App\Models\ContactLog;
use App\Models\ContactStage;
use App\Models\ContactStageData;
use App\Models\Conversation;
use App\Models\QueueLog;
use App\Services\PhoneFormatterService;
use Auth;
use Faker\Guesser\Name;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
   public function create_contact(Request $request)
   {
      $account_id = $request->account_id;
      $contact_form_id = $request->contact_form_id;
      $company_id = $request->user()->company_id;

      $approve_access = AccessChecker::has_account_aprove_access($request->user()->id);
      $create_access = AccessChecker::has_account_create_access($request->user()->id);

      if (!$approve_access && !$create_access) {
         throw ValidationException::withMessages(["Unathorized"]);
      }

      $formdata = ContactFormAttr::with("contact_form_attr_opts")->where("contact_form_id", $contact_form_id)->get();

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

      if ($approve_access) {
         $other_contact = Contact::where("account_id", $account_id)->first();
         $is_primary = false;
         if (!$other_contact) {
            $is_primary = true;
         }
         $account = Account::find($account_id);
         $contact = Contact::create([
            "first_name" => $first_name,
            "middle_name" => $maiden_name,
            "last_name" => $last_name,
            "contact_form_id" => $contact_form_id,
            "account_id" => $account_id,
            "created_by" => $request->user()->id,
            "company_id" => $company_id,
            "account_type_id" => $account->account_type_id,
            "is_primary" => $is_primary
         ]);
         foreach ($formdata as $key => $form_item) {
            $value = $request->user_response[$form_item->id];
            if ($form_item->data_type == "password") {
               $value = Hash::make($request->user_response[$form_item->id]);
            }
            $form_item_id = $form_item->id;

            ContactData::create([
               "contact_id" => $contact->id,
               "contact_form_attr_id" => $form_item_id,
               "value" => $value
            ]);
         }

         $this->log_contact_change($contact->id, "CREATE", $request->user()->id, null, now());
         return response()->json([
            'message' => 'Successfully created'
         ], 200);
         return response()->json([
            'message' => 'Successfully created'
         ], 200);
      } else if ($create_access) {
         $other_contact = Contact::where("account_id", $account_id)->first();
         $is_primary = false;
         if (!$other_contact) {
            $is_primary = true;
         }
         $account = Account::find($account_id);
         $contact = ContactStage::create([
            "first_name" => $first_name,
            "middle_name" => $maiden_name,
            "last_name" => $last_name,
            "contact_form_id" => $contact_form_id,
            "account_id" => $account_id,
            "created_by" => $request->user()->id,
            "company_id" => $company_id,
            "is_primary" => $is_primary,
            "account_type_id" => $account->account_type_id,
            "approval_type" => "CREATE"
         ]);
         foreach ($formdata as $key => $form_item) {
            $value = $request->user_response[$form_item->id];
            if ($form_item->data_type == "password") {
               $value = Hash::make($request->user_response[$form_item->id]);
            }
            if ($form_item->data_type == "phone") {
               $value = PhoneFormatterService::format_phone($request->user_response[$form_item->id]);
            }
            $form_item_id = $form_item->id;

            ContactStageData::create([
               "contact_stage_id" => $contact->id,
               "contact_form_attr_id" => $form_item_id,
               "value" => $value
            ]);
         }
         return response()->json([
            'message' => 'Successfully created, Pending approval'
         ], 200);
      }
   }

   public function update_contact(Request $request)
   {
      $contact_id = $request->contact_id;
      $account_id = $request->account_id;
      $contact_form_id = $request->contact_form_id;
      $company_id = $request->user()->company_id;

      $approve_access = AccessChecker::has_account_aprove_access($request->user()->id);
      $create_access = AccessChecker::has_account_create_access($request->user()->id);

      if (!$approve_access && !$create_access) {
         throw ValidationException::withMessages(["Unathorized"]);
      }

      $formdata = ContactFormAttr::with("contact_form_attr_opts")->where("contact_form_id", $contact_form_id)->get();

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


      if ($approve_access) {

         $contact_update = Contact::find($contact_id);

         $contact_update->first_name = $first_name;
         $contact_update->middle_name = $maiden_name;
         $contact_update->last_name = $last_name;
         $contact_update->contact_form_id = $contact_form_id;
         $contact_update->account_id = $account_id;
         $contact_update->updated_by = $request->user()->id;
         $contact_update->save();

         foreach ($formdata as $key => $form_item) {
            $value = $request->user_response[$form_item->id];
            if ($form_item->data_type == "password") {
               $value = Hash::make($request->user_response[$form_item->id]);
            }
            if ($form_item->data_type == "phone") {
               $value = PhoneFormatterService::format_phone($request->user_response[$form_item->id]);
            }
            $form_item_id = $form_item->id;

            if ($form_item->data_type == "checkbox") {
               ContactData::where([
                  "contact_id" => $contact_id,
                  "contact_form_attr_id" => $form_item->id
               ])->delete();
               foreach ($value as $val) {
                  ContactData::create([
                     "contact_id" => $contact_id,
                     "contact_form_attr_id" => $form_item_id,
                     "value" => $val
                  ]);
               }
            } else {
               $update_contact_data = ContactData::where([
                  "contact_id" => $contact_id,
                  "contact_form_attr_id" => $form_item->id
               ])->first();
               $contact_data_update = ContactData::find($update_contact_data->id);
               $contact_data_update->value = $value;
               $contact_data_update->save();
            }
         }
         $this->log_contact_change($contact_id, "update", $request->user()->id, null, now());
         return response()->json([
            'message' => 'Successfully Updated'
         ], 200);
         return response()->json([
            'message' => 'Successfully updated'
         ], 200);
      } else if ($create_access) {
         $other_contact = Contact::where("account_id", $account_id)->first();
         $is_primary = false;
         if (!$other_contact) {
            $is_primary = true;
         }
         $account = Account::find($account_id);
         $contact = ContactStage::create([
            "first_name" => $first_name,
            "middle_name" => $maiden_name,
            "last_name" => $last_name,
            "contact_form_id" => $contact_form_id,
            "account_id" => $account_id,
            "created_by" => $request->user()->id,
            "company_id" => $company_id,
            "is_primary" => $is_primary,
            "account_type_id" => $account->account_type_id,
            "approval_type" => "UPDATE",
            "contact_id" => $contact_id
         ]);
         foreach ($formdata as $key => $form_item) {
            $value = $request->user_response[$form_item->id];
            if ($form_item->data_type == "password") {
               $value = Hash::make($request->user_response[$form_item->id]);
            }
            $form_item_id = $form_item->id;

            ContactStageData::create([
               "contact_stage_id" => $contact->id,
               "contact_form_attr_id" => $form_item_id,
               "value" => $value
            ]);
         }
         return response()->json([
            'message' => 'Successfully updated, Pending approval'
         ], 200);
      }
   }


   public function get_contacts(Request $request)
   {
      $access_checker = new AccessChecker();
      $account_types = $access_checker->get_account_types($request->user()->id);
      if (!empty($account_types)) {
         $query = Contact::query();
         foreach ($account_types as $key => $account_type) {
            $query = $query->orWhere("account_type_id", $account_type->id);
         }

         return $query->paginate();
      }
   }

   public function get_contact_pending(Request $request)
   {
      $access_checker = new AccessChecker();
      $account_types = AccountType::where("company_id", Auth::user()->company_id)->get()->pluck('id');
      if (!Auth::user()->is_owner) {
         $account_types = $access_checker->get_account_types_update($request->user()->id);
      }

      $pending_contacts = ContactStage::whereIn("account_type_id", $account_types)
         ->where("status", "PENDING")->paginate();
      return $pending_contacts;
   }

   public function get_account_detail_pending(Request $request)
   {
      $validate_date = $request->validate([
         "contact_id" => "required|exists:contact_stages,id"
      ]);
      $account_original = null;

      $account_stage = ContactStage::find($request->contact_id);
      if ($account_stage == "CREATE") {
         $account_original = ContactData::with("contact_form_attr")->where(["contact_id" => $request->contact_id])->get();
      }
      $account = ContactStageData::with("contact_form_attr")->where(["contact_stage_id" => $request->contact_id])->get();
      return response()->json([
         'account_original' => $account_original,
         'account_updated' => $account
      ], 200);
   }

   /**
    * It gets the contact detail, contact form attributes, phone numbers, queue logs, social accounts,
    * and conversations
    * 
    * @param Request request contact_id
    */
   public function get_contact_detail(Request $request)
   {
      $contact_detail = ContactData::with("contact_form_attr")->where("contact_id", $request->contact_id)->get();

      $contact_form_attr = ContactFormAttr::where("contact_form_id")->get();


      $validate_date = $request->validate([
         "contact_id" => "required|exists:contacts,id"
      ]);
      $contact = Contact::find($request->contact_id);
      if ($contact->company_id != Auth::user()->company_id) {
         return response()->json([
            'error' => true,
            'message' => 'Contact does not belong to your company!'
         ], 401);
      }

      $account_form_attrs = ContactFormAttr::where(["contact_form_id" => $contact->contact_form_id, "data_type" => "phone"])->get();

      $phone_numbers = array();
      foreach ($account_form_attrs as $key => $contact_form_attr) {
         $phone_number = ContactData::where(["contact_form_attr_id" => $contact_form_attr->id, "contact_id" => $contact->id])->first();
         if ($phone_number) {
            $phone_numbers[$key] = $phone_number->value;
         }
      }
      $queuelogs = QueueLog::where("company_id", Auth::user()->company_id)
         ->whereIn("caller_id", $phone_numbers)
         ->latest()
         ->limit(5)
         ->get();

      $social_accts = $contact->social_chat_accounts;
      $conversations = array();
      foreach ($social_accts as $key => $social_acct) {
         $conversations = Conversation::where(["channel_id" => $social_acct->channel_id, "customer_id" => $social_acct->social_account])->latest()->get();
      }

      return response()->json([
         'contact_detail' => new ContactResource($contact),
         'contact_logs' => ContactLogResource::collection($contact->contact_logs),
         'tickets' => TicketsResource::collection($contact->tickets),
         'call_logs' => QueueLogResource::collection($queuelogs),
         'conversations' => (!empty($conversations)) ? ConversationResource::collection($conversations) : "Conversation not found"
      ], 200);
   }


   /**
    * It gets the contact detail, contact logs, tickets, call logs, and conversations of a contact
    * 
    * @param Request request contact_id
    */
   public function get_contact_detail_modified(Request $request)
   {
      $contact_detail = ContactData::with("contact_form_attr")->where("contact_id", $request->contact_id)->get();

      $contact_form_attr = ContactFormAttr::where("contact_form_id")->get();


      $validate_date = $request->validate([
         "contact_id" => "required|exists:contacts,id"
      ]);
      $contact = Contact::find($request->contact_id);
      if ($contact->company_id != Auth::user()->company_id) {
         return response()->json([
            'error' => true,
            'message' => 'Contact does not belong to your company!'
         ], 401);
      }

      $account_form_attrs = ContactFormAttr::where(["contact_form_id" => $contact->contact_form_id, "data_type" => "phone"])->get();

      $phone_numbers = array();
      foreach ($account_form_attrs as $key => $contact_form_attr) {
         $phone_number = ContactData::where(["contact_form_attr_id" => $contact_form_attr->id, "contact_id" => $contact->id])->first();
         if ($phone_number) {
            $phone_numbers[$key] = $phone_number->value;
         }
      }

      $queuelogs = QueueLog::where("company_id", Auth::user()->company_id)
         ->whereIn("caller_id", $phone_numbers)
         ->latest()
         ->limit(5)
         ->get();

      $social_accts = $contact->social_chat_accounts;
      $conversations = array();
      foreach ($social_accts as $key => $social_acct) {
         $conversations = Conversation::where(["channel_id" => $social_acct->channel_id, "customer_id" => $social_acct->social_account])->latest()->get();
      }

      return response()->json([
         'contact_detail' => new ContactResourceModified($contact),
         'contact_logs' => ContactLogResource::collection($contact->contact_logs),
         'tickets' => TicketsResource::collection($contact->tickets),
         'call_logs' => QueueLogResource::collection($queuelogs),
         'conversations' => (!empty($conversations)) ? ConversationResource::collection($conversations) : "Conversation not found"
      ], 200);
   }


   public function contact_approve_request(Request $request)
   {
      $validate_data = $request->validate([
         "contact_stage_id" => "required|exists:contact_stages,id"
      ]);
      $contact_stage_id = $request->contact_stage_id;
      $approve_access = AccessChecker::has_account_aprove_access($request->user()->id);
      $company_id = $request->user()->company_id;
      if (!$approve_access) {
         throw ValidationException::withMessages(["Unathorized"]);
      }

      $contact_stage = ContactStage::find($contact_stage_id);

      $access_group_check = AccessChecker::check_has_group_access_account_type($request->user()->id, $contact_stage->account_type_id);

      if (!$access_group_check) {
         throw ValidationException::withMessages(["Unathorized"]);
      }

      if ($contact_stage->approved_by != null) {
         throw ValidationException::withMessages(["Already approved"]);
      }

      if ($contact_stage->approval_type == "CREATE") {
         $contact = Contact::create([
            "account_id" => $contact_stage->account_id,
            "first_name" => $contact_stage->first_name,
            "middle_name" => $contact_stage->maiden_name,
            "last_name" => $contact_stage->last_name,
            "contact_form_id" => $contact_stage->contact_form_id,
            "account_type_id" => $contact_stage->account_type_id,
            "created_by" => $contact_stage->created_by,
            "approved_by" => $request->user()->id,
            "company_id" => $company_id
         ]);
         $contact_stage_data = ContactStageData::where("contact_stage_id", $contact_stage_id)->get();
         // return $account_stage_data;
         foreach ($contact_stage_data as $key => $form_item) {
            ContactData::create([
               "contact_id" => $contact->id,
               "contact_form_attr_id" => $form_item->contact_form_attr_id,
               "value" => $form_item->value
            ]);
         }
         $this->log_contact_change($contact->id, "CREATE", $contact_stage->created_by,  $request->user()->id, $contact_stage->created_at);
         $contact_stage->approved_by = $request->user()->id;
         $contact_stage->status = "APPROVED";
         $contact_stage->approval_type = "APPROVED";
         $contact_stage->save();
         $contact_stage->save();
         return response()->json([
            'message' => 'Successfully approved'
         ], 200);
      } else {
         $contact_id = $contact_stage->account_id;
         $contact_update = Contact::find($contact_id);
         $contact_update->first_name = $contact_stage->first_name;
         $contact_update->middle_name = $contact_stage->maiden_name;
         $contact_update->last_name = $contact_stage->last_name;
         $contact_update->account_form_id = $contact_stage->account_form_id;
         $contact_update->account_type_id = $contact_stage->account_type_id;
         $contact_update->updated_by = $contact_stage->created_by;
         $contact_update->approved_by = $request->user()->id;
         $contact_update->save();
         $account_stage_data = ContactStageData::where("contact_stage_id", $contact_stage_id)->get();
         $formdata = ContactData::where("account_id", $contact_id)->delete();
         foreach ($account_stage_data as $key => $form_item) {
            ContactData::create([
               "account_id" => $contact_id,
               "account_form_attr_id" => $form_item->account_form_attr_id,
               "value" => $form_item->value
            ]);
         }

         $this->log_account_change($contact_id, "UPDATE", $contact_stage->created_by,  $request->user()->id, $contact_stage->created_at);
         $contact_stage->approved_by = $request->user()->id;
         $contact_stage->approval_type = "APPROVED";
         $contact_stage->save();
         return response()->json([
            'message' => 'Successfully approved'
         ], 200);
      }
   }

   public function log_contact_change($contact_id, $change_type, $done_by, $approved_by, $created_date)
   {
      ContactLog::create([
         "contact_id" => $contact_id,
         "action" => $change_type,
         "changed_by" => $done_by,
         "approved_by" => $approved_by,
         "start_date" => $created_date
      ]);
   }
}
