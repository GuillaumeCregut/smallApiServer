<?php

namespace App\Services;

use DateTime;

class JwtToken
{
    /**
     * @var array<mixed>
     */
    private array $header;
    /**
     * @var array<mixed>
     */
    private array $payload;

    /**
     * @param array<mixed> $payload
     * @param string $secret
     * @param int|null $validity=86400
     *
     * @return string
     */
    public function createToken(array $payload, string $secret, ?int $validity = 86400): string
    {
        //Create Header
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];
        //Generate payload
        if ($validity > 0) {
            $now = new DateTime();
            $exp = $now->getTimestamp() + $validity;
            $payload['iat'] = $now->getTimestamp();
            $payload['exp'] = $exp;
        }
        $token = $this->makeToken($header, $payload, $secret, $validity);
        $this->header = $header;
        $this->payload = $payload;
        return $token;
    }
    /**
     * @return array<mixed>
     */
    public function getHeader(): array
    {
        return $this->header;
    }

    /**
     * @return array<mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function checkToken(string $token, string $secret): bool
    {
        if (!$this->checkFormat($token)) {
            throw new \InvalidArgumentException('Invalid token format');
        }
        //Extract Datas from token
        $tokenParts = explode('.', $token);
        if (count($tokenParts) !== 3) {
            throw new \InvalidArgumentException('Invalid token format');
        }
        $header = $this->decodeData($tokenParts[0]);
        $payload = $this->decodeData($tokenParts[1]);
        //Check Signature
        $VerifyToken = $this->makeToken($header, $payload, $secret, 0);
        if ($VerifyToken === $token) {
            $this->payload = $payload;
            $this->header = $header;
            return true;
        }
        return false;
    }

    public function isExpired(): bool
    {
        if (!isset($this->payload['exp'])) {
            return false;
        }
        $exp = $this->payload['exp'];
        $now = new DateTime();
        return $now->getTimestamp() > $exp;
    }

    /**
     * @param array<mixed> $header
     * @param array<mixed> $payload
     * @param string $secret
     * @param int $validity
     *
     * @return string
     */
    private function makeToken(array $header, array $payload, string $secret, int $validity): string
    {
        $JsonHeader = $this->encodeData($header);
        $JsonPayload = $this->encodeData($payload);
        //Generate Signature
        $signature = $this->createSignature($secret, $JsonHeader, $JsonPayload);
        $JsonSignature = $this->encodeData($signature);
        //return JWT
        return $JsonHeader . '.' . $JsonPayload . '.' . $JsonSignature;
    }
    private function createSignature(string $secret, string $JsonHeader, string $JsonPayload): string
    {
        $secret = base64_encode($secret);
        $signature = hash_hmac('sha256', $JsonHeader . '.' . $JsonPayload, $secret, true);
        return $signature;
    }

    /**
     * @param  array<mixed> $data
     *
     * @return string
     */
    private function encodeData(array |string $data): string
    {
        if (is_array($data)) {
            $serialize = json_encode($data);
        } else {
            $serialize = $data;
        }
        if (!$serialize) {
            throw new \InvalidArgumentException('Invalid serialization');
        }
        $baseEncode = base64_encode($serialize);
        $baseEncode = str_replace(['+', '/', '='], ['-', '_', ''], $baseEncode);
        return $baseEncode;
    }

    /**
     * @param string $data
     *
     * @return array<mixed>
     */
    private function decodeData(string $data): array
    {
        try {
            $data = base64_decode($data);
            $data = str_replace(['-', '_', ''], ['+', '/', '='], $data);
            $data = json_decode($data, true);
            return $data;
        } catch (\Throwable $th) {
            throw new \InvalidArgumentException('Token datas are invalid');
        }
    }

    private function checkFormat(string $token): bool
    {
        return preg_match(
            '/^[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+\.[a-zA-Z0-9\-\_\=]+$/',
            $token
        ) === 1;
    }
}
