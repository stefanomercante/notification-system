<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use NotificationSystem\Queue\QueueManager;
use NotificationSystem\Notification;
use NotificationSystem\Channels\EmailChannel;

echo "Worker started at " . date('Y-m-d H:i:s') . "\n";

$queue = new QueueManager();
$notification = new Notification();
$emailChannel = new EmailChannel();

$channels = [
    'email' => $emailChannel,
];

while (true) {
    try {
        $job = $queue->pop();

        if (!$job) {
            echo "Queue empty, waiting...\n";
            sleep(2);
            continue;
        }

        $uuid = $job['uuid'];
        $data = $job['data'];
        $attempts = $job['attempts'] ?? 0;

        echo "Processing notification {$uuid} (attempt " . ($attempts + 1) . ")\n";

        $notificationData = $notification->getByUuid($uuid);
        if (!$notificationData) {
            echo "Notification {$uuid} not found, skipping\n";
            continue;
        }

        // Check if already processed
        if (in_array($notificationData['status'], ['sent', 'failed'])) {
            echo "Notification {$uuid} already processed with status {$notificationData['status']}\n";
            continue;
        }

        $channel = $data['channel'];
        if (!isset($channels[$channel])) {
            echo "Channel {$channel} not supported\n";
            $notification->updateStatus($uuid, 'failed', "Channel not supported: {$channel}");
            continue;
        }

        $notification->updateStatus($uuid, 'processing');

        try {
            $success = $channels[$channel]->send(
                $data['recipient'],
                $data['message'],
                $data['subject'] ?? null,
                $data['data'] ?? null
            );

            if ($success) {
                $notification->updateStatus($uuid, 'sent');
                echo "✓ Notification {$uuid} sent successfully\n";
            } else {
                throw new \Exception('Send method returned false');
            }

        } catch (\Exception $e) {
            echo "✗ Error sending notification {$uuid}: " . $e->getMessage() . "\n";

            // Retry logic
            $maxAttempts = $notificationData['max_attempts'];
            if ($attempts < $maxAttempts - 1) {
                $delay = pow(2, $attempts) * 5; // Exponential backoff
                echo "Retrying in {$delay} seconds...\n";
                $queue->retry($uuid, $data, $delay);
            } else {
                $notification->updateStatus($uuid, 'failed', $e->getMessage());
                echo "Max attempts reached, marking as failed\n";
            }
        }

    } catch (\Exception $e) {
        echo "Worker error: " . $e->getMessage() . "\n";
        sleep(5);
    }

    usleep(100000); // 0.1 second between jobs
}
