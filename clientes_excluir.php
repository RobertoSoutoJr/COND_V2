<?php
require_once 'auth_check.php';
require_once 'conexao.php';

// Variáveis para o redirecionamento
$msg = '';
$type = '';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        // 1. Verifica se o cliente tem histórico (Condicionais)
        // Regra de Negócio: Não podemos apagar clientes que já compraram/pegaram sacolas
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM condicionais WHERE cliente_id = ?");
        $stmt->execute([$id]);
        $historico = $stmt->fetchColumn();

        if ($historico > 0) {
            // ERRO: Tem histórico
            $msg = "Não é possível excluir: Este cliente possui histórico de sacolas registradas.";
            $type = "error";
        } else {
            // SUCESSO: Não tem histórico, pode excluir
            // O DELETE CASCADE do banco vai apagar o endereço automaticamente
            $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
            $stmt->execute([$id]);

            $msg = "Cliente excluído com sucesso!";
            $type = "success";
        }

    } catch (PDOException $e) {
        $msg = "Erro ao excluir: " . $e->getMessage();
        $type = "error";
    }
} else {
    $msg = "ID do cliente não fornecido.";
    $type = "error";
}

// Redirecionamento único com a mensagem na URL (Padrão Toast)
header("Location: clientes_lista.php?msg=" . urlencode($msg) . "&type=" . $type);
exit;
?>