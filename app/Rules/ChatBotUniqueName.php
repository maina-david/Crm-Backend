<?php

namespace App\Rules;

use App\Models\ChatBot;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ChatBotUniqueName implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $chatBot = ChatBot::where([
            'name' => $value,
            'company_id' => Auth::user()->company_id
        ])->first();

        return $chatBot;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be unique in your company!';
    }
}