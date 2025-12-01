<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

$titulo_pagina = "Editar Fornecedor";
$toast_msg = ''; $toast_type = '';

function getValue($key, $data_array) { return htmlspecialchars($data_array[$key] ?? ''); }

$id_fornecedor_editar = (int)($_GET['id'] ?? 0);
try {
    $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id = ?");
    $stmt->execute([$id_fornecedor_editar]);
    $fornecedor = $stmt->fetch();
    if (!$fornecedor) { header("Location: fornecedores_lista.php?msg=" . urlencode("Erro: Fornecedor não encontrado.") . "&type=error"); exit; }
} catch (PDOException $e) { die("Erro ao carregar: " . $e->getMessage()); }

$dados_iniciais = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $fornecedor;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = $_POST['nome']; $email = $_POST['email'];
        $cnpj_cpf_limpo = preg_replace('/[^0-9]/', '', $_POST['cnpj_cpf']);
        $erros = [];
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $erros[] = "O formato do e-mail está incorreto."; }
        if (strlen($cnpj_cpf_limpo) != 11 && strlen($cnpj_cpf_limpo) != 14 && !empty($cnpj_cpf_limpo)) { $erros[] = "O CNPJ/CPF deve ter 11 ou 14 dígitos."; }

        if (count($erros) > 0) {
            $toast_msg = implode('<br>', $erros); $toast_type = "error";
        } else {
            $pdo->beginTransaction();
            $sql_fornecedor = "UPDATE fornecedores SET nome = :nome, cnpj_cpf = :cnpj_cpf, telefone = :telefone, email = :email WHERE id = :id";
            $stmt_forn = $pdo->prepare($sql_fornecedor);
            $stmt_forn->execute([':nome' => $nome, ':cnpj_cpf' => $cnpj_cpf_limpo, ':telefone' => $_POST['telefone'], ':email' => $email, ':id' => $id_fornecedor_editar]);
            $pdo->commit();
            
            $msg_sucesso = "Fornecedor atualizado com sucesso!";
            header("Location: fornecedores_lista.php?msg=" . urlencode($msg_sucesso) . "&type=success");
            exit;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if (isset($stmt_forn) && $stmt_forn->errorCode() == '23000') { $toast_msg = "Atenção: Este CNPJ/CPF já está cadastrado."; } 
        else { $toast_msg = "Erro: " . $e->getMessage(); }
        $toast_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Fornecedor - COND</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-50 font-sans text-gray-900">

    <?php include 'menu.php'; ?>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($toast_msg)): ?>
        <div id='auto-toast' class='fixed top-5 right-5 z-[100] p-4 rounded-lg shadow-lg font-bold w-full max-w-sm transition-all duration-500 border bg-red-100 border-red-200 text-red-800' role='alert'>
            <div class='flex items-center'><span class='text-xl mr-3'><i class="bi bi-exclamation-triangle-fill"></i></span><span class='flex-grow text-sm'><?= $toast_msg ?></span><button onclick='document.getElementById("auto-toast").remove()' class='ml-4 text-xl opacity-60 hover:opacity-100'>&times;</button></div>
        </div>
    <?php endif; ?>

    <div class="md:ml-64 transition-all duration-300 flex flex-col min-h-screen">
        <div class="bg-white shadow-sm p-4 md:hidden flex justify-between items-center sticky top-0 z-30"><span class="font-bold text-xl text-roxo-base">COND</span><button onclick="toggleSidebar()" class="text-gray-600 focus:outline-none"><i class="bi bi-list text-3xl"></i></button></div>

        <main class="flex-1 p-6">
            <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div><h1 class="text-2xl font-bold text-gray-800">Editar Fornecedor</h1><p class="text-sm text-gray-500">Atualize os dados do parceiro comercial.</p></div>
                <a href="fornecedores_lista.php" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-roxo-base transition-colors"><i class="bi bi-arrow-left mr-2"></i> Voltar para Lista</a>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 md:p-8">
                    <form method="POST" action="">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center"><div class="w-8 h-8 rounded-full bg-purple-100 text-roxo-base flex items-center justify-center mr-3 text-sm">1</div>Dados Cadastrais</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Nome/Razão Social *</label><div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="bi bi-building"></i></div><input class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" name="nome" type="text" required value="<?= getValue('nome', $dados_iniciais) ?>"></div></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">CNPJ/CPF</label><div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="bi bi-card-text"></i></div><input class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" id="cnpj_cpf" name="cnpj_cpf" type="text" maxlength="18" oninput="mascaraCNPJCPF(this)" value="<?= getValue('cnpj_cpf', $dados_iniciais) ?>"></div></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Telefone</label><div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="bi bi-telephone"></i></div><input class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" name="telefone" type="text" maxlength="15" oninput="mascaraTelefone(this)" value="<?= getValue('telefone', $dados_iniciais) ?>"></div></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label><div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"><i class="bi bi-envelope"></i></div><input class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" name="email" type="text" value="<?= getValue('email', $dados_iniciais) ?>"></div></div>
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
        function mascaraCNPJCPF(i) { var v = i.value.replace(/\D/g, ''); if (v.length <= 11) { i.setAttribute("maxlength", "14"); v = v.replace(/(\d{3})(\d)/, "$1.$2"); v = v.replace(/(\d{3})(\d)/, "$1.$2"); v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2"); } else { i.setAttribute("maxlength", "18"); v = v.replace(/^(\d{2})(\d)/, "$1.$2"); v = v.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3"); v = v.replace(/\.(\d{3})(\d)/, ".$1/$2"); v = v.replace(/(\d{4})(\d)/, "$1-$2"); } i.value = v; }
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.getElementById('auto-toast');
            if (toast) { setTimeout(() => { toast.classList.add('opacity-0', '-translate-y-5'); setTimeout(() => toast.remove(), 500); }, 4000); }
        });
    </script>
    <?php include 'toast_handler.php'; ?>
</body>
</html>