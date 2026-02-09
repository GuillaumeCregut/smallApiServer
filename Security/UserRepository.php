<?php

namespace App\Security;

use App\Kernel\Connector\AbstractRepository;
use App\Kernel\Interfaces\Databases\EntityInterface;
use Exception;

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
            throw new Exception('More than one user found');
        }
        return $result[0];
    }
}
