<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketReminderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'ticket_id' => 'required|exists:tickets,id',
            'reminder_type' => 'required|exists:ticket_reminder_types,name',
            'reminder_date' => 'date_format:Y/m/d H:i:s'
        ];
    }

    /**
     * Return custom validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'ticket_id.required' => 'The ticket is required',
            'ticket_id.exists' => 'Ticket should be existing in the system',
            'reminder_type.required' => 'Set the reminder type',
            'reminder_type.exists' => 'Reminder type should be among the pre-defined types',
            'reminder_date.date_format' => 'Reminder date should be in the required format'
        ];
    }
}