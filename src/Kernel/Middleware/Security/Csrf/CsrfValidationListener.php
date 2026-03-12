<?php

namespace App\Kernel\Middleware\Security\Csrf;

use App\Kernel\Request;
use App\Kernel\Security\CsrfManager;
use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;
use App\Kernel\Psr14\Exceptions\EventException;

class CsrfValidationListener implements ListenerInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(
        private readonly CsrfManager $csrfManager,
    ) {}

    public function execute(StoppableEventInterface $event): void
    {
        /**@var CheckCsrfEvent $event*/
        $request = $event->request;
        if (in_array($request->getMethod(), self::SAFE_METHODS, strict: true)) {
            return;
        }

         if ($this->isBearerAuth($request)) {
            return;
        }

        if ($this->isSessionAuth($request)) {
            $token = $this->extractToken($request);
            if ($token === null || !$this->csrfManager->validateToken($token)) {
                $reason = $token === null ? 'CSRF token missing' : 'CSRF token invalid or expired';
                Request::getRequestInstance()->setUser(null);
                throw new EventException($reason, 403);
            }
        }

    }

    private function isBearerAuth(Request $request): bool
    {
        $header = $request->getHeaders('Authorization');
        if($header) {
            $header = strtolower($header);
            return str_starts_with($header, 'bearer ');
        }
        return false;
    }

    private function isSessionAuth(Request $request): bool
    {
        $session = $request->getSessionValue('userId');
        return null !== $session;
    }

    private function extractToken(Request $request): ?string
    {
        // 1. Header (AJAX)
        $headerLine = $request->getHeaders('X-CSRF-Token');
        if (null !== $headerLine) {
            return $headerLine;
        }

        // 2. JSON body
        $body = $request->getAllDatas();
        if (is_array($body) && isset($body['_csrf_token'])) {
            return $body['_csrf_token'];
        }

        return null;
    }
}
