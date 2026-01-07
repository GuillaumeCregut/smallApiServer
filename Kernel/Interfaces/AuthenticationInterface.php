<?php

namespace App\Kernel\Interfaces;

use App\Security\User;

interface AuthenticationInterface
{
    public function isAuth(string $token): bool;
    public function getUser(): ?User;
}