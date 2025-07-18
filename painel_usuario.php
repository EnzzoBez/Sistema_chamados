<?php
// painel_usuario.php - Painel do Usuário Comum
session_start();
require 'conexao.php';

// Verifica se o usuário está logado e se é um usuário comum
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] !== 'usuario_comum')) {
    header('Location: index.php'); // Redireciona para o login se não estiver logado ou não for usuário comum
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'];

// Marca usuário como online no logs_login
// Aumentar o tempo de inatividade para considerar offline (e.g., 5 minutos = 300 segundos)
$offline_threshold = 1000; // Segundos

$stmt = $pdo->prepare("REPLACE INTO logs_login (usuario_id, last_active) VALUES (?, NOW())");
$stmt->execute([$usuario_id]);

// Buscar chamados do usuário logado
$stmt = $pdo->prepare("SELECT id, titulo, descricao, prioridade, status, data_abertura, resposta_ti, data_resolucao
                                FROM chamados
                                WHERE usuario_id = ?
                                ORDER BY data_abertura DESC");
$stmt->execute([$usuario_id]);
$meus_chamados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Contar chamados por status para o usuário
$statusCountUsuario = ['Pendente' => 0, 'Em Atendimento' => 0, 'Resolvido' => 0];
foreach ($meus_chamados as $chamado) {
    if (isset($statusCountUsuario[$chamado['status']])) {
        $statusCountUsuario[$chamado['status']]++;
    }
}

// --- NOVO CÓDIGO PARA BUSCAR TÉCNICOS ONLINE E STATUS ---
$tecnicos_online_info = [];
try {
    // Buscar todos os usuários que são técnicos ou administradores
    $stmt_tecnicos = $pdo->prepare("SELECT u.id, u.nome, u.email, u.tipo_usuario
                                     FROM usuarios u
                                     WHERE u.tipo_usuario IN ('tecnico', 'admin')");
    $stmt_tecnicos->execute();
    $tecnicos = $stmt_tecnicos->fetchAll(PDO::FETCH_ASSOC);

    foreach ($tecnicos as $tecnico) {
        $is_online = false;
        $status_atendimento = 'Livre'; // Padrão
        $chamado_atribuido_id = null;

        // Verifica se está online consultando logs_login
        $stmt_online = $pdo->prepare("SELECT last_active FROM logs_login WHERE usuario_id = ?");
        $stmt_online->execute([$tecnico['id']]);
        $log_login = $stmt_online->fetch(PDO::FETCH_ASSOC);

        if ($log_login && (strtotime('now') - strtotime($log_login['last_active'])) < $offline_threshold) {
            $is_online = true;

            // Verifica se o técnico está atendendo algum chamado
            $stmt_atendimento = $pdo->prepare("SELECT id FROM chamados WHERE tecnico_id = ? AND status IN ('Em Atendimento') LIMIT 1");
            $stmt_atendimento->execute([$tecnico['id']]);
            $chamado_em_atendimento = $stmt_atendimento->fetch(PDO::FETCH_ASSOC);

            if ($chamado_em_atendimento) {
                $status_atendimento = 'Em Serviço (#' . $chamado_em_atendimento['id'] . ')';
                $chamado_atribuido_id = $chamado_em_atendimento['id'];
            }
        }

        $tecnicos_online_info[] = [
            'id' => $tecnico['id'],
            'nome' => $tecnico['nome'],
            'online' => $is_online,
            'status_atendimento' => $status_atendimento,
            'chamado_atribuido_id' => $chamado_atribuido_id
        ];
    }
} catch (PDOException $e) {
    // Em um ambiente de produção, logue o erro em vez de exibi-lo
    // error_log("Erro ao buscar status dos técnicos: " . $e->getMessage());
    // $tecnicos_online_info = []; // Limpa para evitar dados incorretos
}
// --- FIM DO NOVO CÓDIGO ---
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Belapedra - Meu Painel</title>
    <link rel="icon" href="imagens/Logo_belapedra.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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
    max-width: 1200px; /* Max width for content */
    background-color: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    overflow: hidden;
    margin: 20px; /* Consistent margin */
}

/* Header */
.header {
    background-color: #3498db; /* Blue for user panel header */
    color: #ffffff;
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 5px solid #2980b9; /* Darker blue accent */
    flex-wrap: wrap;
    gap: 15px;
}

.header h1 {
    margin: 0;
    font-size: 1.8em; /* Adjusted to match admin dashboard */
    display: flex;
    align-items: center;
    gap: 10px;
}

.header h1 .fas {
    font-size: 1.2em;
    color: #ecf0f1;
}

.header-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

/* Buttons (reused from admin dashboard for consistency) */
.btn {
    background-color: #2ecc71; /* Green for primary action */
    color: #ffffff;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    transition: background-color 0.3s ease, transform 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95em;
}

.btn:hover {
    background-color: #27ae60; /* Darker green */
    transform: translateY(-2px);
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
    font-size: 0.95em;
}

.logout-btn:hover {
    background-color: #c0392b; /* Darker red */
    transform: translateY(-2px);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    padding: 30px 30px 0; /* Padding top and sides */
}

.stat-card {
    background: linear-gradient(135deg, #e0f7f7, #c1f0eb); /* Light gradient */
    border-left: 8px solid #2ecc71; /* Green accent */
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    padding: 25px 30px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
}

.stat-card h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 1.3em;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.stat-card h3 .fas {
    color: #2ecc71; /* Green icon */
    font-size: 1.1em;
}

.stat-value {
    font-size: 2.8em;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 10px;
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

/* Section Card (for "Meus Chamados") */
.section-card {
    background-color: #ffffff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    margin: 30px; /* Margin for separation */
    padding: 25px;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.section-header h2 {
    margin: 0;
    font-size: 1.4em;
    color: #34495e;
}

.section-header .fas {
    color: #3498db;
    font-size: 1.1em;
}

.section-content {
    font-size: 0.95em;
    color: #555;
}

/* Table Styling */
.table-container {
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

/* Priority and Status Badges (reused for consistency) */
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

.priority-baixa { background-color: #2ecc71; /* Green */ }
.priority-media { background-color: #f39c12; /* Orange */ }
.priority-alta { background-color: #e74c3c; /* Red */ }

.status-pendente { background-color: #3498db; /* Blue */ }
.status-em_atendimento { background-color: #f39c12; /* Orange */ }
.status-resolvido { background-color: #2ecc71; /* Green */ }

/* Action button inside table */
table .btn {
    padding: 6px 10px;
    font-size: 0.85em;
    background-color: #7f8c8d; /* Secondary button style */
    color: #fff;
}

table .btn:hover {
    background-color: #6c7a89;
    transform: translateY(-1px);
}

/* Empty State Styling (reused) */
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

/* Specific styling for "Resposta TI" column */
td i.fa-check-circle {
    color: #2ecc71; /* Green for resolved */
    margin-right: 5px;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .header {
        flex-direction: column;
        align-items: flex-start;
        padding: 15px 20px;
    }

    .header-actions {
        margin-top: 15px;
        justify-content: flex-start;
        width: 100%;
    }

    .header h1 {
        font-size: 1.5em;
    }

    .btn, .logout-btn {
        padding: 8px 15px;
        font-size: 0.9em;
    }

    .stats-grid {
        grid-template-columns: 1fr;
        padding: 20px 20px 0;
    }

    .stat-card {
        padding: 20px;
    }

    .stat-card h3 {
        font-size: 1.2em;
    }

    .stat-value {
        font-size: 2.5em;
    }

    .section-card {
        margin: 20px;
        padding: 20px;
    }

    .section-header h2 {
        font-size: 1.2em;
    }

    table th, table td {
        padding: 10px 12px;
    }

    table .btn {
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
        padding: 15px;
    }

    .header-actions {
        flex-direction: column;
        gap: 10px;
    }

    .btn, .logout-btn {
        width: 100%;
        justify-content: center;
    }

    .stats-grid {
        padding: 15px 15px 0;
    }

    .stat-card {
        padding: 15px;
    }

    .section-card {
        margin: 15px;
        padding: 15px;
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
}
    </style>
</head>
<body>
    <div class="dashboard-container">
        <header class="header">
            <h1><i class="fas fa-user-circle"></i> Olá, <?= htmlspecialchars($usuario_nome) ?>!</h1>
            <div class="header-actions">
                <a href="abrir_chamado.php" class="btn">
                    <i class="fas fa-plus-circle"></i> Abrir Novo Chamado
                </a>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><i class="fas fa-ticket-alt"></i> Chamados Pendentes</h3>
                <div class="stat-value"><?= $statusCountUsuario['Pendente'] ?></div>
                <ul class="stat-list">
                    <li>
                        <span>Em Atendimento</span>
                        <span class="value"><?= $statusCountUsuario['Em Atendimento'] ?></span>
                    </li>
                    <li>
                        <span>Resolvidos</span>
                        <span class="value"><?= $statusCountUsuario['Resolvido'] ?></span>
                    </li>
                </ul>
            </div>

            <div class="stat-card">
                <h3><i class="fas fa-history"></i> Histórico Recente</h3>
                <div class="stat-value"><?= count($meus_chamados) ?></div>
                <ul class="stat-list">
                    <li>
                        <span>Último Chamado</span>
                        <span class="value">
                            <?php if (!empty($meus_chamados)): ?>
                                #<?= $meus_chamados[0]['id'] ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </span>
                    </li>
                    <li>
                        <span>Abertos Hoje</span>
                        <span class="value">
                            <?php
                            $chamadosHoje = 0;
                            foreach ($meus_chamados as $chamado) {
                                if (date('Y-m-d', strtotime($chamado['data_abertura'])) === date('Y-m-d')) {
                                    $chamadosHoje++;
                                }
                            }
                            echo $chamadosHoje;
                            ?>
                        </span>
                    </li>
                </ul>
            </div>

            <div class="stat-card">
                <h3><i class="fas fa-headset"></i> Técnicos Online</h3>
                <div class="stat-value">
                    <?php
                        $online_count = 0;
                        foreach ($tecnicos_online_info as $tech) {
                            if ($tech['online']) {
                                $online_count++;
                            }
                        }
                        echo $online_count;
                    ?>
                </div>
                <ul class="stat-list">
                    <?php if (!empty($tecnicos_online_info)): ?>
                        <?php foreach ($tecnicos_online_info as $tecnico): ?>
                            <li>
                                <span>
                                    <?= htmlspecialchars($tecnico['nome']) ?>
                                    <?php if ($tecnico['online']): ?>
                                        <i class="fas fa-circle online-status-indicator" title="Online" style="color: #28a745; font-size: 0.7em; margin-left: 5px;"></i>
                                    <?php else: ?>
                                        <i class="fas fa-circle offline-status-indicator" title="Offline" style="color: #6c757d; font-size: 0.7em; margin-left: 5px;"></i>
                                    <?php endif; ?>
                                </span>
                                <span class="value">
                                    <?= htmlspecialchars($tecnico['status_atendimento']) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>Nenhum técnico cadastrado ou online.</li>
                    <?php endif; ?>
                </ul>
            </div>
            </div>

        <div class="section-card">
            <div class="section-header">
                <h2><i class="fas fa-clipboard-list"></i> Meus Chamados</h2>
            </div>
            <div class="section-content">
                <?php if (count($meus_chamados) === 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h3>Você não tem nenhum chamado aberto.</h3>
                        <p>Clique em "Abrir Novo Chamado" para começar.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Título</th>
                                    <th>Prioridade</th>
                                    <th>Status</th>
                                    <th>Abertura</th>
                                    <th>Resposta TI</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($meus_chamados as $chamado): ?>
                                    <tr>
                                        <td><strong>#<?= $chamado['id'] ?></strong></td>
                                        <td><?= htmlspecialchars($chamado['titulo']) ?></td>
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
                                        <td>
                                            <?php if (!empty($chamado['resposta_ti'])): ?>
                                                <i class="fas fa-check-circle" style="color: #2ecc71;"></i> Resolvido
                                            <?php else: ?>
                                                Aguardando...
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="visualizar_chamado.php?id=<?= $chamado['id'] ?>" class="btn view-btn">
                                                <i class="fas fa-eye"></i> Visualizar
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
    </div>
</body>
</html>