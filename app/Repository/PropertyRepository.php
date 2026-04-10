<?php
declare(strict_types=1);

require_once APP_ROOT . '/connections/db.php';

/**
 * PropertyRepository
 *
 * Responsibility:
 * - Execute all landing-page property queries.
 * - Return raw arrays for the service layer.
 */
class PropertyRepository
{
    private mysqli $conn;

    public function __construct()
    {
        // Shared DB connection from app connection helper.
        $this->conn = getConnection();
    }

    /**
     * Get summary counts for the statistics section.
     */
    public function getPropertyStats(): array
    {
        $stats = [];

        // Available flats
        $stmt = $this->conn->query("SELECT COUNT(*) AS count FROM property_units WHERE unit_type = 'flat' AND status = 'Vacant'");
        $stats['flats'] = (int) ($stmt->fetch_assoc()['count'] ?? 0);

        // Available hostels
        $stmt = $this->conn->query("SELECT COUNT(*) AS count FROM property_units WHERE unit_type = 'hostel' AND status = 'Vacant'");
        $stats['hostels'] = (int) ($stmt->fetch_assoc()['count'] ?? 0);

        // Available shops
        $stmt = $this->conn->query("SELECT COUNT(*) AS count FROM property_units WHERE unit_type = 'shop' AND status = 'Vacant'");
        $stats['shops'] = (int) ($stmt->fetch_assoc()['count'] ?? 0);

        // Total tenants served
        $stmt = $this->conn->query('SELECT COUNT(*) AS count FROM tenants');
        $stats['tenants'] = (int) ($stmt->fetch_assoc()['count'] ?? 0);

        return $stats;
    }

    /**
     * Get latest vacant flats including location details.
     */
    public function getVacantFlats(int $limit = 5): array
    {
        $query = "
            SELECT
                pu.id AS unit_id,
                pu.unit_label,
                pu.rent_amount,
                pu.description,
                p.property_label,
                p.address,
                p.town_city,
                p.state
            FROM property_units pu
            JOIN properties p ON pu.property_id = p.property_id
            WHERE pu.unit_type = 'flat'
                AND pu.status = 'Vacant'
                AND p.is_active = 1
            ORDER BY pu.last_update DESC
            LIMIT ?
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $limit);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get latest vacant hostel spaces.
     */
    public function getVacantHostels(int $limit = 5): array
    {
        $query = "
            SELECT
                pu.id AS unit_id,
                pu.unit_label,
                pu.rent_amount,
                pu.description,
                p.property_label,
                p.address
            FROM property_units pu
            JOIN properties p ON pu.property_id = p.property_id
            WHERE pu.unit_type = 'hostel'
                AND pu.status = 'Vacant'
                AND p.is_active = 1
            ORDER BY pu.last_update DESC
            LIMIT ?
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $limit);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get latest vacant commercial shop spaces.
     */
    public function getVacantShops(int $limit = 5): array
    {
        $query = "
            SELECT
                pu.id AS unit_id,
                pu.unit_label,
                pu.rent_amount,
                pu.description,
                p.property_label,
                p.address
            FROM property_units pu
            JOIN properties p ON pu.property_id = p.property_id
            WHERE pu.unit_type = 'shop'
                AND pu.status = 'Vacant'
                AND p.is_active = 1
            ORDER BY pu.last_update DESC
            LIMIT ?
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $limit);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
