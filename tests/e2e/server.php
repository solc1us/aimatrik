<?php

// Start manually: php -S 127.0.0.1:8001 -t public tests/e2e/server.php
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}
// Public assets need neither Laravel nor a database connection on every request.
// Use the original URI: Symfony can strip an existing file's script/base URL.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if (in_array($_SERVER['REQUEST_METHOD'] ?? '', ['GET', 'HEAD'], true) && str_starts_with($path, '/assets/')) {
    $assets = realpath(dirname(__DIR__, 2).'/public/assets');
    $file = realpath(dirname(__DIR__, 2).'/public'.$path);
    if ($assets && $file && is_file($file) && str_starts_with($file, $assets.DIRECTORY_SEPARATOR)
        && ! preg_match('/^(php\d*|phtml|phar)$/i', pathinfo($path, PATHINFO_EXTENSION))
        && ! preg_match('/^(php\d*|phtml|phar)$/i', pathinfo($file, PATHINFO_EXTENSION))) {
        return false;
    }
}
try {
    $context = require __DIR__.'/bootstrap.php';
    extract($context);
    $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
    $request = Illuminate\Http\Request::capture();
    if (rtrim(config('app.url'), '/') !== $request->getSchemeAndHttpHost()) {
        throw new RuntimeException('E2E APP_URL must match the server origin.');
    }
    if (str_starts_with($path, '/storage/')) {
        // Serve only the isolated test disk, never the development storage junction.
        $file = realpath($runtime.'/files/public/'.substr($path, 9));
        $allowed = realpath($runtime.'/files/public');
        if (! $file || ! $allowed || ! is_file($file) || ! str_starts_with($file, $allowed.DIRECTORY_SEPARATOR)) {
            http_response_code(404);
            exit;
        }
        $response = new Symfony\Component\HttpFoundation\BinaryFileResponse($file);
        if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf') {
            $response->headers->set('Content-Type', 'application/pdf');
            $response->prepare($request);
        }
        $response->send();
        exit;
    }
    $static = realpath($root.'/public'.$path);
    if (! str_starts_with($path, '/assets/') && $static && is_file($static)
        && str_starts_with($static, realpath($root.'/public').DIRECTORY_SEPARATOR)
        && strtolower(pathinfo($static, PATHINFO_EXTENSION)) !== 'php') {
        return false;
    }
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle($request);
    $response->headers->set('X-Aimatrik-E2E', $manifest['run']);
    $response->send();
    $kernel->terminate($request, $response);
} catch (Throwable $exception) {
    // Configuration/SQL exceptions can contain credentials: never echo them.
    http_response_code(503);
    echo 'E2E server unavailable: verify .env.testing, PostgreSQL identity, APP_URL and fixture setup.';
}
