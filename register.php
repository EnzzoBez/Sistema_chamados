<?php
session_start();
require 'conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $setor_id = $_POST['setor'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $senha_confirm = $_POST['senha_confirm'] ?? '';

    if (!$nome || !$setor_id || !$senha || !$senha_confirm) {
        $erro = 'Preencha todos os campos.';
    } elseif ($senha !== $senha_confirm) {
        $erro = 'As senhas não coincidem.';
    } else {
        // Verifica se já existe um usuário com mesmo nome no setor
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE nome = ? AND setor_id = ?");
        $stmt->execute([$nome, $setor_id]);
        if ($stmt->fetch()) {
            $erro = 'Já existe um usuário com esse nome nesse setor.';
        } else {
            // Cria o hash da senha
            $hashSenha = password_hash($senha, PASSWORD_DEFAULT);
            
            // Insere no banco
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, setor_id, senha) VALUES (?, ?, ?)");
            $stmt->execute([$nome, $setor_id, $hashSenha]);

            // Redireciona para login após cadastro
            header('Location: index.php');
            exit;
        }
    }
}

// Busca setores para popular o select
$stmt = $pdo->query("SELECT id, nome FROM setores ORDER BY nome");
$setores = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Cadastro - Bela Pedra</title>
<style>
  /* Fonte e reset */
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');

  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: 'Inter', Arial, sans-serif;
    background: #121212;
    color: #eee;
    padding: 2rem;
    min-height: 100vh;
  }

  .container {
    max-width: 400px;
    margin: auto;
    background: #1e1e1e;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.7);
  }

  h1 {
    text-align: center;
    margin-bottom: 1.5rem;
    color: #4a90e2;
    font-weight: 600;
  }

  label {
    display: block;
    margin-top: 1rem;
    font-weight: 600;
    color: #bbb;
  }

  input, select {
    width: 100%;
    padding: 0.7rem;
    margin-top: 0.3rem;
    border-radius: 6px;
    border: 1.5px solid #333;
    background: #2c2c2c;
    color: #eee;
    font-size: 1rem;
    transition: border-color 0.3s ease;
  }

  input::placeholder {
    color: #777;
  }

  input:focus, select:focus {
    border-color: #4a90e2;
    outline: none;
    background: #3a3a3a;
  }

  button {
    margin-top: 1.5rem;
    width: 100%;
    padding: 0.75rem;
    background: #4a90e2;
    border: none;
    border-radius: 8px;
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  button:hover {
    background: #357abd;
  }

  .error {
    background: #b3383a;
    color: #f8d7da;
    padding: 0.8rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    font-weight: 600;
    text-align: center;
  }
</style>
</head>
<body>

<div class="container">
  <h1>Cadastro de Usuário</h1>

  <?php if ($erro): ?>
    <div class="error"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <form method="POST" action="" autocomplete="off" novalidate>
    <label for="nome">Nome do Usuário</label>
    <input type="text" name="nome" id="nome" required placeholder="Digite seu nome" />

    <label for="setor">Setor</label>
    <select name="setor" id="setor" required>
      <option value="">Selecione um setor</option>
      <?php foreach ($setores as $setor): ?>
        <option value="<?= $setor['id'] ?>"><?= htmlspecialchars($setor['nome']) ?></option>
      <?php endforeach; ?>
    </select>

    <label for="senha">Senha</label>
    <input type="password" name="senha" id="senha" required placeholder="Digite sua senha" />

    <label for="senha_confirm">Confirmar Senha</label>
    <input type="password" name="senha_confirm" id="senha_confirm" required placeholder="Confirme sua senha" />

    <button type="submit">Cadastrar</button>
  </form>
</div>

</body>
</html>
