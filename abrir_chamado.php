<?php
// abrir_chamado.php - Abrir Novo Chamado
session_start();
require 'conexao.php';

// Verifica se o usuário está logado e se é um usuário comum
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'usuario_comum') {
    header('Location: index.php');
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $prioridade = trim($_POST['prioridade'] ?? 'Baixa'); // Padrão 'Baixa'

    // Validação
    if (empty($titulo) || empty($descricao)) {
        $erro = 'Por favor, preencha o título e a descrição do chamado.';
    } elseif (!in_array($prioridade, ['Baixa', 'Média', 'Alta'])) {
        $erro = 'Prioridade inválida.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO chamados (usuario_id, titulo, descricao, prioridade, status, data_abertura) VALUES (?, ?, ?, ?, 'Pendente', NOW())");
            if ($stmt->execute([$usuario_id, $titulo, $descricao, $prioridade])) {
                $sucesso = 'Chamado aberto com sucesso! Você será redirecionado para o seu painel.';
                // Redireciona após um pequeno atraso para o usuário ver a mensagem de sucesso
                header('Refresh: 3; URL=painel_usuario.php');
                exit();
            } else {
                $erro = 'Erro ao abrir o chamado. Tente novamente.';
            }
        } catch (PDOException $e) {
            $erro = "Erro no banco de dados: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Belapedra - Abrir Chamado</title>
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

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
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

/* Stats Cards Grid */
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

/* Priority and Status Badges - LIGHT THEME */
.priority-badge,
.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
    color: #ffffff; /* White text on badges */
    white-space: nowrap;
}

.priority-baixa { background-color: #2ecc71; /* Green */ }
.priority-media { background-color: #f39c12; /* Orange */ }
.priority-alta { background-color: #e74c3c; /* Red */ }

.status-pendente { background-color: #3498db; /* Blue */ }
.status-em_atendimento { background-color: #f39c12; /* Orange */ }
.status-resolvido { background-color: #2ecc71; /* Green */ }

/* Quick Actions Card - LIGHT THEME */
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

/* Content and Sidebar Layout */
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

/* Online Users Sidebar - LIGHT THEME */
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

/* Info Card - LIGHT THEME */
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

/* Specific styling for "Resposta TI" column */
td i.fa-check-circle {
    color: #2ecc71; /* Green for resolved */
    margin-right: 5px;
}

/* User Panel Specific Adjustments */
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

/* Form Container (abrir_chamado.php) - LIGHT THEME */
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

/* Abrir Chamado - Ajustes para campos maiores */
.form-container.abrir-chamado .form-group input[type="text"],
.form-container.abrir-chamado .form-group textarea,
.form-container.abrir-chamado .form-group select {
    padding: 16px 20px; /* Aumenta o preenchimento interno */
    font-size: 1.1em;   /* Aumenta o tamanho da fonte */
    border: 1px solid #ccc; /* Lighter border for inputs */
    background-color: #f9f9f9; /* Off-white input background */
    color: #333; /* Dark text in inputs */
}

.form-container.abrir-chamado .form-group input[type="text"]:focus,
.form-container.abrir-chamado .form-group textarea:focus,
.form-container.abrir-chamado .form-group select:focus {
    border-color: #3498db; /* Blue accent on focus */
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2); /* Soft glow on focus */
}

.form-container.abrir-chamado .form-group select {
    background-image: url('data:image/svg+xml;utf8,<svg fill="%23555" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>'); /* Darker SVG arrow for light theme */
    background-position: right 18px center; /* Ajusta a posição da seta */
    background-size: 22px; /* Aumenta o tamanho da seta */
}

/* Ajuste do botão de enviar para acompanhar o tamanho dos campos */
.form-container.abrir-chamado button[type="submit"] {
    padding: 18px; /* Aumenta o preenchimento do botão */
    font-size: 1.2em; /* Aumenta o tamanho da fonte do botão */
    background-color: #2ecc71; /* Green submit button */
    color: #ffffff;
}

.form-container.abrir-chamado button[type="submit"]:hover {
    background-color: #27ae60; /* Darker green on hover */
    box-shadow: 0 6px 12px rgba(46, 204, 113, 0.3);
}

.form-container.abrir-chamado .back-link a {
    color: #3498db; /* Blue for links */
}

.form-container.abrir-chamado .back-link a:hover {
    color: #2980b9; /* Darker blue on hover */
}

/* Ajuste para mensagens de erro/sucesso (opcional, para combinar) */
.error-msg, .success-msg {
    padding: 18px 25px; /* Aumenta o padding */
    font-size: 1.05em;   /* Levemente maior */
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

    .form-container.abrir-chamado {
        padding: 30px;
        margin: 15px;
    }

    .form-container.abrir-chamado h1 {
        font-size: 1.8em;
    }

    .form-group input[type="text"],
    .form-group textarea,
    .form-group select,
    button[type="submit"] {
        padding: 12px 15px;
        font-size: 0.95em;
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

    .form-container.abrir-chamado {
        padding: 20px;
        margin: 10px;
    }

    .form-container.abrir-chamado h1 {
        font-size: 1.6em;
        flex-direction: column;
        gap: 8px;
    }

    .form-group label {
        font-size: 0.9em;
    }
}
   </style>
</head>
<body>
    <div class="form-container abrir-chamado"> <div class="card full-width-card">
            <div class="card-header">
                <h2><i class="fas fa-plus-circle"></i> Abrir Novo Chamado</h2>
            </div>
            <div class="card-body">
                <?php if ($erro !== ''): ?>
                    <div class="error-msg"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>
                <?php if ($sucesso !== ''): ?>
                    <div class="success-msg"><?= $sucesso ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="titulo">Título do Chamado</label>
                        <input type="text" id="titulo" name="titulo" required autofocus placeholder="Ex: Problema com o computador..." />
                    </div>

                    <div class="form-group">
                        <label for="descricao">Descrição Detalhada</label>
                        <textarea id="descricao" name="descricao" required placeholder="Descreva o problema com o máximo de detalhes possível..."></textarea>
                    </div>

                    <div class="form-group">
                        <label for="prioridade">Prioridade</label>
                        <select id="prioridade" name="prioridade" required>
                            <option value="Baixa">Baixa</option>
                            <option value="Média">Média</option>
                            <option value="Alta">Alta</option>
                        </select>
                    </div>

                    <button type="submit">Enviar Chamado</button>
                </form>
                <div class="back-link">
                    <a href="painel_usuario.php"><i class="fas fa-arrow-left"></i> Voltar para o Painel</a>
                </div>
            </div> </div> </div> </body>
</html>