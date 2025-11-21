<?php require_once 'auth_check.php'; ?>
<?php
require_once 'conexao.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // 1. Verifica se o fornecedor tem histórico (Entradas de Produto)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM entradas_produto WHERE fornecedor_id = ?");
        $stmt->execute([$id]);
        $historico = $stmt->fetchColumn();

        if ($historico > 0) {
            // Se tiver histórico, proíbe a exclusão
            echo "<script>
                alert('Não é possível excluir este fornecedor pois ele possui histórico de entradas de produto. Apenas a edição é permitida.');
                window.location.href = 'fornecedores_lista.php';
            </script>";
            exit;
        }

        // 2. Se não tiver histórico, pode excluir
        $stmt = $pdo->prepare("DELETE FROM fornecedores WHERE id = ?");
        $stmt->execute([$id]);

        header("Location: fornecedores_lista.php?msg=" . urlencode("Fornecedor excluído com sucesso!") . "&type=success");

    } catch (PDOException $e) {
        die("Erro ao excluir fornecedor: " . $e->getMessage());
    }
} else {
    header("Location: fornecedores_lista.php");
}
?>
