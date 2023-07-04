<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>
</head>

<body class="antialiased">
    <h1>Hello {{ $name }}, Your otp is {{ $otp }} and expired at {{ $expired }}</h1>
</body>

</html>
