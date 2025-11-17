<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

// Título da Página
$titulo_pagina = "Editar Cliente";

// Variáveis de Mensagem
$toast_msg = '';
$toast_type = '';

// --- Função para 'Sticky Form' (Carrega dados do POST em caso de erro) ---
function getValue($key, $data_array) {
    return htmlspecialchars($data_array[$key] ?? '');
}

// --- 1. CARREGAR DADOS DO CLIENTE (DO BANCO) ---
$id_cliente_editar = (int)($_GET['id'] ?? 0);

try {
    $stmt = $pdo->prepare("
        SELECT c.*, e.cep, e.logradouro, e.numero, e.complemento, e.bairro, e.cidade, e.estado 
        FROM clientes c 
        LEFT JOIN enderecos e ON c.id = e.cliente_id 
        WHERE c.id = ?
    ");
    $stmt->execute([$id_cliente_editar]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        // Redireciona com Toast de erro se o ID não for válido
        header("Location: clientes_lista.php?msg=" . urlencode("Erro: Cliente não encontrado.") . "&type=error");
        exit;
    }
} catch (PDOException $e) {
    die("Erro ao carregar cliente: " . $e->getMessage());
}

// Inicializa array para o Sticky Form (prioriza POST em caso de erro)
$dados_iniciais = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $cliente;


// --- 2. LÓGICA DE SALVAMENTO (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $cpf_limpo = preg_replace('/[^0-9]/', '', $_POST['cpf']);
        $cep_limpo = preg_replace('/[^0-9]/', '', $_POST['cep']);

        // --- BLINDAGEM PHP ---
        $erros = [];
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "O formato do e-mail está incorreto.";
        }
        // Nota: O CPF não é validado aqui, pois é readonly.

        if (count($erros) > 0) {
            // Se houver erros de validação, não redireciona, mostra o toast local
            $toast_msg = implode('<br>', $erros);
            $toast_type = "error";
            throw new Exception("Validação falhou."); // Apenas para pular o SQL
        }
        // --- FIM BLINDAGEM ---

        $pdo->beginTransaction();

        // 1. Atualiza Cliente
        $sql_cliente = "UPDATE clientes SET nome = :nome, telefone = :telefone, email = :email WHERE id = :id";
        $stmt_cli = $pdo->prepare($sql_cliente);
        $stmt_cli->execute([
            ':nome' => $nome, ':telefone' => $_POST['telefone'], ':email' => $email, ':id' => $id_cliente_editar
        ]);

        // 2. Atualiza Endereço (usamos o ID do cliente carregado)
        $sql_endereco = "UPDATE enderecos SET cep = :cep, logradouro = :log, numero = :num, complemento = :comp, bairro = :bairro, cidade = :cid, estado = :est 
                         WHERE cliente_id = :id";
        $stmt_end = $pdo->prepare($sql_endereco);
        $stmt_end->execute([
            ':cep' => $cep_limpo, ':log' => $_POST['logradouro'], ':num' => $_POST['numero'], ':comp' => $_POST['complemento'],
            ':bairro' => $_POST['bairro'], ':cid' => $_POST['cidade'], ':est' => $_POST['estado'], ':id' => $id_cliente_editar
        ]);

        $pdo->commit();
        
        // SUCESSO! Redireciona para a lista
        $msg_sucesso = "Cliente ID #$id_cliente_editar atualizado com sucesso!";
        header("Location: clientes_lista.php?msg=" . urlencode($msg_sucesso) . "&type=success");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        
        if (empty($toast_msg)) { // Se o erro não veio da validação, é um erro de banco (ex: CPF duplicado)
            $toast_msg = "Erro ao atualizar: " . $e->getMessage();
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
    <title>Editar Cliente</title>
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
            
            <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">Editar Cliente</h2>
            
            <form method="POST" action="">
                
                <h3 class="text-lg font-semibold text-roxo-base mb-4">Dados Pessoais</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nome Completo *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3" name="nome" type="text" required value="<?= getValue('nome', $dados_iniciais) ?>">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">CPF (Somente números) *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 bg-gray-100" name="cpf" type="text" maxlength="14" required readonly value="<?= getValue('cpf', $dados_iniciais) ?>">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Celular (WhatsApp)</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3" name="telefone" type="text" oninput="mascaraTelefone(this)" value="<?= getValue('telefone', $dados_iniciais) ?>">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">E-mail</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3" name="email" type="email" value="<?= getValue('email', $dados_iniciais) ?>">
                    </div>
                </div>

                <h3 class="text-lg font-semibold text-roxo-base mb-4">Endereço</h3>
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2">CEP *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3" id="cep" name="cep" type="text" maxlength="9" oninput="mascaraCep(this)" onblur="buscarCep(this.value)" required value="<?= getValue('cep', $dados_iniciais) ?>">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Logradouro *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 bg-gray-50" id="logradouro" name="logradouro" type="text" required readonly value="<?= getValue('logradouro', $dados_iniciais) ?>">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Número *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3" name="numero" type="text" required value="<?= getValue('numero', $dados_iniciais) ?>">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Bairro *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 bg-gray-50" id="bairro" name="bairro" type="text" required readonly value="<?= getValue('bairro', $dados_iniciais) ?>">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Cidade *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 bg-gray-50" id="cidade" name="cidade" type="text" required readonly value="<?= getValue('cidade', $dados_iniciais) ?>">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-gray-700 text-sm font-bold mb-2">UF *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 bg-gray-50" id="estado" name="estado" type="text" required readonly value="<?= getValue('estado', $dados_iniciais) ?>">
                    </div>
                    <div class="md:col-span-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Complemento</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3" name="complemento" type="text" value="<?= getValue('complemento', $dados_iniciais) ?>">
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
        // Scripts para máscara e ViaCEP aqui
        function mascaraTelefone(i) { var v = i.value; v = v.replace(/\D/g, ""); v = v.replace(/^(\d{2})(\d)/g, "($1) $2"); v = v.replace(/(\d)(\d{4})$/, "$1-$2"); i.value = v; }
        function mascaraCep(i) { var v = i.value; v = v.replace(/\D/g, ""); v = v.replace(/^(\d{5})(\d)/, "$1-$2"); i.value = v; }
        function buscarCep(cep) { cep = cep.replace(/\D/g, ''); if (cep.length === 8) { fetch(`https://viacep.com.br/ws/${cep}/json/`).then(response => response.json()).then(data => { if (!data.erro) { document.getElementById('logradouro').value = data.logradouro; document.getElementById('bairro').value = data.bairro; document.getElementById('cidade').value = data.localidade; document.getElementById('estado').value = data.uf; document.getElementById('numero').focus(); } else { alert("CEP não encontrado."); limparFormularioCep(); } }).catch(() => { alert("Erro ao buscar CEP."); }); } }
        function limparFormularioCep() { document.getElementById('logradouro').value = ""; document.getElementById('bairro').value = ""; document.getElementById('cidade').value = ""; document.getElementById('estado').value = ""; }
    </script>
    
    <script>
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