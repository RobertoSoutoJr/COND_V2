<?php 
// 1. Verifica se é Admin
require_once 'auth_admin_check.php'; 
require_once 'conexao.php';

$mensagem = '';
$id_usuario_editar = null;

// --- 2. LÓGICA DE SALVAMENTO (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id_usuario_editar = (int)$_POST['id'];
        $nome = $_POST['nome'];
        $login = $_POST['login'];
        $nivel = $_POST['nivel'];
        $nova_senha = $_POST['senha'];

        // Prepara o SQL
        $sql_parts = [
            "nome = :nome",
            "login = :login",
            "nivel = :nivel"
        ];
        $params = [
            ':nome' => $nome,
            ':login' => $login,
            ':nivel' => $nivel,
            ':id' => $id_usuario_editar
        ];

        // --- Lógica da Senha (IMPORTANTE) ---
        // Só atualiza a senha se o admin digitou uma nova
        if (!empty($nova_senha)) {
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $sql_parts[] = "senha = :senha";
            $params[':senha'] = $senha_hash;
            $mensagem = "<div class= 'bg-yellow-100 text-yellow-700 p-3 rounded mb-4'>Usuário atualizado. <strong>A SENHA FOI REDEFINIDA!</strong></div>";
        }

        $sql = "UPDATE usuarios SET " . implode(', ', $sql_parts) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        if (empty($mensagem)) {
            $mensagem = "<div class='bg-green-100 text-green-700 p-3 rounded mb-4'>Usuário atualizado com sucesso!</div>";
        }

    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) { // Erro de 'login' duplicado
            $mensagem = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Erro: O login '<strong>$login</strong>' já pertence a outro usuário.</div>";
        } else {
            $mensagem = "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Erro: " . $e->getMessage() . "</div>";
        }
    }
}

// --- 3. LÓGICA DE CARREGAMENTO (GET) ---
// Se o ID não veio do POST (salvamento), pega da URL (carregamento)
if ($id_usuario_editar === null) {
    if (!isset($_GET['id'])) {
        header("Location: usuarios_lista.php");
        exit;
    }
    $id_usuario_editar = (int)$_GET['id'];
}

// Busca os dados do usuário no banco para preencher o formulário
try {
    $stmt = $pdo->prepare("SELECT id, nome, login, nivel FROM usuarios WHERE id = ?");
    $stmt->execute([$id_usuario_editar]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        header("Location: usuarios_lista.php?erro=Usuário não encontrado.");
        exit;
    }
} catch (PDOException $e) {
    die("Erro ao carregar usuário: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuário</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-100">

    <?php include 'menu.php'; ?>

    <div class="container mx-auto mt-10 px-4">
        <div class="max-w-xl mx-auto bg-white p-8 rounded-lg shadow-xl">
            <div class="flex justify-between items-center mb-6 border-b pb-2">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="bi bi-pencil-fill text-amber-500 mr-2"></i>
                    Editar Usuário
                </h2>
                <a href="usuarios_lista.php" class="text-sm text-roxo-base hover:underline">&larr; Voltar para a lista</a>
            </div>

            <?= $mensagem ?>

            <form method="POST" action="">
                <input type="hidden" name="id" value="<?= $usuario['id'] ?>">

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Nome Completo:</label>
                    <input class="border rounded w-full py-2 px-3" name="nome" type="text" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Login:</label>
                        <input class="border rounded w-full py-2 px-3" name="login" type="text" value="<?= htmlspecialchars($usuario['login']) ?>" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nível de Acesso:</label>
                        <select name="nivel" class="border rounded w-full py-2 px-3 bg-white">
                            <option value="usuario" <?= $usuario['nivel'] == 'usuario' ? 'selected' : '' ?>>
                                Usuário Padrão
                            </option>
                            <option value="admin" <?= $usuario['nivel'] == 'admin' ? 'selected' : '' ?>>
                                Administrador
                            </option>
                        </select>
                    </div>
                </div>

                <hr class="my-6">

                <div class="mb-6 bg-yellow-50 border border-yellow-300 p-4 rounded-lg">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="bi bi-key-fill"></i> Redefinir Senha
                    </label>
                    <input class="border rounded w-full py-2 px-3" name="senha" type="password" placeholder="******************">
                    <p class="text-xs text-gray-500 mt-1">Deixe em branco para **não** alterar a senha atual.</p>
                </div>

                <button class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-4 rounded transition" type="submit">
                    Salvar Alterações
                </button>
            </form>
        </div>
    </div>
    <?php include 'toast_handler.php'; ?>
</body>
</html>