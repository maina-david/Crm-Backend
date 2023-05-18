<?php

namespace App\Helpers;

use App\Models\AccountType;

class AccountNumberGenratorHelper
{
    public static function generate_account_number($account_type_id)
    {
        $account_type = AccountType::with("account_number")->find($account_type_id);
        $generated_number = "";
        if ($account_type->account_number) {
            $perfix = $account_type->account_number->prefix;
            $has_number = $account_type->account_number->has_number;
            $has_character = $account_type->account_number->has_character;
            $separator = $account_type->account_number->separator;

            $generated_number = $perfix . $separator;
            if ($has_number && $has_character) {
                $generated_number .= self::generate_characters_numbers(8);
            } else if ($has_number) {
                $generated_number .= self::generate_characters_numbers(8);
            } else if ($has_character) {
                $generated_number .= self::generate_characters_numbers(8);
            }
        }
        return $generated_number;
    }

    public static function generate_numbers(int $length)
    {
        $pattern = "1234567890";
        $ID = $pattern[rand(0, strlen($pattern)-1)];
        for ($i = 1; $i < $length; $i++) {
            $ID .= $pattern[rand(0, strlen($pattern)-1)];
        }
        return $ID;
    }

    public static function generate_characters(int $length)
    {
        $pattern = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $ID = $pattern[rand(0, strlen($pattern)-1)];
        for ($i = 1; $i < $length; $i++) {
            $ID .= $pattern[rand(0, strlen($pattern)-1)];
        }
        return $ID;
    }

    public static function generate_characters_numbers(int $length)
    {
        $pattern = "1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $ID = $pattern[rand(0, strlen($pattern)-1)];
        for ($i = 1; $i < $length; $i++) {
            $ID .= $pattern[rand(0, strlen($pattern)-1)];
        }
        return $ID;
    }
}
