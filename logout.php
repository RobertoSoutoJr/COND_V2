<?php
// Inicia a sessão SÓ PARA PODER DESTRUÍ-LA
session_start();

// Limpa todas as variáveis da sessão (apaga o crachá)
$_SESSION = array();

// Destrói a sessão
session_destroy();

// Redireciona para o login com uma mensagem de sucesso
header("Location: login.php?msg=Você saiu do sistema.");
exit;
?>