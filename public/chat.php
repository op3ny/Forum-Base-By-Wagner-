<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$usersCollection = getCollection('users');
$messagesCollection = getCollection('messages');
$username = $_SESSION['username'];

if (!isset($_GET['user'])) {
    header('Location: profile.php');
    exit();
}

$chatUser = trim($_GET['user']);
if (empty($chatUser) || $chatUser === $username) {
    header('Location: profile.php');
    exit();
}

$chatUserData = $usersCollection->findOne(['username' => $chatUser]);
if (!$chatUserData) {
    header('Location: profile.php');
    exit();
}

$messages = $messagesCollection->find([
    '$or' => [
        ['from' => $username, 'to' => $chatUser],
        ['from' => $chatUser, 'to' => $username]
    ]
], [
    'sort' => ['timestamp' => 1]
])->toArray();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chatMessage = trim($_POST['chat_message'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');
    if ($chatMessage || $imageUrl) {
        $messagesCollection->insertOne([
            'from' => $username,
            'to' => $chatUser,
            'message' => $chatMessage,
            'image' => $imageUrl,
            'timestamp' => new MongoDB\BSON\UTCDateTime()
        ]);
        header('Location: chat.php?user=' . urlencode($chatUser));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat com <?php echo htmlspecialchars($chatUser, ENT_QUOTES, 'UTF-8'); ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: "Ubuntu", sans-serif;
        }
        .chat-box {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 20px;
            background-color: #f8f9fa;
        }
        .message-item {
            width: 50%;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 10px;
            display: flex;
            align-items: flex-start;
        }
        .message-item.me {
            background-color: #d1e7dd;
            text-align: right;
            margin-left: auto;
        }
        .message-item.other {
            background-color: #f1f1f1;
            text-align: left;
        }
        .message-item img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 5px;
            margin: 5px 0;
        }
        .message-item .profile-image {
            border-radius: 50%;
            width: 50px;
            height: 50px;
            margin-right: 15px;
        }
        .message-item .timestamp {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .chat-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .chat-header img {
            border-radius: 50%;
            width: 50px;
            height: 50px;
            margin-right: 15px;
            object-fit: cover;
        }
        .chat-header h1 {
            font-size: 1.5rem;
            margin: 0;
        }
        .form-group img {
            max-width: 100%;
            height: auto;
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
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="create_post.php">Criar Novo Post</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Perfil</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Sair</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <div class="container mt-4">
        <div class="chat-header">
            <?php $chatUserData = $usersCollection->findOne(['username' => $chatUser]); ?>
            <img src="<?php echo htmlspecialchars($chatUserData['profile_picture'] ?? '/path/to/default-profile-picture.jpg'); ?>" alt="Imagem do Perfil">
            <h1>Chat com <?php echo htmlspecialchars($chatUser, ENT_QUOTES, 'UTF-8'); ?></h1>
        </div>

        <div class="chat-box">
            <?php foreach ($messages as $message): ?>
                <?php
                $messageUserData = $usersCollection->findOne(['username' => $message['from']]);
                $profilePicture = $messageUserData['profile_picture'] ?? '/path/to/default-profile-picture.jpg';
                ?>
                <div class="message-item <?php echo $message['from'] === $username ? 'me' : 'other'; ?>">
                    <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Imagem do Perfil" class="profile-image">
                    <div>
                        <strong><?php echo htmlspecialchars($message['from'], ENT_QUOTES, 'UTF-8'); ?>:</strong>
                        <?php echo nl2br(htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8')); ?><br>
                        <?php if (isset($message['image']) && !empty($message['image'])): ?>
                            <img src="<?php echo htmlspecialchars($message['image']); ?>" alt="Imagem do chat">
                        <?php endif; ?>
                        <small class="timestamp"><?php echo $message['timestamp']->toDateTime()->format('d/m/Y H:i'); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="post">
            <div class="form-group">
                <label for="chat_message">Sua Mensagem:</label>
                <textarea id="chat_message" name="chat_message" class="form-control" rows="4" placeholder="Digite sua mensagem..." required></textarea>
            </div>
            <div class="form-group">
                <label for="image_url">URL da Imagem (Opcional):</label>
                <input type="text" id="image_url" name="image_url" class="form-control" placeholder="Cole o URL da imagem aqui">
            </div>
            <button type="submit" class="btn btn-primary">Enviar Mensagem</button>
        </form>

        <a href="profile.php" class="btn btn-secondary mt-4">Voltar</a>
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
