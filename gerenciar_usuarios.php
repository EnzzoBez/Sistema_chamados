<?php
session_start();
require 'conexao.php'; // Inclui o arquivo de conexão com o banco de dados

// Verifica se o usuário está logado e se é um administrador ou técnico
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] !== 'admin' && $_SESSION['tipo_usuario'] !== 'tecnico')) {
    header('Location: index.php'); // Redireciona para o login se não for TI/Admin
    exit();
}

$mensagem = '';
$mensagem_tipo = ''; // 'success' ou 'error'

// --- Lógica para Adicionar/Editar Usuário ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'adicionar_editar') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
        $tipo_usuario = filter_input(INPUT_POST, 'tipo_usuario', FILTER_SANITIZE_SPECIAL_CHARS);
        $setor_id = filter_input(INPUT_POST, 'setor_id', FILTER_VALIDATE_INT);
        $senha = $_POST['senha']; // A senha será tratada abaixo

        if (!$nome || !$email || !$tipo_usuario || !$setor_id) {
            $mensagem = "Erro: Todos os campos obrigatórios devem ser preenchidos (Nome, E-mail, Tipo de Usuário, Setor).";
            $mensagem_tipo = "error";
        } elseif (!in_array($tipo_usuario, ['usuario', 'tecnico', 'admin'])) {
            $mensagem = "Erro: Tipo de usuário inválido.";
            $mensagem_tipo = "error";
        } else {
            try {
                if ($id) { // Editar usuário existente
                    $sql = "UPDATE usuarios SET nome = ?, email = ?, tipo_usuario = ?, setor_id = ? ";
                    $params = [$nome, $email, $tipo_usuario, $setor_id];

                    if (!empty($senha)) {
                        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                        $sql .= ", senha = ? ";
                        $params[] = $senha_hash;
                    }
                    $sql .= "WHERE id = ?";
                    $params[] = $id;

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $mensagem = "Usuário atualizado com sucesso!";
                    $mensagem_tipo = "success";
                } else { // Adicionar novo usuário
                    if (empty($senha)) {
                        $mensagem = "Erro: A senha é obrigatória para novos usuários.";
                        $mensagem_tipo = "error";
                    } else {
                        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, tipo_usuario, setor_id) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$nome, $email, $senha_hash, $tipo_usuario, $setor_id]);
                        $mensagem = "Usuário adicionado com sucesso!";
                        $mensagem_tipo = "success";
                    }
                }
            } catch (PDOException $e) {
                // Verificar se é erro de duplicidade de e-mail (código de erro MySQL para chave duplicada)
                if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $mensagem = "Erro: Já existe um usuário cadastrado com este e-mail.";
                } else {
                    $mensagem = "Erro ao salvar usuário: " . $e->getMessage();
                }
                $mensagem_tipo = "error";
            }
        }
    }
}

// --- Lógica para Excluir Usuário ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'excluir') {
    $id_excluir = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if ($id_excluir && $_SESSION['usuario_id'] != $id_excluir) { // Impede que o usuário logado se exclua
        try {
            // Excluir chamados relacionados ao usuário (opcional, dependendo da sua regra de negócio)
            // $pdo->prepare("DELETE FROM chamados WHERE usuario_id = ?")->execute([$id_excluir]);

            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id_excluir]);
            $mensagem = "Usuário excluído com sucesso!";
            $mensagem_tipo = "success";
        } catch (PDOException $e) {
            $mensagem = "Erro ao excluir usuário: " . $e->getMessage();
            $mensagem_tipo = "error";
        }
    } elseif ($_SESSION['usuario_id'] == $id_excluir) {
         $mensagem = "Erro: Você não pode excluir sua própria conta enquanto estiver logado.";
         $mensagem_tipo = "error";
    } else {
        $mensagem = "Erro: ID de usuário inválido para exclusão.";
        $mensagem_tipo = "error";
    }
}

// --- Buscar Usuário para Edição ---
$usuario_para_editar = null;
if (isset($_GET['action']) && $_GET['action'] === 'editar') {
    $id_editar = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id_editar) {
        $stmt = $pdo->prepare("SELECT id, nome, email, tipo_usuario, setor_id FROM usuarios WHERE id = ?");
        $stmt->execute([$id_editar]);
        $usuario_para_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// --- Busca de todos os Usuários e Setores ---
$stmt_usuarios = $pdo->query("SELECT u.id, u.nome, u.email, u.tipo_usuario, s.nome AS setor_nome
                               FROM usuarios u
                               JOIN setores s ON u.setor_id = s.id
                               ORDER BY u.nome ASC");
$usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

$stmt_setores = $pdo->query("SELECT id, nome FROM setores ORDER BY nome ASC");
$setores = $stmt_setores->fetchAll(PDO::FETCH_ASSOC);

// Tipos de usuário permitidos
$tipos_usuario_permitidos = ['usuario', 'tecnico', 'admin'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Gerenciar Usuários - Belapedra Help Desk</title>
        <link rel="icon" href="imagens/Logo_belapedra.ico" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* General Body and Layout - LIGHT THEME */
body {
    font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f7f6; /* Light gray background */
    color: #333; /* Dark gray text */
    line-height: 1.6;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
    box-sizing: border-box; /* Include padding in element's total width and height */
}

.dashboard-container {
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 1400px; /* Max width for content */
    background-color: #ffffff; /* White background for container */
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); /* Subtle shadow */
    border-radius: 12px;
    overflow: hidden;
    margin: 20px;
}

/* Header - LIGHT THEME */
.header {
    background-color: #3498db; /* Blue header */
    color: #ffffff;
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 5px solid #2980b9; /* Darker blue accent */
    flex-wrap: wrap;
    gap: 15px;
}

.header-left h1 {
    margin: 0;
    font-size: 1.8em;
    display: flex;
    align-items: center;
    gap: 10px;
}

.header-left h1 .fas {
    font-size: 1.2em;
    color: #ecf0f1; /* Light icon color */
}

.header-right {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.welcome-message {
    font-size: 1.1em;
    font-weight: 300;
}

.welcome-message strong {
    font-weight: 600;
    color: #ffffff;
}

.time-display {
    background-color: #2980b9; /* Darker blue for time display */
    padding: 8px 15px;
    border-radius: 5px;
    font-family: 'Consolas', monospace;
    font-size: 0.95em;
    color: #e0f0f9;
}

.logout-btn {
    background-color: #e74c3c; /* Red for logout */
    color: #ffffff;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    transition: background-color 0.3s ease, transform 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.logout-btn:hover {
    background-color: #c0392b; /* Darker red */
    transform: translateY(-2px);
}

/* Main Content Area */
.dashboard-main {
    padding: 30px;
    display: flex;
    flex-direction: column;
    gap: 30px;
}

/* Cards General Styling - LIGHT THEME */
.card {
    background-color: #ffffff; /* White card background */
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); /* Subtle shadow */
    padding: 25px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    color: #333;
}

/* No transform on hover for full-width cards in gerenciar_usuarios to avoid shifting */
.card.full-width-card:hover {
    transform: none;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); /* Keep original shadow */
}


.card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee; /* Light border */
}

.card-header h2 {
    margin: 0;
    font-size: 1.4em;
    color: #2c3e50; /* Darker heading color */
}

.card-header .fas {
    color: #3498db; /* Blue icon */
    font-size: 1.1em;
}

.card-body {
    font-size: 0.95em;
    color: #555; /* Medium gray text */
}

/* Stats Cards Grid (might not be used in gerenciar_usuarios.php, but good to keep) */
.stats-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 25px;
}

.stat-card-main {
    display: flex;
    align-items: center;
    gap: 20px;
    background: linear-gradient(135deg, #e0f7f7, #c1f0eb); /* Light gradient */
    border-left: 8px solid #2ecc71; /* Green accent */
    padding: 25px 30px;
}

.stat-card-main.prioridade-chamados {
    border-left-color: #f39c12; /* Orange accent for priority */
    background: linear-gradient(135deg, #fff0e6, #ffe0cc);
}

.card-icon {
    font-size: 3.5em;
    color: #2ecc71; /* Green icon */
    opacity: 0.8;
}

.stat-card-main.prioridade-chamados .card-icon {
    color: #f39c12; /* Orange icon */
}

.card-content h3 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 1.3em;
    color: #2c3e50;
}

.stat-value {
    font-size: 2.8em;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 10px;
}

.stat-value-highlight {
    font-size: 1.8em;
    font-weight: bold;
    margin-bottom: 10px;
}

.stat-value-highlight .priority-label {
    font-size: 0.7em;
    vertical-align: super;
}

.stat-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.stat-list li {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
    border-bottom: 1px dashed #eee; /* Light dashed border */
    font-size: 0.9em;
}

.stat-list li:last-child {
    border-bottom: none;
}

.stat-list .value {
    font-weight: bold;
    color: #444;
}

/* Priority and Status Badges (general) */
.priority-badge,
.status-badge,
.badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
    color: #ffffff; /* White text on badges */
    white-space: nowrap;
    line-height: 1; /* Ensure text aligns vertically in badge */
}

.priority-baixa, .badge.type-usuario { background-color: #2ecc71; /* Green */ }
.priority-media, .badge.type-tecnico { background-color: #f39c12; /* Orange */ }
.priority-alta, .badge.type-admin { background-color: #e74c3c; /* Red */ }

.status-pendente { background-color: #3498db; /* Blue */ }
.status-em_atendimento { background-color: #f39c12; /* Orange */ }
.status-resolvido { background-color: #2ecc71; /* Green */ }


/* Quick Actions Card (reused from dashboard) */
.quick-actions-card .card-icon {
    color: #9b59b6; /* Purple */
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px 15px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.2s ease;
    font-size: 0.95em;
    gap: 8px;
    text-align: center;
    color: #ffffff; /* White text on buttons */
}

.quick-action-btn .fas {
    font-size: 1.1em;
}

.primary-btn {
    background-color: #2ecc71; /* Green for primary */
}
.primary-btn:hover {
    background-color: #27ae60;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(46, 204, 113, 0.3);
}

.secondary-btn {
    background-color: #3498db; /* Blue for secondary */
}
.secondary-btn:hover {
    background-color: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
}

.danger-btn {
    background-color: #e74c3c; /* Red for danger */
}
.danger-btn:hover {
    background-color: #c0392b;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(231, 76, 60, 0.3);
}

/* Content and Sidebar Layout (reused) */
.content-and-sidebar {
    display: flex;
    flex-wrap: wrap;
    gap: 25px;
}

.main-content {
    flex: 3;
    min-width: 600px;
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.sidebar {
    flex: 1;
    min-width: 300px;
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.full-width-card {
    width: 100%;
}

/* Table Styling - LIGHT THEME */
.table-responsive {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    background-color: #fdfdfd; /* Off-white for table background */
    color: #333;
}

table thead tr {
    background-color: #ecf0f1; /* Light header background */
    color: #34495e;
    text-align: left;
    border-bottom: 2px solid #ccc;
}

table th,
table td {
    padding: 12px 15px;
    border-bottom: 1px solid #e0e0e0;
}

table th {
    font-weight: 600;
    font-size: 0.9em;
    text-transform: uppercase;
}

table tbody tr:hover {
    background-color: #f5f9fb; /* Light hover effect for rows */
}

table td {
    font-size: 0.9em;
}

.actions-cell {
    white-space: nowrap;
}

/* Buttons inside table - LIGHT THEME */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 12px;
    border-radius: 5px;
    text-decoration: none;
    font-size: 0.85em;
    font-weight: 500;
    transition: background-color 0.2s ease, transform 0.1s ease;
    margin-right: 5px;
    color: #ffffff; /* White text on all buttons */
    cursor: pointer; /* Indicate it's clickable */
    border: none; /* Remove default button border */
}

.btn-primary {
    background-color: #2ecc71; /* Green */
}
.btn-primary:hover {
    background-color: #27ae60;
    transform: translateY(-1px);
}

.btn-secondary {
    background-color: #7f8c8d; /* Gray */
}
.btn-secondary:hover {
    background-color: #6c7a89;
    transform: translateY(-1px);
}

.btn-danger {
    background-color: #e74c3c; /* Red */
}
.btn-danger:hover {
    background-color: #c0392b;
    transform: translateY(-1px);
}

.btn-disabled {
    background-color: #cccccc; /* Light gray for disabled */
    color: #666666;
    cursor: not-allowed;
    opacity: 0.8;
}

/* Empty State Styling - LIGHT THEME */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #95a5a6;
    background-color: #fcfcfc; /* Off-white background */
    border-radius: 8px;
    border: 1px dashed #e0e0e0; /* Light dashed border */
    margin-top: 20px;
}

.empty-state .fas {
    font-size: 3em;
    margin-bottom: 15px;
    color: #bdc3c7; /* Light gray icon */
}

.empty-state h3 {
    margin: 0 0 10px;
    color: #7f8c8d;
    font-size: 1.3em;
}

.empty-state p {
    margin: 0;
    font-size: 0.9em;
}

/* Online Users Sidebar (reused) */
.online-users {
    padding: 0;
}

.user-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid #eee; /* Light border */
    transition: background-color 0.2s ease;
}

.user-item:last-child {
    border-bottom: none;
}

.user-item:hover {
    background-color: #f5f9fb; /* Light hover */
}

.online-indicator {
    width: 10px;
    height: 10px;
    background-color: #2ecc71; /* Green for online */
    border-radius: 50%;
    box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.3);
}

.user-info {
    flex-grow: 1;
}

.user-name {
    font-weight: 600;
    color: #333;
    font-size: 0.95em;
}

.user-status {
    font-size: 0.8em;
    color: #666;
    text-transform: capitalize;
}

/* Info Card (reused) */
.info-card {
    background: linear-gradient(135deg, #e6f7ff, #ccedff); /* Light blue gradient */
    border-left: 8px solid #3498db; /* Blue accent */
}

.info-card .card-header .fas {
    color: #3498db; /* Blue icon */
}

.info-card .card-body p {
    margin-bottom: 10px;
    line-height: 1.5;
}

.info-card .card-body p:last-child {
    margin-bottom: 0;
}

/* Specific styling for "Resposta TI" column (reused) */
td i.fa-check-circle {
    color: #2ecc71; /* Green for resolved */
    margin-right: 5px;
}

/* User Panel Specific Adjustments (reused) */
.header-actions .btn {
    background-color: #2ecc71; /* Green for user's "Abrir Chamado" */
    color: #ffffff;
}

.header-actions .btn:hover {
    background-color: #27ae60;
}

.stat-card.user-panel-stat-card {
    border-left-color: #2ecc71; /* Green accent for user panel stats */
    background: linear-gradient(135deg, #e0f7f7, #c1f0eb);
}

.stat-card.user-panel-stat-card h3 .fas {
    color: #2ecc71;
}

/* Form Container (abrir_chamado.php) - LIGHT THEME (reused, but simplified as we have specific form-grid) */
.form-container.abrir-chamado { /* Added a specific class for this form */
    background: #ffffff; /* White background */
    padding: 40px; /* Base padding */
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    max-width: 650px;
    position: relative;
    z-index: 1;
}

.form-container.abrir-chamado h1 {
    font-size: 2.2em;
    font-weight: 700;
    background: linear-gradient(135deg, #3498db, #2980b9); /* Blue gradient for title */
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -0.5px;
    text-align: center;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.form-container.abrir-chamado h1 .fas {
    color: #3498db; /* Blue icon */
}

/* Adjustments for larger form fields (reused from previous response) */
.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"],
.form-group textarea,
.form-group select {
    width: 100%; /* Make inputs take full width of their group */
    padding: 14px 18px; /* Slightly adjusted padding for consistency */
    font-size: 1.05em;   /* Slightly adjusted font size */
    border: 1px solid #ccc; /* Lighter border for inputs */
    border-radius: 8px; /* More rounded corners */
    background-color: #f9f9f9; /* Off-white input background */
    color: #333; /* Dark text in inputs */
    box-sizing: border-box; /* Include padding in element's total width and height */
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-group input[type="text"]:focus,
.form-group input[type="email"]:focus,
.form-group input[type="password"]:focus,
.form-group textarea:focus,
.form-group select:focus {
    border-color: #3498db; /* Blue accent on focus */
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2); /* Soft glow on focus */
    outline: none; /* Remove default outline */
}

/* Custom arrow for select (reused) */
.form-group select {
    -webkit-appearance: none; /* Remove default arrow on Chrome/Safari */
    -moz-appearance: none; /* Remove default arrow on Firefox */
    appearance: none; /* Remove default arrow */
    background-image: url('data:image/svg+xml;utf8,<svg fill="%23555" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>'); /* Darker SVG arrow for light theme */
    background-repeat: no-repeat;
    background-position: right 15px center; /* Position the arrow */
    background-size: 20px; /* Size the arrow */
    padding-right: 40px; /* Make space for the arrow */
}


/* General Form Group Styling */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #444;
    font-size: 0.95em;
}

/* Flexbox/Grid for Form Layout */
.form-grid {
    display: grid;
    grid-template-columns: 1fr; /* Default to single column */
    gap: 20px;
}

@media (min-width: 600px) {
    .form-grid {
        grid-template-columns: repeat(2, 1fr); /* Two columns on larger screens */
    }
    .form-grid .form-group:nth-child(1), /* Nome */
    .form-grid .form-group:nth-child(2) { /* Email */
        grid-column: span 1;
    }
    .form-grid .form-group:nth-child(3) { /* Senha */
        grid-column: span 2; /* Senha takes full width */
    }
    .form-grid .form-group:nth-child(4), /* Tipo de Usuário */
    .form-grid .form-group:nth-child(5) { /* Setor */
        grid-column: span 1;
    }
}

.form-actions-grid {
    grid-column: 1 / -1; /* Buttons span all columns */
    display: flex;
    justify-content: flex-end; /* Align buttons to the right */
    gap: 15px;
    margin-top: 10px;
}

.form-actions-grid .btn {
    padding: 12px 25px;
    font-size: 1.1em;
    min-width: 150px; /* Ensure buttons have a minimum width */
    justify-content: center; /* Center icon and text */
}

/* Message Box Styling (Success/Error) */
.message {
    padding: 15px 25px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 1em;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
    border: 1px solid; /* Add a border */
}

.message.success {
    background-color: #e6ffe6; /* Light green */
    color: #28a745; /* Darker green text */
    border-color: #28a745;
}

.message.error {
    background-color: #ffe6e6; /* Light red */
    color: #dc3545; /* Darker red text */
    border-color: #dc3545;
}

/* Responsive Adjustments (reused from previous theme) */
@media (max-width: 1200px) {
    .content-and-sidebar {
        flex-direction: column;
    }

    .main-content,
    .sidebar {
        min-width: unset;
        width: 100%;
    }
}

@media (max-width: 768px) {
    .header {
        flex-direction: column;
        align-items: flex-start;
    }

    .header-right, .header-actions {
        margin-top: 15px;
        justify-content: flex-start;
        width: 100%;
    }

    .dashboard-main {
        padding: 20px;
    }

    .stats-cards-grid, .stats-grid {
        grid-template-columns: 1fr;
    }

    .stat-card-main, .stat-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
        padding: 20px;
    }

    .card-icon {
        font-size: 3em;
    }

    .stat-value {
        font-size: 2.5em;
    }

    .quick-actions-grid {
        grid-template-columns: 1fr;
    }

    table th, table td {
        padding: 10px 12px;
    }

    .btn, table .btn {
        padding: 6px 10px;
        font-size: 0.8em;
    }

    /* Form specific for smaller screens */
    .form-grid {
        grid-template-columns: 1fr; /* Single column on small screens */
    }
    .form-grid .form-group:nth-child(3) { /* Senha, reset to 1 column */
        grid-column: span 1;
    }
    .form-actions-grid {
        flex-direction: column;
        gap: 10px;
    }
    .form-actions-grid .btn {
        width: 100%;
        min-width: unset;
    }
}

@media (max-width: 480px) {
    .dashboard-container {
        margin: 10px;
        border-radius: 8px;
    }

    .header {
        padding: 15px;
    }

    .header-left h1 {
        font-size: 1.5em;
    }

    .logout-btn, .header-actions .btn {
        padding: 8px 15px;
        font-size: 0.9em;
    }

    .header-actions {
        flex-direction: column;
        gap: 10px;
    }

    .btn, .logout-btn {
        width: 100%;
        justify-content: center;
    }

    .dashboard-main {
        padding: 15px;
    }

    .card {
        padding: 20px;
    }

    .card-header h2 {
        font-size: 1.2em;
    }

    .quick-action-btn {
        padding: 10px 12px;
        font-size: 0.9em;
    }

    .empty-state {
        padding: 20px 10px;
    }

    .empty-state .fas {
        font-size: 2.5em;
    }

    .empty-state h3 {
        font-size: 1.1em;
    }

    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"],
    .form-group textarea,
    .form-group select {
        padding: 10px 12px;
        font-size: 0.9em;
    }
}
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header class="header">
            <div class="header-left">
                <h1><i class="fas fa-users-cog"></i> Gerenciar Usuários</h1>
            </div>
            <div class="header-right">
                <span class="welcome-message">Olá, <strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong>!</span>
                <a href="dashboard.php" class="quick-action-btn secondary-btn"><i class="fas fa-arrow-left"></i> Voltar ao Dashboard</a>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </header>

        <main class="dashboard-main">
            <?php if ($mensagem): ?>
                <div class="message <?= $mensagem_tipo ?>">
                    <?= htmlspecialchars($mensagem) ?>
                </div>
            <?php endif; ?>

            <section class="card full-width-card">
                <div class="card-header">
                    <h2><i class="fas fa-user-plus"></i> <?= $usuario_para_editar ? 'Editar Usuário' : 'Adicionar Novo Usuário' ?></h2>
                </div>
                <div class="card-body">
                    <form action="gerenciar_usuarios.php" method="POST" class="form-grid">
                        <input type="hidden" name="action" value="adicionar_editar">
                        <?php if ($usuario_para_editar): ?>
                            <input type="hidden" name="id" value="<?= htmlspecialchars($usuario_para_editar['id']) ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="nome">Nome Completo:</label>
                            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($usuario_para_editar['nome'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">E-mail:</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario_para_editar['email'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="senha">Senha: <small>(<?= $usuario_para_editar ? 'Deixe em branco para manter a atual' : 'Obrigatória' ?>)</small></label>
                            <input type="password" id="senha" name="senha">
                        </div>
                        <div class="form-group">
                            <label for="tipo_usuario">Tipo de Usuário:</label>
                            <select id="tipo_usuario" name="tipo_usuario" required>
                                <?php foreach ($tipos_usuario_permitidos as $tipo): ?>
                                    <option value="<?= $tipo ?>" <?= ($usuario_para_editar && $usuario_para_editar['tipo_usuario'] == $tipo) ? 'selected' : '' ?>>
                                        <?= ucfirst($tipo) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="setor_id">Setor:</label>
                            <select id="setor_id" name="setor_id" required>
                                <?php if (empty($setores)): ?>
                                    <option value="">Nenhum setor cadastrado</option>
                                <?php else: ?>
                                    <option value="">Selecione um setor</option>
                                    <?php foreach ($setores as $setor): ?>
                                        <option value="<?= $setor['id'] ?>" <?= ($usuario_para_editar && $usuario_para_editar['setor_id'] == $setor['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($setor['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-actions-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= $usuario_para_editar ? 'Salvar Alterações' : 'Adicionar Usuário' ?>
                            </button>
                            <?php if ($usuario_para_editar): ?>
                                <a href="gerenciar_usuarios.php" class="btn btn-secondary"><i class="fas fa-times-circle"></i> Cancelar Edição</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </section>

            <section class="card full-width-card">
                <div class="card-header">
                    <h2><i class="fas fa-users"></i> Usuários Cadastrados</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($usuarios)): ?>
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Nenhum usuário cadastrado.</h3>
                            <p>Comece adicionando usuários para o sistema.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nome</th>
                                        <th>E-mail</th>
                                        <th>Tipo</th>
                                        <th>Setor</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['id']) ?></td>
                                            <td><?= htmlspecialchars($user['nome']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><span class="badge type-<?= strtolower($user['tipo_usuario']) ?>"><?= htmlspecialchars(ucfirst($user['tipo_usuario'])) ?></span></td>
                                            <td><?= htmlspecialchars($user['setor_nome']) ?></td>
                                            <td class="actions-cell">
                                                <a href="gerenciar_usuarios.php?action=editar&id=<?= $user['id'] ?>" class="btn btn-sm btn-secondary">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                                <?php if ($_SESSION['usuario_id'] != $user['id']): // Impede que o usuário logado se exclua ?>
                                                    <a href="gerenciar_usuarios.php?action=excluir&id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja excluir este usuário? Esta ação é irreversível e excluirá todos os chamados relacionados (se configurado).');">
                                                        <i class="fas fa-trash-alt"></i> Excluir
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-disabled" disabled title="Você não pode excluir sua própria conta"><i class="fas fa-ban"></i> Excluir</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>
</body>
</html>