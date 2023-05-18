@component('mail::message')
Dear {{$name}}
# New number allocated


As per your request, we have allocated the phone number.

<h2 style="background: #00466a;margin: 0 auto;width: max-content;padding: 0 10px;color: #fff;border-radius: 4px;">{{$phone_number}}</h2>


Thanks,<br>
{{ config('app.name') }}
@endcomponent