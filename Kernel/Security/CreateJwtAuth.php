<?php 

namespace App\Kernel\Security;
use App\Kernel\GetEnvDatas;

class CreateJwtAuth
{
    public static function createToken(int $userId, array $role, ?string $firstname, ?string $lastname, ?int $validity = 86400,): string
    {
        $envs = GetEnvDatas::getEnvInstance();
        $secret = $envs->get('secret');
        $payload = [
            'userId' => $userId,
            'role' => $role,
            'firstname' => $firstname,
            'lastname' => $lastname
        ];
        $jwtToken = new JwtToken();
        return JwtToken::createToken($payload, $secret, $validity);
    }
}