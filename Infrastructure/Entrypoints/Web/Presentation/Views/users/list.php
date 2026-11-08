<?php
/**
 * Directorio de Usuarios — `Views/users/list.php`
 * - Aplica RBAC visual y de formulario: solo `ADMIN` puede eliminar cuentas,
 *   y nunca se permite auto-eliminar la sesión propia activa.
 */
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/menu.php';
$currentAuth = $_SESSION['auth'] ?? array();
$isAdminViewer = (($currentAuth['role'] ?? '') === 'ADMIN');
$currentUserId = (string) ($currentAuth['id'] ?? '');
?>

<div class="page-header">
    <div>
        <h1>Directorio de Usuarios Registrados</h1>
        <p class="muted">Consulta CQRS <code>GetAllUsersQuery</code> con control de acceso por roles (RBAC).</p>
    </div>
    <div class="header-actions">
        <a class="btn btn-secondary" href="?route=reports">Auditoría por Rol</a>
        <a class="btn btn-primary" href="?route=users.create">+ Registrar Usuario</a>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alert-error">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert-success">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (empty($users)): ?>
    <div class="card-panel empty-state">
        <p>No hay usuarios registrados todavía.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php
                    $rowId = $user->getId();
                    $canEditRow = $isAdminViewer || ($rowId === $currentUserId);
                    $canDeleteRow = $isAdminViewer && ($rowId !== $currentUserId);
                    ?>
                    <tr>
                        <td class="col-id"><?= htmlspecialchars($rowId, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><strong><?= htmlspecialchars($user->getName(), ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td><?= htmlspecialchars($user->getEmail(), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge <?= $user->getRole() === 'ADMIN' ? 'badge-admin' : ($user->getRole() === 'REVIEWER' ? 'badge-reviewer' : 'badge-member') ?>">
                                <?= htmlspecialchars($user->getRole(), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $user->getStatus() === 'ACTIVE' ? 'badge-active' : 'badge-inactive' ?>">
                                <?= htmlspecialchars($user->getStatus(), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="col-actions">
                            <a class="btn btn-secondary btn-sm" href="?route=users.show&amp;id=<?= urlencode($rowId) ?>">Ver</a>
                            <?php if ($canEditRow): ?>
                                <a class="btn btn-warning btn-sm" href="?route=users.edit&amp;id=<?= urlencode($rowId) ?>">Editar</a>
                            <?php endif; ?>
                            <?php if ($canDeleteRow): ?>
                                <form method="POST" action="?route=users.delete" class="inline-form"
                                    onsubmit="return confirmDelete(this);"
                                    data-name="<?= htmlspecialchars($user->getName(), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= SecurityGuard::csrfField() ?>
                                    <input type="hidden" name="id" value="<?= htmlspecialchars($rowId, ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>