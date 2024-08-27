<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$usersCollection = getCollection('users');
$username = $_SESSION['username'];
$action = $_GET['action'] ?? '';
$targetUsername = $_GET['username'] ?? '';

if ($action && $targetUsername) {
    if ($action === 'follow') {
        $usersCollection->updateOne(
            ['username' => $username],
            ['$addToSet' => ['following' => $targetUsername]]
        );
        $usersCollection->updateOne(
            ['username' => $targetUsername],
            ['$addToSet' => ['followers' => $username]]
        );
    } elseif ($action === 'unfollow') {
        $usersCollection->updateOne(
            ['username' => $username],
            ['$pull' => ['following' => $targetUsername]]
        );
        $usersCollection->updateOne(
            ['username' => $targetUsername],
            ['$pull' => ['followers' => $username]]
        );
    }
}

header('Location: profile.php');
exit();
?>
