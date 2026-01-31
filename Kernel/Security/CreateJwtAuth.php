<?php 

namespace App\Kernel\Security;
use App\Kernel\GetEnvDatas;

class CreateJwtAuth
{
    public function createToken(int $userId, array $role,?int $validity = 86400, ?string $firstname, ?string $lastname): string
    {
        $envs = new GetEnvDatas();
        $secret = $envs->get('secret');
        $payload = [
            'user_id' => $userId,
            'role' => $role,
            'firstname' => $firstname,
            'lastname' => $lastname
        ];
        $jwtToken = new JwtToken();
        return $jwtToken->createToken($payload, $secret, $validity);
    }
}