<?php

declare(strict_types=1);

namespace NotificationSystem\Channels;

interface ChannelInterface
{
    public function send(string $recipient, string $message, ?string $subject = null, ?array $data = null): bool;
    public function validate(string $recipient): bool;
}
