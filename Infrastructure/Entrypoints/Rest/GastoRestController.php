<?php

declare(strict_types=1);

require_once __DIR__ . '/Dto/ApiRequest.php';
require_once __DIR__ . '/Dto/ApiResponse.php';
require_once __DIR__ . '/Mapper/RestApiMapper.php';

require_once __DIR__ . '/../../../Application/Ports/In/CreateGastoUseCase.php';
require_once __DIR__ . '/../../../Application/Ports/In/UpdateGastoUseCase.php';
require_once __DIR__ . '/../../../Application/Ports/In/GetGastoByIdUseCase.php';
require_once __DIR__ . '/../../../Application/Ports/In/GetAllGastosUseCase.php';
require_once __DIR__ . '/../../../Application/Ports/In/DeleteGastoUseCase.php';
require_once __DIR__ . '/../../../Application/Services/Dto/Commands/DeleteGastoCommand.php';
require_once __DIR__ . '/../../../Application/Services/Dto/Queries/GetAllGastosQuery.php';
require_once __DIR__ . '/../../../Application/Services/Dto/Queries/GetGastoByIdQuery.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — CONTROLADOR REST EJERCICIO #6: GastoRestController
 * ============================================================================
 * CAPA: Infraestructura — Entrypoint REST (`Infrastructure/Entrypoints/Rest`)
 *
 * ¿QUÉ HACE?
 *   Expone los endpoints RESTful JSON `/api/gastos` y `/api/gastos/{id}` invocando
 *   los mismos Puertos de Entrada (`*GastoUseCase`) que la interfaz Web HTML,
 *   demostrando reutilización pura de la capa de Aplicación Hexagonal.
 * ============================================================================
 */
final class GastoRestController
{
    private CreateGastoUseCase $createGastoUseCase;
    private UpdateGastoUseCase $updateGastoUseCase;
    private GetGastoByIdUseCase $getGastoByIdUseCase;
    private GetAllGastosUseCase $getAllGastosUseCase;
    private DeleteGastoUseCase $deleteGastoUseCase;

    public function __construct(
        CreateGastoUseCase $createGastoUseCase,
        UpdateGastoUseCase $updateGastoUseCase,
        GetGastoByIdUseCase $getGastoByIdUseCase,
        GetAllGastosUseCase $getAllGastosUseCase,
        DeleteGastoUseCase $deleteGastoUseCase
    ) {
        $this->createGastoUseCase = $createGastoUseCase;
        $this->updateGastoUseCase = $updateGastoUseCase;
        $this->getGastoByIdUseCase = $getGastoByIdUseCase;
        $this->getAllGastosUseCase = $getAllGastosUseCase;
        $this->deleteGastoUseCase = $deleteGastoUseCase;
    }

    public function list(ApiRequest $request): ApiResponse
    {
        $query = new GetAllGastosQuery(
            $request->query('nombreUsuario'),
            $request->query('fechaDesde'),
            $request->query('fechaHasta')
        );

        $gastos = $this->getAllGastosUseCase->execute($query);
        $items = array();
        $totalSinIva = 0.0;
        $totalIva = 0.0;
        $totalConIva = 0.0;

        foreach ($gastos as $gasto) {
            $items[] = RestApiMapper::gastoModelToArray($gasto);
            $totalSinIva += $gasto->valorTotalSinIVA()->toFloat();
            $totalIva += $gasto->ivaTotal()->toFloat();
            $totalConIva += $gasto->valorTotalConIVA()->toFloat();
        }

        return ApiResponse::success(array(
            'count' => count($items),
            'totals' => array(
                'valorTotalSinIVA' => round($totalSinIva, 2),
                'ivaTotal' => round($totalIva, 2),
                'valorTotalConIVA' => round($totalConIva, 2),
            ),
            'items' => $items,
        ));
    }

    public function getById(string $id): ApiResponse
    {
        $gasto = $this->getGastoByIdUseCase->execute(new GetGastoByIdQuery($id));

        return ApiResponse::success(RestApiMapper::gastoModelToArray($gasto));
    }

    public function create(ApiRequest $request, string $generatedId): ApiResponse
    {
        $command = RestApiMapper::toCreateGastoCommand($request, $generatedId);
        $created = $this->createGastoUseCase->execute($command);

        return ApiResponse::success(
            RestApiMapper::gastoModelToArray($created),
            201,
            'Gasto registrado exitosamente.'
        );
    }

    public function update(ApiRequest $request, string $id): ApiResponse
    {
        $command = RestApiMapper::toUpdateGastoCommand($request, $id);
        $updated = $this->updateGastoUseCase->execute($command);

        return ApiResponse::success(
            RestApiMapper::gastoModelToArray($updated),
            200,
            'Gasto actualizado exitosamente.'
        );
    }

    public function delete(string $id): ApiResponse
    {
        $this->deleteGastoUseCase->execute(new DeleteGastoCommand($id));

        return ApiResponse::success(
            array('id' => $id),
            200,
            'Gasto eliminado exitosamente.'
        );
    }
}