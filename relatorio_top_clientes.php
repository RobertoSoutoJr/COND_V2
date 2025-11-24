<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

$titulo_pagina = "Top 10 Clientes por Volume de Compras";

//FILTRO POR DATA
$data_inicio = $_GET['inicio'] ?? date('Y-m-01');
$data_fim = $_GET['fim'] ?? date('Y-m-t');

// --- 2. LÓGICA DE BUSCA (Top 10 Clientes) ---
try {
    $sql = "
        SELECT 
            cl.nome as cliente_nome,
            COUNT(DISTINCT c.id) as total_sacolas_finalizadas,
            SUM(i.preco_momento * i.quantidade) as total_vendido
        FROM itens_condicional i
        JOIN condicionais c ON i.condicional_id = c.id
        JOIN clientes cl ON c.cliente_id = cl.id
        WHERE 
            i.status_item = 'VENDIDO'
            AND c.status = 'FINALIZADO'
            AND c.data_finalizacao BETWEEN ? AND ?
        GROUP BY
            cl.nome
        ORDER BY 
            total_vendido DESC
        LIMIT 10
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data_inicio, $data_fim]);
    $top_clientes = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Erro ao buscar relatório: " . $e->getMessage());
}

//TOTAIS GERAIS
$total_vendido_geral = 0;
foreach ($top_clientes as $cliente) {
    $total_vendido_geral += $cliente['total_vendido'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?= $titulo_pagina ?></title>
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
        }
    </style>
</head>
<body class="bg-gray-100">

    <?php include 'menu.php'; ?>

    <div class="container mx-auto px-4 mt-8 mb-20">
        
        <h2 class="text-2xl font-bold text-gray-800 mb-6"><?= $titulo_pagina ?></h2>

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
                <p class="text-sm font-bold text-blue-800 uppercase">Total Geral Vendido (Top 10)</p>
                <p class="text-3xl font-bold text-blue-900">R$ <?= number_format($total_vendido_geral, 2, ',', '.') ?></p>
            </div>
            <div class="bg-green-100 p-6 rounded-lg shadow border-2 border-green-500 md:col-span-2">
                <p class="text-sm font-bold text-green-800 uppercase">Período Analisado</p>
                <p class="text-3xl font-bold text-green-900"><?= date('d/m/Y', strtotime($data_inicio)) ?> a <?= date('d/m/Y', strtotime($data_fim)) ?></p>
            </div>
        </div>


        <div class="bg-white md:shadow-md md:rounded-lg overflow-hidden tabela-responsiva">
            <table class="min-w-full leading-normal">
                <thead class="hidden md:table-header-group">
                    <tr class="bg-gray-100 text-gray-600 uppercase text-sm">
                        <th class="py-3 px-6 text-left">#</th>
                        <th class="py-3 px-6 text-left">Cliente</th>
                        <th class="py-3 px-6 text-center">Sacolas Finalizadas</th>
                        <th class="py-3 px-6 text-right">Volume de Compras</th>
                    </tr>
                </thead>
                <tbody class="md:bg-white">
                    <?php if (count($top_clientes) > 0): ?>
                        <?php $rank = 1; ?>
                        <?php foreach ($top_clientes as $cliente): ?>
                            <tr class="block md:table-row hover:bg-gray-50 border-b border-gray-200 md:border-b-0">
                                <td data-label="#" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell font-bold text-center">
                                    <?= $rank++ ?>º
                                </td>
                                <td data-label="Cliente" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell font-bold">
                                    <?= htmlspecialchars($cliente['cliente_nome']) ?>
                                </td>
                                <td data-label="Sacolas" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-center">
                                    <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs font-bold">
                                        <?= $cliente['total_sacolas_finalizadas'] ?>
                                    </span>
                                </td>
                                <td data-label="Volume" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-right font-bold text-green-800">
                                    R$ <?= number_format($cliente['total_vendido'], 2, ',', '.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="block md:table-row">
                            <td colspan="4" class="text-center py-10 text-gray-500">
                                Nenhuma venda finalizada encontrada para este período.
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
