<?php
require_once 'auth_admin_check.php';
require_once 'conexao.php';

$msg = '';
$type = '';

// Validação ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $msg = "ID não fornecido.";
    $type = "error";
}
// Regra de Segurança
else if ((int)$_GET['id'] === (int)$_SESSION['usuario_id']) {
    $msg = "Erro: Você não pode excluir seu próprio usuário.";
    $type = "error";
}
// Exclusão
else {
    try {
        $id = (int)$_GET['id'];
        
        // Pega foto
        $stmt = $pdo->prepare("SELECT foto FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        $foto = $stmt->fetchColumn();

        // Deleta
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);

        // Remove ficheiro
        if (!empty($foto) && file_exists('uploads/usuarios/' . $foto)) {
            unlink('uploads/usuarios/' . $foto);
        }

        $msg = "Usuário excluído com sucesso!";
        $type = "success";

    } catch (PDOException $e) {
        $msg = "Erro ao excluir: " . $e->getMessage();
        $type = "error";
    }
}

// Redireciona
header("Location: usuarios_lista.php?msg=" . urlencode($msg) . "&type=" . $type);
exit;
?>