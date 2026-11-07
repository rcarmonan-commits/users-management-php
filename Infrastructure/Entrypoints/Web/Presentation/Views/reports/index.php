<?php
/**
 * Centro de Reportes Analíticos CQRS — `Views/reports/index.php`
 * Proyecta vistas analíticas (Suma por Lugar, Rango de Fechas, Usuarios por Rol
 * y Consolidado por Usuario Responsable) consumiendo exclusivamente los casos de
 * uso de lectura (`GetAllGastosUseCase` y `GetAllUsersUseCase`).
 */
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/menu.php';
?>

<div class="page-header">
    <div>
        <span class="badge badge-emerald">CQRS Read Projections &middot; Cache-Aside</span>
        <h1>Centro de Reportes Analíticos y Auditoría Contable</h1>
        <p class="muted">Consultas ejecutivas desacopladas construidas sobre <code>GetAllGastosUseCase</code> y <code>GetAllUsersUseCase</code>.</p>
    </div>
    <div class="header-actions">
        <a class="btn btn-secondary" href="?route=gastos.index">Ver Gastos</a>
        <a class="btn btn-primary" href="?route=gastos.create">+ Registrar Gasto</a>
    </div>
</div>

<section class="kpi-grid">
    <div class="kpi-card">
        <span class="kpi-label">Total Gastos</span>
        <strong class="kpi-value"><?= (int) ($globalTotals['count'] ?? 0) ?></strong>
        <span class="kpi-foot">Ticket promedio: $<?= number_format((float) ($globalTotals['avgConIva'] ?? 0), 2, ',', '.') ?></span>
    </div>
    <div class="kpi-card">
        <span class="kpi-label">Base Gravable Global</span>
        <strong class="kpi-value">$<?= number_format((float) ($globalTotals['sinIva'] ?? 0), 2, ',', '.') ?></strong>
        <span class="kpi-foot">Suma acumulada Sin IVA</span>
    </div>
    <div class="kpi-card">
        <span class="kpi-label">IVA Global Soportado</span>
        <strong class="kpi-value">$<?= number_format((float) ($globalTotals['iva'] ?? 0), 2, ',', '.') ?></strong>
        <span class="kpi-foot">Validado al centavo en Dominio</span>
    </div>
    <div class="kpi-card kpi-highlight">
        <span class="kpi-label">Ejecución Presupuestal Total</span>
        <strong class="kpi-value">$<?= number_format((float) ($globalTotals['conIva'] ?? 0), 2, ',', '.') ?></strong>
        <span class="kpi-foot">Usuarios registrados: <?= (int) ($globalTotals['usersCount'] ?? 0) ?></span>
    </div>
</section>

<div class="reports-grid">
    <!-- MÓDULO 1: SUMA Y RANKING POR LUGAR -->
    <div class="card-panel">
        <h2>1. Suma de Gastos por Lugar / Establecimiento</h2>
        <p class="muted">Busca un establecimiento específico o inspecciona la participación porcentual por comercio.</p>

        <form method="GET" action="" class="filter-bar" style="margin-bottom: 14px;">
            <input type="hidden" name="route" value="reports">
            <input type="hidden" name="fechaDesde" value="<?= htmlspecialchars((string) ($filters['fechaDesde'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="fechaHasta" value="<?= htmlspecialchars((string) ($filters['fechaHasta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="rolFiltro" value="<?= htmlspecialchars((string) ($filters['rolFiltro'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="text" name="qLugar" placeholder="Ej: Éxito, Librería, Restaurante..."
                value="<?= htmlspecialchars((string) ($filters['qLugar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-primary">Consultar</button>
            <?php if (!empty($filters['qLugar'])): ?>
                <a class="btn btn-secondary" href="?route=reports">Limpiar</a>
            <?php endif; ?>
        </form>

        <?php if (!empty($filters['qLugar'])): ?>
            <div class="alert-success" style="margin-bottom: 14px;">
                Coincidencias para <strong>"<?= htmlspecialchars((string) $filters['qLugar'], ENT_QUOTES, 'UTF-8') ?>"</strong>:
                <strong><?= (int) ($lugarLookup['count'] ?? 0) ?></strong> gasto(s) &middot;
                Sin IVA: <strong>$<?= number_format((float) ($lugarLookup['sinIva'] ?? 0), 2, ',', '.') ?></strong> &middot;
                IVA: <strong>$<?= number_format((float) ($lugarLookup['iva'] ?? 0), 2, ',', '.') ?></strong> &middot;
                Total Con IVA: <strong>$<?= number_format((float) ($lugarLookup['conIva'] ?? 0), 2, ',', '.') ?></strong>
            </div>
        <?php endif; ?>

        <?php if (empty($byLugar)): ?>
            <p class="muted">No hay gastos registrados para calcular el ranking por lugar.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Lugar</th>
                            <th>Cant.</th>
                            <th class="col-money">Total Con IVA</th>
                            <th>% Part.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($byLugar as $row): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars((string) $row['lugar'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td><?= (int) $row['count'] ?></td>
                                <td class="col-money text-emerald"><strong>$<?= number_format((float) $row['conIva'], 2, ',', '.') ?></strong></td>
                                <td>
                                    <div class="progress-track" title="<?= number_format((float) $row['share'], 1) ?>%">
                                        <div class="progress-fill" style="width: <?= max(2, min(100, (int) round((float) $row['share']))) ?>%;"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- MÓDULO 3: AUDITORÍA DE USUARIOS POR ROL -->
    <div class="card-panel">
        <h2>2. Directorio y Auditoría de Usuarios por Rol</h2>
        <p class="muted">Filtra cuentas según su rol de dominio (<code>ADMIN</code>, <code>MEMBER</code>, <code>REVIEWER</code>).</p>

        <form method="GET" action="" class="filter-bar" style="margin-bottom: 14px;">
            <input type="hidden" name="route" value="reports">
            <input type="hidden" name="qLugar" value="<?= htmlspecialchars((string) ($filters['qLugar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="fechaDesde" value="<?= htmlspecialchars((string) ($filters['fechaDesde'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="fechaHasta" value="<?= htmlspecialchars((string) ($filters['fechaHasta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <select name="rolFiltro">
                <option value="">Todos los roles (<?= (int) ($globalTotals['usersCount'] ?? 0) ?>)</option>
                <?php foreach (($roleCounts ?? array()) as $roleKey => $rCount): ?>
                    <option value="<?= htmlspecialchars((string) $roleKey, ENT_QUOTES, 'UTF-8') ?>"
                        <?= (($filters['rolFiltro'] ?? '') === $roleKey) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $roleKey, ENT_QUOTES, 'UTF-8') ?> (<?= (int) $rCount ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filtrar Rol</button>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($filteredUsers ?? array()) as $u): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u->getName(), ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars($u->getEmail(), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge <?= $u->getRole() === 'ADMIN' ? 'badge-admin' : ($u->getRole() === 'REVIEWER' ? 'badge-reviewer' : 'badge-member') ?>">
                                    <?= htmlspecialchars($u->getRole(), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $u->getStatus() === 'ACTIVE' ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= htmlspecialchars($u->getStatus(), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MÓDULO 2: REPORTE POR RANGO DE FECHAS -->
<div class="card-panel" style="margin-bottom: 22px;">
    <h2>3. Reporte Contable de Gastos por Rango de Fechas</h2>
    <p class="muted">Ejecuta <code>GetAllGastosQuery(null, fechaDesde, fechaHasta)</code> sobre el puerto de salida activo.</p>

    <form method="GET" action="" class="filter-bar" style="margin-bottom: 14px;">
        <input type="hidden" name="route" value="reports">
        <input type="hidden" name="qLugar" value="<?= htmlspecialchars((string) ($filters['qLugar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="rolFiltro" value="<?= htmlspecialchars((string) ($filters['rolFiltro'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <input type="date" name="fechaDesde" value="<?= htmlspecialchars((string) ($filters['fechaDesde'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <input type="date" name="fechaHasta" value="<?= htmlspecialchars((string) ($filters['fechaHasta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" class="btn btn-primary">Aplicar Rango</button>
        <a class="btn btn-secondary" href="?route=reports">Restablecer</a>
    </form>

    <p class="muted" style="margin-bottom: 12px;">
        Mostrando <strong><?= count($dateRangeGastos ?? array()) ?></strong> registro(s) &middot;
        Subtotal Sin IVA: <strong>$<?= number_format((float) ($dateRangeTotals['sinIva'] ?? 0), 2, ',', '.') ?></strong> &middot;
        IVA: <strong>$<?= number_format((float) ($dateRangeTotals['iva'] ?? 0), 2, ',', '.') ?></strong> &middot;
        Total Con IVA: <strong class="text-emerald">$<?= number_format((float) ($dateRangeTotals['conIva'] ?? 0), 2, ',', '.') ?></strong>
    </p>

    <?php if (!empty($dateRangeGastos)): ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Lugar</th>
                        <th>Descripción</th>
                        <th class="col-money">Sin IVA</th>
                        <th class="col-money">IVA</th>
                        <th class="col-money">Total Con IVA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dateRangeGastos as $g): ?>
                        <tr>
                            <td><?= htmlspecialchars($g->getFecha(), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($g->getNombreUsuario(), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><strong><?= htmlspecialchars($g->getLugar(), ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars($g->getDescripcion(), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="col-money">$<?= number_format($g->getValorTotalSinIVA(), 2, ',', '.') ?></td>
                            <td class="col-money">$<?= number_format($g->getIvaTotal(), 2, ',', '.') ?></td>
                            <td class="col-money text-emerald"><strong>$<?= number_format($g->getValorTotalConIVA(), 2, ',', '.') ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- MÓDULO 4: CONSOLIDADO POR USUARIO RESPONSABLE -->
<div class="card-panel">
    <h2>4. Resumen Ejecutivo por Usuario Responsable</h2>
    <p class="muted">Agrupación contable por responsable para control interno y auditoría presupuestal.</p>

    <?php if (empty($byUser)): ?>
        <p class="muted">Sin movimientos registrados.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Usuario Responsable</th>
                        <th>Operaciones</th>
                        <th class="col-money">Subtotal Sin IVA</th>
                        <th class="col-money">IVA Total</th>
                        <th class="col-money">Total Con IVA</th>
                        <th>% Presupuesto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($byUser as $uRow): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string) $uRow['usuario'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= (int) $uRow['count'] ?></td>
                            <td class="col-money">$<?= number_format((float) $uRow['sinIva'], 2, ',', '.') ?></td>
                            <td class="col-money">$<?= number_format((float) $uRow['iva'], 2, ',', '.') ?></td>
                            <td class="col-money text-emerald"><strong>$<?= number_format((float) $uRow['conIva'], 2, ',', '.') ?></strong></td>
                            <td><?= number_format((float) $uRow['share'], 1, ',', '.') ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>