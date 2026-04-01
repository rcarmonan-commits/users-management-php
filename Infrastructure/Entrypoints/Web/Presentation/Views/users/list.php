<?php require __DIR__ . '/../layouts/header.php'; ?>
<?php require __DIR__ . '/../layouts/menu.php'; ?>

<h1>Lista de usuarios</h1>

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
    <p>No hay usuarios registrados todavía.</p>
<?php else: ?>
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
                <tr>
                    <td class="col-id"><?= htmlspecialchars($user->getId(), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($user->getName(), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($user->getEmail(), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($user->getRole(), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($user->getStatus(), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="col-actions">
                        <a class="btn btn-sm" href="?route=users.show&amp;id=<?= urlencode($user->getId()) ?>">Ver</a>
                        <a class="btn btn-warning btn-sm"
                            href="?route=users.edit&amp;id=<?= urlencode($user->getId()) ?>">Editar</a>
                        <form method="POST" action="?route=users.delete" style="display:inline;"
                            onsubmit="return confirmDelete(this);"
                            data-name="<?= htmlspecialchars($user->getName(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($user->getId(), ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/../layouts/footer.php'; ?>