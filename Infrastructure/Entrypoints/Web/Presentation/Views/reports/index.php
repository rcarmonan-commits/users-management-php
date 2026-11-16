<?php
/**
 * Centro de Reportes Analíticos CQRS — `Views/reports/index.php`
 * Proyecciones analíticas desacopladas con FILTROS INDEPENDIENTES POR REPORTE.
 * Cada módulo de consulta opera de forma autónoma sobre los casos de uso de lectura
 * (`GetAllGastosUseCase` y `GetAllUsersUseCase`), permitiendo parametrizar cada vista
 * sin afectar las demás.
 */
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/menu.php';

$buildUrl = static function(array $params = array()): string {
    $current = $_GET;
    unset($current['route'], $current['export']);
    $merged = array_merge($current, $params);
    $clean = array('route' => 'reports');
    foreach ($merged as $k => $v) {
        if ($v !== '' && $v !== null) {
            $clean[$k] = $v;
        }
    }
    return '?' . http_build_query($clean);
};

$renderPreservedHidden = static function(array $excludeKeys = array()): string {
    $current = $_GET;
    unset($current['route'], $current['export']);
    $html = '';
    foreach ($current as $k => $v) {
        if (!in_array($k, $excludeKeys, true) && $v !== '' && $v !== null) {
            $html .= '<input type="hidden" name="' . htmlspecialchars((string) $k, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') . '">';
        }
    }
    return $html;
};

$hasIvaFilter = ($filters['iva_desde'] !== '' || $filters['iva_hasta'] !== '' || $filters['iva_tarifa'] !== '');
$hasLugFilter = ($filters['lug_q'] !== '' || $filters['lug_order'] !== 'conIva');
$hasUsrFilter = ($filters['usr_q'] !== '' || $filters['usr_min'] !== '' || $filters['usr_max'] !== '');
$hasMesFilter = ($filters['mes_desde'] !== '' || $filters['mes_hasta'] !== '');
$hasTopFilter = ($filters['top_limit'] != 5 || $filters['top_lugar'] !== '');
$hasDetFilter = ($filters['det_q'] !== '' || $filters['det_desde'] !== '' || $filters['det_hasta'] !== '' || $filters['det_usr'] !== '' || $filters['det_tarifa'] !== '' || $filters['det_min'] !== '' || $filters['det_max'] !== '');
$hasRolFilter = ($filters['rol_filtro'] !== '' || $filters['rol_estado'] !== '');
$anyFilterActive = ($hasIvaFilter || $hasLugFilter || $hasUsrFilter || $hasMesFilter || $hasTopFilter || $hasDetFilter || $hasRolFilter);
?>

<div class="page-header">
    <div>
        <span class="badge badge-emerald">CQRS Read Projections &middot; Consultas Desacopladas</span>
        <h1>Centro de Reportes Analíticos y Auditoría Contable</h1>
        <p class="muted">Filtros independientes por cada consulta y módulo analítico construidos sobre la capa de lectura CQRS.</p>
    </div>
    <div class="header-actions">
        <?php if ($anyFilterActive): ?>
            <a class="btn btn-secondary" href="?route=reports">Restablecer Todos los Reportes</a>
        <?php endif; ?>
        <button type="button" class="btn btn-secondary" onclick="window.print()">🖨️ Imprimir</button>
        <a class="btn btn-primary" href="?route=gastos.create">+ Registrar Gasto</a>
    </div>
</div>

<!-- KPIS EJECUTIVOS GLOBALES DEL SISTEMA -->
<section class="kpi-grid">
    <div class="kpi-card">
        <span class="kpi-label">Total Transacciones Globales</span>
        <strong class="kpi-value"><?= (int) ($globalTotals['count'] ?? 0) ?></strong>
        <span class="kpi-foot">Ticket medio global: $<?= number_format((float) ($globalTotals['avgConIva'] ?? 0), 2, ',', '.') ?></span>
    </div>
    <div class="kpi-card">
        <span class="kpi-label">Base Gravable Acumulada</span>
        <strong class="kpi-value">$<?= number_format((float) ($globalTotals['sinIva'] ?? 0), 2, ',', '.') ?></strong>
        <span class="kpi-foot">Subtotal general Sin IVA</span>
    </div>
    <div class="kpi-card">
        <span class="kpi-label">IVA Global Soportado</span>
        <strong class="kpi-value">$<?= number_format((float) ($globalTotals['iva'] ?? 0), 2, ',', '.') ?></strong>
        <span class="kpi-foot">Validado al centavo con precisión fiscal</span>
    </div>
    <div class="kpi-card kpi-highlight">
        <span class="kpi-label">Ejecución Presupuestal Global</span>
        <strong class="kpi-value">$<?= number_format((float) ($globalTotals['conIva'] ?? 0), 2, ',', '.') ?></strong>
        <span class="kpi-foot">Total general Con IVA registrado</span>
    </div>
</section>

<div class="reports-grid">
    <!-- MÓDULO 1: AUDITORÍA FISCAL Y DESGLOSE DE IVA -->
    <div class="card-panel" id="rep1">
        <div class="report-module-header">
            <div>
                <h2>1. Auditoría Fiscal y Desglose por Tarifa de IVA</h2>
                <p class="muted">Conciliación contable por tarifas tributarias colombianas (19%, 5%, 0% y especiales).</p>
            </div>
            <a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars($buildUrl(array('export' => 'csv_iva')), ENT_QUOTES, 'UTF-8') ?>">
                📥 CSV IVA
            </a>
        </div>

        <!-- Filtro exclusivo para este reporte -->
        <form method="GET" action="#rep1" class="module-filter-bar">
            <input type="hidden" name="route" value="reports">
            <?= $renderPreservedHidden(array('iva_desde', 'iva_hasta', 'iva_tarifa')) ?>

            <div class="filter-field">
                <label for="iva_desde">Fecha Desde</label>
                <input type="date" id="iva_desde" name="iva_desde" value="<?= htmlspecialchars((string) $filters['iva_desde'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="filter-field">
                <label for="iva_hasta">Fecha Hasta</label>
                <input type="date" id="iva_hasta" name="iva_hasta" value="<?= htmlspecialchars((string) $filters['iva_hasta'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="filter-field">
                <label for="iva_tarifa">Tarifa</label>
                <select id="iva_tarifa" name="iva_tarifa">
                    <option value="">Todas las tarifas</option>
                    <option value="19" <?= $filters['iva_tarifa'] === '19' ? 'selected' : '' ?>>19% General</option>
                    <option value="5" <?= $filters['iva_tarifa'] === '5' ? 'selected' : '' ?>>5% Reducido</option>
                    <option value="0" <?= $filters['iva_tarifa'] === '0' ? 'selected' : '' ?>>0% Exento</option>
                    <option value="other" <?= $filters['iva_tarifa'] === 'other' ? 'selected' : '' ?>>Otras Tarifas</option>
                </select>
            </div>

            <div class="module-filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar IVA</button>
                <?php if ($hasIvaFilter): ?>
                    <a href="<?= htmlspecialchars($buildUrl(array('iva_desde' => '', 'iva_hasta' => '', 'iva_tarifa' => '')), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary btn-sm">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>

        <p class="module-meta-info">
            Periodo fiscal analizado: <strong><?= (int) ($taxTotals['count'] ?? 0) ?></strong> transacciones coincidentes &middot; Total Con IVA: <strong>$<?= number_format((float) ($taxTotals['conIva'] ?? 0), 2, ',', '.') ?></strong>
        </p>

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
                            <td><strong><?= htmlspecialchars($tb['label'], ENT_QUOTES, 'UTF-8') ?></strong></td>
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
    <div class="card-panel" id="rep2">
        <div class="report-module-header">
            <div>
                <h2>2. Ranking y Concentración por Establecimiento / Lugar</h2>
                <p class="muted">Participación acumulada y distribución del gasto por comercio o proveedor.</p>
            </div>
            <a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars($buildUrl(array('export' => 'csv_lugar')), ENT_QUOTES, 'UTF-8') ?>">
                📥 CSV Lugares
            </a>
        </div>

        <!-- Filtro exclusivo para este reporte -->
        <form method="GET" action="#rep2" class="module-filter-bar">
            <input type="hidden" name="route" value="reports">
            <?= $renderPreservedHidden(array('lug_q', 'lug_order')) ?>

            <div class="filter-field" style="flex: 2 1 180px;">
                <label for="lug_q">Buscar Lugar / Comercio</label>
                <input type="text" id="lug_q" name="lug_q" placeholder="ej: Éxito, Cafetería, Ara..."
                    value="<?= htmlspecialchars((string) $filters['lug_q'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="filter-field">
                <label for="lug_order">Ordenar Por</label>
                <select id="lug_order" name="lug_order">
                    <option value="conIva" <?= $filters['lug_order'] === 'conIva' ? 'selected' : '' ?>>Mayor Total ($)</option>
                    <option value="count" <?= $filters['lug_order'] === 'count' ? 'selected' : '' ?>>Más Transacciones</option>
                    <option value="sinIva" <?= $filters['lug_order'] === 'sinIva' ? 'selected' : '' ?>>Mayor Base Sin IVA</option>
                </select>
            </div>

            <div class="module-filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">Consultar</button>
                <?php if ($hasLugFilter): ?>
                    <a href="<?= htmlspecialchars($buildUrl(array('lug_q' => '', 'lug_order' => 'conIva')), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary btn-sm">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>

        <p class="module-meta-info">
            Establecimientos encontrados: <strong><?= count($byLugar ?? array()) ?></strong> &middot; Total agrupado: <strong>$<?= number_format((float) ($lugarTotalConIva ?? 0), 2, ',', '.') ?></strong>
        </p>

        <?php if (empty($byLugar)): ?>
            <p class="empty-state">No hay establecimientos que coincidan con la búsqueda.</p>
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
    <div class="card-panel" id="rep3">
        <div class="report-module-header">
            <div>
                <h2>3. Consolidado por Usuario Responsable</h2>
                <p class="muted">Distribución presupuestal individualizada para auditoría interna y rendición de cuentas.</p>
            </div>
            <a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars($buildUrl(array('export' => 'csv_usuario')), ENT_QUOTES, 'UTF-8') ?>">
                📥 CSV Usuarios
            </a>
        </div>

        <!-- Filtro exclusivo para este reporte -->
        <form method="GET" action="#rep3" class="module-filter-bar">
            <input type="hidden" name="route" value="reports">
            <?= $renderPreservedHidden(array('usr_q', 'usr_min', 'usr_max')) ?>

            <div class="filter-field" style="flex: 2 1 160px;">
                <label for="usr_q">Usuario Responsable</label>
                <select id="usr_q" name="usr_q">
                    <option value="">Todos los usuarios</option>
                    <?php foreach (($gastoUserNames ?? array()) as $uName): ?>
                        <option value="<?= htmlspecialchars($uName, ENT_QUOTES, 'UTF-8') ?>" <?= (($filters['usr_q'] ?? '') === $uName) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($uName, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-field">
                <label for="usr_min">Monto Mín ($)</label>
                <input type="number" step="0.01" id="usr_min" name="usr_min" placeholder="0.00"
                    value="<?= htmlspecialchars((string) $filters['usr_min'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="filter-field">
                <label for="usr_max">Monto Máx ($)</label>
                <input type="number" step="0.01" id="usr_max" name="usr_max" placeholder="Sin límite"
                    value="<?= htmlspecialchars((string) $filters['usr_max'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="module-filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
                <?php if ($hasUsrFilter): ?>
                    <a href="<?= htmlspecialchars($buildUrl(array('usr_q' => '', 'usr_min' => '', 'usr_max' => '')), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary btn-sm">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>

        <p class="module-meta-info">
            Responsables con movimientos: <strong><?= count($byUser ?? array()) ?></strong> &middot; Total asignado: <strong>$<?= number_format((float) ($userTotalConIva ?? 0), 2, ',', '.') ?></strong>
        </p>

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
    <div class="card-panel" id="rep4">
        <div class="report-module-header">
            <div>
                <h2>4. Evolución Temporal Mensual (Histórico Cronológico)</h2>
                <p class="muted">Comportamiento del gasto a lo largo del tiempo agrupado por periodos mensuales (<code>YYYY-MM</code>).</p>
            </div>
            <a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars($buildUrl(array('export' => 'csv_mensual')), ENT_QUOTES, 'UTF-8') ?>">
                📥 CSV Mensual
            </a>
        </div>

        <!-- Filtro exclusivo para este reporte -->
        <form method="GET" action="#rep4" class="module-filter-bar">
            <input type="hidden" name="route" value="reports">
            <?= $renderPreservedHidden(array('mes_desde', 'mes_hasta')) ?>

            <div class="filter-field">
                <label for="mes_desde">Periodo Desde</label>
                <input type="date" id="mes_desde" name="mes_desde" value="<?= htmlspecialchars((string) $filters['mes_desde'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="filter-field">
                <label for="mes_hasta">Periodo Hasta</label>
                <input type="date" id="mes_hasta" name="mes_hasta" value="<?= htmlspecialchars((string) $filters['mes_hasta'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="module-filter-actions">
                <button type="submit" class="btn btn-primary btn-sm">Filtrar Meses</button>
                <?php if ($hasMesFilter): ?>
                    <a href="<?= htmlspecialchars($buildUrl(array('mes_desde' => '', 'mes_hasta' => '')), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary btn-sm">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>

        <p class="module-meta-info">
            Meses analizados: <strong><?= count($byMonth ?? array()) ?></strong> &middot; Total periodo: <strong>$<?= number_format((float) ($monthTotalConIva ?? 0), 2, ',', '.') ?></strong>
        </p>

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

<!-- MÓDULO 5: TOP GASTOS DE MAYOR IMPACTO -->
<div class="card-panel" id="rep5" style="margin-bottom: 22px;">
    <div class="report-module-header">
        <div>
            <h2>5. Top Gastos de Mayor Impacto Presupuestal</h2>
            <p class="muted">Transacciones individuales más significativas ordenadas por monto total Con IVA.</p>
        </div>
    </div>

    <!-- Filtro exclusivo para este reporte -->
    <form method="GET" action="#rep5" class="module-filter-bar">
        <input type="hidden" name="route" value="reports">
        <?= $renderPreservedHidden(array('top_limit', 'top_lugar')) ?>

        <div class="filter-field" style="flex: 1 1 120px;">
            <label for="top_limit">Límite Top</label>
            <select id="top_limit" name="top_limit">
                <option value="3" <?= $filters['top_limit'] == 3 ? 'selected' : '' ?>>Top 3</option>
                <option value="5" <?= $filters['top_limit'] == 5 ? 'selected' : '' ?>>Top 5</option>
                <option value="10" <?= $filters['top_limit'] == 10 ? 'selected' : '' ?>>Top 10</option>
                <option value="15" <?= $filters['top_limit'] == 15 ? 'selected' : '' ?>>Top 15</option>
            </select>
        </div>

        <div class="filter-field" style="flex: 2 1 200px;">
            <label for="top_lugar">Filtrar Lugar Específico (Opcional)</label>
            <input type="text" id="top_lugar" name="top_lugar" placeholder="ej: Éxito, Ara..."
                value="<?= htmlspecialchars((string) $filters['top_lugar'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="module-filter-actions">
            <button type="submit" class="btn btn-primary btn-sm">Actualizar Top</button>
            <?php if ($hasTopFilter): ?>
                <a href="<?= htmlspecialchars($buildUrl(array('top_limit' => '5', 'top_lugar' => '')), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary btn-sm">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (empty($topGastos)): ?>
        <p class="empty-state">No hay gastos para clasificar con los criterios indicados.</p>
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
<div class="card-panel" id="rep6" style="margin-bottom: 22px;">
    <div class="report-module-header">
        <div>
            <h2>6. Tabla de Gastos Filtrados (Detalle Transaccional Completo)</h2>
            <p class="muted">Consulta analítica tabular con criterios combinables para auditoría minuciosa.</p>
        </div>
        <a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars($buildUrl(array('export' => 'csv_detalle')), ENT_QUOTES, 'UTF-8') ?>">
            📥 Descargar CSV Detallado
        </a>
    </div>

    <!-- Filtro exclusivo para la tabla detallada -->
    <form method="GET" action="#rep6" class="module-filter-bar">
        <input type="hidden" name="route" value="reports">
        <?= $renderPreservedHidden(array('det_q', 'det_desde', 'det_hasta', 'det_usr', 'det_tarifa', 'det_min', 'det_max')) ?>

        <div class="filter-field" style="flex: 2 1 180px;">
            <label for="det_q">Buscar Término</label>
            <input type="text" id="det_q" name="det_q" placeholder="Lugar, usuario o descripción..."
                value="<?= htmlspecialchars((string) $filters['det_q'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="filter-field">
            <label for="det_desde">Fecha Desde</label>
            <input type="date" id="det_desde" name="det_desde" value="<?= htmlspecialchars((string) $filters['det_desde'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="filter-field">
            <label for="det_hasta">Fecha Hasta</label>
            <input type="date" id="det_hasta" name="det_hasta" value="<?= htmlspecialchars((string) $filters['det_hasta'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="filter-field">
            <label for="det_usr">Usuario</label>
            <select id="det_usr" name="det_usr">
                <option value="">Todos</option>
                <?php foreach (($gastoUserNames ?? array()) as $uName): ?>
                    <option value="<?= htmlspecialchars($uName, ENT_QUOTES, 'UTF-8') ?>" <?= (($filters['det_usr'] ?? '') === $uName) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($uName, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-field">
            <label for="det_tarifa">Tarifa IVA</label>
            <select id="det_tarifa" name="det_tarifa">
                <option value="">Todas</option>
                <option value="19" <?= $filters['det_tarifa'] === '19' ? 'selected' : '' ?>>19% General</option>
                <option value="5" <?= $filters['det_tarifa'] === '5' ? 'selected' : '' ?>>5% Reducido</option>
                <option value="0" <?= $filters['det_tarifa'] === '0' ? 'selected' : '' ?>>0% Exento</option>
                <option value="other" <?= $filters['det_tarifa'] === 'other' ? 'selected' : '' ?>>Otras Tarifas</option>
            </select>
        </div>

        <div class="filter-field">
            <label for="det_min">Monto Mín ($)</label>
            <input type="number" step="0.01" id="det_min" name="det_min" placeholder="0.00"
                value="<?= htmlspecialchars((string) $filters['det_min'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="filter-field">
            <label for="det_max">Monto Máx ($)</label>
            <input type="number" step="0.01" id="det_max" name="det_max" placeholder="Sin límite"
                value="<?= htmlspecialchars((string) $filters['det_max'], ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="module-filter-actions">
            <button type="submit" class="btn btn-primary btn-sm">Filtrar Tabla</button>
            <?php if ($hasDetFilter): ?>
                <a href="<?= htmlspecialchars($buildUrl(array('det_q' => '', 'det_desde' => '', 'det_hasta' => '', 'det_usr' => '', 'det_tarifa' => '', 'det_min' => '', 'det_max' => '')), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary btn-sm">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <p class="module-meta-info">
        Registros filtrados: <strong><?= (int) ($detTotals['count'] ?? 0) ?></strong> &middot; Subtotal Sin IVA: <strong>$<?= number_format((float) ($detTotals['sinIva'] ?? 0), 2, ',', '.') ?></strong> &middot; IVA: <strong>$<?= number_format((float) ($detTotals['iva'] ?? 0), 2, ',', '.') ?></strong> &middot; Total Con IVA: <strong class="text-emerald">$<?= number_format((float) ($detTotals['conIva'] ?? 0), 2, ',', '.') ?></strong>
    </p>

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
<div class="card-panel" id="rep7">
    <div class="report-module-header">
        <div>
            <h2>7. Directorio y Auditoría de Usuarios por Rol</h2>
            <p class="muted">Filtrado transversal de cuentas según su nivel de privilegios (<code>ADMIN</code>, <code>MEMBER</code>, <code>REVIEWER</code>).</p>
        </div>
    </div>

    <!-- Filtro exclusivo para este reporte -->
    <form method="GET" action="#rep7" class="module-filter-bar">
        <input type="hidden" name="route" value="reports">
        <?= $renderPreservedHidden(array('rol_filtro', 'rol_estado')) ?>

        <div class="filter-field" style="flex: 2 1 200px;">
            <label for="rol_filtro">Rol de Dominio</label>
            <select id="rol_filtro" name="rol_filtro">
                <option value="">Todos los roles (<?= (int) ($globalTotals['usersCount'] ?? 0) ?> usuarios)</option>
                <?php foreach (($roleCounts ?? array()) as $roleKey => $rCount): ?>
                    <option value="<?= htmlspecialchars((string) $roleKey, ENT_QUOTES, 'UTF-8') ?>"
                        <?= (($filters['rol_filtro'] ?? '') === $roleKey) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $roleKey, ENT_QUOTES, 'UTF-8') ?> (<?= (int) $rCount ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-field">
            <label for="rol_estado">Estado</label>
            <select id="rol_estado" name="rol_estado">
                <option value="">Todos los estados</option>
                <option value="ACTIVE" <?= ($filters['rol_estado'] ?? '') === 'ACTIVE' ? 'selected' : '' ?>>Activo</option>
                <option value="INACTIVE" <?= ($filters['rol_estado'] ?? '') === 'INACTIVE' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>

        <div class="module-filter-actions">
            <button type="submit" class="btn btn-primary btn-sm">Filtrar Usuarios</button>
            <?php if ($hasRolFilter): ?>
                <a href="<?= htmlspecialchars($buildUrl(array('rol_filtro' => '', 'rol_estado' => '')), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary btn-sm">Limpiar</a>
            <?php endif; ?>
        </div>
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
