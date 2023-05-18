<?php

namespace App\Http\Controllers\account;

use App\Helpers\AccessChecker;
use App\Http\Controllers\Controller;
use App\Models\AccountType;
use App\Models\AccountTypeGroup;
use App\Models\Group;
use App\Models\UserGroup;
use App\Notifications\AccountTypeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class AccountTypeController extends Controller
{
   var $groups;
   public function __construct()
   {
      $this->groups = array();
   }

   public function create_account_type(Request $request)
   {
      $validated_data = $request->validate([
         "name" => "required|string",
         "description" => "required|string"
      ]);

      $company_id = $request->user()->company_id;
      $duplicate = AccountType::where([
         "name" => $request->name,
         "company_id" => $company_id
      ])->first();

      if ($duplicate) {
         throw ValidationException::withMessages(["name already exist"]);
      }

      AccountType::create([
         "name" => $request->name,
         "description" => $request->description,
         "company_id" => $company_id
      ]);

      $user_to_notify = AccessChecker::get_users_with_similar_access($request->user()->id);
      foreach ($user_to_notify as $key => $user) {
         Notification::send($user->users, new AccountTypeNotification("New account type created", 'New account type "' . $request->name . '"  created by ' . $request->user()->name));
      }

      return response()->json([
         'message' => 'Successfully saved'
      ], 200);
   }

   public function edit_account_type(Request $request)
   {
      $validated_data = $request->validate([
         "account_type_id" => "required|exists:account_types,id",
         "name" => "required|string",
         "description" => "required|string"
      ]);

      $company_id = $request->user()->company_id;
      $duplicate = AccountType::where([
         "name" => $request->name,
         "company_id" => $request->description
      ])->first();

      if ($duplicate) {
         if ($duplicate->id != $request->id)
            throw ValidationException::withMessages(["name already exist"]);
      }
      $account_type_original = AccountType::find($request->account_type_id);
      $account_type_update = AccountType::find($request->account_type_id);
      if ($account_type_update->company_id != $company_id) {
         throw ValidationException::withMessages(["Unauthorized"]);
      }
      $account_type_update->name = $request->name;
      $account_type_update->description = $request->description;
      $account_type_update->save();

      $user_to_notify = AccessChecker::get_users_with_similar_access($request->user()->id);
      foreach ($user_to_notify as $key => $user) {
         Notification::send($user->users, new AccountTypeNotification("Account type updated", 'Account type "' . $account_type_original->name . '" changed to "' . $account_type_update->name . '" updated by ' . $request->user()->name));
      }

      return response()->json([
         'message' => 'Successfully updated'
      ], 200);
   }

   public function get_account_type(Request $request)
   {
      $company_id = $request->user()->company_id;
      $user_id = $request->user()->id;
      $is_owner = $request->user()->is_owner;

      $this->groups = array();

      if (!$is_owner) {
         $this->groups = UserGroup::where("user_id", Auth::user()->id)->pluck('group_id');

         $account_types = AccountType::with("account_form", "contact_form", 'groups')->whereHas('groups', function ($query) {
            // foreach ($this->groups as $key => $group) {
            $query = $query->whereIn('group_id', $this->groups);

            return $query;
         })->get();

         return $account_types;
      } else {
         $account_types = AccountType::with("account_form", "contact_form", 'groups')->where("company_id", $company_id)->get();

         return $account_types;
      }
   }

   public function get_account_type_table()
   {
      $account_type_query = AccountType::where("company_id", Auth::user()->company_id)
         ->with("account_form", "contact_form", 'groups')->withCount("accounts", "contacts");
      if (!Auth::user()->is_owner) {
         $account_type_query->whereHas('groups', function ($query) {
            return UserGroup::where("user_id", Auth::user()->id)->pluck('group_id');
         });
      }
      return $account_type_query->paginate();
   }

   public function assign_account_to_group(Request $request)
   {
      $validated_data = $request->validate([
         "account_type_id" => "required|exists:account_types,id",
         "group_ids" => "required|array"
      ]);
      // return $request;
      $company_id = $request->user()->company_id;
      foreach ($request->group_ids as $key => $group_id) {
         $group = Group::find($group_id);
         if (!$group) {
            throw ValidationException::withMessages(["Invalid group id"]);
         } else {
            if ($group->company_id != $company_id) {
               throw ValidationException::withMessages(["Unauthorized"]);
            }
         }
         $duplicate_group = AccountTypeGroup::where([
            "accounttype_id" => $request->account_type_id,
            "group_id" => $group_id
         ])->first();
         if ($duplicate_group) {
            throw ValidationException::withMessages(["Account type is already associated to group" . $group->name]);
         }
      }

      $group_list = "";
      foreach ($request->group_ids as $key => $group_id) {
         AccountTypeGroup::create([
            "accounttype_id" => $request->account_type_id,
            "group_id" => $group_id,
         ]);
         $group = Group::find($group_id);
         if ($group_list = "") {
            $group_list = $group->name;
         } else {
            $group_list .= ", " . $group->name;
         }
      }


      $user_to_notify = AccessChecker::get_users_with_similar_access($request->user()->id);
      $account_type_original = AccountType::find($request->account_type_id);
      foreach ($user_to_notify as $key => $user) {
         Notification::send($user->users, new AccountTypeNotification("Account type associated to groups", 'Account type "' . $account_type_original->name . '" has been added to new groups "' . $group_list . '" added by ' . $request->user()->name));
      }

      return response()->json([
         'message' => 'Successfully added'
      ], 200);
   }

   public function remove_group_account_type(Request $request)
   {
      $validated_data = $request->validate([
         "account_type_id" => "required|exists:account_types,id",
         "group_id" => "required|exists:groups,id"
      ]);
      $company_id = $request->user()->company_id;
      $check_account_type = AccountType::find($request->account_type_id);
      $check_group = Group::find($request->group_id);
      if ($check_account_type->company_id != $company_id || $check_group->company_id != $company_id) {
         throw ValidationException::withMessages(["Unauthorized"]);
      }

      AccountTypeGroup::where([
         "accounttype_id" => $request->account_type_id,
         "group_id" => $request->group_id,
      ])->delete();

      $user_to_notify = AccessChecker::get_users_with_similar_access($request->user()->id);
      $account_type_original = AccountType::find($request->account_type_id);
      foreach ($user_to_notify as $key => $user) {
         Notification::send($user->users, new AccountTypeNotification("Account type associated to group removed", 'Account type "' . $check_account_type->name . '" has been removed from group "' . $check_group->name . '" removed by ' . $request->user()->name));
      }

      return response()->json([
         'message' => 'Successfully removed'
      ], 200);
   }



   /************************************************************* */
   private function where_group($user_id)
   {
      $where_array = "";
      $user_groups = UserGroup::where("user_id", $user_id)->get("group_id");
      return $user_groups;
      foreach ($user_groups as $key => $user_group) {
         if ($where_array == "") {
            $where_array = " (group_id=$user_group->group_id";
         } else {
            $where_array .= " OR group_id=$user_group->group_id";
         }
      }
      return $where_array . ")";
   }
}
