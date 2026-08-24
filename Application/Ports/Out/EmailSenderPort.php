<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Domain/Models/EmailDestinationModel.php';

interface EmailSenderPort
{
    public function send(EmailDestinationModel $destination): void;
}