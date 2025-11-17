<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

// Título da Página (para o menu móvel)
$titulo_pagina = "Relatório de Vendas";

// --- 1. FILTROS DE DATA ---
$data_inicio = $_GET['inicio'] ?? date('Y-m-01');
$data_fim = $_GET['fim'] ?? date('Y-m-t');

// --- 2. LÓGICA DE BUSCA ---
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
        WHERE 
            i.status_item = 'VENDIDO'
            AND c.status = 'FINALIZADO'
            AND c.data_finalizacao BETWEEN ? AND ?
        ORDER BY 
            c.data_finalizacao DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data_inicio, $data_fim]);
    $vendas = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Erro ao buscar relatório: " . $e->getMessage());
}

// --- 3. CÁLCULO DOS TOTAIS ---
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
    <title>Relatório de Vendas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        @media (max-width: 767px) {
            .tabela-responsiva thead { display: none; }
            .tabela-responsiva, .tabela-responsiva tbody, .tabela-responsiva tr {
                display: block; width: 100%;
            }
            .tabela-responsiva tr {
                margin-bottom: 1rem; border: 1px solid #ddd; border-radius: 0.5rem;
                overflow: hidden; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            .tabela-responsiva td {
                display: flex; justify-content: space-between; align-items: center;
                padding: 0.75rem 1rem; border-bottom: 1px solid #eee;
                text-align: right; width: 100%;
            }
            .tabela-responsiva td::before {
                content: attr(data-label); font-weight: bold; text-align: left;
                padding-right: 1rem; color: #555; flex-shrink: 0;
            }
            .tabela-responsiva tr td:last-child { border-bottom: 0; }
            /* Célula Produto (para quebrar linha) */
            .tabela-responsiva td.celula-produto {
                display: block; text-align: right;
            }
            .tabela-responsiva td.celula-produto::before {
                display: block; text-align: left; margin-bottom: 5px;
            }
        }
    </style>
</head>
<body class="bg-gray-100">

    <?php include 'menu.php'; ?>

    <div class="container mx-auto px-4 mt-8 mb-20">
        
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Relatório de Vendas</h2>

        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <form method="GET" action="" class="flex flex-wrap items-end gap-4">
                <div>
                    <label for="inicio" class="block text-sm font-medium text-gray-700">Data Início:</label>
                    <input type="date" name="inicio" id="inicio" value="<?= $data_inicio ?>"
                           class="border rounded px-3 py-2 mt-1 focus:outline-none focus:border-roxo-base w-full">
                </div>
                <div>
                    <label for="fim" class="block text-sm font-medium text-gray-700">Data Fim:</label>
                    <input type="date" name="fim" id="fim" value="<?= $data_fim ?>"
                           class="border rounded px-3 py-2 mt-1 focus:outline-none focus:border-roxo-base w-full">
                </div>
                <button type="submit" class="bg-roxo-base text-white px-5 py-2 rounded shadow hover:bg-purple-700 transition h-10">
                    <i class="bi bi-search mr-1"></i> Filtrar
                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-blue-100 p-6 rounded-lg shadow">
                <p class="text-sm font-bold text-blue-800 uppercase">Total Vendido (Bruto)</p>
                <p class="text-3xl font-bold text-blue-900">R$ <?= number_format($total_vendido_bruto, 2, ',', '.') ?></p>
            </div>
            <div class="bg-yellow-100 p-6 rounded-lg shadow">
                <p class="text-sm font-bold text-yellow-800 uppercase">Custo Apurado</p>
                <p class="text-3xl font-bold text-yellow-900">R$ <?= number_format($total_custo_apurado, 2, ',', '.') ?></p>
            </div>
            <div class="bg-green-100 p-6 rounded-lg shadow border-2 border-green-500">
                <p class="text-sm font-bold text-green-800 uppercase">Lucro Líquido</p>
                <p class="text-3xl font-bold text-green-900">R$ <?= number_format($total_lucro_apurado, 2, ',', '.') ?></p>
            </div>
        </div>


        <div class="bg-white md:shadow-md md:rounded-lg overflow-hidden tabela-responsiva">
            <table class="min-w-full leading-normal">
                <thead class="hidden md:table-header-group">
                    <tr class="bg-gray-100 text-gray-600 uppercase text-sm">
                        <th class="py-3 px-6 text-left">Data Venda</th>
                        <th class="py-3 px-6 text-left">Produto</th>
                        <th class="py-3 px-6 text-left">Cliente</th>
                        <th class="py-3 px-6 text-center">Qtd.</th>
                        <th class="py-3 px-6 text-center">P. Venda (Un)</th>
                        <th class="py-3 px-6 text-center">P. Custo (Un)</th>
                        <th class="py-3 px-6 text-center">Lucro (Total)</th>
                    </tr>
                </thead>
                <tbody class="md:bg-white">
                    <?php if (count($vendas) > 0): ?>
                        <?php foreach ($vendas as $venda): ?>
                            <tr class="block md:table-row hover:bg-gray-50 border-b border-gray-200 md:border-b-0">
                                <td data-label="Data" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell">
                                    <?= date('d/m/Y', strtotime($venda['data_finalizacao'])) ?>
                                </td>
                                <td data-label="Produto" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell font-bold celula-produto">
                                    <?= htmlspecialchars($venda['produto_nome']) ?>
                                </td>
                                <td data-label="Cliente" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell celula-produto">
                                    <?= htmlspecialchars($venda['cliente_nome']) ?>
                                </td>
                                <td data-label="Qtd." class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-center font-bold">
                                    <?= $venda['quantidade'] ?>
                                </td>
                                <td data-label="P. Venda" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-center text-green-700">
                                    R$ <?= number_format($venda['preco_venda'], 2, ',', '.') ?>
                                </td>
                                <td data-label="P. Custo" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-center text-red-700">
                                    R$ <?= number_format($venda['preco_custo'], 2, ',', '.') ?>
                                </td>
                                <td data-label="Lucro" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-center font-bold text-green-800">
                                    R$ <?= number_format($venda['lucro'], 2, ',', '.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="block md:table-row">
                            <td colspan="7" class="text-center py-10 text-gray-500">
                                Nenhuma venda encontrada para este período.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include 'toast_handler.php'; ?>
</body>
</html>