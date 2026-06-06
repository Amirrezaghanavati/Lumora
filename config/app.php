<?php

const APP_NAME = 'app_name';
const BASE_URL = 'http://localhost:8000/';

define("BASE_DIR", dirname(__DIR__ . '/../'));


$temporary = str_replace(BASE_URL, '', explode('/', $_SERVER['DOCUMENT_ROOT'])[0]);
$temporary = $temporary === '/' ? '' : substr($temporary, 1);
define('CURRENT_ROUTE', $temporary);

global $routes;

$routes = [
    'GET' => [],
    'POST' => [],
    'PUT' => [],
    'DELETE' => [],
];