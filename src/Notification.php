<?php

declare(strict_types=1);

namespace NotificationSystem;

use PDO;

class Notification
{
    private PDO $db;
    private array $data;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(string $recipient, string $channel, string $message, ?string $subject = null, ?array $data = null): string
    {
        $uuid = $this->generateUuid();

        $sql = "INSERT INTO notifications (uuid, recipient, channel, subject, message, data, status, created_at) 
                VALUES (:uuid, :recipient, :channel, :subject, :message, :data, 'pending', NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':uuid' => $uuid,
            ':recipient' => $recipient,
            ':channel' => $channel,
            ':subject' => $subject,
            ':message' => $message,
            ':data' => $data ? json_encode($data) : null,
        ]);

        $this->log($uuid, 'created', ['recipient' => $recipient, 'channel' => $channel]);

        return $uuid;
    }

    public function updateStatus(string $uuid, string $status, ?string $errorMessage = null): bool
    {
        $sql = "UPDATE notifications SET status = :status, updated_at = NOW()";
        $params = [':uuid' => $uuid, ':status' => $status];

        if ($status === 'sent') {
            $sql .= ", sent_at = NOW()";
        } elseif ($status === 'failed') {
            $sql .= ", failed_at = NOW(), error_message = :error, attempts = attempts + 1";
            $params[':error'] = $errorMessage;
        }

        $sql .= " WHERE uuid = :uuid";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);

        $this->log($uuid, 'status_changed', ['status' => $status, 'error' => $errorMessage]);

        return $result;
    }

    public function getByUuid(string $uuid): ?array
    {
        $sql = "SELECT * FROM notifications WHERE uuid = :uuid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uuid' => $uuid]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function getStats(): array
    {
        $sql = "SELECT 
                    channel,
                    status,
                    COUNT(*) as count,
                    DATE(created_at) as date
                FROM notifications 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY channel, status, DATE(created_at)
                ORDER BY date DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function getRecent(int $limit = 10): array
    {
        $sql = "SELECT uuid, recipient, channel, subject, status, sent_at, failed_at, created_at 
                FROM notifications 
                ORDER BY created_at DESC 
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    private function log(string $uuid, string $event, array $details = []): void
    {
        $notification = $this->getByUuid($uuid);
        if (!$notification) {
            return;
        }

        $sql = "INSERT INTO notification_logs (notification_id, event, details, created_at) 
                VALUES (:notification_id, :event, :details, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':notification_id' => $notification['id'],
            ':event' => $event,
            ':details' => json_encode($details),
        ]);
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
