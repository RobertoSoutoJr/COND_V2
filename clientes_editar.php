<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

// Título da Página
$titulo_pagina = "Editar Cliente";

// Variáveis de Mensagem
$toast_msg = '';
$toast_type = '';

// --- Função para 'Sticky Form' ---
function getValue($key, $data_array) {
    return htmlspecialchars($data_array[$key] ?? '');
}

// --- 1. CARREGAR DADOS DO CLIENTE ---
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
        header("Location: clientes_lista.php?msg=" . urlencode("Erro: Cliente não encontrado.") . "&type=error");
        exit;
    }
} catch (PDOException $e) {
    die("Erro ao carregar cliente: " . $e->getMessage());
}

$dados_iniciais = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $cliente;


// --- 2. LÓGICA DE SALVAMENTO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $cpf_limpo = preg_replace('/[^0-9]/', '', $_POST['cpf']);
        $cep_limpo = preg_replace('/[^0-9]/', '', $_POST['cep']);

        // --- BLINDAGEM ---
        $erros = [];
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "O formato do e-mail está incorreto.";
        }

        if (count($erros) > 0) {
            $toast_msg = implode('<br>', $erros);
            $toast_type = "error";
        } else {
            $pdo->beginTransaction();

            // 1. Atualiza Cliente
            $sql_cliente = "UPDATE clientes SET nome = :nome, telefone = :telefone, email = :email WHERE id = :id";
            $stmt_cli = $pdo->prepare($sql_cliente);
            $stmt_cli->execute([
                ':nome' => $nome, ':telefone' => $_POST['telefone'], ':email' => $email, ':id' => $id_cliente_editar
            ]);

            // 2. Atualiza Endereço
            $sql_endereco = "UPDATE enderecos SET cep = :cep, logradouro = :log, numero = :num, complemento = :comp, bairro = :bairro, cidade = :cid, estado = :est 
                             WHERE cliente_id = :id";
            $stmt_end = $pdo->prepare($sql_endereco);
            $stmt_end->execute([
                ':cep' => $cep_limpo, ':log' => $_POST['logradouro'], ':num' => $_POST['numero'], ':comp' => $_POST['complemento'],
                ':bairro' => $_POST['bairro'], ':cid' => $_POST['cidade'], ':est' => $_POST['estado'], ':id' => $id_cliente_editar
            ]);

            $pdo->commit();
            
            $msg_sucesso = "Cliente ID #$id_cliente_editar atualizado com sucesso!";
            header("Location: clientes_lista.php?msg=" . urlencode($msg_sucesso) . "&type=success");
            exit;
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        
        if (empty($toast_msg)) {
            $toast_msg = "Erro ao atualizar: " . $e->getMessage();
            $toast_type = "error";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente - COND</title>
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
                    <h1 class="text-2xl font-bold text-gray-800">Editar Cliente</h1>
                    <p class="text-sm text-gray-500">Atualize os dados cadastrais do cliente.</p>
                </div>
                <a href="clientes_lista.php" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-roxo-base transition-colors">
                    <i class="bi bi-arrow-left mr-2"></i> Voltar para Lista
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 md:p-8">
                    
                    <form method="POST" action="">
                        
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
                                        <input class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" name="nome" type="text" required value="<?= getValue('nome', $dados_iniciais) ?>">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">CPF (Somente Leitura)</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="bi bi-card-heading"></i></div>
                                        <input class="w-full pl-10 pr-4 py-2 bg-gray-100 border border-gray-300 rounded-lg text-gray-500 cursor-not-allowed focus:outline-none" name="cpf" type="text" maxlength="14" required readonly value="<?= getValue('cpf', $dados_iniciais) ?>">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Celular (WhatsApp)</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="bi bi-whatsapp"></i></div>
                                        <input class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" name="telefone" type="text" oninput="mascaraTelefone(this)" value="<?= getValue('telefone', $dados_iniciais) ?>">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="bi bi-envelope"></i></div>
                                        <input class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" name="email" type="email" value="<?= getValue('email', $dados_iniciais) ?>">
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
                                        <input class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" id="cep" name="cep" type="text" maxlength="9" oninput="mascaraCep(this)" onblur="buscarCep(this.value)" required value="<?= getValue('cep', $dados_iniciais) ?>">
                                        <div id="loading-cep" class="absolute right-3 top-2.5 hidden">
                                            <svg class="animate-spin h-5 w-5 text-roxo-base" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Logradouro *</label>
                                    <input class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:outline-none" id="logradouro" name="logradouro" type="text" required readonly value="<?= getValue('logradouro', $dados_iniciais) ?>">
                                </div>

                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Número *</label>
                                    <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" name="numero" type="text" required value="<?= getValue('numero', $dados_iniciais) ?>">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Bairro *</label>
                                    <input class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:outline-none" id="bairro" name="bairro" type="text" required readonly value="<?= getValue('bairro', $dados_iniciais) ?>">
                                </div>

                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cidade *</label>
                                    <input class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:outline-none" id="cidade" name="cidade" type="text" required readonly value="<?= getValue('cidade', $dados_iniciais) ?>">
                                </div>

                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">UF *</label>
                                    <input class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed focus:outline-none" id="estado" name="estado" type="text" required readonly value="<?= getValue('estado', $dados_iniciais) ?>">
                                </div>

                                <div class="md:col-span-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                                    <input class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" name="complemento" type="text" value="<?= getValue('complemento', $dados_iniciais) ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end pt-6 border-t border-gray-100">
                            <button class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-8 rounded-lg shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center" type="submit">
                                <i class="bi bi-check-lg mr-2 text-xl"></i> Salvar Alterações
                            </button>
                        </div>
                        
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function mascaraTelefone(i) { var v = i.value; v = v.replace(/\D/g, ""); v = v.replace(/^(\d{2})(\d)/g, "($1) $2"); v = v.replace(/(\d)(\d{4})$/, "$1-$2"); i.value = v; }
        function mascaraCep(i) { var v = i.value; v = v.replace(/\D/g, ""); v = v.replace(/^(\d{5})(\d)/, "$1-$2"); i.value = v; }
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
                }).catch(() => { 
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
</body>
</html>