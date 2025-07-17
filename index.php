<?php
session_start();
require 'conexao.php';

if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
} else {
    // Usuário não está logado, trate o caso ou defina valor padrão
    $usuario_id = null;
}

// Agora pode usar $usuario_id sem warning

// Pega os setores para popular select
$stmt = $pdo->query("SELECT id, nome FROM setores ORDER BY nome");
$setores = $stmt->fetchAll();

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $setor_id = $_POST['setor'] ?? '';
    $usuario_id = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (!$setor_id || !$usuario_id || !$senha) {
        $erro = 'Preencha todos os campos.';
    } else {
        // Verifica se o usuário pertence ao setor selecionado e senha
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND setor_id = ?");
        $stmt->execute([$usuario_id, $setor_id]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Login válido
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_setor'] = $setor_id;

            // Atualiza logs_login para marcar online
            $stmt = $pdo->prepare("REPLACE INTO logs_login (usuario_id, last_active) VALUES (?, NOW())");
            $stmt->execute([$usuario['id']]);

            header('Location: painel_usuario.php');
            exit;
        } else {
            $erro = 'Usuário, setor ou senha inválidos.';
        }
    }
}
$stmt = $pdo->prepare("UPDATE usuarios SET status = 'online' WHERE id = ?");
$stmt->execute([$usuario_id]);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Bela Pedra - Suporte Técnico</title>
<link rel="stylesheet" href="style.css" />
<script src="script.js" defer></script>
</head>
<body>
<div class="login-container">
    <h1>Bela Pedra - Login</h1>

    <?php if ($erro): ?>
    <div class="error-msg"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="POST" id="loginForm" autocomplete="off">
        <label for="setor">Setor</label>
        <select id="setor" name="setor" required>
            <option value="">Selecione o setor</option>
            <?php foreach ($setores as $setor): ?>
                <option value="<?= $setor['id'] ?>"><?= htmlspecialchars($setor['nome']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="usuario">Nome do Usuário</label>
        <select id="usuario" name="usuario" required disabled>
            <option value="">Selecione o setor primeiro</option>
        </select>

        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" required />

        <button type="submit">Entrar</button>
    </form>
</div>
</body>
</html>
