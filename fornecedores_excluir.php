<?php
require_once 'auth_check.php';
require_once 'conexao.php';

$msg = '';
$type = '';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        // 1. Verifica se o fornecedor tem histórico
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM entradas_produto WHERE fornecedor_id = ?");
        $stmt->execute([$id]);
        $historico = $stmt->fetchColumn();

        if ($historico > 0) {
            $msg = "Não é possível excluir: Este fornecedor possui histórico de entradas registradas.";
            $type = "error";
        } else {
            $stmt = $pdo->prepare("DELETE FROM fornecedores WHERE id = ?");
            $stmt->execute([$id]);
            $msg = "Fornecedor excluído com sucesso!";
            $type = "success";
        }

    } catch (PDOException $e) {
        $msg = "Erro ao excluir: " . $e->getMessage();
        $type = "error";
    }
} else {
    $msg = "ID não fornecido.";
    $type = "error";
}

header("Location: fornecedores_lista.php?msg=" . urlencode($msg) . "&type=" . $type);
exit;
?>