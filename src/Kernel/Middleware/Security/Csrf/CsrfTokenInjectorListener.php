<?php

namespace App\Kernel\Middleware\Security\Csrf;

use App\Kernel\Security\CsrfManager;
use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;
use App\Kernel\Request;

class CsrfTokenInjectorListener implements ListenerInterface
{
    public function __construct(
        private readonly CsrfManager $csrfManager
    ) {}

    /**
     * Undocumented function
     *
     * @param ReturnResponseKernelEvent $event
     * @return void
     */
    public function execute(StoppableEventInterface $event): void
    {
        $sessionUser = Request::getRequestInstance()->getSessionValue('userId');
        if(null === $sessionUser) {
            return;
        }

        $token = $this->csrfManager->getOrCreateToken();
        // Inject in response header
        $response = $event->getResponse();
        /**@var ResponseInterface $response */
        $response->setHeader('X-CSRF-Token', $token->value);
    }
}