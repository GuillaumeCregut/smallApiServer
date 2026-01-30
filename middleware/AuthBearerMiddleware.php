<?php

namespace App\Middleware;

use DateTime;
use App\Security\User;
use App\Kernel\GetEnvDatas;
use App\Kernel\Request;
use App\Services\Security\JwtToken;
use App\Interfaces\ConnectorInterface;
use App\Kernel\Traits\GetUserAuthTrait;
use App\Kernel\Interfaces\AuthenticationInterface;

class AuthBearerMiddleware implements AuthenticationInterface
{
    private ?User $user = null;
    private string $secret;
    private JwtToken $jwtToken;
    private array $userRole = [];
    private ConnectorInterface $connector;
    private ?string $token = null;

    use GetUserAuthTrait;

    public function __construct(ConnectorInterface $connector)
    {
        $envs = GetEnvDatas::getEnvInstance();
        $this->secret = $envs->get('secret');
        $this->jwtToken = new JwtToken();
        $this->connector = $connector;
        $request =Request::getRequestInstance();
        $authHeader = $request->getHeaders('Authorization');
        if ($authHeader !== null) {
            $parts = explode(' ', $authHeader,2);
            if (count($parts) === 2 && strtolower($parts[0]) === 'bearer') {
                $this->token = $parts[1];
            }
        }
    }
   
    public function isAuth(): bool
    {
        $userInfo = $this->jwtToken->extractPayload($this->token);
        $userId = (int)$userInfo['user_id'];
        $this->user = $this->getUserFromDB($userId);
        if ($this->user === null) {
            return false;
        }
        $userToken = $this->user->getToken();
        if(($this->token === null) || ($this->token !== $userToken)){
            return false;
        }
        $validity =  $userInfo['exp'] ?? 0;
        $now = new DateTime();
        $exp = $now->getTimestamp() - $validity;
        if($exp > 0){
            return false;
        }
        return $this->jwtToken->checkToken($this->token, $this->secret) ;
    }

    /**
     * Get the value of user
     */
    public function getUser(): ?User
    {
        return $this->user;
    }
}