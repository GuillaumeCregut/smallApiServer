<?php

namespace App\Kernel\Traits;

use App\Security\User;

trait GetUserAuthTrait
{
    private function getUserFromDB(int $id) : ?User
    {
        $connection = $this->connector->getConnection();
        if($id <= 0){
            return null;
        }
        $user = new User($this->connector);
        $user->setId($id);
        $user->fetchFromDb();
        //Todo: check if user exists really
        if(!$user){
            return null;
        }
        return $user;
    }
}