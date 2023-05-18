<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $template->name }}</title>
    <style type="text/css">
        /* Add your custom styles here */
    </style>
</head>

<body>
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="600">
        <tr>
            <td align="center" bgcolor="#ffffff" style="padding: 40px 0 30px 0;">
                <img src="{{ Auth::user()->company->logo }}" alt="{{ Auth::user()->company->name }}">
            </td>
        </tr>
        <tr>
            <td bgcolor="#f7f7f7" style="padding: 40px 30px 40px 30px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td>
                            <h2>Dear {{ $name }},</h2>
                            <p>{{ $message }}</p>
                            <p>Regards,<br>{{ Auth::user()->name }}<br>
                                {{Auth::user()->company->name }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td bgcolor="#f7f7f7" style="padding: 30px 30px 30px 30px;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td width="75%">
                            &copy; {{ Auth::user()->company->name }}, {{ date('Y') }}<br>
                            {{ Auth::user()->company->company_address->city }}<br>
                            {{ Auth::user()->company->company_address->country_code }}
                        </td>
                        <td align="right">
                            <table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <a href="https://youtube.com/">
                                            <img src="https://goipspace.fra1.cdn.digitaloceanspaces.com/call_center/icons/icons8-youtube-48.png"
                                                alt="Youtube" width="38" height="38" style="display: block;">
                                        </a>
                                    </td>
                                    <td style="font-size: 0; line-height: 0;" width="20">&nbsp;</td>
                                    <td>
                                        <a href="https://instagram.com/">
                                            <img src="https://goipspace.fra1.cdn.digitaloceanspaces.com/call_center/icons/icons8-instagram-48.png"
                                                alt="Instagram" width="38" height="38" style="display: block;">
                                        </a>
                                    </td>
                                    <td>
                                        <a href="https://twitter.com/">
                                            <img src="https://goipspace.fra1.cdn.digitaloceanspaces.com/call_center/icons/icons8-twitter-48.png"
                                                alt="Twitter" width="38" height="38" style="display: block;">
                                        </a>
                                    </td>
                                    <td style="font-size: 0; line-height: 0;" width="20">&nbsp;</td>
                                    <td>
                                        <a href="https://facebook.com/">
                                            <img src="https://goipspace.fra1.cdn.digitaloceanspaces.com/call_center/icons/icons8-facebook-48.png"
                                                alt="Facebook" width="38" height="38" style="display: block;">
                                        </a>
                                    </td>
                                    <td style="font-size: 0; line-height: 0;" width="20">&nbsp;</td>
                                    <td>
                                        <a href="https://whatsapp.com/">
                                            <img src="https://goipspace.fra1.cdn.digitaloceanspaces.com/call_center/icons/icons8-whatsapp-48.png"
                                                alt="WhatsApp" width="38" height="38" style="display: block;">
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>