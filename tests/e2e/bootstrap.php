<?php

// Required only by the manual E2E fixture command and E2E HTTP router.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
if (! is_file($root.'/.env.testing')) {
    throw new RuntimeException('Create the ignored .env.testing first.');
}
Dotenv\Dotenv::createImmutable($root, '.env.testing')->load();
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_DATABASE' => 'aimatrik_e2e', 'PW_E2E' => '1'] as $key => $expected) {
    if (Illuminate\Support\Env::get($key) !== $expected) {
        throw new RuntimeException('E2E environment guard rejected configuration.');
    }
}
if (Illuminate\Support\Env::get('DB_URL') || ! Illuminate\Support\Env::get('APP_KEY')) {
    throw new RuntimeException('E2E requires an APP_KEY and an empty DB_URL.');
}
$runtime = $root.'/node_modules/.cache/aimatrik-e2e';
foreach (['APP_CONFIG_CACHE' => 'node_modules/.cache/aimatrik-e2e/config.php', 'APP_ROUTES_CACHE' => 'node_modules/.cache/aimatrik-e2e/routes.php', 'TELESCOPE_ENABLED' => 'false', 'PULSE_ENABLED' => 'false'] as $key => $value) {
    $_ENV[$key] = $_SERVER[$key] = $value;
    putenv($key.'='.$value);
}
foreach (['', '/sessions', '/views', '/files'] as $directory) {
    if (! is_dir($runtime.$directory)) {
        mkdir($runtime.$directory, 0700, true);
    }
}
$app = require $root.'/bootstrap/app.php';
$app->loadEnvironmentFrom('.env.testing');
if ($app->configurationIsCached()) {
    throw new RuntimeException('E2E refuses cached configuration.');
}
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing') || DB::connection()->getDriverName() !== 'pgsql'
    || DB::connection()->getDatabaseName() !== 'aimatrik_e2e') {
    throw new RuntimeException('E2E database guard rejected configuration.');
}
if (DB::selectOne('select current_database() as name')->name !== 'aimatrik_e2e') {
    throw new RuntimeException('E2E actual database identity mismatch.');
}
config([
    'app.debug' => false,
    'session.driver' => 'file', 'session.files' => $runtime.'/sessions', 'session.cookie' => 'aimatrik_e2e_session',
    'cache.default' => 'array', 'queue.default' => 'sync', 'mail.default' => 'array',
    'view.compiled' => $runtime.'/views',
    'filesystems.default' => 'e2e',
    'filesystems.disks.e2e' => ['driver' => 'local', 'root' => $runtime.'/files', 'throw' => true],
]);
$manifestPath = $runtime.'/fixtures.json';

return compact('app', 'root', 'runtime', 'manifestPath');
