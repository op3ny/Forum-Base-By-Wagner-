<?php
session_start();
require_once __DIR__ . '/../src/db.php';

$searchResults = [];
$searchTerm = $_GET['q'] ?? '';

if ($searchTerm) {
    $usersCollection = getCollection('users');
    $searchResults = $usersCollection->find([
        'username' => new MongoDB\BSON\Regex($searchTerm, 'i')
    ])->toArray();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Usuários</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .profile-picture {
            border-radius: 50%;
            width: 40px;
            height: 40px;
            object-fit: cover;
        }
        .search-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        header {
            background: #343a40;
            color: #fff;
            padding: 15px 0;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
        }
        header .navbar-brand {
            color: #fff;
            font-size: 1.5rem;
        }
        .navbar-nav {
            display: flex;
            align-items: center;
        }
        .navbar-nav .nav-item {
            margin-right: 20px;
        }
        .profile-image {
            border-radius: 50%;
            width: 40px;
            height: 40px;
            object-fit: cover;
            margin-left: 15px;
        }
        footer {
            background: #343a40;
            color: #fff;
            padding: 15px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
        }
        footer a {
            color: #fff;
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
                <!-- Imagem do Perfil -->
                <?php if (isset($_SESSION['username'])): ?>
                <li class="nav-item">
                    <?php
                    $usersCollection = getCollection('users');
                    $username = $_SESSION['username'];
                    $user = $usersCollection->findOne(['username' => $username]);
                    ?>
                    <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? '/path/to/default-profile-picture.jpg'); ?>" alt="Imagem do Perfil" class="profile-image">
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
</header>

<div class="container mt-4">
    <h1>Buscar Usuários</h1>
    <div class="search-container">
        <form method="get" class="form-inline">
            <input type="text" name="q" class="form-control mr-2" placeholder="Pesquisar por username" value="<?php echo htmlspecialchars($searchTerm); ?>">
            <button type="submit" class="btn btn-primary">Buscar</button>
        </form>
    </div>

    <?php if ($searchTerm): ?>
        <?php if (count($searchResults) > 0): ?>
            <ul class="list-group">
                <?php foreach ($searchResults as $user): ?>
                    <li class="list-group-item">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? '/path/to/default-profile-picture.jpg'); ?>" alt="Foto de Perfil" class="profile-picture mr-3">
                            <div>
                                <h5 class="mb-1">
                                    <a href="user_profile.php?user=<?php echo urlencode($user['username']); ?>">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </a>
                                </h5>
                                <p class="mb-1">Nome completo: <?php echo htmlspecialchars($user['full_name'] ?? 'Desconhecido'); ?></p>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Nenhum usuário encontrado.</p>
        <?php endif; ?>
    <?php endif; ?>
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
