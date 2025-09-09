<?php
session_start();
//avec la méthode orientée objet
require_once '../vendor/Autoload.php';
use App\Services\RequestObject;
use App\Services\RouterObject;

App\Vendor\Autoload::register();
$request = new RequestObject(); //Ici, l'objet request contient la méthode et les données envoyées par le front
$router = new RouterObject($request);
echo $router->route();




