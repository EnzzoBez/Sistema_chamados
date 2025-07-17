<?php
require 'conexao.php';

$setor_id = $_GET['setor_id'] ?? '';

if ($setor_id) {
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE setor_id = ? ORDER BY nome");
    $stmt->execute([$setor_id]);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($usuarios);
} else {
    echo json_encode([]);
}
