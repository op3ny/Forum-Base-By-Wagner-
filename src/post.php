<?php

require_once __DIR__ . '/db.php';
use MongoDB\BSON\UTCDateTime;

// Função para criar um novo post
function createPost($title, $content) {
    $collection = getCollection();
    $result = $collection->insertOne([
        'title' => htmlspecialchars($title),
        'content' => htmlspecialchars($content),
        'created_at' => new UTCDateTime(),
        'comments' => [] // Inicialmente sem comentários
    ]);
    return $result->getInsertedId();
}

// Função para adicionar um comentário a um post
function addComment($postId, $user, $comment, $imagePath = null) {
    try {
        $collection = getCollection();
        $userCollection = getCollection('users');

        // Obter a foto de perfil do usuário
        $userDoc = $userCollection->findOne(['username' => $user]);
        $profilePictureUrl = $userDoc ? $userDoc['profile_picture'] : '/path/to/default-profile-picture.jpg';

        // Processar menções
        $comment = preg_replace_callback(
            '/@(\w+)/',
            function ($matches) {
                return '<a href="profile.php?user=' . htmlspecialchars($matches[1]) . '">@' . htmlspecialchars($matches[1]) . '</a>';
            },
            $comment
        );

        $result = $collection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($postId)],
            ['$push' => [
                'comments' => [
                    'user' => htmlspecialchars($user),
                    'comment' => htmlspecialchars($comment),
                    'created_at' => new UTCDateTime(),
                    'image' => htmlspecialchars($imagePath),
                    'profile_picture' => htmlspecialchars($profilePictureUrl)
                ]
            ]]
        );
        return $result->getModifiedCount();
    } catch (Exception $e) {
        // Lida com a exceção
        return false;
    }
}

// Função para obter todos os posts
function getPosts() {
    $collection = getCollection();
    return $collection->find([], ['sort' => ['created_at' => -1]])->toArray();
}

// Função para obter um post específico por ID
function getPostById($id) {
    $collection = getCollection();
    return $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
}

// Função para deletar um post por ID
function deletePost($id) {
    $collection = getCollection();
    $result = $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
    return $result->getDeletedCount();
}

// Função para atualizar um post
function updatePost($id, $title, $content) {
    $collection = getCollection();
    $result = $collection->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($id)],
        ['$set' => [
            'title' => htmlspecialchars($title),
            'content' => htmlspecialchars($content)
        ]]
    );
    return $result->getModifiedCount();
}
?>
