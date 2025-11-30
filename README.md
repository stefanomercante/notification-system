# Notification System

Multi-channel notification system with queue processing, built with PHP 8.4, Redis, MySQL, and Docker.

## Features

- ✅ Multi-channel support (Email, SMS*, Webhook*)
- ✅ Queue-based processing with Redis
- ✅ Retry mechanism with exponential backoff
- ✅ Real-time dashboard with statistics
- ✅ RESTful API
- ✅ Docker containerized
- ✅ Background worker process
- ✅ Tracking and logging

*SMS and Webhook channels coming soon

## Architecture

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Client    │────▶│  REST API   │────▶│    Redis    │
│ Application │     │ (Nginx+PHP) │     │    Queue    │
└─────────────┘     └─────────────┘     └─────────────┘
                            │                    │
                            ▼                    ▼
                    ┌─────────────┐     ┌─────────────┐
                    │    MySQL    │     │   Worker    │
                    │  Database   │◀────│   Process   │
                    └─────────────┘     └─────────────┘
                                               │
                                               ▼
                                        ┌─────────────┐
                                        │  Channels   │
                                        │ (Email/SMS) │
                                        └─────────────┘
```

## Quick Start

### 1. Start Docker containers

```bash
cd projects/notification-system
docker-compose up -d
```

Services will be available at:
- **API**: http://localhost:8080
- **Dashboard**: http://localhost:8080/dashboard.html
- **MailHog UI**: http://localhost:8025

### 2. Install dependencies

```bash
docker-compose exec php composer install
```

### 3. Test the system

Send a test notification:

```bash
curl -X POST http://localhost:8080/api/send \
  -H "Content-Type: application/json" \
  -d '{
    "recipient": "test@example.com",
    "channel": "email",
    "subject": "Test Notification",
    "message": "Hello from Notification System!"
  }'
```

## API Endpoints

### POST /api/send
Send a notification (queued processing)

**Request:**
```json
{
  "recipient": "user@example.com",
  "channel": "email",
  "subject": "Hello",
  "message": "Your notification message",
  "data": {"key": "value"},
  "immediate": false
}
```

**Response:**
```json
{
  "success": true,
  "uuid": "550e8400-e29b-41d4-a716-446655440000",
  "message": "Notification queued"
}
```

### GET /api/status/{uuid}
Get notification status

**Response:**
```json
{
  "success": true,
  "notification": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "recipient": "user@example.com",
    "channel": "email",
    "status": "sent",
    "sent_at": "2024-01-15 10:30:00",
    "created_at": "2024-01-15 10:29:55"
  }
}
```

### GET /api/stats
Get system statistics

**Response:**
```json
{
  "success": true,
  "stats": [
    {"channel": "email", "status": "sent", "count": 42, "date": "2024-01-15"},
    {"channel": "email", "status": "failed", "count": 2, "date": "2024-01-15"}
  ]
}
```

### GET /api/recent?limit=10
Get recent notifications

## Dashboard

Access the web dashboard at http://localhost:8080/dashboard.html

Features:
- Real-time statistics
- Channel and status charts
- Recent notifications table
- Send test notifications
- Auto-refresh every 5 seconds

## Configuration

Edit `config/config.php` to customize:

- Database connection
- Redis settings
- Email SMTP (MailHog by default)
- Retry attempts and delays
- Channel configurations

## Development

### Watch logs

```bash
# Worker logs
docker-compose logs -f worker

# Nginx logs
docker-compose logs -f nginx

# PHP logs
docker-compose logs -f php
```

### Access containers

```bash
# PHP container
docker-compose exec php sh

# MySQL
docker-compose exec mysql mysql -u notifyuser -pnotifypass notifications
```

### Run tests

```bash
docker-compose exec php vendor/bin/phpunit
```

## Production Deployment

1. Update `config/config.php`:
   - Set `env` to `production`
   - Set `debug` to `false`
   - Configure real SMTP server (not MailHog)

2. Use environment variables for sensitive data:
   ```bash
   export DB_PASSWORD="secure_password"
   export TWILIO_AUTH_TOKEN="your_token"
   ```

3. Enable SSL/TLS in Nginx configuration

4. Set up log rotation for production logs

## Tech Stack

- **PHP 8.4** (FPM)
- **Nginx** (Web server)
- **Redis 7** (Queue + Cache)
- **MySQL 8.0** (Database)
- **MailHog** (Email testing)
- **Docker** (Containerization)

## License

MIT

## Author

Stefano Mercante - [https://stefano-mercante.com](https://stefano-mercante.com)
