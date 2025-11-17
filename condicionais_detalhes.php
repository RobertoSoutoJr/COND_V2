<?php
require_once 'auth_check.php';
require_once 'conexao.php';

// Título da Página
$titulo_pagina = "Detalhes da Sacola";

if (!isset($_GET['id'])) {
    header("Location: condicionais_lista.php");
    exit;
}
$cond_id = $_GET['id'];
$toast_msg = ''; // Variável para o toast de erro
$toast_type = '';

// --- NOVO HANDLER: ATUALIZAR PREÇO DO ITEM ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'mudar_preco') {
    try {
        $item_id = (int)$_POST['item_id'];
        // Remove R$, espaços e troca vírgula por ponto
        $novo_preco_raw = str_replace(['R$', ' '], '', $_POST['novo_preco']);
        $novo_preco = (float)str_replace(',', '.', $novo_preco_raw);

        // --- INÍCIO DA BLINDAGEM ---
        if ($novo_preco < 0) {
            // Se o preço for negativo, lança um erro que será apanhado pelo 'catch'
            throw new Exception("O preço não pode ser negativo.");
        }
        // --- FIM DA BLINDAGEM ---

        $stmt = $pdo->prepare("UPDATE itens_condicional SET preco_momento = ? WHERE id = ? AND condicional_id = ?");
        $stmt->execute([$novo_preco, $item_id, $cond_id]);
        
        // Sucesso: Redireciona com mensagem de sucesso
        $msg_sucesso = "Preço do item atualizado com sucesso!";
        header("Location: condicionais_detalhes.php?id=$cond_id&msg=" . urlencode($msg_sucesso) . "&type=success");
        exit;

    } catch (Exception $e) { // Alterado de PDOException para Exception
        // Falha: Prepara a mensagem de erro para o Toast
        $toast_msg = "Erro: " . $e->getMessage();
        $toast_type = "error";
    }
}

// --- CARREGAMENTO DOS DADOS (Com Endereço) ---
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
<body class="bg-gray-100 pb-20">

    <?php include 'menu.php'; ?>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($toast_msg)) {
        $bgColor = 'bg-red-100 border border-red-300 text-red-800';
        $icon = '<i class="bi bi-exclamation-triangle-fill"></i>';
        
        echo "
        <div id='auto-toast' class='fixed top-5 left-1/2 -translate-x-1/2 z-[100] p-4 rounded-lg shadow-lg font-bold w-full max-w-md transition-opacity duration-300 $bgColor' role='alert'>
            <div class='flex items-center'>
                <span class='text-xl mr-3'>$icon</span>
                <span class='flex-grow'>$toast_msg</span>
                <button onclick='document.getElementById(\"auto-toast\").remove()' class='ml-4 text-2xl font-light opacity-70 hover:opacity-100'>&times;</button>
            </div>
        </div>
        ";
    }
    ?>

    <div class="container mx-auto mt-10 px-4">
        
        <a href="condicionais_lista.php" class="text-roxo-base hover:underline mb-4 inline-block">&larr; Voltar para a Lista</a>

        <div class="bg-white p-8 rounded-lg shadow-lg">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-b pb-6 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Detalhes #<?= $cond_id ?></h2>
                    <p class="text-gray-600 text-lg mt-1 font-bold"><?= htmlspecialchars($condicional['nome']) ?></p>
                    <p class="text-gray-500">CPF: <?= $condicional['cpf'] ?> / Tel: <?= $condicional['telefone'] ?></p>
                    <div class="mt-4 border-t pt-4">
                        <p class="text-xs text-gray-400 uppercase font-bold">Endereço</p>
                        <p class="text-gray-600"><?= htmlspecialchars($condicional['logradouro']) ?>, <?= htmlspecialchars($condicional['numero']) ?> - <?= htmlspecialchars($condicional['bairro']) ?></p>
                    </div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg self-start">
                    <h3 class="font-bold text-gray-600 mb-2">Linha do Tempo</h3>
                    <p>Status: <span class="font-bold <?= $condicional['status'] == 'FINALIZADO' ? 'text-green-600' : 'text-yellow-600' ?>"><?= $condicional['status'] ?></span></p>
                    <p>Data Saída: <span class="font-bold"><?= date('d/m/Y', strtotime($condicional['data_saida'])) ?></span></p>
                    <p>Data Retorno: <span class="font-bold"><?= date('d/m/Y', strtotime($condicional['data_prevista_retorno'])) ?></span></p>
                </div>
            </div>

            <h3 class="text-xl font-bold text-gray-800 mb-4">Resumo Financeiro</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-blue-50 p-4 rounded-lg text-center"><p class="text-sm text-blue-800 font-bold uppercase">Total da Sacola</p><p class="text-2xl font-bold text-blue-900">R$ <?= number_format($total_sacola, 2, ',', '.') ?></p></div>
                <div class="bg-green-50 p-4 rounded-lg text-center"><p class="text-sm text-green-800 font-bold uppercase">Total Vendido</p><p class="text-2xl font-bold text-green-900">R$ <?= number_format($total_vendido, 2, ',', '.') ?></p></div>
                <div class="bg-gray-100 p-4 rounded-lg text-center"><p class="text-sm text-gray-600 font-bold uppercase">Devolvido</p><p class="text-2xl font-bold text-gray-800">R$ <?= number_format($total_devolvido, 2, ',', '.') ?></p></div>
                <div class="bg-green-100 p-4 rounded-lg text-center border border-green-300"><p class="text-sm text-green-800 font-bold uppercase">Lucro Apurado</p><p class="text-2xl font-bold text-green-900">R$ <?= number_format($lucro_apurado, 2, ',', '.') ?></p></div>
            </div>

            <h3 class="text-xl font-bold text-gray-800 mb-4">Itens da Operação</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-3 px-4 text-left" colspan="2">Produto</th>
                            <th class="py-3 px-4 text-center">Qtd</th>
                            <th class="py-3 px-4 text-center">Preço Venda (Unit)</th>
                            <th class="py-3 px-4 text-center">Custo (Unit)</th>
                            <th class="py-3 px-4 text-right">Status Final</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $item): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-4 w-16">
                                <div class="w-12 h-12 rounded overflow-hidden border">
                                    <img src="uploads/<?= $item['imagem'] ?: 'default.png' ?>" class="w-full h-full object-cover">
                                </div>
                            </td>
                            <td class="py-3 px-4 font-bold"><?= $item['nome'] ?></td>
                            <td class="py-3 px-4 text-center"><?= $item['quantidade'] ?></td>
                            <td class="py-3 px-4 text-center">
                                <span id="texto-preco-<?= $item['id'] ?>" class="font-bold text-base">R$ <?= number_format($item['preco_momento'], 2, ',', '.') ?></span>
                                <form method="POST" id="form-preco-<?= $item['id'] ?>" class="hidden">
                                    <input type="hidden" name="acao" value="mudar_preco">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    <input type="text" name="novo_preco" value="<?= number_format($item['preco_momento'], 2, ',', '.') ?>" class="w-24 text-center font-bold border rounded py-1 px-2">
                                    <button type="submit" class="bg-green-500 text-white py-1 px-2 rounded text-xs">OK</button>
                                    <button type="button" onclick="toggleEdit(<?= $item['id'] ?>)" class="text-gray-500 py-1 px-2 text-xs">X</button>
                                </form>
                                <?php if ($condicional['status'] !== 'FINALIZADO'): ?>
                                    <button id="btn-preco-<?= $item['id'] ?>" onclick="toggleEdit(<?= $item['id'] ?>)" class="text-blue-600 hover:underline text-xs ml-2">[Alterar]</button>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-center text-sm text-gray-500">R$ <?= number_format($item['preco_custo'], 2, ',', '.') ?></td>
                            <td class="py-3 px-4 text-right">
                                <?php if($item['status_item'] == 'VENDIDO'): ?>
                                    <span class="bg-green-100 text-green-800 py-1 px-3 rounded-full text-xs font-bold">VENDIDO</span>
                                <?php else: ?>
                                    <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs font-bold"><?= $item['status_item'] == 'DEVOLVIDO' ? 'DEVOLVIDO' : 'NA SACOLA' ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function toggleEdit(itemId) {
            document.getElementById('texto-preco-' + itemId).classList.toggle('hidden');
            document.getElementById('btn-preco-' + itemId).classList.toggle('hidden');
            document.getElementById('form-preco-' + itemId).classList.toggle('hidden');
        }
    </script>

    <?php include 'toast_handler.php'; ?>
</body>
</html>