<?php
session_set_cookie_params([
    'httponly' => true,
]);
session_start();
//avec la méthode orientée objet
require_once '../vendor/Autoload.php';
require_once '../Kernel/utils/Helpers.php';
use App\Kernel\Kernel;

App\Vendor\Autoload::register();
$router= new Kernel();
$response =  $router->route();
$response->send();




