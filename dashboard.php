<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Tratar ação "Atender" (enviada via POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['iniciar_atendimento'], $_POST['chamado_id'])) {
    $chamado_id = intval($_POST['chamado_id']);

    // Atualiza status do técnico para 'ocupado'
    $stmt = $pdo->prepare("UPDATE usuarios SET status = 'ocupado' WHERE id = ?");
    $stmt->execute([$usuario_id]);

    // Opcional: atualiza status do chamado para 'em andamento'
    $stmt = $pdo->prepare("UPDATE chamados SET status = 'em andamento', tecnico_id = ? WHERE id = ?");
    $stmt->execute([$usuario_id, $chamado_id]);

    // Redireciona para evitar reenvio POST
    header('Location: dashboard.php');
    exit;
}

// Defina aqui a senha e usuário admin (você pode depois expandir para tabela usuários com perfil)
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'admin123'); // Troque para uma senha forte

// Login simples para o painel TI
if (!isset($_SESSION['ti_logado'])) {
    $erro = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = $_POST['usuario'] ?? '';
        $pass = $_POST['senha'] ?? '';

        if ($user === ADMIN_USER && $pass === ADMIN_PASS) {
            $_SESSION['ti_logado'] = true;
        } else {
            $erro = 'Usuário ou senha inválidos.';
        }
    }

    if (!isset($_SESSION['ti_logado'])) {
        // Form login TI
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1" />
            <title>Bela Pedra - Login Técnico</title>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
                
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: 'Inter', sans-serif;
                    background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 50%, #0f0f0f 100%);
                    color: #ffffff;
                    min-height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    padding: 1rem;
                    position: relative;
                    overflow: hidden;
                }

                body::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: radial-gradient(circle at 20% 80%, rgba(0, 255, 255, 0.1) 0%, transparent 50%),
                                radial-gradient(circle at 80% 20%, rgba(138, 43, 226, 0.1) 0%, transparent 50%);
                    pointer-events: none;
                }

                .login-container {
                    background: rgba(15, 15, 15, 0.95);
                    padding: 3rem;
                    border-radius: 24px;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    backdrop-filter: blur(20px);
                    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5),
                                0 0 100px rgba(0, 255, 255, 0.1);
                    width: 100%;
                    max-width: 450px;
                    position: relative;
                    z-index: 1;
                }

                .login-container::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: linear-gradient(45deg, transparent, rgba(0, 255, 255, 0.05), transparent);
                    border-radius: 24px;
                    pointer-events: none;
                }

                .logo {
                    text-align: center;
                    margin-bottom: 2.5rem;
                }

                .logo h1 {
                    font-size: 2.5rem;
                    font-weight: 800;
                    background: linear-gradient(135deg, #00ffff, #8a2be2);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                    margin-bottom: 0.5rem;
                    letter-spacing: -1px;
                }

                .logo p {
                    color: #888;
                    font-size: 0.9rem;
                    font-weight: 500;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                }

                .form-group {
                    margin-bottom: 1.5rem;
                }

                label {
                    display: block;
                    margin-bottom: 0.5rem;
                    font-weight: 600;
                    font-size: 0.9rem;
                    color: #ccc;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }

                input[type="text"],
                input[type="password"] {
                    width: 100%;
                    padding: 1rem 1.25rem;
                    border-radius: 12px;
                    border: 2px solid rgba(255, 255, 255, 0.1);
                    background: rgba(255, 255, 255, 0.05);
                    color: #ffffff;
                    font-size: 1rem;
                    font-weight: 500;
                    transition: all 0.3s ease;
                    backdrop-filter: blur(10px);
                }

                input[type="text"]:focus,
                input[type="password"]:focus {
                    outline: none;
                    border-color: #00ffff;
                    box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
                    background: rgba(255, 255, 255, 0.08);
                }

                button {
                    width: 100%;
                    padding: 1rem;
                    background: linear-gradient(135deg, #00ffff, #8a2be2);
                    color: #ffffff;
                    border: none;
                    border-radius: 12px;
                    font-weight: 700;
                    font-size: 1rem;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    position: relative;
                    overflow: hidden;
                }

                button::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
                    transition: left 0.5s;
                }

                button:hover::before {
                    left: 100%;
                }

                button:hover {
                    box-shadow: 0 10px 30px rgba(0, 255, 255, 0.4);
                    transform: translateY(-2px);
                }

                .error-msg {
                    background: linear-gradient(135deg, #ff4444, #cc0000);
                    color: #ffffff;
                    padding: 1rem;
                    border-radius: 12px;
                    margin-bottom: 1.5rem;
                    font-weight: 600;
                    text-align: center;
                    font-size: 0.9rem;
                    border: 1px solid rgba(255, 255, 255, 0.1);
                }
            </style>
        </head>
        <body>
            <div class="login-container">
                <div class="logo">
                    <h1>Bela Pedra</h1>
                    <p>Painel Técnico</p>
                </div>
                <?php if ($erro !== ''): ?>
                    <div class="error-msg"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="usuario">Usuário</label>
                        <input type="text" id="usuario" name="usuario" required autofocus autocomplete="username" />
                    </div>

                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" required autocomplete="current-password" />
                    </div>

                    <button type="submit">Entrar</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// --- A PARTIR DAQUI O USUÁRIO JÁ ESTÁ LOGADO ---

// Marca usuário TI (admin id=1) como online no logs_login
$usuarioTIid = 1; // ajuste se necessário
$stmt = $pdo->prepare("REPLACE INTO logs_login (usuario_id, last_active) VALUES (?, NOW())");
$stmt->execute([$usuarioTIid]);

// Consulta quantidade de usuários online (últimos 3 minutos)
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT usuario_id) AS online FROM logs_login WHERE last_active >= (NOW() - INTERVAL 3 MINUTE)");
$stmt->execute();
$online = (int)$stmt->fetchColumn();

// Contar chamados por status
$statusCount = ['Pendente' => 0, 'Em Atendimento' => 0, 'Resolvido' => 0];
$stmt = $pdo->query("SELECT status, COUNT(*) AS total FROM chamados GROUP BY status");
foreach ($stmt->fetchAll() as $row) {
    $statusCount[$row['status']] = (int)$row['total'];
}

// Contar chamados por setor
$setoresChamados = [];
$sql = "SELECT se.nome AS setor_nome, COUNT(ch.id) AS total FROM setores se 
        LEFT JOIN usuarios us ON us.setor_id = se.id
        LEFT JOIN chamados ch ON ch.usuario_id = us.id
        GROUP BY se.nome ORDER BY se.nome";
$stmt = $pdo->query($sql);
foreach ($stmt->fetchAll() as $row) {
    $setoresChamados[$row['setor_nome']] = (int)$row['total'];
}

// Buscar chamados pendentes para fila
$stmt = $pdo->prepare("SELECT ch.id, ch.titulo, ch.prioridade, us.nome AS usuario_nome, se.nome AS setor_nome, ch.data_abertura
    FROM chamados ch 
    JOIN usuarios us ON ch.usuario_id = us.id 
    JOIN setores se ON us.setor_id = se.id 
    WHERE ch.status = 'Pendente' ORDER BY FIELD(ch.prioridade, 'Alta', 'Média', 'Baixa'), ch.data_abertura ASC");
$stmt->execute();
$fila = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar chamados em atendimento para responder
$stmt = $pdo->prepare("SELECT ch.id, ch.titulo, ch.descricao, us.nome AS usuario_nome, ch.data_abertura FROM chamados ch JOIN usuarios us ON ch.usuario_id = us.id WHERE ch.status = 'Em Atendimento' ORDER BY ch.data_abertura");
$stmt->execute();
$em_atendimento = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar estatísticas adicionais
$stmt = $pdo->query("SELECT DATE(data_abertura) as data, COUNT(*) as total FROM chamados WHERE data_abertura >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(data_abertura) ORDER BY data DESC");
$stats_semana = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar top usuários com mais chamados
$stmt = $pdo->query("SELECT u.nome, COUNT(c.id) as total FROM usuarios u LEFT JOIN chamados c ON u.id = c.usuario_id GROUP BY u.id ORDER BY total DESC LIMIT 5");
$top_usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar ações do painel (iniciar atendimento, resolver)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['iniciar_atendimento'])) {
        $chamado_id = (int)($_POST['chamado_id'] ?? 0);
        if ($chamado_id > 0) {
            $upd = $pdo->prepare("UPDATE chamados SET status = 'Em Atendimento' WHERE id = ? AND status = 'Pendente'");
            $upd->execute([$chamado_id]);
        }
        header('Location: dashboard.php');
        exit;
    }
    // Supondo que você já atualizou o status do chamado para 'Em andamento'
    $stmt = $pdo->prepare("UPDATE usuarios SET status = 'livre' WHERE id = ?");
  $stmt->execute([$usuario_id]);



    if (isset($_POST['resolver'])) {
        $chamado_id = (int)($_POST['chamado_id'] ?? 0);
        $resposta_ti = trim($_POST['resposta_ti'] ?? '');
        if ($chamado_id > 0 && $resposta_ti !== '') {
            $upd = $pdo->prepare("UPDATE chamados SET status = 'Resolvido', resposta_ti = ?, data_resolucao = NOW() WHERE id = ?");
            $upd->execute([$resposta_ti, $chamado_id]);
        }
        header('Location: dashboard.php');
        exit;
    }
}

$sql_online_setores = "
    SELECT DISTINCT se.id, se.nome, COUNT(ll.usuario_id) as usuarios_online
    FROM logs_login ll
    JOIN usuarios us ON ll.usuario_id = us.id
    JOIN setores se ON us.setor_id = se.id
    WHERE ll.last_active >= (NOW() - INTERVAL 3 MINUTE)
    GROUP BY se.id, se.nome
    ORDER BY usuarios_online DESC, se.nome
";
$stmt = $pdo->prepare($sql_online_setores);
$stmt->execute();
$setores_online = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular tempo médio de resolução
$stmt = $pdo->query("SELECT AVG(TIMESTAMPDIFF(MINUTE, data_abertura, data_resolucao)) as tempo_medio FROM chamados WHERE status = 'Resolvido' AND data_resolucao IS NOT NULL");
$tempo_medio = $stmt->fetchColumn();
$tempo_medio = $tempo_medio ? round($tempo_medio, 0) : 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Bela Pedra - Dashboard de Chamados</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');

:root {
  --primary-bg: #0a0a0a;
  --secondary-bg: #111111;
  --card-bg: #1a1a1a;
  --border-color: rgba(255, 255, 255, 0.1);
  --text-primary: #ffffff;
  --text-secondary: #b0b0b0;
  --accent-cyan: #00ffff;
  --accent-purple: #8a2be2;
  --accent-green: #00ff88;
  --accent-orange: #ff8800;
  --accent-red: #ff4444;
  --gradient-primary: linear-gradient(135deg, var(--accent-cyan), var(--accent-purple));
  --gradient-success: linear-gradient(135deg, #00ff88, #00cc66);
  --gradient-warning: linear-gradient(135deg, #ff8800, #ff6600);
  --gradient-danger: linear-gradient(135deg, #ff4444, #cc0000);
  --shadow-glow: 0 0 30px rgba(0, 255, 255, 0.2);
  --shadow-card: 0 10px 30px rgba(0, 0, 0, 0.3);
  --border-radius: 16px;
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Inter', sans-serif;
  background: var(--primary-bg);
  color: var(--text-primary);
  min-height: 100vh;
  line-height: 1.6;
  overflow-x: hidden;
}

body::before {
  content: '';
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: 
    radial-gradient(circle at 20% 80%, rgba(0, 255, 255, 0.1) 0%, transparent 50%),
    radial-gradient(circle at 80% 20%, rgba(138, 43, 226, 0.1) 0%, transparent 50%),
    radial-gradient(circle at 40% 40%, rgba(0, 255, 136, 0.05) 0%, transparent 50%);
  pointer-events: none;
  z-index: 0;
}

.dashboard-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
  position: relative;
  z-index: 1;
}

/* Header */
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 3rem;
  padding: 1.5rem 2rem;
  background: rgba(26, 26, 26, 0.8);
  backdrop-filter: blur(20px);
  border-radius: var(--border-radius);
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-card);
}

.header h1 {
  font-size: 2.5rem;
  font-weight: 800;
  background: var(--gradient-primary);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -1px;
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.time-display {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-secondary);
  font-family: 'Courier New', monospace;
}

.logout-btn {
  background: var(--gradient-danger);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: var(--border-radius);
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.logout-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(255, 68, 68, 0.4);
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.stat-card {
  background: rgba(26, 26, 26, 0.8);
  backdrop-filter: blur(20px);
  border-radius: var(--border-radius);
  padding: 2rem;
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-card);
  transition: var(--transition);
  position: relative;
  overflow: hidden;
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: var(--gradient-primary);
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-glow);
}

.stat-card h3 {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-secondary);
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.stat-value {
  font-size: 3rem;
  font-weight: 800;
  background: var(--gradient-primary);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1;
  margin-bottom: 1rem;
}

.stat-list {
  list-style: none;
}

.stat-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  color: var(--text-secondary);
}

.stat-list li:last-child {
  border-bottom: none;
}

.stat-list .value {
  font-weight: 700;
  color: var(--accent-cyan);
}

/* Main Content Grid */
.main-grid {
  display: grid;
  grid-template-columns: 1fr 350px;
  gap: 2rem;
  margin-bottom: 3rem;
}

.main-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.sidebar {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Section Cards */
.section-card {
  background: rgba(26, 26, 26, 0.8);
  backdrop-filter: blur(20px);
  border-radius: var(--border-radius);
  border: 1px solid var(--border-color);
  box-shadow: var(--shadow-card);
  overflow: hidden;
}

.section-header {
  padding: 1.5rem 2rem;
  background: rgba(255, 255, 255, 0.05);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.section-header h2 {
  font-size: 1.3rem;
  font-weight: 700;
  color: var(--text-primary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-content {
  padding: 1.5rem 2rem;
}

/* Table Styles */
.table-container {
  overflow-x: auto;
  border-radius: var(--border-radius);
  background: rgba(0, 0, 0, 0.2);
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

thead {
  background: rgba(255, 255, 255, 0.1);
}

th, td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

th {
  font-weight: 600;
  color: var(--accent-cyan);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-size: 0.8rem;
}

tr:hover {
  background: rgba(255, 255, 255, 0.05);
}

.priority-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.priority-alta { background: var(--gradient-danger); }
.priority-media { background: var(--gradient-warning); }
.priority-baixa { background: var(--gradient-success); }

/* Buttons */
.btn {
  background: var(--gradient-primary);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: var(--border-radius);
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(0, 255, 255, 0.4);
}

.btn-success {
  background: var(--gradient-success);
}

.btn-success:hover {
  box-shadow: 0 10px 30px rgba(0, 255, 136, 0.4);
}

/* Forms */
.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-size: 0.8rem;
}

.form-group textarea {
  width: 100%;
  min-height: 120px;
  padding: 1rem;
  border: 2px solid var(--border-color);
  border-radius: var(--border-radius);
  background: rgba(255, 255, 255, 0.05);
  color: var(--text-primary);
  font-family: inherit;
  font-size: 0.9rem;
  resize: vertical;
  transition: var(--transition);
}

.form-group textarea:focus {
  outline: none;
  border-color: var(--accent-cyan);
  box-shadow: 0 0 20px rgba(0, 255, 255, 0.2);
}

/* Online Users */
.online-users {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.user-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.05);
  border-radius: var(--border-radius);
  border: 1px solid var(--border-color);
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: var(--gradient-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: white;
}

.user-info {
  flex: 1;
}

.user-name {
  font-weight: 600;
  color: var(--text-primary);
}

.user-status {
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.online-indicator {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: var(--accent-green);
  box-shadow: 0 0 10px var(--accent-green);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { opacity: 1; }
  50% { opacity: 0.5; }
  100% { opacity: 1; }
}

/* Quick Actions */
.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.quick-action {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.05);
  border-radius: var(--border-radius);
  border: 1px solid var(--border-color);
  text-decoration: none;
  color: var(--text-primary);
  transition: var(--transition);
}

.quick-action:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: translateX(5px);
}

.quick-action i {
  font-size: 1.2rem;
  color: var(--accent-cyan);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  color: var(--text-secondary);
}

.empty-state i {
  font-size: 3rem;
  margin-bottom: 1rem;
  color: var(--accent-cyan);
}

.empty-state h3 {
  font-size: 1.2rem;
  margin-bottom: 0.5rem;
}

/* Responsive */
@media (max-width: 1024px) {
  .main-grid {
    grid-template-columns: 1fr;
  }
  
  .sidebar {
    order: -1;
  }
}

@media (max-width: 768px) {
  .dashboard-container {
    padding: 1rem;
  }
  
  .header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .header h1 {
    font-size: 2rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .stat-value {
    font-size: 2rem;
  }
}

/* Scrollbar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: var(--secondary-bg);
}

::-webkit-scrollbar-thumb {
  background: var(--accent-cyan);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: var(--accent-purple);
}
</style>
</head>
<body>
<div class="dashboard-container">
  
  <!-- Header -->
  <header class="header">
    <h1><i class="fas fa-cogs"></i> Bela Pedra - Dashboard de Chamados</h1>
    <div class="header-actions">
      <div class="time-display" id="current-time"></div>
      <a href="logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> Sair
      </a>
    </div>
  </header>

  <!-- Stats Grid -->
  <div class="stats-grid">
    <div class="stat-card">
      <h3><i class="fas fa-users"></i> Usuários Online</h3>
      <div class="stat-value"><?= $online ?></div>
      <ul class="stat-list">
        <li>
          <span>Setores Ativos</span>
          <span class="value"><?= count($setores_online) ?></span>
        </li>
        <li>
          <span>Tempo Médio Resolução</span>
          <span class="value"><?= $tempo_medio ?>min</span>
        </li>
      </ul>
    </div>

    <div class="stat-card">
      <h3><i class="fas fa-ticket-alt"></i> Chamados Pendentes</h3>
      <div class="stat-value"><?= $statusCount['Pendente'] ?></div>
      <ul class="stat-list">
        <li>
          <span>Em Atendimento</span>
          <span class="value"><?= $statusCount['Em Atendimento'] ?></span>
        </li>
        <li>
          <span>Resolvidos</span>
          <span class="value"><?= $statusCount['Resolvido'] ?></span>
        </li>
      </ul>
    </div>

    <div class="stat-card">
      <h3><i class="fas fa-chart-line"></i> Desempenho</h3>
      <div class="stat-value"><?= count($stats_semana) ?></div>
      <ul class="stat-list">
        <li>
          <span>Chamados Hoje</span>
          <span class="value"><?= isset($stats_semana[0]) ? $stats_semana[0]['total'] : 0 ?></span>
        </li>
        <li>
          <span>Taxa Resolução</span>
          <span class="value"><?= $statusCount['Resolvido'] > 0 ? round(($statusCount['Resolvido'] / array_sum($statusCount)) * 100) : 0 ?>%</span>
        </li>
      </ul>
    </div>

    <div class="stat-card">
      <h3><i class="fas fa-building"></i> Setores</h3>
      <div class="stat-value"><?= count($setoresChamados) ?></div>
      <ul class="stat-list">
        <?php 
        $topSetores = array_slice($setoresChamados, 0, 2, true);
        foreach($topSetores as $setor => $total): 
        ?>
        <li>
          <span><?= htmlspecialchars($setor) ?></span>
          <span class="value"><?= $total ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <!-- Main Content Grid -->
  <div class="main-grid">
    <div class="main-content">
      
      <!-- Fila de Atendimento -->
      <div class="section-card">
        <div class="section-header">
          <h2><i class="fas fa-clock"></i> Fila de Atendimento</h2>
          <span class="badge"><?= count($fila) ?> pendente(s)</span>
        </div>
        <div class="section-content">
          <?php if (count($fila) === 0): ?>
            <div class="empty-state">
              <i class="fas fa-check-circle"></i>
              <h3>Nenhum chamado pendente</h3>
              <p>Todos os chamados estão sendo atendidos ou foram resolvidos.</p>
            </div>
          <?php else: ?>
            <div class="table-container">
              <table>
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Prioridade</th>
                    <th>Usuário</th>
                    <th>Setor</th>
                    <th>Abertura</th>
                    <th>Ações</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($fila as $chamado): ?>
                  <tr>
                    <td><strong>#<?= $chamado['id'] ?></strong></td>
                    <td><?= htmlspecialchars($chamado['titulo']) ?></td>
                    <td>
                      <span class="priority-badge priority-<?= strtolower($chamado['prioridade']) ?>">
                        <?= htmlspecialchars($chamado['prioridade']) ?>
                      </span>
                    </td>
                    <td><?= htmlspecialchars($chamado['usuario_nome']) ?></td>
                    <td><?= htmlspecialchars($chamado['setor_nome']) ?></td>
                    <td><?= date('d/m H:i', strtotime($chamado['data_abertura'])) ?></td>
                    <td>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="chamado_id" value="<?= $chamado['id'] ?>" />
                        <button type="submit" name="iniciar_atendimento" class="btn">
                          <i class="fas fa-play"></i> Atender
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Chamados em Atendimento -->
      <?php if (count($em_atendimento) > 0): ?>
        <div class="section-card">
          <div class="section-header">
            <h2><i class="fas fa-tools"></i> Finalizar Chamados</h2>
            <span class="badge"><?= count($em_atendimento) ?> em atendimento</span>
          </div>
          <div class="section-content">
            <?php foreach ($em_atendimento as $chamado): ?>
              <div style="background: rgba(255, 255, 255, 0.05); border-radius: var(--border-radius); padding: 1.5rem; margin-bottom: 1.5rem; border: 1px solid var(--border-color);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                  <h3 style="color: var(--accent-cyan);">
                    <i class="fas fa-ticket-alt"></i> 
                    Chamado #<?= $chamado['id'] ?> - <?= htmlspecialchars($chamado['titulo']) ?>
                  </h3>
                  <span style="color: var(--text-secondary); font-size: 0.9rem;">
                    <?= date('d/m/Y H:i', strtotime($chamado['data_abertura'])) ?>
                  </span>
                </div>
                
                <div style="margin-bottom: 1rem;">
                  <p style="margin-bottom: 0.5rem;"><strong>Usuário:</strong> <?= htmlspecialchars($chamado['usuario_nome']) ?></p>
                  <p style="margin-bottom: 1rem;"><strong>Descrição:</strong> <?= htmlspecialchars($chamado['descricao']) ?></p>
                </div>

                <form method="POST">
                  <div class="form-group">
                    <label for="resposta_ti_<?= $chamado['id'] ?>">
                      <i class="fas fa-comment"></i> Resposta do Técnico
                    </label>
                    <textarea 
                      name="resposta_ti" 
                      id="resposta_ti_<?= $chamado['id'] ?>" 
                      placeholder="Descreva a solução aplicada..."
                      required
                    ></textarea>
                  </div>

                  <input type="hidden" name="chamado_id" value="<?= $chamado['id'] ?>" />
                  <button type="submit" name="resolver" class="btn btn-success">
                    <i class="fas fa-check"></i> Finalizar Chamado
                  </button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>

    <!-- Sidebar -->
    <div class="sidebar">
      
      <!-- Setores Online -->
      <div class="section-card">
        <div class="section-header">
          <h2><i class="fas fa-broadcast-tower"></i> Setores Online</h2>
        </div>
        <div class="section-content">
          <?php if (count($setores_online) === 0): ?>
            <div class="empty-state">
              <i class="fas fa-wifi"></i>
              <h3>Nenhum setor online</h3>
              <p>Aguardando conexões...</p>
            </div>
          <?php else: ?>
            <div class="online-users">
              <?php foreach ($setores_online as $setor): ?>
                <div class="user-item">
                  <div class="user-avatar">
                    <?= strtoupper(substr($setor['nome'], 0, 2)) ?>
                  </div>
                  <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($setor['nome']) ?></div>
                    <div class="user-status"><?= $setor['usuarios_online'] ?> usuário(s) online</div>
                  </div>
                  <div class="online-indicator"></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Top Usuários -->
      <div class="section-card">
        <div class="section-header">
          <h2><i class="fas fa-crown"></i> Top Usuários</h2>
        </div>
        <div class="section-content">
          <ul class="stat-list">
            <?php foreach ($top_usuarios as $index => $usuario): ?>
              <li>
                <span>
                  <i class="fas fa-medal" style="color: <?= $index === 0 ? '#ffd700' : ($index === 1 ? '#c0c0c0' : '#cd7f32') ?>"></i>
                  <?= htmlspecialchars($usuario['nome']) ?>
                </span>
                <span class="value"><?= $usuario['total'] ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <!-- Ações Rápidas -->
      <div class="section-card">
        <div class="section-header">
          <h2><i class="fas fa-bolt"></i> Ações Rápidas</h2>
        </div>
        <div class="section-content">
          <div class="quick-actions">
            <a href="#" class="quick-action" onclick="location.reload()">
              <i class="fas fa-sync-alt"></i>
              <span>Atualizar Dashboard</span>
            </a>
            <a href="#" class="quick-action" onclick="exportData()">
              <i class="fas fa-download"></i>
              <span>Exportar Relatório</span>
            </a>
            <a href="#" class="quick-action" onclick="toggleNotifications()">
              <i class="fas fa-bell"></i>
              <span>Notificações</span>
            </a>
            <a href="#" class="quick-action" onclick="showSettings()">
              <i class="fas fa-cog"></i>
              <span>Configurações</span>
            </a>
          </div>
        </div>
      </div>

      <!-- Estatísticas da Semana -->
      <div class="section-card">
        <div class="section-header">
          <h2><i class="fas fa-chart-bar"></i> Últimos 7 Dias</h2>
        </div>
        <div class="section-content">
          <ul class="stat-list">
            <?php foreach ($stats_semana as $stat): ?>
              <li>
                <span><?= date('d/m', strtotime($stat['data'])) ?></span>
                <span class="value"><?= $stat['total'] ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

    </div>
  </div>

</div>

<script>
// Atualizar relógio
function updateClock() {
  const now = new Date();
  const timeString = now.toLocaleTimeString('pt-BR');
  document.getElementById('current-time').textContent = timeString;
}

// Atualizar a cada segundo
setInterval(updateClock, 1000);
updateClock();

// Auto-refresh da página a cada 30 segundos
setInterval(function() {
  location.reload();
}, 30000);

// Funções para ações rápidas
function exportData() {
  alert('Função de exportação será implementada em breve!');
}

function toggleNotifications() {
  alert('Sistema de notificações será implementado em breve!');
}

function showSettings() {
  alert('Painel de configurações será implementado em breve!');
}

// Adicionar efeitos visuais
document.addEventListener('DOMContentLoaded', function() {
  // Animação de entrada dos cards
  const cards = document.querySelectorAll('.stat-card, .section-card');
  cards.forEach((card, index) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    setTimeout(() => {
      card.style.transition = 'all 0.5s ease';
      card.style.opacity = '1';
      card.style.transform = 'translateY(0)';
    }, index * 100);
  });

  // Efeito de hover nas linhas da tabela
  const tableRows = document.querySelectorAll('tbody tr');
  tableRows.forEach(row => {
    row.addEventListener('mouseenter', function() {
      this.style.backgroundColor = 'rgba(0, 255, 255, 0.1)';
    });
    row.addEventListener('mouseleave', function() {
      this.style.backgroundColor = '';
    });
  });
});

// Notification system (placeholder)
function showNotification(message, type = 'info') {
  // Implementar sistema de notificações toast
  console.log(`${type}: ${message}`);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
  if (e.ctrlKey || e.metaKey) {
    switch(e.key) {
      case 'r':
        e.preventDefault();
        location.reload();
        break;
      case 'q':
        e.preventDefault();
        window.location.href = 'logout.php';
        break;
    }
  }
});
</script>

</body>
</html>