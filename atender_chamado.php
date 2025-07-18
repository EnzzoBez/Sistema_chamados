<?php
// atender_chamado.php - Atendimento de Chamado (para Técnicos)
session_start();
require 'conexao.php';

// Verifica se o usuário está logado e se é um técnico/admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo_usuario'] !== 'admin' && $_SESSION['tipo_usuario'] !== 'tecnico')) {
    header('Location: index.php'); // Redireciona para o login se não for técnico
    exit();
}

$chamado_id = (int)($_GET['id'] ?? 0);
$chamado = null;
$erro = '';
$sucesso = '';

$tecnico_logado_id = $_SESSION['usuario_id']; // ID do técnico logado

if ($chamado_id > 0) {
    // Busca os detalhes do chamado, incluindo o técnico atual
    $stmt = $pdo->prepare("SELECT ch.id, ch.titulo, ch.descricao, ch.prioridade, ch.status, ch.data_abertura, ch.data_atendimento, ch.resposta_ti, ch.data_resolucao,
                            us.nome AS usuario_nome, us.email AS usuario_email, se.nome AS setor_nome,
                            t.nome AS tecnico_nome, t.email AS tecnico_email, ch.tecnico_id
                            FROM chamados ch
                            JOIN usuarios us ON ch.usuario_id = us.id
                            JOIN setores se ON us.setor_id = se.id
                            LEFT JOIN usuarios t ON ch.tecnico_id = t.id -- Junta com a tabela de usuários novamente para pegar o nome do técnico
                            WHERE ch.id = ?");
    $stmt->execute([$chamado_id]);
    $chamado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$chamado) {
        $erro = 'Chamado não encontrado.';
    }
} else {
    $erro = 'ID do chamado inválido.';
}

// Processar ação de "Iniciar Atendimento" ou "Resolver"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $chamado) {
    if (isset($_POST['iniciar_atendimento'])) {
        // Permite iniciar atendimento apenas se status for 'Pendente' e nenhum técnico estiver atribuído
        if ($chamado['status'] === 'Pendente') {
            try {
                // Atribui o técnico logado ao chamado
                $upd = $pdo->prepare("UPDATE chamados SET status = 'Em Atendimento', data_atendimento = NOW(), tecnico_id = ? WHERE id = ?");
                if ($upd->execute([$tecnico_logado_id, $chamado_id])) {
                    $sucesso = 'Chamado marcado como "Em Atendimento".';
                    $chamado['status'] = 'Em Atendimento'; // Atualiza o status na variável para refletir na tela
                    $chamado['data_atendimento'] = date('Y-m-d H:i:s'); // Adiciona a data de atendimento
                    $chamado['tecnico_id'] = $tecnico_logado_id; // Atribui o ID do técnico
                    // Atualiza o nome do técnico para exibição imediata
                    $chamado['tecnico_nome'] = $_SESSION['usuario_nome'];
                } else {
                    $erro = 'Erro ao iniciar atendimento.';
                }
            } catch (PDOException $e) {
                $erro = "Erro no banco de dados: " . $e->getMessage();
            }
        } else {
            $erro = 'Este chamado já está em atendimento ou resolvido.';
        }
    } elseif (isset($_POST['resolver'])) {
        $resposta_ti = trim($_POST['resposta_ti'] ?? '');
        // Adicionamos a condição para permitir resolver apenas se o status não for "Resolvido"
        // e o técnico atribuído ao chamado for o técnico logado (para evitar que outro técnico resolva)
        if (empty($resposta_ti)) {
            $erro = 'Por favor, preencha a resposta do técnico para finalizar o chamado.';
        } elseif ($chamado['status'] !== 'Resolvido' && $chamado['tecnico_id'] == $tecnico_logado_id) {
            try {
                // Define tecnico_id como NULL ao resolver o chamado
                $upd = $pdo->prepare("UPDATE chamados SET status = 'Resolvido', resposta_ti = ?, data_resolucao = NOW(), tecnico_id = NULL WHERE id = ?");
                if ($upd->execute([$resposta_ti, $chamado_id])) {
                    $sucesso = 'Chamado resolvido com sucesso!';
                    $chamado['status'] = 'Resolvido'; // Atualiza o status na variável
                    $chamado['resposta_ti'] = $resposta_ti;
                    $chamado['data_resolucao'] = date('Y-m-d H:i:s');
                    $chamado['tecnico_id'] = NULL; // Remove a atribuição do técnico
                    $chamado['tecnico_nome'] = NULL;
                } else {
                    $erro = 'Erro ao resolver o chamado. Tente novamente.';
                }
            } catch (PDOException $e) {
                $erro = "Erro no banco de dados: " . $e->getMessage();
            }
        } else if ($chamado['tecnico_id'] != $tecnico_logado_id && $chamado['status'] !== 'Resolvido') {
             $erro = 'Este chamado está sendo atendido por outro técnico. Você não pode resolvê-lo.';
        } else {
            $erro = 'Este chamado já foi resolvido.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Belapedra - Atender Chamado #<?= $chamado_id ?></title>
        <link rel="icon" href="imagens/Logo_belapedra.ico" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Variáveis CSS para o Tema Claro */
:root {
    --primary-color: #007bff; /* Azul para ações principais */
    --secondary-color: #6c757d; /* Cinza para ações secundárias */
    --accent-color: #28a745; /* Verde para sucesso */
    --warning-color: #ffc107; /* Amarelo para atenção/início */
    --danger-color: #dc3545; /* Vermelho para erro */

    --background-light: #f8f9fa; /* Fundo principal claro */
    --background-card: #ffffff; /* Fundo dos cards */
    --background-hover: #e9ecef; /* Fundo ao passar o mouse */
    --border-color: #dee2e6; /* Cor da borda suave */
    --shadow-color: rgba(0, 0, 0, 0.1); /* Sombra leve */

    --text-primary: #212529; /* Cor principal do texto (quase preto) */
    --text-secondary: #6c757d; /* Cor secundária do texto (cinza) */
    --text-light: #ffffff; /* Texto claro para fundos escuros */

    --header-bg: #ffffff; /* Fundo do cabeçalho */
    --header-border: #e0e0e0; /* Borda do cabeçalho */

    --border-radius: 8px;
    --spacing-unit: 1rem;
    --transition-speed: 0.3s ease;
}

/* Base Styles */
body {
    font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    line-height: 1.6;
    color: var(--text-primary);
    background-color: var(--background-light);
    margin: 0;
    padding: 0;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

a {
    color: var(--primary-color);
    text-decoration: none;
    transition: color var(--transition-speed);
}

a:hover {
    color: #0056b3;
}

/* Utilities */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--spacing-unit);
}

.mt-3 {
    margin-top: 1.5rem !important;
}

.mb-3 {
    margin-bottom: 1.5rem !important;
}

/* Card Component */
.card {
    background-color: var(--background-card);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    box-shadow: 0 4px 12px var(--shadow-color);
    margin-bottom: 2rem;
    overflow: hidden;
}

.card-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background-color: var(--background-hover);
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-body {
    padding: 1.5rem;
}

/* Button Styles */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.25rem;
    border-radius: var(--border-radius);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all var(--transition-speed);
    text-align: center;
    white-space: nowrap;
    text-decoration: none; /* Ensure no underline */
}

.btn-icon {
    gap: 0.5rem; /* Espaçamento entre ícone e texto */
}

.btn-primary {
    background-color: var(--primary-color);
    color: var(--text-light);
}

.btn-primary:hover {
    background-color: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
}

.btn-secondary {
    background-color: var(--secondary-color);
    color: var(--text-light);
}

.btn-secondary:hover {
    background-color: #5a6268;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(108, 117, 125, 0.2);
}

.btn-success {
    background-color: var(--accent-color);
    color: var(--text-light);
}

.btn-success:hover {
    background-color: #218838;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.2);
}

.btn-warning {
    background-color: var(--warning-color);
    color: var(--text-primary); /* Texto escuro para botão amarelo */
}

.btn-warning:hover {
    background-color: #e0a800;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(255, 193, 7, 0.2);
}

.btn-danger {
    background-color: var(--danger-color);
    color: var(--text-light);
}

.btn-danger:hover {
    background-color: #c82333;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
}

.btn:disabled, .btn[disabled] {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

/* Form Styles */
.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text-primary);
}

.form-group label i {
    margin-right: 0.5rem;
    color: var(--primary-color);
}

.form-group input[type="text"],
.form-group input[type="password"],
.form-group input[type="email"],
.form-group input[type="number"],
.form-group select,
.form-group textarea {
    width: calc(100% - 24px); /* Full width minus padding */
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    background-color: var(--background-card);
    color: var(--text-primary);
    font-size: 1rem;
    transition: border-color var(--transition-speed), box-shadow var(--transition-speed);
}

.form-group input[type="text"]:focus,
.form-group input[type="password"]:focus,
.form-group input[type="email"]:focus,
.form-group input[type="number"]:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    outline: none;
}

.form-group textarea {
    resize: vertical; /* Allow vertical resizing */
    min-height: 100px;
}

/* Message Styles (Success, Error) */
.message {
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    border-radius: var(--border-radius);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border: 1px solid transparent;
}

.message.success {
    background-color: #d4edda;
    color: #155724;
    border-color: #c3e6cb;
}

.message.error {
    background-color: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

/* --- Specific Styles for atender_chamado.php --- */

.chamado-detalhes-container {
    max-width: 900px;
    margin: 2rem auto; /* Adjusted margin */
    background: var(--background-card);
    padding: 2.5rem 3rem; /* Adjusted padding */
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
    box-shadow: 0 8px 24px var(--shadow-color); /* Mais leve para tema claro */
    position: relative;
    z-index: 1;
}

.chamado-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.chamado-header h1 {
    font-size: 2.2rem; /* Aumentado um pouco */
    font-weight: 700; /* Levemente menos bold */
    color: var(--text-primary);
    line-height: 1.3;
}

.chamado-header h1 i {
    color: var(--primary-color);
    margin-right: 0.8rem;
}

/* Badges for Status and Priority */
.status-badge, .priority-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 50px; /* Mais arredondado */
    font-size: 0.85rem;
    font-weight: 700;
    margin-left: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.status-pendente { background-color: #ffc107; color: #664d03; } /* Warning */
.status-badge.status-em_atendimento { background-color: #0dcaf0; color: #084298; } /* Info/Cyan */
.status-badge.status-resolvido { background-color: #28a745; color: #ffffff; } /* Success */
.status-badge.status-cancelado { background-color: #6c757d; color: #ffffff; } /* Secondary */

.priority-badge.priority-baixa { background-color: #198754; color: #ffffff; } /* Verde escuro */
.priority-badge.priority-media { background-color: #ffc107; color: #212529; } /* Amarelo */
.priority-badge.priority-alta { background-color: #dc3545; color: #ffffff; } /* Vermelho */
.priority-badge.priority-urgente { background-color: #6f42c1; color: #ffffff; } /* Roxo (nova cor para urgente) */


.chamado-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.2rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.chamado-info-grid p {
    margin: 0;
    color: var(--text-secondary);
    font-size: 0.95rem;
}

.chamado-info-grid strong {
    color: var(--text-primary);
    font-weight: 600;
}

.chamado-section {
    background: var(--background-hover); /* Fundo sutil para seções */
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid var(--border-color);
}

.chamado-section h3 {
    color: var(--primary-color); /* Cor de destaque para títulos de seção */
    margin-bottom: 1rem;
    font-size: 1.3rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.chamado-section h3 i {
    color: var(--primary-color);
}

.chamado-section p {
    color: var(--text-primary);
    white-space: pre-wrap; /* Preserva quebras de linha */
    line-height: 1.7;
    font-size: 1rem;
}

/* Button groups */
.btn-group-horizontal {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    flex-wrap: wrap; /* Quebra linha em telas menores */
}

/* Back link */
.back-link {
    text-align: center;
    margin-top: 2rem;
}

.back-link .btn {
    padding: 0.8rem 1.5rem;
}

/* Responsiveness */
@media (max-width: 768px) {
    .chamado-detalhes-container {
        padding: 1.5rem 1.5rem;
    }

    .chamado-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .chamado-header h1 {
        font-size: 1.8rem;
    }

    .chamado-info-grid {
        grid-template-columns: 1fr;
    }

    .btn-group-horizontal {
        flex-direction: column;
    }

    .btn-group-horizontal .btn {
        width: 100%;
    }
}
    </style>
</head>
<body>
    <div class="chamado-detalhes-container">
        <?php if ($erro !== ''): ?>
            <div class="message error"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <?php if ($sucesso !== ''): ?>
            <div class="message success"><?= htmlspecialchars($sucesso) ?></div>
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
                        Nenhum
                    <?php endif; ?>
                </p>
            </div>

            <div class="chamado-section description-section">
                <h3><i class="fas fa-info-circle"></i> Descrição do Problema</h3>
                <p><?= nl2br(htmlspecialchars($chamado['descricao'])) ?></p>
            </div>

            <?php if ($chamado['status'] !== 'Resolvido'): ?>
                <div class="chamado-section action-section">
                    <h3><i class="fas fa-tools"></i> Ações do Técnico</h3>
                    <form method="POST">
                        <?php if ($chamado['status'] === 'Pendente'): ?>
                            <button type="submit" name="iniciar_atendimento" class="btn btn-primary btn-icon mb-3">
                                <i class="fas fa-play"></i> Iniciar Atendimento
                            </button>
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="resposta_ti"><i class="fas fa-comment"></i> Resposta do Técnico</label>
                            <textarea
                                name="resposta_ti"
                                id="resposta_ti"
                                placeholder="Descreva a solução aplicada ou os próximos passos..."
                                <?= ($chamado['status'] === 'Pendente' || ($chamado['tecnico_id'] !== null && $chamado['tecnico_id'] != $tecnico_logado_id)) ? 'disabled' : '' ?>
                                required
                            ><?= htmlspecialchars($chamado['resposta_ti'] ?? '') ?></textarea>
                        </div>

                        <div class="btn-group-horizontal">
                            <button type="submit" name="resolver" class="btn btn-success btn-icon" <?= ($chamado['status'] === 'Pendente' || ($chamado['tecnico_id'] !== null && $chamado['tecnico_id'] != $tecnico_logado_id)) ? 'disabled' : '' ?>>
                                <i class="fas fa-check"></i> Finalizar Chamado
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary btn-icon">
                                <i class="fas fa-arrow-left"></i> Voltar ao Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            <?php elseif (!empty($chamado['resposta_ti'])): ?>
                <div class="chamado-section response-section">
                    <h3><i class="fas fa-reply"></i> Resposta do Técnico</h3>
                    <p><?= nl2br(htmlspecialchars($chamado['resposta_ti'])) ?></p>
                </div>
                <div class="back-link mt-3">
                    <a href="dashboard.php" class="btn btn-secondary btn-icon"><i class="fas fa-arrow-left"></i> Voltar para o Dashboard</a>
                </div>
            <?php endif; ?>

            <?php if ($chamado['status'] === 'Resolvido' && empty($chamado['resposta_ti'])): ?>
                 <div class="back-link mt-3">
                    <a href="dashboard.php" class="btn btn-secondary btn-icon"><i class="fas fa-arrow-left"></i> Voltar para o Dashboard</a>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>