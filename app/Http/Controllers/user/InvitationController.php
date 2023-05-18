<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Models\OTPTable;
use App\Models\User;
use App\Services\AccessRightService;
use App\Services\GroupService;
use App\Services\InvitationService;
use App\Services\UserService;
use Auth;
use Illuminate\Http\Request;

class InvitationController extends Controller
{

    /**
     * It takes an array of emails, checks if they exist in the database, if they don't, it sends an
     * invitation to them
     * 
     * @param Request request The request object.
     * 
     * @return The response is a JSON object with the following properties:
     */
    public function invite_users(Request $request)
    {
        $validated_data = $request->validate([
            "group_id" => "exists:groups,id",
            "role_profile_id" => "exists:role_profiles,id"
        ]);
        $group_id = $request->group_id;
        $role_profile_id = $request->role_profile_id;

        $company_id = $request->user()->company_id;
        $error = array();
        $has_error = false;

        $invitation_service = new InvitationService();
        foreach ($request->emails as $key => $email) {
            $existing_user = User::where("email", $email)->first();
            $existing_invitation = Invitation::where("email", $email)->first();
            if (!$existing_invitation && !$existing_user) {
                $invitation_data = new Invitation();
                $invitation_data['email'] = $email;
                $invitation_data['group_id'] = $group_id;
                $invitation_data['role_profile_id'] = $role_profile_id;
                $invitation_data['company_id'] = $company_id;
                $invitation_data['invited_by'] = $request->user()->id;
                $invitation_data['status'] = "ACTIVE";

                $invitaion = $invitation_service->send_invitation($invitation_data);
            } else {
                $has_error = true;
                $error[$key]["data"] = $email;
                $error[$key]["message"] = "The email exist in the system please use another email";
            }
        }
        return response()->json([
            'message' => 'created successfully',
            'has_eror' => $has_error,
            "error_message" => $error
        ], 200);
    }

    /**
     * It gets all the invitations for a company
     */
    public function get_all_invitations()
    {
        $company_id = Auth::user()->company_id;
        $invitaion = Invitation::with(["invited_by", "group", "role"])->where("company_id", $company_id)->get();
        return $invitaion;
    }

   /**
    * It gets all the invitations for a company and returns them as a collection of InvitationResource
    * 
    * @return A collection of InvitationResource
    */
    public function get_all_invitations_table()
    {
        $company_id = Auth::user()->company_id;
        $invitaion = Invitation::with(["invited_by", "group", "role"])->where("company_id", $company_id)->paginate();
        return InvitationResource::collection($invitaion);
    }

    public function revoke_invitation(Request $request)
    {
        $validated_data = $request->validate([
            "invitation_id" => "exists:invitations,id"
        ]);

        $invitation_service = new InvitationService();
        $invitaion = $invitation_service->revoke_invitation($request->invitation_id);
        return response()->json([
            'message' => 'successfully revoked',
        ], 200);
    }



    public function reactivate_invite(Request $request)
    {
        $validated_data = $request->validate([
            "invitation_id" => "exists:invitations,id"
        ]);

        $invitation_service = new InvitationService();
        $invitaion = $invitation_service->reactivate_invitation($request->invitation_id);
        return response()->json([
            'message' => 'successfully reactivated',
        ], 200);
    }

    public function resend_invitation(Request $request)
    {
        // return $request;
        $validated_data = $request->validate([
            "invitation_id" => "required|exists:invitations,id"
        ]);

        $invitation_service = new InvitationService();
        $invitaion = $invitation_service->resend_invitation($request->invitation_id);
        return response()->json([
            'message' => 'Invitation sent',
        ], 200);
    }

    public function accept_invitation(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'otp_code' => 'required|string|exists:o_t_p_tables,OTP_code',
            'password' => 'required|string|min:8|confirmed',
            // 'phone' => 'regex:/(0)[0-9]{9}/|unique:users,phone_number'
        ]);

        $chek_otp = OTPTable::where(["otp_code" => $request->otp_code, "status" => "ACTIVE", "OTP_type" => "INVITEUSER"])->first();
        if ($chek_otp) {
            $get_invitation = Invitation::where(["id" => $chek_otp->OTP_value, "status" => "ACTIVE"])->first();
            if ($get_invitation) {
                $user_service = new UserService();
                $user_created = $user_service->signup_user_with_otp($request->name, $get_invitation->email, $request->phone, $request->password, $get_invitation->company_id);

                if ($user_created["type"]=="success") {
                    $user = $user_created["user"];
                    $otp_to_update = OTPTable::find($chek_otp->id);
                    $otp_to_update->status = "USED";
                    $otp_to_update->save();
                    $invitation_to_update = Invitation::find($get_invitation->id);
                    $invitation_to_update->status = "ACCEPTED";
                    $invitation_to_update->accepted_at = now();
                    $invitation_to_update->save();
                    if ($get_invitation->group_id != null) {
                        $group_service = new GroupService();
                        $group_service->assign_group_user($user->id, $get_invitation->group_id, $get_invitation->company_id);
                    }
                    if ($get_invitation->role_profile_id != null) {
                        $access_service = new AccessRightService();
                        $access_service->assign_user_access_profile([
                            "user_id" => $user->id,
                            "access_profile_id" => $get_invitation->role_profile_id,
                            "company_id" => $get_invitation->company_id
                        ]);
                    }
                    $login = $user_service->login($user->email);
                    return response([
                        "message" => "Successfully registered",
                        "user" => $login
                    ], 200);
                } else {
                    return $user_created;
                }
            } else {
                return response()->json([
                    'message' => 'Invalid otp code',
                ], 422);
            }
        } else {
            return response()->json([
                'message' => 'Invalid otp code',
            ], 422);
        }
    }
}
