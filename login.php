<?php
// Inicia a sessão para poder CRIAR o "crachá" se o login for bem-sucedido
session_start();
require_once 'conexao.php';

$erro_login = '';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['login']) || empty($_POST['senha'])) {
        $erro_login = "Por favor, preencha o login e a senha.";
    } else {
        $login = $_POST['login'];
        $senha_pura = $_POST['senha'];

        try {
            // 1. Busca o usuário no banco pelo login
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE login = ?");
            $stmt->execute([$login]);
            $usuario = $stmt->fetch();


            // 2. Verifica se o usuário existe E se a senha está correta
            // password_verify() compara a senha pura com o HASH salvo no banco
            if ($usuario && password_verify($senha_pura, $usuario['senha'])) {

                // 3. SUCESSO! Armazena os dados no "crachá" (Sessão)
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_foto'] = $usuario['foto']; // Ex: 'ana.png'
                $_SESSION['usuario_nivel'] = $usuario['nivel']; // GUARDA O NÍVEL (admin ou usuario)

                // 4. Redireciona para o Dashboard
                header("Location: index.php");
                exit;
            } else {
                // 5. Falha
                $erro_login = "Login ou senha inválidos.";
            }

        } catch (PDOException $e) {
            $erro_login = "Erro no banco de dados: " . $e->getMessage();
        }
    }
}

// Se o usuário já está logado e tentar acessar o login.php, manda ele pro index.
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Pega mensagens da URL (ex: ?erro=Acesso negado)
$erro_url = $_GET['erro'] ?? '';
$msg_url = $_GET['msg'] ?? '';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Login - Sistema Condicional</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="w-full max-w-md bg-white rounded-lg shadow-xl p-8 mx-4">

        <div class="flex justify-center mb-6">
            <img src="img/cond_logo.png" alt="Logo COND" class="h-32 w-32">
        </div>

        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Controle de Condicionais</h2>

        <?php if (!empty($erro_login)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                <?= htmlspecialchars($erro_login) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($erro_url)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm">
                <?= htmlspecialchars($erro_url) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($msg_url)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-sm">
                <?= htmlspecialchars($msg_url) ?>
            </div>
        <?php endif; ?>


        <form method="POST" action="login.php">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="login">
                    <i class="bi bi-person-fill"></i> Login
                </label>
                <input
                    class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:shadow-outline focus:border-roxo-base"
                    id="login" name="login" type="text" placeholder="Seu usuário" required>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="senha">
                    <i class="bi bi-lock-fill"></i> Senha
                </label>
                <input
                    class="shadow appearance-none border rounded w-full py-3 px-4 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline focus:border-roxo-base"
                    id="senha" name="senha" type="password" placeholder="******************" required>
            </div>

            <div class="flex items-center justify-between">
                <button
                    class="w-full bg-roxo-base hover:bg-purple-700 text-white font-bold py-3 px-4 rounded focus:outline-none focus:shadow-outline transition duration-300"
                    type="submit">
                    Entrar
                </button>
            </div>
        </form>
    </div>

</body>

</html>