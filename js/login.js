// ========================================
// CONFIGURAÇÃO INICIAL
// ========================================
// A variável global API_URL é definida no próprio login.php
// ========================================
// NOTIFICAÇÕES
// ========================================

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

// ========================================
// ALTERNÂNCIA DE ABAS
// ========================================

function setupTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabName = button.getAttribute('data-tab');
            
            // Remover classe ativa de todos os botões e conteúdos
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Adicionar classe ativa ao botão e conteúdo clicado
            button.classList.add('active');
            document.getElementById(`${tabName}Tab`).classList.add('active');
            
            // Limpar formulário
            if (tabName === 'login') {
                document.getElementById('loginForm').reset();
            } else {
                document.getElementById('registerForm').reset();
            }
        });
    });

    // Alternar abas via links
    const switchTabLinks = document.querySelectorAll('.switch-tab');
    switchTabLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const tabName = link.getAttribute('data-tab');
            document.querySelector(`[data-tab="${tabName}"]`).click();
        });
    });
}

// ========================================
// TOGGLE DE SENHA
// ========================================

function setupPasswordToggle() {
    const toggleButtons = document.querySelectorAll('.toggle-password');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const input = button.parentElement.querySelector('input');
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        });
    });
}

// ========================================
// VALIDAÇÃO DE SENHA
// ========================================

function validatePassword(password) {
    const requirements = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
    };
    
    return requirements;
}

function updatePasswordRequirements(password) {
    const requirements = validatePassword(password);
    
    const reqElements = {
        'length': document.getElementById('req-length'),
        'uppercase': document.getElementById('req-uppercase'),
        'number': document.getElementById('req-number'),
        'special': document.getElementById('req-special')
    };
    
    Object.keys(requirements).forEach(key => {
        const element = reqElements[key];
        if (element) {
            if (requirements[key]) {
                element.classList.add('met');
                element.querySelector('i').className = 'bi bi-check-circle-fill';
            } else {
                element.classList.remove('met');
                element.querySelector('i').className = 'bi bi-circle';
            }
        }
    });
    
    return requirements;
}

function isPasswordValid(password) {
    const requirements = validatePassword(password);
    return Object.values(requirements).every(req => req === true);
}

// ========================================
// VALIDAÇÃO DE EMAIL
// ========================================

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// ========================================
// VALIDAÇÃO DE USUÁRIO
// ========================================

function isValidUsername(username) {
    // Apenas letras, números, underscore e hífen, 3-20 caracteres
    const usernameRegex = /^[a-zA-Z0-9_-]{3,20}$/;
    return usernameRegex.test(username);
}

// ========================================
// ANIMAÇÕES
// ========================================

function showErrorAnimation() {
    const card = document.getElementById('authCard');
    card.classList.add('shake');
    setTimeout(() => {
        card.classList.remove('shake');
    }, 500);
}

function showSuccessAnimation() {
    const card = document.getElementById('authCard');
    card.classList.add('success-scale');
}

// ========================================
// CONTROLE DE CARREGAMENTO
// ========================================

function setLoading(button, isLoading) {
    if (isLoading) {
        button.classList.add('loading');
        button.disabled = true;
    } else {
        button.classList.remove('loading');
        button.disabled = false;
    }
}

// ========================================
// LOGIN
// ========================================

async function handleLogin(event) {
    event.preventDefault();
    
    const usuario = document.getElementById('loginUsuario').value.trim();
    const senha = document.getElementById('loginSenha').value.trim();
    const loginBtn = document.getElementById('loginBtn');
    
    // Validações
    if (!usuario || !senha) {
        showNotification('Por favor, preencha todos os campos', 'error');
        showErrorAnimation();
        return;
    }
    
    setLoading(loginBtn, true);
    showNotification('Verificando credenciais...', 'info', 2000);
    
    try {
        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('usuario', usuario);
        formData.append('senha', senha);
        
        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`Erro HTTP: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Resposta do servidor não é JSON válido');
        }
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Login realizado com sucesso!', 'success', 2000);
            
            // Armazenar dados do usuário
            localStorage.setItem('user', JSON.stringify(data.user));
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('userRole', data.user.tipo);
            
            showSuccessAnimation();
            
            setTimeout(() => {
                showNotification('Redirecionando para o dashboard...', 'info', 1500);
                setTimeout(() => {
                  window.location.href = data.redirect || 'index.php';
                }, 1500);
            }, 1000);
        } else {
            showNotification(data.message || 'Usuário ou senha incorretos', 'error');
            showErrorAnimation();
        }
    } catch (error) {
        console.error('Erro na requisição:', error);
        let errorMessage = 'Erro de conexão. Verifique se o servidor está rodando.';
        
        if (error.message.includes('NetworkError')) {
            errorMessage = 'Erro de rede. Verifique se o Apache está rodando.';
        } else if (error.message.includes('JSON')) {
            errorMessage = 'Erro no servidor. Verifique os logs do PHP.';
        } else if (error.message.includes('HTTP: 404')) {
            errorMessage = 'Controlador não encontrado.';
        } else if (error.message.includes('HTTP: 500')) {
            errorMessage = 'Erro interno do servidor.';
        }
        
        showNotification(errorMessage, 'error');
        showErrorAnimation();
    } finally {
        setLoading(loginBtn, false);
    }
}

// ========================================
// CADASTRO
// ========================================

async function handleRegister(event) {
    event.preventDefault();
    
    const nome = document.getElementById('registerNome').value.trim();
    const email = document.getElementById('registerEmail').value.trim();
    const usuario = document.getElementById('registerUsuario').value.trim();
    const senha = document.getElementById('registerSenha').value.trim();
    const confirmSenha = document.getElementById('registerConfirmSenha').value.trim();
    const registerBtn = document.getElementById('registerBtn');
    
    // Validações
    if (!nome || !email || !usuario || !senha || !confirmSenha) {
        showNotification('Por favor, preencha todos os campos', 'error');
        showErrorAnimation();
        return;
    }
    
    if (nome.length < 3) {
        showNotification('Nome deve ter pelo menos 3 caracteres', 'error');
        showErrorAnimation();
        return;
    }
    
    if (!isValidEmail(email)) {
        showNotification('Email inválido', 'error');
        showErrorAnimation();
        return;
    }
    
    if (!isValidUsername(usuario)) {
        showNotification('Usuário deve conter 3-20 caracteres (letras, números, _ ou -)', 'warning');
        showErrorAnimation();
        return;
    }
    
    if (!isPasswordValid(senha)) {
        showNotification('Senha não atende aos requisitos', 'error');
        showErrorAnimation();
        return;
    }
    
    if (senha !== confirmSenha) {
        showNotification('As senhas não coincidem', 'error');
        showErrorAnimation();
        return;
    }
    
    setLoading(registerBtn, true);
    showNotification('Criando sua conta...', 'info', 2000);
    
    try {
        const formData = new FormData();
        formData.append('action', 'register');
        formData.append('nome', nome);
        formData.append('email', email);
        formData.append('usuario', usuario);
        formData.append('senha', senha);
        formData.append('tipo', 'usuario'); // Tipo padrão para novo usuário
        
        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`Erro HTTP: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Resposta do servidor não é JSON válido');
        }
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Conta criada com sucesso! Faça login para continuar.', 'success', 3000);
            
            // Limpar formulário
            document.getElementById('registerForm').reset();
            
            // Voltar para aba de login após 2 segundos
            setTimeout(() => {
                document.getElementById('loginTabBtn').click();
                showNotification('Bem-vindo! Faça login com suas credenciais.', 'info', 3000);
            }, 2000);
        } else {
            showNotification(data.message || 'Erro ao criar conta', 'error');
            showErrorAnimation();
        }
    } catch (error) {
        console.error('Erro na requisição:', error);
        let errorMessage = 'Erro de conexão. Verifique se o servidor está rodando.';
        
        if (error.message.includes('NetworkError')) {
            errorMessage = 'Erro de rede. Verifique se o Apache está rodando.';
        } else if (error.message.includes('JSON')) {
            errorMessage = 'Erro no servidor. Verifique os logs do PHP.';
        }
        
        showNotification(errorMessage, 'error');
        showErrorAnimation();
    } finally {
        setLoading(registerBtn, false);
    }
}

// ========================================
// INICIALIZAÇÃO DO DOCUMENTO
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    // Verificar se já está logado
        // A verificação de login agora é feita no lado do servidor (login.php)
    // const isLoggedIn = localStorage.getItem('isLoggedIn');
    // if (isLoggedIn === 'true') { ... }
    
    // Setup de abas
    setupTabs();
    
    // Setup de toggle de senha
    setupPasswordToggle();
    
    // Setup do formulário de login
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Setup do formulário de cadastro
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
    
    // Validação de senha em tempo real
    const senhaInput = document.getElementById('registerSenha');
    if (senhaInput) {
        senhaInput.addEventListener('input', (e) => {
            updatePasswordRequirements(e.target.value);
        });
    }
    
    // Suporte a Enter para enviar formulário
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            const activeTab = document.querySelector('.tab-content.active');
            const form = activeTab.querySelector('form');
            if (form && !form.querySelector('button[type="submit"]').disabled) {
                form.dispatchEvent(new Event('submit'));
            }
        }
    });
    
    // Efeito hover no card
    const authCard = document.getElementById('authCard');
    if (authCard) {
        authCard.addEventListener('mouseenter', function() {
            if (!this.classList.contains('success-scale')) {
                this.style.transform = 'translateY(-5px)';
            }
        });
        
        authCard.addEventListener('mouseleave', function() {
            if (!this.classList.contains('success-scale')) {
                this.style.transform = 'translateY(0)';
            }
        });
    }
    
    // Notificação de boas-vindas
    setTimeout(() => {
        showNotification('Bem-vindo ao Sistema COND', 'info', 3000);
    }, 500);
});