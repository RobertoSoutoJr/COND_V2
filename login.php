<?php
// Inicia a sessão para poder CRIAR o "crachá" se o login for bem-sucedido
session_start();

// Se o usuário já está logado e tentar acessar o login.php, manda ele pro index.
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Pega mensagens da URL (ex: ?erro=Acesso negado)
$erro_url = $_GET['erro'] ?? '';
$msg_url = $_GET['msg'] ?? '';

// Se houver mensagens de erro ou sucesso na URL, vamos exibi-las na nova tela
// A nova tela usa JavaScript para exibir notificações.
// Vamos criar um script PHP para injetar essas mensagens no JS.
$notificacao_js = '';
if (!empty($erro_url)) {
    $notificacao_js = "showNotification('".htmlspecialchars($erro_url)."', 'error', 5000);";
} elseif (!empty($msg_url)) {
    $notificacao_js = "showNotification('".htmlspecialchars($msg_url)."', 'success', 5000);";
}

// O arquivo auth_api.php vai lidar com a lógica de login/registro via AJAX.
// O arquivo login.php agora é apenas a interface.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GROF - Login & Cadastro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="notification-container" id="notificationContainer"></div>

    <div class="container">
        <!-- Animação de fundo -->
        <div class="background-animation">
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
            <div class="floating-element"></div>
        </div>

        <!-- Card principal -->
        <div class="auth-card" id="authCard">
            <div class="logo-container">
                <div class="logo-wrapper">
                    <!-- Usando a nova imagem de logo (condSfundo.png não existe no COND2, vou usar cond_logo.png) -->
                    <img src="img/cond_logo.png" alt="Logo COND" class="logo">
                </div>
            </div>

            <!-- Abas de navegação -->
            <div class="tabs-container">
                <button class="tab-btn active" data-tab="login" id="loginTabBtn">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Entrar</span>
                </button>
                <button class="tab-btn" data-tab="register" id="registerTabBtn">
                    <i class="bi bi-person-plus"></i>
                    <span>Cadastro</span>
                </button>
            </div>

            <!-- Conteúdo das abas -->
            <div class="tabs-content">
                <div class="tab-content active" id="loginTab">
                    <!-- O action do formulário será tratado via JS (AJAX) -->
                    <form id="loginForm" class="auth-form">
                        <div class="form-group">
                            <div class="input-wrapper">
                                <input 
                                    type="text" 
                                    id="loginUsuario" 
                                    name="usuario" 
                                    required
                                    placeholder=" "
                                >
                                <label for="loginUsuario">
                                    <i class="bi bi-person"></i>
                                    Usuário
                                </label>
                                <div class="input-underline"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-wrapper">
                                <input 
                                    type="password" 
                                    id="loginSenha" 
                                    name="senha" 
                                    required
                                    placeholder=" "
                                >
                                <label for="loginSenha">
                                    <i class="bi bi-lock"></i>
                                    Senha
                                </label>
                                <button type="button" class="toggle-password" id="toggleLoginPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <div class="input-underline"></div>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn" id="loginBtn">
                            <span class="btn-text">Entrar</span>
                            <span class="btn-loader">
                                <i class="bi bi-arrow-repeat"></i>
                            </span>
                        </button>

                        <div class="form-footer">
                            <a href="#" class="forgot-password">Esqueceu a senha?</a>
                        </div>
                    </form>
                </div>

                <div class="tab-content" id="registerTab">
                    <!-- O action do formulário será tratado via JS (AJAX) -->
                    <form id="registerForm" class="auth-form">
                        <div class="form-group">
                            <div class="input-wrapper">
                                <input 
                                    type="text" 
                                    id="registerNome" 
                                    name="nome" 
                                    required
                                    placeholder=" "
                                >
                                <label for="registerNome">
                                    <i class="bi bi-person-badge"></i>
                                    Nome Completo
                                </label>
                                <div class="input-underline"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-wrapper">
                                <input 
                                    type="email" 
                                    id="registerEmail" 
                                    name="email" 
                                    required
                                    placeholder=" "
                                >
                                <label for="registerEmail">
                                    <i class="bi bi-envelope"></i>
                                    Email
                                </label>
                                <div class="input-underline"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-wrapper">
                                <input 
                                    type="text" 
                                    id="registerUsuario" 
                                    name="usuario" 
                                    required
                                    placeholder=" "
                                >
                                <label for="registerUsuario">
                                    <i class="bi bi-at"></i>
                                    Usuário
                                </label>
                                <div class="input-underline"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-wrapper">
                                <input 
                                    type="password" 
                                    id="registerSenha" 
                                    name="senha" 
                                    required
                                    placeholder=" "
                                >
                                <label for="registerSenha">
                                    <i class="bi bi-lock"></i>
                                    Senha
                                </label>
                                <button type="button" class="toggle-password" id="toggleRegisterPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <div class="input-underline"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-wrapper">
                                <input 
                                    type="password" 
                                    id="registerConfirmSenha" 
                                    name="confirmSenha" 
                                    required
                                    placeholder=" "
                                >
                                <label for="registerConfirmSenha">
                                    <i class="bi bi-lock-fill"></i>
                                    Confirmar Senha
                                </label>
                                <button type="button" class="toggle-password" id="toggleRegisterConfirmPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <div class="input-underline"></div>
                            </div>
                        </div>

                        <div class="password-requirements">
                            <p class="requirements-title">Requisitos da senha:</p>
                            <ul>
                                <li id="req-length">
                                    <i class="bi bi-circle"></i>
                                    Mínimo 8 caracteres
                                </li>
                                <li id="req-uppercase">
                                    <i class="bi bi-circle"></i>
                                    Uma letra maiúscula
                                </li>
                                <li id="req-number">
                                    <i class="bi bi-circle"></i>
                                    Um número
                                </li>
                                <li id="req-special">
                                    <i class="bi bi-circle"></i>
                                    Um caractere especial (!@#$%^&*)
                                </li>
                            </ul>
                        </div>

                        <button type="submit" class="submit-btn" id="registerBtn">
                            <span class="btn-text">Criar Conta</span>
                            <span class="btn-loader">
                                <i class="bi bi-arrow-repeat"></i>
                            </span>
                        </button>

                        <div class="form-footer">
                            <p>Já tem uma conta? <a href="#" class="switch-tab" data-tab="login">Faça login</a></p>
                        </div>
                    </form>
                </div>
            </div>

            <div class="auth-footer">
                <p>&copy; 2025 Cond - Gestão de Condicionais</p>
            </div>
        </div>
    </div>

    <script>
        // Variável global para o caminho da API, que será usada no login.js
        const API_URL = 'auth_api.php';
        
        // Função de notificação (precisa ser definida antes de ser chamada)
        function showNotification(message, type = 'info', duration = 5000) {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            
            let icon = '';
            switch(type) {
                case 'error':
                    icon = '<i class="bi bi-exclamation-triangle-fill"></i>';
                    break;
                case 'success':
                    icon = '<i class="bi bi-check-circle-fill"></i>';
                    break;
                case 'info':
                    icon = '<i class="bi bi-info-circle-fill"></i>';
                    break;
                case 'warning':
                    icon = '<i class="bi bi-exclamation-circle-fill"></i>';
                    break;
            }
            
            notification.innerHTML = `${icon}<span>${message}</span>`;
            container.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 400);
            }, duration);
        }
        
        // Injeta a notificação da URL, se houver
        <?php echo $notificacao_js; ?>
    </script>
    <script src="js/login.js"></script>
</body>
</html>
