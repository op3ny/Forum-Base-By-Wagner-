<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = $_POST['post_id'] ?? null;
    $commentId = $_POST['comment_id'] ?? null;

    if ($postId && $commentId) {
        deleteComment($postId, $commentId);
    }

    header('Location: view_post.php?id=' . $postId);
    exit();
}

function deleteComment($postId, $commentId) {
    $postsCollection = getCollection('posts');
    $postsCollection->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($postId)],
        ['$pull' => ['comments' => ['_id' => new MongoDB\BSON\ObjectId($commentId)]]]
    );
}
?>
