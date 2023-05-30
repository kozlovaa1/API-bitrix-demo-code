<?php

#declare(strict_types = 1);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, GET, OPTION');
header('Access-Control-Allow-Headers: *');

define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
define("NO_KEEP_STATISTIC", true);
define("STOP_STATISTICS", true);

require $_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php";

use DI\Container;

use Local\Controllers\GeoController;
use Local\Controllers\ReviewController;
use Local\Response\ErrorResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;


// Create slim app instance
$container = new Container();
$container->set('validator', function () {
    return new Awurth\SlimValidation\Validator();
});
AppFactory::setContainer($container);

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
// Define Custom Error Handler
$customErrorHandler = function (
    ServerRequestInterface $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails,
    ?LoggerInterface $logger = null
) use ($app) {
    return new ErrorResponse($exception->getMessage());
};

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);

$app->setBasePath("/api");

/* Отзывы */
$app->group("/review", function (RouteCollectorProxy $group) {
    $group->any('/add', [ReviewController::class, 'add']);
    $group->any('/like', [ReviewController::class, 'like']);
    $group->any('/dislike', [ReviewController::class, 'dislike']);
});

/* Геолокация */
$app->group("/geo", function (RouteCollectorProxy $group) {
    $group->post('/findCities', [GeoController::class, 'findCities']);
    $group->post('/setCity', [GeoController::class, 'setCity']);
});

$app->run();
