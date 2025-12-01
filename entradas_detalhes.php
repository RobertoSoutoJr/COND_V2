<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

// Título da Página
$titulo_pagina = "Detalhes da Entrada";

$id_entrada = (int)($_GET['id'] ?? 0);

if ($id_entrada === 0) {
    header("Location: entradas_lista.php?msg=" . urlencode("Erro: ID da entrada não fornecido.") . "&type=error");
    exit;
}

try {
    // 1. Buscar dados da Entrada (Cabeçalho)
    $sql_entrada = "SELECT 
                        e.*, 
                        f.nome as fornecedor_nome, 
                        f.cnpj_cpf as fornecedor_cnpj_cpf,
                        f.telefone as fornecedor_telefone,
                        f.email as fornecedor_email
                    FROM entradas_produto e
                    JOIN fornecedores f ON e.fornecedor_id = f.id
                    WHERE e.id = ?";
    $stmt_entrada = $pdo->prepare($sql_entrada);
    $stmt_entrada->execute([$id_entrada]);
    $entrada = $stmt_entrada->fetch();

    if (!$entrada) {
        header("Location: entradas_lista.php?msg=" . urlencode("Erro: Entrada não encontrada.") . "&type=error");
        exit;
    }

    // 2. Buscar Itens da Entrada
    $sql_itens = "SELECT 
                    ie.quantidade, ie.preco_custo_momento,
                    p.nome as produto_nome, p.tamanho, p.cor, p.preco, p.imagem
                  FROM itens_entrada ie
                  JOIN produtos p ON ie.produto_id = p.id
                  WHERE ie.entrada_id = ?";
    $stmt_itens = $pdo->prepare($sql_itens);
    $stmt_itens->execute([$id_entrada]);
    $itens = $stmt_itens->fetchAll();

} catch (PDOException $e) {
    die("Erro ao carregar detalhes da entrada: " . $e->getMessage());
}

// Lógica de Status
$hoje = date('Y-m-d');
$classeStatus = 'bg-gray-100 text-gray-700 border border-gray-200';
$textoStatus = $entrada['status_pagamento'];
$data_vencimento = $entrada['data_vencimento'] ? date('Y-m-d', strtotime($entrada['data_vencimento'])) : null;

if ($entrada['status_pagamento'] == 'PENDENTE' && $data_vencimento && $data_vencimento < $hoje) {
    $classeStatus = 'bg-red-50 text-red-700 border border-red-200'; 
    $textoStatus = 'ATRASADO';
} elseif ($entrada['status_pagamento'] == 'PENDENTE') {
    $classeStatus = 'bg-yellow-50 text-yellow-700 border border-yellow-200';
} elseif ($entrada['status_pagamento'] == 'PAGO') {
    $classeStatus = 'bg-green-50 text-green-700 border border-green-200';
} elseif ($entrada['status_pagamento'] == 'CANCELADO') {
    $classeStatus = 'bg-gray-200 text-gray-500 border border-gray-300';
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Detalhes da Entrada #<?= $id_entrada ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
                    <h1 class="text-2xl font-bold text-gray-800">Detalhes da Entrada</h1>
                    <p class="text-sm text-gray-500">Visualizando entrada #<?= $entrada['id'] ?></p>
                </div>
                <div class="flex gap-3">
                    <a href="entradas_lista.php" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-roxo-base transition-colors">
                        <i class="bi bi-arrow-left mr-2"></i> Voltar
                    </a>
                    <?php if($entrada['status_pagamento'] == 'PENDENTE' || $textoStatus == 'ATRASADO'): ?>
                        <a href="entradas_pagar.php?id=<?= $entrada['id'] ?>" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition flex items-center text-sm">
                            <i class="bi bi-cash-coin mr-2"></i> Pagar Agora
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                
                <div class="lg:col-span-2 space-y-6">
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden p-6">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">Informações da Nota</h3>
                                <p class="text-sm text-gray-500">Dados fiscais e de recebimento</p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide <?= $classeStatus ?>">
                                <?= $textoStatus ?>
                            </span>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase">Data Entrada</p>
                                <p class="text-gray-800 font-medium"><?= date('d/m/Y', strtotime($entrada['data_entrada'])) ?></p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase">Nº Nota / Pedido</p>
                                <p class="text-gray-800 font-medium"><?= htmlspecialchars($entrada['numero_nota'] ?: '-') ?></p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase">NF-e / Série</p>
                                <p class="text-gray-800 font-medium">
                                    <?= htmlspecialchars($entrada['numero_nfe'] ?: '-') ?> 
                                    <span class="text-gray-400 text-xs">/ <?= htmlspecialchars($entrada['serie_nfe'] ?: '-') ?></span>
                                </p>
                            </div>
                            <div class="col-span-2 md:col-span-3">
                                <p class="text-xs font-bold text-gray-400 uppercase">Chave de Acesso</p>
                                <p class="text-xs font-mono text-gray-600 break-all bg-gray-50 p-2 rounded border border-gray-100 mt-1">
                                    <?= htmlspecialchars($entrada['chave_acesso'] ?: 'Não informada') ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="text-lg font-bold text-gray-800">Itens Recebidos</h3>
                            <span class="text-xs font-bold bg-purple-100 text-roxo-base px-2 py-1 rounded-full"><?= count($itens) ?> itens</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left">
                                <thead class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <tr>
                                        <th class="px-6 py-3">Produto</th>
                                        <th class="px-6 py-3 text-center">Qtd.</th>
                                        <th class="px-6 py-3 text-right">Custo Un.</th>
                                        <th class="px-6 py-3 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-sm">
                                    <?php foreach ($itens as $item): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-6 py-3">
                                                <div class="flex items-center">
                                                    <div class="h-10 w-10 rounded bg-gray-100 border border-gray-200 flex-shrink-0 overflow-hidden flex items-center justify-center">
                                                        <?php if (!empty($item['imagem'])): ?>
                                                            <img src="uploads/<?= $item['imagem'] ?>" class="h-full w-full object-cover">
                                                        <?php else: ?>
                                                            <i class="bi bi-box-seam text-gray-400"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="font-medium text-gray-900"><?= htmlspecialchars($item['produto_nome']) ?></p>
                                                        <p class="text-xs text-gray-500"><?= $item['tamanho'] ?> | <?= $item['cor'] ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-3 text-center font-medium text-gray-700"><?= $item['quantidade'] ?></td>
                                            <td class="px-6 py-3 text-right text-gray-600">R$ <?= number_format($item['preco_custo_momento'], 2, ',', '.') ?></td>
                                            <td class="px-6 py-3 text-right font-bold text-gray-800">R$ <?= number_format($item['preco_custo_momento'] * $item['quantidade'], 2, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <div class="space-y-6">
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-sm font-bold text-gray-400 uppercase mb-4">Fornecedor</h3>
                        <div class="flex items-start mb-4">
                            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-lg mr-3 flex-shrink-0">
                                <?= substr($entrada['fornecedor_nome'], 0, 1) ?>
                            </div>
                            <div>
                                <p class="text-base font-bold text-gray-800 leading-tight"><?= htmlspecialchars($entrada['fornecedor_nome']) ?></p>
                                <p class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($entrada['fornecedor_cnpj_cpf']) ?></p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600">
                            <p class="flex items-center"><i class="bi bi-telephone mr-2 text-gray-400"></i> <?= $entrada['fornecedor_telefone'] ?: '-' ?></p>
                            <p class="flex items-center"><i class="bi bi-envelope mr-2 text-gray-400"></i> <?= $entrada['fornecedor_email'] ?: '-' ?></p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-sm font-bold text-gray-400 uppercase mb-4">Resumo Financeiro</h3>
                        
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-600">Valor Total</span>
                            <span class="text-2xl font-extrabold text-gray-800">R$ <?= number_format($entrada['valor_total'], 2, ',', '.') ?></span>
                        </div>
                        
                        <div class="border-t border-gray-100 my-4 pt-4">
                            <div class="flex justify-between items-center text-sm mb-2">
                                <span class="text-gray-500">Vencimento</span>
                                <span class="font-bold text-gray-800">
                                    <?= $data_vencimento ? date('d/m/Y', strtotime($data_vencimento)) : 'À Vista' ?>
                                </span>
                            </div>
                            <?php if ($data_vencimento && $data_vencimento < $hoje && $entrada['status_pagamento'] == 'PENDENTE'): ?>
                                <div class="bg-red-50 text-red-700 text-xs px-3 py-2 rounded mt-2 flex items-center">
                                    <i class="bi bi-exclamation-circle-fill mr-2"></i> Vencido há <?= (strtotime($hoje) - strtotime($data_vencimento)) / 86400 ?> dias
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-4">
                            <p class="text-xs font-bold text-gray-400 uppercase mb-1">Observações</p>
                            <p class="text-sm text-gray-600 italic bg-gray-50 p-3 rounded border border-gray-100">
                                <?= htmlspecialchars($entrada['observacoes'] ?: 'Nenhuma observação registrada.') ?>
                            </p>
                        </div>
                    </div>

                </div>

            </div>

        </main>
    </div>

</body>
</html>