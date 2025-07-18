<?php
// index.php - Página de Login
session_start();
require 'conexao.php'; // Inclui o arquivo de conexão com o banco de dados

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } else {
        // Busca o usuário no banco de dados pelo email
        $stmt = $pdo->prepare("SELECT id, nome, email, senha_hash, tipo_usuario, setor_id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            // Login bem-sucedido
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_email'] = $usuario['email'];
            $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
            $_SESSION['setor_id'] = $usuario['setor_id'];

            // Atualiza o log de login (marca como online)
            $stmt = $pdo->prepare("REPLACE INTO logs_login (usuario_id, last_active) VALUES (?, NOW())");
            $stmt->execute([$usuario['id']]);

            // Redireciona de acordo com o tipo de usuário
            if ($usuario['tipo_usuario'] === 'admin' || $usuario['tipo_usuario'] === 'tecnico') {
                $_SESSION['ti_logado'] = true; // Mantém para compatibilidade com seu dashboard atual
                header('Location: dashboard.php');
                exit();
            } else {
                header('Location: painel_usuario.php');
                exit();
            }
        } else {
            $erro = 'Email ou senha inválidos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Belapedra - Login</title>
        <link rel="icon" href="imagens/Logo_belapedra.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Variáveis CSS para facilitar a manutenção */
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

        /* Efeito de partículas/bolhas no fundo (opcional, pode ser pesado para alguns navegadores) */
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

        .login-container {
            background: var(--container-bg);
            padding: 3rem 4rem; /* Aumentado o padding para um visual mais espaçoso */
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.08); /* Borda mais sutil */
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 80px rgba(0, 255, 255, 0.05); /* Sombra mais profunda */
            width: 100%;
            max-width: 480px; /* Levemente mais largo */
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

        .error-msg {
            background-color: var(--error-color);
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: bold;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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
        .form-group input[type="email"],
        .form-group input[type="password"] {
            width: calc(100% - 24px); /* Ajuste para padding */
            padding: 12px;
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 8px;
            color: var(--text-color);
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            outline: none; /* Remove o outline padrão */
        }
        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus {
            border-color: var(--input-focus-border);
            box-shadow: 0 0 0 3px rgba(0, 255, 255, 0.2); /* Brilho suave no foco */
        }
        /* Placeholder styling */
        .form-group input::placeholder {
            color: #888;
            opacity: 0.7;
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

        .register-link {
            text-align: center;
            margin-top: 2rem; /* Espaço maior */
            font-size: 0.95rem;
            color: #bbb;
        }
        .register-link a {
            color: var(--link-color);
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }
        .register-link a:hover {
            color: var(--link-hover-color);
            text-decoration: underline;
        }

        /* Responsividade */
        @media (max-width: 600px) {
            .login-container {
                padding: 2.5rem 2rem;
                border-radius: 16px;
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
            .form-group input {
                padding: 10px;
            }
            button[type="submit"] {
                padding: 12px;
                font-size: 1.1rem;
            }
            .register-link {
                margin-top: 1.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>Belapedra</h1>
            <p>Sistema de Chamados</p>
        </div>
        <?php if ($erro !== ''): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus autocomplete="email" placeholder="seu.email@exemplo.com" />
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required autocomplete="current-password" placeholder="••••••••" />
            </div>

            <button type="submit">Entrar <i class="fas fa-sign-in-alt"></i></button>
        </form>
        <div class="register-link">
            Não tem uma conta? <a href="register.php">Cadastre-se aqui</a>
        </div>
    </div>
</body>
</html>