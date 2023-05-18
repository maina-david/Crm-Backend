<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class HelperService
{
    public function format_phone_number($phone_number, $countrycode)
    {
        $phone = preg_replace('/\D+/', '', $phone_number);
        $formattednumber = '';
        $countrycode = $countrycode;
        $is_phonenumber_valid = true;
        if (preg_match('/^[0-9]{9}+$/', $phone)) {
            $formattednumber = $countrycode . $phone;
        } elseif (preg_match('/^[0-9]{10}+$/', $phone)) {
            $phone = substr($phone, 1);
            $formattednumber = $countrycode . $phone;
        } elseif (preg_match('/^[0-9]{12}+$/', $phone)) {
            $formattednumber = '+' . $phone;
        } else {
            $is_phonenumber_valid = false;
            throw ValidationException::withMessages(["Invalid phonenumber format"]);
        }
        return $formattednumber;
    }
}
