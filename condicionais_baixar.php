<?php
require_once 'conexao.php';

if (!isset($_GET['id'])) {
    header("Location: condicionais_lista.php");
    exit;
}
$cond_id = $_GET['id'];
$mensagem = '';

// --- PROCESSAMENTO DO FORMULÁRIO (BAIXA) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $acoes = $_POST['acao'] ?? [];

        foreach ($acoes as $item_id => $acao) {
            // Busca dados do item
            $stmt_item = $pdo->prepare("SELECT produto_id, quantidade FROM itens_condicional WHERE id = ? AND status_item = 'EM_CONDICIONAL'");
            $stmt_item->execute([$item_id]);
            $item = $stmt_item->fetch();

            if ($item) {
                if ($acao === 'devolveu') {
                    // 1. Devolveu
                    $pdo->prepare("UPDATE itens_condicional SET status_item = 'DEVOLVIDO' WHERE id = ?")->execute([$item_id]);
                    $pdo->prepare("UPDATE produtos SET estoque_loja = estoque_loja + ? WHERE id = ?")->execute([$item['quantidade'], $item['produto_id']]);
                } elseif ($acao === 'vendido') {
                    // 2. Vendeu
                    $pdo->prepare("UPDATE itens_condicional SET status_item = 'VENDIDO' WHERE id = ?")->execute([$item_id]);
                }
            }
        }

        // Verifica se fechou a sacola
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM itens_condicional WHERE condicional_id = ? AND status_item = 'EM_CONDICIONAL'");
        $stmt_check->execute([$cond_id]);
        $pendentes = $stmt_check->fetchColumn();

        if ($pendentes == 0) {
            $pdo->prepare("UPDATE condicionais SET status = 'FINALIZADO' WHERE id = ?")->execute([$cond_id]);
        }
        $pdo->commit();

        $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>Baixa realizada com sucesso!</div>";
        // Recarregar a página para limpar os botões de rádio
        header("Location: condicionais_baixar.php?id=$cond_id&msg=sucesso");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Erro: " . $e->getMessage() . "</div>";
    }
}

// --- CARREGAR DADOS PARA EXIBIÇÃO ---
try {
    // Dados do Cabeçalho
    $stmt = $pdo->prepare("SELECT c.*, cl.nome, cl.cpf FROM condicionais c JOIN clientes cl ON c.cliente_id = cl.id WHERE c.id = ?");
    $stmt->execute([$cond_id]);
    $condicional = $stmt->fetch();

    // Itens da Sacola (COM A IMAGEM)
    $stmt_itens = $pdo->prepare("
        SELECT i.*, p.nome, p.tamanho, p.cor, p.imagem 
        FROM itens_condicional i 
        JOIN produtos p ON i.produto_id = p.id 
        WHERE i.condicional_id = ?
    ");
    $stmt_itens->execute([$cond_id]);
    $itens = $stmt_itens->fetchAll();

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}

// Mensagem de sucesso (após redirect)
if (isset($_GET['msg']) && $_GET['msg'] == 'sucesso') {
    $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>Baixa realizada com sucesso!</div>";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Baixar Condicional #<?= $cond_id ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          // Aqui está sua cor personalizada
          'roxo-base': '#6753d8', // que é o seu rgba(103, 83, 216)
        }
      }
    }
  }
</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-100 pb-20">

    <?php include 'menu.php'; ?>

    <div class="container mx-auto mt-10 px-4">
        <div class="max-w-5xl mx-auto bg-white p-8 rounded-lg shadow-lg">
            
            <div class="border-b pb-4 mb-6 flex justify-between items-start">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Recebimento #<?= $condicional['id'] ?></h2>
                    <p class="text-gray-600 text-lg mt-1">Cliente: <span class="font-bold"><?= htmlspecialchars($condicional['nome']) ?></span></p>
                </div>
                <div>
                    <span class="px-3 py-1 rounded-full text-sm font-bold 
                        <?= $condicional['status'] == 'FINALIZADO' ? 'bg-green-200 text-green-800' : 'bg-blue-200 text-blue-800' ?>">
                        Status: <?= $condicional['status'] ?>
                    </span>
                </div>
            </div>

            <?= $mensagem ?>

            <form method="POST" action="">
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                                <th class="py-3 px-6 text-left" colspan="2">Produto</th>
                                <th class="py-3 px-6 text-center">Preço</th>
                                <th class="py-3 px-6 text-center">Qtd</th>
                                <th class="py-3 px-6 text-center">Ação (Retorno)</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 text-sm font-light">
                            <?php 
                            $total_vendido = 0;
                            foreach ($itens as $item): 
                                if($item['status_item'] == 'VENDIDO') $total_vendido += ($item['preco_momento'] * $item['quantidade']);
                            ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    
                                    <td class="py-3 px-4 text-left w-16">
                                        <div class="w-12 h-12 rounded overflow-hidden border bg-gray-100 flex items-center justify-center">
                                            <?php if (!empty($item['imagem'])): ?>
                                                <img src="uploads/<?= $item['imagem'] ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <span class="text-xl">👗</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="py-3 px-2 text-left font-bold">
                                        <?= htmlspecialchars($item['nome']) ?>
                                        <div class="text-xs font-normal text-gray-500"><?= $item['tamanho'] ?> / <?= $item['cor'] ?></div>
                                    </td>
                                    
                                    <td class="py-3 px-6 text-center">
                                        R$ <?= number_format($item['preco_momento'], 2, ',', '.') ?>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        <?= $item['quantidade'] ?>
                                    </td>
                                    <td class="py-3 px-6 text-center">
                                        
                                        <?php if ($item['status_item'] == 'EM_CONDICIONAL'): ?>
                                            <div class="flex justify-center space-x-4">
                                                <label class="flex items-center space-x-2 cursor-pointer p-2 rounded hover:bg-green-50">
                                                    <input type="radio" name="acao[<?= $item['id'] ?>]" value="vendido" class="form-radio text-green-600 h-4 w-4">
                                                    <span class="text-green-700 font-bold">Vendido</span>
                                                </label>
                                                <label class="flex items-center space-x-2 cursor-pointer p-2 rounded hover:bg-blue-50">
                                                    <input type="radio" name="acao[<?= $item['id'] ?>]" value="devolveu" class="form-radio text-blue-600 h-4 w-4" checked> <span class="text-blue-700 font-bold">Devolveu</span>
                                                </label>
                                            </div>
                                        <?php else: ?>
                                            <?php if($item['status_item'] == 'VENDIDO'): ?>
                                                <span class="bg-green-100 text-green-800 py-1 px-3 rounded-full text-xs font-bold">VENDIDO</span>
                                            <?php else: ?>
                                                <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs font-bold">DEVOLVIDO</span>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 text-right">
                    <p class="text-xl text-gray-600">Total já vendido nesta sacola:</p>
                    <p class="text-3xl font-bold text-green-600">R$ <?= number_format($total_vendido, 2, ',', '.') ?></p>
                </div>

                <?php if ($condicional['status'] != 'FINALIZADO'): ?>
                    <div class="mt-8 flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-3 px-8 rounded-lg shadow-lg transition">
                            Processar Retorno
                        </button>
                    </div>
                <?php endif; ?>

            </form>
        </div>
    </div>
</body>
</html>