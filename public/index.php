<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

error_reporting(E_ALL);
ini_set('display_errors','1');


$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim($uri, '/');

switch ($path) {
    case '':
        (new App\Controllers\HomeController)();
        break;

    case '/armory':
        (new App\Controllers\ArmoryController)();
        break;

    case '/armory/search':
        (new App\Controllers\ArmorySearchController())();
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

    case '/account':
        (new \App\Controllers\AccountController())();
        break;

    case '/character':
        (new \App\Controllers\CharacterController())();
        break;

    case '/shop':
        (new \App\Controllers\ShopController())();
        break;

    case '/features':
        (new \App\Controllers\FeaturesController())();
        break;

    case '/admin/soap':
        (new \App\Controllers\SoapConsoleController())();
        break;

    case '/admin/metrics':
        (new \App\Controllers\AdminMetricsController())();
        break;

    case '/admin/settings':
        (new \App\Controllers\AdminSettingsController())();
        break;

    case '/auction':
        (new \App\Controllers\AuctionController())();
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
