<?php
require_once 'auth_check.php';
require_once 'conexao.php';
require_once 'whatsapp_utils.php';

// Título da Página
$titulo_pagina = "Detalhes da Sacola";

if (!isset($_GET['id'])) {
    header("Location: condicionais_lista.php");
    exit;
}
$cond_id = $_GET['id'];
$toast_msg = ''; 
$toast_type = '';

// --- HANDLER: ATUALIZAR PREÇO DO ITEM ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'mudar_preco') {
    try {
        $item_id = (int)$_POST['item_id'];
        $novo_preco_raw = str_replace(['R$', ' '], '', $_POST['novo_preco']);
        $novo_preco = (float)str_replace(',', '.', $novo_preco_raw);

        // Blindagem
        if ($novo_preco < 0) {
            throw new Exception("O preço não pode ser negativo.");
        }

        $stmt = $pdo->prepare("UPDATE itens_condicional SET preco_momento = ? WHERE id = ? AND condicional_id = ?");
        $stmt->execute([$novo_preco, $item_id, $cond_id]);
        
        $msg_sucesso = "Preço do item atualizado com sucesso!";
        header("Location: condicionais_detalhes.php?id=$cond_id&msg=" . urlencode($msg_sucesso) . "&type=success");
        exit;

    } catch (Exception $e) { 
        $toast_msg = "Erro: " . $e->getMessage();
        $toast_type = "error";
    }
}

// --- CARREGAMENTO DOS DADOS ---
try {
    $stmt_cond = $pdo->prepare("
        SELECT c.*, cl.nome, cl.cpf, cl.telefone,
               e.logradouro, e.numero, e.bairro, e.cidade, e.estado, e.complemento
        FROM condicionais c 
        JOIN clientes cl ON c.cliente_id = cl.id
        LEFT JOIN enderecos e ON cl.id = e.cliente_id
        WHERE c.id = ?");
    $stmt_cond->execute([$cond_id]);
    $condicional = $stmt_cond->fetch();
    if (!$condicional) die("Condicional não encontrado.");

    $stmt_itens = $pdo->prepare("
        SELECT i.*, p.nome, p.imagem, p.preco_custo 
        FROM itens_condicional i JOIN produtos p ON i.produto_id = p.id
        WHERE i.condicional_id = ?
    ");
    $stmt_itens->execute([$cond_id]);
    $itens = $stmt_itens->fetchAll();

    // Cálculos Financeiros
    $total_sacola = 0; $total_vendido = 0; $total_devolvido = 0; $lucro_apurado = 0;
    foreach ($itens as $item) {
        $valor_item = $item['preco_momento'] * $item['quantidade'];
        $total_sacola += $valor_item;
        if ($item['status_item'] == 'VENDIDO') {
            $total_vendido += $valor_item;
            $custo_total_item = $item['preco_custo'] * $item['quantidade'];
            $lucro_apurado += ($valor_item - $custo_total_item);
        } else {
            $total_devolvido += $valor_item;
        }
    }
} catch (PDOException $e) {
    die("Erro ao buscar detalhes: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Detalhes do Condicional #<?= $cond_id ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-50 font-sans text-gray-900">

    <?php include 'menu.php'; ?>

    <?php if (!empty($toast_msg)): ?>
        <div id='auto-toast' class='fixed top-5 right-5 z-[100] p-4 rounded-lg shadow-lg font-bold w-full max-w-sm transition-all duration-500 border bg-red-100 border-red-200 text-red-800' role='alert'>
            <div class='flex items-center'>
                <span class='text-xl mr-3'><i class="bi bi-exclamation-triangle-fill"></i></span>
                <span class='flex-grow text-sm'><?= $toast_msg ?></span>
                <button onclick='document.getElementById("auto-toast").remove()' class='ml-4 text-xl opacity-60 hover:opacity-100'>&times;</button>
            </div>
        </div>
    <?php endif; ?>

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
                    <h1 class="text-2xl font-bold text-gray-800">Detalhes da Sacola</h1>
                    <p class="text-sm text-gray-500">ID #<?= $cond_id ?> - <?= htmlspecialchars($condicional['nome']) ?></p>
                </div>
                <a href="condicionais_lista.php" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-roxo-base transition-colors">
                    <i class="bi bi-arrow-left mr-2"></i> Voltar para Lista
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="p-6 md:p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <div>
                        <h3 class="text-sm font-bold text-gray-400 uppercase mb-4">Cliente</h3>
                        <div class="flex items-start">
                            <div class="w-12 h-12 rounded-full bg-purple-100 text-roxo-base flex items-center justify-center font-bold text-xl mr-4 flex-shrink-0">
                                <?= substr($condicional['nome'], 0, 1) ?>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-gray-800"><?= htmlspecialchars($condicional['nome']) ?></p>
                                <div class="flex flex-col gap-1 text-sm text-gray-500 mt-1">
                                    <span><i class="bi bi-whatsapp text-green-500 mr-1"></i> <?= $condicional['telefone'] ?></span>
                                    <span><i class="bi bi-card-heading text-gray-400 mr-1"></i> <?= $condicional['cpf'] ?></span>
                                </div>
                                <div class="text-sm text-gray-600 mt-2 bg-gray-50 p-2 rounded border border-gray-100">
                                    <i class="bi bi-geo-alt text-gray-400 mr-1"></i> 
                                    <?= htmlspecialchars($condicional['logradouro']) ?>, <?= htmlspecialchars($condicional['numero']) ?> - <?= htmlspecialchars($condicional['bairro']) ?>
                                </div>
                                
                                <?php 
                                    $condicional_data = [
                                        'id' => $condicional['id'],
                                        'status' => $condicional['status'],
                                        'data_prevista_retorno' => $condicional['data_prevista_retorno'],
                                        'cliente_nome' => $condicional['nome'],
                                        'telefone' => $condicional['telefone']
                                    ];
                                    $link_whatsapp = gerarLinkWhatsApp($pdo, $condicional_data);
                                ?>
                                <a href="<?= $link_whatsapp ?>" target="_blank" class="inline-flex items-center justify-center w-full mt-3 bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg text-sm shadow-sm transition-colors">
                                    <i class="bi bi-whatsapp mr-2 text-lg"></i> Enviar Mensagem
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                        <h3 class="text-sm font-bold text-gray-400 uppercase mb-3">Status da Sacola</h3>
                        <div class="flex items-center justify-between mb-4">
                            <span class="px-4 py-1.5 rounded-full text-sm font-bold inline-block
                                <?= $condicional['status'] == 'FINALIZADO' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-roxo-base' ?>">
                                <?= $condicional['status'] ?>
                            </span>
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Data de Saída:</span>
                                <span class="font-bold text-gray-800"><?= date('d/m/Y', strtotime($condicional['data_saida'])) ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Previsão Retorno:</span>
                                <span class="font-bold text-gray-800"><?= date('d/m/Y', strtotime($condicional['data_prevista_retorno'])) ?></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <h3 class="text-lg font-bold text-gray-800 mb-4">Resumo Financeiro</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-wide mb-1">Total da Sacola</p>
                    <p class="text-2xl font-bold text-gray-800">R$ <?= number_format($total_sacola, 2, ',', '.') ?></p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-green-600 uppercase tracking-wide mb-1">Total Vendido</p>
                    <p class="text-2xl font-bold text-gray-800">R$ <?= number_format($total_vendido, 2, ',', '.') ?></p>
                </div>
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Devolvido</p>
                    <p class="text-2xl font-bold text-gray-400">R$ <?= number_format($total_devolvido, 2, ',', '.') ?></p>
                </div>
                <div class="bg-green-50 p-5 rounded-xl shadow-sm border border-green-200">
                    <p class="text-xs font-bold text-green-800 uppercase tracking-wide mb-1">Lucro Apurado</p>
                    <p class="text-2xl font-bold text-green-700">R$ <?= number_format($lucro_apurado, 2, ',', '.') ?></p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800">Itens da Operação</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="px-6 py-3" colspan="2">Produto</th>
                                <th class="px-6 py-3 text-center">Qtd</th>
                                <th class="px-6 py-3 text-center">Venda (Un)</th>
                                <th class="px-6 py-3 text-center">Custo (Un)</th>
                                <th class="px-6 py-3 text-right">Status Final</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php foreach ($itens as $item): ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 w-16">
                                    <div class="h-12 w-12 rounded-lg bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center">
                                        <?php if (!empty($item['imagem'])): ?>
                                            <img src="uploads/<?= $item['imagem'] ?>" class="h-full w-full object-cover">
                                        <?php else: ?>
                                            <i class="bi bi-tshirt-fill text-gray-400 text-xl"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-2 py-4 font-bold text-gray-800"><?= $item['nome'] ?></td>
                                <td class="px-6 py-4 text-center text-gray-600"><?= $item['quantidade'] ?></td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center">
                                        <span id="texto-preco-<?= $item['id'] ?>" class="font-bold text-gray-800">
                                            R$ <?= number_format($item['preco_momento'], 2, ',', '.') ?>
                                        </span>
                                        
                                        <form method="POST" id="form-preco-<?= $item['id'] ?>" class="hidden flex items-center ml-2">
                                            <input type="hidden" name="acao" value="mudar_preco">
                                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                            <input type="text" name="novo_preco" value="<?= number_format($item['preco_momento'], 2, ',', '.') ?>" 
                                                   class="w-20 text-center text-sm font-bold border border-roxo-base rounded px-1 py-0.5 focus:outline-none">
                                            <button type="submit" class="ml-1 text-green-600 hover:text-green-800"><i class="bi bi-check-lg"></i></button>
                                            <button type="button" onclick="toggleEdit(<?= $item['id'] ?>)" class="ml-1 text-red-500 hover:text-red-700"><i class="bi bi-x-lg"></i></button>
                                        </form>

                                        <?php if ($condicional['status'] !== 'FINALIZADO'): ?>
                                            <button id="btn-preco-<?= $item['id'] ?>" onclick="toggleEdit(<?= $item['id'] ?>)" class="ml-2 text-gray-400 hover:text-roxo-base transition-colors" title="Editar Preço">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center text-gray-500">R$ <?= number_format($item['preco_custo'], 2, ',', '.') ?></td>
                                <td class="px-6 py-4 text-right">
                                    <?php if($item['status_item'] == 'VENDIDO'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            VENDIDO
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?= $item['status_item'] == 'DEVOLVIDO' ? 'DEVOLVIDO' : 'NA SACOLA' ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        function toggleEdit(itemId) {
            document.getElementById('texto-preco-' + itemId).classList.toggle('hidden');
            document.getElementById('btn-preco-' + itemId).classList.toggle('hidden');
            document.getElementById('form-preco-' + itemId).classList.toggle('hidden');
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.getElementById('auto-toast');
            if (toast) {
                setTimeout(() => {
                    toast.classList.add('opacity-0', '-translate-y-5');
                    setTimeout(() => toast.remove(), 500); 
                }, 4000);
            }
        });
    </script>

    <?php include 'toast_handler.php'; ?>
</body>
</html>