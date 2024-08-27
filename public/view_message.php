<?php
session_start();
require_once __DIR__ . '/../src/db.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$messageId = $_GET['id'] ?? '';
if (!$messageId) {
    header('Location: profile.php');
    exit();
}

$messagesCollection = getCollection('messages');
$message = $messagesCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($messageId)]);

if (!$message) {
    echo 'Mensagem não encontrada!';
    exit();
}

// Verificar se a mensagem é para o usuário logado
if ($message['recipient'] !== $_SESSION['username']) {
    echo 'Você não tem permissão para visualizar esta mensagem!';
    exit();
}

// Atualizar a mensagem como lida
$messagesCollection->updateOne(
    ['_id' => new MongoDB\BSON\ObjectId($messageId)],
    ['$set' => ['read' => true]]
);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Mensagem</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
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
        <h1>Visualizar Mensagem</h1>
        <div class="card">
            <div class="card-header">
                <strong>De:</strong> <?php echo htmlspecialchars($message['from']); ?>
            </div>
            <div class="card-body">
                <p><strong>Mensagem:</strong></p>
                <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                <p><small>Recebida em: <?php echo htmlspecialchars($message['date'] ?? 'Desconhecido'); ?></small></p>
            </div>
        </div>
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
