<?php
// get_usuarios.php - Retorna lista de usuários (para uso via AJAX, por exemplo)
session_start();
require 'conexao.php';

header('Content-Type: application/json'); // Define o cabeçalho para JSON

// Verifica se o usuário está logado e se é um técnico/admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] !== 'admin' && $_SESSION['tipo_usuario'] !== 'tecnico')) {
    echo json_encode(['error' => 'Acesso não autorizado.']);
    exit();
}

$termo = trim($_GET['termo'] ?? '');
$usuarios = [];

try {
    if (!empty($termo)) {
        $stmt = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE nome LIKE ? OR email LIKE ? ORDER BY nome ASC LIMIT 10");
        $stmt->execute(["%$termo%", "%$termo%"]);
    } else {
        // Se não houver termo, retorna uma lista limitada (ou nenhum)
        $stmt = $pdo->prepare("SELECT id, nome, email FROM usuarios ORDER BY nome ASC LIMIT 10");
        $stmt->execute();
    }
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'usuarios' => $usuarios]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao buscar usuários: ' . $e->getMessage()]);
}
?>