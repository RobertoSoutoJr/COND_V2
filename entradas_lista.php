<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';
$hoje = date('Y-m-d');

// --- Lógica de Ordenação ---
$allowedSort = [
    'fornecedor' => 'fornecedor_nome', 'entrada'   => 'data_entrada', 'vencimento' => 'data_vencimento',
    'pecas'   => 'qtd_pecas_total', 'valor'   => 'valor_total', 'status'  => 'status_pagamento'
];
$sortParam = $_GET['sort'] ?? 'entrada';
$sortDir_URL = $_GET['dir'] ?? 'desc';
if (!array_key_exists($sortParam, $allowedSort)) $sortParam = 'entrada';
$sortDir_SQL = (strtolower($sortDir_URL) === 'desc') ? 'DESC' : 'ASC';
$sortColumnDB = $allowedSort[$sortParam];
if ($sortParam === 'status') {
    // Ordena PENDENTE > ATRASADO > PAGO > CANCELADO
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
    return "<a href='?sort=$col&dir=$newDir' class='text-gray-600 hover:text-gray-900'>$label $arrow</a>";
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
} catch (PDOException $e) {
    die("Erro ao listar entradas: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Entradas de Produtos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        @media (max-width: 767px) {
            .tabela-responsiva thead {
                display: none;
            }
            .tabela-responsiva, .tabela-responsiva tbody, .tabela-responsiva tr {
                display: block;
                width: 100%;
            }
            .tabela-responsiva tr {
                margin-bottom: 1rem;
                border: 1px solid #ddd;
                border-radius: 0.5rem;
                overflow: hidden;
                background: #fff;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            .tabela-responsiva td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.75rem 1rem;
                border-bottom: 1px solid #eee;
                text-align: right;
                width: 100%;
            }
            /* Adiciona o Label (título) antes do conteúdo */
            .tabela-responsiva td::before {
                content: attr(data-label);
                font-weight: bold;
                text-align: left;
                padding-right: 1rem;
                color: #555;
                /* Impede que o label encolha */
                flex-shrink: 0; 
            }
            /* Permite que o conteúdo (valor) quebre a linha */
            .tabela-responsiva td > * {
                text-align: right;
            }

            /* --- MUDANÇAS PARA A CÉLULA DE AÇÃO --- */
            
            /* 1. Tira a célula de ação do modo flex (label/valor) */
            .tabela-responsiva td.celula-acao {
                display: block;
                padding-top: 0.75rem;
                padding-bottom: 0.75rem;
            }
            /* 2. Esconde o label "Ação:" (desnecessário) */
            .tabela-responsiva td.celula-acao::before {
                display: none;
            }
            /* 3. Faz os botões quebrarem a linha se necessário */
            .tabela-responsiva td.celula-acao > div {
                justify-content: center; /* Centraliza */
                flex-wrap: wrap; /* Permite quebrar a linha */
                gap: 0.5rem; /* Adiciona espaço */
            }
        }
    </style>
</head>
<body class="bg-gray-100">

    <?php include 'menu.php'; ?>

    <div class="container mx-auto px-4 mt-8">
        
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Gerenciamento de Entradas (Contas a Pagar)</h2>
            <a href="entradas_criar.php" class="bg-roxo-base hover:bg-purple-700 text-white px-4 py-2 rounded shadow font-bold transition flex items-center">
                <i class="bi bi-plus-lg mr-2"></i> Nova Entrada
            </a>
        </div>

        <div class="bg-white md:shadow-md md:rounded-lg overflow-x-auto tabela-responsiva">
            <table class="min-w-full leading-normal">
                <thead class="hidden md:table-header-group">
                    <tr>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold uppercase tracking-wider">
                            <?= getSortLink('fornecedor', 'Fornecedor / Nota', $sortParam, $sortDir_URL) ?>
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold uppercase tracking-wider">
                            <?= getSortLink('entrada', 'Data Entrada', $sortParam, $sortDir_URL) ?>
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold uppercase tracking-wider">
                            <?= getSortLink('vencimento', 'Vencimento', $sortParam, $sortDir_URL) ?>
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold uppercase tracking-wider">
                            <?= getSortLink('pecas', 'Peças', $sortParam, $sortDir_URL) ?>
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold uppercase tracking-wider">
                            <?= getSortLink('valor', 'Valor Total', $sortParam, $sortDir_URL) ?>
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold uppercase tracking-wider">
                            <?= getSortLink('status', 'Status Pagamento', $sortParam, $sortDir_URL) ?>
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Ação
                        </th>
                    </tr>
                </thead>
                <tbody class="md:bg-white">
                    <?php if (count($entradas) == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center py-10 text-gray-500">Nenhuma entrada de produto registrada.</td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($entradas as $e): 
                        $classeStatus = 'bg-gray-200 text-gray-700';
                        $textoStatus = $e['status_pagamento'];
                        $data_vencimento = $e['data_vencimento'] ? date('Y-m-d', strtotime($e['data_vencimento'])) : null;

                        if ($e['status_pagamento'] == 'PENDENTE' && $data_vencimento && $data_vencimento < $hoje) {
                            $classeStatus = 'bg-red-200 text-red-800 border'; $textoStatus = 'ATRASADO';
                        } elseif ($e['status_pagamento'] == 'PENDENTE') {
                            $classeStatus = 'bg-yellow-100 text-yellow-800 border';
                        } elseif ($e['status_pagamento'] == 'PAGO') {
                            $classeStatus = 'bg-green-200 text-green-800';
                        } elseif ($e['status_pagamento'] == 'CANCELADO') {
                            $classeStatus = 'bg-gray-400 text-white';
                        }
                    ?>
                        <tr class="block md:table-row border-b border-gray-200 md:border-b-0 hover:bg-gray-50">
                            
                            <td data-label="Fornecedor" class="px-5 py-3 md:py-5 text-sm md:table-cell">
                                <div>
                                    <p class="text-gray-900 font-bold"><?= htmlspecialchars($e['fornecedor_nome']) ?></p>
                                    <p class="text-gray-500 text-xs">Nota: <?= $e['numero_nota'] ?: 'N/A' ?></p>
                                </div>
                            </td>
                            <td data-label="Entrada" class="px-5 py-3 md:py-5 text-sm md:table-cell">
                                <p><?= date('d/m/Y', strtotime($e['data_entrada'])) ?></p>
                            </td>
                            <td data-label="Vencimento" class="px-5 py-3 md:py-5 text-sm md:table-cell">
                                <p class="font-bold">
                                    <?= $data_vencimento ? date('d/m/Y', strtotime($data_vencimento)) : 'À Vista' ?>
                                </p>
                            </td>
                            <td data-label="Peças" class="px-5 py-3 md:py-5 text-sm md:table-cell md:text-center">
                                <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs font-bold">
                                    <?= $e['qtd_pecas_total'] ?: 0 ?> itens
                                </span>
                            </td>
                            <td data-label="Valor Total" class="px-5 py-3 md:py-5 text-sm md:table-cell md:text-center">
                                <span class="text-gray-800 font-bold">R$ <?= number_format($e['valor_total'], 2, ',', '.') ?></span>
                            </td>
                            <td data-label="Status" class="px-5 py-3 md:py-5 text-sm md:table-cell md:text-center">
                                <span class="relative inline-block px-3 py-1 font-semibold leading-tight <?= $classeStatus ?> rounded-full">
                                    <span class="relative"><?= $textoStatus ?></span>
                                </span>
                            </td>
                            <td data-label="Ação" class="px-5 py-3 md:py-5 text-sm md:table-cell md:text-center celula-acao">
                                <div class="flex justify-center items-center space-x-2">
                                    <?php if($e['status_pagamento'] == 'PENDENTE' || $textoStatus == 'ATRASADO'): ?>
                                        <a href="entradas_pagar.php?id=<?= $e['id'] ?>" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-3 rounded text-xs shadow" title="Marcar como Pago">
                                            <i class="bi bi-cash-coin"></i> Pagar
                                        </a>
                                    <?php endif; ?>
                                    <a href="entradas_detalhes.php?id=<?= $e['id'] ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-3 rounded text-xs" title="Ver Detalhes">
                                        <i class="bi bi-search"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include 'toast_handler.php'; ?>
</body>
</html>
