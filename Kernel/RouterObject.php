<?php

namespace App\Kernel;

use App\Services\Connector;
use App\Kernel\RequestObject;
use App\Middleware\AuthManagerMiddleware;
use App\Services\Responses\ErrorResponse;
use App\Kernel\Interfaces\ResponseInterface;
use App\Services\Responses\ClientErrorResponse;

class RouterObject
{
    private string $routeCall;
    private RequestObject $request;
    private array $routes = [
        '' => ['\App\Controllers\HomeController', 'index',],
        'items' => ['\App\Controllers\ItemController', 'index',],
        'categories' => ['\App\Controllers\CategoryController', 'index',],
    ];

    public function __construct()
    {
        $this->request = RequestObject::getRequestInstance();
        $this->routeCall = $this->request->getURI();
    }

    public function route(): ResponseInterface
    {
        // If required route is not is $routes, return a 404 Page not found error
        if (!key_exists($this->routeCall, $this->routes)) {
            $response = new ClientErrorResponse(404);
            return $response;
        }
        $matchingRoute = $this->routes[$this->routeCall];
        $controller = $matchingRoute[0];
        $method = $matchingRoute[1];

        try {
            //Add auth middleware to request
            $connector = new Connector();
            $manager = new AuthManagerMiddleware($connector);
            $authMiddleware = $manager->getAuthMiddleware();
            $this->request->setAuth($authMiddleware);
            // execute the controller
            $page = (new $controller($authMiddleware))->$method();
            return $page;
        } catch (\Exception $e) {
            // if an exception is thrown during controller execution
           $response = new ErrorResponse(500);
           return $response;
        }
    }
}
