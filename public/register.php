<?php
require_once __DIR__ . '/../src/db.php';

$usersCollection = getCollection('users');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Hash da senha
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Verifica se o usuário já existe
    $existingUser = $usersCollection->findOne(['username' => $username]);

    if ($existingUser) {
        echo 'Usuário já existe!';
    } else {
        $usersCollection->insertOne([
            'username' => $username,
            'password' => $hashedPassword
        ]);
        echo 'Registro bem-sucedido!';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Registrar</h2>
        <form method="post">
            <div class="form-group">
                <label for="username">Nome de Usuário:</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Registrar</button>
        </form>
        <a href="login.php" class="btn btn-secondary mt-2">Já tem uma conta? Faça login</a>
    </div>
</body>
</html>
