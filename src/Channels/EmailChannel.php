<?php

declare(strict_types=1);

namespace NotificationSystem\Channels;

class EmailChannel implements ChannelInterface
{
    private array $config;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/config.php';
        $this->config = $config['channels']['email'];
    }

    public function send(string $recipient, string $message, ?string $subject = null, ?array $data = null): bool
    {
        if (!$this->validate($recipient)) {
            throw new \InvalidArgumentException("Invalid email address: {$recipient}");
        }

        $headers = [
            'From' => sprintf('%s <%s>', $this->config['from']['name'], $this->config['from']['address']),
            'Reply-To' => $this->config['from']['address'],
            'X-Mailer' => 'PHP/' . phpversion(),
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/html; charset=UTF-8',
        ];

        $headerString = '';
        foreach ($headers as $key => $value) {
            $headerString .= "{$key}: {$value}\r\n";
        }

        $htmlMessage = $this->wrapInTemplate($message, $subject, $data);

        // PHP mail() now configured to use msmtp -> MailHog
        return mail($recipient, $subject ?? 'Notification', $htmlMessage, $headerString);
    }

    public function validate(string $recipient): bool
    {
        return filter_var($recipient, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function wrapInTemplate(string $message, ?string $subject, ?array $data): string
    {
        $template = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; }
        .footer { background: #f1f1f1; padding: 10px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 5px 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>%s</h2>
    </div>
    <div class="content">
        %s
    </div>
    <div class="footer">
        <p>This is an automated notification from Notification System</p>
    </div>
</body>
</html>
HTML;

        return sprintf($template, htmlspecialchars($subject ?? 'Notification'), $message);
    }
}
