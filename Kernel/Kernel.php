<?php

namespace App\Kernel;

use App\Kernel\Config\DatabaseConnector;
use App\Kernel\Request;
use App\Kernel\Config\Events;
use App\Kernel\Config\Router;
use App\Kernel\Connector\ConnectorDispatcher;
use App\Kernel\Exceptions\KernelException;
use App\Kernel\GetClientParams;
use App\Kernel\Responses\ErrorResponse;
use App\Kernel\Interfaces\ResponseInterface;
use App\Kernel\Psr14\Events\InitKernelEvent;
use App\Kernel\Responses\ClientErrorResponse;
use App\Kernel\Psr14\Listener\ListenerProvider;
use App\Kernel\Psr14\Dispatcher\EventDispatcher;
use App\Kernel\Psr14\Events\CallAuthKernelEvent;
use App\Kernel\Psr14\Events\ConnectorKernelEvent;
use App\Kernel\Psr14\Events\CheckApiKeyKernelEvent;
use App\Kernel\Psr14\Events\ReturnResponseKernelEvent;
use App\Kernel\Psr14\Events\StartControllerKernelEvent;

class Kernel
{
    private string $routeCall;
    private Request $request;
    private array $routes;

    public function __construct()
    {
        $iniFile = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . '.env';
        try {
            $env = GetEnvDatas::getEnvInstance($iniFile);
        } catch (KernelException $e) {
            $response = new ErrorResponse(500);
            return $response;
        }
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
            EventDispatcher::getInstance()->dispatch(new ConnectorKernelEvent());
            ConnectorDispatcher::setConnector(DatabaseConnector::getConnector());
            EventDispatcher::getInstance()->dispatch(new CallAuthKernelEvent());
            EventDispatcher::getInstance()->dispatch(new CheckApiKeyKernelEvent());
            // execute the controller
            EventDispatcher::getInstance()->dispatch(new StartControllerKernelEvent());
            $page = (new $controller())->$method();
            EventDispatcher::getInstance()->dispatch(new ReturnResponseKernelEvent());
            return $page;
        } catch (\Exception $e) {
            // if an exception is thrown during controller execution
            $response = new ErrorResponse(500, $e);
            EventDispatcher::getInstance()->dispatch(new ReturnResponseKernelEvent());
            return $response;
        }
    }
}
