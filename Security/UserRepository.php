<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Security;

use App\Security\User;
use App\Kernel\Connector\DatabaseException;
use App\Kernel\Connector\AbstractRepository;
use App\Kernel\Interfaces\Databases\EntityInterface;

class UserRepository extends AbstractRepository
{
    protected ?string $entity = User::class;

    public function find(int $id): ?EntityInterface
    {
        /**
         * @var User $user
         */
        $user = parent::find($id);
        return $user;
    }

     public function findBy(array $fields): array
     {
        $users = parent::findBy($fields);
        return $users;
     }
   
    public function findOneByEmail(string $email): ?EntityInterface
    {
        $search = [
            'email' => $email
        ];
        $result = $this->findBy($search);
        if(empty($result)) {
            return null;
        }
        if(1<count($result)) {
            throw new DatabaseException('More than one user found');
        }
        $user = $result[0];
        /**
         * @var User $user
         */
        return $user;
    }

    public function findByUserNameCredentials(string $username, string $password): ?User
    {
         $search = [
            'username' => $username
        ];
        $result = $this->findBy($search);
        if(empty($result)) {
            return null;
        }
        if(1<count($result)) {
            throw new DatabaseException('More than one user found');
        }
       /**
        * @var User $user
        */ 
       $user = $result[0];
       //Check password
        $dbPass = $user->getPassword();
        if(!password_verify($password, $dbPass)) {
            return null;
        }
       //return new User
       return $user;
    }
}
