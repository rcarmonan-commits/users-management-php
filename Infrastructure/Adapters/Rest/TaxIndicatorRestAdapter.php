<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../Application/Ports/Out/TaxIndicatorRestPort.php';
require_once __DIR__ . '/../../../Domain/Enums/GastoIvaRateEnum.php';

/**
 * Adaptador REST saliente hacia servicio externo de parámetros tributarios,
 * con degradación controlada (circuit-safe fallback) ante ausencia de red.
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
        if ($this->endpointUrl !== '') {
            $context = stream_context_create(array(
                'http' => array(
                    'method' => 'GET',
                    'timeout' => $this->timeoutSeconds,
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
}