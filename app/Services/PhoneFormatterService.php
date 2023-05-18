<?php

namespace App\Services;

class PhoneFormatterService
{
    public static function format_phone($phone)
    {
        $phone = preg_replace('/\D+/', '', $phone);
        $formattednumber = '';
        if (preg_match('/^[0-9]{9}+$/', $phone)) {
            $formattednumber = "254" . $phone;
        } elseif (preg_match('/^[0-9]{10}+$/', $phone)) {
            $phone = substr($phone, 1);
            $formattednumber = "254" . $phone;
        } else if (preg_match('/^[0-9]{12}+$/', $phone)) {
            $formattednumber = $phone;
        } else if (preg_match('/^[0-9]{15}+$/', $phone)) {
            $phone = substr($phone, 3);
            $formattednumber = $phone;
        }else if(preg_match('/^[0-9]{4}+$/', $phone)){
            $formattednumber = $phone;
        }else if(preg_match('/^[0-9]{5}+$/', $phone)){
            $formattednumber = $phone;
        }
        return $formattednumber;
    }
}
