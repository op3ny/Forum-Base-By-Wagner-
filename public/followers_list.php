<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$usersCollection = getCollection('users');
$username = $_SESSION['username'];
$user = $usersCollection->findOne(['username' => $username]);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguidores e Seguindo</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <header>
        <!-- Navbar... -->
    </header>

    <div class="container mt-4">
        <h1>Seguidores</h1>
        <ul class="list-group">
            <?php foreach ($user['followers'] as $follower): ?>
                <li class="list-group-item"><?php echo htmlspecialchars($follower); ?></li>
            <?php endforeach; ?>
        </ul>

        <h2 class="mt-4">Seguindo</h2>
        <ul class="list-group">
            <?php foreach ($user['following'] as $following): ?>
                <li class="list-group-item"><?php echo htmlspecialchars($following); ?></li>
            <?php endforeach; ?>
        </ul>

        <a href="index.php" class="btn btn-secondary mt-4">Voltar</a>
    </div>

    <footer>
        <!-- Footer... -->
    </footer>

    <!-- Incluindo Bootstrap JS e dependências -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
