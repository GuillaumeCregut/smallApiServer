<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel;

use App\Kernel\Request;
use App\Kernel\Config\Events;
use App\Kernel\Config\Router;
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
use App\Kernel\Psr14\Events\CheckCsrfEvent;
use App\Kernel\Psr14\Events\ReturnResponseKernelEvent;
use App\Kernel\Psr14\Events\StartControllerKernelEvent;
use App\Kernel\Psr14\Exceptions\EventException;
use Exception;

class Kernel
{
    private ?string $routeCall;
    private Request $request;
    private array $routes;
    private bool $errorInBoot = false;
    private string $errorMessage = '';

    public function __construct()
    {
        //Get env values
        $iniFile = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';
        try {
            GetEnvDatas::getEnvInstance($iniFile);
        } catch (KernelException $e) {
            $this->errorInBoot = true;
            $this->errorMessage = $e->getMessage();
            Logger::error($this, $this->errorMessage, false, false);
        }

        //get routes for controller instantiation
        $this->routes = Router::getRoutes();

        //Get Values from server
        try {
            $datas = GetClientParams::getInputs();
            $headers = GetClientParams::getheaders();
        } catch (KernelException $e) {
            $this->errorMessage = $e->getMessage();
            $this->errorInBoot = true;
            $datas = [];
            $headers = [];
        }

        //Create Request
        $this->request = Request::initInstance($_SERVER, $datas, $_GET, $_POST, $_FILES, $_SESSION, $headers, $_COOKIE);
        $this->routeCall = $this->request->getURI($this->routes);

        //Init Events
        $eventList = Events::getListeners();
        MakeListener::applyListener($eventList);
    }

    public function route(): ResponseInterface
    {
        if ($this->errorInBoot) {
            $e = new Exception($this->errorMessage);
            return $this->sendErrorResponse($e);
        }
        $provider = ListenerProvider::getInstance();

        //Launch initKernelEvent
        EventDispatcher::getInstance($provider)->dispatch(new InitKernelEvent());

        if (null === $this->routeCall) {
            $response = new ClientErrorResponse(404);
            EventDispatcher::getInstance()->dispatch(new ReturnResponseKernelEvent($response, Request::getRequestInstance()));
            return $response;
        }

        //If no routes for request
        if (!key_exists($this->routeCall, $this->routes)) {
            $response = new ClientErrorResponse(404);
            EventDispatcher::getInstance()->dispatch(new ReturnResponseKernelEvent($response, Request::getRequestInstance()));
            return $response;
        }

        //Get Controller and method
        $clientMethod = Request::getRequestInstance()->getMethod();

        $methods = implode(',',$this->getAllowedMethods($this->routeCall));
        //Handle Preflight request
        if ('OPTIONS' === $clientMethod) {
            $origin = GetEnvDatas::getEnvInstance()->get('ALLOW_ORIGIN', '*');
            header("Access-Control-Allow-Origin: {$origin}");
            header("Access-Control-Allow-Methods: {$methods}, OPTIONS");
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Access-Control-Max-Age: 86400');
            http_response_code(200);
            exit();
        }

        $routeAndMethod = $this->routes[$this->routeCall];

        if (!key_exists($clientMethod, $routeAndMethod)) {
            $response = new ClientErrorResponse(405);
            EventDispatcher::getInstance()->dispatch(new ReturnResponseKernelEvent($response, Request::getRequestInstance()));
            return $response;
        } else {
            $matchingRoute = $routeAndMethod[$clientMethod];
        }

        //Getting associated controller
        $controller = $matchingRoute[0];
        $method = $matchingRoute[1];

        try {
            EventDispatcher::getInstance()->dispatch(new ConnectorKernelEvent());
            EventDispatcher::getInstance()->dispatch(new CallAuthKernelEvent());
            EventDispatcher::getInstance()->dispatch(new CheckCsrfEvent(Request::getRequestInstance()));
            EventDispatcher::getInstance()->dispatch(new CheckApiKeyKernelEvent());
            // execute the controller
            EventDispatcher::getInstance()->dispatch(new StartControllerKernelEvent());
            $page = (new $controller())->$method();

            EventDispatcher::getInstance()->dispatch(new ReturnResponseKernelEvent($page, Request::getRequestInstance()));
            return $page;
        }catch(EventException $e) {
            // if an exception is thrown during Events
            $debug = GetEnvDatas::getEnvInstance()->get('DEBUG_MODE');
            $response = new ErrorResponse($e->getCode(), $debug, $e);
            EventDispatcher::getInstance()->dispatch(new ReturnResponseKernelEvent($response, Request::getRequestInstance()));
            return $response;
        }
         catch (Exception $e) {
            // if an exception is thrown during controller execution
            $debug = GetEnvDatas::getEnvInstance()->get('DEBUG_MODE');
            $response = new ErrorResponse(500, $debug, $e);
            EventDispatcher::getInstance()->dispatch(new ReturnResponseKernelEvent($response, Request::getRequestInstance()));
            return $response;
        }
    }

    private function sendErrorResponse(Exception $e): ResponseInterface
    {
        $response = new ErrorResponse(500, true, $e);
        return $response;
    }

    private function getAllowedMethods(string $route): array
    {
        $methods = [];
        foreach ($this->routes[$route] as $method => $_) {
            $methods[] = $method;
        }
        return $methods;
    }
}
