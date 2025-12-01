<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

$titulo_pagina = "Top Clientes";

$data_inicio = $_GET['inicio'] ?? date('Y-m-01');
$data_fim = $_GET['fim'] ?? date('Y-m-t');

try {
    $sql = "SELECT cl.nome as cliente_nome, COUNT(DISTINCT c.id) as total_sacolas_finalizadas, SUM(i.preco_momento * i.quantidade) as total_vendido FROM itens_condicional i JOIN condicionais c ON i.condicional_id = c.id JOIN clientes cl ON c.cliente_id = cl.id WHERE i.status_item = 'VENDIDO' AND c.status = 'FINALIZADO' AND c.data_finalizacao BETWEEN ? AND ? GROUP BY cl.nome ORDER BY total_vendido DESC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data_inicio, $data_fim]);
    $top_clientes = $stmt->fetchAll();
} catch (PDOException $e) { die("Erro: " . $e->getMessage()); }

$total_vendido_geral = 0;
foreach ($top_clientes as $cliente) { $total_vendido_geral += $cliente['total_vendido']; }
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Top Clientes - COND</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>@media (max-width: 767px) { .tabela-responsiva thead { display: none; } .tabela-responsiva, .tabela-responsiva tbody, .tabela-responsiva tr { display: block; width: 100%; } .tabela-responsiva tr { margin-bottom: 1rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; overflow: hidden; background: #fff; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05); } .tabela-responsiva td { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; text-align: right; width: 100%; } .tabela-responsiva td::before { content: attr(data-label); font-weight: 600; text-align: left; padding-right: 1rem; color: #6b7280; flex-shrink: 0; font-size: 0.875rem; } .tabela-responsiva tr td:last-child { border-bottom: 0; } }</style>
</head>
<body class="bg-gray-50 font-sans text-gray-900">

    <?php include 'menu.php'; ?>

    <div class="md:ml-64 transition-all duration-300 flex flex-col min-h-screen">
        <div class="bg-white shadow-sm p-4 md:hidden flex justify-between items-center sticky top-0 z-30"><span class="font-bold text-xl text-roxo-base">COND</span><button onclick="toggleSidebar()" class="text-gray-600 focus:outline-none"><i class="bi bi-list text-3xl"></i></button></div>

        <main class="flex-1 p-6">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Ranking de Clientes</h1>
                <p class="text-sm text-gray-500">Os 10 maiores compradores do período.</p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-8">
                <form method="GET" action="" class="flex flex-col sm:flex-row items-end gap-4">
                    <div class="w-full sm:w-auto"><label class="block text-sm font-medium text-gray-700 mb-1">Início</label><input type="date" name="inicio" id="inicio" value="<?= $data_inicio ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"></div>
                    <div class="w-full sm:w-auto"><label class="block text-sm font-medium text-gray-700 mb-1">Fim</label><input type="date" name="fim" id="fim" value="<?= $data_fim ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"></div>
                    <button type="submit" class="w-full sm:w-auto px-5 py-2 bg-roxo-base text-white rounded-lg hover:bg-purple-700 shadow-md transition-colors font-medium h-[42px] flex items-center justify-center"><i class="bi bi-funnel-fill mr-2"></i> Filtrar</button>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-blue-500">
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-wide mb-1">Total (Top 10)</p>
                    <p class="text-2xl font-bold text-gray-800">R$ <?= number_format($total_vendido_geral, 2, ',', '.') ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-green-500 md:col-span-2">
                    <p class="text-xs font-bold text-green-600 uppercase tracking-wide mb-1">Período Analisado</p>
                    <p class="text-2xl font-bold text-gray-800"><?= date('d/m/Y', strtotime($data_inicio)) ?> a <?= date('d/m/Y', strtotime($data_fim)) ?></p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden overflow-x-auto tabela-responsiva">
                <table class="min-w-full leading-normal">
                    <thead class="hidden md:table-header-group bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Sacolas</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Volume de Compras</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (count($top_clientes) > 0): $rank = 1; foreach ($top_clientes as $cliente): ?>
                            <tr class="block md:table-row hover:bg-gray-50 transition-colors">
                                <td data-label="#" class="px-6 py-4 md:table-cell font-bold text-gray-500 md:text-center text-lg"><?= $rank++ ?>º</td>
                                <td data-label="Cliente" class="px-6 py-4 md:table-cell font-bold text-gray-800"><?= htmlspecialchars($cliente['cliente_nome']) ?></td>
                                <td data-label="Sacolas" class="px-6 py-4 md:table-cell md:text-center"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700"><?= $cliente['total_sacolas_finalizadas'] ?></span></td>
                                <td data-label="Volume" class="px-6 py-4 md:table-cell md:text-right font-bold text-green-700">R$ <?= number_format($cliente['total_vendido'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr class="block md:table-row"><td colspan="4" class="text-center py-12 text-gray-500"><i class="bi bi-people text-4xl mb-2 block text-gray-300"></i> Nenhuma venda finalizada encontrada.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <?php include 'toast_handler.php'; ?>
</body>
</html>