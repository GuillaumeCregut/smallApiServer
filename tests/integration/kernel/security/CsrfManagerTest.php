<?php

use App\Kernel\Request;
use PHPUnit\Framework\TestCase;
use App\Kernel\Security\CsrfManager;

class CsrfManagerTest extends TestCase
{
    private array $baseServer = [
        'REQUEST_METHOD'  => 'POST',
        'REQUEST_URI'     => '/api/resource',
        'HTTP_HOST'       => 'localhost:8000',
        'SERVER_PROTOCOL' => 'HTTP/1.1',
    ];

    protected function tearDown(): void
    {
        Request::resetInstance();
    }

    private function makeManager(array $session = []): CsrfManager
    {
        Request::resetInstance();
        $request = Request::initInstance($this->baseServer, [], [], [], [], $session, []);
        return new CsrfManager($request);
    }

    public function testGeneratesTokenWhenNoneInSession(): void
    {
        $manager = $this->makeManager(); // empty session
        $token   = $manager->getOrCreateToken();

        $this->assertNotEmpty($token->value);
        $this->assertGreaterThan(time(), $token->expiresAt);
    }

    public function testTokenIValidHexStringOfCorrectLength(): void
    {
        $manager = $this->makeManager();
        $token   = $manager->getOrCreateToken();

        // 32 bytes = 64 hex chars
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token->value);
    }

    public function testReturnsSameTokenOnSecondCall(): void
    {
        $manager = $this->makeManager();
        $first   = $manager->getOrCreateToken();
        $second  = $manager->getOrCreateToken();

        $this->assertSame($first->value, $second->value);
    }

    public function testRegeneratesTokenWhenExpired(): void
    {
        $expiredSession = [
            '_csrf_token' => [
                'value'      => 'old-expired-token',
                'expires_at' => time() - 1, // already expired
            ]
        ];
        $manager = $this->makeManager($expiredSession);
        $token   = $manager->getOrCreateToken();

        $this->assertNotSame('old-expired-token', $token->value);
        $this->assertGreaterThan(time(), $token->expiresAt);
    }

    public function testTokenTtlIsApproximatelyOneHour(): void
    {
        $manager = $this->makeManager();
        $token   = $manager->getOrCreateToken();

        $this->assertEqualsWithDelta(time() + 3600, $token->expiresAt, 5);
    }

    public function testValidatesCorrectToken(): void
    {
        $manager = $this->makeManager();
        $token   = $manager->getOrCreateToken();

        $this->assertTrue($manager->validateToken($token->value));
    }

    public function testRejectsWrongTokenValue(): void
    {
        $manager = $this->makeManager();
        $manager->getOrCreateToken();

        $this->assertFalse($manager->validateToken('not-the-right-value'));
    }

    public function testRejectsWhenNoTokenInSession(): void
    {
        $manager = $this->makeManager(); // empty session

        $this->assertFalse($manager->validateToken('any-value'));
    }

    public function testRejectsExpiredToken(): void
    {
        $expiredSession = [
            '_csrf_token' => [
                'value'      => 'expired-token-value',
                'expires_at' => time() - 1,
            ]
        ];
        $manager = $this->makeManager($expiredSession);

        $this->assertFalse($manager->validateToken('expired-token-value'));
    }
}