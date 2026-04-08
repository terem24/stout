<?php
/**
 * Обработчик отправки сметы на Email
 * Возвращает строго JSON. Любые ошибки подавляются для чистоты вывода.
 */

// Отключаем вывод системных ошибок в браузер
error_reporting(0);
ini_set('display_errors', 0);

// Заголовок JSON
header("Content-Type: application/json; charset=UTF-8");

// Читаем входные данные
$json = file_get_contents("php://input");
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Данные не получены или неверный формат"]);
    exit;
}

$to = "kovdor24@yandex.ru";
$projectName = $data['projectName'] ?? 'Без названия';
$subject = "Новая смета: " . $projectName;

// Тело письма
$message = "Поступила новая смета с сайта HeatCalc.ru\n";
$message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$message .= "🏠 Проект: " . $projectName . "\n";
$message .= "👤 Отправитель: " . ($data['userName'] ?? 'Не указано') . "\n";
$message .= "📞 Телефон: " . ($data['userPhone'] ?? 'Не указано') . "\n";
$message .= "📧 Email: " . ($data['userEmail'] ?? 'Не указано') . "\n";
$message .= "📐 Площадь объекта: " . ($data['area'] ?? 0) . " м2\n";
$message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$message .= "💰 ИТОГО ОБОРУДОВАНИЕ: " . number_format($data['eqSum'] ?? 0, 0, '.', ' ') . " руб.\n";
$message .= "💰 ИТОГО МОНТАЖ: " . number_format($data['worksSum'] ?? 0, 0, '.', ' ') . " руб.\n";
$message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
$message .= "📋 СПЕЦИФИКАЦИЯ ОБОРУДОВАНИЯ:\n";

if (isset($data['equipment']) && is_array($data['equipment'])) {
    foreach ($data['equipment'] as $idx => $item) {
        $num = $idx + 1;
        $name = $item['name'] ?? 'Неизвестный товар';
        $qty = $item['qty'] ?? 0;
        $unit = $item['unit'] ?? 'шт';
        $sum = number_format($item['sum'] ?? 0, 0, '.', ' ');
        $message .= "$num. $name — $qty $unit (на сумму $sum руб.)\n";
    }
}

$message .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$message .= "Письмо сгенерировано автоматически.";

// Заголовки
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type: text/plain; charset=UTF-8" . "\r\n";
$headers .= "From: HeatCalc Robot <noreply@heatcalc.ru>" . "\r\n";
if (!empty($data['userEmail'])) {
    $headers .= "Reply-To: " . $data['userEmail'] . "\r\n";
}

// Отправка
if (mail($to, $subject, $message, $headers)) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => "Функция mail() на сервере вернула ошибку. Обратитесь к хостеру."]);
}
?>
