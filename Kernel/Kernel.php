<?php

namespace App\Kernel;

use App\Kernel\Request;
use App\Kernel\Config\Events;
use App\Kernel\Config\Router;
use App\Kernel\GetClientParams;
use App\Kernel\Responses\ErrorResponse;
use App\Kernel\Config\DatabaseConnector;
use App\Kernel\Interfaces\ResponseInterface;
use App\Kernel\Psr14\Events\InitKernelEvent;
use App\Kernel\Responses\ClientErrorResponse;
use App\Kernel\Psr14\Listener\ListenerProvider;
use App\Kernel\Psr14\Dispatcher\EventDispatcher;
use App\Kernel\Psr14\Events\CallAuthKernelEvent;
use App\Kernel\Psr14\Events\ConnectorKernelEvent;
use App\Kernel\Psr14\Events\CheckApiKeyKernelEvent;
use App\Kernel\Middleware\Security\AuthManagerMiddleware;
use App\Kernel\Psr14\Events\ReturnResponseKernelEvent;
use App\Kernel\Psr14\Events\StartControllerKernelEvent;

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
        $eventList = Events::getListeners();
        MakeListener::applyListener($eventList);
    }

    public function route(): ResponseInterface
    {
        $provider = ListenerProvider::getInstance();
        EventDispatcher::getInstance($provider)->dispatch(new InitKernelEvent());
        if (!key_exists($this->routeCall, $this->routes)) {
            $response = new ClientErrorResponse(404);
            EventDispatcher::getInstance()->dispatch(new ReturnResponseKernelEvent());
            return $response;
        }
        $matchingRoute = $this->routes[$this->routeCall];
        $controller = $matchingRoute[0];
        $method = $matchingRoute[1];

        try {
            //Add auth middleware to request
            EventDispatcher::getInstance()->dispatch(new ConnectorKernelEvent());
            $connector = DatabaseConnector::getConnector();

            EventDispatcher::getInstance()->dispatch(new CallAuthKernelEvent());
            $authMiddleware = AuthManagerMiddleware::getAuthMiddleware($connector);
            $this->request->setAuth($authMiddleware);
            EventDispatcher::getInstance()->dispatch(new CheckApiKeyKernelEvent());
            // execute the controller
            EventDispatcher::getInstance()->dispatch(new StartControllerKernelEvent());
            $page = (new $controller())->$method();
            EventDispatcher::getInstance()->dispatch(new ReturnResponseKernelEvent());
            return $page;
        } catch (\Exception $e) {
            // if an exception is thrown during controller execution
            $response = new ErrorResponse(500);
            EventDispatcher::getInstance()->dispatch(new ReturnResponseKernelEvent());
            return $response;
        }
    }
}
