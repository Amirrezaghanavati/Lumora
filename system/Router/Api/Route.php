<?php

namespace System\Router\Api;
class Route
{

    public static function get($url, $executeMethod, $name = null): void
    {
        [$class, $method] = explode('@', $executeMethod);
        global $routes;
        $routes['get']['url'] = trim($url, '/ ');
        $routes['get']['class'] = $class;
        $routes['get']['method'] = $method;
        $routes['get']['name'] = $name;
    }

    public static function post($url, $executeMethod, $name = null): void
    {
        [$class, $method] = explode('@', $executeMethod);
        global $routes;
        $routes['post']['url'] = trim($url, '/ ');
        $routes['post']['class'] = $class;
        $routes['post']['method'] = $method;
        $routes['post']['name'] = $name;
    }

}