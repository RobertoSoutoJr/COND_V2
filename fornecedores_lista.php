<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

$titulo_pagina = "Lista de Fornecedores";

try {
    $sql = "SELECT 
                f.id, f.nome, f.cnpj_cpf, f.telefone, f.email,
                (SELECT COUNT(*) FROM entradas_produto WHERE fornecedor_id = f.id) as total_entradas,
                (SELECT COUNT(*) FROM entradas_produto WHERE fornecedor_id = f.id AND status_pagamento = 'PENDENTE') as pendentes
            FROM fornecedores f
            ORDER BY f.nome ASC";
    $stmt = $pdo->query($sql);
    $fornecedores = $stmt->fetchAll();
    $total_itens = count($fornecedores);
} catch (PDOException $e) {
    die("Erro ao listar fornecedores: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Fornecedores - COND</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        @media (max-width: 767px) {
            .tabela-responsiva thead { display: none; }
            .tabela-responsiva, .tabela-responsiva tbody, .tabela-responsiva tr { display: block; width: 100%; }
            .tabela-responsiva tr { margin-bottom: 1rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; overflow: hidden; background: #fff; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05); }
            .tabela-responsiva td { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; text-align: right; width: 100%; }
            .tabela-responsiva td::before { content: attr(data-label); font-weight: 600; text-align: left; padding-right: 1rem; color: #6b7280; flex-shrink: 0; font-size: 0.875rem; }
            .tabela-responsiva tr td:last-child { border-bottom: 0; }
            .tabela-responsiva td.celula-acao { display: block; }
            .tabela-responsiva td.celula-acao::before { display: none; }
            .tabela-responsiva td.celula-acao > div { justify-content: center; flex-wrap: wrap; gap: 0.5rem; }
            .tabela-responsiva td.celula-fornecedor { display: block; text-align: left; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; }
            .tabela-responsiva td.celula-fornecedor::before { display: none; }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-900">

    <?php include 'menu.php'; ?>

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
                    <h1 class="text-2xl font-bold text-gray-800">Fornecedores</h1>
                    <p class="text-sm text-gray-500">Gerencie seus parceiros e histórico de compras (<?= $total_itens ?>).</p>
                </div>
                <a href="fornecedores_criar.php" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white transition-colors bg-roxo-base rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 shadow-md">
                    <i class="bi bi-person-plus-fill mr-2"></i> Novo Fornecedor
                </a>
            </div>

            <div class="mb-6 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="bi bi-search text-gray-400"></i>
                </div>
                <input type="text" id="filtroBusca" onkeyup="filtrarTabela()" 
                       placeholder="Filtrar por nome ou CNPJ/CPF..." 
                       class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all bg-white shadow-sm text-sm">
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden overflow-x-auto tabela-responsiva">
                <table class="min-w-full leading-normal">
                    <thead class="hidden md:table-header-group bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" colspan="2">Fornecedor</th>
                            <th class="py-3 px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Contato</th>
                            <th class="py-3 px-6 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Entradas</th>
                            <th class="py-3 px-6 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Financeiro</th>
                            <th class="py-3 px-6 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="md:bg-white divide-y divide-gray-100" id="corpoTabelaFornecedores">
                        <?php if (count($fornecedores) > 0): ?>
                            <?php foreach ($fornecedores as $forn): ?>
                                <tr class="block md:table-row hover:bg-gray-50 transition-colors" 
                                    data-nome="<?= strtolower(htmlspecialchars($forn['nome'])) ?>" 
                                    data-cnpj_cpf="<?= htmlspecialchars($forn['cnpj_cpf']) ?>">
                                    
                                    <td class="px-5 py-4 md:table-cell md:w-16 celula-fornecedor">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold uppercase text-sm border border-blue-200">
                                                <?= substr($forn['nome'], 0, 2) ?>
                                            </div>
                                            <div class="ml-3 md:hidden">
                                                <p class="text-gray-900 font-bold"><?= htmlspecialchars($forn['nome']) ?></p>
                                                <p class="text-gray-500 text-xs">CNPJ: <?= $forn['cnpj_cpf'] ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden md:table-cell px-1 py-4 align-middle">
                                        <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($forn['nome']) ?></p>
                                        <p class="text-xs text-gray-500"><?= $forn['cnpj_cpf'] ?></p>
                                    </td>

                                    <td data-label="Contato" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell">
                                        <div class="text-right md:text-left space-y-1">
                                            <p class="text-gray-700 flex items-center justify-end md:justify-start gap-2">
                                                <i class="bi bi-telephone text-green-500 text-xs"></i> <?= $forn['telefone'] ?: '-' ?>
                                            </p>
                                            <p class="text-gray-500 text-xs flex items-center justify-end md:justify-start gap-2">
                                                <i class="bi bi-envelope text-gray-400"></i> <?= $forn['email'] ?: '-' ?>
                                            </p>
                                        </div>
                                    </td>
                                    <td data-label="Entradas" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-center">
                                        <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <?= $forn['total_entradas'] ?> notas
                                        </div>
                                    </td>
                                    <td data-label="Financeiro" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-center">
                                        <?php if ($forn['pendentes'] > 0): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 border border-orange-200">
                                                <span class="w-1.5 h-1.5 mr-1.5 bg-orange-500 rounded-full"></span>
                                                <?= $forn['pendentes'] ?> a pagar
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                                <span class="w-1.5 h-1.5 mr-1.5 bg-green-500 rounded-full"></span>
                                                Em dia
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Ação" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-center celula-acao">
                                        <div class="flex justify-center items-center gap-2">
                                            <a href="entradas_criar.php?fornecedor_id=<?= $forn['id'] ?>" class="p-2 text-purple-600 hover:text-purple-900 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors" title="Nova Entrada">
                                                <i class="bi bi-box-arrow-in-down text-lg"></i>
                                            </a>
                                            <a href="fornecedores_editar.php?id=<?= $forn['id'] ?>" class="p-2 text-amber-600 hover:text-amber-900 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors" title="Editar Dados">
                                                <i class="bi bi-pencil-fill text-lg"></i>
                                            </a>
                                            <?php if ($forn['total_entradas'] == 0): ?>
                                                <a href="fornecedores_excluir.php?id=<?= $forn['id'] ?>" onclick="return confirm('Tem certeza?');" class="p-2 text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 rounded-lg transition-colors" title="Excluir Fornecedor">
                                                    <i class="bi bi-trash-fill text-lg"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-12 text-gray-500">
                                    <i class="bi bi-truck text-4xl mb-2 block text-gray-300"></i>
                                    Nenhum fornecedor cadastrado.
                                </td>
                            </tr>
                        <?php endif; ?>
                        
                        <tr id="linhaSemResultados" class="hidden">
                             <td colspan="6" class="text-center py-12 text-gray-500">
                                <i class="bi bi-search text-4xl mb-2 block text-gray-300"></i>
                                Nenhum fornecedor encontrado.
                             </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function filtrarTabela() {
            const input = document.getElementById('filtroBusca');
            const tabela = document.getElementById('corpoTabelaFornecedores');
            const linhas = tabela.getElementsByTagName('tr');
            const linhaSemResultados = document.getElementById('linhaSemResultados');
            const termoBusca = input.value.toLowerCase();
            let resultadosEncontrados = 0;
            for (let i = 0; i < linhas.length; i++) {
                const linha = linhas[i];
                if (linha.id === 'linhaSemResultados') continue;
                const nome = linha.dataset.nome;
                const cnpj = linha.dataset.cnpj_cpf;
                if (nome.includes(termoBusca) || cnpj.includes(termoBusca)) {
                    linha.style.display = "";
                    resultadosEncontrados++;
                } else {
                    linha.style.display = "none";
                }
            }
            if (resultadosEncontrados === 0) {
                linhaSemResultados.style.display = "";
            } else {
                linhaSemResultados.style.display = "none";
            }
        }
    </script>

    <?php include 'toast_handler.php'; ?>
</body>
</html>