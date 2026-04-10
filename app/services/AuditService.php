<?php
declare(strict_types=1);

require_once APP_ROOT . '/Repository/AuditRepository.php';

class AuditService
{
    private static ?self $instance = null;

    private AuditRepository $repo;
    private array $context;
    private string $requestId;

    public function __construct(?AuditRepository $repo = null)
    {
        $this->repo = $repo ?? new AuditRepository();
        $this->context = $this->buildContext();
        $this->requestId = uniqid('req_', true);
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function record(
        string $eventKey,
        ?int $entityId = null,
        string $entityType = 'system',
        array $payload = [],
        string $reason = ''
    ): bool {
        $enriched = array_merge($payload, [
            'request_id' => $this->requestId,
            'timestamp' => date('Y-m-d H:i:s'),
            'actor' => $this->context['actor'],
            'actor_role' => $this->context['actor_role'],
            'session_id' => $this->context['session_id'],
            'reason' => $reason,
        ]);

        return $this->repo->insertAudit(
            $eventKey,
            $entityId,
            $entityType,
            json_encode($enriched, JSON_UNESCAPED_SLASHES),
            $this->context['actor_id'],
            $this->context['ip']
        );
    }

    public function mutate(
        string $eventKey,
        int $entityId,
        string $entityType,
        array $oldState,
        array $newState,
        string $reason = ''
    ): bool {
        $diff = [];
        foreach ($newState as $key => $value) {
            $oldValue = $oldState[$key] ?? null;
            if ($oldValue !== $value) {
                $diff[$key] = ['from' => $oldValue, 'to' => $value];
            }
        }

        if ($diff === []) {
            return true;
        }

        return $this->record($eventKey, $entityId, $entityType, [
            'old_state' => $oldState,
            'new_state' => $newState,
            'diff' => $diff,
            'changed_fields' => array_keys($diff),
        ], $reason);
    }

    public function failed(string $eventKey, string $reason, array $context = []): bool
    {
        return $this->record($eventKey . '_FAILED', $context['entity_id'] ?? null, $context['entity_type'] ?? 'system', $context, $reason);
    }

    public function portal(string $action, string $portalType = 'admin'): bool
    {
        if ($this->context['actor_id'] <= 0) {
            return false;
        }

        return $this->repo->insertPortalAccess(
            $portalType,
            $this->context['actor_id'],
            $action,
            $this->context['ip'],
            $this->context['user_agent']
        );
    }

    private function buildContext(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return [
            'actor' => (string) ($_SESSION['username'] ?? 'system'),
            'actor_id' => (int) ($_SESSION['user_id'] ?? 0),
            'actor_role' => (string) ($_SESSION['level'] ?? $_SESSION['role'] ?? 'unknown'),
            'ip' => $this->getClientIp(),
            'user_agent' => (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'),
            'session_id' => session_id() ?: '',
        ];
    }

    private function getClientIp(): string
    {
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'CF_CONNECTING_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                return trim(explode(',', (string) $_SERVER[$header])[0]);
            }
        }
        return '0.0.0.0';
    }
}
