<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

// Refuse to serve web requests in dev/debug unless explicitly allowed: a public
// dev deployment exposes the profiler, which is how this server was compromised.
if (('prod' !== $_SERVER['APP_ENV'] || $_SERVER['APP_DEBUG']) && empty($_SERVER['APP_ALLOW_DEV_WEB'])) {
    error_log(sprintf('Refusing web request: APP_ENV=%s APP_DEBUG=%s without APP_ALLOW_DEV_WEB', $_SERVER['APP_ENV'], $_SERVER['APP_DEBUG']));
    http_response_code(503);
    exit;
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
