<?php
// dashboard.php - Painel de Controle para TI/Admin
session_start();
require 'conexao.php'; // Inclui o arquivo de conexão com o banco de dados

// Verifica se o usuário está logado e se é um técnico ou admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] !== 'admin' && $_SESSION['tipo_usuario'] !== 'tecnico')) {
    header('Location: index.php'); // Redireciona para o login se não for TI/Admin
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'];
$tipo_usuario = $_SESSION['tipo_usuario'];

// Marca usuário como online no logs_login
try {
    $stmt = $pdo->prepare("REPLACE INTO logs_login (usuario_id, last_active) VALUES (?, NOW())");
    $stmt->execute([$usuario_id]);
} catch (PDOException $e) {
    // Log do erro, mas não impeça o dashboard de carregar
    error_log("Erro ao atualizar last_active: " . $e->getMessage());
}


// --- Dados para o Dashboard ---

// Total de chamados
$totalChamados = $pdo->query("SELECT COUNT(*) FROM chamados")->fetchColumn();

// Chamados por status
$statusCount = [];
$stmt = $pdo->query("SELECT status, COUNT(*) AS count FROM chamados GROUP BY status");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $statusCount[$row['status']] = $row['count'];
}
$statusCount['Pendente'] = $statusCount['Pendente'] ?? 0;
$statusCount['Em Atendimento'] = $statusCount['Em Atendimento'] ?? 0;
$statusCount['Resolvido'] = $statusCount['Resolvido'] ?? 0;

// Chamados por prioridade
$prioridadeCount = [];
$stmt = $pdo->query("SELECT prioridade, COUNT(*) AS count FROM chamados GROUP BY prioridade");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $prioridadeCount[$row['prioridade']] = $row['count'];
}
$prioridadeCount['Baixa'] = $prioridadeCount['Baixa'] ?? 0;
$prioridadeCount['Média'] = $prioridadeCount['Média'] ?? 0;
$prioridadeCount['Alta'] = $prioridadeCount['Alta'] ?? 0;

// Chamados Pendentes (lista completa para TI)
$stmt = $pdo->prepare("SELECT ch.id, ch.titulo, ch.descricao, ch.prioridade, ch.status, ch.data_abertura,
                            us.nome AS usuario_nome, se.nome AS setor_nome
                            FROM chamados ch
                            JOIN usuarios us ON ch.usuario_id = us.id
                            JOIN setores se ON us.setor_id = se.id
                            WHERE ch.status IN ('Pendente', 'Em Atendimento')
                            ORDER BY FIELD(ch.prioridade, 'Alta', 'Média', 'Baixa') DESC, ch.data_abertura ASC");
$stmt->execute();
$chamadosPendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Últimos Chamados Resolvidos (limitado a 5)
$stmt = $pdo->query("SELECT ch.id, ch.titulo, ch.data_resolucao, us.nome AS usuario_nome
                    FROM chamados ch
                    JOIN usuarios us ON ch.usuario_id = us.id
                    WHERE ch.status = 'Resolvido'
                    ORDER BY ch.data_resolucao DESC
                    LIMIT 5");
$recentesResolvidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Usuários online (últimos 5 minutos)
$stmt = $pdo->query("SELECT u.nome, u.tipo_usuario
                    FROM logs_login ll
                    JOIN usuarios u ON ll.usuario_id = u.id
                    WHERE ll.last_active >= NOW() - INTERVAL 5 MINUTE
                    ORDER BY u.nome ASC");
$usuariosOnline = $stmt->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Belapedra - Dashboard TI</title>
    <link rel="icon" href="imagens/Logo_belapedra.ico" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta http-equiv="refresh" content="20">
    <style>
        /* General Body and Layout */
body {
    font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f7f6; /* Light gray background */
    color: #333;
    line-height: 1.6;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
}

.dashboard-container {
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 1400px; /* Max width for content */
    background-color: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    overflow: hidden;
    margin: 20px;
}

/* Header */
.header {
    background-color: #2c3e50; /* Dark blue/gray */
    color: #ffffff;
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 5px solid #3498db; /* Blue accent */
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
    color: #ecf0f1;
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
    color: #ecf0f1;
}

.time-display {
    background-color: #34495e;
    padding: 8px 15px;
    border-radius: 5px;
    font-family: 'Consolas', monospace;
    font-size: 0.95em;
}

.logout-btn {
    background-color: #e74c3c; /* Red */
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

/* Cards General Styling */
.card {
    background-color: #ffffff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    padding: 25px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
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
    border-bottom: 1px solid #eee;
}

.card-header h2 {
    margin: 0;
    font-size: 1.4em;
    color: #34495e;
}

.card-header .fas {
    color: #3498db;
    font-size: 1.1em;
}

.card-body {
    font-size: 0.95em;
    color: #555;
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
    background: linear-gradient(135deg, #e0f2f7, #c1e4eb); /* Light gradient */
    border-left: 8px solid #3498db;
    padding: 25px 30px;
}

.stat-card-main.prioridade-chamados {
    border-left-color: #f39c12; /* Orange accent */
    background: linear-gradient(135deg, #fef0db, #fce9c4);
}


.card-icon {
    font-size: 3.5em;
    color: #3498db;
    opacity: 0.8;
}

.stat-card-main.prioridade-chamados .card-icon {
    color: #f39c12;
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
    font-size: 0.7em; /* Make "Alta" label smaller in highlight */
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
    border-bottom: 1px dashed #eee;
    font-size: 0.9em;
}

.stat-list li:last-child {
    border-bottom: none;
}

.stat-list .value {
    font-weight: bold;
    color: #444;
}

/* Priority and Status Badges */
.priority-badge,
.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: bold;
    text-transform: uppercase;
    color: #fff;
    white-space: nowrap;
}

.priority-low { background-color: #2ecc71; /* Green */ }
.priority-media { background-color: #f39c12; /* Orange */ }
.priority-alta { background-color: #e74c3c; /* Red */ }

.status-pendente { background-color: #3498db; /* Blue */ }
.status-em_atendimento { background-color: #f39c12; /* Orange */ }
.status-resolvido { background-color: #2ecc71; /* Green */ }


/* Quick Actions Card */
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
}

.quick-action-btn .fas {
    font-size: 1.1em;
}

.primary-btn {
    background-color: #3498db; /* Blue */
    color: #ffffff;
}
.primary-btn:hover {
    background-color: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
}

.secondary-btn {
    background-color: #95a5a6; /* Gray */
    color: #ffffff;
}
.secondary-btn:hover {
    background-color: #7f8c8d;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(149, 165, 166, 0.3);
}

.danger-btn {
    background-color: #e74c3c; /* Red */
    color: #ffffff;
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
    flex: 3; /* Takes up more space */
    min-width: 600px; /* Minimum width before wrapping */
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.sidebar {
    flex: 1; /* Takes remaining space */
    min-width: 300px; /* Minimum width before wrapping */
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.full-width-card {
    width: 100%;
}

/* Table Styling */
.table-responsive {
    overflow-x: auto; /* Allows table to scroll horizontally on small screens */
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    background-color: #fdfdfd;
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
    background-color: #f5f9fb; /* Hover effect for rows */
}

table td {
    font-size: 0.9em;
}

.actions-cell {
    white-space: nowrap; /* Prevents action buttons from wrapping */
}

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
    margin-right: 5px; /* Space between buttons */
}

.btn-primary {
    background-color: #3498db;
    color: #fff;
}
.btn-primary:hover {
    background-color: #2980b9;
    transform: translateY(-1px);
}

.btn-secondary {
    background-color: #7f8c8d;
    color: #fff;
}
.btn-secondary:hover {
    background-color: #6c7a89;
    transform: translateY(-1px);
}

/* Empty State Styling */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #95a5a6;
    background-color: #fcfcfc;
    border-radius: 8px;
    border: 1px dashed #e0e0e0;
    margin-top: 20px;
}

.empty-state .fas {
    font-size: 3em;
    margin-bottom: 15px;
    color: #bdc3c7;
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

/* Online Users Sidebar */
.online-users {
    padding: 0; /* Remove default card padding */
}

.user-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid #eee;
    transition: background-color 0.2s ease;
}

.user-item:last-child {
    border-bottom: none;
}

.user-item:hover {
    background-color: #f5f9fb;
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
    color: #34495e;
    font-size: 0.95em;
}

.user-status {
    font-size: 0.8em;
    color: #7f8c8d;
    text-transform: capitalize;
}

/* Info Card */
.info-card {
    background: linear-gradient(135deg, #dceefc, #b4d8f0);
    border-left: 8px solid #3498db;
}

.info-card .card-header .fas {
    color: #3498db;
}

.info-card .card-body p {
    margin-bottom: 10px;
    line-height: 1.5;
}

.info-card .card-body p:last-child {
    margin-bottom: 0;
}

/* Responsive Adjustments */
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

    .header-right {
        margin-top: 15px;
        justify-content: flex-start;
        width: 100%;
    }

    .dashboard-main {
        padding: 20px;
    }

    .stats-cards-grid {
        grid-template-columns: 1fr;
    }

    .stat-card-main {
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

    .btn {
        padding: 6px 10px;
        font-size: 0.8em;
    }
}

@media (max-width: 480px) {
    .dashboard-container {
        margin: 10px;
        border-radius: 8px;
    }

    .header {
        padding: 15px 20px;
    }

    .header-left h1 {
        font-size: 1.5em;
    }

    .logout-btn {
        padding: 8px 15px;
        font-size: 0.9em;
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
}
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header class="header">
            <div class="header-left">
                <h1><i class="fas fa-tools"></i> Painel de Gerenciamento de Chamados</h1>
            </div>
            <div class="header-right">
                <span class="welcome-message">Olá, **<?= htmlspecialchars($usuario_nome) ?>**! (<?= ucfirst($tipo_usuario) ?>)</span>
                <span id="current-time" class="time-display"></span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </header>

        <main class="dashboard-main">
            <section class="stats-cards-grid">
                <div class="card stat-card-main total-chamados">
                    <div class="card-icon"><i class="fas fa-ticket-alt"></i></div>
                    <div class="card-content">
                        <h3>Total de Chamados</h3>
                        <div class="stat-value"><?= $totalChamados ?></div>
                        <ul class="stat-list">
                            <li><span>Pendentes:</span> <span class="value"><?= $statusCount['Pendente'] ?></span></li>
                            <li><span>Em Atendimento:</span> <span class="value"><?= $statusCount['Em Atendimento'] ?></span></li>
                            <li><span>Resolvidos:</span> <span class="value"><?= $statusCount['Resolvido'] ?></span></li>
                        </ul>
                    </div>
                </div>

                <div class="card stat-card-main prioridade-chamados">
                    <div class="card-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="card-content">
                        <h3>Prioridade dos Chamados</h3>
                        <div class="stat-value-highlight">
                            <span class="priority-label high-priority-text">Alta:</span> <span class="value"><?= $prioridadeCount['Alta'] ?></span>
                        </div>
                        <ul class="stat-list">
                            <li><span>Média:</span> <span class="value"><?= $prioridadeCount['Média'] ?></span></li>
                            <li><span>Baixa:</span> <span class="value"><?= $prioridadeCount['Baixa'] ?></span></li>
                        </ul>
                    </div>
                </div>

                <div class="card quick-actions-card">
                    <div class="card-icon"><i class="fas fa-bolt"></i></div>
                    <div class="card-content">
                        <h3>Ações Rápidas</h3>
                        <div class="quick-actions-grid">
                            <a href="gerenciar_usuarios.php" class="quick-action-btn secondary-btn">
                                <i class="fas fa-users-cog"></i> Gerenciar Usuários
                            </a>
                            <?php if ($tipo_usuario === 'admin'): ?>
                                <a href="excluir_chamados_setor.php" class="quick-action-btn danger-btn">
                                    <i class="fas fa-trash-alt"></i> Excluir Chamados (Admin)
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <div class="content-and-sidebar">
                <section class="main-content">
                    <div class="card full-width-card">
                        <div class="card-header">
                            <h2><i class="fas fa-hourglass-half"></i> Chamados Ativos</h2>
                        </div>
                        <div class="card-body">
                            <?php if (count($chamadosPendentes) === 0): ?>
                                <div class="empty-state">
                                    <i class="fas fa-check-circle"></i>
                                    <h3>Parabéns! Nenhum chamado pendente no momento.</h3>
                                    <p>Sua equipe está em dia com as demandas.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Título</th>
                                                <th>Usuário</th>
                                                <th>Setor</th>
                                                <th>Prioridade</th>
                                                <th>Status</th>
                                                <th>Abertura</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($chamadosPendentes as $chamado): ?>
                                                <tr>
                                                    <td><strong>#<?= $chamado['id'] ?></strong></td>
                                                    <td><?= htmlspecialchars($chamado['titulo']) ?></td>
                                                    <td><?= htmlspecialchars($chamado['usuario_nome']) ?></td>
                                                    <td><?= htmlspecialchars($chamado['setor_nome']) ?></td>
                                                    <td>
                                                        <span class="priority-badge priority-<?= strtolower($chamado['prioridade']) ?>">
                                                            <?= htmlspecialchars($chamado['prioridade']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge status-<?= str_replace(' ', '_', strtolower($chamado['status'])) ?>">
                                                            <?= htmlspecialchars($chamado['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('d/m H:i', strtotime($chamado['data_abertura'])) ?></td>
                                                    <td class="actions-cell">
                                                        <a href="atender_chamado.php?id=<?= $chamado['id'] ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-hand-paper"></i> Atender
                                                        </a>
                                                        <a href="detalhes_chamado.php?id=<?= $chamado['id'] ?>" class="btn btn-sm btn-secondary">
                                                            <i class="fas fa-eye"></i> Ver
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card full-width-card">
                        <div class="card-header">
                            <h2><i class="fas fa-history"></i> Últimos Chamados Resolvidos</h2>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentesResolvidos)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-box-open"></i>
                                    <h3>Nenhum chamado resolvido recentemente.</h3>
                                    <p>Nenhuma atividade recente para exibir.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Título</th>
                                                <th>Usuário</th>
                                                <th>Resolução</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentesResolvidos as $chamado): ?>
                                                <tr>
                                                    <td>#<?= $chamado['id'] ?></td>
                                                    <td><?= htmlspecialchars($chamado['titulo']) ?></td>
                                                    <td><?= htmlspecialchars($chamado['usuario_nome']) ?></td>
                                                    <td><?= date('d/m H:i', strtotime($chamado['data_resolucao'])) ?></td>
                                                    <td class="actions-cell">
                                                        <a href="detalhes_chamado.php?id=<?= $chamado['id'] ?>" class="btn btn-sm btn-secondary">
                                                            <i class="fas fa-eye"></i> Ver
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <aside class="sidebar">
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-users"></i> Usuários Online</h2>
                        </div>
                        <div class="card-body online-users">
                            <?php if (empty($usuariosOnline)): ?>
                                <div class="empty-state" style="padding: 1rem;">
                                    <i class="fas fa-user-slash"></i>
                                    <p style="font-size: 0.9rem;">Nenhum usuário ativo.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($usuariosOnline as $onlineUser): ?>
                                    <div class="user-item">
                                        <div class="online-indicator"></div>
                                        <div class="user-info">
                                            <div class="user-name"><?= htmlspecialchars($onlineUser['nome']) ?></div>
                                            <div class="user-status"><?= htmlspecialchars(ucfirst($onlineUser['tipo_usuario'])) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card info-card">
                        <div class="card-header">
                            <h2><i class="fas fa-info-circle"></i> Avisos e Informações</h2>
                        </div>
                        <div class="card-body">
                            <p>Mantenha seus sistemas atualizados e faça backups regularmente para evitar perdas de dados.</p>
                            <p>Lembre-se de priorizar chamados com status "Alta" para garantir a continuidade dos serviços críticos.</p>
                        </div>
                    </div>
                </aside>
            </div>
        </main>
    </div>
    <script src="script.js"></script>
</body>
</html>