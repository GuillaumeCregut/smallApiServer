<?php

namespace App\Kernel\Middleware\Security;

use DateTime;
use App\Security\User;
use App\Kernel\Request;
use App\Kernel\GetEnvDatas;
use App\Kernel\Security\JwtToken;
use App\Kernel\Interfaces\AuthenticationInterface;
use App\Security\UserRepository;

class AuthBearerMiddleware implements AuthenticationInterface
{
    private ?User $user = null;
    private string $secret;
    private JwtToken $jwtToken;
    private ?string $token = null;

    public function __construct(private UserRepository $repo)
    {
        $envs = GetEnvDatas::getEnvInstance();
        $this->secret = $envs->get('secret');
        $this->jwtToken = new JwtToken();
        $request =Request::getRequestInstance();
        $authHeader = $request->getHeaders('Authorization');
        if ($authHeader !== null) {
            $parts = explode(' ', $authHeader,2);
            if (count($parts) === 2 && strtolower($parts[0]) === 'bearer') {
                
                $this->token = $parts[1];
            }
        }
        $this->isAuth();
    }
   
    public function isAuth(): bool
    {
        if(null === $this->token) {
            return false;
        }
        if(!$this->jwtToken->checkFormat($this->token)) {
            return false;
        }
        $userInfo = $this->jwtToken->extractPayload($this->token);
        $userId = (int)$userInfo['userId'];
        $validity =  $userInfo['exp'] ?? 0;
        $now = new DateTime();
        $exp = $now->getTimestamp() - $validity;
        if($exp > 0){
            return false;
        }
        $this->user = $this->getUserFromDB($userId);
        if ($this->user === null) {
            return false;
        }
        $userToken = $this->user->getToken();
        if($this->token !== $userToken)
        {
            return false;
        }
        return $this->jwtToken->checkToken($this->token, $this->secret) ;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    private function getUserFromDB(int $id): ?User
    {
        return $this->repo->find($id);
    }
}