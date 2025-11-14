<?php
// Configurações do Banco de Dados (Padrão XAMPP)
$host = 'localhost';
$dbname = 'cond_v1';
$user = 'root';
$pass = ''; // No XAMPP a senha padrão geralmente é vazia

try {
    // Cria a conexão usando PDO
    // charset=utf8mb4 é essencial para aceitar acentos (ç, ã, é) e emojis corretamente
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);

    // Configurações de Erro e Retorno
    // 1. ERRMODE_EXCEPTION: Se der erro no SQL, o PHP avisa (ajuda MUITO a achar bugs)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. DEFAULT_FETCH_MODE: Traz os dados como um array associativo (['nome' => 'Camisa'])
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Se não conseguir conectar, mostra o erro e para o script
    die("Erro fatal na conexão: " . $e->getMessage());
}
?>