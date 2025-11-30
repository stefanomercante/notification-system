<?php

declare(strict_types=1);

namespace NotificationSystem\Queue;

use Redis;
use NotificationSystem\Database;

class QueueManager
{
    private Redis $redis;
    private string $queueName;

    public function __construct(string $queueName = 'default')
    {
        $this->redis = Database::getRedis();
        $this->queueName = $queueName;
    }

    public function push(string $uuid, array $data, int $delay = 0): bool
    {
        $payload = json_encode([
            'uuid' => $uuid,
            'data' => $data,
            'attempts' => 0,
            'queued_at' => time(),
        ]);

        if ($delay > 0) {
            $score = time() + $delay;
            return $this->redis->zAdd("queue:delayed:{$this->queueName}", $score, $payload) !== false;
        }

        return $this->redis->rPush("queue:{$this->queueName}", $payload) !== false;
    }

    public function pop(): ?array
    {
        // Check for delayed jobs that are ready
        $this->migrateDelayedJobs();

        $payload = $this->redis->lPop("queue:{$this->queueName}");

        if (!$payload) {
            return null;
        }

        return json_decode($payload, true);
    }

    public function retry(string $uuid, array $data, int $delay = 5): bool
    {
        $data['attempts'] = ($data['attempts'] ?? 0) + 1;
        return $this->push($uuid, $data, $delay);
    }

    public function size(): int
    {
        return $this->redis->lLen("queue:{$this->queueName}");
    }

    public function clear(): void
    {
        $this->redis->del("queue:{$this->queueName}");
        $this->redis->del("queue:delayed:{$this->queueName}");
    }

    private function migrateDelayedJobs(): void
    {
        $now = time();
        $jobs = $this->redis->zRangeByScore(
            "queue:delayed:{$this->queueName}",
            0,
            $now
        );

        foreach ($jobs as $job) {
            $this->redis->rPush("queue:{$this->queueName}", $job);
            $this->redis->zRem("queue:delayed:{$this->queueName}", $job);
        }
    }
}
