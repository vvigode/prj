<?php
// Конфигурация подключения к базе данных и Redis

// Настройки PostgreSQL (поддержка Docker переменных окружения)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'order_system');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') ?: 'password');

// Настройки Redis (поддержка Docker переменных окружения)
define('REDIS_HOST', getenv('REDIS_HOST') ?: '127.0.0.1');
define('REDIS_PORT', getenv('REDIS_PORT') ?: 6379);
define('REDIS_PASSWORD', getenv('REDIS_PASSWORD') ?: ''); // Если есть пароль

// Функция подключения к PostgreSQL
function getDbConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo; // Используем уже открытое соединение
    }
    try {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}

// Функция подключения к Redis
function getRedisConnection() {
    try {
        $redis = new Redis();
        $redis->connect(REDIS_HOST, REDIS_PORT);
        if (REDIS_PASSWORD) {
            $redis->auth(REDIS_PASSWORD);
        }
        return $redis;
    } catch (Exception $e) {
        error_log("Redis connection failed: " . $e->getMessage());
        return null;
    }
}

// Логирование для отладки
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message");
}
?>