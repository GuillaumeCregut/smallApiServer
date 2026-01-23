<?php

namespace App\Middleware;

use App\Kernel\Interfaces\AuthenticationInterface;
use App\Kernel\RequestObject;
use App\Kernel\Traits\GetUserAuthTrait;
use App\Security\User;

class HttpAuthMiddleware implements AuthenticationInterface
{

    use GetUserAuthTrait;

    private ?User $user=null;
    // Implementation for HTTP authentication middleware
    public function isAuth(): bool
    {
        $request = RequestObject::getRequestInstance();
        $username = $request->getServer('PHP_AUTH_USER');
        $password = $request->getServer('PHP_AUTH_PW');

        throw new \Exception('Not implemented');
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}