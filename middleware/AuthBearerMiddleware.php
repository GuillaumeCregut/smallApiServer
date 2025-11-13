<?php

namespace App\Middleware;

use DateTime;
use App\Security\User;
use App\Services\JwtToken;
use App\Services\GetEnvDatas;
use App\Interfaces\AuthenticationInterface;


class AuthBearerMiddleware implements AuthenticationInterface
{
    private User $user;
    private string $secret;
    private JwtToken $jwtToken;
    private array $userRole = [];

    public function __construct()
    {
        $this->user = new User();
        $envs = new GetEnvDatas();
        $this->secret = $envs->get('secret');
        $this->jwtToken = new JwtToken();
    }

    public function getUserRole(): array
    {
        return $this->userRole;
    }
   
    public function isAuth(string $token): bool
    {
        $userInfo = $this->jwtToken->extractPayload($token);
        if(is_array ($userInfo['role']) ){
            $this->userRole = $userInfo['role'];
        } else {
            $this->userRole = [$userInfo['role']];
        }
        $this->user->setId((int)$userInfo['user_id']);
        $userToken = $this->user->getToken();
        if(($token === null) || ($token !== $userToken)){
            return false;
        }
        $validity =  $userInfo['exp'] ?? 0;
        $now = new DateTime();
        $exp = $now->getTimestamp() - $validity;
        if($exp > 0){
            return false;
        }
        return $this->jwtToken->checkToken($token, $this->secret) ;
    }
}