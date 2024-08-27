<?php
require_once __DIR__ . '/../src/db.php'; // Ajuste o caminho conforme necessário

$data = json_decode(file_get_contents('php://input'), true);

if ($data['action'] === 'new_comment') {
    $postId = $data['post_id'];
    $user = $data['user'];
    $comment = $data['comment'];
    $image = $data['image'];
    $createdAt = new DateTime($data['created_at']);

    // Adiciona o comentário ao MongoDB
    addComment($postId, $user, $comment, $image, $createdAt->getTimestamp() * 1000); // Converta para timestamp em milissegundos
}
