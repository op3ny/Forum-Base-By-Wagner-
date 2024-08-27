<?php
require_once __DIR__ . '/../src/db.php';

header('Content-Type: application/json');

$collection = getCollection();
$messages = $collection->find([], ['sort' => ['created_at' => 1]]);

$response = [];
foreach ($messages as $message) {
    $response[] = [
        'username' => $message['username'],
        'message' => $message['message'],
        'created_at' => $message['created_at']->toDateTime()->format('c') // ISO 8601
    ];
}

echo json_encode($response);
?>
