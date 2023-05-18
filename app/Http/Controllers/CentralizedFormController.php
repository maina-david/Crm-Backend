<?php

namespace App\Http\Controllers;

use App\Models\CentralizedForm;
use App\Models\FormAttributeOption;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use App\Helpers\AccessChecker;
use App\Models\FormAttribute;
use App\Notifications\AccountTypeNotification;

class CentralizedFormController extends Controller
{

    /**
     * It returns all the data from the CentralizedForm table where the company_id is equal to the user's
     * company_id.
     * 
     * @return A list of all the CentralizedForms that belong to the company that the user is logged in as.
     */
    public function get_forms()
    {
        $data = CentralizedForm::where('company_id', Auth::user()->company_id)->get();

        return response()->json($data, 200);
    }

    /**
     * This function returns all the helpdesk forms of the company
     * 
     * @return The helpdesk forms of the company
     */
    public function helpdesk_forms()
    {
        $helpdeskForms = CentralizedForm::where([
            'company_id' => Auth::user()->company_id,
            'type' => 'HELPDESK'
        ])->get();

        return response()->json([
            'success' => true,
            'helpdesk_forms' => $helpdeskForms
        ], 200);
    }

    /**
     * It validates the request, creates a new centralized form, and returns a JSON response
     * 
     * @param Request request The request object
     * 
     * @return A JSON response with a success message and the data of the newly created centralized form.
     */
    public function create_form(Request $request)
    {
        $request->validate([
            'type' => 'required|in:HELPDESK,WORKFLOW,SURVEYFORM',
            'name' => 'required|unique:centralized_forms,name',
            'description' => 'required',
        ]);

        $centralizedForm = CentralizedForm::create([
            'company_id' => Auth::user()->company_id,
            'type' => $request->type,
            'name' => $request->name,
            'description' => $request->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Form saved successfully!',
            'data' => $centralizedForm
        ], 200);
    }

    /**
     * It creates form items for a centralized form
     * 
     * @param Request request the request object
     */
    public function createFormItems(Request $request)
    {
        $request->validate([
            "form_id" => "required|exists:centralized_forms,id",
            "multipleNames" => "nullable|array",
            "form_items" => "required|array"
        ]);

        $company_id = Auth::user()->company_id;
        $form_id = $request->form_id;
        $check_form = CentralizedForm::find($request->form_id);
        if ($check_form->company_id != $company_id) {
            throw ValidationException::withMessages(["Unauthorized"]);
        }
        $already_has_item = FormAttribute::where("form_id", $request->form_id)->first();
        if ($already_has_item) {
            throw ValidationException::withMessages(["already has items please edit"]);
        }
        // $name = str_replace(' ', '_', $name);
        try {
            DB::beginTransaction();
            $sequence = 1;
            // if ($request->has('multipleNames')) {
            /*******Names******************** */
            // $this->check_duplicate($request->multipleNames, "name");
            // $check_required = false;
            // foreach ($request->multipleNames as $key => $multiple_name) {
            //     if ($multiple_name["is_required"] == "true") {
            //         $check_required = true;
            //     }
            // }
            // if (!$check_required) {
            //     throw ValidationException::withMessages(["At least one required filed is needed on name"]);
            // }

            //     $sequence = 1;
            //     foreach ($request->multipleNames as $key => $multiple_name) {
            //         $form_attr_id = DB::table('form_attributes')->insertGetId([
            //             "name" => $multiple_name["name"],
            //             "data_name" => str_replace(' ', '_', $multiple_name["name"]),
            //             "is_required" => ($multiple_name["is_required"] == "true") ? true : false,
            //             "data_type" => $multiple_name["data_type"],
            //             "is_masked" => ($multiple_name["is_masked"] == "true") ? true : false,
            //             "form_id" => $form_id,
            //             "status" => "ACTIVE",
            //             "sequence" => $sequence,
            //             "company_id" => $company_id,
            //             "created_at" => now(),
            //             "updated_at" => now()
            //         ]);
            //         $sequence += 1;
            //     }
            // }
            /*******form items******************** */
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
                $form_attr_id = DB::table('form_attributes')->insertGetId([
                    "name" => $form_item["name"],
                    "data_name" => str_replace(' ', '_', $form_item["name"]),
                    "is_required" => ($form_item["is_required"] == "true") ? true : false,
                    "data_type" => $form_item["data_type"],
                    "is_masked" => ($form_item["is_masked"] == "true") ? true : false,
                    "form_id" => $form_id,
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
                            $duplicate_option_check = FormAttributeOption::where(["option_name" => $options["option"], "form_attr_id" => $form_attr_id])->first();
                            if ($duplicate_option_check) {
                                throw ValidationException::withMessages(["You need to add options to form:" . $options["option"]]);
                            }
                            DB::table('form_attribute_options')->insertGetId([
                                "option_name" => $options["option"],
                                "form_attr_id" => $form_attr_id,
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
                Notification::send($user->users, new AccountTypeNotification("Added form items", 'Added form items to centralized form "' . $check_form->name . '" updated by ' . $request->user()->name));
            }
            return response()->json([
                'message' => 'Successfully added'
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * It adds a form item to the centralized form
     * 
     * @param Request request The request object.
     */
    public function add_form_item(Request $request)
    {
        $request->validate([
            "name" => "required|string",
            "is_masked" => "required|boolean",
            "is_required" => "required|boolean",
            "status" => "required|string",
            "form_id" => "required|exists:centralized_forms,id"
        ]);
        $company_id = $request->user()->company_id;
        $form_id = $request->form_id;
        $check_form = CentralizedForm::find($request->form_id);
        if ($check_form->company_id != $company_id) {
            throw ValidationException::withMessages(["Unauthorized"]);
        }

        $check_duplicate_item_name = FormAttribute::where(["name" => $request->name, "form_id" => $request->form_id])->first();
        if ($check_duplicate_item_name) {
            throw ValidationException::withMessages(["You have a form with the same name  : " . $request->name]);
        }

        if ($request->data_type == "select" || $request->data_type == "radio" || $request->data_type == "checkbox") {
            $this->check_duplicate($request->options, "option");
        }
        $last_item = FormAttribute::where(["form_id" => $request->form_id])->orderByDesc('sequence')->first();

        $new_item = FormAttribute::create([
            "name" => $request->name,
            "data_name" => str_replace(' ', '_', $request->name),
            "is_required" => $request->is_required,
            "data_type" => $request->data_type,
            "is_masked" =>  $request->is_masked,
            "form_id" => $form_id,
            "status" => "ACTIVE",
            "sequence" => $last_item->sequence + 1,
            "company_id" => $company_id,
        ]);

        if ($request->data_type == "select" || $request->data_type == "radio" || $request->data_type == "checkbox") {

            foreach ($request->options as $key => $options) {
                FormAttributeOption::create([
                    "option_name" => $options["option"],
                    "form_attr_id" => $new_item->id
                ]);
            }
        }
        return response()->json([
            'message' => 'Successfully added'
        ], 200);
    }

    /**
     * This function updates the form items
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a message.
     */
    public function update_form_items(Request $request)
    {
        $request->validate([
            "form_item_id" => "required|exists:form_attributes,id",
            "name" => "required|string",
            "is_required" => "required|boolean",
            "status" => "required|string"
        ]);
        $company_id = $request->user()->company_id;
        $form_item_update = FormAttribute::find($request->form_item_id);
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

        $check_duplicate_item_name = FormAttribute::where(["name" => $request->name, "form_id" => $form_item_update->form_id])->first();
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

    /**
     * It updates the sequence of the form items
     * 
     * @param Request request The request object.
     * 
     * @return A JSON response with a message of "Successfully updated"
     */
    public function update_form_sequence(Request $request)
    {
        $validation_data = $request->validate([
            "form_id" => "required|exists:centralized_forms,id",
        ]);
        $company_id = $request->user()->company_id;
        $form_item_update = CentralizedForm::find($request->form_id);
        if ($form_item_update->company_id != $company_id) {
            throw ValidationException::withMessages(["Unauthorized"]);
        }

        foreach ($request->form_items as $key => $form_item) {
            $form_items = FormAttribute::find($form_item["item_id"]);
            $form_items->sequence = $form_item["sequence"];
            $form_items->save();
        }

        return response()->json([
            'message' => 'Successfully updated'
        ], 200);
    }

    /**
     * It takes an array of arrays and a key to check for duplicates. If it finds a duplicate, it throws a
     * validation exception
     * 
     * @param check_array The array that you want to check for duplicates.
     * @param check_key The key to check for duplicates.
     */
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

    /**
     * It returns all the form attributes for a given form
     * 
     * @param Request request The request object.
     * 
     * @return The form attributes and their options for a given form.
     */
    public function get_form_items(Request $request)
    {
        $request->validate([
            "form_id" => 'required|exists:centralized_forms,id'
        ]);

        $form_id = $request->form_id;

        $centralizedForm = CentralizedForm::find($form_id);

        if ($centralizedForm->company_id != Auth::user()->company_id) {
            return response()->json([
                'error' => true,
                'message' => 'Form does not belong to your company!'
            ], 401);
        }
        return FormAttribute::with("form_attr_options")->where(["form_id" => $form_id])->get();
    }

    /**
     * It checks if the form belongs to the user's company, and if it does, it returns the form
     * 
     * @param CentralizedForm centralizedForm This is the model that we are using.
     * 
     * @return A JSON response with the centralized form data.
     */
    public function show(CentralizedForm $centralizedForm)
    {
        if ($centralizedForm->company_id != Auth::user()->company_id) {
            return response()->json([
                'error' => true,
                'message' => 'Form does not belong to your company!'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => $centralizedForm
        ], 200);
    }

    /**
     * It updates the centralized form's name and description
     * 
     * @param Request request The request object
     * @param CentralizedForm centralizedForm The centralized form that will be updated.
     * 
     * @return The response is being returned in JSON format.
     */
    public function edit_form(Request $request, $id)
    {
        $centralizedForm = CentralizedForm::findOrFail($id);

        $request->validate([
            'type' => 'required|in:HELPDESK,WORKFLOW,SURVEYFORM',
            'name' => 'required|unique:centralized_forms,name,' . $centralizedForm->id,
            'description' => 'required',
        ]);

        if ($centralizedForm->company_id != Auth::user()->company_id) {
            return response()->json([
                'error' => true,
                'message' => 'Form does not belong to your company!'
            ], 401);
        }

        $centralizedForm->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Form updated successfully!',
            'data' => $centralizedForm
        ], 200);
    }

    /**
     * It deletes the form from the database
     * 
     * @param CentralizedForm centralizedForm This is the model that we are using for the API.
     * 
     * @return A JSON response with a success message.
     */
    public function destroy($id)
    {
        $centralizedForm = CentralizedForm::findOrFail($id);

        if ($centralizedForm->company_id != Auth::user()->company_id) {
            return response()->json([
                'error' => true,
                'message' => 'Form does not belong to your company!'
            ], 401);
        }

        $centralizedForm->delete();

        return response()->json([
            'success' => true,
            'message' => 'Form deleted successfully!'
        ], 200);
    }
}