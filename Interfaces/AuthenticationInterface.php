<?php

namespace App\Interfaces;

interface AuthenticationInterface
{
    public function isAuth(string $token): bool;
}