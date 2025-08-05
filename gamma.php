<?php
// Скрипт Gamma - генерация статистики по заказам

require_once 'config.php';

function getOrdersStatistics() {
    $db = getDbConnection();
    if (!$db) {
        return ['error' => 'Не удалось подключиться к базе данных'];
    }

    try {
        // Получаем общую статистику заказов
        $totalOrdersStmt = $db->query("SELECT COUNT(*) as total FROM orders");
        $totalOrders = $totalOrdersStmt->fetch()['total'];

        if ($totalOrders == 0) {
            return [
                'total_orders' => 0,
                'message' => 'Заказов пока нет',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }

        // Получаем последние 100 заказов с детальной информацией (согласно ТЗ)
        $query = "
            SELECT 
                o.id,
                o.quantity,
                o.total_price,
                o.purchase_time,
                o.customer_name,
                p.name as product_name,
                p.price as product_price,
                c.name as category_name
            FROM orders o
            JOIN products p ON o.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            ORDER BY o.purchase_time DESC
            LIMIT 100
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $recentOrders = $stmt->fetchAll();

        if (empty($recentOrders)) {
            return [
                'total_orders' => $totalOrders,
                'message' => 'Нет данных для анализа',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }

        // Анализируем статистику по категориям (согласно ТЗ)
        $categoryStats = [];
        $totalQuantity = 0;
        $totalAmount = 0;

        foreach ($recentOrders as $order) {
            $category = $order['category_name'];
            
            if (!isset($categoryStats[$category])) {
                $categoryStats[$category] = [
                    'quantity' => 0,
                    'amount' => 0,
                    'orders_count' => 0
                ];
            }
            
            $categoryStats[$category]['quantity'] += $order['quantity'];
            $categoryStats[$category]['amount'] += $order['total_price'];
            $categoryStats[$category]['orders_count']++;
            
            $totalQuantity += $order['quantity'];
            $totalAmount += $order['total_price'];
        }

        // Сортируем категории по количеству товаров
        uasort($categoryStats, function($a, $b) {
            return $b['quantity'] - $a['quantity'];
        });

        // Вычисляем время между первым и последним заказом (согласно ТЗ)
        $firstOrder = end($recentOrders);
        $lastOrder = reset($recentOrders);
        
        $firstTime = strtotime($firstOrder['purchase_time']);
        $lastTime = strtotime($lastOrder['purchase_time']);
        $timeDifference = $lastTime - $firstTime;
        
        // Форматируем временную разницу
        $timeFormatted = formatTimeDifference($timeDifference);

        return [
            'total_orders_in_db' => $totalOrders,
            'analyzed_orders' => count($recentOrders),
            'period' => [
                'from' => $firstOrder['purchase_time'],
                'to' => $lastOrder['purchase_time'],
                'duration' => $timeFormatted,
                'duration_seconds' => $timeDifference
            ],
            'summary' => [
                'total_items' => $totalQuantity,
                'total_amount' => round($totalAmount, 2),
                'average_order_value' => round($totalAmount / count($recentOrders), 2),
                'categories_count' => count($categoryStats)
            ],
            'categories' => $categoryStats,
            'timestamp' => date('Y-m-d H:i:s'),
            'cached' => false
        ];

    } catch (Exception $e) {
        return ['error' => 'Ошибка при получении статистики: ' . $e->getMessage()];
    }
}

function formatTimeDifference($seconds) {
    if ($seconds < 60) {
        return $seconds . ' сек';
    } elseif ($seconds < 3600) {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return $minutes . ' мин ' . $remainingSeconds . ' сек';
    } elseif ($seconds < 86400) {
        $hours = floor($seconds / 3600);
        $remainingMinutes = floor(($seconds % 3600) / 60);
        return $hours . ' ч ' . $remainingMinutes . ' мин';
    } else {
        $days = floor($seconds / 86400);
        $remainingHours = floor(($seconds % 86400) / 3600);
        return $days . ' дн ' . $remainingHours . ' ч';
    }
}

// Кэширование для оптимизации производительности (согласно ТЗ)
function getCachedStatistics() {
    $redis = getRedisConnection();
    $cacheKey = 'gamma_statistics';
    $cacheTime = 0; // 0 или меньше — кэш отключён (всегда свежие данные)

    // Если кэш выключен, сразу возвращаем актуальную статистику
    if ($cacheTime <= 0) {
        return getOrdersStatistics();
    }
    
    if ($redis) {
        $cached = $redis->get($cacheKey);
        if ($cached) {
            $data = json_decode($cached, true);
            $data['cached'] = true;
            return $data;
        }
    }
    
    $stats = getOrdersStatistics();
    
    if ($redis && !isset($stats['error'])) {
        $redis->setex($cacheKey, $cacheTime, json_encode($stats));
    }
    
    return $stats;
}

// Обработка запроса
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    
    $statistics = getCachedStatistics();
    echo json_encode($statistics, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Метод не поддерживается']);
}
?>