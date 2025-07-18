<?php
// excluir_chamados_setor.php - Excluir Chamados por Setor (Função Administrativa)
session_start();
require 'conexao.php';

// Verifica se o usuário está logado e se é um administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: index.php'); // Redireciona para o login se não for admin
    exit();
}

$erro = '';
$sucesso = '';
$setores = [];

// Busca todos os setores para o select
try {
    $stmt = $pdo->query("SELECT id, nome FROM setores ORDER BY nome");
    $setores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao carregar setores: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $setor_id_para_excluir = (int)($_POST['setor_id'] ?? 0);
    $confirmacao_texto = trim($_POST['confirmacao'] ?? '');

    // Para segurança, adicione uma confirmação de texto
    if ($confirmacao_texto !== 'CONFIRMAR EXCLUSAO') {
        $erro = 'Por favor, digite "CONFIRMAR EXCLUSAO" no campo de confirmação para prosseguir.';
    } elseif ($setor_id_para_excluir === 0) {
        $erro = 'Por favor, selecione um setor válido.';
    } else {
        try {
            // Primeiro, obtenha os IDs dos usuários pertencentes ao setor
            $stmt_usuarios = $pdo->prepare("SELECT id FROM usuarios WHERE setor_id = ?");
            $stmt_usuarios->execute([$setor_id_para_excluir]);
            $usuarios_ids = $stmt_usuarios->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($usuarios_ids)) {
                // Converte array de IDs para string para a cláusula IN
                $ids_str = implode(',', array_fill(0, count($usuarios_ids), '?'));

                // Exclui os chamados associados a esses usuários
                $stmt_chamados = $pdo->prepare("DELETE FROM chamados WHERE usuario_id IN ($ids_str)");
                $stmt_chamados->execute($usuarios_ids);
                $count_chamados = $stmt_chamados->rowCount();

                $sucesso = "Foram excluídos $count_chamados chamados do setor selecionado.";
            } else {
                $sucesso = "Nenhum usuário ou chamado encontrado para o setor selecionado.";
            }

        } catch (PDOException $e) {
            $erro = "Erro ao excluir chamados: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Belapedra - Excluir Chamados por Setor</title>
        <link rel="icon" href="imagens/Logo_belapedra.ico" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            padding: 2rem;
        }
        .admin-container {
            max-width: 700px;
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
        .admin-container h1 {
            font-size: 2rem;
            font-weight: 800;
            background: var(--gradient-danger);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -1px;
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .warning-box {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid var(--accent-red);
            color: var(--accent-red);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1><i class="fas fa-trash-alt"></i> Excluir Chamados por Setor</h1>
        <div class="warning-box">
            <i class="fas fa-exclamation-triangle"></i> Esta é uma operação irreversível. Todos os chamados dos usuários do setor selecionado serão **PERMANENTEMENTE excluídos**.
        </div>

        <?php if ($erro !== ''): ?>
            <div class="error-msg"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <?php if ($sucesso !== ''): ?>
            <div class="success-msg"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="setor_id">Selecione o Setor</label>
                <select id="setor_id" name="setor_id" required>
                    <option value="">-- Selecione um Setor --</option>
                    <?php foreach ($setores as $setor): ?>
                        <option value="<?= $setor['id'] ?>">
                            <?= htmlspecialchars($setor['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="confirmacao">Digite "CONFIRMAR EXCLUSAO" para prosseguir</label>
                <input type="text" id="confirmacao" name="confirmacao" required placeholder="CONFIRMAR EXCLUSAO" />
            </div>

            <button type="submit">Excluir Chamados do Setor</button>
        </form>

        <div class="back-link">
            <a href="dashboard.php"><i class="fas fa-arrow-left"></i> Voltar para o Dashboard</a>
        </div>
    </div>
</body>
</html>