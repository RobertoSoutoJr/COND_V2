<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

$titulo_pagina = "Relatório de Vendas";

$data_inicio = $_GET['inicio'] ?? date('Y-m-01');
$data_fim = $_GET['fim'] ?? date('Y-m-t');

try {
    $sql = "
        SELECT 
            c.data_finalizacao, p.nome as produto_nome, p.preco_custo,
            i.preco_momento as preco_venda, i.quantidade,
            cl.nome as cliente_nome,
            ((i.preco_momento - p.preco_custo) * i.quantidade) as lucro
        FROM itens_condicional i
        JOIN produtos p ON i.produto_id = p.id
        JOIN condicionais c ON i.condicional_id = c.id
        JOIN clientes cl ON c.cliente_id = cl.id
        WHERE i.status_item = 'VENDIDO' AND c.status = 'FINALIZADO' AND c.data_finalizacao BETWEEN ? AND ?
        ORDER BY c.data_finalizacao DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data_inicio, $data_fim]);
    $vendas = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erro ao buscar relatório: " . $e->getMessage());
}

$total_vendido_bruto = 0;
$total_custo_apurado = 0;
$total_lucro_apurado = 0;

foreach ($vendas as $venda) {
    $total_vendido_bruto += $venda['preco_venda'] * $venda['quantidade'];
    $total_custo_apurado += $venda['preco_custo'] * $venda['quantidade'];
    $total_lucro_apurado += $venda['lucro'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Vendas - COND</title>
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
            .tabela-responsiva td.celula-produto { display: block; text-align: right; }
            .tabela-responsiva td.celula-produto::before { display: block; text-align: left; margin-bottom: 5px; }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-900">

    <?php include 'menu.php'; ?>

    <div class="md:ml-64 transition-all duration-300 flex flex-col min-h-screen">
        <div class="bg-white shadow-sm p-4 md:hidden flex justify-between items-center sticky top-0 z-30"><span class="font-bold text-xl text-roxo-base">COND</span><button onclick="toggleSidebar()" class="text-gray-600 focus:outline-none"><i class="bi bi-list text-3xl"></i></button></div>

        <main class="flex-1 p-6">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Relatório de Vendas</h1>
                <p class="text-sm text-gray-500">Análise detalhada de vendas e lucratividade.</p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-8">
                <form method="GET" action="" class="flex flex-col sm:flex-row items-end gap-4">
                    <div class="w-full sm:w-auto"><label for="inicio" class="block text-sm font-medium text-gray-700 mb-1">Início</label><input type="date" name="inicio" id="inicio" value="<?= $data_inicio ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"></div>
                    <div class="w-full sm:w-auto"><label for="fim" class="block text-sm font-medium text-gray-700 mb-1">Fim</label><input type="date" name="fim" id="fim" value="<?= $data_fim ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"></div>
                    <button type="submit" class="w-full sm:w-auto px-5 py-2 bg-roxo-base text-white rounded-lg hover:bg-purple-700 shadow-md transition-colors font-medium flex items-center justify-center h-[42px]"><i class="bi bi-funnel-fill mr-2"></i> Filtrar</button>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-blue-500">
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-wide mb-1">Venda Bruta</p>
                    <p class="text-2xl font-bold text-gray-800">R$ <?= number_format($total_vendido_bruto, 2, ',', '.') ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-yellow-500">
                    <p class="text-xs font-bold text-yellow-600 uppercase tracking-wide mb-1">Custo Apurado</p>
                    <p class="text-2xl font-bold text-gray-800">R$ <?= number_format($total_custo_apurado, 2, ',', '.') ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-green-500">
                    <p class="text-xs font-bold text-green-600 uppercase tracking-wide mb-1">Lucro Líquido</p>
                    <p class="text-2xl font-bold text-gray-800">R$ <?= number_format($total_lucro_apurado, 2, ',', '.') ?></p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden overflow-x-auto tabela-responsiva">
                <table class="min-w-full leading-normal">
                    <thead class="hidden md:table-header-group bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Produto</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Qtd.</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Venda</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Custo</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Lucro</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (count($vendas) > 0): ?>
                            <?php foreach ($vendas as $venda): ?>
                                <tr class="block md:table-row hover:bg-gray-50 transition-colors">
                                    <td data-label="Data" class="px-6 py-4 text-sm text-gray-600 md:table-cell"><?= date('d/m/Y', strtotime($venda['data_finalizacao'])) ?></td>
                                    <td data-label="Produto" class="px-6 py-4 text-sm font-bold text-gray-800 md:table-cell celula-produto"><?= htmlspecialchars($venda['produto_nome']) ?></td>
                                    <td data-label="Cliente" class="px-6 py-4 text-sm text-gray-600 md:table-cell celula-produto"><?= htmlspecialchars($venda['cliente_nome']) ?></td>
                                    <td data-label="Qtd." class="px-6 py-4 text-sm font-medium md:table-cell md:text-center"><?= $venda['quantidade'] ?></td>
                                    <td data-label="Venda" class="px-6 py-4 text-sm text-green-700 md:table-cell md:text-right">R$ <?= number_format($venda['preco_venda'], 2, ',', '.') ?></td>
                                    <td data-label="Custo" class="px-6 py-4 text-sm text-red-700 md:table-cell md:text-right">R$ <?= number_format($venda['preco_custo'], 2, ',', '.') ?></td>
                                    <td data-label="Lucro" class="px-6 py-4 text-sm font-bold text-green-800 md:table-cell md:text-right">R$ <?= number_format($venda['lucro'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="block md:table-row"><td colspan="7" class="text-center py-12 text-gray-500"><i class="bi bi-search text-4xl mb-2 block text-gray-300"></i> Nenhuma venda encontrada para este período.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <?php include 'toast_handler.php'; ?>
</body>
</html>