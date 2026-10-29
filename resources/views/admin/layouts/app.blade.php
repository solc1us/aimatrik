<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="AIMATRIK - AI Maturity &amp; Governance Assessment">
    <meta name="author" content="AIMATRIK Team">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">

    <title>AIMATRIK - @yield('title')</title>

    @include('admin.layouts._css')
</head>

<body>
    <div class="berhasil" data-berhasil="{{ ucfirst(strtolower(Session::get('success'))) }}"></div>
    <div class="gagal" data-gagal="{{ ucfirst(strtolower(Session::get('error'))) }}"></div>
    <div id="app">
        @include('admin.layouts._sidebar')
        <div id="main" class="layout-navbar navbar-fixed">
            <header class="mb-3">
                @include('admin.layouts._navbar')
            </header>

            <div id="main-content">
                @yield('main')
            </div>
        </div>
    </div>

    @include('admin.layouts._js')
</body>

</html>
