<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

// Título da Página
$titulo_pagina = "Editar Fornecedor";

// Variáveis de Mensagem
$toast_msg = '';
$toast_type = '';

// --- Função para 'Sticky Form' (Carrega dados do POST em caso de erro) ---
function getValue($key, $data_array) {
    return htmlspecialchars($data_array[$key] ?? '');
}

// --- 1. CARREGAR DADOS DO FORNECEDOR (DO BANCO) ---
$id_fornecedor_editar = (int)($_GET['id'] ?? 0);

try {
    $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id = ?");
    $stmt->execute([$id_fornecedor_editar]);
    $fornecedor = $stmt->fetch();

    if (!$fornecedor) {
        // Redireciona com Toast de erro se o ID não for válido
        header("Location: fornecedores_lista.php?msg=" . urlencode("Erro: Fornecedor não encontrado.") . "&type=error");
        exit;
    }
} catch (PDOException $e) {
    die("Erro ao carregar fornecedor: " . $e->getMessage());
}

// Inicializa array para o Sticky Form (prioriza POST em caso de erro)
$dados_iniciais = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $fornecedor;


// --- 2. LÓGICA DE SALVAMENTO (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $cnpj_cpf_limpo = preg_replace('/[^0-9]/', '', $_POST['cnpj_cpf']);

        // --- BLINDAGEM PHP ---
        $erros = [];
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "O formato do e-mail está incorreto.";
        }
        // Validação básica de tamanho (11 para CPF, 14 para CNPJ)
        if (strlen($cnpj_cpf_limpo) != 11 && strlen($cnpj_cpf_limpo) != 14 && !empty($cnpj_cpf_limpo)) {
             $erros[] = "O CNPJ/CPF deve ter 11 ou 14 dígitos.";
        }

        if (count($erros) > 0) {
            // Se houver erros de validação, não redireciona, mostra o toast local
            $toast_msg = implode('<br>', $erros);
            $toast_type = "error";
            throw new Exception("Validação falhou."); // Apenas para pular o SQL
        }
        // --- FIM BLINDAGEM ---

        $pdo->beginTransaction();

        // 1. Atualiza Fornecedor
        $sql_fornecedor = "UPDATE fornecedores SET nome = :nome, cnpj_cpf = :cnpj_cpf, telefone = :telefone, email = :email WHERE id = :id";
        $stmt_forn = $pdo->prepare($sql_fornecedor);
        $stmt_forn->execute([
            ':nome' => $nome, 
            ':cnpj_cpf' => $cnpj_cpf_limpo, 
            ':telefone' => $_POST['telefone'], 
            ':email' => $email, 
            ':id' => $id_fornecedor_editar
        ]);

        $pdo->commit();
        
        // SUCESSO! Redireciona para a lista
        $msg_sucesso = "Fornecedor ID #$id_fornecedor_editar atualizado com sucesso!";
        header("Location: fornecedores_lista.php?msg=" . urlencode($msg_sucesso) . "&type=success");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        
        if (empty($toast_msg)) { // Se o erro não veio da validação, é um erro de banco (ex: CNPJ/CPF duplicado)
            if (isset($stmt_forn) && $stmt_forn->errorCode() == '23000') {
                $toast_msg = "Atenção: Este CNPJ/CPF já está cadastrado no sistema.";
            } else {
                $toast_msg = "Erro ao atualizar: " . $e->getMessage();
            }
            $toast_type = "error";
        }
        // Se a validação falhar, o script continua e mostra o toast local
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Fornecedor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-100">

    <?php include 'menu.php'; ?>

    <?php
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
            
            <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">Editar Fornecedor</h2>
            
            <form method="POST" action="">
                
                <h3 class="text-lg font-semibold text-roxo-base mb-4">Dados do Fornecedor</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nome/Razão Social *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3" name="nome" type="text" required value="<?= getValue('nome', $dados_iniciais) ?>">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">CNPJ/CPF</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3" id="cnpj_cpf" name="cnpj_cpf" type="text" maxlength="18" oninput="mascaraCNPJCPF(this)" value="<?= getValue('cnpj_cpf', $dados_iniciais) ?>">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Telefone</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3" name="telefone" type="text" maxlength="15" oninput="mascaraTelefone(this)" value="<?= getValue('telefone', $dados_iniciais) ?>">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">E-mail</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3" name="email" type="email" value="<?= getValue('email', $dados_iniciais) ?>">
                    </div>
                </div>

                <div class="mt-6">
                    <button class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-4 rounded transition" type="submit">
                        Salvar Alterações
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
