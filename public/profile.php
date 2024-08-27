<?php
session_start();
require_once __DIR__ . '/../src/db.php';

// Redireciona se o usuário não estiver autenticado
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$usersCollection = getCollection('users');
$messagesCollection = getCollection('messages');
$username = $_SESSION['username'];

// Busca o usuário logado
$user = $usersCollection->findOne(['username' => $username]);

// Contar mensagens enviadas
function countMessagesSent($username) {
    global $messagesCollection;
    return $messagesCollection->countDocuments(['sender' => $username]);
}

// Atualizar informações do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim($_POST['new_username'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $profilePictureUrl = trim($_POST['profile_picture_url'] ?? '');

    if ($newUsername && $newUsername !== $username) {
        $existingUser = $usersCollection->findOne(['username' => $newUsername]);
        if (!$existingUser) {
            $usersCollection->updateOne(
                ['username' => $username],
                ['$set' => ['username' => $newUsername]]
            );
            $_SESSION['username'] = $newUsername;
            $username = $newUsername;
            echo '<div class="alert alert-success">Nome de usuário atualizado com sucesso!</div>';
        } else {
            echo '<div class="alert alert-danger">Nome de usuário já está em uso!</div>';
        }
    }

    if ($newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $usersCollection->updateOne(
            ['username' => $username],
            ['$set' => ['password' => $hashedPassword]]
        );
        echo '<div class="alert alert-success">Senha atualizada com sucesso!</div>';
    }

    if ($profilePictureUrl) {
        $usersCollection->updateOne(
            ['username' => $username],
            ['$set' => ['profile_picture' => $profilePictureUrl]]
        );
        echo '<div class="alert alert-success">Foto de perfil atualizada com sucesso!</div>';
    }
}

// Funções para seguir e deixar de seguir
function handleFollow($action, $targetUsername) {
    global $usersCollection, $username;

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

// Lidar com ações de seguir e deixar de seguir
if (isset($_GET['action'], $_GET['username'])) {
    $action = $_GET['action'];
    $targetUsername = $_GET['username'];
    if (in_array($action, ['follow', 'unfollow']) && !empty($targetUsername)) {
        handleFollow($action, $targetUsername);
        header('Location: profile.php');
        exit();
    }
}

// Contar seguidores e seguindo
$followersCount = count($user['followers'] ?? []);
$followingCount = count($user['following'] ?? []);

// Obter todos os usuários exceto o atual
$allUsers = $usersCollection->find(['username' => ['$ne' => $username]]);

$defaultProfilePictureUrl = 'https://img.freepik.com/vetores-premium/imagem-de-perfil-de-avatar-de-homem-ilustracao-vetorial-eps10_268834-1920.jpg';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
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

.notification-bell {
    position: relative;
    cursor: pointer;
}

.notification-bell .badge {
    position: absolute;
    top: -10px;
    right: -10px;
    background: red;
    color: white;
    font-size: 12px;
    border-radius: 50%;
    padding: 3px 6px;
}

.notification-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    right: 0;
    width: 300px;
    background: white;
    border: 1px solid #ddd;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    z-index: 1000;
}

.notification-dropdown.show {
    display: block;
}

.notification-item {
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

.notification-item:last-child {
    border-bottom: none;
}

        .profile-picture {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
        }
        .message-box {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 20px;
        }
        .message-item {
            margin-bottom: 10px;
        }
        .response-form {
            margin-top: 20px;
        }
        .sender-header {
            font-weight: bold;
            margin-top: 20px;
        }
        .badge-danger {
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 0.5em;
            font-size: 0.8em;
            vertical-align: middle;
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
                    <a class="nav-link" href="search.php">Buscar Usuários</a>
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
                <!-- Notificações -->
                <!-- Imagem do Perfil -->
                <li class="nav-item">
                    <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? '/path/to/default-profile-picture.jpg'); ?>" alt="Imagem do Perfil" class="profile-image">
                </li>
            </ul>
        </div>
    </nav>
</header>

    <div class="container mt-4">
        <h1>Perfil</h1>
        <div class="text-center">
            <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? $defaultProfilePictureUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto de Perfil" class="profile-picture">
        </div>

        <p><strong>Nome de usuário:</strong> <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p><strong>Seguidores:</strong> <?php echo $followersCount; ?></p>
        <p><strong>Seguindo:</strong> <?php echo $followingCount; ?></p>

        <h2>Atualizar Perfil</h2>
        <form method="post">
            <div class="form-group">
                <label for="new_username">Novo Nome de Usuário:</label>
                <input type="text" id="new_username" name="new_username" class="form-control">
            </div>
            <div class="form-group">
                <label for="new_password">Nova Senha:</label>
                <input type="password" id="new_password" name="new_password" class="form-control">
            </div>
            <div class="form-group">
                <label for="profile_picture_url">URL da Foto de Perfil:</label>
                <input type="text" id="profile_picture_url" name="profile_picture_url" class="form-control" value="<?php echo htmlspecialchars($user['profile_picture'] ?? $defaultProfilePictureUrl, ENT_QUOTES, 'UTF-8'); ?>">
                <small class="form-text text-muted">Insira a URL completa da imagem.</small>
            </div>
            <button type="submit" class="btn btn-primary">Atualizar</button>
        </form>

        <h2 class="mt-4">Conversas</h2>
        <div class="list-group">
            <?php foreach ($allUsers as $profileUser): ?>
                <a href="chat.php?user=<?php echo urlencode($profileUser['username']); ?>" class="list-group-item list-group-item-action">
                    <?php echo htmlspecialchars($profileUser['username'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <h2 class="mt-4">Seguir/Deixar de Seguir</h2>
        <?php
        $allUsers = $usersCollection->find(['username' => ['$ne' => $username]]);
        foreach ($allUsers as $profileUser):
            $isFollowing = in_array($profileUser['username'], $user['following']);
            $messagesCount = countMessagesSent($profileUser['username']);
        ?>
            <div class="mb-2">
                <p>
                    <strong><?php echo htmlspecialchars($profileUser['username'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php if ($messagesCount > 0): ?>
                        <span class="badge badge-danger"><?php echo $messagesCount; ?></span>
                    <?php endif; ?>
                </p>
                <?php if ($profileUser['username'] !== $username): ?>
                    <form action="profile.php" method="get">
                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($profileUser['username'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if ($isFollowing): ?>
                            <button type="submit" name="action" value="unfollow" class="btn btn-danger">Deixar de Seguir</button>
                        <?php else: ?>
                            <button type="submit" name="action" value="follow" class="btn btn-primary">Seguir</button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <a href="index.php" class="btn btn-secondary mt-4">Voltar</a>
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
    <script>
        document.querySelector('.notification-bell').addEventListener('click', function() {
    var dropdown = document.querySelector('.notification-dropdown');
    dropdown.classList.toggle('show');
});

document.addEventListener('click', function(event) {
    var bell = document.querySelector('.notification-bell');
    var dropdown = document.querySelector('.notification-dropdown');
    if (!bell.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

    </script>
</body>
</html>
