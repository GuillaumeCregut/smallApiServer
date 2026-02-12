<?php

namespace App\Security;

use App\Security\User;
use App\Kernel\Connector\AbstractRepository;
use App\Kernel\Connector\DatabaseException;
use App\Kernel\Interfaces\Databases\EntityInterface;

/**
 * @template T of EntityInterface
 */
class UserRepository extends AbstractRepository
{
    protected ?string $entity = User::class;
   
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
        $user->setPassword(null);
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
       $user->setPassword(null);
       return $user;
    }
}
