<?php

namespace App\Kernel;

use App\Kernel\Request;
use App\Services\Connector;
use App\Kernel\GetClientParams;
use App\Kernel\Responses\ErrorResponse;
use App\Kernel\Interfaces\ResponseInterface;
use App\Kernel\Responses\ClientErrorResponse;
use App\Kernel\Middleware\Security\AuthManagerMiddleware;

class Kernel
{
    private string $routeCall;
    private Request $request;
    private array $routes;

    public function __construct()
    {
        $this->routes = Router::getRoutes();
        $datas = GetClientParams::getInputs();
        $headers = GetClientParams::getheaders();
        $this->request = Request::initInstance($_SERVER, $datas, $_GET, $_POST, $_FILES, $_SESSION, $headers);
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
            $authMiddleware = AuthManagerMiddleware::getAuthMiddleware($connector);
            $this->request->setAuth($authMiddleware);
            // execute the controller
            $page = (new $controller())->$method();
            return $page;
        } catch (\Exception $e) {
            // if an exception is thrown during controller execution
           $response = new ErrorResponse(500);
           return $response;
        }
    }
}
