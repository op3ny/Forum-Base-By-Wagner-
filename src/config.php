<?php
require_once __DIR__ . '/../vendor/autoload.php'; // Adjust the path as needed

$client = new MongoDB\Client("mongodb://localhost:27017");
$database = $client->selectDatabase('cadastros');
$collection = $database->selectCollection('registros');
