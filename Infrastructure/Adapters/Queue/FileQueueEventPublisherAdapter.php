<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Application/Ports/Out/DomainEventPublisherPort.php';
require_once __DIR__ . '/../../../Domain/Events/EventDomain.php';

/**
 * Adaptador de infraestructura para publicación asíncrona de Eventos de Dominio en cola local JSONL.
 */
final class FileQueueEventPublisherAdapter implements DomainEventPublisherPort
{
    private string $queueFile;

    public function __construct(?string $queueFile = null)
    {
        $this->queueFile = $queueFile ?? dirname(__DIR__, 3) . '/storage/queue/domain_events.jsonl';
        $dir = dirname($this->queueFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    public function publish(DomainEvent $event): void
    {
        $dir = dirname($this->queueFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $envelope = array(
            'message_id' => bin2hex(random_bytes(8)),
            'event_name' => $event->eventName(),
            'occurred_on' => $event->occurredOn(),
            'payload' => $event->payload(),
        );

        @file_put_contents(
            $this->queueFile,
            json_encode($envelope, JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function readPublishedEvents(): array
    {
        if (!is_file($this->queueFile)) {
            return array();
        }

        $lines = file($this->queueFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return array();
        }

        $events = array();
        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $events[] = $decoded;
            }
        }

        return $events;
    }
}