<?php

namespace App\Kernel\Security;

use App\Kernel\Request;
use App\Kernel\Security\CsrfToken;

class CsrfManager
{
    private const TOKEN_LENGTH = 32;
    private const TOKEN_TTL    = 3600; // 1 hour
    private const SESSION_KEY  = '_csrf_token';


    public function __construct(private Request $request) {}

    public function getOrCreateToken(): CsrfToken
    {
        $stored = $this->request->getSessionValue(self::SESSION_KEY);
        if (null === $stored) {
            return $this->generateToken();
        }

        $token  = new CsrfToken($stored['value'], $stored['expires_at']);

        if ($token->isExpired()) {
            return $this->generateToken();
        }

        return $token;
    }

    public function validateToken(string $submittedToken): bool
    {
        $stored = $this->request->getSessionValue(self::SESSION_KEY);
        if (null === $stored) {
            return false;
        }
        $token  = new CsrfToken($stored['value'], $stored['expires_at']);
        if ($token->isExpired()) {
            return false;
        }
        // Timing-safe comparison to prevent timing attacks
        return hash_equals($token->value, $submittedToken);
    }

    private function generateToken(): CsrfToken
    {
        $token = new CsrfToken(
            value: bin2hex(random_bytes(self::TOKEN_LENGTH)),
            expiresAt: time() + self::TOKEN_TTL
        );
        $tokenInfo = [
            'value'      => $token->value,
            'expires_at' => $token->expiresAt,
        ];
        $this->request->setSessionValue(self::SESSION_KEY, $tokenInfo);
        return $token;
    }
}
