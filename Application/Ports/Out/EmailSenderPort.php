<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Domain/Models/EmailDestinationModel.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — PUERTO DE SALIDA (DRIVEN PORT): EmailSenderPort
 * ============================================================================
 * CAPA: Aplicación (`Application/Ports/Out`)
 * PATRÓN: Arquitectura Hexagonal (Ports & Adapters) + Inversión de Dependencias (SOLID - DIP)
 *
 * ¿QUÉ HACE?
 *   Define el contrato abstracto mediante el cual el núcleo de Aplicación solicita
 *   el despacho de un correo electrónico representado por `EmailDestinationModel`.
 *
 * ¿CÓMO LO HACE?
 *   Declara un único método `send(EmailDestinationModel $destination): void` libre
 *   de detalles tecnológicos (no menciona PHPMailer, sockets TCP ni claves API).
 *
 * ¿POR QUÉ SE HACE ASÍ?
 *   Permite cambiar o combinar transportes en Infraestructura (SMTP puerto 587/465,
 *   API HTTPS 443 o bandeja local Outbox para pruebas) sin tocar ni una sola línea
 *   de los casos de uso de negocio.
 * ============================================================================
 */
interface EmailSenderPort
{
    public function send(EmailDestinationModel $destination): void;
}