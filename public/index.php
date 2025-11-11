<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

error_reporting(E_ALL);
ini_set('display_errors','1');


$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($uri, '/');

// TEMP DEBUG:
echo "<!-- uri={$uri} | path={$path} -->";

switch ($path) {
    case '':
        (new App\Controllers\HomeController)();
        break;

    case '/armory':
        (new App\Controllers\ArmoryController)();
        break;

    case '/login':
        (new App\Controllers\AuthController)->login();
        break;

    case '/logout':
        (new App\Controllers\AuthController)->logout();
        break;

    case '/health':
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'time' => time()]);
        break;

    case '/maintenance':
        if (($_ENV['APP_MAINTENANCE'] ?? 'false') === 'true') {
            http_response_code(503);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Maintenance";
        } else {
            header('Content-Type: text/plain; charset=utf-8');
            echo "Not in maintenance";
        }
        break;

    default:
        http_response_code(404);
        echo "Not found ($path)";
        break;
}
