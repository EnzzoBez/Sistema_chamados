<?php
// conexao.php

// Configurações do banco de dados
$host = 'localhost'; // Geralmente 'localhost' se o banco estiver no mesmo servidor
$db    = 'sistema_chamados'; // O nome do seu banco de dados
$user = 'root';   // O usuário do seu banco de dados (ex: 'root')
$pass = '';     // A senha do seu usuário do banco de dados
$charset = 'utf8mb4';

// Data Source Name (DSN) para a conexão PDO
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Opções para a conexão PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lança exceções em caso de erro
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Retorna os resultados como array associativo
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Desabilita a emulação de prepared statements (melhor segurança e performance)
];

// Tenta estabelecer a conexão
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // echo "Conexão com o banco de dados estabelecida com sucesso!"; // Apenas para teste, remova em produção
} catch (\PDOException $e) {
    // Em caso de erro na conexão, exibe uma mensagem e encerra o script
    // Em um ambiente de produção, você deve registrar o erro em um log
    // e mostrar uma mensagem genérica para o usuário, sem detalhes técnicos.
    echo "Erro de conexão com o banco de dados: " . $e->getMessage();
    exit(); // Encerra a execução do script
}
?>