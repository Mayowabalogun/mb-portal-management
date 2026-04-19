<?php
declare(strict_types=1);

require_once APP_ROOT . '/Repository/UnitRepository.php';

function mark_unit_reserved(mysqli $db, int $unitId): void
{
    (new UnitRepository($db))->markReserved($unitId);
}

function mark_unit_occupied(mysqli $db, int $unitId): void
{
    (new UnitRepository($db))->markOccupied($unitId);
}

function mark_unit_vacant(mysqli $db, int $unitId): void
{
    (new UnitRepository($db))->markVacant($unitId);
}

function mark_unit_holdover_sufferance(mysqli $db, int $unitId): void
{
    (new UnitRepository($db))->markHoldoverSufferance($unitId);
}

function recalc_property_occupancy(mysqli $db, int $propertyId): void
{
    (new UnitRepository($db))->recalcPropertyOccupancy($propertyId);
}
