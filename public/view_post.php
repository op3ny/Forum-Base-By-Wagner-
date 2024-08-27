<?php
session_start();
require_once __DIR__ . '/../src/post.php';
require_once __DIR__ . '/../src/db.php';

$id = $_GET['id'] ?? null;
$post = $id ? getPostById($id) : null;
$usersCollection = getCollection('users');
$username = $_SESSION['username'] ?? null;
$user = $username ? $usersCollection->findOne(['username' => $username]) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['comment'])) {
        // Adicionar comentário
        $user = $_SESSION['username'] ?? 'anônimo';
        $comment = $_POST['comment'];
        $imageUrl = $_POST['image_url'] ?? null;
        addComment($id, $user, $comment, $imageUrl);
        header('Location: view_post.php?id=' . $id);
        exit();
    } elseif (isset($_POST['delete_comment'])) {
        // Excluir comentário
        $commentId = $_POST['comment_id'] ?? null;
        $commentUser = $_POST['comment_user'] ?? null;
        
        if ($commentId && $commentUser === $username && $post) {
            deleteComment($id, $commentId);
        }
        header('Location: view_post.php?id=' . $id);
        exit();
    }
}

if (!$post) {
    echo "Post não encontrado!";
    exit();
}

function deleteComment($postId, $commentId) {
    $postsCollection = getCollection('posts');
    $postsCollection->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($postId)],
        ['$pull' => ['comments' => ['_id' => new MongoDB\BSON\ObjectId($commentId)]]]
    );
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?></title>
    <!-- Incluindo Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=New+Amsterdam&family=Rubik+Mono+One&family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Ubuntu", sans-serif; /* Espaço para o cabeçalho fixo */
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
            background: #f8f9fa;
            padding: 20px 0;
            margin-top: 20px;
        }
        
        .card {
            margin-bottom: 20px;
        }

        .user-name {
            font-weight: bold;
            font-size: 1.1rem;
        }

        .comment-textarea {
            height: 100px;
        }

        .img-comment {
            max-width: 100%;
            height: auto;
        }

        .comments-container {
            display: flex;
            flex-direction: column;
        }

        .comment-wrapper {
            display: flex;
            margin-bottom: 10px;
        }

        .comment-wrapper.current-user {
            justify-content: flex-end;
        }

        .comment-body {
            max-width: 60%;
            padding: 10px;
            border-radius: 10px;
            background-color: #f1f1f1;
        }

        .comment-wrapper.current-user .comment-body {
            background-color: #d1e7dd;
        }

        .comment-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .comment-header img {
            border-radius: 50%;
            margin-right: 15px;
            width: 50px;
            height: 50px;
            object-fit: cover;
        }

        .comment-header a {
            text-decoration: none;
            color: #000;
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
                <li class="nav-item">
                    <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? '/path/to/default-profile-picture.jpg'); ?>" alt="Imagem do Perfil" class="profile-image">
                </li>
            </ul>
        </div>
    </nav>
</header>

<div class="container mt-4">
    <!-- Post Card -->
    <div class="card">
        <div class="card-body">
            <h2 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h2>
            <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
        </div>
    </div>

    <!-- Comments Section -->
    <h2 class="mt-4">Comentários</h2>
    <div class="comments-container">
        <?php if (!empty($post['comments'])): ?>
            <?php foreach ($post['comments'] as $comment): ?>
                <?php
                $commentUser = $usersCollection->findOne(['username' => $comment['user']]);
                $profilePicture = $commentUser['profile_picture'] ?? '/path/to/default-profile-picture.jpg';
                $isCurrentUser = $comment['user'] === $username;
                ?>
                <div class="comment-wrapper <?php echo $isCurrentUser ? 'current-user' : ''; ?>">
                    <div class="comment-body">
                        <div class="comment-header">
                            <!-- Imagem do perfil do usuário que fez o comentário -->
                            <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Imagem do Perfil">
                            <a href="profile.php?username=<?php echo urlencode($comment['user']); ?>" class="user-name"><?php echo htmlspecialchars($comment['user']); ?></a>
                        </div>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                        <?php if (isset($comment['image'])): ?>
                            <img src="<?php echo htmlspecialchars($comment['image']); ?>" alt="Imagem do comentário" class="img-comment">
                        <?php endif; ?>
                        <small class="text-muted">Em: <?php echo $comment['created_at']->toDateTime()->format('Y-m-d H:i:s'); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Este post ainda não tem comentários.</p>
        <?php endif; ?>
    </div>

    <!-- Add Comment Form -->
    <h2 class="mt-4">Adicionar Comentário</h2>
    <form method="post" class="comment-form">
        <div class="form-group">
            <label for="user">Nome:</label>
            <input type="text" id="user" name="user" class="form-control" value="<?php echo htmlspecialchars($_SESSION['username'] ?? 'anônimo'); ?>" readonly>
        </div>
        <div class="form-group">
            <label for="comment">Comentário:</label>
            <textarea id="comment" name="comment" class="form-control comment-textarea" required></textarea>
        </div>
        <div class="form-group">
            <label for="image_url">URL da Imagem (Opcional):</label>
            <input type="text" id="image_url" name="image_url" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Adicionar Comentário</button>
    </form>

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
    document.addEventListener('DOMContentLoaded', function() {
        var ws = new WebSocket('ws://localhost:8080');
        
        ws.onmessage = function(event) {
            console.log("Message received: ", event.data); // Log recebido
            var data = JSON.parse(event.data);
            if (data.action === 'new_comment') {
                // Atualize a lista de comentários com o novo comentário
                var commentContainer = document.querySelector('.comments-container');
                var commentHTML = '<div class="comment-wrapper">' +
                    '<div class="comment-body">' +
                    '<div class="comment-header">' +
                    '<img src="' + data.profile_picture + '" alt="Imagem do Perfil">' +
                    '<a href="profile.php?username=' + encodeURIComponent(data.user) + '" class="user-name">' + data.user + '</a>' +
                    '</div>' +
                    '<p class="card-text">' + data.comment + '</p>';
                
                if (data.image) {
                    commentHTML += '<img src="' + data.image + '" alt="Imagem do comentário" class="img-comment">';
                }
                
                commentHTML += '<small class="text-muted">Em: ' + data.created_at + '</small>' +
                    '</div>' +
                    '</div>';
                
                commentContainer.innerHTML += commentHTML;
            }
        };
        
        document.querySelector('form.comment-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            var comment = document.getElementById('comment').value;
            var imageUrl = document.getElementById('image_url').value;
            
            ws.send(JSON.stringify({
                action: 'new_comment',
                user: '<?php echo $_SESSION['username'] ?? 'anônimo'; ?>',
                comment: comment,
                image: imageUrl,
                created_at: new Date().toISOString(),
                profile_picture: '<?php echo htmlspecialchars($user['profile_picture'] ?? '/path/to/default-profile-picture.jpg'); ?>'
            }));
            
            document.getElementById('comment').value = '';
            document.getElementById('image_url').value = '';
        });
    });
</script>

</body>
</html>
