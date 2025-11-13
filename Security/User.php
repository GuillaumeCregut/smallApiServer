<?php

namespace App\Security;

use App\Services\Connector;

class User
{
    private Connector $connector;
    private int $id;

    public function __construct()
    {
        $this->connector = new Connector();
    }

    public function getRole(): array
    {
        //Todo : get user role from database
        return ['ROLE_USER'];
    }

    public function getToken(): string
    {
        //Todo : get user key from database
        return '';
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}