<?php

use App\Kernel\Request;
use PHPUnit\Framework\TestCase;
use App\Kernel\Security\CsrfToken;
use App\Kernel\Security\CsrfManager;
use PHPUnit\Framework\MockObject\Stub\Stub;
use App\Kernel\Interfaces\ResponseInterface;
use PHPUnit\Framework\MockObject\MockObject;
use App\Kernel\Psr14\Events\ReturnResponseKernelEvent;
use App\Kernel\Middleware\Security\Csrf\CsrfTokenInjectorListener;

class CsrfTokenInjectionTest extends TestCase
{
    private array $baseServer = [
        'REQUEST_METHOD'  => 'POST',
        'REQUEST_URI'     => '/api/resource',
        'HTTP_HOST'       => 'localhost:8000',
        'SERVER_PROTOCOL' => 'HTTP/1.1',
    ];
    private CsrfManager&MockObject $csrfManager;
    // private ResponseInterface&MockObject $response;
    private CsrfTokenInjectorListener $listener;

    protected function setUp(): void
    {
        //$this->response    = $this->createMock(ResponseInterface::class);
    }

    private function makeEvent(?ResponseInterface $response = null): ReturnResponseKernelEvent
    {
        return new ReturnResponseKernelEvent(
            $response,
            Request::getRequestInstance()
        );
    }

    private function initRequestWithSession(array $session): void
    {
        Request::resetInstance();
        Request::initInstance($this->baseServer, [], [], [], [], $session, []);
    }

    protected function tearDown(): void
    {
        Request::resetInstance();
    }

    public function testNoTokenInjectedForAnonymousUser(): void
    {
        $response    = $this->createMock(ResponseInterface::class);
        $this->csrfManager = $this->createMock(CsrfManager::class);
        $this->listener    = new CsrfTokenInjectorListener($this->csrfManager);
        // No userId in session → anonymous
        Request::resetInstance();
        Request::initInstance($this->baseServer, [], [], [], [], [], []);

        $this->csrfManager->expects($this->never())->method('getOrCreateToken');
        $response->expects($this->never())->method('setHeader');

        $this->listener->execute($this->makeEvent($response));
    }

    public function testTokenIsInjectedForSessionUser(): void
    {
        $response    = $this->createMock(ResponseInterface::class);
        $this->initRequestWithSession(['userId' => 42]);
        $csrfManager = $this->createStub(CsrfManager::class);
        $this->listener  = new CsrfTokenInjectorListener($csrfManager);
        $token = new CsrfToken('abc123tokenvalue', time() + 3600);
        $csrfManager->method('getOrCreateToken')->willReturn($token);

        $response->expects($this->once())
            ->method('setHeader')
            ->with('X-CSRF-Token', 'abc123tokenvalue');

        $this->listener->execute($this->makeEvent($response));
    }

    public function testTokenValueMatchesCsrfManagerOutput(): void
    {
        $this->initRequestWithSession(['userId' => 7]);
        $csrfManager = $this->createStub(CsrfManager::class);
        $response = $this->createStub(ResponseInterface::class);
        $this->listener  = new CsrfTokenInjectorListener($csrfManager);
        $expectedValue = bin2hex(random_bytes(16));
        $token = new CsrfToken($expectedValue, time() + 3600);
        $csrfManager->method('getOrCreateToken')->willReturn($token);

        $capturedValue = null;
        $response->method('setHeader')
            ->willReturnCallback(function (string $name, string $value) use (&$capturedValue, $response) {
                $capturedValue = $value;
                return $response;
            });

        $this->listener->execute($this->makeEvent($response));

        $this->assertSame($expectedValue, $capturedValue);
    }

    public function testGetOrCreateTokenCalledExactlyOncePerRequest(): void
    {
        $this->initRequestWithSession(['userId' => 1]);
        $csrfManager = $this->createMock(CsrfManager::class);
        $this->listener  = new CsrfTokenInjectorListener($csrfManager);
        $response = $this->createStub(ResponseInterface::class);
        $token = new CsrfToken('once-only', time() + 3600);
        $csrfManager->expects($this->once())
            ->method('getOrCreateToken')
            ->willReturn($token);

        $response->method('setHeader')->willReturnSelf();

        $this->listener->execute($this->makeEvent($response));
    }

    public function testHeaderNameIsCorrect(): void
    {
        $this->initRequestWithSession(['userId' => 3]);
        $response = $this->createStub(ResponseInterface::class);
        $token = new CsrfToken('sometoken', time() + 3600);
        $csrfManager = $this->createStub(CsrfManager::class);
        $csrfManager->method('getOrCreateToken')->willReturn($token);
        $this->listener  = new CsrfTokenInjectorListener($csrfManager);
        $capturedHeader = null;
        $response->method('setHeader')
            ->willReturnCallback(function (string $name, string $value) use (&$capturedHeader, $response) {
                $capturedHeader = $name;
                return $response;
            });

        $this->listener->execute($this->makeEvent($response));

        $this->assertSame('X-CSRF-Token', $capturedHeader);
    }
}
