<?php
/**
 * Vista de Detalle de Usuario — `Views/users/show.php`
 * Alimentada por `GetUserByIdQuery`.
 */
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/menu.php';
$currentAuthShow = $_SESSION['auth'] ?? array();
$canEditProfile = (($currentAuthShow['role'] ?? '') === 'ADMIN') || (($currentAuthShow['id'] ?? '') === $user->getId());
?>

<div class="page-header">
    <div>
        <h1>Ficha de Usuario</h1>
        <p class="muted">Consulta individual CQRS <code>GetUserByIdQuery</code>.</p>
    </div>
    <div class="header-actions">
        <?php if ($canEditProfile): ?>
            <a class="btn btn-warning" href="?route=users.edit&amp;id=<?= urlencode($user->getId()) ?>">Editar Usuario</a>
        <?php endif; ?>
        <a class="btn btn-secondary" href="?route=users.index">&larr; Volver al directorio</a>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert-error">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div class="card-panel">
    <table class="detail-table">
        <tr>
            <th>Identificador (ID)</th>
            <td class="col-id"><?= htmlspecialchars($user->getId(), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <th>Nombre Completo</th>
            <td><strong><?= htmlspecialchars($user->getName(), ENT_QUOTES, 'UTF-8') ?></strong></td>
        </tr>
        <tr>
            <th>Correo Electrónico</th>
            <td><?= htmlspecialchars($user->getEmail(), ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <tr>
            <th>Rol Asignado</th>
            <td>
                <span class="badge <?= $user->getRole() === 'ADMIN' ? 'badge-admin' : ($user->getRole() === 'REVIEWER' ? 'badge-reviewer' : 'badge-member') ?>">
                    <?= htmlspecialchars($user->getRole(), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </td>
        </tr>
        <tr>
            <th>Estado de Cuenta</th>
            <td>
                <span class="badge <?= $user->getStatus() === 'ACTIVE' ? 'badge-active' : 'badge-inactive' ?>">
                    <?= htmlspecialchars($user->getStatus(), ENT_QUOTES, 'UTF-8') ?>
                </span>
            </td>
        </tr>
    </table>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>