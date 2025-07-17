<?php
require 'conexao.php';
session_start();

// Proteção básica: exige login e perfil de admin
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $setor_id = $_POST['setor_id'] ?? null;

    if ($setor_id) {
        // Buscar os IDs dos usuários do setor selecionado
        $stmtUsuarios = $pdo->prepare("SELECT id FROM usuarios WHERE setor_id = ?");
        $stmtUsuarios->execute([$setor_id]);
        $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_COLUMN);

        if ($usuarios) {
            // Monta uma lista de IDs separados por vírgula
            $placeholders = implode(',', array_fill(0, count($usuarios), '?'));

            // Excluir chamados dos usuários encontrados
            $stmtExcluir = $pdo->prepare("DELETE FROM chamados WHERE usuario_id IN ($placeholders)");
            $stmtExcluir->execute($usuarios);

            $msg = "Todos os chamados do setor foram excluídos com sucesso.";
        } else {
            $msg = "Nenhum usuário encontrado para esse setor.";
        }
    } else {
        $msg = "Setor inválido.";
    }
}

// Carrega os setores
$setores = $pdo->query("SELECT id, nome FROM setores ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Excluir Chamados por Setor</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Excluir Chamados por Setor</h2>

    <?php if ($msg): ?>
        <p style="color:red;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <form method="post">
        <label for="setor_id">Selecione o Setor:</label>
        <select name="setor_id" required>
            <option value="">-- Selecione --</option>
            <?php foreach ($setores as $setor): ?>
                <option value="<?= $setor['id'] ?>"><?= htmlspecialchars($setor['nome']) ?></option>
            <?php endforeach; ?>
        </select>
        <br><br>
        <button type="submit" onclick="return confirm('Tem certeza que deseja excluir TODOS os chamados desse setor?')">
            Excluir Chamados do Setor
        </button>
    </form>
</body>
</html>
