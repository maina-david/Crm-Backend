<?php

namespace App\Services;

use App\Mail\ForgetPasswordMail;
use App\Mail\Signup;
use App\Models\OTPTable;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class OTPService
{
    /**
     * generate_OTP - will generate
     * @otp_type - the type of OTP we generate
     * @otp_value - the thing to check for when validating otp
     * Return- OTPTable object 
     */
    public function generate_OTP($otp_type, $otp_value, $send_address)
    {
        $expire_date = now()->addMinute(30);
        $is_duplicate_otp = true;
        $generated_opt = "";
        while ($is_duplicate_otp) {
            $generated_opt = rand(10000000, 99999999);
            $duplicate = OTPTable::where("otp_code", $generated_opt)->first();
            if (!$duplicate)
                $is_duplicate_otp = false;
        }
        $otpTable = OTPTable::create([
            'OTP_type' => $otp_type,
            'OTP_code' => $generated_opt,
            'OTP_value' => $otp_value,
            'expires_at' => $expire_date,
            'status' => "ACTIVE",
        ]);
        return $otpTable;
    }
    /**
     * @resend_otp - will look for valid OTP and send emails back
     * @otp_type - the type of OTP 
     * @otp_value - the check value 
     * 
     * @Returns -boolean
     */

    public function resend_otp($otp_value, $otp_type)
    {
        $otp_check["OTP_value"] = $otp_value;
        $otp_check["OTP_type"] = $otp_type;
        $otp_check["status"] = "ACTIVE";

        $otp = OTPTable::where($otp_check)->first();
        // return $otp;
        if ($otp) {
            if ($otp_type == "SIGNUP") {
                $user = User::where("id", $otp_value)->first();
                $mail = $mail = Mail::to($user->email)->send(new Signup($user, $otp, ["name" => $user->name, "link" => env('FRONT_END_URL') . $otp->otp_code]));
                return $mail;
            }else if ($otp_type == "FORGETPASSWORD") {
                $user = User::where("id", $otp_value)->first();
                $mail = $mail = Mail::to($user->email)->send(new ForgetPasswordMail($user, $otp, ["name" => $user->name, "link" => env('FRONT_END_URL') . $otp->otp_code]));
                return $mail;
            }
        } else {
            return false;
        }
    }
}
