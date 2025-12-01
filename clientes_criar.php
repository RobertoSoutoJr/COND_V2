<?php
require_once 'auth_check.php';
require_once 'conexao.php';

// Título da Página
$titulo_pagina = "Novo Cliente";

// --- FUNÇÕES AUXILIARES ---
function validaCPF($cpf) {
    $cpf = preg_replace( '/[^0-9]/is', '', $cpf );
    if (strlen($cpf) != 11) return false;
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) { $d += $cpf[$c] * (($t + 1) - $c); }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}
function valor($campo) {
    return isset($_POST[$campo]) ? htmlspecialchars($_POST[$campo]) : '';
}

// --- PROCESSAMENTO DO FORMULÁRIO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $toast_msg = '';
    $toast_type = '';

    try {
        $nome = $_POST['nome'];
        $cpf_bruto = $_POST['cpf'];
        $email = $_POST['email'];
        
        $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf_bruto);
        $cep_limpo = preg_replace('/[^0-9]/', '', $_POST['cep']);
        
        // --- VALIDAÇÕES ---
        $erros = [];
        if (!validaCPF($cpf_limpo)) {
            $erros[] = "O CPF informado é inválido.";
        }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "O formato do e-mail está incorreto.";
        }

        if (count($erros) > 0) {
            $toast_msg = implode('<br>', $erros);
            $toast_type = "error";
        } else {
            // --- SALVAMENTO ---
            $pdo->beginTransaction();

            $sql_cliente = "INSERT INTO clientes (nome, cpf, telefone, email) VALUES (:nome, :cpf, :telefone, :email)";
            $stmt = $pdo->prepare($sql_cliente);
            $stmt->execute([
                ':nome' => $nome, ':cpf' => $cpf_limpo, ':telefone' => $_POST['telefone'], ':email' => $email
            ]);
            $cliente_id = $pdo->lastInsertId();

            $sql_endereco = "INSERT INTO enderecos (cliente_id, cep, logradouro, numero, complemento, bairro, cidade, estado) 
                             VALUES (:cliente_id, :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :estado)";
            $stmt = $pdo->prepare($sql_endereco);
            $stmt->execute([
                ':cliente_id' => $cliente_id, ':cep' => $cep_limpo, ':logradouro' => $_POST['logradouro'],
                ':numero' => $_POST['numero'], ':complemento' => $_POST['complemento'],
                ':bairro' => $_POST['bairro'], ':cidade' => $_POST['cidade'], ':estado' => $_POST['estado']
            ]);

            $pdo->commit();
            
            // Sucesso
            $_POST = array(); 
            $msg_sucesso = "Cliente cadastrado com sucesso!";
            header("Location: clientes_lista.php?msg=" . urlencode($msg_sucesso) . "&type=success");
            exit;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        
        if (isset($stmt) && $stmt->errorCode() == '23000') {
            $toast_msg = "Atenção: Este CPF já está cadastrado.";
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
    <title>Novo Cliente - COND</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-50 font-sans text-gray-900">

    <?php include 'menu.php'; ?>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($toast_msg)) {
        $bgColor = ($toast_type === 'error') ? 'bg-red-100 border-red-200 text-red-800' : 'bg-blue-100 border-blue-200 text-blue-800';
        $icon = ($toast_type === 'error') ? '<i class="bi bi-exclamation-triangle-fill"></i>' : '<i class="bi bi-info-circle-fill"></i>';
        echo "
        <div id='auto-toast' class='fixed top-5 right-5 z-[100] p-4 rounded-lg shadow-lg font-bold w-full max-w-sm transition-all duration-500 border $bgColor' role='alert'>
            <div class='flex items-center'>
                <span class='text-xl mr-3'>$icon</span>
                <span class='flex-grow text-sm'>$toast_msg</span>
                <button onclick='document.getElementById(\"auto-toast\").remove()' class='ml-4 text-xl opacity-60 hover:opacity-100'>&times;</button>
            </div>
        </div>";
    }
    ?>

    <div class="md:ml-64 transition-all duration-300 flex flex-col min-h-screen">

        <div class="bg-white shadow-sm p-4 md:hidden flex justify-between items-center sticky top-0 z-30">
            <span class="font-bold text-xl text-roxo-base">COND</span>
            <button onclick="toggleSidebar()" class="text-gray-600 focus:outline-none">
                <i class="bi bi-list text-3xl"></i>
            </button>
        </div>

        <main class="flex-1 p-6">
            
            <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Novo Cliente</h1>
                    <p class="text-sm text-gray-500">Preencha os dados abaixo para cadastrar um cliente.</p>
                </div>
                <a href="clientes_lista.php" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-roxo-base transition-colors">
                    <i class="bi bi-arrow-left mr-2"></i> Voltar para Lista
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 md:p-8">
                    <form method="POST" action="" id="formCliente">
                        
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <div class="w-8 h-8 rounded-full bg-purple-100 text-roxo-base flex items-center justify-center mr-3 text-sm">1</div>
                                Dados Pessoais
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo *</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="bi bi-person"></i></div>
                                        <input class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" name="nome" type="text" placeholder="Ex: Maria Silva" required value="<?= valor('nome') ?>">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">CPF *</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="bi bi-card-heading"></i></div>
                                        <input class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" id="cpf" name="cpf" type="text" maxlength="14" placeholder="000.000.000-00" oninput="mascaraCPF(this)" onblur="validarCPF(this)" required value="<?= valor('cpf') ?>">
                                    </div>
                                    <p id="erro-cpf" class="text-xs text-red-500 mt-1 font-semibold hidden">CPF Inválido!</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Celular (WhatsApp)</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="bi bi-whatsapp"></i></div>
                                        <input class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" name="telefone" type="text" maxlength="15" placeholder="(00) 00000-0000" oninput="mascaraTelefone(this)" value="<?= valor('telefone') ?>">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="bi bi-envelope"></i></div>
                                        <input class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" name="email" type="email" placeholder="exemplo@email.com" value="<?= valor('email') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="border-gray-100 my-8">

                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <div class="w-8 h-8 rounded-full bg-purple-100 text-roxo-base flex items-center justify-center mr-3 text-sm">2</div>
                                Endereço
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">CEP *</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="bi bi-geo-alt"></i></div>
                                        <input class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" id="cep" name="cep" type="text" maxlength="9" placeholder="00000-000" oninput="mascaraCep(this)" onblur="buscarCep(this.value)" required value="<?= valor('cep') ?>">
                                        <div id="loading-cep" class="absolute right-3 top-2.5 hidden">
                                            <svg class="animate-spin h-5 w-5 text-roxo-base" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Logradouro *</label>
                                    <input class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:outline-none" id="logradouro" name="logradouro" type="text" required readonly value="<?= valor('logradouro') ?>">
                                </div>

                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Número *</label>
                                    <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" id="numero" name="numero" type="text" required value="<?= valor('numero') ?>">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Bairro *</label>
                                    <input class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:outline-none" id="bairro" name="bairro" type="text" required readonly value="<?= valor('bairro') ?>">
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cidade *</label>
                                    <input class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:outline-none" id="cidade" name="cidade" type="text" required readonly value="<?= valor('cidade') ?>">
                                </div>

                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">UF *</label>
                                    <input class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:outline-none" id="estado" name="estado" type="text" required readonly value="<?= valor('estado') ?>">
                                </div>

                                <div class="md:col-span-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                                    <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" name="complemento" type="text" placeholder="Apto, Bloco, etc." value="<?= valor('complemento') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end pt-6 border-t border-gray-100">
                            <button class="bg-roxo-base hover:bg-purple-700 text-white font-bold py-3 px-8 rounded-lg shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center" type="submit" id="btn-salvar">
                                <i class="bi bi-check-lg mr-2 text-xl"></i> Salvar Cliente
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // MÁSCARAS
        function mascaraCPF(i) { var v = i.value; if(isNaN(v[v.length-1])){ i.value = v.substring(0, v.length-1); return; } i.setAttribute("maxlength", "14"); v = v.replace(/\D/g, ""); v = v.replace(/(\d{3})(\d)/, "$1.$2"); v = v.replace(/(\d{3})(\d)/, "$1.$2"); v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2"); i.value = v; }
        function mascaraTelefone(i) { var v = i.value; v = v.replace(/\D/g, ""); v = v.replace(/^(\d{2})(\d)/g, "($1) $2"); v = v.replace(/(\d)(\d{4})$/, "$1-$2"); i.value = v; }
        function mascaraCep(i) { var v = i.value; v = v.replace(/\D/g, ""); v = v.replace(/^(\d{5})(\d)/, "$1-$2"); i.value = v; }

        // VALIDAÇÃO CPF
        function validarCPF(el) { 
            const cpfLimpo = el.value.replace(/\D/g, ''); 
            const erroMsg = document.getElementById('erro-cpf'); 
            const btnSalvar = document.getElementById('btn-salvar'); 
            
            if(cpfLimpo.length !== 11 || /^(\d)\1+$/.test(cpfLimpo)) { return erroCPF(el, erroMsg, btnSalvar); } 
            
            let soma = 0, resto; 
            for (let i = 1; i <= 9; i++) soma += parseInt(cpfLimpo.substring(i-1, i)) * (11 - i); 
            resto = (soma * 10) % 11; 
            if (resto == 10 || resto == 11) resto = 0; 
            if (resto != parseInt(cpfLimpo.substring(9, 10))) { return erroCPF(el, erroMsg, btnSalvar); } 
            
            soma = 0; 
            for (let i = 1; i <= 10; i++) soma += parseInt(cpfLimpo.substring(i-1, i)) * (12 - i); 
            resto = (soma * 10) % 11; 
            if (resto == 10 || resto == 11) resto = 0; 
            if (resto != parseInt(cpfLimpo.substring(10, 11))) { return erroCPF(el, erroMsg, btnSalvar); } 
            
            // Sucesso
            el.classList.remove('border-red-500', 'focus:ring-red-500'); 
            el.classList.add('border-green-500', 'focus:ring-green-500'); 
            erroMsg.classList.add('hidden'); 
            btnSalvar.disabled = false; 
            btnSalvar.classList.remove('opacity-50', 'cursor-not-allowed'); 
        }
        function erroCPF(el, msg, btn) { 
            el.classList.add('border-red-500', 'focus:ring-red-500'); 
            el.classList.remove('border-green-500', 'focus:ring-green-500'); 
            msg.classList.remove('hidden'); 
            btn.disabled = true; 
            btn.classList.add('opacity-50', 'cursor-not-allowed'); 
        }

        // BUSCA CEP (VIACEP)
        function buscarCep(cep) { 
            cep = cep.replace(/\D/g, ''); 
            if (cep.length === 8) { 
                document.getElementById('loading-cep').classList.remove('hidden'); 
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => { 
                    document.getElementById('loading-cep').classList.add('hidden'); 
                    if (!data.erro) { 
                        document.getElementById('logradouro').value = data.logradouro; 
                        document.getElementById('bairro').value = data.bairro; 
                        document.getElementById('cidade').value = data.localidade; 
                        document.getElementById('estado').value = data.uf; 
                        document.getElementById('numero').focus(); 
                    } else { 
                        alert("CEP não encontrado."); 
                        limparFormularioCep(); 
                    } 
                })
                .catch(() => { 
                    document.getElementById('loading-cep').classList.add('hidden'); 
                    alert("Erro ao buscar CEP."); 
                }); 
            } 
        }
        function limparFormularioCep() { 
            document.getElementById('logradouro').value = ""; 
            document.getElementById('bairro').value = ""; 
            document.getElementById('cidade').value = ""; 
            document.getElementById('estado').value = ""; 
        }

        // AUTO-TOAST TIMER
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.getElementById('auto-toast');
            if (toast) {
                setTimeout(() => {
                    toast.classList.add('opacity-0', '-translate-y-5');
                    setTimeout(() => toast.remove(), 500); 
                }, 4000);
            }
        });
    </script>

    <?php include 'toast_handler.php'; ?>
</body>
</html>