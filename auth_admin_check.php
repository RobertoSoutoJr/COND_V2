<?php
// 1. Verifica se o usuário está logado (qualquer nível)
require_once 'auth_check.php';

// 2. Agora, verifica se o nível é 'admin'
if ($_SESSION['usuario_nivel'] !== 'admin') {
    // Se não for admin, expulsa para o Dashboard
    die("Acesso negado. Você não tem permissão de administrador.");
    // Ou, de forma mais elegante:
    // header("Location: index.php?erro=Acesso negado");
    // exit;
}
// Se for admin, o script continua...
?>