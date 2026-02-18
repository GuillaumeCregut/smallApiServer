<?php
session_set_cookie_params([
    'httponly' => true,
]);
session_start();

require_once '../vendor/Autoload.php';
require_once '../src/Kernel/utils/Helpers.php';
use App\Kernel\Kernel;

App\Vendor\Autoload::register();
$router= new Kernel();
$response =  $router->route();
echo $response->send();




