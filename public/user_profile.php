<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$usersCollection = getCollection('users');
$username = $_SESSION['username'];
$viewedUsername = $_GET['user'] ?? '';

if (!$viewedUsername) {
    header('Location: search.php');
    exit();
}

// Busca o usuário logado e o usuário visualizado
$user = $usersCollection->findOne(['username' => $username]);
$viewedUser = $usersCollection->findOne(['username' => $viewedUsername]);

if (!$viewedUser) {
    echo 'Usuário não encontrado.';
    exit();
}

// Converte BSONArray para array PHP
$following = $user['following'] ?? [];
if ($following instanceof MongoDB\Model\BSONArray) {
    $following = $following->getArrayCopy();
}

$isFollowing = in_array($viewedUsername, $following);

// Contagem de seguidores e seguindo
$followers = $viewedUser['followers'] ?? [];
if ($followers instanceof MongoDB\Model\BSONArray) {
    $followers = $followers->getArrayCopy();
}
$followersCount = count($followers);

$followingUsers = $viewedUser['following'] ?? [];
if ($followingUsers instanceof MongoDB\Model\BSONArray) {
    $followingUsers = $followingUsers->getArrayCopy();
}
$followingCount = count($followingUsers);

// Handle follow/unfollow action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['follow'])) {
        if (!$isFollowing) {
            $usersCollection->updateOne(
                ['username' => $username],
                ['$addToSet' => ['following' => $viewedUsername]]
            );
            $usersCollection->updateOne(
                ['username' => $viewedUsername],
                ['$addToSet' => ['followers' => $username]]
            );
            $isFollowing = true;
        }
    } elseif (isset($_POST['unfollow'])) {
        if ($isFollowing) {
            $usersCollection->updateOne(
                ['username' => $username],
                ['$pull' => ['following' => $viewedUsername]]
            );
            $usersCollection->updateOne(
                ['username' => $viewedUsername],
                ['$pull' => ['followers' => $username]]
            );
            $isFollowing = false;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Usuário</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .profile-picture {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <a class="navbar-brand" href="index.php">Fórum</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create_post.php">Criar Novo Post</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Perfil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Sair</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <div class="container mt-4">
        <h1>Perfil do Usuário</h1>
        <div class="text-center">
            <img src="<?php echo htmlspecialchars($viewedUser['profile_picture'] ?? '/path/to/default-profile-picture.jpg'); ?>" alt="Foto de Perfil" class="profile-picture">
        </div>
        <p><strong>Nome de usuário:</strong> <?php echo htmlspecialchars($viewedUser['username']); ?></p>
        <p><strong>Nome completo:</strong> <?php echo htmlspecialchars($viewedUser['full_name'] ?? 'Desconhecido'); ?></p>
        <p><strong>Seguidores:</strong> <?php echo htmlspecialchars($followersCount); ?></p>
        <p><strong>Seguindo:</strong> <?php echo htmlspecialchars($followingCount); ?></p>

        <form method="post" class="mt-4">
            <?php if ($isFollowing): ?>
                <button type="submit" name="unfollow" class="btn btn-secondary">Deixar de Seguir</button>
            <?php else: ?>
                <button type="submit" name="follow" class="btn btn-primary">Seguir</button>
            <?php endif; ?>
        </form>

        <a href="chat.php?user=<?php echo urlencode($viewedUsername); ?>" class="btn btn-primary mt-3">Chat</a>

        <a href="search.php" class="btn btn-secondary mt-4">Voltar</a>
    </div>

    <footer>
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date("Y"); ?> Fórum. Todos os direitos reservados.</p>
            <p><a href="privacy.php">Política de Privacidade</a> | <a href="terms.php">Termos de Serviço</a></p>
        </div>
    </footer>

    <!-- Incluindo Bootstrap JS e dependências -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
