@component('mail::message')
Dear {{$name}}
# Please confirm this email

As per your request we have changed your email to this one. Please confirm this email using the OTP code bellow
<h2 style="background: #00466a;margin: 0 auto;width: max-content;padding: 0 10px;color: #fff;border-radius: 4px;">{{$link}}</h2>

Thanks,<br>
{{ config('app.name') }}
@endcomponent