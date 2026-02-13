<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Security;

use DateTime;
use Exception;

class JwtToken
{
    /**
     * @var array<mixed>
     */
    private ?array $payload = null;
    private bool $set =false;

    public static function createToken(array $payload, string $secret, int $validity = 86400): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        if ($validity > 0) {
            $now = new DateTime();
            $expiration = $now->getTimestamp() + $validity;
            $payload['iat'] = $now->getTimestamp();
            $payload['exp'] = $expiration;
        }
        $encodedHeader = self::encodeData($header);
        $encodedPayload = self::encodeData($payload);
        $signature = self::makeSignature($secret, $encodedHeader, $encodedPayload);
        // Create token
        $jwt = $encodedHeader . '.' . $encodedPayload . '.' . $signature;
        return $jwt;
    }

    /**
     * Vérification du token
     * @param string $token token to check
     * @param string $secret
     * @return bool 
     */
    public  function checkToken(string $token, string $secret): bool
    {
        try{
            $payload = $this->getHashPayload($token);
            $verifToken = $this->createToken($payload, $secret, 0);
            $result =  $token === $verifToken;
            if($result) {
                //Store payload for further access
                $this->payload = $this->extractPayload($token);
                $this->set= true;
            }
            return $result;
        } catch (Exception $e){
            return false;
        }
    }

    /**
     * Check expiration
     * @param string $token
     * @return bool 
     */
    public function isExpired(string $token): bool
    {
        try{
            $payload = $this->gethashPayload($token);
            $now = new DateTime();
            return $payload['exp'] < $now->getTimestamp();
        } catch(Exception $e) {
            return true;
        }

    }

    /**
     * Check Token validity
     * @param string $token 
     * @return bool 
     */
    public static function checkFormat(string $token): bool
    {
        return preg_match(
            '/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/',
            $token
        ) === 1;
    }

    public function extractPayload(string $token): array
    {
        if (!$this->checkFormat($token)) {
            throw new \InvalidArgumentException('Invalid token format');
        }
        //Extract Datas from token
        $tokenParts = explode('.', $token);
        if (count($tokenParts) !== 3) {
            throw new \InvalidArgumentException('Invalid token format');
        }
        $payload = $this->decodeData($tokenParts[1]);
        $this->payload = $payload;
        $this->set = true;
        return $payload;
    }

     /**
     * Get the value of isSet
     */
    public function isSet(): bool
    {
        return $this->set;
    }

    /**
     * Get the value of payload
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    /**
     * Get hash payload
     * @param string $token Token
     * @return array Payload
     */
    private function getHashPayload(string $token)
    {
        if(!$this->checkFormat($token)){
            throw new Exception('Unable to decode Token');
        }
        $array = explode('.', $token);
        $payload = json_decode(base64_decode($array[1]), true);
        return $payload;
    }
    
    private static function encodeData(array $data): string
    {
        $dataEncoded = base64_encode(json_encode($data));
        $base64value = str_replace(['+', '/', '='], ['-', '_', ''], $dataEncoded);
        return $base64value;
    }

     /* @param string $data
     *
     * @return array<mixed>
     */
    private function decodeData(string $data): array
    {
        try {
            $data = base64_decode($data);
            $data = json_decode($data, true);
            return $data;
        } catch (\Throwable $th) {
            throw new \InvalidArgumentException('Token datas are invalid');
        }
    }

    private static function makeSignature(string $secret, string $header, string $payload): string
    {
        $secret = base64_encode($secret);
        $signature = hash_hmac('sha256', $header . '.' . $payload, $secret, true);
        $encodedSignature = base64_encode($signature);
        $signature = str_replace(['+', '/', '='], ['-', '_', ''], $encodedSignature);
        return $signature;
    }

}
