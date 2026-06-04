<?php

namespace System\Router\Web;
class Route
{

    public static function get($url, $executeMethod, $name = null){
        [$class, $method] = explode('@', $executeMethod);
        global $routes;
        $routes['get'][] = array('url' => trim($url, "/ "), 'class' => $class, 'method' => $method, 'name' => $name);
    }

    public static function post($url, $executeMethod, $name = null){

        [$class, $method] = explode('@', $executeMethod);
        global $routes;
        $routes['post'][] = array('url' => trim($url, "/ "), 'class' => $class, 'method' => $method, 'name' => $name);
    }

    public static function put($url, $executeMethod, $name = null){
        [$class, $method] = explode('@', $executeMethod);
        global $routes;
        $routes['put'][] = array('url' => trim($url, "/ "), 'class' => $class, 'method' => $method, 'name' => $name);
    }

    public static function delete($url, $executeMethod, $name = null){

        [$class, $method] = explode('@', $executeMethod);
        global $routes;
        $routes['delete'][] = array('url' => trim($url, "/ "), 'class' => $class, 'method' => $method, 'name' => $name);
    }

}