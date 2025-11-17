<?php
// 1. INICIA A SESSÃO (Isto é tudo o que este ficheiro deve fazer)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. VERIFICA O LOGIN
if (!isset($_SESSION['usuario_id'])) {
    session_destroy();
    // Vamos redirecionar para o login sem a mensagem de cache
    header("Location: login.php?erro=Acesso negado.");
    exit;
}
?>