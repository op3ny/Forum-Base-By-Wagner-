<?php
require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;

// Função para obter a coleção do MongoDB
function getCollection() {
    $client = new Client('mongodb://localhost:27017'); // Ajuste o URI conforme necessário
    $collection = $client->selectCollection('mydatabase', 'posts'); // Substitua 'mydatabase' e 'posts' pelos nomes reais
    return $collection;
}
// Criar coleção de seguidores
function getFollowersCollection() {
    return getCollection('followers');
}

