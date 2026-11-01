<?php
declare(strict_types=1);
/**
 * Layout Superior — Slate Dark Enterprise Theme
 * Escapa siempre `$pageTitle` con `ENT_QUOTES` / `UTF-8` contra XSS reflejado.
 */
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Control de Gastos #6 · Arquitectura Hexagonal PHP', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="?asset=css">
</head>

<body>
<div class="top-banner">
    ARQUITECTURA HEXAGONAL &middot; DDD &middot; CQRS &middot; SEGURIDAD OWASP TOP 10 &mdash; PHP 8.3 | UNIVERSIDAD DE CARTAGENA
</div>
<main class="app-shell">