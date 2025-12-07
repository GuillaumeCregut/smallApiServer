<?php
session_start();
//avec la méthode orientée objet
require_once '../vendor/Autoload.php';
use App\Kernel\RequestObject;
use App\Kernel\RouterObject;

App\Vendor\Autoload::register();
$router= new RouterObject();
$response =  $router->route();
$response->send();




