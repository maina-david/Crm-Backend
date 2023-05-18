<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketRequest extends FormRequest
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
            'account_id' => 'required',
            'ticket_priority_id' => 'required|exists:ticket_priorities,id',
            'created_from' => 'required',
            'ticket_escallation_level_id' => 'required|exists:ticket_escallation_levels,id',
            'status' => 'nullable'
        ];
    }

    /**
     * Return custom messages to Ticket requests.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'ticket_priority_id.required' => 'Ticket priority is required!',
            'ticket_priority_id.exists' => 'Ticket priority should exist!',
            'assigned_to.required' => 'Ticket should be assigned to a user!',
            'assigned_to.exists' => 'Assigned user must be existing!',
            'ticket_escallation_level_id.required' => 'Ticket escallation level is required',
            'ticket_escallation_level_id.exists' => 'Ticket escallation level should exist'
        ];
    }
}