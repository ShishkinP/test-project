<?php

/** @var ContainerInterface $container */
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\App;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Interfaces\RouteCollectorInterface;
use Slim\Interfaces\RouteResolverInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Psr7\Factory\ServerRequestFactory;

$container = require dirname(__DIR__) . '/bootstrap.php';
$request = ServerRequestFactory::createFromGlobals();

$app = new App(
    $container->get(ResponseFactoryInterface::class),
    $container,
    $container->get(CallableResolverInterface::class),
    $container->get(RouteCollectorInterface::class),
    $container->get(RouteResolverInterface::class)
);

$app->add($container->get(RoutingMiddleware::class));
$app->add($container->get(ErrorMiddleware::class));

$app->run($request);
