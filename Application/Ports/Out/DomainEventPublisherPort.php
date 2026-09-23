<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Domain/Events/EventDomain.php';

interface DomainEventPublisherPort
{
    public function publish(DomainEvent $event): void;
}