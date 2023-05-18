<?php

namespace App\Http\Controllers\account;

use App\Helpers\AccessChecker;
use App\Http\Controllers\Controller;
use App\Models\AccountForm;
use App\Models\AccountFormAttr;
use App\Models\AccountFormAttrOption;
use App\Models\AccountType;
use App\Notifications\AccountTypeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class AccountFormController extends Controller
{
    public function create_account_form(Request $request)
    {
        $validation_data = $request->validate([
            "name" => "required|string",
            "description" => "required|string"
        ]);
        $company_id = $request->user()->company_id;

        $duplicate_check = AccountForm::where(["name" => $request->name, "company_id" => $company_id])->first();
        if ($duplicate_check) {
            throw ValidationException::withMessages(["name already exist"]);
        }
        AccountForm::create([
            "name" => $request->name,
            "description" => $request->description,
            "company_id" => $company_id
        ]);

        $user_to_notify = AccessChecker::get_users_with_similar_access($request->user()->id);
        foreach ($user_to_notify as $key => $user) {
            Notification::send($user->users, new AccountTypeNotification("New account form created", 'Account Form "' . $request->name . '" created by ' . $request->user()->name));
        }

        return response()->json([
            'message' => 'Successfully saved'
        ], 200);
    }

    public function edit_account_form(Request $request)
    {
        $validation_data = $request->validate([
            "id" => "required|exists:account_forms,id",
            "name" => "required|string",
            "description" => "required|string"
        ]);
        $company_id = $request->user()->company_id;
        $check_duplicate = AccountForm::where(["name" => $request->name, "company_id" => $company_id])->first();
        if ($check_duplicate) {
            if ($check_duplicate->id != $request->id) {
                throw ValidationException::withMessages(["name already exist"]);
            }
        }
        $account_form_original = AccountForm::find($request->id);
        if ($account_form_original->company_id != $company_id) {
            throw ValidationException::withMessages(["Unauthorized"]);
        }
        $account_form_update = AccountForm::find($request->id);
        $account_form_update->name = $request->name;
        $account_form_update->description = $request->description;
        $account_form_update->save();

        $user_to_notify = AccessChecker::get_users_with_similar_access($request->user()->id);
        foreach ($user_to_notify as $key => $user) {
            Notification::send($user->users, new AccountTypeNotification("New account form updated", 'Account Form "' . $account_form_original->name . ' changed to ' .  $account_form_update->name . '" updated by ' . $request->user()->name));
        }

        return response()->json([
            'message' => 'Successfully updated'
        ], 200);
    }

    public function assign_account_form_to_account_type(Request $request)
    {
        $validation_data = $request->validate([
            "account_form_id" => "required|exists:account_forms,id",
            "account_type_id" => "required|exists:account_types,id",
        ]);

        $company_id = $request->user()->company_id;
        $account_type = AccountType::find($request->account_type_id);
        $account_form = AccountForm::find($request->account_form_id);
        if ($account_type->company_id != $company_id) {
            throw ValidationException::withMessages(["Unauthorized"]);
        }
        if ($account_form->company_id != $company_id) {
            throw ValidationException::withMessages(["Unauthorized"]);
        }
        $account_type->account_form_id = $request->account_form_id;
        $account_type->save();

        $user_to_notify = AccessChecker::get_users_with_similar_access($request->user()->id);
        foreach ($user_to_notify as $key => $user) {
            Notification::send($user->users, new AccountTypeNotification("New account form assigned account type", 'Account type "' . $account_type->name . '" assigned to account form "' .  $account_form->name . '" updated by ' . $request->user()->name));
        }

        return response()->json([
            'message' => 'Successfully updated'
        ], 200);
    }

    public function get_account_forms(Request $request)
    {
        $company_id = $request->user()->company_id;
        return AccountForm::with("account_form_items")->where("company_id", $company_id)->get();
    }

    public function create_account_form_items(Request $request)
    {
        $validation_data = $request->validate([
            "account_form_id" => "required|exists:account_forms,id",
            "multipleNames" => "required|array",
            "form_items" => "required|array"
        ]);

        $company_id = $request->user()->company_id;
        $account_form_id = $request->account_form_id;
        $check_form = AccountForm::find($request->account_form_id);
        if ($check_form->company_id != $company_id) {
            throw ValidationException::withMessages(["Unauthorized"]);
        }
        $already_has_item = AccountFormAttr::where("account_form_id", $request->account_form_id)->first();
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
                $account_form_attr_id = DB::table('account_form_attrs')->insertGetId([
                    "name" => $multiple_name["name"],
                    "data_name" => str_replace(' ', '_', $multiple_name["name"]),
                    "is_required" => ($multiple_name["is_required"] == "true") ? true : false,
                    "data_type" => $multiple_name["data_type"],
                    "is_masked" => ($multiple_name["is_masked"] == "true") ? true : false,
                    "account_form_id" => $account_form_id,
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
                        $this->check_duplicate($form_item["options"], "option");
                    }
                }
            }

            foreach ($request->form_items as $key => $form_item) {
                /////addd form items
                $account_form_attr_id = DB::table('account_form_attrs')->insertGetId([
                    "name" => $form_item["name"],
                    "data_name" => str_replace(' ', '_', $form_item["name"]),
                    "is_required" => ($form_item["is_required"] == "true") ? true : false,
                    "data_type" => $form_item["data_type"],
                    "is_masked" => ($form_item["is_masked"] == "true") ? true : false,
                    "account_form_id" => $account_form_id,
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
                            $duplicate_option_check = AccountFormAttrOption::where(["option_name" => $options["option"], "account_form_attr_id" => $account_form_attr_id])->first();
                            if ($duplicate_option_check) {
                                throw ValidationException::withMessages(["You need to add options to form:" . $options["option"]]);
                            }
                            DB::table('account_form_attr_options')->insertGetId([
                                "option_name" => $options["option"],
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
                Notification::send($user->users, new AccountTypeNotification("Added from items", 'Added from items to Account form "' . $check_form->name . '" updated by ' . $request->user()->name));
            }
            return response()->json([
                'message' => 'Successfully added'
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function add_account_form_item(Request $request)
    {
        $validation_data = $request->validate([
            "name" => "required|string",
            "is_masked" => "required|boolean",
            "is_required" => "required|boolean",
            "status" => "required|string",
            "account_form_id" => "required|exists:account_forms,id"
        ]);
        $company_id = $request->user()->company_id;
        $account_form_id = $request->account_form_id;
        $check_form = AccountForm::find($request->account_form_id);
        if ($check_form->company_id != $company_id) {
            throw ValidationException::withMessages(["Unauthorized"]);
        }

        $check_duplicate_item_name = AccountFormAttr::where(["name" => $request->name, "account_form_id" => $request->account_form_id])->first();
        if ($check_duplicate_item_name) {
            throw ValidationException::withMessages(["You have a form with the same name  : " . $request->name]);
        }

        if ($request->data_type == "select" || $request->data_type == "radio" || $request->data_type == "checkbox") {
            $this->check_duplicate($request->options, "option_name");
        }
        $last_item = AccountFormAttr::where(["account_form_id" => $request->account_form_id])->orderByDesc('sequence')->first();

        $new_item = AccountFormAttr::create([
            "name" => $request->name,
            "data_name" => str_replace(' ', '_', $request->name),
            "is_required" => $request->is_required,
            "data_type" => $request->data_type,
            "is_masked" =>  $request->is_masked,
            "account_form_id" => $account_form_id,
            "status" => "ACTIVE",
            "sequence" => $last_item->sequence + 1,
            "company_id" => $company_id,
        ]);

        if ($request->data_type == "select" || $request->data_type == "radio" || $request->data_type == "checkbox") {

            foreach ($request->options as $key => $options) {
                AccountFormAttrOption::create([
                    "option_name" => $options["option_name"],
                    "account_form_attr_id" => $new_item->id
                ]);
            }
        }
        return response()->json([
            'message' => 'Successfully added'
        ], 200);
    }

    public function update_account_form_items(Request $request)
    {
        // return $request;
        $validation_data = $request->validate([
            "form_item_id" => "required|exists:account_form_attrs,id",
            "name" => "required|string",
            "is_required" => "required|boolean",
            "status" => "required|string"
        ]);
        $company_id = $request->user()->company_id;
        $form_item_update = AccountFormAttr::find($request->form_item_id);
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

        $check_duplicate_item_name = AccountFormAttr::where(["name" => $request->name, "account_form_id" => $form_item_update->account_form_id])->first();
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
            "account_form_id" => "required|exists:account_forms,id",
        ]);
        $company_id = $request->user()->company_id;
        $form_item_update = AccountForm::find($request->account_form_id);
        if ($form_item_update->company_id != $company_id) {
            throw ValidationException::withMessages(["Unauthorized"]);
        }

        foreach ($request->form_items as $key => $form_item) {
            $form_items = AccountFormAttr::find($form_item["item_id"]);
            $form_items->sequence = $form_item["sequence"];
            $form_items->save();
        }

        return response()->json([
            'message' => 'Successfully updated'
        ], 200);
    }



    /////////////////////////////////////////////////////////////////////////////////////////////////////////////
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

    public function get_account_form_items(Request $request)
    {
        $company_id = $request->user()->company_id;
        $account_form_id = $request->account_form_id;
        return AccountFormAttr::with("account_form_attr_options")->where(["account_form_id" => $account_form_id])->get();
    }

    public function get_account_form_in_group(Request $request)
    {
        $request->validate(["group_id"=>"required|exists:groups,id"]);
        
    }
}