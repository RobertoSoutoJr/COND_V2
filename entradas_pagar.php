<?php
require_once 'auth_check.php';
require_once 'conexao.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $msg = '';
    $type = '';

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT status_pagamento FROM entradas_produto WHERE id = ?");
        $stmt->execute([$id]);
        $entrada = $stmt->fetch();

        if (!$entrada) {
            $msg = "Erro: Entrada não encontrada.";
            $type = "error";
        } elseif ($entrada['status_pagamento'] == 'PAGO') {
            $msg = "Atenção: Esta entrada já está paga.";
            $type = "info";
        } elseif ($entrada['status_pagamento'] == 'CANCELADO') {
            $msg = "Atenção: Esta entrada está cancelada.";
            $type = "error";
        } else {
            // Atualiza para PAGO
            $sql = "UPDATE entradas_produto SET status_pagamento = 'PAGO' WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $pdo->commit();

            $msg = "Entrada #$id marcada como PAGA com sucesso!";
            $type = "success";
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $msg = "Erro ao pagar: " . $e->getMessage();
        $type = "error";
    }

    // Redireciona para a lista com o Toast
    if (empty($msg)) { $msg = "Ação concluída."; $type = "info"; }
    header("Location: entradas_lista.php?msg=" . urlencode($msg) . "&type=" . $type);
    exit;

} else {
    header("Location: entradas_lista.php");
    exit;
}
?>