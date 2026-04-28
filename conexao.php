<?php
// Carrega variáveis do .env (formato KEY=VALUE)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

$host    = $_ENV['DB_HOST']    ?? 'localhost';
$dbname  = $_ENV['DB_NAME']    ?? 'cond_v1';
$user    = $_ENV['DB_USER']    ?? 'root';
$pass    = $_ENV['DB_PASS']    ?? '';
$charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$charset",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Em produção, evite expor a mensagem do erro ao usuário.
    if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
        die('Erro fatal na conexão: ' . $e->getMessage());
    }
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('Erro de conexão com o banco de dados.');
}
