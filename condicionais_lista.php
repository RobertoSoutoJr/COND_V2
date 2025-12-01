<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';
require_once 'whatsapp_utils.php';

$titulo_pagina = "Gerenciar Sacolas";
$hoje = date('Y-m-d');

// --- Lógica de Ordenação ---
$allowedSort = [
    'cliente' => 'cliente_nome', 'saida'   => 'data_saida', 'retorno' => 'data_prevista_retorno',
    'pecas'   => 'qtd_pecas_total', 'valor'   => 'valor_total_sacola', 'status'  => 'status'
];
$sortParam = $_GET['sort'] ?? 'saida';
$sortDir_URL = $_GET['dir'] ?? 'desc';

if (!array_key_exists($sortParam, $allowedSort)) $sortParam = 'status';
$sortDir_SQL = (strtolower($sortDir_URL) === 'desc') ? 'DESC' : 'ASC';
$sortColumnDB = $allowedSort[$sortParam];

if ($sortParam === 'status') {
    $orderBy = "FIELD(c.status, 'ABERTO', 'ATRASADO', 'FINALIZADO') $sortDir_SQL, c.data_prevista_retorno DESC";
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
                c.id, c.data_saida, c.data_prevista_retorno, c.status,
                cl.nome as cliente_nome, cl.telefone,
                (SELECT SUM(quantidade) FROM itens_condicional WHERE condicional_id = c.id) as qtd_pecas_total,
                (SELECT SUM(preco_momento * quantidade) FROM itens_condicional WHERE condicional_id = c.id) as valor_total_sacola,
                (SELECT SUM(p.preco_custo * i.quantidade) FROM itens_condicional i JOIN produtos p ON i.produto_id = p.id WHERE i.condicional_id = c.id) as valor_custo_sacola
            FROM condicionais c
            JOIN clientes cl ON c.cliente_id = cl.id
            ORDER BY $orderBy";
    $stmt = $pdo->query($sql);
    $condicionais = $stmt->fetchAll();
    $total_registros = count($condicionais);
} catch (PDOException $e) {
    die("Erro ao listar: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Sacolas - COND</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        @media (max-width: 767px) {
            .tabela-responsiva thead { display: none; }
            .tabela-responsiva, .tabela-responsiva tbody, .tabela-responsiva tr { display: block; width: 100%; }
            .tabela-responsiva tr {
                margin-bottom: 1rem; border: 1px solid #e5e7eb; border-radius: 0.75rem;
                overflow: hidden; background: #fff; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            }
            .tabela-responsiva td {
                display: flex; justify-content: space-between; align-items: center;
                padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6;
                text-align: right; width: 100%;
            }
            .tabela-responsiva td::before {
                content: attr(data-label); font-weight: 600; text-align: left;
                padding-right: 1rem; color: #6b7280; flex-shrink: 0; font-size: 0.875rem;
            }
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
                    <h1 class="text-2xl font-bold text-gray-800">Sacolas</h1>
                    <p class="text-sm text-gray-500">Gerencie os condicionais ativos e finalizados (<?= $total_registros ?>).</p>
                </div>
                <a href="condicionais_criar.php" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white transition-colors bg-roxo-base rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 shadow-md">
                    <i class="bi bi-bag-plus-fill mr-2"></i> Nova Sacola
                </a>
            </div>

            <div class="mb-6 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="bi bi-search text-gray-400"></i>
                </div>
                <input type="text" id="filtroBusca" onkeyup="filtrarTabela()" 
                       placeholder="Filtrar por Cliente, ID ou Status..." 
                       class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all bg-white shadow-sm text-sm">
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden overflow-x-auto tabela-responsiva">
                <table class="min-w-full leading-normal">
                    <thead class="hidden md:table-header-group bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <?= getSortLink('cliente', 'Cliente / ID', $sortParam, $sortDir_URL) ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <?= getSortLink('saida', 'Saída', $sortParam, $sortDir_URL) ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <?= getSortLink('retorno', 'Retorno', $sortParam, $sortDir_URL) ?>
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <?= getSortLink('pecas', 'Peças', $sortParam, $sortDir_URL) ?>
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <?= getSortLink('valor', 'Financeiro', $sortParam, $sortDir_URL) ?>
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
                        <?php if (count($condicionais) > 0): ?>
                            <?php foreach ($condicionais as $c): 
                                $textoStatus = $c['status'];
                                $classeStatus = 'bg-gray-100 text-gray-700 border border-gray-200';
                                
                                if ($c['status'] == 'ABERTO') {
                                    if ($c['data_prevista_retorno'] < $hoje) {
                                        $classeStatus = 'bg-red-50 text-red-700 border border-red-200';
                                        $textoStatus = 'ATRASADO';
                                    } else {
                                        $classeStatus = 'bg-yellow-50 text-yellow-700 border border-yellow-200';
                                    }
                                } elseif ($c['status'] == 'FINALIZADO') {
                                    $classeStatus = 'bg-green-50 text-green-700 border border-green-200';
                                }

                                $valor_venda = $c['valor_total_sacola'] ?: 0;
                                $valor_custo = $c['valor_custo_sacola'] ?: 0;
                                $lucro_potencial = $valor_venda - $valor_custo;
                            ?>
                                <tr class="block md:table-row hover:bg-gray-50 transition-colors"
                                    data-cliente="<?= strtolower(htmlspecialchars($c['cliente_nome'])) ?>"
                                    data-id="<?= $c['id'] ?>"
                                    data-status="<?= strtolower($textoStatus) ?>">
                                    
                                    <td data-label="Cliente" class="px-6 py-4 md:table-cell">
                                        <div>
                                            <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($c['cliente_nome']) ?></p>
                                            <p class="text-xs text-gray-500 font-mono">ID: #<?= $c['id'] ?></p>
                                        </div>
                                    </td>
                                    <td data-label="Saída" class="px-6 py-4 text-sm text-gray-600 md:table-cell">
                                        <?= date('d/m/Y', strtotime($c['data_saida'])) ?>
                                    </td>
                                    <td data-label="Retorno" class="px-6 py-4 text-sm font-medium text-gray-800 md:table-cell">
                                        <?= date('d/m/Y', strtotime($c['data_prevista_retorno'])) ?>
                                    </td>
                                    <td data-label="Peças" class="px-6 py-4 text-sm md:table-cell md:text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-roxo-base">
                                            <?= $c['qtd_pecas_total'] ?: 0 ?> itens
                                        </span>
                                    </td>
                                    <td data-label="Valor Sacola" class="px-6 py-4 text-sm md:table-cell md:text-center">
                                        <div class="flex flex-col items-end md:items-center">
                                            <span class="font-bold text-gray-900">R$ <?= number_format($valor_venda, 2, ',', '.') ?></span>
                                            <div class="text-xs text-gray-400 mt-0.5">
                                                Lucro Est.: <span class="text-green-600 font-medium">R$ <?= number_format($lucro_potencial, 2, ',', '.') ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Status" class="px-6 py-4 text-sm md:table-cell md:text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wide <?= $classeStatus ?>">
                                            <?= $textoStatus ?>
                                        </span>
                                    </td>
                                    <td data-label="Ação" class="px-6 py-4 text-sm md:table-cell md:text-center celula-acao">
                                        <div class="flex justify-center items-center gap-2">
                                            
                                            <?php 
                                                $condicional_data = [
                                                    'id' => $c['id'],
                                                    'status' => $c['status'],
                                                    'data_prevista_retorno' => $c['data_prevista_retorno'],
                                                    'cliente_nome' => $c['cliente_nome'],
                                                    'telefone' => $c['telefone']
                                                ];
                                                $link_whatsapp = gerarLinkWhatsApp($pdo, $condicional_data);
                                            ?>
                                            <a href="<?= $link_whatsapp ?>" target="_blank" class="p-2 bg-green-50 hover:bg-green-100 text-green-600 hover:text-green-700 rounded-lg transition-colors" title="WhatsApp">
                                                <i class="bi bi-whatsapp text-lg"></i>
                                            </a>

                                            <?php if($c['status'] !== 'FINALIZADO'): ?>
                                                <a href="condicionais_baixar.php?id=<?= $c['id'] ?>" class="p-2 bg-purple-50 hover:bg-purple-100 text-roxo-base hover:text-purple-800 rounded-lg transition-colors" title="Receber/Baixar">
                                                    <i class="bi bi-arrow-down-up text-lg"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <a href="condicionais_detalhes.php?id=<?= $c['id'] ?>" class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-800 rounded-lg transition-colors" title="Ver Detalhes">
                                                <i class="bi bi-search text-lg"></i>
                                            </a>
                                            
                                            <a href="condicionais_imprimir.php?id=<?= $c['id'] ?>" target="_blank" class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-800 rounded-lg transition-colors" title="Imprimir Recibo">
                                                <i class="bi bi-printer-fill text-lg"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-12 text-gray-500">
                                    <i class="bi bi-bag-x text-4xl mb-2 block text-gray-300"></i>
                                    Nenhuma sacola encontrada.
                                </td>
                            </tr>
                        <?php endif; ?>

                        <tr id="linhaSemResultados" class="hidden">
                            <td colspan="7" class="text-center py-12 text-gray-500">
                                <i class="bi bi-search text-4xl mb-2 block text-gray-300"></i>
                                Nenhuma sacola encontrada.
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
                
                // Verifica se a linha tem os atributos (para ignorar cabeçalho se houver bug de renderização)
                if (!linha.dataset.cliente) continue;

                const nomeCliente = linha.dataset.cliente;
                const idSacola = linha.dataset.id;
                const statusSacola = linha.dataset.status;
                
                if (nomeCliente.includes(termoBusca) || idSacola.includes(termoBusca) || statusSacola.includes(termoBusca)) {
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