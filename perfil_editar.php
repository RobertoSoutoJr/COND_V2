<?php 
require_once 'auth_check.php'; // Proteção NORMAL (só precisa estar logado)
require_once 'conexao.php';

$mensagem = '';
$id_usuario_logado = $_SESSION['usuario_id']; // Pega o ID da sessão

// Processa o formulário de UPLOAD DE FOTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])) {
    try {
        if ($_FILES['foto']['error'] === 0) {
            if (!is_dir('uploads/usuarios')) mkdir('uploads/usuarios', 0755, true);
            
            $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $novo_nome = $id_usuario_logado . '_' . time() . '.' . $extensao; // ex: 3_16788899.png
            $destino = 'uploads/usuarios/' . $novo_nome;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                // Atualiza o banco de dados
                $stmt = $pdo->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
                $stmt->execute([$novo_nome, $id_usuario_logado]);

                // ATUALIZA A SESSÃO (para o menu mudar na hora)
                $_SESSION['usuario_foto'] = $novo_nome;
                
                $mensagem = "<div class='bg-green-100 text-green-700 p-3 rounded mb-4'>Foto de perfil atualizada com sucesso!</div>";
            }
        } else {
            throw new Exception("Erro no upload da foto.");
        }
    } catch (Exception $e) {
        $mensagem = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Erro: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meu Perfil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'menu.php'; ?>
    <div class="container mx-auto mt-10 px-4">
        <div class="max-w-lg mx-auto bg-white p-8 rounded-lg shadow-xl text-center">
            
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Meu Perfil</h2>
            <?= $mensagem ?>

            <?php
            $foto_atual = 'uploads/usuarios/' . $_SESSION['usuario_foto'];
            if (!file_exists($foto_atual) || empty($_SESSION['usuario_foto'])) {
                $foto_atual = 'img/default_avatar.png';
            }
            ?>
            <img src="<?= $foto_atual ?>" alt="Minha Foto" class="w-32 h-32 rounded-full object-cover mx-auto mb-4 border-4 border-roxo-base">
            <h3 class="text-xl font-bold text-gray-700"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></h3>
            <p class="text-gray-500 mb-6"><?= $_SESSION['usuario_nivel'] == 'admin' ? 'Administrador' : 'Usuário' ?></p>
            
            <hr class="my-6">

            <form method="POST" action="" enctype="multipart/form-data">
                <label class="block text-gray-700 text-sm font-bold mb-2">Alterar Foto de Perfil:</label>
                <input type="file" name="foto" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:bg-blue-50 file:text-blue-700 mb-4" required>
                
                <button class="w-full bg-roxo-base hover:bg-purple-700 text-white font-bold py-3 px-4 rounded transition" type="submit">
                    <i class="bi bi-upload mr-2"></i> Salvar Nova Foto
                </button>
            </form>
            
        </div>
    </div>
    <?php include 'toast_handler.php'; ?>
</body>
</html>