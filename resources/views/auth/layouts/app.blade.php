<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="AIMATRIK Login App">
    <meta name="author" content="AIMATRIK Team">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>AIMATRIK - @yield('title')</title>

    @include('auth.layouts._css')
    @yield('customJs')
</head>

<body>
    <div class="berhasil" data-berhasil="{{ ucWords(Session::get('success')) ?? '' }}"></div>
    <div class="gagal" data-gagal="{{ ucWords(Session::get('error')) ?? '' }}"></div>
    <div id="auth">
        @yield('content')
    </div>
    @include('auth.layouts._js')
</body>

</html>
