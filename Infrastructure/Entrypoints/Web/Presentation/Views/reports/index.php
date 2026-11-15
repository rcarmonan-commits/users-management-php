<?php
/**
 * Centro de Reportes Analíticos CQRS — `Views/reports/index.php`
 * Proyecta vistas analíticas desacopladas (Auditoría Fiscal de IVA, Ranking por Lugar,
 * Consolidado por Usuario, Evolución Temporal Mensual, Top 5 Gastos de Mayor Impacto,
 * Detalle Filtrado y Directorio por Rol) consumiendo exclusivamente los casos de
 * uso de lectura (`GetAllGastosUseCase` y `GetAllUsersUseCase`).
 */
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/menu.php';

$buildFilterUrl = static function(array $overrides = array()) use ($filters): string {
    $merged = array_merge($filters, $overrides);
    $params = array('route' => 'reports');
    foreach ($merged as $k => $v) {
        if ($v !== '' && $v !== null) {
            $params[$k] = $v;
        }
    }
    return '?' . http_build_query($params);
};
?>

<div class="page-header">
    <div>
        <span class="badge badge-emerald">CQRS Read Projections &middot; Cache-Aside</span>
        <h1>Centro de Reportes Analíticos y Auditoría Contable</h1>
        <p class="muted">Consultas ejecutivas desacopladas construidas sobre <code>GetAllGastosUseCase</code> y <code>GetAllUsersUseCase</code>.</p>
    </div>
    <div class="header-actions">
        <a class="btn btn-secondary" href="<?= htmlspecialchars($buildFilterUrl(array('export' => 'csv')), ENT_QUOTES, 'UTF-8') ?>">📥 Exportar CSV</a>
        <button type="button" class="btn btn-secondary" onclick="window.print()">🖨️ Imprimir</button>
        <a class="btn btn-primary" href="?route=gastos.create">+ Registrar Gasto</a>
    </div>
</div>

<!-- BARRA DE FILTROS UNIFICADA CQRS -->
<div class="card-panel" style="margin-bottom: 22px;">
    <h2>Filtros de Consulta CQRS Multi-Criterio</h2>
    <p class="muted">Aplica filtros combinados sobre la proyección de lectura sin alterar el estado del almacén.</p>

    <form method="GET" action="" class="filter-bar">
        <input type="hidden" name="route" value="reports">
        <input type="hidden" name="rolFiltro" value="<?= htmlspecialchars((string) ($filters['rolFiltro'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

        <div style="display: flex; flex-wrap: wrap; gap: 12px; width: 100%;">
            <div style="flex: 1; min-width: 170px;">
                <label for="qLugar" style="font-size: 0.8rem; margin-bottom: 4px;">Lugar / Comercio</label>
                <input type="text" id="qLugar" name="qLugar" placeholder="ej: Éxito, Librería..."
                    value="<?= htmlspecialchars((string) ($filters['qLugar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%;">
            </div>

            <div style="flex: 1; min-width: 145px;">
                <label for="fechaDesde" style="font-size: 0.8rem; margin-bottom: 4px;">Fecha Desde</label>
                <input type="date" id="fechaDesde" name="fechaDesde"
                    value="<?= htmlspecialchars((string) ($filters['fechaDesde'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%;">
            </div>

            <div style="flex: 1; min-width: 145px;">
                <label for="fechaHasta" style="font-size: 0.8rem; margin-bottom: 4px;">Fecha Hasta</label>
                <input type="date" id="fechaHasta" name="fechaHasta"
                    value="<?= htmlspecialchars((string) ($filters['fechaHasta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%;">
            </div>

            <div style="flex: 1; min-width: 160px;">
                <label for="usuarioFiltro" style="font-size: 0.8rem; margin-bottom: 4px;">Usuario Responsable</label>
                <select id="usuarioFiltro" name="usuarioFiltro" style="width: 100%;">
                    <option value="">Todos los usuarios</option>
                    <?php foreach (($distinctUserNames ?? array()) as $uName): ?>
                        <option value="<?= htmlspecialchars($uName, ENT_QUOTES, 'UTF-8') ?>" <?= (($filters['usuarioFiltro'] ?? '') === $uName) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($uName, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="flex: 1; min-width: 140px;">
                <label for="tarifaIva" style="font-size: 0.8rem; margin-bottom: 4px;">Tarifa de IVA</label>
                <select id="tarifaIva" name="tarifaIva" style="width: 100%;">
                    <option value="">Todas las tarifas</option>
                    <option value="19" <?= (($filters['tarifaIva'] ?? '') === '19') ? 'selected' : '' ?>>19% General</option>
                    <option value="5" <?= (($filters['tarifaIva'] ?? '') === '5') ? 'selected' : '' ?>>5% Reducido</option>
                    <option value="0" <?= (($filters['tarifaIva'] ?? '') === '0') ? 'selected' : '' ?>>0% Exento</option>
                    <option value="other" <?= (($filters['tarifaIva'] ?? '') === 'other') ? 'selected' : '' ?>>Otras Tarifas</option>
                </select>
            </div>

            <div style="flex: 1; min-width: 120px;">
                <label for="montoMin" style="font-size: 0.8rem; margin-bottom: 4px;">Monto Mín ($)</label>
                <input type="number" step="0.01" id="montoMin" name="montoMin" placeholder="0.00"
                    value="<?= htmlspecialchars((string) ($filters['montoMin'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%;">
            </div>

            <div style="flex: 1; min-width: 120px;">
                <label for="montoMax" style="font-size: 0.8rem; margin-bottom: 4px;">Monto Máx ($)</label>
                <input type="number" step="0.01" id="montoMax" name="montoMax" placeholder="Sin límite"
                    value="<?= htmlspecialchars((string) ($filters['montoMax'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width: 100%;">
            </div>
        </div>

        <div class="form-actions" style="margin-top: 14px; width: 100%; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <button type="submit" class="btn btn-primary">🔍 Aplicar Filtros CQRS</button>
            <a class="btn btn-secondary" href="?route=reports">Restablecer</a>
            <a class="btn btn-secondary" href="<?= htmlspecialchars($buildFilterUrl(array('export' => 'csv')), ENT_QUOTES, 'UTF-8') ?>">📥 Exportar CSV</a>
            <button type="button" class="btn btn-secondary" onclick="window.print()">🖨️ Imprimir</button>
        </div>
    </form>

    <?php if (!empty($hasActiveFilters)): ?>
        <div class="filter-chips-bar">
            <span style="font-size: 0.8rem; color: #94a3b8; font-weight: 600;">Filtros Activos:</span>
            <?php foreach ($activeFilters as $af): ?>
                <span class="filter-chip">
                    <?= htmlspecialchars($af['label'], ENT_QUOTES, 'UTF-8') ?>:
                    <?php /* 'value' llega YA escapado desde buildReportsViewData() (public/index.php) */ ?>
                    <strong><?= $af['value'] ?></strong>
                    <?php
                    $clearParam = $af['param'];
                    $removeUrl = match ($clearParam) {
                        'fechaRango' => $buildFilterUrl(array('fechaDesde' => '', 'fechaHasta' => '')),
                        'montoRango' => $buildFilterUrl(array('montoMin' => '', 'montoMax' => '')),
                        default => $buildFilterUrl(array($clearParam => '')),
                    };
                    ?>
                    <a href="<?= htmlspecialchars($removeUrl, ENT_QUOTES, 'UTF-8') ?>" class="filter-chip-remove" title="Quitar filtro">&times;</a>
                </span>
            <?php endforeach; ?>
            <a href="?route=reports" class="filter-clear-all">Limpiar todos</a>
        </div>
    <?php endif; ?>
</div>

<!-- KPIS EJECUTIVOS (FILTRADOS VS GLOBALES) -->
<section class="kpi-grid">
    <div class="kpi-card">
        <span class="kpi-label">Gastos Filtrados</span>
        <strong class="kpi-value"><?= (int) ($filteredTotals['count'] ?? 0) ?> <small style="font-size: 0.9rem; font-weight: normal; color: #94a3b8;">/ <?= (int) ($globalTotals['count'] ?? 0) ?></small></strong>
        <span class="kpi-foot">Ticket medio: $<?= number_format((float) ($filteredTotals['avgConIva'] ?? 0), 2, ',', '.') ?></span>
    </div>
    <div class="kpi-card">
        <span class="kpi-label">Base Gravable Sin IVA</span>
        <strong class="kpi-value">$<?= number_format((float) ($filteredTotals['sinIva'] ?? 0), 2, ',', '.') ?></strong>
        <span class="kpi-foot">Global: $<?= number_format((float) ($globalTotals['sinIva'] ?? 0), 2, ',', '.') ?></span>
    </div>
    <div class="kpi-card">
        <span class="kpi-label">IVA Soportado / Liquidado</span>
        <strong class="kpi-value">$<?= number_format((float) ($filteredTotals['iva'] ?? 0), 2, ',', '.') ?></strong>
        <span class="kpi-foot">Calculado al centavo con precisión fiscal</span>
    </div>
    <div class="kpi-card kpi-highlight">
        <span class="kpi-label">Ejecución Presupuestal Total</span>
        <strong class="kpi-value">$<?= number_format((float) ($filteredTotals['conIva'] ?? 0), 2, ',', '.') ?></strong>
        <span class="kpi-foot">Mín: $<?= number_format((float) ($filteredTotals['min'] ?? 0), 2, ',', '.') ?> &middot; Máx: $<?= number_format((float) ($filteredTotals['max'] ?? 0), 2, ',', '.') ?></span>
    </div>
</section>

<div class="reports-grid">
    <!-- MÓDULO 1: AUDITORÍA FISCAL Y DESGLOSE DE IVA -->
    <div class="card-panel">
        <h2>1. Auditoría Fiscal y Desglose por Tarifa de IVA</h2>
        <p class="muted">Conciliación contable por tarifas tributarias colombianas (General 19%, Reducido 5%, Exento 0%).</p>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tarifa Impositiva</th>
                        <th>Cant.</th>
                        <th class="col-money">Base Gravable</th>
                        <th class="col-money">IVA Liquidado</th>
                        <th class="col-money">Total Con IVA</th>
                        <th>% Part.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($taxBuckets ?? array()) as $tb): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($tb['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                            </td>
                            <td><?= (int) $tb['count'] ?></td>
                            <td class="col-money">$<?= number_format((float) $tb['sinIva'], 2, ',', '.') ?></td>
                            <td class="col-money">$<?= number_format((float) $tb['iva'], 2, ',', '.') ?></td>
                            <td class="col-money text-emerald"><strong>$<?= number_format((float) $tb['conIva'], 2, ',', '.') ?></strong></td>
                            <td>
                                <div class="progress-track" title="<?= number_format((float) $tb['share'], 1) ?>%">
                                    <div class="progress-fill" style="width: <?= max(2, min(100, (int) round((float) $tb['share']))) ?>%;"></div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MÓDULO 2: SUMA Y RANKING POR LUGAR -->
    <div class="card-panel">
        <h2>2. Ranking y Concentración por Establecimiento / Lugar</h2>
        <p class="muted">Participación acumulada y distribución del gasto por comercio según los filtros activos.</p>

        <?php if (empty($byLugar)): ?>
            <p class="empty-state">No hay gastos que coincidan con los criterios seleccionados.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Lugar / Proveedor</th>
                            <th>Cant.</th>
                            <th class="col-money">Base Sin IVA</th>
                            <th class="col-money">Total Con IVA</th>
                            <th>% Part.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($byLugar as $row): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars((string) $row['lugar'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td><?= (int) $row['count'] ?></td>
                                <td class="col-money">$<?= number_format((float) $row['sinIva'], 2, ',', '.') ?></td>
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
</div>

<div class="reports-grid">
    <!-- MÓDULO 3: CONSOLIDADO POR USUARIO RESPONSABLE -->
    <div class="card-panel">
        <h2>3. Consolidado por Usuario Responsable</h2>
        <p class="muted">Distribución presupuestal individualizada para auditoría interna y rendición de cuentas.</p>

        <?php if (empty($byUser)): ?>
            <p class="empty-state">Sin movimientos para los criterios seleccionados.</p>
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

    <!-- MÓDULO 4: EVOLUCIÓN TEMPORAL MENSUAL -->
    <div class="card-panel">
        <h2>4. Evolución Temporal Mensual (Histórico Cronológico)</h2>
        <p class="muted">Comportamiento del gasto a lo largo del tiempo agrupado por periodos mensuales (<code>YYYY-MM</code>).</p>

        <?php if (empty($byMonth)): ?>
            <p class="empty-state">No hay periodos con movimientos registrados.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Periodo</th>
                            <th>Operaciones</th>
                            <th class="col-money">Sin IVA</th>
                            <th class="col-money">IVA</th>
                            <th class="col-money">Total Con IVA</th>
                            <th class="col-money">Ticket Medio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($byMonth as $mRow): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars((string) $mRow['mes'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                <td><?= (int) $mRow['count'] ?></td>
                                <td class="col-money">$<?= number_format((float) $mRow['sinIva'], 2, ',', '.') ?></td>
                                <td class="col-money">$<?= number_format((float) $mRow['iva'], 2, ',', '.') ?></td>
                                <td class="col-money text-emerald"><strong>$<?= number_format((float) $mRow['conIva'], 2, ',', '.') ?></strong></td>
                                <td class="col-money">$<?= number_format((float) $mRow['avg'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MÓDULO 5: TOP 5 GASTOS DE MAYOR IMPACTO -->
<div class="card-panel" style="margin-bottom: 22px;">
    <h2>5. Top 5 Gastos de Mayor Impacto Presupuestal</h2>
    <p class="muted">Transacciones individuales más significativas dentro de los resultados filtrados.</p>

    <?php if (empty($topGastos)): ?>
        <p class="empty-state">No hay gastos para clasificar.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Fecha</th>
                        <th>Usuario Responsable</th>
                        <th>Lugar / Proveedor</th>
                        <th>Descripción</th>
                        <th class="col-money">Subtotal Sin IVA</th>
                        <th class="col-money">IVA</th>
                        <th class="col-money">Total Con IVA</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topGastos as $idx => $tg): ?>
                        <tr>
                            <td><span class="badge <?= $idx === 0 ? 'badge-admin' : 'badge-member' ?>">#<?= $idx + 1 ?></span></td>
                            <td><?= htmlspecialchars($tg->getFecha(), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><strong><?= htmlspecialchars($tg->getNombreUsuario(), ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars($tg->getLugar(), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($tg->getDescripcion(), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="col-money">$<?= number_format($tg->getValorTotalSinIVA(), 2, ',', '.') ?></td>
                            <td class="col-money">$<?= number_format($tg->getIvaTotal(), 2, ',', '.') ?></td>
                            <td class="col-money text-emerald"><strong>$<?= number_format($tg->getValorTotalConIVA(), 2, ',', '.') ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- MÓDULO 6: TABLA DETALLADA DE GASTOS FILTRADOS -->
<div class="card-panel" style="margin-bottom: 22px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2>6. Tabla de Gastos Filtrados (Detalle Transaccional)</h2>
            <p class="muted" style="margin: 0;">Mostrando <strong><?= count($filteredGastos ?? array()) ?></strong> registro(s) coincidentes.</p>
        </div>
        <a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars($buildFilterUrl(array('export' => 'csv')), ENT_QUOTES, 'UTF-8') ?>">
            📥 Descargar CSV para Excel
        </a>
    </div>

    <?php if (empty($filteredGastos)): ?>
        <p class="empty-state">No se encontraron transacciones que cumplan todos los filtros seleccionados.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="col-id">ID</th>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Lugar</th>
                        <th>Descripción</th>
                        <th class="col-money">Sin IVA</th>
                        <th class="col-money">IVA</th>
                        <th class="col-money">Total Con IVA</th>
                        <th class="col-actions">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filteredGastos as $g): ?>
                        <tr>
                            <td class="col-id"><?= htmlspecialchars(substr($g->getId(), 0, 8), ENT_QUOTES, 'UTF-8') ?>&hellip;</td>
                            <td><?= htmlspecialchars($g->getFecha(), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($g->getNombreUsuario(), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><strong><?= htmlspecialchars($g->getLugar(), ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td><?= htmlspecialchars($g->getDescripcion(), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="col-money">$<?= number_format($g->getValorTotalSinIVA(), 2, ',', '.') ?></td>
                            <td class="col-money">$<?= number_format($g->getIvaTotal(), 2, ',', '.') ?></td>
                            <td class="col-money text-emerald"><strong>$<?= number_format($g->getValorTotalConIVA(), 2, ',', '.') ?></strong></td>
                            <td class="col-actions">
                                <a class="btn btn-secondary btn-sm" href="?route=gastos.show&id=<?= urlencode($g->getId()) ?>">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- MÓDULO 7: DIRECTORIO DE USUARIOS POR ROL -->
<div class="card-panel">
    <h2>7. Directorio y Auditoría de Usuarios por Rol</h2>
    <p class="muted">Filtrado transversal de cuentas según su nivel de privilegios (<code>ADMIN</code>, <code>MEMBER</code>, <code>REVIEWER</code>).</p>

    <form method="GET" action="" class="filter-bar" style="margin-bottom: 14px;">
        <input type="hidden" name="route" value="reports">
        <input type="hidden" name="qLugar" value="<?= htmlspecialchars((string) ($filters['qLugar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="fechaDesde" value="<?= htmlspecialchars((string) ($filters['fechaDesde'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="fechaHasta" value="<?= htmlspecialchars((string) ($filters['fechaHasta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="usuarioFiltro" value="<?= htmlspecialchars((string) ($filters['usuarioFiltro'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="tarifaIva" value="<?= htmlspecialchars((string) ($filters['tarifaIva'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <select name="rolFiltro">
            <option value="">Todos los roles (<?= (int) ($globalTotals['usersCount'] ?? 0) ?> usuarios)</option>
            <?php foreach (($roleCounts ?? array()) as $roleKey => $rCount): ?>
                <option value="<?= htmlspecialchars((string) $roleKey, ENT_QUOTES, 'UTF-8') ?>"
                    <?= (($filters['rolFiltro'] ?? '') === $roleKey) ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $roleKey, ENT_QUOTES, 'UTF-8') ?> (<?= (int) $rCount ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Filtrar Rol</button>
        <?php if (!empty($filters['rolFiltro'])): ?>
            <a class="btn btn-secondary" href="<?= htmlspecialchars($buildFilterUrl(array('rolFiltro' => '')), ENT_QUOTES, 'UTF-8') ?>">Ver Todos</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <th>Correo Electrónico</th>
                    <th>Rol de Dominio</th>
                    <th>Estado de Cuenta</th>
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

<?php require __DIR__ . '/../layouts/footer.php'; ?>
