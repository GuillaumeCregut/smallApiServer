<?php

namespace App\Kernel;

use App\Services\Responses\ErrorResponse;
use App\Kernel\RequestObject;
use App\Kernel\Interfaces\ResponseInterface;
use App\Services\Responses\ClientErrorResponse;
use App\Middleware\AuthBearerMiddleware;

class RouterObject
{
    private string $routeCall;
    private RequestObject $request;
    private array $routes = [
        '' => ['\App\Controllers\HomeController', 'index',],
        'items' => ['\App\Controllers\ItemController', 'index',],
        'categories' => ['\App\Controllers\CategoryController', 'index',],
    ];
    public function __construct(RequestObject $request)
    {
        $this->request = $request;
        $route = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');
        $this->routeCall = $this->makeRoute($route);
    }

    public function route(): ResponseInterface
    {
        // If required route is not is $routes, return a 404 Page not found error
        if (!key_exists($this->routeCall, $this->routes)) {
            $response = new ClientErrorResponse(404);
            return $response;
            exit();
        }
        $matchingRoute = $this->routes[$this->routeCall];
        $controller = $matchingRoute[0];
        $method = $matchingRoute[1];

        try {
            // execute the controller
            $authMiddleware = new AuthBearerMiddleware();
            $page = (new $controller($this->request,$authMiddleware))->$method();
            return $page;
        } catch (\Exception $e) {
            // if an exception is thrown during controller execution
           $response = new ErrorResponse(500);
           return $response;
            exit();
        }
    }

    private function makeRoute(string $route): string
    {
        $route = filter_var($route, FILTER_SANITIZE_URL);
        $routes = explode('/', $route);
        $id = end($routes);
        if (is_numeric($id)) {
            $this->request->setData('id', (int)$id);
            array_pop($routes);
        }
        return implode('/', $routes);
        // Remove any unwanted characters from the route
    }
}
