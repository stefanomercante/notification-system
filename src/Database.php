<?php

declare(strict_types=1);

namespace NotificationSystem;

use PDO;
use PDOException;
use Redis;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../config/config.php';
            $dbConfig = $config['database'];

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $dbConfig['host'],
                $dbConfig['port'],
                $dbConfig['database'],
                $dbConfig['charset']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $dbConfig['username'],
                    $dbConfig['password'],
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                throw new \RuntimeException('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    public static function getRedis(): Redis
    {
        $config = require __DIR__ . '/../config/config.php';
        $redisConfig = $config['redis'];

        $redis = new Redis();
        $redis->connect($redisConfig['host'], $redisConfig['port']);
        $redis->setOption(Redis::OPT_PREFIX, $redisConfig['prefix']);

        return $redis;
    }
}
