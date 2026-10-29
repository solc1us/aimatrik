<?php

// Run with: php tests/security-hardening.php (no Laravel dependencies or database needed).
$root = dirname(__DIR__);
$environment = [];

function env($key, $default = null)
{
    global $environment;

    return $environment[$key] ?? $default;
}

foreach ([
    [[], false],
    [['APP_DEBUG' => true], false],
    [['APP_ENV' => 'production', 'APP_DEBUG' => true], false],
    [['APP_ENV' => 'production', 'APP_DEBUG' => false], false],
    [['APP_ENV' => 'local', 'APP_DEBUG' => true], true],
    [['APP_ENV' => 'local', 'APP_DEBUG' => false], false],
] as [$environment, $expected]) {
    $config = require $root.'/config/app.php';
    if ($config['debug'] !== $expected) {
        fwrite(STDERR, "FAIL: debug configuration\n");
        exit(1);
    }
}

$directory = $root.'/public/assets/extensions/ckeditor/filemanager/';
$probe = <<<'PHP'
$_GET['akey'] = 'invalid-test-key';
$_SESSION['RF']['verify'] = 'RESPONSIVEfilemanager';
register_shutdown_function(function () {
    if (http_response_code() !== 404 || session_status() === PHP_SESSION_ACTIVE) {
        exit(1);
    }
});
require $argv[1];
PHP;

foreach ([
    'dialog.php', 'upload.php', 'execute.php', 'ajax_calls.php', 'force_download.php',
    'config/config.php', 'uploader/index.php', 'uploader/jupload.php', 'uploader/success.php',
] as $endpoint) {
    $process = proc_open(
        [PHP_BINARY, '-d', 'display_errors=1', '-r', $probe, $directory.$endpoint],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        dirname($directory.$endpoint),
    );
    if (! is_resource($process)) {
        throw new RuntimeException('Could not start PHP endpoint probe.');
    }
    $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0 || $output !== '') {
        // Never print endpoint output: a regression could expose configuration.
        fwrite(STDERR, "FAIL: legacy endpoint $endpoint\n");
        exit(1);
    }
}

echo "PASS: 6 debug configurations; 9 legacy endpoints denied before session startup.\n";
