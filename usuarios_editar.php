<?php 
require_once 'auth_admin_check.php'; 
require_once 'conexao.php';

$titulo_pagina = "Editar Usuário";
$toast_msg = ''; $toast_type = '';

// --- CARREGAMENTO ---
$id_usuario_editar = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id_usuario_editar === 0) { header("Location: usuarios_lista.php"); exit; }

// Busca dados atuais para o Sticky Form e para pegar a foto atual
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$id_usuario_editar]);
$usuario = $stmt->fetch();
if (!$usuario) { header("Location: usuarios_lista.php?msg=" . urlencode("Usuário não encontrado") . "&type=error"); exit; }

// --- SALVAMENTO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = $_POST['nome'];
        $login = $_POST['login'];
        $nivel = $_POST['nivel'];
        $nova_senha = $_POST['senha'];

        $sql_parts = ["nome = :nome", "login = :login", "nivel = :nivel"];
        $params = [':nome' => $nome, ':login' => $login, ':nivel' => $nivel, ':id' => $id_usuario_editar];

        // 1. Senha
        if (!empty($nova_senha)) {
            $sql_parts[] = "senha = :senha";
            $params[':senha'] = password_hash($nova_senha, PASSWORD_DEFAULT);
        }

        // 2. Foto (Upload)
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            if (!is_dir('uploads/usuarios')) mkdir('uploads/usuarios', 0755, true);
            
            $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $novo_nome = $id_usuario_editar . '_' . time() . '.' . $extensao;
            $destino = 'uploads/usuarios/' . $novo_nome;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                $sql_parts[] = "foto = :foto";
                $params[':foto'] = $novo_nome;
                
                // Atualiza a sessão SE o admin estiver editando a si mesmo
                if ($id_usuario_editar == $_SESSION['usuario_id']) {
                    $_SESSION['usuario_foto'] = $novo_nome;
                }
            }
        }

        $sql = "UPDATE usuarios SET " . implode(', ', $sql_parts) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $msg_final = "Usuário atualizado com sucesso!";
        if (!empty($nova_senha)) $msg_final .= " (Senha redefinida)";
        
        header("Location: usuarios_lista.php?msg=" . urlencode($msg_final) . "&type=success");
        exit;

    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) { $toast_msg = "Erro: Login já existe."; } 
        else { $toast_msg = "Erro: " . $e->getMessage(); }
        $toast_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuário - COND</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-50 font-sans text-gray-900">

    <?php include 'menu.php'; ?>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($toast_msg)): ?>
        <div id='auto-toast' class='fixed top-5 right-5 z-[100] p-4 rounded-lg shadow-lg font-bold w-full max-w-sm transition-all duration-500 border bg-red-100 border-red-200 text-red-800' role='alert'>
            <div class='flex items-center'><span class='text-xl mr-3'><i class="bi bi-exclamation-triangle-fill"></i></span><span class='flex-grow text-sm'><?= $toast_msg ?></span><button onclick='document.getElementById("auto-toast").remove()' class='ml-4 text-xl opacity-60 hover:opacity-100'>&times;</button></div>
        </div>
    <?php endif; ?>

    <div class="md:ml-64 transition-all duration-300 flex flex-col min-h-screen">
        <div class="bg-white shadow-sm p-4 md:hidden flex justify-between items-center sticky top-0 z-30"><span class="font-bold text-xl text-roxo-base">COND</span><button onclick="toggleSidebar()" class="text-gray-600 focus:outline-none"><i class="bi bi-list text-3xl"></i></button></div>

        <main class="flex-1 p-6">
            <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div><h1 class="text-2xl font-bold text-gray-800">Editar Usuário</h1><p class="text-sm text-gray-500">Alterar dados, permissões e foto.</p></div>
                <a href="usuarios_lista.php" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-roxo-base transition-colors"><i class="bi bi-arrow-left mr-2"></i> Voltar para Lista</a>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden max-w-2xl mx-auto">
                <div class="p-6 md:p-8">
                    
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                        
                        <div class="flex justify-center mb-8">
                            <div class="relative group">
                                <img src="uploads/usuarios/<?= $usuario['foto'] ?: '../img/default_avatar.png' ?>" class="w-24 h-24 rounded-full object-cover border-4 border-gray-100 shadow-sm">
                                <label class="absolute bottom-0 right-0 bg-roxo-base text-white p-1.5 rounded-full cursor-pointer hover:bg-purple-700 transition shadow-md" title="Alterar Foto">
                                    <i class="bi bi-camera-fill text-sm"></i>
                                    <input type="file" name="foto" accept="image/*" class="hidden">
                                </label>
                            </div>
                        </div>

                        <div class="mb-6"><label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo</label><input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" name="nome" type="text" value="<?= htmlspecialchars($usuario['nome']) ?>" required></div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Login</label><input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" name="login" type="text" value="<?= htmlspecialchars($usuario['login']) ?>" required></div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nível de Acesso</label>
                                <select name="nivel" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 bg-white">
                                    <option value="usuario" <?= $usuario['nivel'] == 'usuario' ? 'selected' : '' ?>>Usuário Padrão</option>
                                    <option value="admin" <?= $usuario['nivel'] == 'admin' ? 'selected' : '' ?>>Administrador</option>
                                </select>
                            </div>
                        </div>

                        <hr class="border-gray-100 my-6">

                        <div class="mb-6 bg-yellow-50 border border-yellow-100 p-4 rounded-lg">
                            <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center"><i class="bi bi-key-fill text-yellow-600 mr-2"></i> Redefinir Senha</label>
                            <input class="w-full px-4 py-2 border border-yellow-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent bg-white" name="senha" type="password" placeholder="Nova senha (deixe em branco para manter)">
                            <p class="text-xs text-gray-500 mt-2">Preencha apenas se quiser alterar a senha deste usuário.</p>
                        </div>

                        <div class="flex justify-end">
                            <button class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2.5 px-6 rounded-lg shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center" type="submit">
                                <i class="bi bi-check-lg mr-2 text-lg"></i> Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script>document.addEventListener('DOMContentLoaded', function() { const toast = document.getElementById('auto-toast'); if (toast) { setTimeout(() => { toast.classList.add('opacity-0', '-translate-y-5'); setTimeout(() => toast.remove(), 500); }, 4000); } });</script>
    <?php include 'toast_handler.php'; ?>
</body>
</html>