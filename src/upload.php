<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $uploadDir = __DIR__ . '/../uploads/';
    $uploadFile = $uploadDir . basename($_FILES['image']['name']);
    
    // Mover o arquivo para o diretório de uploads
    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
        // O caminho do arquivo para armazenar no banco de dados
        $imagePath = 'uploads/' . basename($_FILES['image']['name']);
        
        // Adicionar o comentário e o caminho da imagem ao banco de dados
        $postId = $_POST['post_id'];
        $user = $_POST['user'];
        $comment = $_POST['comment'];
        
        require_once __DIR__ . '/../src/post.php';
        addComment($postId, $user, $comment, $imagePath);
        
        header('Location: view_post.php?id=' . $postId);
        exit();
    } else {
        echo "Falha ao fazer upload do arquivo.";
    }
}
?>
