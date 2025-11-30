<?php

declare(strict_types=1);

namespace NotificationSystem;

use NotificationSystem\Channels\ChannelInterface;
use NotificationSystem\Channels\EmailChannel;
use NotificationSystem\Queue\QueueManager;

class NotificationService
{
    private Notification $notification;
    private QueueManager $queue;
    private array $channels = [];

    public function __construct()
    {
        $this->notification = new Notification();
        $this->queue = new QueueManager();
        $this->registerChannels();
    }

    public function send(string $recipient, string $channel, string $message, ?string $subject = null, ?array $data = null): string
    {
        $channelInstance = $this->getChannel($channel);

        if (!$channelInstance->validate($recipient)) {
            throw new \InvalidArgumentException("Invalid recipient for channel {$channel}: {$recipient}");
        }

        // Create notification record
        $uuid = $this->notification->create($recipient, $channel, $message, $subject, $data);

        // Queue for processing
        $this->queue->push($uuid, [
            'recipient' => $recipient,
            'channel' => $channel,
            'message' => $message,
            'subject' => $subject,
            'data' => $data,
        ]);

        $this->notification->updateStatus($uuid, 'queued');

        return $uuid;
    }

    public function sendNow(string $recipient, string $channel, string $message, ?string $subject = null, ?array $data = null): string
    {
        $channelInstance = $this->getChannel($channel);

        if (!$channelInstance->validate($recipient)) {
            throw new \InvalidArgumentException("Invalid recipient for channel {$channel}: {$recipient}");
        }

        $uuid = $this->notification->create($recipient, $channel, $message, $subject, $data);

        try {
            $this->notification->updateStatus($uuid, 'processing');
            $success = $channelInstance->send($recipient, $message, $subject, $data);

            if ($success) {
                $this->notification->updateStatus($uuid, 'sent');
            } else {
                $this->notification->updateStatus($uuid, 'failed', 'Send returned false');
            }
        } catch (\Exception $e) {
            $this->notification->updateStatus($uuid, 'failed', $e->getMessage());
            throw $e;
        }

        return $uuid;
    }

    public function getStatus(string $uuid): ?array
    {
        return $this->notification->getByUuid($uuid);
    }

    public function getStats(): array
    {
        return $this->notification->getStats();
    }

    public function getRecent(int $limit = 10): array
    {
        return $this->notification->getRecent($limit);
    }

    private function registerChannels(): void
    {
        $this->channels['email'] = new EmailChannel();
        // Future: $this->channels['sms'] = new SMSChannel();
        // Future: $this->channels['webhook'] = new WebhookChannel();
    }

    private function getChannel(string $channel): ChannelInterface
    {
        if (!isset($this->channels[$channel])) {
            throw new \InvalidArgumentException("Channel not supported: {$channel}");
        }

        return $this->channels[$channel];
    }
}
