<?php
session_start();
require 'conexao.php';

// Verifica login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$chamado_id = (int) $_GET['id'];

// Atualiza chamado com técnico e status "Em Atendimento"
$stmt = $pdo->prepare("
    UPDATE chamados 
    SET atendente_id = ?, status = 'Em Atendimento'
    WHERE id = ? AND (atendente_id IS NULL OR atendente_id = ?)
");
$stmt->execute([$usuario_id, $chamado_id, $usuario_id]);

header('Location: painel_usuario.php');
exit;
