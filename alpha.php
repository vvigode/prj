<?php
// Скрипт Alpha - генерация заказов с защитой от повторного запуска

require_once 'config.php';

// Уникальный ключ для блокировки в Redis
$lockKey = 'alpha_script_lock';
$lockTimeout = 10; // Максимальное время работы скрипта в секундах

function generateOrder() {
    $db = getDbConnection();
    if (!$db) {
        return false;
    }

    try {
        // Получаем случайный продукт
        $stmt = $db->query("SELECT id, price FROM products ORDER BY RANDOM() LIMIT 1");
        $product = $stmt->fetch();
        
        if (!$product) {
            return false;
        }

        // Генерируем случайное количество (1-5)
        $quantity = rand(1, 5);
        $totalPrice = $product['price'] * $quantity;
        
        // Массив имен для случайного выбора
        $customerNames = ['Иван Петров', 'Анна Сидорова', 'Максим Козлов', 'Елена Волкова', 'Дмитрий Орлов'];
        $customerName = $customerNames[array_rand($customerNames)];

        // Вставляем заказ
        $stmt = $db->prepare("
            INSERT INTO orders (product_id, quantity, total_price, customer_name, purchase_time) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([$product['id'], $quantity, $totalPrice, $customerName]);
        
    } catch (Exception $e) {
        return false;
    }
}

// Основная логика скрипта
function runAlphaScript() {
    global $lockKey, $lockTimeout;
    
    $redis = getRedisConnection();
    if (!$redis) {
        return false;
    }

    // Проверяем блокировку
    if ($redis->exists($lockKey)) {
        return false; // Скрипт уже выполняется
    }

    // Устанавливаем блокировку
    $redis->setex($lockKey, $lockTimeout, getmypid());

    try {
        // Пауза для демонстрации эффекта:
        // - 1 секунда для одиночных HTTP вызовов (требование ТЗ)
        // - 0.001 секунды для массовых операций (MASS_MODE)
        if (defined('MASS_MODE')) {
            usleep(1000); // 0.001 c для массовых операций
        } else {
            sleep(1); // 1 c для одиночных вызовов
        }
        
        // Генерируем заказ
        $success = generateOrder();
        
        return $success;
        
    } finally {
        // Снимаем блокировку
        $redis->del($lockKey);
    }
}

// Если скрипт вызван напрямую
if (isset($_GET['run']) || php_sapi_name() === 'cli') {
    $result = runAlphaScript();
    
    // Для веб-запросов возвращаем JSON ответ
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        echo json_encode(['success' => $result, 'timestamp' => date('Y-m-d H:i:s')]);
    }
}
?>