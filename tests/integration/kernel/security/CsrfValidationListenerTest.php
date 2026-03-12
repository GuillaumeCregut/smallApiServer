<?php

use App\Kernel\Request;
use PHPUnit\Framework\TestCase;
use App\Kernel\Security\CsrfManager;
use App\Kernel\Psr14\Events\CheckCsrfEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Kernel\Psr14\Exceptions\EventException;
use App\Kernel\Middleware\Security\Csrf\CsrfValidationListener;

class CsrfValidationListenerTest extends TestCase
{
    private array $baseServer = [
        'REQUEST_METHOD'  => 'POST',
        'REQUEST_URI'     => '/api/resource',
        'HTTP_HOST'       => 'localhost:8000',
        'SERVER_PROTOCOL' => 'HTTP/1.1',
    ];

    protected function setUp(): void {}

    protected function tearDown(): void
    {
        Request::resetInstance();
    }

    private function initRequest(
        string $method  = 'POST',
        array  $session = [],
        array  $headers = [],
        array  $post    = [],
    ): Request {
        Request::resetInstance();
        $server = array_merge($this->baseServer, ['REQUEST_METHOD' => $method]);
        return Request::initInstance($server, [], [], $post, [], $session, $headers);
    }

    public static function safeMethods(): array
    {
        return [
            'GET'     => ['GET'],
            'HEAD'    => ['HEAD'],
            'OPTIONS' => ['OPTIONS'],
        ];
    }

    public static function mutatingMethods(): array
    {
        return [
            'POST'   => ['POST'],
            'PUT'    => ['PUT'],
            'PATCH'  => ['PATCH'],
            'DELETE' => ['DELETE'],
        ];
    }

    #[DataProvider('safeMethods')]
    public function testSafeMethodsAreAlwaysAllowed(string $method): void
    {
        $csrfManager = $this->createMock(CsrfManager::class);
        $listener    = new CsrfValidationListener($csrfManager);

        $request = $this->initRequest(method: $method);

        $csrfManager->expects($this->never())->method('validateToken');

        $listener->execute(new CheckCsrfEvent($request));

        $this->assertTrue(true);
    }

    public function testBearerAuthSkipsCsrfCheck(): void
    {
        $csrfManager = $this->createMock(CsrfManager::class);
        $listener    = new CsrfValidationListener($csrfManager);
        $request = $this->initRequest(
            headers: ['Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.payload.sig']
        );

        $csrfManager->expects($this->never())->method('validateToken');

        $listener->execute(new CheckCsrfEvent($request));
    }

    public function testBearerAuthIsCaseInsensitive(): void
    {
        $csrfManager = $this->createMock(CsrfManager::class);
        $listener    = new CsrfValidationListener($csrfManager);
        $request = $this->initRequest(
            headers: ['Authorization' => 'BEARER sometoken']
        );

        $csrfManager->expects($this->never())->method('validateToken');

        $listener->execute(new CheckCsrfEvent($request));
    }

    public function testAnonymousRequestSkipsCsrfCheck(): void
    {
        $csrfManager = $this->createMock(CsrfManager::class);
        $listener    = new CsrfValidationListener($csrfManager);
        $request = $this->initRequest();  // no session, no auth header

        $csrfManager->expects($this->never())->method('validateToken');

        $listener->execute(new CheckCsrfEvent($request));
    }

    public function testSessionAuthWithValidHeadeTokenPasses(): void
    {
        $csrfManager = $this->createMock(CsrfManager::class);
        $listener    = new CsrfValidationListener($csrfManager);
        $request = $this->initRequest(
            session: ['userId' => 42],
            headers: ['X-CSRF-Token' => 'valid-token-value']
        );

        $csrfManager->expects($this->once())
            ->method('validateToken')
            ->with('valid-token-value')
            ->willReturn(true);

        $listener->execute(new CheckCsrfEvent($request));
        $this->assertTrue(true);
    }

    public function testSessionAuthWithValidBodyTokenPasses(): void
    {
        $csrfManager = $this->createMock(CsrfManager::class);
        $listener    = new CsrfValidationListener($csrfManager);
        $request = $this->initRequest(
            session: ['userId' => 42],
            post: ['_csrf_token' => 'body-token-value']
        );

        $csrfManager->expects($this->once())
            ->method('validateToken')
            ->with('body-token-value')
            ->willReturn(true);

        $listener->execute(new CheckCsrfEvent($request));

        $this->assertTrue(true);
    }

    public function testSessionAuthWithMissingTokenThrows403(): void
    {
        $csrfManager = $this->createMock(CsrfManager::class);
        $listener    = new CsrfValidationListener($csrfManager);
        $request = $this->initRequest(session: ['userId' => 42]);
        // No token in headers or body

        $csrfManager->expects($this->never())->method('validateToken');

        $this->expectException(EventException::class);
        $this->expectExceptionMessage('CSRF token missing');
        $this->expectExceptionCode(403);

        $listener->execute(new CheckCsrfEvent($request));
    }

    public function testSessionAuthWithInvalidTokenThrows403(): void
    {
        $csrfManager = $this->createMock(CsrfManager::class);
        $listener    = new CsrfValidationListener($csrfManager);
        $request = $this->initRequest(
            session: ['userId' => 42],
            headers: ['X-CSRF-Token' => 'wrong-token']
        );

        $csrfManager->expects($this->once())
            ->method('validateToken')
            ->with('wrong-token')
            ->willReturn(false);

        $this->expectException(EventException::class);
        $this->expectExceptionMessage('CSRF token invalid or expired');
        $this->expectExceptionCode(403);

        $listener->execute(new CheckCsrfEvent($request));
    }

    public function testSessionAuthWithInvalidTokenResetsUser(): void
    {
        $csrfManager = $this->createMock(CsrfManager::class);
        $listener    = new CsrfValidationListener($csrfManager);
        $request = $this->initRequest(
            session: ['userId' => 42],
            headers: ['X-CSRF-Token' => 'bad-token']
        );

        $csrfManager->expects($this->once())->method('validateToken')->willReturn(false);

        try {
            $listener->execute(new CheckCsrfEvent($request));
            $this->fail('Expected EventException was not thrown');
        } catch (EventException) {
            // The listener must have called setUser(null) before throwing
            $this->assertNull(Request::getRequestInstance()->getUser());
        }
    }

    #[DataProvider('mutatingMethods')]
    public function testMutatingMethodsRequireCsrfForSession(string $method): void
    {
        $csrfManager = $this->createStub(CsrfManager::class);
        $listener    = new CsrfValidationListener($csrfManager);
        $request = $this->initRequest(
            method:  $method,
            session: ['userId' => 1]
            // no token supplied
        );
 
        $this->expectException(EventException::class);
        $this->expectExceptionCode(403);
        $csrfManager = $this->createStub(CsrfManager::class);
        $listener = new CsrfValidationListener($csrfManager);
        $listener->execute(new CheckCsrfEvent($request));
    }
}
