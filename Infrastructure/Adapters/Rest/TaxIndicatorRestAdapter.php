<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Application/Ports/Out/TaxIndicatorRestPort.php';
require_once __DIR__ . '/../../../Domain/Enums/GastoIvaRateEnum.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — ADAPTADOR CLIENTE REST SALIENTE: TaxIndicatorRestAdapter
 * ============================================================================
 * CAPA: Infraestructura — Adaptadores (`Infrastructure/Adapters/Rest`)
 * PATRÓN: Circuit-Safe Outbound REST Adapter + Protección Anti-SSRF (OWASP A10)
 *
 * ¿QUÉ HACE?
 *   Consulta indicadores tributarios externos por HTTP/HTTPS con timeout acotado y
 *   degrada de forma controlada a los valores oficiales de respaldo si no hay red.
 *
 * ¿CÓMO PREVIENE VULNERABILIDADES SSRF (OWASP A10)?
 *   Verifica que la URL configurada use exclusivamente esquema `https://` o `http://`,
 *   bloqueando wrappers peligrosos de PHP como `file://`, `phar://` o `php://`.
 * ============================================================================
 */
final class TaxIndicatorRestAdapter implements TaxIndicatorRestPort
{
    private string $endpointUrl;
    private int $timeoutSeconds;

    public function __construct(string $endpointUrl = '', int $timeoutSeconds = 3)
    {
        $this->endpointUrl = trim($endpointUrl);
        $this->timeoutSeconds = max(1, $timeoutSeconds);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchCurrentTaxIndicators(): array
    {
        if ($this->isSafeHttpUrl($this->endpointUrl)) {
            $context = stream_context_create(array(
                'http' => array(
                    'method' => 'GET',
                    'timeout' => $this->timeoutSeconds,
                    'follow_location' => 0,
                    'header' => "Accept: application/json\r\nUser-Agent: HexagonalGastosPHP/1.0\r\n",
                ),
            ));

            $raw = @file_get_contents($this->endpointUrl, false, $context);
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    return array(
                        'source' => 'remote_rest_api',
                        'iva_rates' => GastoIvaRateEnum::values(),
                        'default_iva_rate' => GastoIvaRateEnum::GENERAL,
                        'remote_payload' => $decoded,
                    );
                }
            }
        }

        return array(
            'source' => 'local_fallback_colombia_2026',
            'country' => 'CO',
            'currency' => 'COP',
            'iva_rates' => GastoIvaRateEnum::values(),
            'default_iva_rate' => GastoIvaRateEnum::GENERAL,
            'uvt_cop' => 49799,
        );
    }

    private function isSafeHttpUrl(string $url): bool
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, array('https', 'http'), true);
    }
}