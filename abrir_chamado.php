<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $prioridade = $_POST['prioridade'] ?? 'Média';

    if (!$titulo || !$descricao) {
        $_SESSION['erro_abertura'] = 'Título e descrição são obrigatórios.';
        header('Location: painel_usuario.php');
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO chamados (usuario_id, titulo, descricao, prioridade, status, data_abertura) VALUES (?, ?, ?, ?, 'Pendente', NOW())");
    $stmt->execute([$usuario_id, $titulo, $descricao, $prioridade]);

    $_SESSION['sucesso_abertura'] = 'Chamado aberto com sucesso!';
    header('Location: painel_usuario.php');
    exit;
} else {
    header('Location: painel_usuario.php');
    exit;
}
