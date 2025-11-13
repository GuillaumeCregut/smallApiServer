<?php

namespace App\Middleware;

use App\Security\User;
use App\Services\JwtToken;
use App\Services\GetEnvDatas;
use App\Interfaces\AuthenticationInterface;

class AuthBearerMiddleware implements AuthenticationInterface
{
    private User $user;
    private string $secret;
    private JwtToken $jwtToken;

    public function __construct()
    {
        $this->user = new User();
        $envs = new GetEnvDatas();
        $this->secret = $envs->get('secret');
        $this->jwtToken = new JwtToken();
    }
   
    public function isAuth(string $token): bool
    {
        $userToken = $this->user->getToken();
        echo $token;
        if(($token === null) || ($token !== $userToken)){
            return false;
        }
        return true;
    }


}