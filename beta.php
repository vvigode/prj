<?php
// Скрипт Beta - запуск Alpha скрипта N раз одновременно

require_once 'config.php';

function runAlphaMultiple($count) {
    // Для надёжного заполнения БД вызываем Alpha напрямую в цикле (массовый режим).
    // Это устраняет конфликты блокировки Redis и гарантирует генерацию N заказов без задержек.
    logMessage("Beta: Запуск $count экземпляров Alpha (массовый режим)");
    return runAlphaSequential($count);
}

function runAlphaSequential($count) {
    $results = [];
    $startTime = microtime(true);
    $successCount = 0;
    $errorCount = 0;
    
    // Помечаем массовый режим для отключения задержек
    if (!defined('MASS_MODE')) define('MASS_MODE', true);
    // Подключаем alpha.php для использования его функций
    require_once 'alpha.php';
    
    // Запускаем Alpha скрипт N раз в цикле для больших объемов
    for ($i = 0; $i < $count; $i++) {
        try {
            // Вызываем функцию runAlphaScript напрямую
            ob_start();
            $success = runAlphaScript();
            ob_get_clean();
            
            if ($success) {
                $successCount++;
                $results[] = ['index' => $i + 1, 'status' => 'success'];
            } else {
                $errorCount++;
                $results[] = ['index' => $i + 1, 'status' => 'error'];
            }
            
        } catch (Exception $e) {
            $errorCount++;
            $results[] = ['index' => $i + 1, 'status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    
    return [
        'total_requests' => $count,
        'successful' => $successCount,
        'errors' => $errorCount,
        'execution_time' => $executionTime,
        'results' => $results
    ];
}

// Обработка запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['count'])) {
    $count = 1000; // По умолчанию
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $count = isset($input['count']) ? (int)$input['count'] : $count;
    } elseif (isset($_GET['count'])) {
        $count = (int)$_GET['count'];
    }
    
    // Ограничиваем количество запросов для безопасности
    $count = min(max($count, 1), 5000);
    
    $result = runAlphaMultiple($count);

    // Сбрасываем кэш статистики Gamma, чтобы клиент сразу получил свежие данные
    $redis = getRedisConnection();
    if ($redis) {
        $redis->del('gamma_statistics');
    }
    
    header('Content-Type: application/json');
    echo json_encode($result);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Метод не поддерживается']);
}
?>