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
                        f.cnpj_cpf as fornecedor_cnpj_cpf
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
                    p.nome as produto_nome, p.tamanho, p.cor, p.preco
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
$classeStatus = 'bg-gray-200 text-gray-700';
$textoStatus = $entrada['status_pagamento'];
$data_vencimento = $entrada['data_vencimento'] ? date('Y-m-d', strtotime($entrada['data_vencimento'])) : null;

if ($entrada['status_pagamento'] == 'PENDENTE' && $data_vencimento && $data_vencimento < $hoje) {
    $classeStatus = 'bg-red-200 text-red-800 border'; $textoStatus = 'ATRASADO';
} elseif ($entrada['status_pagamento'] == 'PENDENTE') {
    $classeStatus = 'bg-yellow-100 text-yellow-800 border';
} elseif ($entrada['status_pagamento'] == 'PAGO') {
    $classeStatus = 'bg-green-200 text-green-800';
} elseif ($entrada['status_pagamento'] == 'CANCELADO') {
    $classeStatus = 'bg-gray-400 text-white';
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
<body class="bg-gray-100">

    <?php include 'menu.php'; ?>

    <div class="container mx-auto px-4 mt-8 mb-10">
        <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
            
            <div class="flex justify-between items-center border-b pb-4 mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Detalhes da Entrada #<?= $entrada['id'] ?></h2>
                <span class="relative inline-block px-3 py-1 font-semibold leading-tight <?= $classeStatus ?> rounded-full text-sm">
                    <span class="relative"><?= $textoStatus ?></span>
                </span>
            </div>

            <!-- Dados da Entrada -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div>
                    <p class="text-sm font-semibold text-gray-500">Fornecedor:</p>
                    <p class="text-lg font-bold text-gray-800"><?= htmlspecialchars($entrada['fornecedor_nome']) ?></p>
                    <p class="text-sm text-gray-600">CNPJ/CPF: <?= htmlspecialchars($entrada['fornecedor_cnpj_cpf']) ?></p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-500">Data da Entrada:</p>
                    <p class="text-lg font-bold text-gray-800"><?= date('d/m/Y', strtotime($entrada['data_entrada'])) ?></p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-500">Número da Nota/Pedido:</p>
                    <p class="text-lg font-bold text-gray-800"><?= htmlspecialchars($entrada['numero_nota'] ?: 'N/A') ?></p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-500">NF-e / Série:</p>
                    <p class="text-lg font-bold text-gray-800">
                        <?= htmlspecialchars($entrada['numero_nfe'] ?: 'N/A') ?> / <?= htmlspecialchars($entrada['serie_nfe'] ?: 'N/A') ?>
                    </p>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-500">Valor Total da Nota:</p>
                    <p class="text-2xl font-extrabold text-roxo-base">R$ <?= number_format($entrada['valor_total'], 2, ',', '.') ?></p>
                </div>
                <div class="md:col-span-3">
                    <p class="text-sm font-semibold text-gray-500">Chave de Acesso:</p>
                    <p class="text-sm font-mono text-gray-800 break-all"><?= htmlspecialchars($entrada['chave_acesso'] ?: 'N/A') ?></p>
                </div>
            </div>

            <!-- Contas a Pagar -->
            <div class="border-t pt-4 mt-4">
                <h3 class="text-xl font-bold text-gray-800 mb-3">Contas a Pagar</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-500">Vencimento:</p>
                        <p class="text-lg font-bold text-gray-800">
                            <?= $data_vencimento ? date('d/m/Y', strtotime($data_vencimento)) : 'À Vista' ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500">Status:</p>
                        <p class="text-lg font-bold text-gray-800"><?= $textoStatus ?></p>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-sm font-semibold text-gray-500">Observações:</p>
                    <p class="text-base text-gray-700 italic"><?= htmlspecialchars($entrada['observacoes'] ?: 'Nenhuma observação.') ?></p>
                </div>
            </div>

            <!-- Itens da Entrada -->
            <div class="border-t pt-4 mt-6">
                <h3 class="text-xl font-bold text-gray-800 mb-3">Produtos Recebidos (<?= count($itens) ?> itens)</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border border-gray-200 rounded-lg">
                        <thead>
                            <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <th class="px-4 py-3">Produto</th>
                                <th class="px-4 py-3 text-center">Qtd.</th>
                                <th class="px-4 py-3 text-right">Custo Unitário</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                                <th class="px-4 py-3 text-right">Preço Venda Atual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itens as $item): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?= htmlspecialchars($item['produto_nome']) ?> 
                                        <span class="text-xs text-gray-500">(Tam: <?= $item['tamanho'] ?> | Cor: <?= $item['cor'] ?>)</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-center"><?= $item['quantidade'] ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-700 text-right">R$ <?= number_format($item['preco_custo_momento'], 2, ',', '.') ?></td>
                                    <td class="px-4 py-3 text-sm font-bold text-gray-800 text-right">R$ <?= number_format($item['preco_custo_momento'] * $item['quantidade'], 2, ',', '.') ?></td>
                                    <td class="px-4 py-3 text-sm text-green-600 font-semibold text-right">R$ <?= number_format($item['preco'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="entradas_lista.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded transition">
                    <i class="bi bi-arrow-left"></i> Voltar para a Lista
                </a>
                <?php if($entrada['status_pagamento'] == 'PENDENTE' || $textoStatus == 'ATRASADO'): ?>
                    <a href="entradas_pagar.php?id=<?= $entrada['id'] ?>" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded transition">
                        <i class="bi bi-cash-coin"></i> Marcar como Pago
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>
</html>
