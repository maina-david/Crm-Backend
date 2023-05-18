<html>

<head>
    <title>{{ $template->name }}</title>
</head>

<body>
    {!! preg_replace_callback('/\{\{(.*?)\}\}/', function($match) use ($data) {
    return $data[$match[1]] ?? '';
    }, $template->body) !!}
</body>

</html>