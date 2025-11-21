<?php
require_once 'auth_check.php';
require_once 'conexao.php';

// Título da Página
$titulo_pagina = "Novo Fornecedor";

// --- FUNÇÕES AUXILIARES (Sticky Form) ---
function valor($campo) {
    return isset($_POST[$campo]) ? htmlspecialchars($_POST[$campo]) : '';
}

// --- PROCESSAMENTO DO FORMULÁRIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $toast_msg = '';
    $toast_type = '';

    try {
        $nome = $_POST['nome'];
        $cnpj_cpf_bruto = $_POST['cnpj_cpf'];
        $email = $_POST['email'];
        
        $cnpj_cpf_limpo = preg_replace('/[^0-9]/', '', $cnpj_cpf_bruto);
        
        // --- VALIDAÇÕES PHP ---
        $erros = [];
        // Validação básica de tamanho (11 para CPF, 14 para CNPJ)
        if (strlen($cnpj_cpf_limpo) != 11 && strlen($cnpj_cpf_limpo) != 14 && !empty($cnpj_cpf_limpo)) {
             $erros[] = "O CNPJ/CPF deve ter 11 ou 14 dígitos.";
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "O formato do e-mail está incorreto.";
        }

        if (count($erros) > 0) {
            // Se houver erros, NÃO redireciona. Mostra o erro na própria página.
            $toast_msg = implode('<br>', $erros);
            $toast_type = "error";
        } else {
            // --- SALVAMENTO (Sem Erros) ---
            $pdo->beginTransaction();

            $sql_fornecedor = "INSERT INTO fornecedores (nome, cnpj_cpf, telefone, email) VALUES (:nome, :cnpj_cpf, :telefone, :email)";
            $stmt = $pdo->prepare($sql_fornecedor);
            $stmt->execute([
                ':nome' => $nome, 
                ':cnpj_cpf' => $cnpj_cpf_limpo, 
                ':telefone' => $_POST['telefone'], 
                ':email' => $email
            ]);

            $pdo->commit();
            
            // SUCESSO! Limpa o POST e redireciona com a mensagem
            $_POST = array(); 
            $toast_msg = "Fornecedor cadastrado com sucesso!";
            $toast_type = "success";
            
            $location = "fornecedores_lista.php?msg=" . urlencode($toast_msg) . "&type=" . $toast_type;
            header("Location: " . $location);
            exit;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        
        if (isset($stmt) && $stmt->errorCode() == '23000') {
            $toast_msg = "Atenção: Este CNPJ/CPF já está cadastrado no sistema.";
        } else {
            $toast_msg = "Erro: " . $e->getMessage();
        }
        $toast_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Novo Fornecedor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-100">

    <?php include 'menu.php'; ?>

    <?php include 'toast_handler.php'; ?>
    
    <?php
    // Este bloco é para erros que acontecem ANTES do redirecionamento
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($toast_msg)) {
        $bgColor = ($toast_type === 'error') ? 'bg-red-100 border border-red-300 text-red-800' : 'bg-blue-100 border border-blue-300 text-blue-800';
        $icon = ($toast_type === 'error') ? '<i class="bi bi-exclamation-triangle-fill"></i>' : '<i class="bi bi-info-circle-fill"></i>';
        
        echo "
        <div id='auto-toast' class='fixed top-5 left-1/2 -translate-x-1/2 z-[100] p-4 rounded-lg shadow-lg font-bold w-full max-w-md transition-opacity duration-300 $bgColor' role='alert'>
            <div class='flex items-center'>
                <span class='text-xl mr-3'>$icon</span>
                <span class='flex-grow'>$toast_msg</span>
                <button onclick='document.getElementById(\"auto-toast\").remove()' class='ml-4 text-2xl font-light opacity-70 hover:opacity-100'>&times;</button>
            </div>
        </div>
        ";
    }
    ?>

    <div class="container mx-auto mt-10 px-4 mb-10">
        <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
            
            <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">Cadastrar Fornecedor</h2>
            <form method="POST" action="" id="formFornecedor">
                
                <h3 class="text-lg font-semibold text-roxo-base mb-4">Dados do Fornecedor</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nome/Razão Social *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3" name="nome" type="text" required value="<?= valor('nome') ?>">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">CNPJ/CPF</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3" id="cnpj_cpf" name="cnpj_cpf" type="text" maxlength="18" oninput="mascaraCNPJCPF(this)" value="<?= valor('cnpj_cpf') ?>">
                        <p id="erro-cnpj_cpf" class="text-xs text-red-500 hidden mt-1 font-bold">CNPJ/CPF Inválido!</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Telefone</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3" name="telefone" type="text" maxlength="15" oninput="mascaraTelefone(this)" value="<?= valor('telefone') ?>">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">E-mail</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3" name="email" type="email" value="<?= valor('email') ?>">
                    </div>
                </div>

                <div class="mt-6">
                    <button class="w-full bg-roxo-base hover:bg-purple-700 text-white font-bold py-3 px-4 rounded focus:outline-none focus:shadow-outline transition duration-300" 
                            type="submit" id="btn-salvar">
                        Salvar Fornecedor
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function mascaraTelefone(i) { 
            var v = i.value; 
            v = v.replace(/\D/g, ""); 
            v = v.replace(/^(\d{2})(\d)/g, "($1) $2"); 
            v = v.replace(/(\d)(\d{4})$/, "$1-$2"); 
            i.value = v; 
        }
        
        function mascaraCNPJCPF(i) {
            var v = i.value.replace(/\D/g, '');
            if (v.length <= 11) { // CPF
                i.setAttribute("maxlength", "14");
                v = v.replace(/(\d{3})(\d)/, "$1.$2");
                v = v.replace(/(\d{3})(\d)/, "$1.$2");
                v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
            } else { // CNPJ
                i.setAttribute("maxlength", "18");
                v = v.replace(/^(\d{2})(\d)/, "$1.$2");
                v = v.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
                v = v.replace(/\.(\d{3})(\d)/, ".$1/$2");
                v = v.replace(/(\d{4})(\d)/, "$1-$2");
            }
            i.value = v;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.getElementById('auto-toast');
            if (toast) {
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 500); 
                }, 4000);
            }
        });
    </script>
</body>
</html>
