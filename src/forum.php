<?php
require_once __DIR__ . '/db.php';

function createPost($title, $content) {
    $collection = getCollection();
    $result = $collection->insertOne([
        'title' => $title,
        'content' => $content,
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ]);
    return $result->getInsertedId();
}

function getPosts() {
    $collection = getCollection();
    return $collection->find()->toArray();
}

function getPostById($id) {
    $collection = getCollection();
    return $collection->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
}
