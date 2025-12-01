<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';
$hoje = date('Y-m-d');

$titulo_pagina = "Gerenciar Entradas";

// --- Lógica de Ordenação ---
$allowedSort = [
    'fornecedor' => 'fornecedor_nome', 'entrada' => 'data_entrada', 'vencimento' => 'data_vencimento',
    'pecas' => 'qtd_pecas_total', 'valor' => 'valor_total', 'status' => 'status_pagamento'
];
$sortParam = $_GET['sort'] ?? 'entrada';
$sortDir_URL = $_GET['dir'] ?? 'desc';
if (!array_key_exists($sortParam, $allowedSort)) $sortParam = 'entrada';
$sortDir_SQL = (strtolower($sortDir_URL) === 'desc') ? 'DESC' : 'ASC';
$sortColumnDB = $allowedSort[$sortParam];

if ($sortParam === 'status') {
    $orderBy = "FIELD(e.status_pagamento, 'PENDENTE', 'ATRASADO', 'PAGO', 'CANCELADO') $sortDir_SQL, e.data_vencimento DESC";
} else {
    $orderBy = "$sortColumnDB $sortDir_SQL";
}

function getSortLink($col, $label, $currentParam, $currentDir_URL) {
    $newDir = 'asc'; $arrow = '';
    if ($col === $currentParam) {
        $newDir = ($currentDir_URL === 'asc') ? 'desc' : 'asc';
        $arrow = ($currentDir_URL === 'asc') ? ' ▲' : ' ▼';
    }
    return "<a href='?sort=$col&dir=$newDir' class='text-gray-600 hover:text-roxo-base font-semibold transition-colors'>$label $arrow</a>";
}

try {
    $sql = "SELECT 
                e.id, e.data_entrada, e.data_vencimento, e.numero_nota, e.valor_total, e.status_pagamento,
                f.nome as fornecedor_nome,
                (SELECT SUM(quantidade) FROM itens_entrada WHERE entrada_id = e.id) as qtd_pecas_total
            FROM entradas_produto e
            JOIN fornecedores f ON e.fornecedor_id = f.id
            ORDER BY $orderBy";
    $stmt = $pdo->query($sql);
    $entradas = $stmt->fetchAll();
    $total_itens = count($entradas);
} catch (PDOException $e) {
    die("Erro ao listar entradas: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Entradas - COND</title>
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
                    <h1 class="text-2xl font-bold text-gray-800">Entradas de Produtos</h1>
                    <p class="text-sm text-gray-500">Gerencie compras de estoque e contas a pagar (<?= $total_itens ?>).</p>
                </div>
                <a href="entradas_criar.php" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white transition-colors bg-roxo-base rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 shadow-md">
                    <i class="bi bi-plus-lg mr-2"></i> Nova Entrada
                </a>
            </div>

            <div class="mb-6 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="bi bi-search text-gray-400"></i>
                </div>
                <input type="text" id="filtroBusca" onkeyup="filtrarTabela()" 
                       placeholder="Filtrar por Fornecedor, Nota ou Status..." 
                       class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all bg-white shadow-sm text-sm">
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden overflow-x-auto tabela-responsiva">
                <table class="min-w-full leading-normal">
                    <thead class="hidden md:table-header-group bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <?= getSortLink('fornecedor', 'Fornecedor / Nota', $sortParam, $sortDir_URL) ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <?= getSortLink('entrada', 'Data Entrada', $sortParam, $sortDir_URL) ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <?= getSortLink('vencimento', 'Vencimento', $sortParam, $sortDir_URL) ?>
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <?= getSortLink('pecas', 'Peças', $sortParam, $sortDir_URL) ?>
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <?= getSortLink('valor', 'Valor Total', $sortParam, $sortDir_URL) ?>
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <?= getSortLink('status', 'Status', $sortParam, $sortDir_URL) ?>
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                Ação
                            </th>
                        </tr>
                    </thead>
                    <tbody class="md:bg-white divide-y divide-gray-100">
                        <?php if (count($entradas) == 0): ?>
                            <tr>
                                <td colspan="7" class="text-center py-12 text-gray-500">
                                    <i class="bi bi-box-seam text-4xl mb-2 block text-gray-300"></i>
                                    Nenhuma entrada registrada.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <?php foreach ($entradas as $e): 
                            $textoStatus = $e['status_pagamento'];
                            $data_vencimento = $e['data_vencimento'] ? date('Y-m-d', strtotime($e['data_vencimento'])) : null;
                            $classeStatus = 'bg-gray-100 text-gray-700';

                            if ($e['status_pagamento'] == 'PENDENTE') {
                                if ($data_vencimento && $data_vencimento < $hoje) {
                                    $classeStatus = 'bg-red-50 text-red-700 border border-red-200';
                                    $textoStatus = 'ATRASADO';
                                } else {
                                    $classeStatus = 'bg-yellow-50 text-yellow-700 border border-yellow-200';
                                }
                            } elseif ($e['status_pagamento'] == 'PAGO') {
                                $classeStatus = 'bg-green-50 text-green-700 border border-green-200';
                            } elseif ($e['status_pagamento'] == 'CANCELADO') {
                                $classeStatus = 'bg-gray-200 text-gray-500';
                            }
                        ?>
                            <tr class="block md:table-row hover:bg-gray-50 transition-colors"
                                data-fornecedor="<?= strtolower(htmlspecialchars($e['fornecedor_nome'])) ?>"
                                data-nota="<?= strtolower(htmlspecialchars($e['numero_nota'])) ?>"
                                data-status="<?= strtolower($textoStatus) ?>">
                                
                                <td data-label="Fornecedor" class="px-6 py-4 md:table-cell">
                                    <div>
                                        <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($e['fornecedor_nome']) ?></p>
                                        <p class="text-xs text-gray-500 mt-0.5">Nota: <?= $e['numero_nota'] ?: 'N/A' ?></p>
                                    </div>
                                </td>
                                <td data-label="Data Entrada" class="px-6 py-4 text-sm text-gray-600 md:table-cell">
                                    <?= date('d/m/Y', strtotime($e['data_entrada'])) ?>
                                </td>
                                <td data-label="Vencimento" class="px-6 py-4 text-sm md:table-cell">
                                    <?php if($data_vencimento): ?>
                                        <span class="font-medium <?= ($textoStatus == 'ATRASADO') ? 'text-red-600' : 'text-gray-800' ?>">
                                            <?= date('d/m/Y', strtotime($data_vencimento)) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400 italic">À Vista</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Peças" class="px-6 py-4 text-sm md:table-cell md:text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-roxo-base">
                                        <?= $e['qtd_pecas_total'] ?: 0 ?> itens
                                    </span>
                                </td>
                                <td data-label="Valor Total" class="px-6 py-4 text-sm md:table-cell md:text-center">
                                    <span class="font-bold text-gray-800">R$ <?= number_format($e['valor_total'], 2, ',', '.') ?></span>
                                </td>
                                <td data-label="Status" class="px-6 py-4 text-sm md:table-cell md:text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide <?= $classeStatus ?>">
                                        <?= $textoStatus ?>
                                    </span>
                                </td>
                                <td data-label="Ação" class="px-6 py-4 text-sm md:table-cell md:text-center celula-acao">
                                    <div class="flex justify-center items-center gap-2">
                                        <?php if($e['status_pagamento'] == 'PENDENTE' || $textoStatus == 'ATRASADO'): ?>
                                            <a href="entradas_pagar.php?id=<?= $e['id'] ?>" class="p-2 bg-green-50 hover:bg-green-100 text-green-600 hover:text-green-700 rounded-lg transition-colors" title="Marcar como Pago">
                                                <i class="bi bi-cash-coin text-lg"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="entradas_detalhes.php?id=<?= $e['id'] ?>" class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-800 rounded-lg transition-colors" title="Ver Detalhes">
                                            <i class="bi bi-search text-lg"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr id="linhaSemResultados" class="hidden">
                            <td colspan="7" class="text-center py-12 text-gray-500">
                                <i class="bi bi-search text-4xl mb-2 block text-gray-300"></i>
                                Nenhuma entrada encontrada.
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
            const tabela = document.querySelector('tbody');
            const linhas = tabela.getElementsByTagName('tr');
            const linhaSemResultados = document.getElementById('linhaSemResultados');
            const termoBusca = input.value.toLowerCase();
            let resultadosEncontrados = 0;
            
            for (let i = 0; i < linhas.length; i++) {
                const linha = linhas[i];
                if (linha.id === 'linhaSemResultados') continue;
                
                if (!linha.dataset.fornecedor) continue; 

                const fornecedor = linha.dataset.fornecedor;
                const nota = linha.dataset.nota;
                const status = linha.dataset.status;
                
                if (fornecedor.includes(termoBusca) || nota.includes(termoBusca) || status.includes(termoBusca)) {
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