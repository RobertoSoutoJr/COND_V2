<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

$titulo_pagina = "Relatório de Estoque";

try {
    $sql_kpi = "SELECT SUM(estoque_loja) as total_pecas, SUM(preco_custo * estoque_loja) as total_custo, SUM(preco * estoque_loja) as total_venda FROM produtos WHERE estoque_loja > 0";
    $kpis = $pdo->query($sql_kpi)->fetch();
    $total_lucro_potencial = ($kpis['total_venda'] ?: 0) - ($kpis['total_custo'] ?: 0);

    $sql_lista = "SELECT id, nome, imagem, tamanho, cor, estoque_loja, preco_custo, preco as preco_venda, (preco_custo * estoque_loja) as valor_custo_total, (preco * estoque_loja) as valor_venda_total, ((preco - preco_custo) * estoque_loja) as lucro_potencial_total FROM produtos WHERE estoque_loja > 0 ORDER BY nome ASC";
    $produtos = $pdo->query($sql_lista)->fetchAll();
} catch (PDOException $e) { die("Erro: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Estoque - COND</title>
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
                <h1 class="text-2xl font-bold text-gray-800">Balanço de Estoque</h1>
                <p class="text-sm text-gray-500">Valorização atual do inventário físico.</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-blue-500">
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-wide mb-1">Peças</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $kpis['total_pecas'] ?: 0 ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-yellow-500">
                    <p class="text-xs font-bold text-yellow-600 uppercase tracking-wide mb-1">Custo Total</p>
                    <p class="text-2xl font-bold text-gray-800">R$ <?= number_format($kpis['total_custo'] ?: 0, 2, ',', '.') ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-gray-500">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Venda Total</p>
                    <p class="text-2xl font-bold text-gray-800">R$ <?= number_format($kpis['total_venda'] ?: 0, 2, ',', '.') ?></p>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 border-l-4 border-l-green-500">
                    <p class="text-xs font-bold text-green-600 uppercase tracking-wide mb-1">Lucro Potencial</p>
                    <p class="text-2xl font-bold text-gray-800">R$ <?= number_format($total_lucro_potencial, 2, ',', '.') ?></p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden overflow-x-auto tabela-responsiva">
                <table class="min-w-full leading-normal">
                    <thead class="hidden md:table-header-group bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" colspan="2">Produto</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Estoque</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Custo (Un)</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Venda (Un)</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Custo Total</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Venda Total</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Lucro Potencial</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (count($produtos) > 0): ?>
                            <?php foreach ($produtos as $p): ?>
                                <tr class="block md:table-row hover:bg-gray-50 transition-colors">
                                    <td class="px-5 py-4 md:w-16 celula-produto">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center">
                                                <?php if (!empty($p['imagem'])): ?>
                                                    <img src="uploads/<?= $p['imagem'] ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <i class="bi bi-tshirt-fill text-gray-400 text-xl"></i>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Produto" class="px-6 py-4 md:table-cell font-bold text-gray-800 celula-produto">
                                        <?= htmlspecialchars($p['nome']) ?> <span class="text-gray-500 font-normal text-xs ml-1">(<?= $p['tamanho'] ?> | <?= $p['cor'] ?>)</span>
                                    </td>
                                    <td data-label="Estoque" class="px-6 py-4 text-sm md:table-cell md:text-center"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"><?= $p['estoque_loja'] ?> un</span></td>
                                    <td data-label="Custo" class="px-6 py-4 text-sm text-red-600 md:table-cell md:text-right">R$ <?= number_format($p['preco_custo'], 2, ',', '.') ?></td>
                                    <td data-label="Venda" class="px-6 py-4 text-sm text-green-600 md:table-cell md:text-right">R$ <?= number_format($p['preco_venda'], 2, ',', '.') ?></td>
                                    <td data-label="Custo Total" class="px-6 py-4 text-sm font-bold text-red-700 md:table-cell md:text-right">R$ <?= number_format($p['valor_custo_total'], 2, ',', '.') ?></td>
                                    <td data-label="Venda Total" class="px-6 py-4 text-sm font-bold text-green-700 md:table-cell md:text-right">R$ <?= number_format($p['valor_venda_total'], 2, ',', '.') ?></td>
                                    <td data-label="Lucro Potencial" class="px-6 py-4 text-sm font-bold text-blue-600 md:table-cell md:text-right">R$ <?= number_format($p['lucro_potencial_total'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="block md:table-row"><td colspan="8" class="text-center py-12 text-gray-500"><i class="bi bi-box-seam text-4xl mb-2 block text-gray-300"></i> Nenhum produto com estoque.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <?php include 'toast_handler.php'; ?>
</body>
</html>