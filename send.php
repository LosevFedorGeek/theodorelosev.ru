<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$botcheck = filter_input(INPUT_POST, 'botcheck', FILTER_DEFAULT);
if (!empty($botcheck)) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'OK']);
    exit;
}

$name = trim((string)filter_input(INPUT_POST, 'name', FILTER_DEFAULT));
$contact = trim((string)filter_input(INPUT_POST, 'contact', FILTER_DEFAULT));
$message = trim((string)filter_input(INPUT_POST, 'message', FILTER_DEFAULT));

if (empty($name) || empty($contact)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Заполните обязательные поля']);
    exit;
}

$cleanName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$cleanContact = htmlspecialchars($contact, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$cleanMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$telegramBotToken = '489127260';
$telegramChatId = '1310555894';

$text = "⚡️ <b>Новая заявка с сайта Theodore Losev</b>\n\n";
$text .= "👤 <b>Клиент:</b> {$cleanName}\n";
$text .= "📞 <b>Контакт:</b> {$cleanContact}\n";
if (!empty($cleanMessage)) {
    $text .= "📝 <b>Детали:</b>\n{$cleanMessage}\n";
}
$text .= "⏱ <b>Время:</b> " . date('d.m.Y H:i:s') . "\n";
$text .= "🌐 <b>IP:</b> " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown');

$tgSuccess = false;
if ($telegramBotToken !== '489127260' && !empty($telegramChatId)) {
    $tgUrl = "https://api.telegram.org/bot{$telegramBotToken}/sendMessage";
    $payload = json_encode([
        'chat_id' => $telegramChatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true,
    ]);

    $ch = curl_init($tgUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $tgSuccess = true;
    }
}

$toEmail = 'losevfedor287@gmail.com';
$emailSubject = '=?UTF-8?B?' . base64_encode('Новая заявка: ' . $cleanName) . '?=';
$emailBody = "Имя: {$name}\nКонтакт: {$contact}\n\nСообщение:\n{$message}\n\nДата: " . date('Y-m-d H:i:s');
$emailHeaders = [
    'From' => 'no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost'),
    'Reply-To' => $cleanContact,
    'X-Mailer' => 'PHP/' . phpversion(),
    'Content-Type' => 'text/plain; charset=UTF-8',
];

@mail($toEmail, $emailSubject, $emailBody, $emailHeaders);

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Заявка успешно принята',
    'tg_delivered' => $tgSuccess,
]);
