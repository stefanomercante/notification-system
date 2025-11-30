<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use NotificationSystem\NotificationService;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/');

try {
    $service = new NotificationService();

    // POST /api/send - Send notification
    if ($method === 'POST' && $path === '/api/send') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['recipient'], $input['channel'], $input['message'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields: recipient, channel, message']);
            exit;
        }

        $immediate = $input['immediate'] ?? false;

        if ($immediate) {
            $uuid = $service->sendNow(
                $input['recipient'],
                $input['channel'],
                $input['message'],
                $input['subject'] ?? null,
                $input['data'] ?? null
            );
        } else {
            $uuid = $service->send(
                $input['recipient'],
                $input['channel'],
                $input['message'],
                $input['subject'] ?? null,
                $input['data'] ?? null
            );
        }

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'uuid' => $uuid,
            'message' => $immediate ? 'Notification sent' : 'Notification queued',
        ]);
        exit;
    }

    // GET /api/status/{uuid} - Get notification status
    if ($method === 'GET' && preg_match('#^/api/status/([a-f0-9-]+)$#', $path, $matches)) {
        $uuid = $matches[1];
        $notification = $service->getStatus($uuid);

        if (!$notification) {
            http_response_code(404);
            echo json_encode(['error' => 'Notification not found']);
            exit;
        }

        echo json_encode(['success' => true, 'notification' => $notification]);
        exit;
    }

    // GET /api/stats - Get statistics
    if ($method === 'GET' && $path === '/api/stats') {
        $stats = $service->getStats();
        echo json_encode(['success' => true, 'stats' => $stats]);
        exit;
    }

    // GET /api/recent - Get recent notifications
    if ($method === 'GET' && $path === '/api/recent') {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $recent = $service->getRecent($limit);
        echo json_encode(['success' => true, 'notifications' => $recent]);
        exit;
    }

    // Route not found
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);

} catch (\InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
