<?php

namespace App\Services;

use App\Mail\ForgetPasswordMail;
use App\Mail\RegistrationCompletedMail;
use App\Mail\Signup;
use App\Mail\UserEmailChangedMail;
use App\Models\AccessProfile;
use App\Models\AccessRight;
use App\Models\ActiveAgentQueue;
use App\Models\AgentSessionLog;
use App\Models\AgentStatus;
use App\Models\OTPTable;
use App\Models\RoleProfile;
use App\Models\User;
use App\Models\UserAccessProfile;
use App\Models\UserQueue;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class UserService
{

    /***
     * create_owner - will create a user with owner type
     * $name: name of the user
     * @email: email address of the user to create
     * @phone_number: phone number of the user to create
     * @password: password of the user to create
     */
    public function create_owner($name, $email, $phone_number, $password)
    {
        $user = null;
        $optTable = null;
        try {
            $user = User::create([
                "name" => $name,
                "email" => $email,
                "phone_number" => $phone_number,
                "password" => Hash::make($password),
                "status" => "ACTIVE",
                "is_locked" => false,
                "is_owner" => true
            ]);

            $expire_date = now()->addMinute(30);
            $is_duplicate_otp = true;
            $generated_opt = "";
            while ($is_duplicate_otp) {
                $generated_opt = rand(10000000, 99999999);
                $duplicate = OTPTable::where("otp_code", $generated_opt)->first();
                if (!$duplicate)
                    $is_duplicate_otp = false;
            }
            $optTable = OTPTable::create([
                'OTP_type' => "SIGNUP",
                'OTP_code' => $generated_opt,
                'OTP_value' => $user->id,
                'expires_at' => $expire_date,
                'status' => "ACTIVE",
            ]);
            Mail::to($user->email)->send(new Signup($user, $optTable, ["name" => $user->name, "link" => env('FRONT_END_URL') . $optTable->otp_code]));
        } catch (\Throwable $th) {
            if ($user) $user->delete();
            if ($optTable) $optTable->delete();
            $error = "";
            if ($th->errorInfo) {
                $error = $th->errorInfo[2];
            }
            return ["type" => "error", "message" => $error];
        }
        return ["type" => "success", "user" => $user];
    }

    public function signup_user_with_otp($name, $email, $phone_number, $password, $company_id)
    {
        $user = null;
        try {
            $user = User::create([
                "name" => $name,
                "email" => $email,
                "phone_number" => $phone_number,
                "password" => Hash::make($password),
                "status" => "ACTIVE",
                "email_verified_at" => now(),
                "is_locked" => false,
                "is_owner" => false,
                "company_id" => $company_id
            ]);
            Mail::to($user->email)->send(new RegistrationCompletedMail($user, ["name" => $user->name]));
            // return $user;
            return ["type" => "success", "user" => $user];
        } catch (\Throwable $th) {
            if ($user) $user->delete();
            return ["type" => "error", "message" => "something went wrong", $th];
        }
        return ["type" => "success", "user" => $user];
    }

    public function login($email)
    {
        $tokenable_user = User::where('email', $email)->first();
        $token = $tokenable_user->createToken('External', ['role:customer']);
        $user = User::with(['company', 'groups', 'role_profile', 'sip'])->find($tokenable_user->id);
        // $user_update=User::find();
        $user->is_loggedin = true;
        $user->save();
        $user_access_profile = UserAccessProfile::where('user_id', $tokenable_user->id)->first();
        $access_right = null;
        if ($user_access_profile)
            $access_right = AccessProfile::where('role_profile_id', $user_access_profile->access_profile_id)->get();
        $user->access_rights = $access_right;
        $login_data = ["user" => $user, "token" => $token->plainTextToken];
        $is_agent = \App\Helpers\AccessChecker::check_if_agent($user->id);
        if ($is_agent)
            $this->agent_login($user);
        return $login_data;
    }

    public function agent_login(User $user)
    {
        $agent_status = AgentStatus::where(["user_id" => $user->id, "date" => date('Y-m-d')])->first();
        if ($agent_status) {
            $agent_status_update = AgentStatus::find($agent_status->id);
            $agent_status_update->sip_status = "LOGEDIN";
            $agent_status_update->call_status = "LOGEDIN";
            $agent_status_update->save();
        } else {
            $agent_status =  AgentStatus::create([
                "user_id" => $user->id,
                "date" => now(),
                "logged_in_at" => now(),
                'sip_status' => "LOGEDIN",
                "call_status" => "ONLINE",
                "online_time" => 0,
                "break_time" => 0
            ]);
        }

        ////login to queues
        $user_queue = User::with('queue', 'sip')->find($user->id);
        foreach ($user_queue->queue as $key => $queue) {
            ActiveAgentQueue::create([
                "queue_id" => $queue->id,
                "sip_id" => $user->sip->sip_id,
                "user_id" => $user->id,
                "last_call_hung_up_at" => now(),
                "status" => "ONLINE",
                "sip_status" => "ONLINE",
                "penality" => ($agent_status->current_penality == null) ? 0 : $agent_status->current_penality,
                "company_id" => $user->company_id
            ]);
        }

        AgentSessionLog::create([
            "user_id" => $user->id,
            "attribute_type" => "LOGIN",
            "start_time" => now()
        ]);
    }

    public function agent_logout($user_id)
    {
        $agent_status = AgentStatus::where(["user_id" => $user_id, "date" => date("Y-m-d")])->first();
        if ($agent_status) {
            $login_time_attribute = AgentSessionLog::where([
                "user_id" => $user_id,
                "attribute_type" => "LOGIN",
                "end_time" => null
            ])->first();

            $timeFirst  = strtotime($login_time_attribute->start_time);
            $timeSecond = strtotime(now());
            $logged_in_for = $timeSecond - $timeFirst;
            $total_logged_time = $agent_status->online_time + $logged_in_for;
            AgentSessionLog::where("id", $login_time_attribute->id)->update(["end_time" => now()]);
            $break_time = 0;
            $break_attributes = AgentSessionLog::where([
                "attribute_type" => "BREAK",
                "user_id" => $user_id,
            ])
                ->whereDate('start_time', '<=', $login_time_attribute->start_time)->get();
            foreach ($break_attributes as $key => $break_attribute) {
                if ($break_attribute->end_time == null) {
                    AgentSessionLog::where("id", $break_attribute->id)->update(["end_time" => now()]);
                }
                $timeFirst  = strtotime($break_attribute->start_time);
                $timeSecond = ($break_attribute->end_time == null) ? strtotime(now()) : strtotime($break_attribute->end_time);
                $break_time += $timeSecond - $timeFirst;
            }

            AgentStatus::where("id", $agent_status->id)->update([
                "logged_out_at" => now(),
                "online_time" => $total_logged_time,
                "break_time" => $break_time,
                "sip_status" => "LOGEDOUT",
                "call_status" => "LOGEDOUT"
            ]);
            ActiveAgentQueue::where("user_id", $user_id)->delete();
        }
    }

    public function forget_password($email)
    {
        $user = User::where(["email" => $email, "status" => "ACTIVE"])->first();
        if ($user) {
            $otp_service = new OTPService();
            $otp = $otp_service->generate_OTP("FORGETPASSWORD", $user->id, $user->email);
            $user->is_locked = 1;
            $user->save();
            // User::where("id", $user->id)->update(["is_locked" => 1]);

            if ($otp) {
                return Mail::to($user->email)->send(new ForgetPasswordMail($user, $otp, ["name" => $user->name, "link" => env('FRONT_END_URL') . $otp->otp_code]));
            }
        }
    }

    public function reset_password($password)
    {
    }

    public function change_user_information(User $user)
    {
        $original_user = User::find($user->id);
        if ($original_user->email != $user->email) {
            $otp_service = new OTPService();
            $otp = $otp_service->generate_OTP("SIGNUP", $user->id, $user->email);
            $original_user->email_verified_at = null;
            $original_user->email = $user->email;
            Mail::to($user->email)->send(new UserEmailChangedMail($user, $otp, ["name" => $user->name, "link" => env('FRONT_END_URL') . $otp->otp_code]));
        }
        $original_user->name = $user->name;
        $original_user->phone_number = $user->phone_number;
        $original_user->save();
        return $original_user;
    }
}