@component('mail::message')
Dear {{$name}}
# Account deactivated

Your account has been deactivated, if it is by mistake please contact the admin.

Thanks,<br>
{{ config('app.name') }}
@endcomponent