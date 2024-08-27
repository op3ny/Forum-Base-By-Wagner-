<?php
session_start();
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/forum.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Obter dados do usuário
$usersCollection = getCollection('users');
$messagesCollection = getCollection('messages');
$username = $_SESSION['username'];
$user = $usersCollection->findOne(['username' => $username]);

// Obtém os posts
try {
    $posts = getPosts();
} catch (Exception $e) {
    die('Erro ao obter posts: ' . $e->getMessage());
}

// Obtém mensagens não lidas
$unreadMessagesCursor = $messagesCollection->find([
    'to' => $username,
    'read' => false
]);
$unreadMessages = iterator_to_array($unreadMessagesCursor); // Converte o cursor para array
$unreadCount = count($unreadMessages); // Conta mensagens não lidas

// Obtém as conversas mais recentes
$recentConversationsCursor = $messagesCollection->aggregate([
    ['$match' => ['$or' => [['from' => $username], ['to' => $username]]]],
    ['$sort' => ['timestamp' => -1]],
    ['$group' => [
        '_id' => ['$cond' => [
            'if' => ['$eq' => ['$from', $username]],
            'then' => '$to',
            'else' => '$from'
        ]],
        'last_message' => ['$last' => '$message'],
        'last_timestamp' => ['$last' => '$timestamp']
    ]],
    ['$sort' => ['last_timestamp' => -1]]
]);
$recentConversations = iterator_to_array($recentConversationsCursor); // Converte o cursor para array
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fórum</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=New+Amsterdam&family=Rubik+Mono+One&family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Ubuntu", sans-serif;
            padding-top: 70px; /* Espaço para o cabeçalho fixo */
        }
        
        header {
            background: #343a40;
            color: #fff;
            padding: 15px 0;
            position: fixed;
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

.notification-bell img {
    width: 50px; /* Ajuste conforme necessário */
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
    min-width: 20px; /* Ajuste conforme necessário */
    text-align: center;
    line-height: 20px; /* Ajuste conforme necessário */
    display: flex;
    align-items: center;
    justify-content: center;
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

        footer {
            background: #f8f9fa;
            padding: 20px 0;
            margin-top: 20px;
        }

        .post-list-item {
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }

        .post-list-item a {
            text-decoration: none;
            color: #007bff;
        }

        .post-list-item a:hover {
            text-decoration: underline;
        }

        .conversations-list-item {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .conversations-list-item a {
            text-decoration: none;
            color: #007bff;
        }

        .conversations-list-item a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <a class="navbar-brand" href="#">Fórum</a>
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
                   <li class="nav-item notification-bell">
    <img src="https://img.icons8.com/ios-filled/50/ffffff/appointment-reminders.png" alt="Notificações" />
    <?php if ($unreadCount > 0): ?>
        <span class="badge"><?php echo $unreadCount; ?></span>
    <?php endif; ?>
    <div class="notification-dropdown">
        <?php if (!empty($recentConversations)): ?>
            <?php foreach ($recentConversations as $conversation): ?>
                <div class="notification-item">
                    <a href="chat.php?user=<?php echo htmlspecialchars($conversation['_id']); ?>">
                        Nova mensagem de <?php echo htmlspecialchars($conversation['_id']); ?>: <?php echo htmlspecialchars($conversation['last_message']); ?>
                    </a>
                    <small class="text-muted d-block"><?php echo $conversation['last_timestamp']->toDateTime()->format('d/m/Y H:i'); ?></small>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="notification-item">
                Sem novas mensagens.
            </div>
        <?php endif; ?>
    </div>
</li>
                    <!-- Imagem do Perfil -->
                    <li class="nav-item">
                        <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? '/path/to/default-profile-picture.jpg'); ?>" alt="Imagem do Perfil" class="profile-image">
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <div class="container mt-4">
        <h1 class="mb-4">Fórum</h1>
        <a href="create_post.php" class="btn btn-primary mb-4">Criar Novo Post</a>

        <!-- Seção de Conversas Recentes -->
        
        <!-- Posts -->
        <h2 class="mt-4">Posts Recentes</h2>
        <ul class="list-unstyled">
            <?php foreach ($posts as $post): ?>
                <li class="post-list-item">
                    <a href="view_post.php?id=<?php echo htmlspecialchars($post['_id']); ?>">
                        <?php echo htmlspecialchars($post['title']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
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
        // JavaScript para alternar a exibição das notificações
        document.querySelector('.notification-bell').addEventListener('click', function() {
            var dropdown = document.querySelector('.notification-dropdown');
            dropdown.classList.toggle('show');
        });

        // Fechar o dropdown se clicar fora
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
