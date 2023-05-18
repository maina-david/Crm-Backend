@component('mail::message')
Dear {{$email}}
# Invitation to callcenter system

You have been invited to Callcenter system. To accept the invitaion please click on the link bellow

https://goexperience.goipcloud.co.ke/acceptInvite

when asked plese use this code to signup to the system.

<h2 style="background: #00466a;margin: 0 auto;width: max-content;padding: 0 10px;color: #fff;border-radius: 4px;">{{$link}}</h2>


Thanks,<br>
{{ config('app.name') }}
@endcomponent