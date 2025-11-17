<?php 
// 1. VERIFICA SE É ADMIN
require_once 'auth_admin_check.php'; 
// 2. CONECTA NO BANCO
require_once 'conexao.php';

$mensagem = '';

// 3. PROCESSA O CADASTRO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = $_POST['nome'];
        $login = $_POST['login'];
        $senha_pura = $_POST['senha'];
        $nivel = $_POST['nivel']; // Nível (admin ou usuario)

        // 1. Criptografa a senha
        $senha_hash = password_hash($senha_pura, PASSWORD_DEFAULT);
        
        // 2. Lógica de upload da foto
        $caminho_foto = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            if (!is_dir('uploads/usuarios')) {
                mkdir('uploads/usuarios', 0755, true);
            }
            
            $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $novo_nome = uniqid() . '.' . $extensao;
            $destino = 'uploads/usuarios/' . $novo_nome;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                $caminho_foto = $novo_nome;
            }
        }

        // 3. Salva no banco (agora com o nível)
        $sql = "INSERT INTO usuarios (nome, login, senha, nivel, foto) VALUES (?, ?, ?, ?, ?)";
        $pdo->prepare($sql)->execute([$nome, $login, $senha_hash, $nivel, $caminho_foto]);
        
        $mensagem = "<div class='bg-green-100 text-green-700 p-3 rounded mb-4'>Usuário cadastrado com sucesso!</div>";

    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) { // Erro de 'login' duplicado
            $mensagem = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Erro: O login '<strong>$login</strong>' já existe.</div>";
        } else {
            $mensagem = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Erro: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Registrar Novo Usuário</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-100">

    <?php include 'menu.php'; // Inclui o menu para navegação ?>

    <div class="container mx-auto mt-10 px-4">
        <div class="max-w-xl mx-auto bg-white p-8 rounded-lg shadow-xl">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2">
                <i class="bi bi-person-plus-fill text-roxo-base mr-2"></i>
                Cadastrar Novo Usuário
            </h2>

            <?= $mensagem ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nome Completo:</label>
                    <input class="border rounded w-full py-2 px-3" name="nome" type="text" required>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Login:</label>
                        <input class="border rounded w-full py-2 px-3" name="login" type="text" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Senha:</label>
                        <input class="border rounded w-full py-2 px-3" name="senha" type="password" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nível de Acesso:</label>
                    <select name="nivel" class="border rounded w-full py-2 px-3 bg-white">
                        <option value="usuario">Usuário Padrão</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Foto de Perfil (Opcional):</label>
                    <input type="file" name="foto" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:bg-blue-50 file:text-blue-700"/>
                </div>

                <button class="w-full bg-roxo-base hover:bg-purple-700 text-white font-bold py-3 px-4 rounded transition" type="submit">
                    Salvar Usuário
                </button>
            </form>
        </div>
    </div>
    <?php include 'toast_handler.php'; ?>
</body>
</html>