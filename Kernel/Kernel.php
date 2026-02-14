<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

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
use Exception;

class Kernel
{
    private string $routeCall;
    private Request $request;
    private array $routes;

    public function __construct()
    {
        //Get env values
        $iniFile = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . '.env';
        try {
            GetEnvDatas::getEnvInstance($iniFile);
        } catch (KernelException $e) {
            $response = new ErrorResponse(500, true, $e);
            return $response;
        }

        //get routes for controller instantiation
        $this->routes = Router::getRoutes();

        //Get Values from server
        $datas = GetClientParams::getInputs();
        $headers = GetClientParams::getheaders();

        //Create Request
        $this->request = Request::initInstance($_SERVER, $datas, $_GET, $_POST, $_FILES, $_SESSION, $headers, $_COOKIE);
        $this->routeCall = $this->request->getURI();

        //Init Events
        $eventList = Events::getListeners();
        MakeListener::applyListener($eventList);
    }

    public function route(): ResponseInterface
    {
        $provider = ListenerProvider::getInstance();

        //Launch initKernelEvent
        EventDispatcher::getInstance($provider)->dispatch(new InitKernelEvent());

        //If no routes for request
        if (!key_exists($this->routeCall, $this->routes)) {
            $response = new ClientErrorResponse(404);
            EventDispatcher::getInstance()->dispatch(new ReturnResponseKernelEvent());
            return $response;
        }
        //Get Controller and method
        $clientMethod = Request::getRequestInstance()->getMethod();
        $routeAndMethod = $this->routes[$this->routeCall];
        
        if (!key_exists($clientMethod, $routeAndMethod)) {
            $response = new ClientErrorResponse(405);
            EventDispatcher::getInstance()->dispatch(new ReturnResponseKernelEvent());
            return $response;
        }
        else {
            $matchingRoute = $routeAndMethod[$clientMethod];
        }

        //Getting associated controller
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
        } catch (Exception $e) {
            // if an exception is thrown during controller execution
            $debug = GetEnvDatas::getEnvInstance()->get('debug_mode');
            $response = new ErrorResponse(500, $debug, $e);
            EventDispatcher::getInstance()->dispatch(new ReturnResponseKernelEvent());
            return $response;
        }
    }
}
