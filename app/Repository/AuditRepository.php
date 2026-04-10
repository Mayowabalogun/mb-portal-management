<?php
declare(strict_types=1);

require_once APP_ROOT . '/connections/db.php';

class AuditRepository
{
    private mysqli $db;

    public function __construct(?mysqli $db = null)
    {
        $this->db = $db ?? getConnection();
    }

    public function insertAudit(
        string $eventKey,
        ?int $entityId,
        string $entityType,
        string $payload,
        int $actorId,
        string $ip
    ): bool {
        $stmt = $this->db->prepare('INSERT INTO audit_logs (event_key, entity_id, entity_type, payload, actor_id, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('sissis', $eventKey, $entityId, $entityType, $payload, $actorId, $ip);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function insertPortalAccess(
        string $portalType,
        int $entityId,
        string $action,
        string $ip,
        string $agent
    ): bool {
        $stmt = $this->db->prepare('INSERT INTO portal_access_logs (portal_type, entity_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('sisss', $portalType, $entityId, $action, $ip, $agent);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }


    public function getRecentActivity(int $limit = 20): array
    {
        $safeLimit = max(1, min($limit, 100));

        $sql = "SELECT id, event_key, entity_id, entity_type, payload, actor_id, ip_address, created_at
                FROM audit_logs
                ORDER BY created_at DESC
                LIMIT {$safeLimit}";

        $result = $this->db->query($sql);
        if (!$result) {
            return [];
        }

        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();

        return $rows;
    }

    public function countActionsLast24h(): int
    {
        $result = $this->db->query("SELECT COUNT(*) AS total FROM audit_logs WHERE created_at >= (NOW() - INTERVAL 1 DAY)");
        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc() ?: ['total' => 0];
        $result->free();

        return (int) ($row['total'] ?? 0);
    }

    public function countFailedActions(): int
    {
        $result = $this->db->query("SELECT COUNT(*) AS total FROM audit_logs WHERE event_key LIKE '%_FAILED'");
        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc() ?: ['total' => 0];
        $result->free();

        return (int) ($row['total'] ?? 0);
    }

}
