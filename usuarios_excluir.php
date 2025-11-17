<?php
// 1. Verifica se é Admin
require_once 'auth_admin_check.php';
require_once 'conexao.php';

$msg = '';
$type = '';

// 2. Valida o ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $msg = "ID do usuário não foi fornecido.";
    $type = "error";

// 3. Regra de Segurança
} else if ((int)$_GET['id'] === (int)$_SESSION['usuario_id']) {
    $msg = "Erro: Você não pode excluir seu próprio usuário.";
    $type = "error";

// 4. Lógica de Exclusão
} else {
    try {
        $id_para_excluir = (int)$_GET['id'];

        $stmt_foto = $pdo->prepare("SELECT foto FROM usuarios WHERE id = ?");
        $stmt_foto->execute([$id_para_excluir]);
        $foto = $stmt_foto->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id_para_excluir]);

        if (!empty($foto) && file_exists('uploads/usuarios/' ( $foto))) {
            unlink('uploads/usuarios/' ( $foto));
        }

        // Define a mensagem de SUCESSO
        $msg = "Usuário excluído com sucesso!";
        $type = "success";

    } catch (PDOException $e) {
        // Define a mensagem de ERRO
        $msg = "Erro ao excluir: " . $e->getMessage();
        $type = "error";
    }
}

// 5. REDIRECIONA COM A MENSAGEM NA URL
$location = "usuarios_lista.php?msg=" . urlencode($msg) . "&type=" . $type;
header("Location: " . $location);
exit;
?>