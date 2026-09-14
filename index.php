<?php
// Telegram bot for Railway
// The token is read from the TELEGRAM_BOT_TOKEN environment variable.
// IMPORTANT: do not put your real token directly into GitHub.

$token = getenv('TELEGRAM_BOT_TOKEN');

if (!$token) {
    http_response_code(500);
    exit("TELEGRAM_BOT_TOKEN is not set");
}

function tg($method, $data = []) {
    global $token;

    $url = "https://api.telegram.org/bot" . $token . "/" . $method;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_TIMEOUT => 20
    ]);

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

$update = json_decode(file_get_contents("php://input"), true);

if (!$update) {
    echo "Bot is running";
    exit;
}

$message = $update["message"] ?? null;

if (!$message) {
    echo "OK";
    exit;
}

$chat_id = $message["chat"]["id"];
$text = trim($message["text"] ?? "");

if ($text === "/start") {
    tg("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "👋 Привет!\n\nБот работает. Добро пожаловать!"
    ]);
} elseif ($text !== "") {
    tg("sendMessage", [
        "chat_id" => $chat_id,
        "text" => "Ты написал: " . $text
    ]);
}

echo "OK";
?>
