<?php

require dirname(__DIR__) . '/app/bootstrap.php';

$router = new \App\Core\Router();
require dirname(__DIR__) . '/app/routes.php';
$router->dispatch();
