@component('mail::message')
Dear {{$name}}
# You are ready to use the system

You have completed registering to use our system, now you are ready to use the system.

Thanks,<br>
{{ config('app.name') }}
@endcomponent