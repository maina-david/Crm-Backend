@component('mail::message')
Dear {{$email}}
# Invitation to canceled callcenter system

Your invitation to the call center system has been canceled. if it is by mistake please contact the system admins.


Thanks,<br>
{{ config('app.name') }}
@endcomponent