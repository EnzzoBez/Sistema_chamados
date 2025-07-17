<?php
session_start();
require 'conexao.php';

if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $stmt = $pdo->prepare("UPDATE usuarios SET status = 'offline' WHERE id = ?");
    $stmt->execute([$usuario_id]);

    session_destroy();
}

header('Location: index.php');
exit;