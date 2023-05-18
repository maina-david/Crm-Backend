<?php

namespace App\Http\Controllers\user;

use App\Events\TestEvent;
use App\Helpers\AgentStatusChangedEventHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserDetailResource;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\Mail;
use App\Mail\Subscribe;
use App\Mail\UserDeactivatedMail;
use App\Models\AccessProfile;
use App\Models\Invitation;
use App\Models\OTPTable;
use App\Models\User;
use App\Models\UserAccessProfile;
use App\Rules\MatchOldPassword;
use App\Services\OTPService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    protected $user_service, $otp_service;
    public function __construct(UserService $user_service, OTPService $otp_service)
    {
        $this->user_service = $user_service;
        $this->otp_service = $otp_service;
    }


    /**
     * create_user create a new owner on the system
     * sends email with token link to confirm registration
     * 
     */
    public function create_user(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            // 'phone_number' => 'regex:/(01)[0-9]{9}/'
        ]);

        $user = $this->user_service->create_owner($validatedData["name"], $validatedData["email"], $request->phone_number, $validatedData["password"]);
        if ($user["type"] == "error")
            return response()->json([
                'message' => $user
            ], 422);

        return response()->json([
            'message' => 'created successfuly',
            'user' => $user["user"]
        ], 200);
    }


    /**
     * resend_signup_token resends email with token link to confirm registration
     * 
     */
    public function resend_signup_token(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'otp_type' => 'required'
        ]);

        $otp_sender = $this->otp_service->resend_otp($validatedData["user_id"], $validatedData["otp_type"]);

        return $otp_sender;

        if ($otp_sender) {
            return response()->json([
                'message' => 'sent successfully',
            ], 200);
        }
        return response()->json([
            'message' => 'Token doesn\'t exist',
        ], 422);
    }

    /**
     * confirm email
     */

    public function confirm_email(Request $request)
    {
        $validatedData = $request->validate([
            'otp_code' => 'required|exists:o_t_p_tables,OTP_code',
            // 'email' => 'required|email|exists:users,email'
        ]);

        $otp_data = OTPTable::where(["OTP_code" => $request->otp_code, "status" => "ACTIVE", "OTP_type" => "SIGNUP"])->first();
        if ($otp_data) {
            $otp_data->status = "USED";
            $user = User::find($otp_data->OTP_value);
            $user->email_verified_at = now();
            $user->save();
            $otp_data->save();
            $login_data = $this->user_service->login($user->email);
            return response()->json([
                'message' => 'successfully confirmed',
                "user" => $login_data
            ], 200);
        } else {
            return response()->json([
                'message' => 'Token doesn\'t exist',
            ], 422);
        }
    }

    /**
     * authorize users to use the system
     * @return - user data, authorization key and access 
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $user_checks = User::where("email", $credentials["email"])->first();
            if ($user_checks->company->active == FALSE) {
                return response([
                    "message" => "Your company is not active. Please contact the company's account administrator."
                ], 401);
            }
            if (!$user_checks->status == "ACTIVE") {
                return response([
                    "message" => "Your account has been deactivated, Please contact the administrator."
                ], 401);
            }
            if ($user_checks->is_locked) {
                return response([
                    "message" => "Pending password reset request, please check your email."
                ], 422);
            }
            if ($user_checks->email_verified_at == null) {
                return response([
                    "message" => "Pending email verification, please check your email."
                ], 422);
            }
            if (count($user_checks->tokens()->get()) > 0) {
                return response()->json([
                    'message' => 'Already logged in'
                ], 422);
            }

            $login_data = $this->user_service->login($credentials["email"]);

            $event_response = AgentStatusChangedEventHelper::notify_agent_status(Auth::user()->id);

            return response([
                "message" => "Successfully logged in",
                "user" => $login_data
            ], 200);
        }
        return response(["message" => "The provided credentials do not match our records."], 401);
    }

    public function new_session(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $user_checks = User::where("email", $credentials["email"])->first();
            if ($user_checks->company->active == FALSE) {
                return response([
                    "message" => "Your company is not active. Please contact the company's account administrator."
                ], 401);
            }
            if (!$user_checks->status == "ACTIVE") {
                return response([
                    "message" => "Your account has been deactivated, Please contact the administrator."
                ], 401);
            }
            if ($user_checks->is_locked) {
                return response([
                    "message" => "Pending password reset request, please check your email."
                ], 422);
            }
            if ($user_checks->email_verified_at == null) {
                return response([
                    "message" => "Pending email verification, please check your email."
                ], 422);
            }
            $is_agent = \App\Helpers\AccessChecker::check_if_agent($request->user()->id);
            if ($is_agent) {
                $this->user_service->agent_logout($request->user()->id);
            }
            $user_checks->tokens()->delete();
            $login_data = $this->user_service->login($credentials["email"]);
            $event_response = AgentStatusChangedEventHelper::notify_agent_status(Auth::user()->id);
            return response([
                "message" => "Successfully loggedin",
                "user" => $login_data
            ], 200);
        }
        return response(["message" => "The provided credentials do not match our records."], 401);
    }

    /**
     * remove session 
     */

    public function logout(Request $request)
    {
        $is_agent = \App\Helpers\AccessChecker::check_if_agent($request->user()->id);
        if ($is_agent) {
            $this->user_service->agent_logout($request->user()->id);
        }
        $user = User::find($request->user()->id);
        $user->is_loggedin = false;
        $user->save();
        $request->user()->currentAccessToken()->delete();
        $event_response = AgentStatusChangedEventHelper::notify_agent_status($user->id);

        return response(["message" => "Successfully logged out"], 200);
    }

    /**
     * 
     */
    public function change_password(Request $request)
    {
        $field_data = $request->validate([
            "password" => "required|string|min:8|confirmed",
            "current_password" => ['required', new MatchOldPassword]
        ]);

        $user = User::where("id", $request->user()->id)->first();
        if (!$user && !Hash::check($field_data["old_password"], $user->password)) {
            return response(["message" => "Bad user credential combination."], 401);
        } else {
            $user = User::where("id", $request->user()->id)->update(["password" => Hash::make($field_data["password"])]);
            return response(["message" => "password changed"], 200);
        }
    }

    /**
     * 
     */
    public function forget_password(Request $request)
    {
        $field_data = $request->validate([
            "email" => "required|email|exists:users,email",
        ]);
        $otp = $this->user_service->forget_password($field_data["email"]);
        return $otp;
    }

    /**
     * 
     */

    public function password_reset(Request $request)
    {
        $field_data = $request->validate([
            "token_code" => "required|exists:o_t_p_tables,OTP_code",
            "password" => "required|string|min:8|confirmed"
        ]);

        $otp_table_criteria["OTP_code"] = $field_data["token_code"];
        $otp_table_criteria["status"] = "ACTIVE";
        $otp_table_criteria["OTP_type"] = "FORGETPASSWORD";

        $token_table = OTPTable::where($otp_table_criteria)->first();

        if ($token_table) {
            $user_update["is_locked"] = 0;
            $user_update["password"] = Hash::make($field_data["password"]);
            $user_update["email_verified_at"] = now();
            OTPTable::where($otp_table_criteria)->update(["status" => "USED"]);
            User::where("id", $token_table->OTP_value)->update($user_update);
            return response()->json([
                'message' => 'successfully changed',
            ], 200);
        } else {
            return response()->json([
                'message' => 'Token doesn\'t exist',
            ], 422);
        }
    }

    public function all_users()
    {
        $request = Request();
        return User::with("company", "role_profile", 'groups')->where("company_id", $request->user()->company_id)->get();
    }

    public function activate_deactivate_user(Request $request)
    {
        $field_data = $request->validate([
            "user_id" => "required|exists:users,id",
        ]);
        $user = User::find($request->user_id);
        $user->status = ($user->status == "ACTIVE") ? "DEACTIVATED" : "ACTIVE";
        $user->save();

        if ($user->status == "DEACTIVATED") {
            Mail::to($user->email)->send(new UserDeactivatedMail($user, ["name" => $user->name]));
        }

        return response()->json([
            'message' => 'successfully changed',
            'user' => $user
        ], 200);
    }

    public function reset_user_password(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::find($request->user_id);
        $user->password = Hash::make($request->password);
        $user->save();
        return response()->json([
            'message' => 'successfully changed',
            'user' => $user
        ], 200);
    }

    public function get_current_access_rigts()
    {
        $request = Request();

        $user_access_profile = UserAccessProfile::where('user_id', $request->user()->id)->first();
        $access_right = null;
        if ($user_access_profile)
            $access_right = AccessProfile::where('role_profile_id', $user_access_profile->access_profile_id)->get();
        return ["access_right" => $access_right];
    }

    public function change_user_information(Request $request)
    {
        $user_id = $request->user()->id;
        $validatedData = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email|unique:users,email,' . $user_id,
            // 'email' => 'required|email|unique:invitations,email,ACTIVE',
            'phone_number' => 'regex:/(0)[0-9]{9}/'
        ]);

        $invitation_check = Invitation::where(["email" => $request->email, "status" => "ACTIVE"])->first();
        if ($invitation_check)
            throw ValidationException::withMessages(["Email already registered"]);
        $user_to_update = User::find($user_id);
        $user_to_update->name = $request->name;
        $user_to_update->email = $request->email;
        $user_to_update->phone_number = $request->phone_number;

        $new_user_data = $this->user_service->change_user_information($user_to_update);

        return response()->json([
            'message' => 'successfully changed',
            'user' => $new_user_data
        ], 200);
    }

    /**
     * It gets all the users from the database where the company_id is equal to the company_id of the
     * logged in user and then paginates the results
     * 
     * @return A collection of UserDetailResource
     */
    public function get_user_table(Request $request)
    {
        $user_query = User::where("company_id", Auth::user()->company_id);
        if ($request->user_name != null) {
            $user_query->Where("name", "like", "%$request->user_name%");
        }
        return $users = $user_query->paginate();
        return UserDetailResource::collection($users);
    }
}