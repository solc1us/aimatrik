<?php

// Start manually: php -S 127.0.0.1:8001 -t public tests/e2e/server.php
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}
try {
    $context = require __DIR__.'/bootstrap.php';
    extract($context);
    $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
    $request = Illuminate\Http\Request::capture();
    if (rtrim(config('app.url'), '/') !== $request->getSchemeAndHttpHost()) {
        throw new RuntimeException('E2E APP_URL must match the server origin.');
    }
    // For an existing static file, Symfony may strip its script/base URL to '/'.
    // Classify static requests using the original URI, before dispatching to Laravel.
    $path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    if (str_starts_with($path, '/storage/')) {
        // Serve only the isolated test disk, never the development storage junction.
        $file = realpath($runtime.'/files/public/'.substr($path, 9));
        $allowed = realpath($runtime.'/files/public');
        if (! $file || ! $allowed || ! is_file($file) || ! str_starts_with($file, $allowed.DIRECTORY_SEPARATOR)) {
            http_response_code(404);
            exit;
        }
        (new Symfony\Component\HttpFoundation\BinaryFileResponse($file))->send();
        exit;
    }
    $static = realpath($root.'/public'.$path);
    if ($static && is_file($static) && str_starts_with($static, realpath($root.'/public').DIRECTORY_SEPARATOR)
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
