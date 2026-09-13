<?php

declare(strict_types=1);

require_once __DIR__ . '/EventDomain.php';
require_once __DIR__ . '/../Models/GastoModel.php';

final class GastoUpdatedDomainEvent extends DomainEvent
{
    private GastoModel $gasto;

    public function __construct(GastoModel $gasto)
    {
        parent::__construct('gasto.updated');
        $this->gasto = $gasto;
    }

    public function gasto(): GastoModel
    {
        return $this->gasto;
    }

    /**
     * @return array<string, float|string>
     */
    public function payload(): array
    {
        return $this->gasto->toArray();
    }
}