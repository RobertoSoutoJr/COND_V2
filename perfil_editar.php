<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

// Título da Página
$titulo_pagina = "Meu Perfil";

$mensagem_foto = '';
$mensagem_senha = '';
$id_usuario_logado = $_SESSION['usuario_id']; // Pega o ID da sessão

// --- LÓGICA DE PROCESSAMENTO (Dividida em Foto e Senha) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- AÇÃO 1: ATUALIZAR FOTO DE PERFIL ---
    if (isset($_POST['acao']) && $_POST['acao'] === 'mudar_foto') {
        try {
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
                if (!is_dir('uploads/usuarios')) mkdir('uploads/usuarios', 0755, true);
                
                $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $novo_nome = $id_usuario_logado . '_' . time() . '.' . $extensao;
                $destino = 'uploads/usuarios/' . $novo_nome;

                if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                    // (Opcional: apagar a foto antiga aqui)

                    $stmt = $pdo->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
                    $stmt->execute([$novo_nome, $id_usuario_logado]);
                    $_SESSION['usuario_foto'] = $novo_nome; // Atualiza a sessão
                    
                    $mensagem_foto = "<div class='bg-green-100 text-green-700 p-3 rounded mb-4'>Foto de perfil atualizada!</div>";
                }
            } else {
                throw new Exception("Erro no upload da foto.");
            }
        } catch (Exception $e) {
            $mensagem_foto = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Erro: " . $e->getMessage() . "</div>";
        }
    }

    // --- AÇÃO 2: ATUALIZAR SENHA ---
    elseif (isset($_POST['acao']) && $_POST['acao'] === 'mudar_senha') {
        $senha_atual = $_POST['senha_atual'];
        $nova_senha = $_POST['nova_senha'];
        $confirmar_senha = $_POST['confirmar_senha'];

        try {
            // 1. Verifica se a nova senha e a confirmação batem
            if ($nova_senha !== $confirmar_senha) {
                throw new Exception("A nova senha e a confirmação não são idênticas.");
            }
            // 2. Verifica se a nova senha não está vazia
            if (empty($nova_senha)) {
                throw new Exception("A nova senha não pode estar em branco.");
            }

            // 3. Busca a senha HASH atual no banco
            $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
            $stmt->execute([$id_usuario_logado]);
            $hash_atual = $stmt->fetchColumn();

            // 4. Verifica se a "Senha Atual" digitada bate com o HASH
            if (password_verify($senha_atual, $hash_atual)) {
                // 5. SUCESSO: Gera o novo hash e atualiza
                $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt_update = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $stmt_update->execute([$novo_hash, $id_usuario_logado]);

                $mensagem_senha = "<div class='bg-green-100 text-green-700 p-3 rounded mb-4'>Senha alterada com sucesso!</div>";
            } else {
                // 6. FALHA: Senha atual incorreta
                throw new Exception("A 'Senha Atual' está incorreta.");
            }

        } catch (Exception $e) {
            $mensagem_senha = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Erro: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meu Perfil</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'menu.php'; ?>
    
    <div class="container mx-auto mt-10 px-4 mb-20">
        <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8">

            <div class="bg-white p-8 rounded-lg shadow-xl">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Meu Perfil</h2>

                <?php
                $foto_atual = 'uploads/usuarios/' . $_SESSION['usuario_foto'];
                if (!file_exists($foto_atual) || empty($_SESSION['usuario_foto'])) {
                    $foto_atual = 'img/default_avatar.png';
                }
                ?>
                <img src="<?= $foto_atual ?>" alt="Minha Foto" class="w-32 h-32 rounded-full object-cover mx-auto mb-4 border-4 border-roxo-base">
                <h3 class="text-xl font-bold text-gray-700 text-center"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></h3>
                <p class="text-gray-500 text-center mb-6"><?= $_SESSION['usuario_nivel'] == 'admin' ? 'Administrador' : 'Usuário' ?></p>
                
                <hr class="my-6">

                <?= $mensagem_foto ?>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="mudar_foto">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Alterar Foto de Perfil:</label>
                    <input type="file" name="foto" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:bg-blue-50 file:text-blue-700 mb-4" required>
                    <button class="w-full bg-roxo-base hover:bg-purple-700 text-white font-bold py-3 px-4 rounded transition" type="submit">
                        <i class="bi bi-upload mr-2"></i> Salvar Nova Foto
                    </button>
                </form>
            </div>

            <div class="bg-white p-8 rounded-lg shadow-xl">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="bi bi-key-fill text-amber-500 mr-2"></i>
                    Alterar Senha
                </h2>
                
                <?= $mensagem_senha ?>
                <form method="POST" action="">
                    <input type="hidden" name="acao" value="mudar_senha">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="senha_atual">Senha Atual:</label>
                        <input class="border rounded w-full py-2 px-3" name="senha_atual" id="senha_atual" type="password" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="nova_senha">Nova Senha:</label>
                        <input class="border rounded w-full py-2 px-3" name="nova_senha" id="nova_senha" type="password" required>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="confirmar_senha">Confirmar Nova Senha:</label>
                        <input class="border rounded w-full py-2 px-3" name="confirmar_senha" id="confirmar_senha" type="password" required>
                    </div>

                    <button class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-4 rounded transition" type="submit">
                        <i class="bi bi-lock-fill mr-2"></i> Redefinir Senha
                    </button>
                </form>
            </div>

        </div>
    </div>
    
    <?php // include 'toast_handler.php'; ?>
</body>
</html>