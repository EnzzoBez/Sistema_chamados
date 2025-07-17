<?php
require 'conexao.php';

if (!isset($_GET['setor_id'])) {
    echo json_encode([]);
    exit;
}

$setor_id = (int)$_GET['setor_id'];

$stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE setor_id = ? ORDER BY nome");
$stmt->execute([$setor_id]);
$usuarios = $stmt->fetchAll();

header('Content-Type: application/json');
echo json_encode($usuarios);
