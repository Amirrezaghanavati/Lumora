<?php

namespace System\Router;

use ReflectionMethod;

class Routing
{

    private $currentRoute;
    private $methodField;
    private $routes;
    private array $values = [];

    public function __construct(){

        $this->currentRoute = explode('/', CURRENT_ROUTE);
        $this->methodField = $this->methodField();

        global $routes;
        $this->routes = $routes;
    }

    private function methodField()
    {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        if (($method === 'post') && isset($_POST['_method'])) {
            if($_POST['_method'] === 'put'){
                $method = 'put';
            }elseif ($_POST['_method'] === 'delete'){
                $method = 'delete';
            }
        }
        return $method;
    }

    public function run()
    {

        $match = $this->match();

        if (empty($match)) {
            $this->error404();
        }

        $classPath = str_replace('\\', '/', $match['class']);
        $path = BASE_DIR . "/app/Http/Controllers/" . $classPath . ".php";
        if(!file_exists($path)){
            $this->error404(); //programming error - controller does not exist, error must show
        }



        $class =  "\App\Http\Controllers\\" . $match['class'];
        $controller = new $class();
        if (!method_exists($controller, $match['method'])) {
            $this->error404();
        }else{
            $reflectionMethod = new ReflectionMethod($controller, $match['method']);
            $params = $reflectionMethod->getNumberOfParameters();
            if(count($this->values) >= $params){
                call_user_func_array([$controller, $match['method']], $this->values);
            }else{
                $this->error404();
            }
        }


    }

    public function match(): array
    {

        $reservedRoutes = $this->routes[$this->methodField];


        foreach ($reservedRoutes as $reservedRoute) {

            if ($this->compare($reservedRoute['url'])){
                return [
                        'class' => $reservedRoute['class'],
                        'method' =>$reservedRoute['method']
                ];
            }
            $this->values = [];
        }

        return [];
    }

    private function compare($reservedRouteUrl): bool
    {

        if (trim($reservedRouteUrl, '/') === '') {
            return trim($this->currentRoute[0], '/') === '';
        }

        $reservedRouteUrlArray = explode('/', $reservedRouteUrl);
        if (count($reservedRouteUrlArray) !== count($this->currentRoute)) {
            return false;
        }

        foreach ($this->currentRoute as $key => $currentRouteElement) {
            $reservedRouteUrlElement = $reservedRouteUrlArray[$key];
            if(str_starts_with($reservedRouteUrlElement, '{') && str_ends_with($reservedRouteUrlElement, '}')) {
                $this->values[] = $currentRouteElement;
            }elseif ($reservedRouteUrlElement !== $currentRouteElement) {
                return false;
            }
        }

        return true;

    }

    public function error404()
    {

        http_response_code(404);
        include __DIR__. DIRECTORY_SEPARATOR . 'View'. DIRECTORY_SEPARATOR . '404.php';
        exit();
    }
}