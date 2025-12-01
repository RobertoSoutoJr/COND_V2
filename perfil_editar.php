<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

$titulo_pagina = "Meu Perfil";

$mensagem_foto = '';
$mensagem_senha = '';
$id_usuario_logado = $_SESSION['usuario_id']; 

// --- LÓGICA DE PROCESSAMENTO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. MUDAR FOTO
    if (isset($_POST['acao']) && $_POST['acao'] === 'mudar_foto') {
        try {
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
                if (!is_dir('uploads/usuarios')) mkdir('uploads/usuarios', 0755, true);
                
                $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $novo_nome = $id_usuario_logado . '_' . time() . '.' . $extensao;
                $destino = 'uploads/usuarios/' . $novo_nome;

                if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                    $stmt = $pdo->prepare("UPDATE usuarios SET foto = ? WHERE id = ?");
                    $stmt->execute([$novo_nome, $id_usuario_logado]);
                    $_SESSION['usuario_foto'] = $novo_nome; 
                    
                    // Redireciona com Toast
                    header("Location: perfil_editar.php?msg=" . urlencode("Foto atualizada com sucesso!") . "&type=success");
                    exit;
                }
            } else {
                throw new Exception("Erro no upload da foto.");
            }
        } catch (Exception $e) {
            $mensagem_foto = "<div class='bg-red-50 text-red-600 p-3 rounded-lg text-sm mb-4 border border-red-100'>" . $e->getMessage() . "</div>";
        }
    }

    // 2. MUDAR SENHA
    elseif (isset($_POST['acao']) && $_POST['acao'] === 'mudar_senha') {
        $senha_atual = $_POST['senha_atual'];
        $nova_senha = $_POST['nova_senha'];
        $confirmar_senha = $_POST['confirmar_senha'];

        try {
            if ($nova_senha !== $confirmar_senha) throw new Exception("A nova senha e a confirmação não conferem.");
            if (empty($nova_senha)) throw new Exception("A nova senha não pode estar em branco.");

            $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
            $stmt->execute([$id_usuario_logado]);
            $hash_atual = $stmt->fetchColumn();

            if (password_verify($senha_atual, $hash_atual)) {
                $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt_update = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $stmt_update->execute([$novo_hash, $id_usuario_logado]);

                header("Location: perfil_editar.php?msg=" . urlencode("Senha alterada com sucesso!") . "&type=success");
                exit;
            } else {
                throw new Exception("A senha atual está incorreta.");
            }
        } catch (Exception $e) {
            $mensagem_senha = "<div class='bg-red-50 text-red-600 p-3 rounded-lg text-sm mb-4 border border-red-100'>" . $e->getMessage() . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meu Perfil - COND</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-50 font-sans text-gray-900">

    <?php include 'menu.php'; ?>

    <div class="md:ml-64 transition-all duration-300 flex flex-col min-h-screen">

        <div class="bg-white shadow-sm p-4 md:hidden flex justify-between items-center sticky top-0 z-30">
            <span class="font-bold text-xl text-roxo-base">COND</span>
            <button onclick="toggleSidebar()" class="text-gray-600 focus:outline-none">
                <i class="bi bi-list text-3xl"></i>
            </button>
        </div>

        <main class="flex-1 p-6">
            
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Meu Perfil</h1>
                <p class="text-sm text-gray-500">Gerencie sua foto e suas credenciais de acesso.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center">
                    <h2 class="text-lg font-bold text-gray-800 mb-6 w-full text-left border-b border-gray-100 pb-2">Foto de Perfil</h2>

                    <?php
                    $foto_atual = 'uploads/usuarios/' . $_SESSION['usuario_foto'];
                    if (!file_exists($foto_atual) || empty($_SESSION['usuario_foto'])) {
                        $foto_atual = 'img/default_avatar.png';
                    }
                    ?>
                    
                    <div class="relative group mb-4">
                        <img src="<?= $foto_atual ?>" alt="Minha Foto" class="w-32 h-32 rounded-full object-cover border-4 border-purple-100 shadow-sm group-hover:border-purple-200 transition-colors">
                        <div class="absolute bottom-0 right-0 bg-green-500 w-5 h-5 rounded-full border-2 border-white"></div>
                    </div>
                    
                    <h3 class="text-xl font-bold text-gray-700"><?= htmlspecialchars($_SESSION['usuario_nome']) ?></h3>
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider mt-2 
                        <?= $_SESSION['usuario_nivel'] == 'admin' ? 'bg-purple-100 text-roxo-base' : 'bg-gray-100 text-gray-600' ?>">
                        <?= $_SESSION['usuario_nivel'] ?>
                    </span>
                    
                    <hr class="w-full border-gray-100 my-6">

                    <?= $mensagem_foto ?>
                    <form method="POST" action="" enctype="multipart/form-data" class="w-full">
                        <input type="hidden" name="acao" value="mudar_foto">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Alterar Foto:</label>
                        <input type="file" name="foto" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-roxo-base hover:file:bg-purple-100 cursor-pointer mb-4" required>
                        
                        <button class="w-full bg-white border border-gray-300 text-gray-700 hover:border-roxo-base hover:text-roxo-base font-bold py-2 px-4 rounded-lg transition-colors flex items-center justify-center" type="submit">
                            <i class="bi bi-upload mr-2"></i> Enviar Nova Foto
                        </button>
                    </form>
                </div>

                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100">
                    <h2 class="text-lg font-bold text-gray-800 mb-6 border-b border-gray-100 pb-2 flex items-center">
                        <i class="bi bi-shield-lock text-roxo-base mr-2"></i> Segurança
                    </h2>
                    
                    <?= $mensagem_senha ?>
                    
                    <form method="POST" action="" class="space-y-4">
                        <input type="hidden" name="acao" value="mudar_senha">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Senha Atual</label>
                            <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" name="senha_atual" type="password" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nova Senha</label>
                            <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" name="nova_senha" type="password" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar Nova Senha</label>
                            <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" name="confirmar_senha" type="password" required>
                        </div>

                        <div class="pt-4">
                            <button class="w-full bg-roxo-base hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-lg shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5" type="submit">
                                Redefinir Senha
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </main>
    </div>
    
    <?php include 'toast_handler.php'; ?>
</body>
</html>