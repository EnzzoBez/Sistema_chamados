<?php
// visualizar_chamado.php - Visualização de Chamado (para Usuários Comuns)
session_start();
require 'conexao.php';

// Verifica se o usuário está logado e se é um usuário comum
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'usuario_comum') {
    header('Location: index.php'); // Redireciona para o login
    exit();
}

$chamado_id = (int)($_GET['id'] ?? 0);
$chamado = null;
$erro = '';

if ($chamado_id > 0) {
    // Busca os detalhes do chamado, garantindo que seja do usuário logado
    $stmt = $pdo->prepare("SELECT ch.id, ch.titulo, ch.descricao, ch.prioridade, ch.status, ch.data_abertura, ch.data_atendimento, ch.resposta_ti, ch.data_resolucao,
                            us.nome AS usuario_nome, us.email AS usuario_email, se.nome AS setor_nome,
                            t.nome AS tecnico_nome, t.email AS tecnico_email
                            FROM chamados ch
                            JOIN usuarios us ON ch.usuario_id = us.id
                            JOIN setores se ON us.setor_id = se.id
                            LEFT JOIN usuarios t ON ch.tecnico_id = t.id -- Junta com a tabela de usuários para pegar o nome do técnico
                            WHERE ch.id = ? AND ch.usuario_id = ?"); // IMPORTANTE: Apenas chamados do usuário logado
    $stmt->execute([$chamado_id, $_SESSION['usuario_id']]);
    $chamado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$chamado) {
        $erro = 'Chamado não encontrado ou você não tem permissão para visualizá-lo.';
    }
} else {
    $erro = 'ID do chamado inválido.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Belapedra - Visualizar Chamado #<?= $chamado_id ?></title>
        <link rel="icon" href="imagens/Logo_belapedra.ico" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Existing styles... */

/* Adicionado para o card de Técnicos Online */
.stat-card .online-status-indicator {
    color: #28a745; /* Green for online */
    font-size: 0.7em;
    margin-left: 5px;
}

.stat-card .offline-status-indicator {
    color: #6c757d; /* Gray for offline */
    font-size: 0.7em;
    margin-left: 5px;
}

/* Novo estilo para o botão de visualizar na tabela */
table .btn.view-btn {
    background-color: #3498db; /* Azul para visualizar */
}

table .btn.view-btn:hover {
    background-color: #2980b9;
}
    </style>
</head>
<body>
    <div class="chamado-detalhes-container">
        <?php if ($erro !== ''): ?>
            <div class="message error"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($chamado): ?>
            <div class="chamado-header">
                <h1>Chamado #<?= $chamado['id'] ?>: <?= htmlspecialchars($chamado['titulo']) ?></h1>
                <div>
                    <span class="priority-badge priority-<?= strtolower($chamado['prioridade']) ?>">
                        <?= htmlspecialchars($chamado['prioridade']) ?>
                    </span>
                    <span class="status-badge status-<?= str_replace(' ', '_', strtolower($chamado['status'])) ?>">
                        <?= htmlspecialchars($chamado['status']) ?>
                    </span>
                </div>
            </div>

            <div class="chamado-info-grid">
                <p><strong>Usuário:</strong> <?= htmlspecialchars($chamado['usuario_nome']) ?> (<?= htmlspecialchars($chamado['usuario_email']) ?>)</p>
                <p><strong>Setor:</strong> <?= htmlspecialchars($chamado['setor_nome']) ?></p>
                <p><strong>Abertura:</strong> <?= date('d/m/Y H:i', strtotime($chamado['data_abertura'])) ?></p>
                <?php if ($chamado['data_atendimento']): ?>
                    <p><strong>Início Atendimento:</strong> <?= date('d/m/Y H:i', strtotime($chamado['data_atendimento'])) ?></p>
                <?php endif; ?>
                <?php if ($chamado['data_resolucao']): ?>
                    <p><strong>Resolução:</strong> <?= date('d/m/Y H:i', strtotime($chamado['data_resolucao'])) ?></p>
                <?php endif; ?>
                <p><strong>Técnico Atribuído:</strong>
                    <?php if ($chamado['tecnico_nome']): ?>
                        <?= htmlspecialchars($chamado['tecnico_nome']) ?>
                    <?php else: ?>
                        Nenhum (<?= ($chamado['status'] === 'Pendente') ? 'Aguardando Atribuição' : 'Não Aplicável' ?>)
                    <?php endif; ?>
                </p>
            </div>

            <div class="chamado-section description-section">
                <h3><i class="fas fa-info-circle"></i> Descrição do Problema</h3>
                <p><?= nl2br(htmlspecialchars($chamado['descricao'])) ?></p>
            </div>

            <?php if (!empty($chamado['resposta_ti'])): ?>
                <div class="chamado-section response-section">
                    <h3><i class="fas fa-reply"></i> Resposta do Técnico</h3>
                    <p><?= nl2br(htmlspecialchars($chamado['resposta_ti'])) ?></p>
                </div>
            <?php else: ?>
                 <div class="chamado-section response-section">
                    <h3><i class="fas fa-reply"></i> Resposta do Técnico</h3>
                    <p>Aguardando resposta do técnico.</p>
                </div>
            <?php endif; ?>

            <div class="back-link mt-3">
                <a href="painel_usuario.php" class="btn btn-secondary btn-icon">
                    <i class="fas fa-arrow-left"></i> Voltar para o Painel
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>