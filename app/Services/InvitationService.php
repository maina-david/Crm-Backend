<?php

namespace App\Services;

use App\Mail\InvitationMail;
use App\Mail\InvitationRevokedMail;
use App\Models\Invitation;
use App\Models\OTPTable;
use App\Models\User;
use App\Notifications\UserInvitedNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public function send_invitation(Invitation $invitation)
    {
        $invite_saved = Invitation::create([
            "email" => $invitation->email,
            "group_id" => $invitation->group_id,
            "role_profile_id" => $invitation->role_profile_id,
            "invited_by" => $invitation->invited_by,
            "company_id" => $invitation->company_id,
            "status" => $invitation->status
        ]);
        $otp_service = new OTPService();
        $otp = $otp_service->generate_OTP("INVITEUSER", $invite_saved->id, $invite_saved->email);
        Mail::to($invitation->email)->send(new InvitationMail($invitation, $otp, ['otp' => $otp->otp_code]));
        Notification::send(User::find($invitation->invited_by),new UserInvitedNotification( $invite_saved->email));
        \App\Helpers\LogActivity::addToLog('user ' . $invitation->email . ' invited by ' . $invitation->user_id);
        return $invite_saved;
    }

    public function revoke_invitation($invitation_id)
    {
        $invitation = Invitation::find($invitation_id);
        if ($invitation->status == "ACTIVE") {
            Mail::to($invitation->email)->send(new InvitationRevokedMail($invitation->email));
            $invitation->status = "DEACTIVATED";
            $invite_saved = $invitation->save();
            \App\Helpers\LogActivity::addToLog('user ' . $invitation->email . ' invitation canceled by ' . $invitation->user_id);
            return $invite_saved;
        } else {
            throw ValidationException::withMessages(["the invitation has been already used or revoked"]);
        }
    }

    public function reactivate_invitation($invitation_id)
    {
        $invitation = Invitation::find($invitation_id);
        if ($invitation->status == "DEACTIVATED") {
            $invitation->status = "ACTIVE";
            $invite_saved = $invitation->save();
            $otp = OTPTable::where(["OTP_value" => $invitation_id, "OTP_type" => 'INVITEUSER'])->first();
            Mail::to($invitation->email)->send(new InvitationMail($invitation, $otp));
            \App\Helpers\LogActivity::addToLog('user ' . $invitation->email . ' Reinvited by ' . $invitation->user_id);
            return $invite_saved;
        } else {
            throw ValidationException::withMessages(["the invitation has been already used or revoked"]);
        }
    }

    public function resend_invitation($invitation_id)
    {
        $invitation = Invitation::find($invitation_id);
        if ($invitation->status == "ACTIVE") {
            $otp = OTPTable::where(["OTP_value" => $invitation_id, "OTP_type" => 'INVITEUSER'])->first();
            $mail = Mail::to($invitation->email)->send(new InvitationMail($invitation, $otp));
            \App\Helpers\LogActivity::addToLog('user ' . $invitation->email . ' Reinvited by ' . $invitation->user_id);
            return $mail;
        } else {
            throw ValidationException::withMessages(["the invitation has been already used or revoked"]);
        }
    }
}
