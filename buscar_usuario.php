<?php
// buscar_usuario.php - Busca de Usuários (para Admin/TI)
session_start();
require 'conexao.php';

// Verifica se o usuário está logado e se é um técnico/admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] !== 'admin' && $_SESSION['tipo_usuario'] !== 'tecnico')) {
    header('Location: index.php');
    exit();
}

$termo_busca = trim($_GET['termo'] ?? '');
$usuarios = [];
$mensagem = '';

if (!empty($termo_busca)) {
    try {
        $stmt = $pdo->prepare("SELECT u.id, u.nome, u.email, u.tipo_usuario, s.nome AS setor_nome, u.data_cadastro
                               FROM usuarios u
                               JOIN setores s ON u.setor_id = s.id
                               WHERE u.nome LIKE ? OR u.email LIKE ?
                               ORDER BY u.nome ASC");
        $stmt->execute(["%$termo_busca%", "%$termo_busca%"]);
        $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($usuarios)) {
            $mensagem = 'Nenhum usuário encontrado para o termo "' . htmlspecialchars($termo_busca) . '".';
        }
    } catch (PDOException $e) {
        $mensagem = "Erro ao buscar usuários: " . $e->getMessage();
    }
} else {
    $mensagem = 'Digite um nome ou email para buscar usuários.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Belapedra - Buscar Usuário</title>
        <link rel="icon" href="imagens/Logo_belapedra.ico" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            padding: 2rem;
        }
        .search-container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(15, 15, 15, 0.95);
            padding: 3rem;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5), 0 0 100px rgba(0, 255, 255, 0.1);
            position: relative;
            z-index: 1;
        }
        .search-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .search-header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -1px;
            margin-bottom: 1rem;
        }
        .search-form {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .search-form input[type="text"] {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="search-container">
        <div class="search-header">
            <h1><i class="fas fa-search"></i> Buscar Usuários</h1>
        </div>

        <form method="GET" action="" class="search-form">
            <input type="text" name="termo" placeholder="Buscar por nome ou email..." value="<?= htmlspecialchars($termo_busca) ?>" autofocus />
            <button type="submit"><i class="fas fa-search"></i> Buscar</button>
        </form>

        <?php if (!empty($mensagem)): ?>
            <div class="empty-state">
                <p><?= htmlspecialchars($mensagem) ?></p>
            </div>
        <?php elseif (!empty($usuarios)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Setor</th>
                            <th>Tipo</th>
                            <th>Cadastro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= $usuario['id'] ?></td>
                                <td><?= htmlspecialchars($usuario['nome']) ?></td>
                                <td><?= htmlspecialchars($usuario['email']) ?></td>
                                <td><?= htmlspecialchars(ucfirst($usuario['tipo_usuario'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($usuario['data_cadastro'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="back-link">
            <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Voltar para o Dashboard</a>
        </div>
    </div>
</body>
</html>