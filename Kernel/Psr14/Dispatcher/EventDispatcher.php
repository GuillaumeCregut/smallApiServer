<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel\Psr14\Dispatcher;

use App\Kernel\Psr14\Exceptions\EventException;
use App\Kernel\Interfaces\Psr14\ListenerInterface;
use App\Kernel\Interfaces\Psr14\StoppableEventInterface;
use App\Kernel\Interfaces\Psr14\EventDispatcherInterface;
use App\Kernel\Interfaces\Psr14\ListenerProviderInterface;

class EventDispatcher implements EventDispatcherInterface
{
    private static ?EventDispatcher $instance = null;
    private ListenerProviderInterface $listenerProvider;

    public function __construct(ListenerProviderInterface $listenerProvider)
    {
        $this->listenerProvider = $listenerProvider;
    }
    public static function getInstance(?ListenerProviderInterface $listenerProvider=null): EventDispatcher
    {
        if(null === self::$instance) {
            if(null === $listenerProvider) {
                throw new EventException('No provider for create Dispatcher');
            }
            self::$instance = new EventDispatcher($listenerProvider);
        }
        return self::$instance;
    }

    public function dispatch(object $event): object
    {
        if (!($event instanceof StoppableEventInterface)) {
            throw new EventException('Not a Stoppable Event');
        }
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            if (!($listener instanceof ListenerInterface)) {
                throw new \Exception('Listener must implement ListenerInterface');
            }
            if ($event->isPropagationStopped()) {
                return $event;
            }
            $listener->execute($event);
        }
        return $event;
    }

    public static function resetInstance(): void
    {
        self::$instance=null;
    }
}