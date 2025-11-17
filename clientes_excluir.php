<?php require_once 'auth_check.php'; ?>
<?php
require_once 'conexao.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // 1. Verifica se o cliente tem histórico (Condicionais)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM condicionais WHERE cliente_id = ?");
        $stmt->execute([$id]);
        $historico = $stmt->fetchColumn();

        if ($historico > 0) {
            // Se tiver histórico, proíbe a exclusão
            echo "<script>
                alert('Não é possível excluir este cliente pois ele possui histórico de condicionais. Apenas a edição é permitida.');
                window.location.href = 'clientes_lista.php';
            </script>";
            exit;
        }

        // 2. Se não tiver histórico, pode excluir
        // Como configuramos ON DELETE CASCADE no banco, apagar o cliente apaga o endereço junto automaticamente.
        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: clientes_lista.php?sucesso=excluido");

    } catch (PDOException $e) {
        die("Erro ao excluir: " . $e->getMessage());
    }
} else {
    header("Location: clientes_lista.php");
}
?>