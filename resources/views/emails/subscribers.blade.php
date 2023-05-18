@component('mail::message')
Dear {{$name}}
# Welcome to Callcenter

Welcome to our Callcenter system, you are a few clicks away from setting up your call center. Please click on the button and proceed to the rest of the setup.{{$link}}

<!-- <h2 style="background: #00466a;margin: 0 auto;width: max-content;padding: 0 10px;color: #fff;border-radius: 4px;">324457</h2> -->


@component('mail::button', ['url' => '{{$link}}'])
Continue
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent