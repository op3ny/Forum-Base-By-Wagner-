<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$usersCollection = getCollection('users');
$messagesCollection = getCollection('messages');
$username = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chatUser = trim($_POST['chat_user'] ?? '');
    $chatMessage = trim($_POST['chat_message'] ?? '');

    if ($chatUser && $chatMessage) {
        $messagesCollection->insertOne([
            'from' => $username,
            'to' => $chatUser,
            'message' => $chatMessage,
            'timestamp' => new MongoDB\BSON\UTCDateTime()
        ]);
        header('Location: chat.php?user=' . urlencode($chatUser));
        exit();
    }
}
?>
