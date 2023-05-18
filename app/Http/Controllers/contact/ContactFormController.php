<?php

namespace App\Http\Controllers\contact;

use App\Helpers\AccessChecker;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AccountType;
use App\Models\ContactForm;
use App\Models\ContactFormAttr;
use App\Models\ContactFormAttrOpts;
use App\Notifications\AccountTypeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;

use Illuminate\Validation\ValidationException;

class ContactFormController extends Controller
{
   //
   public function create_contact_form(Request $request)
   {
      $company_id = $request->user()->company_id;
      $validator = Validator::make($request->all(), [
         'name' => 'required|string|min:3',
         'description' => 'required|string|min:3'
      ]);
      $contact_form = ContactForm::where(['name' => $request->name, 'company_id' => $company_id])->first();
      if ($contact_form == null) {
         ContactForm::create([
            "name" => $request->name,
            "description" => $request->description,
            "company_id" => $company_id,
         ]);
         $user_to_notify = AccessChecker::get_users_with_similar_access($request->user()->id);
         foreach ($user_to_notify as $key => $user) {
            Notification::send($user->users, new AccountTypeNotification("New contact form created", 'Contact Form "' . $request->name . '" created by ' . $request->user()->name));
         }
         return response()->json([
            'message' => 'successfully added'
         ], 200);
      } else {
         throw ValidationException::withMessages(["Contact form name already exist"]);
      }
   }

   public function update_contact_form(Request $request)
   {
      $company_id = $request->user()->company_id;
      $validator = Validator::make($request->all(), [
         'contact_form_id' => 'required|exists:contact_forms,id',
         'name' => 'required|string|min:3',
         'description' => 'required|string|min:3'
      ]);
      $contact_form = ContactForm::where(['name' => $request->name, 'company_id' => $company_id])->first();
      if ($contact_form) {
         if ($contact_form->id == $request->contact_form_id) {
            throw ValidationException::withMessages(["Contact form name already exist"]);
         }
         if ($contact_form->company_id == $company_id) {
            throw ValidationException::withMessages(["Unathorized"]);
         }
      }

      $contact_form_original = ContactForm::find($request->contact_form_id);
      $contact_form_update = ContactForm::find($request->contact_form_id);
      $contact_form_update->name = $request->name;
      $contact_form_update->description = $request->description;
      $contact_form_update->save();

      $user_to_notify = AccessChecker::get_users_with_similar_access($request->user()->id);
      foreach ($user_to_notify as $key => $user) {
         Notification::send($user->users, new AccountTypeNotification("New contact form updated", 'Contact Form "' . $contact_form_original->name . ' changed to ' .  $contact_form_update->name . '" updated by ' . $request->user()->name));
      }

      return response()->json([
         'message' => 'successfully updated'
      ], 200);
   }

   public function get_contact_form(Request $request)
   {
      $company_id = $request->user()->company_id;
      return ContactForm::with("account_types")->where("company_id", $company_id)->get();
   }

   public function assign_contact_form_account_type(Request $request)
   {
      $validated_data = $request->validate([
         "contact_form_id" => "required|exists:contact_forms,id",
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

      $account_type_list = "";

      foreach ($request->account_types as $key => $account_type) {
         $account_type_update = AccountType::find($account_type);
         $account_type_update->contact_form_id = $request->contact_form_id;
         $account_type_update->save();
         if ($key == 0) {
            $account_type_list .= $account_type_update->name;
         } else if ($key == (count($request->account_types) - 1)) {
            $account_type_list .= " and, " . $account_type_update->name;
         } else {
            $account_type_list .= " , " . $account_type_update->name;
         }
      }
      $contact_form = ContactForm::find($request->contact_form_id);
      $user_to_notify = AccessChecker::get_users_with_similar_access($request->user()->id);
      foreach ($user_to_notify as $key => $user) {
         Notification::send($user->users, new AccountTypeNotification("New contact form assigned account type", 'Account type "' . $account_type_list  . '" assigned to contact form "' .  $contact_form->name . '" updated by ' . $request->user()->name));
      }

      return response()->json([
         'message' => 'Successfully updated'
      ], 200);
   }

   public function create_contact_form_items(Request $request)
   {
      $validation_data = $request->validate([
         "contact_form_id" => "required|exists:contact_forms,id",
         "multipleNames" => "required|array",
         "form_items" => "required|array"
      ]);

      $company_id = $request->user()->company_id;
      $contact_form_id = $request->contact_form_id;
      $check_form = ContactForm::find($request->contact_form_id);
      if ($check_form->company_id != $company_id) {
         throw ValidationException::withMessages(["Unauthorized"]);
      }
      $already_has_item = ContactFormAttr::where("contact_form_id", $request->contact_form_id)->first();
      if ($already_has_item) {
         throw ValidationException::withMessages(["already has items please edit"]);
      }
      // $name = str_replace(' ', '_', $name);
      try {
         DB::beginTransaction();

         /*******Names******************** */
         $this->check_duplicate($request->multipleNames, "name");
         $check_required = false;
         foreach ($request->multipleNames as $key => $multiple_name) {
            if ($multiple_name["is_required"] == "true") {
               $check_required = true;
            }
         }
         if (!$check_required) {
            throw ValidationException::withMessages(["At least one required filed is needed on name"]);
         }

         $sequence = 1;

         foreach ($request->multipleNames as $key => $multiple_name) {
            $account_form_attr_id = DB::table('contact_form_attrs')->insertGetId([
               "name" => $multiple_name["name"],
               "data_name" => str_replace(' ', '_', $multiple_name["name"]),
               "is_required" => ($multiple_name["is_required"] == "true") ? true : false,
               "data_type" => $multiple_name["data_type"],
               "is_masked" => ($multiple_name["is_masked"] == "true") ? true : false,
               "contact_form_id" => $contact_form_id,
               "status" => "ACTIVE",
               "sequence" => $sequence,
               "company_id" => $company_id,
               "created_at" => now(),
               "updated_at" => now()
            ]);
            $sequence += 1;
         }
         /*******from items******************** */
         $this->check_duplicate($request->form_items, "name");
         foreach ($request->form_items as $key => $form_item) {
            if ($form_item["data_type"] == "select" | $form_item["data_type"] == "radio" || $form_item["data_type"] == "checkbox") {
               if (!is_array($form_item["options"])) {
                  throw ValidationException::withMessages(["You need to add options to form:" . $form_item["name"]]);
               } else {
                  $this->check_duplicate($form_item["options"], "option_name");
               }
            }
         }

         foreach ($request->form_items as $key => $form_item) {
            /////addd form items
            $account_form_attr_id = DB::table('contact_form_attrs')->insertGetId([
               "name" => $form_item["name"],
               "data_name" => str_replace(' ', '_', $form_item["name"]),
               "is_required" => ($form_item["is_required"] == "true") ? true : false,
               "data_type" => $form_item["data_type"],
               "is_masked" => ($form_item["is_masked"] == "true") ? true : false,
               "contact_form_id" => $contact_form_id,
               "status" => "ACTIVE",
               "sequence" => $sequence,
               "company_id" => $company_id,
               "created_at" => now(),
               "updated_at" => now()
            ]);
            $sequence += 1;

            if ($form_item["data_type"] == "select" | $form_item["data_type"] == "radio" || $form_item["data_type"] == "checkbox") {
               if (!is_array($form_item["options"])) {
                  throw ValidationException::withMessages(["You need to add options to form:" . $form_item["name"]]);
               } else {
                  ////add options
                  foreach ($form_item["options"] as $options) {
                     $duplicate_option_check = ContactFormAttrOpts::where(["option_name" => $options["option_name"], "account_form_attr_id" => $account_form_attr_id])->first();
                     if ($duplicate_option_check) {
                        throw ValidationException::withMessages(["You need to add options to form:" . $options["option_name"]]);
                     }
                     DB::table('contact_form_attr_opts')->insertGetId([
                        "option_name" => $options["option_name"],
                        "account_form_attr_id" => $account_form_attr_id,
                        "created_at" => now(),
                        "updated_at" => now()
                     ]);
                  }
               }
            }
         }
         DB::commit();



         $user_to_notify = AccessChecker::get_users_with_similar_access($request->user()->id);
         foreach ($user_to_notify as $key => $user) {
            Notification::send($user->users, new AccountTypeNotification("Added from items", 'Added from items to contact form "' . $check_form->name . '" updated by ' . $request->user()->name));
         }

         return response()->json([
            'message' => 'Successfully added'
         ], 200);
      } catch (\Exception $e) {
         DB::rollback();
         throw $e;
      }
   }

   public function add_contact_form_items(Request $request)
   {
      $validation_data = $request->validate([
         "name" => "required|string",
         "is_masked" => "required|boolean",
         "is_required" => "required|boolean",
         "status" => "required|string",
         "contact_form_id" => "required|exists:contact_forms,id"
      ]);
      $company_id = $request->user()->company_id;
      $contact_form_id = $request->contact_form_id;
      $check_form = ContactForm::find($request->contact_form_id);
      if ($check_form->company_id != $company_id) {
         throw ValidationException::withMessages(["Unauthorized"]);
      }

      $check_duplicate_item_name = ContactFormAttr::where(["name" => $request->name, "contact_form_id" => $request->contact_form_id])->first();
      if ($check_duplicate_item_name) {
         throw ValidationException::withMessages(["You have a form with the same name  : " . $request->name]);
      }

      if ($request->data_type == "select" || $request->data_type == "radio" || $request->data_type == "checkbox") {
         $this->check_duplicate($request->options, "option_name");
      }
      $last_item = ContactFormAttr::where(["contact_form_id" => $request->contact_form_id])->orderByDesc('sequence')->first();

      $new_item = ContactFormAttr::create([
         "name" => $request->name,
         "data_name" => str_replace(' ', '_', $request->name),
         "is_required" => $request->is_required,
         "data_type" => $request->data_type,
         "is_masked" =>  $request->is_masked,
         "contact_form_id" => $contact_form_id,
         "status" => "ACTIVE",
         "sequence" => $last_item->sequence + 1,
         "company_id" => $company_id,
      ]);

      if ($request->data_type == "select" || $request->data_type == "radio" || $request->data_type == "checkbox") {

         foreach ($request->options as $key => $options) {
            ContactFormAttrOpts::create([
               "option_name" => $options["option_name"],
               "account_form_attr_id" => $new_item->id
            ]);
         }
      }
      return response()->json([
         'message' => 'Successfully added'
      ], 200);
   }

   public function update_contact_form_items(Request $request)
   {
      // return $request;
      $validation_data = $request->validate([
         "form_item_id" => "required|exists:contact_form_attrs,id",
         "name" => "required|string",
         "is_required" => "required|boolean",
         "status" => "required|string"
      ]);
      $company_id = $request->user()->company_id;
      $form_item_update = ContactFormAttr::find($request->form_item_id);
      if ($form_item_update->company_id != $company_id) {
         throw ValidationException::withMessages(["Unauthorized"]);
      }
      if ($form_item_update->data_type == "select" || $form_item_update->data_type == "radio" || $form_item_update->data_type == "checkbox") {
         if ($request->data_type != "select" && $request->data_type != "radio" && $request->data_type != "checkbox") {
            throw ValidationException::withMessages(["you are not allowed to change the data type to " . $request->data_type]);
         }
      } else {
         if ($request->data_type == "select" || $request->data_type == "radio" || $request->data_type == "checkbox") {
            throw ValidationException::withMessages(["you are not allowed to change the data type to " . $request->data_type]);
         }
      }

      $check_duplicate_item_name = ContactFormAttr::where(["name" => $request->name, "contact_form_id" => $form_item_update->contact_form_id])->first();
      if ($check_duplicate_item_name) {
         if ($check_duplicate_item_name->id != $form_item_update->id) {
            throw ValidationException::withMessages(["You have a form with the same name  : " . $request->name]);
         }
      }

      $form_item_update->name = $request->name;
      $form_item_update->data_type = $request->data_type;
      $form_item_update->is_masked = $request->is_masked;
      $form_item_update->is_required = $request->is_required;
      $form_item_update->status = $request->status;
      $form_item_update->save();
      return response()->json([
         'message' => 'Successfully updated'
      ], 200);
   }

   public function update_form_sequence(Request $request)
   {
      $validation_data = $request->validate([
         "contact_form_id" => "required|exists:contact_forms,id",
      ]);
      $company_id = $request->user()->company_id;
      $form_item_update = ContactForm::find($request->contact_form_id);
      if ($form_item_update->company_id != $company_id) {
         throw ValidationException::withMessages(["Unauthorized"]);
      }

      foreach ($request->form_items as $key => $form_item) {
         $form_items = ContactFormAttr::find($form_item["item_id"]);
         $form_items->sequence = $form_item["sequence"];
         $form_items->save();
      }

      return response()->json([
         'message' => 'Successfully updated'
      ], 200);
   }

   public function get_contact_form_items(Request $request)
   {
      $company_id = $request->user()->company_id;
      return ContactFormAttr::with("contact_form_attr_opts")->where(["company_id" => $company_id, "contact_form_id" => $request->contact_form_id])->get();
   }

   public function check_duplicate($check_array, $check_key)
   {
      foreach ($check_array as $current_key => $current_array) {
         foreach ($check_array as $search_key => $search_array) {
            if ($search_array[$check_key] == $current_array[$check_key]) {
               if ($search_key != $current_key) {
                  throw ValidationException::withMessages(["You have a form with the same " . $check_key . " : " . $search_array[$check_key]]);
               }
            }
         }
      }
   }
}
