<?php
/**
 * Consola Interactiva de Pruebas de la API REST — `Views/api/tester.php`
 * Permite a estudiantes, evaluadores y desarrolladores probar en vivo el consumo
 * de la API RESTful (JWT HS256, CRUDL de Gastos, Usuarios, Casos Válidos e Inválidos)
 * directamente desde el navegador, con ejemplos listos para Postman y cURL,
 * y alineación didáctica con principios SOLID, Arquitectura Hexagonal y CQRS.
 */
require __DIR__ . '/../layouts/header.php';
require __DIR__ . '/../layouts/menu.php';
?>

<div class="page-header">
    <div>
        <h1>Consola Interactiva de Pruebas de la API REST</h1>
        <p class="muted">
            Herramienta integrada para validar el consumo de la API RESTful protegida con JWT (HS256),
            principios SOLID, Arquitectura Hexagonal, CQRS y validación de invariantes DDD en tiempo real.
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a class="btn btn-secondary" href="?route=swagger" target="_blank" rel="noopener">Abrir Swagger UI</a>
        <a class="btn btn-secondary" href="postman/CRUD_Usuarios_Gastos_Hexagonal.postman_collection.json" download>Descargar Colección Postman</a>
    </div>
</div>

<!-- SECCIÓN 1: AUTENTICACIÓN JWT -->
<div class="card-panel form-card" style="margin-bottom: 22px;">
    <h2>1. Autenticación Stateless JWT (RFC 7519 — HS256)</h2>
    <p class="muted">
        Genera un token de portador (*Bearer Token*) firmado criptográficamente para autorizar las solicitudes a endpoints protegidos.
        Únicamente <code>/api/health</code> y <code>/api/auth/login</code> son públicos; los demás exigen este token.
    </p>

    <div class="form-grid-2">
        <div class="form-group">
            <label for="jwt_email">Correo Electrónico</label>
            <input type="email" id="jwt_email" value="admin@crud.com" placeholder="admin@crud.com">
        </div>
        <div class="form-group">
            <label for="jwt_password">Contraseña</label>
            <input type="password" id="jwt_password" value="Admin12345" placeholder="Admin12345">
        </div>
    </div>

    <div style="display: flex; gap: 12px; align-items: center; margin-top: 10px;">
        <button type="button" class="btn btn-primary" id="btn_login_jwt">Generar Token JWT con 1 Clic</button>
        <button type="button" class="btn btn-secondary" id="btn_clear_jwt">Limpiar Token</button>
        <span id="jwt_status_badge" class="badge" style="display: none;"></span>
    </div>

    <div style="margin-top: 14px;">
        <label for="active_jwt_token" style="font-weight: 600;">Token Bearer JWT Activo:</label>
        <div style="position: relative; margin-top: 6px;">
            <textarea id="active_jwt_token" rows="2" readonly
                style="width: 100%; font-family: monospace; font-size: 0.82rem; background: #0f172a; color: #38bdf8; border: 1px solid #334155; border-radius: 6px; padding: 8px;"
                placeholder="Haz clic en 'Generar Token JWT con 1 Clic' para autenticar automáticamente..."></textarea>
        </div>
        <div id="jwt_claims_box" style="display: none; margin-top: 8px; font-size: 0.85rem; color: #94a3b8; background: #020617; padding: 10px; border-radius: 6px; border: 1px solid #1e293b;">
            <strong>Claims Verificados:</strong>
            <span id="jwt_claims_text"></span>
        </div>
    </div>
</div>

<!-- SECCIÓN 2: ESCENARIOS RÁPIDOS DE PRUEBA -->
<div class="card-panel form-card" style="margin-bottom: 22px;">
    <h2>2. Escenarios Didácticos de Prueba Preconfigurados</h2>
    <p class="muted">Selecciona un escenario para cargar automáticamente el verbo, la URL, las cabeceras y el payload JSON:</p>

    <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px;">
        <button type="button" class="btn btn-secondary btn-sm preset-btn" data-preset="health">
            🟢 Health Check (Público)
        </button>
        <button type="button" class="btn btn-secondary btn-sm preset-btn" data-preset="list-gastos">
            📋 Listar Gastos (Query CQRS)
        </button>
        <button type="button" class="btn btn-secondary btn-sm preset-btn" data-preset="create-gasto-valid">
            ✅ Crear Gasto Válido (201 Created)
        </button>
        <button type="button" class="btn btn-secondary btn-sm preset-btn" data-preset="create-gasto-invalid">
            ⚠️ Invariante IVA Inválido (422 DDD Error)
        </button>
        <button type="button" class="btn btn-secondary btn-sm preset-btn" data-preset="list-users">
            👥 Listar Usuarios (RBAC Admin)
        </button>
        <button type="button" class="btn btn-secondary btn-sm preset-btn" data-preset="tax-indicators">
            📈 Indicadores Tributarios (Adapter REST)
        </button>
        <button type="button" class="btn btn-secondary btn-sm preset-btn" data-preset="unauthorized-test">
            🚫 Probar Sin Token (401 Security Guard)
        </button>
    </div>
</div>

<!-- SECCIÓN 3: CONSOLA DE PETICIÓN Y RESPUESTA -->
<div class="card-panel form-card" style="margin-bottom: 22px;">
    <h2>3. Ejecución de Petición HTTP en Vivo</h2>

    <div class="form-grid-2" style="grid-template-columns: 140px 1fr; margin-bottom: 12px;">
        <div class="form-group">
            <label for="http_method">Método HTTP</label>
            <select id="http_method" style="width: 100%;">
                <option value="GET">GET</option>
                <option value="POST">POST</option>
                <option value="PUT">PUT</option>
                <option value="DELETE">DELETE</option>
            </select>
        </div>
        <div class="form-group">
            <label for="http_endpoint">Endpoint API</label>
            <input type="text" id="http_endpoint" value="/api/gastos" style="font-family: monospace;">
        </div>
    </div>

    <div class="form-group" style="margin-bottom: 12px;">
        <label for="http_headers">Cabeceras HTTP (Headers)</label>
        <textarea id="http_headers" rows="2" style="width: 100%; font-family: monospace; font-size: 0.85rem;" placeholder="Accept: application/json&#10;Content-Type: application/json"></textarea>
    </div>

    <div class="form-group" id="group_http_body" style="margin-bottom: 14px;">
        <label for="http_body">Cuerpo JSON de la Petición (Request Body)</label>
        <textarea id="http_body" rows="6" style="width: 100%; font-family: monospace; font-size: 0.85rem;" placeholder="{\n  &quot;key&quot;: &quot;value&quot;\n}"></textarea>
    </div>

    <div style="display: flex; gap: 12px; align-items: center;">
        <button type="button" class="btn btn-primary" id="btn_execute_req" style="font-weight: 700; padding: 10px 24px;">
            🚀 Ejecutar Petición en Vivo
        </button>
        <button type="button" class="btn btn-secondary" id="btn_copy_curl">
            📋 Copiar como cURL / Postman
        </button>
        <span id="req_loader" style="display: none; color: #38bdf8; font-size: 0.9rem;">
            ⏳ Conectando con la API...
        </span>
    </div>

    <!-- RESPUESTA HTTP EN VIVO -->
    <div style="margin-top: 20px; border-top: 1px solid #334155; padding-top: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h3 style="margin: 0; font-size: 1.1rem; color: #f1f5f9;">Respuesta del Servidor (HTTP Response)</h3>
            <div style="display: flex; gap: 10px; align-items: center;">
                <span id="resp_status_badge" class="badge" style="display: none;"></span>
                <span id="resp_time_badge" class="badge badge-member" style="display: none;"></span>
            </div>
        </div>

        <div style="background: #020617; border: 1px solid #1e293b; border-radius: 8px; padding: 14px; position: relative;">
            <div style="font-size: 0.78rem; color: #64748b; margin-bottom: 8px; font-family: monospace;" id="resp_headers_display">
                Esperando ejecución de petición...
            </div>
            <pre id="resp_body_display" style="margin: 0; max-height: 420px; overflow-y: auto; color: #f8fafc; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.86rem; line-height: 1.5;">{\n  "status": "ready",\n  "message": "Selecciona un escenario arriba o haz clic en 'Ejecutar Petición en Vivo'"\n}</pre>
        </div>
    </div>
</div>

<!-- SECCIÓN 4: MATRIZ DE ALINEACIÓN ARQUITECTÓNICA & PRINCIPIOS SOLID -->
<div class="card-panel form-card">
    <h2>4. Arquitectura y Principios SOLID Demostrados en esta API</h2>
    <p class="muted">
        Cada petición que procesa esta API refleja la separación estricta de responsabilidades entre capas:
    </p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px; margin-top: 14px;">
        <div style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 14px;">
            <h4 style="color: #38bdf8; margin: 0 0 6px 0;">S — Responsabilidad Única (SRP)</h4>
            <p style="font-size: 0.85rem; color: #cbd5e1; margin: 0; line-height: 1.5;">
                <code>GastoRestController</code> únicamente deserializa el JSON entrante, delega en el caso de uso y emite la respuesta HTTP. Nunca ejecuta SQL ni calcula impuestos.
            </p>
        </div>

        <div style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 14px;">
            <h4 style="color: #38bdf8; margin: 0 0 6px 0;">O — Abierto / Cerrado (OCP)</h4>
            <p style="font-size: 0.85rem; color: #cbd5e1; margin: 0; line-height: 1.5;">
                Nuevos adaptadores de salida (como clientes REST o colas Kafka) se incorporan implementando contratos de puertos sin tocar el núcleo de dominio ni los casos de uso.
            </p>
        </div>

        <div style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 14px;">
            <h4 style="color: #38bdf8; margin: 0 0 6px 0;">L — Sustitución de Liskov (LSP)</h4>
            <p style="font-size: 0.85rem; color: #cbd5e1; margin: 0; line-height: 1.5;">
                Tanto <code>GastoRepositoryMySQL</code> como <code>InMemoryGastoRepository</code> satisfacen <code>SaveGastoPort</code> idénticamente. El sistema puede alternar entre ellos sin fallos.
            </p>
        </div>

        <div style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 14px;">
            <h4 style="color: #38bdf8; margin: 0 0 6px 0;">I — Segregación de Interfaces (ISP)</h4>
            <p style="font-size: 0.85rem; color: #cbd5e1; margin: 0; line-height: 1.5;">
                En lugar de una interfaz monolítica gigante, cada caso de uso expone un contrato fino y específico (<code>CreateGastoUseCase</code>, <code>GetAllGastosUseCase</code>).
            </p>
        </div>

        <div style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 14px;">
            <h4 style="color: #38bdf8; margin: 0 0 6px 0;">D — Inversión de Dependencias (DIP)</h4>
            <p style="font-size: 0.85rem; color: #cbd5e1; margin: 0; line-height: 1.5;">
                La capa de aplicación declara los puertos abstractos (interfaces). La capa de infraestructura depende de ellos; el dominio nunca conoce MySQL ni cURL.
            </p>
        </div>

        <div style="background: #0f172a; border: 1px solid #334155; border-radius: 8px; padding: 14px;">
            <h4 style="color: #2dd4bf; margin: 0 0 6px 0;">CQRS &amp; Invariantes DDD</h4>
            <p style="font-size: 0.85rem; color: #cbd5e1; margin: 0; line-height: 1.5;">
                Las lecturas viajan por <code>GetAllGastosQuery</code>; las escrituras por <code>CreateGastoCommand</code>. El modelo <code>GastoModel</code> asegura en centavos que <code>SinIVA + IVA === ConIVA</code> antes de permitir la persistencia.
            </p>
        </div>
    </div>
</div>

<script>
(function() {
    let currentToken = '';

    const elEmail = document.getElementById('jwt_email');
    const elPassword = document.getElementById('jwt_password');
    const elBtnLogin = document.getElementById('btn_login_jwt');
    const elBtnClear = document.getElementById('btn_clear_jwt');
    const elTokenArea = document.getElementById('active_jwt_token');
    const elBadge = document.getElementById('jwt_status_badge');
    const elClaimsBox = document.getElementById('jwt_claims_box');
    const elClaimsText = document.getElementById('jwt_claims_text');

    const elMethod = document.getElementById('http_method');
    const elEndpoint = document.getElementById('http_endpoint');
    const elHeaders = document.getElementById('http_headers');
    const elBody = document.getElementById('http_body');
    const elGroupBody = document.getElementById('group_http_body');
    const elBtnExec = document.getElementById('btn_execute_req');
    const elBtnCurl = document.getElementById('btn_copy_curl');
    const elLoader = document.getElementById('req_loader');

    const elRespStatus = document.getElementById('resp_status_badge');
    const elRespTime = document.getElementById('resp_time_badge');
    const elRespHeaders = document.getElementById('resp_headers_display');
    const elRespBody = document.getElementById('resp_body_display');

    function updateMethodVisibility() {
        if (elMethod.value === 'GET' || elMethod.value === 'DELETE') {
            elGroupBody.style.display = 'none';
        } else {
            elGroupBody.style.display = 'block';
        }
    }

    elMethod.addEventListener('change', updateMethodVisibility);

    function syncHeadersToken() {
        let lines = ['Accept: application/json'];
        if (elMethod.value !== 'GET') {
            lines.push('Content-Type: application/json');
        }
        if (currentToken) {
            lines.push('Authorization: Bearer ' + currentToken);
        }
        elHeaders.value = lines.join('\n');
    }

    elBtnLogin.addEventListener('click', async function() {
        elBtnLogin.disabled = true;
        elBtnLogin.textContent = 'Autenticando...';
        try {
            const resp = await fetch('/api/auth/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    email: elEmail.value.trim(),
                    password: elPassword.value
                })
            });
            const data = await resp.json();
            const token = (data && data.data && (data.data.access_token || data.data.token)) || '';
            if (resp.ok && data.success && token) {
                currentToken = token;
                elTokenArea.value = currentToken;
                elBadge.style.display = 'inline-block';
                elBadge.className = 'badge badge-admin';
                elBadge.textContent = 'JWT Activo (' + (data.data.user.role || 'ADMIN') + ')';

                elClaimsBox.style.display = 'block';
                elClaimsText.textContent = ' Usuario: ' + data.data.user.name + ' | Correo: ' + data.data.user.email + ' | Expira: ' + (data.data.expires_in || 28800) + 's';

                syncHeadersToken();
                showResponse(resp.status, 'OK', 0, 'Autenticación exitosa', data);
            } else {
                const errMsg = (data && data.error) ? (typeof data.error === 'string' ? data.error : (data.error.message || JSON.stringify(data.error))) : ((data && data.message) || 'Credenciales inválidas');
                throw new Error(errMsg);
            }
        } catch (err) {
            elBadge.style.display = 'inline-block';
            elBadge.className = 'badge badge-danger';
            elBadge.textContent = 'Error: ' + err.message;
            showResponse(401, 'Unauthorized', 0, 'Error de autenticación', { error: err.message });
        } finally {
            elBtnLogin.disabled = false;
            elBtnLogin.textContent = 'Generar Token JWT con 1 Clic';
        }
    });

    elBtnClear.addEventListener('click', function() {
        currentToken = '';
        elTokenArea.value = '';
        elBadge.style.display = 'none';
        elClaimsBox.style.display = 'none';
        syncHeadersToken();
    });

    const presets = {
        'health': {
            method: 'GET',
            endpoint: '/api/health',
            withAuth: false,
            body: ''
        },
        'list-gastos': {
            method: 'GET',
            endpoint: '/api/gastos?limit=10&page=1',
            withAuth: true,
            body: ''
        },
        'create-gasto-valid': {
            method: 'POST',
            endpoint: '/api/gastos',
            withAuth: true,
            body: JSON.stringify({
                fecha: new Date().toISOString().split('T')[0],
                lugar: "Papelería Universitaria Centro",
                descripcion: "Compra de útiles y papelería institucional (Caso Válido con IVA 19%)",
                valorTotalSinIVA: 100000,
                ivaTotal: 19000,
                valorTotalConIVA: 119000
            }, null, 2)
        },
        'create-gasto-invalid': {
            method: 'POST',
            endpoint: '/api/gastos',
            withAuth: true,
            body: JSON.stringify({
                fecha: new Date().toISOString().split('T')[0],
                lugar: "Ferretería La Central",
                descripcion: "Intento de registrar gasto con IVA descuadrado para probar regla de negocio DDD",
                valorTotalSinIVA: 100000,
                ivaTotal: 10000,
                valorTotalConIVA: 119000
            }, null, 2)
        },
        'list-users': {
            method: 'GET',
            endpoint: '/api/users',
            withAuth: true,
            body: ''
        },
        'tax-indicators': {
            method: 'GET',
            endpoint: '/api/tax-indicators',
            withAuth: true,
            body: ''
        },
        'unauthorized-test': {
            method: 'GET',
            endpoint: '/api/gastos',
            withAuth: false,
            body: ''
        }
    };

    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const key = this.getAttribute('data-preset');
            const p = presets[key];
            if (!p) return;

            elMethod.value = p.method;
            elEndpoint.value = p.endpoint;
            updateMethodVisibility();

            if (!p.withAuth) {
                elHeaders.value = 'Accept: application/json' + (p.method !== 'GET' ? '\nContent-Type: application/json' : '');
            } else {
                syncHeadersToken();
            }

            elBody.value = p.body;
        });
    });

    function showResponse(status, statusText, durationMs, headersSummary, data) {
        elRespStatus.style.display = 'inline-block';
        elRespStatus.textContent = 'HTTP ' + status + ' ' + (statusText || '');
        if (status >= 200 && status < 300) {
            elRespStatus.className = 'badge badge-admin';
        } else if (status === 401 || status === 403) {
            elRespStatus.className = 'badge badge-danger';
        } else if (status === 422) {
            elRespStatus.className = 'badge';
            elRespStatus.style.background = '#8b5cf6';
            elRespStatus.style.color = '#ffffff';
        } else {
            elRespStatus.className = 'badge badge-danger';
        }

        if (durationMs > 0) {
            elRespTime.style.display = 'inline-block';
            elRespTime.textContent = durationMs + ' ms';
        }

        elRespHeaders.textContent = headersSummary;
        elRespBody.textContent = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
    }

    elBtnExec.addEventListener('click', async function() {
        elBtnExec.disabled = true;
        elLoader.style.display = 'inline';

        const method = elMethod.value;
        const url = elEndpoint.value.trim();
        const headerLines = elHeaders.value.split('\n');
        const headers = {};
        headerLines.forEach(line => {
            const idx = line.indexOf(':');
            if (idx > 0) {
                const k = line.substring(0, idx).trim();
                const v = line.substring(idx + 1).trim();
                headers[k] = v;
            }
        });

        const reqOpts = {
            method: method,
            headers: headers
        };

        if (method !== 'GET' && method !== 'DELETE') {
            reqOpts.body = elBody.value;
        }

        const start = performance.now();
        try {
            const resp = await fetch(url, reqOpts);
            const duration = Math.round(performance.now() - start);

            const headerEntries = [];
            resp.headers.forEach((val, key) => {
                headerEntries.push(key + ': ' + val);
            });
            const headersSummary = headerEntries.slice(0, 5).join(' | ');

            let data;
            const text = await resp.text();
            try {
                data = JSON.parse(text);
            } catch (e) {
                data = text;
            }

            showResponse(resp.status, resp.statusText, duration, headersSummary, data);
        } catch (err) {
            const duration = Math.round(performance.now() - start);
            showResponse(0, 'Network / Fetch Error', duration, 'Error en cliente', {
                error: err.message,
                hint: 'Verifica la URL o que el servidor esté en línea.'
            });
        } finally {
            elBtnExec.disabled = false;
            elLoader.style.display = 'none';
        }
    });

    elBtnCurl.addEventListener('click', function() {
        const method = elMethod.value;
        const host = window.location.origin;
        const endpoint = elEndpoint.value.trim();
        const fullUrl = endpoint.startsWith('http') ? endpoint : (host + (endpoint.startsWith('/') ? '' : '/') + endpoint);

        let cmd = 'curl -X ' + method + " '" + fullUrl + "'";
        const headerLines = elHeaders.value.split('\n');
        headerLines.forEach(line => {
            if (line.trim()) {
                cmd += " \\\n  -H '" + line.trim() + "'";
            }
        });

        if (method !== 'GET' && method !== 'DELETE' && elBody.value.trim()) {
            cmd += " \\\n  -d '" + elBody.value.replace(/'/g, "'\\''") + "'";
        }

        navigator.clipboard.writeText(cmd).then(() => {
            alert('¡Comando cURL copiado al portapapeles!\nPuedes pegarlo en tu terminal o importarlo en Postman (File -> Import -> Raw text).');
        }).catch(() => {
            prompt('Copia manualmente este comando cURL:', cmd);
        });
    });

    // Inicializar cabeceras por defecto
    syncHeadersToken();
    updateMethodVisibility();
})();
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>