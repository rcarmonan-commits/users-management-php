<?php
/**
 * Manual Técnico e Interactivo — `Views/about.php`
 * Sirve como guía viva dentro de la aplicación explicando el cumplimiento quirúrgico
 * de Arquitectura Hexagonal, DDD, CQRS, SOLID y Seguridad Antivulneraciones OWASP.
 */
require __DIR__ . '/layouts/header.php';
require __DIR__ . '/layouts/menu.php';
?>

<section class="hero-card">
    <div>
        <span class="badge badge-emerald">Manual Pedagógico &amp; Guía de Buenas Prácticas</span>
        <h1>Guía de Arquitectura Hexagonal, DDD, CQRS y Seguridad OWASP</h1>
        <p class="subtitle">
            Documentación viva de cómo está diseñado cada módulo desde el núcleo de negocio hacia los adaptadores tecnológicos,
            por qué respeta SOLID/Clean Code y cómo protege la integridad de los datos en producción.
        </p>
    </div>
    <div class="hero-actions">
        <a class="btn btn-primary" href="?route=swagger" target="_blank" rel="noopener">Abrir Swagger UI</a>
        <a class="btn btn-secondary" href="/api/health" target="_blank" rel="noopener">JSON /api/health</a>
    </div>
</section>

<div class="dashboard-grid" style="margin-bottom: 22px;">
    <div class="card-panel">
        <h2>1. Capa de Dominio (<code>Domain/</code>)</h2>
        <p class="muted"><strong>¿Qué hace?</strong> Modela las entidades <code>UserModel</code> y <code>GastoModel</code> (#6) junto a sus Value Objects inmutables, Enums, Eventos y Excepciones.</p>
        <ul class="feature-list">
            <li><strong>Cero dependencias externas:</strong> No importa PDO, SQL, HTTP ni librerías de terceros.</li>
            <li><strong>Aritmética exacta:</strong> <code>GastoMonto</code> opera en centavos enteros evitando errores binarios IEEE-754.</li>
            <li><strong>Invariante contable:</strong> <code>GastoModel</code> exige siempre <code>valorTotalSinIVA + ivaTotal === valorTotalConIVA</code>.</li>
        </ul>
    </div>

    <div class="card-panel">
        <h2>2. Capa de Aplicación y CQRS (<code>Application/</code>)</h2>
        <p class="muted"><strong>¿Cómo funciona?</strong> Separa comandos de escritura (<code>Commands/</code>) y consultas de lectura (<code>Queries/</code>).</p>
        <ul class="feature-list">
            <li><strong>Puertos de Entrada (<code>Ports/In</code>):</strong> Contratos <code>*UseCase</code> invocados por Web y REST.</li>
            <li><strong>Puertos de Salida (<code>Ports/Out</code>):</strong> Contratos de persistencia, <code>CachePort</code>, <code>DomainEventPublisherPort</code>, <code>TaxIndicatorRestPort</code> y <code>EmailSenderPort</code>.</li>
            <li><strong>Orquestación:</strong> <code>CreateGastoService</code> persiste, invalida caché y publica <code>GastoCreatedDomainEvent</code>.</li>
        </ul>
    </div>

    <div class="card-panel">
        <h2>3. Adaptadores de Infraestructura (<code>Infrastructure/Adapters/</code>)</h2>
        <p class="muted"><strong>¿Por qué se hace así?</strong> Permite intercambiar tecnologías sin tocar reglas de negocio (Principio DIP).</p>
        <ul class="feature-list">
            <li><strong>Persistencia Dual:</strong> <code>UserRepositoryMySQL</code> / <code>GastoRepositoryMySQL</code> (PDO preparado) e <code>InMemory*Repository</code>.</li>
            <li><strong>Caché y Colas:</strong> <code>FileCacheAdapter</code> (TTL) y <code>FileQueueEventPublisherAdapter</code> (JSONL).</li>
            <li><strong>Correo Resiliente:</strong> <code>SmtpConfigRepositoryMySQL</code> + <code>EmailSenderService</code> (IPv4 587/465, API HTTPS 443 y Outbox).</li>
        </ul>
    </div>

    <div class="card-panel">
        <h2>4. Blindaje Antivulneraciones OWASP Top 10 (<code>SecurityGuard</code>)</h2>
        <p class="muted"><strong>Defensa en profundidad</strong> transversal sobre Web y API REST:</p>
        <ul class="feature-list">
            <li><strong>API2 JWT HS256 &amp; Revocación en Vivo:</strong> Todo endpoint protegido exige <code>Authorization: Bearer &lt;JWT&gt;</code> firmado con <code>HMAC-SHA256</code>, bloqueando peticiones sin token (<code>401</code>), <code>alg: none</code>, expiración y cuentas revocadas.</li>
            <li><strong>API1/API5 RBAC &amp; Anti-BOLA/CSRF:</strong> Control estricto por rol (<code>403</code>), protección anti-IDOR y tokens sincronizadores de 256 bits con <code>hash_equals()</code>.</li>
            <li><strong>A03/A05 Anti-Inyección &amp; Headers:</strong> PDO 100% parametrizado, filtro anti-CRLF, <code>Content-Security-Policy</code>, <code>SAMEORIGIN</code> y <code>nosniff</code>.</li>
            <li><strong>A07 Autenticación Robusta:</strong> BCrypt, <code>session_regenerate_id(true)</code>, <em>Rate Limiting</em> (<code>429</code>) y ecualización de tiempos contra enumeración.</li>
        </ul>
    </div>
</div>

<?php require __DIR__ . '/layouts/footer.php'; ?>