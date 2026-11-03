<?php

declare(strict_types=1);

require_once __DIR__ . '/../Dto/CreateGastoRequest.php';
require_once __DIR__ . '/../Dto/UpdateGastoRequest.php';
require_once __DIR__ . '/../Dto/GastoResponse.php';

require_once __DIR__ . '/../../../../../Application/Services/Dto/Commands/CreateGastoCommand.php';
require_once __DIR__ . '/../../../../../Application/Services/Dto/Commands/UpdateGastoCommand.php';
require_once __DIR__ . '/../../../../../Application/Services/Dto/Commands/DeleteGastoCommand.php';
require_once __DIR__ . '/../../../../../Application/Services/Dto/Queries/GetGastoByIdQuery.php';
require_once __DIR__ . '/../../../../../Application/Services/Dto/Queries/GetAllGastosQuery.php';

require_once __DIR__ . '/../../../../../Domain/Models/GastoModel.php';

/**
 * ============================================================================
 * GUÍA ARQUITECTÓNICA — TRADUCTOR DE FRONTERA WEB: GastoWebMapper
 * ============================================================================
 * CAPA: Infraestructura — Entrypoint Web (`Infrastructure/Entrypoints/Web/Controllers/Mapper`)
 *
 * ¿QUÉ HACE?
 *   Convierte peticiones Web (`CreateGastoWebRequest`, `UpdateGastoWebRequest`, filtros)
 *   en mensajes CQRS de Aplicación (`*Command` y `*Query`), y transforma el agregado
 *   `GastoModel` de vuelta a `GastoResponse` para la capa de presentación.
 * ============================================================================
 */
final class GastoWebMapper
{
    public function fromCreateRequestToCommand(CreateGastoWebRequest $request): CreateGastoCommand
    {
        return new CreateGastoCommand(
            $request->getId(),
            $request->getFecha(),
            $request->getValorTotalSinIVA(),
            $request->getIvaTotal(),
            $request->getValorTotalConIVA(),
            $request->getNombreUsuario(),
            $request->getLugar(),
            $request->getDescripcion()
        );
    }

    public function fromUpdateRequestToCommand(UpdateGastoWebRequest $request): UpdateGastoCommand
    {
        return new UpdateGastoCommand(
            $request->getId(),
            $request->getFecha(),
            $request->getValorTotalSinIVA(),
            $request->getIvaTotal(),
            $request->getValorTotalConIVA(),
            $request->getNombreUsuario(),
            $request->getLugar(),
            $request->getDescripcion()
        );
    }

    public function fromFiltersToGetAllQuery(
        ?string $nombreUsuario = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null
    ): GetAllGastosQuery {
        return new GetAllGastosQuery($nombreUsuario, $fechaDesde, $fechaHasta);
    }

    public function fromIdToGetByIdQuery(string $id): GetGastoByIdQuery
    {
        return new GetGastoByIdQuery($id);
    }

    public function fromIdToDeleteCommand(string $id): DeleteGastoCommand
    {
        return new DeleteGastoCommand($id);
    }

    public function fromModelToResponse(GastoModel $gasto): GastoResponse
    {
        return new GastoResponse(
            $gasto->id()->value(),
            $gasto->fecha()->value(),
            $gasto->valorTotalSinIVA()->toFloat(),
            $gasto->ivaTotal()->toFloat(),
            $gasto->valorTotalConIVA()->toFloat(),
            $gasto->nombreUsuario()->value(),
            $gasto->lugar()->value(),
            $gasto->descripcion()->value()
        );
    }

    /**
     * @param GastoModel[] $gastos
     * @return GastoResponse[]
     */
    public function fromModelsToResponses(array $gastos): array
    {
        $responses = array();

        foreach ($gastos as $gasto) {
            $responses[] = $this->fromModelToResponse($gasto);
        }

        return $responses;
    }
}