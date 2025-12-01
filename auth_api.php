<?php
session_start();
require_once 'conexao.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Requisição inválida.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'login':
            $login = $_POST['usuario'] ?? '';
            $senha_pura = $_POST['senha'] ?? '';

            if (empty($login) || empty($senha_pura)) {
                $response['message'] = "Por favor, preencha o login e a senha.";
                break;
            }

            try {
                // 1. Busca o usuário no banco pelo login
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE login = ?");
                $stmt->execute([$login]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                // 2. Verifica se o usuário existe E se a senha está correta
                if ($usuario && password_verify($senha_pura, $usuario['senha'])) {

                    // 3. SUCESSO! Armazena os dados na Sessão
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nome'] = $usuario['nome'];
                    $_SESSION['usuario_foto'] = $usuario['foto'];
                    $_SESSION['usuario_nivel'] = $usuario['nivel'];

                    $response['success'] = true;
                    $response['message'] = "Login realizado com sucesso!";
                    $response['redirect'] = 'index.php'; // Redirecionamento para o dashboard
                    $response['user'] = [
                        'id' => $usuario['id'],
                        'nome' => $usuario['nome'],
                        'foto' => $usuario['foto'],
                        'nivel' => $usuario['nivel']
                    ];

                } else {
                    // 4. Falha
                    $response['message'] = "Login ou senha inválidos.";
                }

            } catch (PDOException $e) {
                $response['message'] = "Erro no banco de dados: " . $e->getMessage();
            }
            break;

        case 'register':
            $nome = $_POST['nome'] ?? '';
            $email = $_POST['email'] ?? ''; // Campo 'email' da tela de registro (não usado no DB, mas mantido para compatibilidade)
            $login = $_POST['usuario'] ?? '';
            $senha_pura = $_POST['senha'] ?? '';
            $nivel = 'usuario'; // Nível padrão para registro via tela

            if (empty($nome) || empty($login) || empty($senha_pura)) {
                $response['message'] = "Por favor, preencha todos os campos obrigatórios.";
                break;
            }
            
            // Validação simples de email (opcional, pois o campo não está no DB)
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = "Formato de e-mail inválido.";
                break;
            }
            
            try {
                // 1. Criptografa a senha
                $senha_hash = password_hash($senha_pura, PASSWORD_DEFAULT);
                
                // 2. Salva no banco
                // O campo 'email' da tela de registro não existe na tabela `usuarios`, então será ignorado.
                $sql = "INSERT INTO usuarios (nome, login, senha, nivel) VALUES (?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nome, $login, $senha_hash, $nivel]);
                
                $response['success'] = true;
                $response['message'] = "Conta criada com sucesso! Faça login para continuar.";

            } catch (PDOException $e) {
                if ($e->errorInfo[1] == 1062) { // Erro de 'login' duplicado
                    $response['message'] = "Erro: O login '{$login}' já existe.";
                } else {
                    // Erro no banco de dados (ex: campo faltando, etc.)
                    $response['message'] = "Erro ao registrar: " . $e->getMessage();
                }
            }
            break;

        case 'check_login':
            if (isset($_SESSION['usuario_id'])) {
                $response['success'] = true;
                $response['message'] = "Usuário já logado.";
                $response['redirect'] = 'index.php';
            } else {
                $response['message'] = "Usuário não logado.";
            }
            break;

        default:
            $response['message'] = "Ação não especificada.";
            break;
    }
}

echo json_encode($response);
exit;
?>
