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
        $sql = 'INSERT INTO audit_logs (event_key, entity_id, entity_type, payload, actor_id, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('sissis', $eventKey, $entityId, $entityType, $payload, $actorId, $ip);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function insertPortalAccess(
        string $portalType,
        int $entityId,
        string $action,
        string $ip,
        string $agent
    ): bool {
        $sql = 'INSERT INTO portal_access_logs (portal_type, entity_id, action, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('sisss', $portalType, $entityId, $action, $ip, $agent);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getRecentActivity(int $limit = 20): array
    {
        $safeLimit = max(1, min($limit, 100));
        $sql = "SELECT a.id, a.event_key, a.entity_id, a.entity_type, a.payload, a.actor_id, a.ip_address, a.created_at,
                       COALESCE(u.full_name, pa.username, JSON_UNQUOTE(JSON_EXTRACT(a.payload, '$.actor')), 'System') AS actor_name
                FROM audit_logs a
                LEFT JOIN users u ON a.actor_id = u.id
                LEFT JOIN portal_accounts pa ON u.portal_account_id = pa.id
                ORDER BY a.created_at DESC
                LIMIT {$safeLimit}";

        $result = $this->db->query($sql);
        if (!$result) {
            return [];
        }

        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();

        foreach ($rows as &$row) {
            $decoded = json_decode((string) ($row['payload'] ?? '{}'), true);
            $row['payload'] = is_array($decoded) ? $decoded : [];
        }

        return $rows;
    }

    public function getUnifiedRecentActivity(int $limit = 20): array
    {
        $safeLimit = max(1, min($limit, 100));
        $sql = "SELECT * FROM (
                    SELECT
                        CAST(a.id AS CHAR) AS row_id,
                        'audit' AS source,
                        a.event_key AS event_key,
                        COALESCE(a.entity_type, 'system') AS entity_type,
                        a.entity_id AS entity_id,
                        a.payload AS payload_json,
                        COALESCE(u.full_name, pa.username, JSON_UNQUOTE(JSON_EXTRACT(a.payload, '$.actor')), 'System') AS actor_name,
                        a.ip_address AS ip_address,
                        a.created_at AS created_at
                    FROM audit_logs a
                    LEFT JOIN users u ON a.actor_id = u.id
                    LEFT JOIN portal_accounts pa ON u.portal_account_id = pa.id

                    UNION ALL

                    SELECT
                        CAST(ag.id AS CHAR) AS row_id,
                        'agreement' AS source,
                        CONCAT('agreement.', ag.event_type) AS event_key,
                        'agreement' AS entity_type,
                        ag.agreement_id AS entity_id,
                        ag.meta AS payload_json,
                        ag.actor AS actor_name,
                        NULL AS ip_address,
                        ag.created_at AS created_at
                    FROM agreement_events ag

                    UNION ALL

                    SELECT
                        CAST(se.id AS CHAR) AS row_id,
                        'signature' AS source,
                        CONCAT('signature.', se.event_type) AS event_key,
                        'agreement' AS entity_type,
                        se.agreement_id AS entity_id,
                        se.meta AS payload_json,
                        se.actor AS actor_name,
                        se.ip_address AS ip_address,
                        se.created_at AS created_at
                    FROM signature_events se

                    UNION ALL

                    SELECT
                        CAST(el.id AS CHAR) AS row_id,
                        'email' AS source,
                        CONCAT('email.', el.status) AS event_key,
                        'email' AS entity_type,
                        el.id AS entity_id,
                        JSON_OBJECT('to_email', el.to_email, 'subject', el.subject, 'category', el.category) AS payload_json,
                        el.to_email AS actor_name,
                        NULL AS ip_address,
                        el.created_at AS created_at
                    FROM email_log el

                    UNION ALL

                    SELECT
                        CAST(cr.id AS CHAR) AS row_id,
                        'cron' AS source,
                        CONCAT('cron.', cr.status) AS event_key,
                        cr.cron_name AS entity_type,
                        cr.id AS entity_id,
                        cr.summary AS payload_json,
                        cr.cron_name AS actor_name,
                        NULL AS ip_address,
                        cr.created_at AS created_at
                    FROM cron_runs cr

                    UNION ALL

                    SELECT
                        CAST(lt.id AS CHAR) AS row_id,
                        'login' AS source,
                        IF(lt.success = 1, 'login.success', 'login.failed') AS event_key,
                        'user' AS entity_type,
                        lt.user_id AS entity_id,
                        JSON_OBJECT('username', lt.username, 'portal', lt.portal, 'failure_reason', lt.failure_reason) AS payload_json,
                        lt.username AS actor_name,
                        lt.ip_address AS ip_address,
                        lt.attempted_at AS created_at
                    FROM login_telemetry lt
                ) unified
                ORDER BY created_at DESC
                LIMIT {$safeLimit}";

        $result = $this->db->query($sql);
        if (!$result) {
            return [];
        }

        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();

        foreach ($rows as &$row) {
            $decoded = json_decode((string) ($row['payload_json'] ?? '{}'), true);
            $row['payload'] = is_array($decoded) ? $decoded : [];
        }

        return $rows;
    }

    public function getUnifiedTrends(int $days = 7): array
    {
        $safeDays = max(1, min($days, 30));
        $sql = "SELECT DATE(created_at) AS day_key, COUNT(*) AS total_events
                FROM (
                    SELECT created_at FROM audit_logs WHERE created_at >= (NOW() - INTERVAL {$safeDays} DAY)
                    UNION ALL
                    SELECT created_at FROM agreement_events WHERE created_at >= (NOW() - INTERVAL {$safeDays} DAY)
                    UNION ALL
                    SELECT created_at FROM signature_events WHERE created_at >= (NOW() - INTERVAL {$safeDays} DAY)
                    UNION ALL
                    SELECT created_at FROM email_log WHERE created_at >= (NOW() - INTERVAL {$safeDays} DAY)
                    UNION ALL
                    SELECT created_at FROM cron_runs WHERE created_at >= (NOW() - INTERVAL {$safeDays} DAY)
                    UNION ALL
                    SELECT attempted_at AS created_at FROM login_telemetry WHERE attempted_at >= (NOW() - INTERVAL {$safeDays} DAY)
                ) all_events
                GROUP BY DATE(created_at)
                ORDER BY day_key ASC";

        $result = $this->db->query($sql);
        if (!$result) {
            return [];
        }

        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        return $rows;
    }

    public function getUnifiedCriticalAlerts(int $limit = 10): array
    {
        $safeLimit = max(1, min($limit, 100));
        $sql = "SELECT * FROM (
                    SELECT 'audit' AS source, CAST(id AS CHAR) AS row_id, event_key, entity_type, entity_id, ip_address, created_at
                    FROM audit_logs
                    WHERE event_key LIKE '%_FAILED' OR event_key LIKE '%_ERROR'

                    UNION ALL

                    SELECT 'email' AS source, CAST(id AS CHAR) AS row_id, 'email.failed' AS event_key, 'email' AS entity_type, id AS entity_id, NULL AS ip_address, created_at
                    FROM email_log
                    WHERE status = 'failed'

                    UNION ALL

                    SELECT 'cron' AS source, CAST(id AS CHAR) AS row_id, CONCAT('cron.', status) AS event_key, cron_name AS entity_type, id AS entity_id, NULL AS ip_address, created_at
                    FROM cron_runs
                    WHERE status IN ('failed', 'partial')

                    UNION ALL

                    SELECT 'login' AS source, CAST(id AS CHAR) AS row_id, 'login.failed' AS event_key, 'user' AS entity_type, user_id AS entity_id, ip_address, attempted_at AS created_at
                    FROM login_telemetry
                    WHERE success = 0
                ) critical_events
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
        $sql = "SELECT SUM(cnt) AS total FROM (
                    SELECT COUNT(*) AS cnt FROM audit_logs WHERE created_at >= (NOW() - INTERVAL 1 DAY)
                    UNION ALL
                    SELECT COUNT(*) AS cnt FROM agreement_events WHERE created_at >= (NOW() - INTERVAL 1 DAY)
                    UNION ALL
                    SELECT COUNT(*) AS cnt FROM signature_events WHERE created_at >= (NOW() - INTERVAL 1 DAY)
                    UNION ALL
                    SELECT COUNT(*) AS cnt FROM email_log WHERE created_at >= (NOW() - INTERVAL 1 DAY)
                    UNION ALL
                    SELECT COUNT(*) AS cnt FROM cron_runs WHERE created_at >= (NOW() - INTERVAL 1 DAY)
                    UNION ALL
                    SELECT COUNT(*) AS cnt FROM login_telemetry WHERE attempted_at >= (NOW() - INTERVAL 1 DAY)
                ) counts";

        $result = $this->db->query($sql);
        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc() ?: ['total' => 0];
        $result->free();
        return (int) ($row['total'] ?? 0);
    }

    public function countFailedActions(): int
    {
        $sql = "SELECT SUM(cnt) AS total FROM (
                    SELECT COUNT(*) AS cnt FROM audit_logs WHERE event_key LIKE '%_FAILED' OR event_key LIKE '%_ERROR'
                    UNION ALL
                    SELECT COUNT(*) AS cnt FROM email_log WHERE status = 'failed'
                    UNION ALL
                    SELECT COUNT(*) AS cnt FROM cron_runs WHERE status IN ('failed', 'partial')
                    UNION ALL
                    SELECT COUNT(*) AS cnt FROM login_telemetry WHERE success = 0
                ) counts";

        $result = $this->db->query($sql);
        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc() ?: ['total' => 0];
        $result->free();
        return (int) ($row['total'] ?? 0);
    }
}
