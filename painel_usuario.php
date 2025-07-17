<?php
session_start();
require 'conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Usuário';

// Busca chamados do usuário
$stmt = $pdo->prepare("SELECT * FROM chamados WHERE usuario_id = ? ORDER BY data_abertura DESC");
$stmt->execute([$usuario_id]);
$chamados = $stmt->fetchAll();

$total_chamados = count($chamados);
$em_andamento = false;

foreach ($chamados as $c) {
    $status = strtolower($c['status']);
    if ($status === 'em andamento' || $status === 'andamento') {
        $em_andamento = true;
        break;
    }
}

// Funções para badges
function badgePrioridade($p) {
    $p = strtolower($p);
    return match($p) {
        'alta' => '<span class="badge alta">Alta</span>',
        'média', 'media' => '<span class="badge media">Média</span>',
        'baixa' => '<span class="badge baixa">Baixa</span>',
        default => '<span class="badge default">'.htmlspecialchars($p).'</span>'
    };
}
function badgeStatus($s) {
    $s = strtolower($s);
    return match($s) {
        'aberto' => '<span class="badge status aberto">Aberto</span>',
        'em andamento', 'andamento' => '<span class="badge status andamento">Em andamento</span>',
        'encerrado', 'fechado' => '<span class="badge status encerrado">Encerrado</span>',
        default => '<span class="badge status default">'.htmlspecialchars($s).'</span>'
    };
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Painel Profissional - Bela Pedra</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

/* Reset */
* {
  margin: 0; padding: 0; box-sizing: border-box;
}
body, html {
  height: 100%;
  font-family: 'Inter', sans-serif;
  background: linear-gradient(135deg, #0a0a1a 0%, #1a1a2e 100%);
  color: #d0d4f7;
  overflow: hidden;
}
a {
  color: #89a9f8;
  text-decoration: none;
}
a:hover {
  text-decoration: underline;
}

/* Layout grid: sidebar + main */
.container {
  display: grid;
  grid-template-columns: 260px 1fr;
  height: 100vh;
  overflow: hidden;
}

/* Sidebar */
.sidebar {
  background: #121627;
  padding: 2rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 2rem;
  box-shadow: 2px 0 12px rgba(0,0,0,0.9);
  user-select: none;
}
.sidebar h2 {
  color: #8899cc;
  font-weight: 700;
  letter-spacing: 2px;
  font-size: 1.8rem;
  margin-bottom: 1.5rem;
  text-align: center;
  text-transform: uppercase;
}
.sidebar nav a {
  display: block;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1.1rem;
  color: #a3adff;
  transition: background-color 0.3s ease, box-shadow 0.3s ease;
  box-shadow: inset 0 0 5px rgb(0 0 0 / 0.2);
}
.sidebar nav a:hover, .sidebar nav a.active {
  background: #3743b2;
  box-shadow: 0 0 12px #5566f7;
  color: #dde6ff;
}

/* Main content */
.main {
  display: flex;
  flex-direction: column;
  padding: 2rem 3rem;
  overflow-y: auto;
}

/* Header topo */
.main header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #2c3275;
  user-select: none;
}
.main header h1 {
  font-weight: 700;
  font-size: 2.4rem;
  color: #8899cc;
  letter-spacing: 1.4px;
}
.user-info {
  font-weight: 600;
  font-size: 1.1rem;
  color: #a0a7c1;
}
.btn-sair {
  background: #4d4f7c;
  color: #d6d8ff;
  padding: 0.55rem 1.5rem;
  border-radius: 8px;
  font-weight: 700;
  font-size: 1rem;
  transition: background-color 0.3s ease, box-shadow 0.3s ease;
  border: none;
  cursor: pointer;
  box-shadow: 0 0 6px #3a3d5f;
  user-select: none;
  margin-left: 1rem;
}
.btn-sair:hover {
  background: #6c70b8;
  box-shadow: 0 0 14px #6c70b8;
}

/* Cards resumo */
.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 1.8rem;
  margin: 2rem 0 3rem;
}
.card {
  background: #222846;
  border-radius: 14px;
  padding: 2rem 2.5rem;
  box-shadow: inset 0 0 25px rgb(255 255 255 / 0.04), 0 8px 22px rgb(0 0 0 / 0.6);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  cursor: default;
  user-select: none;
}
.card:hover {
  transform: translateY(-7px);
  box-shadow: 0 12px 36px #4b4f9fcc;
}
.card h3 {
  font-weight: 700;
  font-size: 1.5rem;
  color: #a3aaff;
  margin-bottom: 0.5rem;
  letter-spacing: 0.6px;
}
.card p {
  font-size: 3rem;
  font-weight: 900;
  color: #d3d8ff;
  letter-spacing: 2.3px;
  line-height: 1;
}

/* Tabela chamada */
.table-container {
  overflow-x: auto;
  border-radius: 14px;
  box-shadow: 0 10px 30px rgba(15, 18, 38, 0.7);
  background: #1f2541;
  padding: 1.2rem 1rem;
}
table {
  border-collapse: separate;
  border-spacing: 0 16px;
  width: 100%;
  font-size: 1rem;
  min-width: 700px;
}
thead tr {
  background: #252f6d;
  color: #c5cbff;
  font-weight: 700;
  border-radius: 12px;
}
thead th {
  padding: 16px 24px;
  text-align: left;
  border-radius: 12px 12px 0 0;
}
tbody tr {
  background: #1a1e4c;
  box-shadow: 0 8px 20px rgb(3 5 18 / 0.8);
  border-radius: 14px;
  transition: background-color 0.3s ease;
}
tbody tr:hover {
  background-color: #2a3387;
}
tbody td {
  padding: 14px 20px;
  color: #d0d3f0;
  vertical-align: middle;
  white-space: nowrap;
}

/* Badges */
.badge {
  display: inline-block;
  padding: 5px 14px;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.85rem;
  user-select: none;
  box-shadow: 0 0 8px rgb(0 0 0 / 0.25);
  text-align: center;
  min-width: 75px;
}
.alta {
  background: #e05252;
  color: #fff5f5;
  box-shadow: 0 0 14px #e05252cc;
}
.media {
  background: #f3a261;
  color: #fff9f0;
  box-shadow: 0 0 14px #f3a261cc;
}
.baixa {
  background: #6abf69;
  color: #f0fff0;
  box-shadow: 0 0 14px #6abf69cc;
}
.status.aberto {
  background: #5d90ff;
  color: #e5eaff;
  box-shadow: 0 0 14px #5d90ffcc;
}
.status.andamento {
  background: #f0b429;
  color: #fff9d1;
  box-shadow: 0 0 14px #f0b429cc;
}
.status.encerrado {
  background: #5bc85b;
  color: #eaffea;
  box-shadow: 0 0 14px #5bc85bcc;
}

/* Formulário abrir chamado */
form {
  background: #1a1e4c;
  padding: 2rem 2.5rem;
  border-radius: 16px;
  box-shadow: 0 6px 25px rgba(10, 10, 40, 0.85);
  max-width: 700px;
  margin-bottom: 3rem;
  user-select: none;
}
form h2 {
  font-weight: 700;
  font-size: 2rem;
  margin-bottom: 1.5rem;
  color: #a8b1ff;
  border-bottom: 2px solid #3b42a0;
  padding-bottom: 0.7rem;
}
label {
  display: block;
  font-weight: 600;
  margin-top: 1.5rem;
  margin-bottom: 0.5rem;
  color: #bec3f8;
}
input[type="text"], textarea, select {
  width: 100%;
  padding: 0.85rem 1.4rem;
  font-size: 1.1rem;
  border-radius: 10px;
  border: none;
  background: #2c3463;
  color: #dbe0ff;
  font-weight: 400;
  box-shadow: inset 0 0 14px rgb(0 0 0 / 0.4);
  transition: background-color 0.3s ease, box-shadow 0.3s ease;
  resize: vertical;
}
input::placeholder, textarea::placeholder {
  color: #8c92c9;
}
input:focus, textarea:focus, select:focus {
  background: #3a437a;
  box-shadow: 0 0 18px #8a91f9;
  outline: none;
}
button {
  margin-top: 2rem;
  background: #5060f0;
  border: none;
  color: #eef0ff;
  font-size: 1.25rem;
  font-weight: 700;
  padding: 1rem 0;
  border-radius: 12px;
  cursor: pointer;
  width: 100%;
  box-shadow: 0 0 22px #7280f8;
  transition: background-color 0.3s ease, box-shadow 0.3s ease;
  user-select: none;
}
button:hover {
  background: #3743b2;
  box-shadow: 0 0 28px #4756f0;
}

/* Mensagens */
.error-msg, .sucesso-msg {
  margin-top: 1.5rem;
  padding: 1.2rem 1.4rem;
  border-radius: 12px;
  font-weight: 700;
  font-size: 1.1rem;
  user-select: none;
  max-width: 700px;
}
.error-msg {
  background: #632929;
  color: #f87b7b;
  border: 2px solid #f87b7b;
  box-shadow: inset 0 0 16px #b24c4c;
}
.sucesso-msg {
  background: #294632;
  color: #7bf87b;
  border: 2px solid #7bf87b;
  box-shadow: inset 0 0 16px #4cb24c;
}

/* Responsividade */
@media (max-width: 900px) {
  .container {
    grid-template-columns: 1fr;
    height: auto;
  }
  .sidebar {
    flex-direction: row;
    justify-content: space-around;
    padding: 1rem 0;
    box-shadow: none;
  }
  .sidebar h2 {
    display: none;
  }
  .main {
    padding: 1.5rem 1.5rem 3rem;
    overflow: visible;
  }
  .cards {
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  }
  table {
    min-width: 100%;
  }
}
@media (max-width: 500px) {
  form {
    padding: 1.5rem 1.6rem;
  }
  button {
    font-size: 1.1rem;
  }
}
 </style>
</head>
<body>
<div class="container">
  <aside class="sidebar">
    <h2>Bela Pedra</h2>
    <nav>
      <a href="#" class="active">Dashboard</a>
      <a href="#">Meus Chamados</a>
      <a href="#">Abrir Chamado</a>
      <a href="logout.php" class="btn-sair">Sair</a>
    </nav>
  </aside>

  <main class="main">
    <header>
      <h1>Painel do Usuário</h1>
      <div class="user-info"><?= htmlspecialchars($usuario_nome) ?></div>
    </header>

    <!-- Único card com status do técnico -->
    <section class="cards">
      <article class="card">
        <h3>Status do Técnico</h3>
        <p style="font-size: 2rem; color: <?= $em_andamento ? '#f0b429' : '#5bc85b' ?>;">
          <?= $em_andamento ? 'Atendendo chamado' : 'Livre' ?>
        </p>
      </article>
    </section>

    <!-- Formulário de chamado -->
    <form action="abrir_chamado.php" method="POST">
      <h2>Abrir Novo Chamado</h2>

      <?php if (!empty($_SESSION['erro_abertura'])): ?>
        <div class="error-msg"><?= htmlspecialchars($_SESSION['erro_abertura']) ?></div>
        <?php unset($_SESSION['erro_abertura']); ?>
      <?php endif; ?>

      <?php if (!empty($_SESSION['sucesso_abertura'])): ?>
        <div class="sucesso-msg"><?= htmlspecialchars($_SESSION['sucesso_abertura']) ?></div>
        <?php unset($_SESSION['sucesso_abertura']); ?>
      <?php endif; ?>

      <label for="titulo">Título</label>
      <input type="text" id="titulo" name="titulo" required maxlength="255" placeholder="Digite o título do chamado" />

      <label for="descricao">Descrição</label>
      <textarea id="descricao" name="descricao" rows="5" required placeholder="Descreva o problema"></textarea>

      <label for="prioridade">Prioridade</label>
      <select id="prioridade" name="prioridade" required>
        <option value="Baixa">Baixa</option>
        <option value="Média" selected>Média</option>
        <option value="Alta">Alta</option>
      </select>

      <button type="submit">Enviar Chamado</button>
    </form>

    <!-- Tabela de chamados -->
    <section>
      <h2>Meus Chamados</h2>

      <?php if ($total_chamados === 0): ?>
        <p>Nenhum chamado aberto até o momento.</p>
      <?php else: ?>
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Título</th>
                <th>Prioridade</th>
                <th>Status</th>
                <th>Data de abertura</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($chamados as $ch): ?>
                <tr>
                  <td><?= htmlspecialchars($ch['titulo']) ?></td>
                  <td><?= badgePrioridade($ch['prioridade']) ?></td>
                  <td><?= badgeStatus($ch['status']) ?></td>
                  <td><?= date('d/m/Y H:i', strtotime($ch['data_abertura'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </main>
</div>
</body>
</html>
