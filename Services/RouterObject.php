<?php

namespace App\Services;

use App\Interfaces\ResponseInterface;
use App\Services\RequestObject;

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
        $this->routeCall = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');
        $this->request = $request;
    }

    public function route(): ResponseInterface
    {
        // If required route is not is $routes, return a 404 Page not found error
        if (!key_exists($this->routeCall, $this->routes)) {
            $response = new NotFoundResponse();
            return $response;
            exit();
        }
        $matchingRoute = $this->routes[$this->routeCall];
        $controller = $matchingRoute[0];
        $method = $matchingRoute[1];

        try {
            // execute the controller
            $page = (new $controller())->$method($this->request);
            return $page;
        } catch (\Exception $e) {
            // if an exception is thrown during controller execution
            header("HTTP/1.0 500 Internal Server Error");
            echo '500 - Internal Server Error';
            exit();
        }
    }
}
