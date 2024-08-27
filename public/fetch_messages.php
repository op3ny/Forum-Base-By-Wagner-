<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if (!isset($_SESSION['username'])) {
    http_response_code(403);
    exit();
}

$messagesCollection = getCollection('messages');
$username = $_SESSION['username'];
$withUser = $_GET['with'] ?? null;

if (!$withUser) {
    http_response_code(400);
    exit();
}

// Obtém mensagens trocadas
$messages = $messagesCollection->find([
    '$or' => [
        ['from' => $username, 'to' => $withUser],
        ['from' => $withUser, 'to' => $username]
    ]
], ['sort' => ['timestamp' => 1]])->toArray();

foreach ($messages as $message) {
    $class = $message['from'] === $username ? 'current-user' : 'other-user';
    echo '<div class="message ' . $class . '">';
    echo '<p>' . htmlspecialchars($message['text']) . '</p>';
    echo '<div class="message-time">' . date('H:i', $message['timestamp']->toDateTime()->getTimestamp()) . '</div>';
    echo '</div>';
}
?>
