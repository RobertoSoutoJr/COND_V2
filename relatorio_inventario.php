<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

// Título da Página
$titulo_pagina = "Relatório de Estoque";

try {
    // --- 1. BUSCA OS TOTAIS (PARA OS CARDS) ---
    $sql_kpi = "
        SELECT 
            SUM(estoque_loja) as total_pecas,
            SUM(preco_custo * estoque_loja) as total_custo,
            SUM(preco * estoque_loja) as total_venda
        FROM produtos
        WHERE estoque_loja > 0
    ";
    $kpis = $pdo->query($sql_kpi)->fetch();
    
    $total_lucro_potencial = ($kpis['total_venda'] ?: 0) - ($kpis['total_custo'] ?: 0);

    // --- 2. BUSCA A LISTA DETALHADA DE PRODUTOS ---
    $sql_lista = "
        SELECT 
            id, nome, imagem, tamanho, cor, estoque_loja,
            preco_custo,
            preco as preco_venda,
            (preco_custo * estoque_loja) as valor_custo_total,
            (preco * estoque_loja) as valor_venda_total,
            ((preco - preco_custo) * estoque_loja) as lucro_potencial_total
        FROM produtos
        WHERE estoque_loja > 0
        ORDER BY nome ASC
    ";
    $produtos = $pdo->query($sql_lista)->fetchAll();

} catch (PDOException $e) {
    die("Erro ao buscar relatório: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Inventário</title>
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
            /* Célula Produto (Avatar + Nome) */
            .tabela-responsiva td.celula-produto {
                display: block; text-align: left;
            }
            .tabela-responsiva td.celula-produto::before { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100">

    <?php include 'menu.php'; ?>

    <div class="container mx-auto px-4 mt-8 mb-20">
        
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Relatório de Inventário (Balanço)</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6 mb-8">
            <div class="bg-blue-100 p-4 md:p-6 rounded-lg shadow">
                <p class="text-xs md:text-sm font-bold text-blue-800 uppercase">Peças</p>
                <p class="text-2xl md:text-3xl font-bold text-blue-900"><?= $kpis['total_pecas'] ?: 0 ?></p>
            </div>
            <div class="bg-yellow-100 p-4 md:p-6 rounded-lg shadow">
                <p class="text-xs md:text-sm font-bold text-yellow-800 uppercase">Custo Total</p>
                <p class="text-2xl md:text-3xl font-bold text-yellow-900">R$ <?= number_format($kpis['total_custo'] ?: 0, 2, ',', '.') ?></p>
            </div>
            <div class="bg-gray-100 p-4 md:p-6 rounded-lg shadow">
                <p class="text-xs md:text-sm font-bold text-gray-800 uppercase">Venda Total</p>
                <p class="text-2xl md:text-3xl font-bold text-gray-900">R$ <?= number_format($kpis['total_venda'] ?: 0, 2, ',', '.') ?></p>
            </div>
            <div class="bg-green-100 p-4 md:p-6 rounded-lg shadow border-2 border-green-500">
                <p class="text-xs md:text-sm font-bold text-green-800 uppercase">Lucro Potencial</p>
                <p class="text-2xl md:text-3xl font-bold text-green-900">R$ <?= number_format($total_lucro_potencial, 2, ',', '.') ?></p>
            </div>
        </div>

        <div class="bg-white md:shadow-md md:rounded-lg overflow-hidden tabela-responsiva">
            <table class="min-w-full leading-normal">
                <thead class="hidden md:table-header-group">
                    <tr class="bg-gray-100 text-gray-600 uppercase text-sm">
                        <th class="py-3 px-6 text-left" colspan="2">Produto</th>
                        <th class="py-3 px-6 text-center">Estoque</th>
                        <th class="py-3 px-6 text-center">Custo (Un)</th>
                        <th class="py-3 px-6 text-center">Venda (Un)</th>
                        <th class="py-3 px-6 text-center">Custo Total</th>
                        <th class="py-3 px-6 text-center">Venda Total</th>
                        <th class="py-3 px-6 text-center">Lucro Potencial</th>
                    </tr>
                </thead>
                <tbody class="md:bg-white">
                    <?php if (count($produtos) > 0): ?>
                        <?php foreach ($produtos as $p): ?>
                            <tr class="block md:table-row hover:bg-gray-50 border-b border-gray-200 md:border-b-0">
                                
                                <td class="px-5 py-4 md:px-3 md:py-4 md:w-16 celula-produto">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-12 h-12 rounded overflow-hidden border bg-gray-100">
                                            <img src="uploads/<?= $p['imagem'] ?: 'default.png' ?>" class="w-full h-full object-cover">
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-gray-900 font-bold whitespace-normal"><?= htmlspecialchars($p['nome']) ?></p>
                                            <p class="text-gray-500 text-xs"><?= $p['tamanho'] ?> / <?= $p['cor'] ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden md:table-cell"></td>

                                <td data-label="Estoque" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-center">
                                    <span class="font-bold"><?= $p['estoque_loja'] ?> un</span>
                                </td>
                                <td data-label="Custo (Un)" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-center">
                                    <span class="text-red-700">R$ <?= number_format($p['preco_custo'], 2, ',', '.') ?></span>
                                </td>
                                <td data-label="Venda (Un)" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-center">
                                    <span class="text-green-700">R$ <?= number_format($p['preco_venda'], 2, ',', '.') ?></span>
                                </td>
                                <td data-label="Custo Total" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-center">
                                    <span class="font-bold text-red-800">R$ <?= number_format($p['valor_custo_total'], 2, ',', '.') ?></span>
                                </td>
                                <td data-label="Venda Total" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-center">
                                    <span class="font-bold text-green-800">R$ <?= number_format($p['valor_venda_total'], 2, ',', '.') ?></span>
                                </td>
                                <td data-label="Lucro Potencial" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-center">
                                    <span class="font-bold text-blue-800">R$ <?= number_format($p['lucro_potencial_total'], 2, ',', '.') ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="block md:table-row">
                            <td colspan="8" class="text-center py-10 text-gray-500">
                                Nenhum produto com estoque encontrado.
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