@component('mail::message')
Hello {{ $name }},

You have been registered successfully to the Callcenter internal CRM.

Your password is {{ $password }}.

Please do not share this email with anyone and update your password as soon as you login.

@component('mail::button', ['url' => '', 'color' => 'success'])
Login
@endcomponent

Regards,<br>
{{ config('app.name') }}
@endcomponent