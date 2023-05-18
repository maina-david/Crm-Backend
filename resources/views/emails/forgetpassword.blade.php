@component('mail::message')
Dear {{$name}}
# Reset password 

You requested for password reset, click on the link below. {{$link}}
<!-- <h2 style="background: #00466a;margin: 0 auto;width: max-content;padding: 0 10px;color: #fff;border-radius: 4px;">324457</h2> -->


@component('mail::button', ['url' => '{{$link}}'])
Continue
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent