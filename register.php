<?php
// register.php - Cadastro de Usuários
session_start();
require 'conexao.php'; // Inclui o arquivo de conexão com o banco de dados

$erro = '';
$sucesso = '';

// Busca os setores para o campo select
$setores = [];
try {
    $stmt = $pdo->query("SELECT id, nome FROM setores ORDER BY nome");
    $setores = $stmt->fetchAll();
} catch (PDOException $e) {
    $erro = "Erro ao carregar setores: " . $e->getMessage();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    $confirmar_senha = trim($_POST['confirmar_senha'] ?? '');
    $setor_id = (int)($_POST['setor_id'] ?? 0);

    // Validação básica
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha) || $setor_id === 0) {
        $erro = 'Por favor, preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Formato de email inválido.';
    } elseif ($senha !== $confirmar_senha) {
        $erro = 'As senhas não coincidem.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } else {
        // Verifica se o email já está cadastrado
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $erro = 'Este email já está cadastrado.';
        } else {
            // Hash da senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Insere o novo usuário no banco de dados
            try {
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha_hash, setor_id, tipo_usuario, data_cadastro) VALUES (?, ?, ?, ?, 'usuario_comum', NOW())");
                if ($stmt->execute([$nome, $email, $senha_hash, $setor_id])) {
                    $sucesso = 'Cadastro realizado com sucesso! Você já pode <a href="index.php">fazer login</a>.';
                    // Limpa os campos do formulário
                    $nome = $email = $senha = $confirmar_senha = '';
                    $setor_id = 0;
                } else {
                    $erro = 'Erro ao cadastrar usuário. Tente novamente.';
                }
            } catch (PDOException $e) {
                $erro = "Erro no banco de dados: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Belapedra - Cadastro</title>
    <link rel="icon" href="imagens/Logo_belapedra.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Variáveis CSS para facilitar a manutenção, espelhando index.php */
        :root {
            --dark-bg: #1a1a2e;
            --container-bg: rgba(25, 25, 45, 0.95);
            --gradient-1: #00ffff; /* Ciano */
            --gradient-2: #8a2be2; /* Azul violeta */
            --text-color: #e0e0e0;
            --input-bg: rgba(40, 40, 70, 0.7);
            --input-border: rgba(100, 100, 150, 0.3);
            --input-focus-border: #00ffff;
            --button-start-color: #00ffff;
            --button-end-color: #8a2be2;
            --button-hover-start-color: #8a2be2;
            --button-hover-end-color: #00ffff;
            --error-color: #e74c3c;
            --success-color: #2ecc71;
            --link-color: #00ffff;
            --link-hover-color: #8a2be2;
        }

        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, var(--dark-bg) 0%, #0f0f1c 100%);
            color: var(--text-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1rem;
            overflow: hidden; /* Evita scroll desnecessário */
            position: relative;
        }

        /* Efeito de partículas/bolhas no fundo */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 15% 50%, rgba(0, 255, 255, 0.1), transparent 30%),
                        radial-gradient(circle at 85% 70%, rgba(138, 43, 226, 0.1), transparent 30%);
            animation: moveGradient 20s infinite alternate;
            opacity: 0.7;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes moveGradient {
            0% { background-position: 0% 0%, 100% 100%; }
            100% { background-position: 100% 100%, 0% 0%; }
        }

        .register-container {
            background: var(--container-bg);
            padding: 3rem 4rem; /* Aumentado o padding para um visual mais espaçoso */
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.08); /* Borda mais sutil */
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 80px rgba(0, 255, 255, 0.05); /* Sombra mais profunda */
            width: 100%;
            max-width: 550px; /* Levemente mais largo para mais campos */
            position: relative;
            z-index: 10; /* Garante que fique acima do background */
            animation: fadeInScale 0.8s ease-out; /* Animação ao carregar */
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .logo {
            text-align: center;
            margin-bottom: 3rem; /* Mais espaço abaixo do logo */
        }
        .logo h1 {
            font-size: 3.2rem; /* Tamanho maior */
            font-weight: 900; /* Mais encorpado */
            background: linear-gradient(135deg, var(--gradient-1), var(--gradient-2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.6rem; /* Ajuste de espaçamento */
            letter-spacing: -1.5px;
            text-shadow: 0 0 10px rgba(0, 255, 255, 0.3), 0 0 20px rgba(138, 43, 226, 0.2); /* Sombra de texto brilhante */
        }
        .logo p {
            color: #aaa; /* Cor mais suave */
            font-size: 1rem; /* Levemente maior */
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 3px; /* Mais espaçamento para elegância */
        }

        .error-msg, .success-msg {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .error-msg {
            background-color: var(--error-color);
            color: #fff;
        }
        .success-msg {
            background-color: var(--success-color);
            color: #fff;
        }

        .form-group {
            margin-bottom: 1.8rem; /* Mais espaçamento entre os grupos */
        }
        .form-group label {
            display: block;
            margin-bottom: 0.8rem; /* Mais espaço entre label e input */
            color: var(--text-color);
            font-size: 1.05rem;
            font-weight: 600;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group select { /* Adicionado estilo para o select */
            width: calc(100% - 24px); /* Ajuste para padding */
            padding: 12px;
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 8px;
            color: var(--text-color);
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            outline: none; /* Remove o outline padrão */
            -webkit-appearance: none; /* Remove estilo padrão do select em navegadores webkit */
            -moz-appearance: none; /* Remove estilo padrão do select em navegadores mozilla */
            appearance: none; /* Remove estilo padrão do select */
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23e0e0e0%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13%205.1L146.2%20202.7%2018.7%2074.5a17.6%2017.6%200%200%200-25.3%2023.3l130.8%20130.8c6.7%206.7%2017.7%206.7%2024.5%200l130.8-130.8a17.6%2017.6%200%200%200-13-30z%22%2F%3E%3C%2Fsvg%3E'); /* Seta customizada para o select */
            background-repeat: no-repeat;
            background-position: right 12px top 50%;
            background-size: 12px auto;
        }
        .form-group input:focus,
        .form-group select:focus { /* Adicionado estilo de foco para o select */
            border-color: var(--input-focus-border);
            box-shadow: 0 0 0 3px rgba(0, 255, 255, 0.2); /* Brilho suave no foco */
        }
        /* Placeholder styling */
        .form-group input::placeholder {
            color: #888;
            opacity: 0.7;
        }
        .form-group select option {
            background-color: var(--input-bg); /* Fundo das opções do select */
            color: var(--text-color);
        }

        button[type="submit"] {
            width: 100%;
            padding: 15px;
            background: linear-gradient(90deg, var(--button-start-color), var(--button-end-color));
            border: none;
            border-radius: 10px;
            color: #fff;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 15px rgba(0, 255, 255, 0.2);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 1rem; /* Espaço acima do botão */
            display: flex; /* Para alinhar o ícone */
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        button[type="submit"]:hover {
            background: linear-gradient(90deg, var(--button-hover-start-color), var(--button-hover-end-color));
            box-shadow: 0 10px 20px rgba(138, 43, 226, 0.3);
            transform: translateY(-2px); /* Efeito de levitação */
        }
        button[type="submit"]:active {
            transform: translateY(0);
            box-shadow: 0 4px 8px rgba(0, 255, 255, 0.1);
        }

        .login-link {
            text-align: center;
            margin-top: 2rem; /* Espaço maior */
            font-size: 0.95rem;
            color: #bbb;
        }
        .login-link a {
            color: var(--link-color);
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }
        .login-link a:hover {
            color: var(--link-hover-color);
            text-decoration: underline;
        }

        /* Responsividade */
        @media (max-width: 600px) {
            .register-container {
                padding: 2.5rem 2rem;
                border-radius: 16px;
                max-width: 95%; /* Permite ocupar mais espaço em telas pequenas */
            }
            .logo h1 {
                font-size: 2.8rem;
            }
            .logo p {
                font-size: 0.9rem;
                letter-spacing: 2px;
            }
            .form-group label {
                font-size: 1rem;
            }
            .form-group input,
            .form-group select {
                padding: 10px;
            }
            button[type="submit"] {
                padding: 12px;
                font-size: 1.1rem;
            }
            .login-link {
                margin-top: 1.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <h1>Belapedra</h1>
            <p>Cadastro de Usuário</p>
        </div>
        <?php if ($erro !== ''): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <?php if ($sucesso !== ''): ?>
            <div class="success-msg"><i class="fas fa-check-circle"></i> <?= $sucesso ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($nome ?? '') ?>" required autofocus placeholder="Seu nome completo" />
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required placeholder="seu.email@exemplo.com" />
            </div>

            <div class="form-group">
                <label for="setor_id">Setor</label>
                <select id="setor_id" name="setor_id" required>
                    <option value="">Selecione seu setor</option>
                    <?php foreach ($setores as $setor): ?>
                        <option value="<?= $setor['id'] ?>" <?= (isset($setor_id) && $setor_id == $setor['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($setor['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required placeholder="Mínimo 6 caracteres" />
            </div>

            <div class="form-group">
                <label for="confirmar_senha">Confirmar Senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required placeholder="Repita sua senha" />
            </div>

            <button type="submit">Cadastrar <i class="fas fa-user-plus"></i></button>
        </form>
        <div class="login-link">
            Já tem uma conta? <a href="index.php">Faça login aqui</a>
        </div>
    </div>
</body>
</html>