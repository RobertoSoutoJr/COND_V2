<?php
require_once 'auth_check.php';
require_once 'conexao.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        $pdo->beginTransaction();

        // 1. Verifica se a entrada existe e se o status é PENDENTE
        $stmt = $pdo->prepare("SELECT status_pagamento FROM entradas_produto WHERE id = ?");
        $stmt->execute([$id]);
        $entrada = $stmt->fetch();

        if (!$entrada) {
            header("Location: entradas_lista.php?msg=" . urlencode("Erro: Entrada não encontrada.") . "&type=error");
            exit;
        }

        if ($entrada['status_pagamento'] == 'PAGO') {
            header("Location: entradas_lista.php?msg=" . urlencode("Atenção: Esta entrada já está marcada como PAGA.") . "&type=info");
            exit;
        }
        
        if ($entrada['status_pagamento'] == 'CANCELADO') {
            header("Location: entradas_lista.php?msg=" . urlencode("Atenção: Esta entrada está CANCELADA e não pode ser paga.") . "&type=error");
            exit;
        }

        // 2. Atualiza o status para PAGO
        $sql = "UPDATE entradas_produto SET status_pagamento = 'PAGO' WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        $pdo->commit();

        header("Location: entradas_lista.php?msg=" . urlencode("Entrada #$id marcada como PAGA com sucesso!") . "&type=success");

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Erro ao marcar como pago: " . $e->getMessage());
    }
} else {
    header("Location: entradas_lista.php");
}
?>
