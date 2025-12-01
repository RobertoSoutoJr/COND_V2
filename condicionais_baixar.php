<?php 
require_once 'auth_check.php'; 
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
        $quantidades = $_POST['quantidade'] ?? [];
        $data_finalizacao = date('Y-m-d H:i:s');
        $tipo_acao = $_POST['tipo_acao'] ?? 'processar'; // 'processar' ou 'faturar'

        // PROCESSAR RETORNO: Apenas atualiza estoque e divide o item, mantendo a sacola aberta
        if ($tipo_acao === 'processar') {
            foreach ($acoes as $item_id => $acao) {
                $stmt_item = $pdo->prepare("SELECT produto_id, quantidade FROM itens_condicional WHERE id = ? AND status_item = 'EM_CONDICIONAL'");
                $stmt_item->execute([$item_id]);
                $item = $stmt_item->fetch();

                if ($item && $acao === 'devolveu') {
                    $qtd_devolvida = isset($quantidades[$item_id]) && $quantidades[$item_id] > 0 ? (int)$quantidades[$item_id] : $item['quantidade'];
                    if ($qtd_devolvida > $item['quantidade']) $qtd_devolvida = $item['quantidade'];
                    $qtd_restante = $item['quantidade'] - $qtd_devolvida;

                    // 1. Devolve ao estoque
                    $pdo->prepare("UPDATE produtos SET estoque_loja = estoque_loja + ? WHERE id = ?")->execute([$qtd_devolvida, $item['produto_id']]);

                    // 2. Atualiza o item
                    if ($qtd_restante > 0) {
                        // Parcial
                        $pdo->prepare("UPDATE itens_condicional SET quantidade = ?, status_item = 'DEVOLVIDO' WHERE id = ?")->execute([$qtd_devolvida, $item_id]);
                        $stmt_novo = $pdo->prepare("INSERT INTO itens_condicional (condicional_id, produto_id, quantidade, preco_momento, status_item) SELECT condicional_id, produto_id, ?, preco_momento, 'EM_CONDICIONAL' FROM itens_condicional WHERE id = ?");
                        $stmt_novo->execute([$qtd_restante, $item_id]);
                    } else {
                        // Total
                        $pdo->prepare("UPDATE itens_condicional SET status_item = 'DEVOLVIDO' WHERE id = ?")->execute([$item_id]);
                    }
                }
            }
            $mensagem_tipo = "Retorno processado! Saldo atualizado na sacola.";
        }
        // FATURAR: Finaliza os itens definitivamente
        else {
            foreach ($acoes as $item_id => $acao) {
                $stmt_item = $pdo->prepare("SELECT produto_id, quantidade FROM itens_condicional WHERE id = ? AND status_item = 'EM_CONDICIONAL'");
                $stmt_item->execute([$item_id]);
                $item = $stmt_item->fetch();

                if ($item) {
                    $qtd_acao = isset($quantidades[$item_id]) && $quantidades[$item_id] > 0 ? (int)$quantidades[$item_id] : $item['quantidade'];
                    if ($qtd_acao > $item['quantidade']) $qtd_acao = $item['quantidade'];

                    if ($acao === 'devolveu') {
                        if ($qtd_acao < $item['quantidade']) {
                            $qtd_vendida = $item['quantidade'] - $qtd_acao;
                            $pdo->prepare("UPDATE itens_condicional SET quantidade = ?, status_item = 'DEVOLVIDO' WHERE id = ?")->execute([$qtd_acao, $item_id]);
                            $stmt_novo = $pdo->prepare("INSERT INTO itens_condicional (condicional_id, produto_id, quantidade, preco_momento, status_item) SELECT condicional_id, produto_id, ?, preco_momento, 'VENDIDO' FROM itens_condicional WHERE id = ?");
                            $stmt_novo->execute([$qtd_vendida, $item_id]);
                            $pdo->prepare("UPDATE produtos SET estoque_loja = estoque_loja + ? WHERE id = ?")->execute([$qtd_acao, $item['produto_id']]);
                        } else {
                            $pdo->prepare("UPDATE itens_condicional SET status_item = 'DEVOLVIDO' WHERE id = ?")->execute([$item_id]);
                            $pdo->prepare("UPDATE produtos SET estoque_loja = estoque_loja + ? WHERE id = ?")->execute([$item['quantidade'], $item['produto_id']]);
                        }
                    } elseif ($acao === 'vendido') {
                        if ($qtd_acao < $item['quantidade']) {
                            $qtd_devolvida = $item['quantidade'] - $qtd_acao;
                            $pdo->prepare("UPDATE itens_condicional SET quantidade = ?, status_item = 'VENDIDO' WHERE id = ?")->execute([$qtd_acao, $item_id]);
                            $stmt_novo = $pdo->prepare("INSERT INTO itens_condicional (condicional_id, produto_id, quantidade, preco_momento, status_item) SELECT condicional_id, produto_id, ?, preco_momento, 'DEVOLVIDO' FROM itens_condicional WHERE id = ?");
                            $stmt_novo->execute([$qtd_devolvida, $item_id]);
                            $pdo->prepare("UPDATE produtos SET estoque_loja = estoque_loja + ? WHERE id = ?")->execute([$qtd_devolvida, $item['produto_id']]);
                        } else {
                            $pdo->prepare("UPDATE itens_condicional SET status_item = 'VENDIDO' WHERE id = ?")->execute([$item_id]);
                        }
                    }
                }
            }

            // Verifica se fechou a sacola
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM itens_condicional WHERE condicional_id = ? AND status_item = 'EM_CONDICIONAL'");
            $stmt_check->execute([$cond_id]);
            $pendentes = $stmt_check->fetchColumn();

            // Sempre atualiza data_finalizacao ao faturar
            $pdo->prepare("UPDATE condicionais SET status = 'FINALIZADO', data_finalizacao = ? WHERE id = ?")->execute([$data_finalizacao, $cond_id]);
            $mensagem_tipo = "Sacola faturada com sucesso!";
        }
        
        $pdo->commit();
        header("Location: condicionais_baixar.php?id=$cond_id&msg=sucesso&tipo={$tipo_acao}");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "Erro: " . $e->getMessage();
    }
}

// --- CARREGAR DADOS ---
try {
    $stmt = $pdo->prepare("
        SELECT c.*, cl.nome, cl.cpf, cl.telefone, e.logradouro, e.numero, e.bairro, e.cidade, e.estado, e.complemento
        FROM condicionais c 
        JOIN clientes cl ON c.cliente_id = cl.id
        LEFT JOIN enderecos e ON cl.id = e.cliente_id
        WHERE c.id = ?
    ");
    $stmt->execute([$cond_id]);
    $condicional = $stmt->fetch();

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

// Mensagem via URL
if (isset($_GET['msg']) && $_GET['msg'] == 'sucesso') {
    $tipo = $_GET['tipo'] ?? 'processar';
    $texto = $tipo === 'faturar' ? 'Sacola faturada com sucesso!' : 'Retorno processado! Saldo atualizado.';
    // Vamos usar o toast_handler para exibir isso, mas mantemos a variável para compatibilidade
    $mensagem_toast = $texto;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Baixar Condicional #<?= $cond_id ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-50 font-sans text-gray-900">

    <?php include 'menu.php'; ?>
    
    <?php if (!empty($mensagem) && strpos($mensagem, 'Erro') !== false): ?>
        <div id='auto-toast' class='fixed top-5 right-5 z-[100] p-4 rounded-lg shadow-lg font-bold w-full max-w-sm transition-all duration-500 border bg-red-100 border-red-200 text-red-800' role='alert'>
            <div class='flex items-center'>
                <span class='text-xl mr-3'><i class="bi bi-exclamation-triangle-fill"></i></span>
                <span class='flex-grow text-sm'><?= strip_tags($mensagem) ?></span>
                <button onclick='document.getElementById("auto-toast").remove()' class='ml-4 text-xl opacity-60 hover:opacity-100'>&times;</button>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($mensagem_toast)): ?>
        <div id='auto-toast' class='fixed top-5 right-5 z-[100] p-4 rounded-lg shadow-lg font-bold w-full max-w-sm transition-all duration-500 border bg-green-100 border-green-200 text-green-800' role='alert'>
            <div class='flex items-center'>
                <span class='text-xl mr-3'><i class="bi bi-check-circle-fill"></i></span>
                <span class='flex-grow text-sm'><?= $mensagem_toast ?></span>
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
                    <h1 class="text-2xl font-bold text-gray-800">Recebimento de Sacola</h1>
                    <p class="text-sm text-gray-500">Condicional #<?= $condicional['id'] ?> - <?= date('d/m/Y', strtotime($condicional['data_saida'])) ?></p>
                </div>
                <a href="condicionais_lista.php" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-roxo-base transition-colors">
                    <i class="bi bi-arrow-left mr-2"></i> Voltar para Lista
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="p-6 md:p-8 flex flex-col md:flex-row justify-between gap-6">
                    
                    <div class="flex-1">
                        <h3 class="text-sm font-bold text-gray-400 uppercase mb-2">Cliente</h3>
                        <div class="flex items-center mb-2">
                            <div class="w-10 h-10 rounded-full bg-purple-100 text-roxo-base flex items-center justify-center font-bold text-lg mr-3">
                                <?= substr($condicional['nome'], 0, 1) ?>
                            </div>
                            <div>
                                <p class="text-lg font-bold text-gray-800"><?= htmlspecialchars($condicional['nome']) ?></p>
                                <div class="flex gap-3 text-sm text-gray-500">
                                    <span><i class="bi bi-whatsapp text-green-500"></i> <?= $condicional['telefone'] ?></span>
                                    <span><i class="bi bi-card-heading"></i> <?= $condicional['cpf'] ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600 ml-13">
                            <i class="bi bi-geo-alt text-gray-400"></i> 
                            <?= htmlspecialchars($condicional['logradouro']) ?>, <?= htmlspecialchars($condicional['numero']) ?> - <?= htmlspecialchars($condicional['bairro']) ?>
                        </div>
                    </div>

                    <div class="flex-shrink-0 text-right">
                        <p class="text-sm font-bold text-gray-400 uppercase mb-1">Status Atual</p>
                        <span class="px-4 py-1.5 rounded-full text-sm font-bold inline-block
                            <?= $condicional['status'] == 'FINALIZADO' ? 'bg-green-100 text-green-800' : 'bg-purple-50 text-roxo-base border border-purple-100' ?>">
                            <?= $condicional['status'] ?>
                        </span>
                        <p class="text-sm text-gray-500 mt-3">
                            Previsão de Retorno:<br>
                            <span class="font-bold text-gray-800 text-base"><?= date('d/m/Y', strtotime($condicional['data_prevista_retorno'])) ?></span>
                        </p>
                    </div>
                </div>
            </div>

            <form method="POST" action="">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="px-6 py-4">Produto</th>
                                    <th class="px-6 py-4 text-center">Preço Unit.</th>
                                    <th class="px-6 py-4 text-center">Quantidade</th>
                                    <th class="px-6 py-4 text-center w-64">Ação (Retorno)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                <?php 
                                $total_vendido = 0;
                                foreach ($itens as $item): 
                                    if($item['status_item'] == 'VENDIDO') $total_vendido += ($item['preco_momento'] * $item['quantidade']);
                                ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="h-12 w-12 flex-shrink-0 rounded-lg bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center">
                                                    <?php if (!empty($item['imagem'])): ?>
                                                        <img src="uploads/<?= $item['imagem'] ?>" class="h-full w-full object-cover">
                                                    <?php else: ?>
                                                        <i class="bi bi-tshirt-fill text-gray-400 text-xl"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ml-4">
                                                    <p class="font-bold text-gray-900"><?= htmlspecialchars($item['nome']) ?></p>
                                                    <p class="text-gray-500 text-xs mt-0.5"><?= $item['tamanho'] ?> | <?= $item['cor'] ?></p>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 text-center font-medium text-gray-700">
                                            R$ <?= number_format($item['preco_momento'], 2, ',', '.') ?>
                                        </td>

                                        <td class="px-6 py-4 text-center">
                                            <?php if ($item['status_item'] == 'EM_CONDICIONAL'): ?>
                                                <input type="number" name="quantidade[<?= $item['id'] ?>]" 
                                                       value="<?= $item['quantidade'] ?>" min="1" max="<?= $item['quantidade'] ?>"
                                                       class="w-16 px-2 py-1 border border-gray-300 rounded-md text-center focus:ring-2 focus:ring-roxo-base focus:border-transparent">
                                            <?php else: ?>
                                                <span class="font-bold text-gray-800"><?= $item['quantidade'] ?></span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="px-6 py-4 text-center">
                                            <?php if ($item['status_item'] == 'EM_CONDICIONAL'): ?>
                                                <div class="flex justify-center gap-3">
                                                    <label class="cursor-pointer flex items-center gap-2 px-3 py-1.5 rounded-md border border-green-200 bg-green-50 hover:bg-green-100 transition">
                                                        <input type="radio" name="acao[<?= $item['id'] ?>]" value="vendido" class="text-green-600 focus:ring-green-500">
                                                        <span class="text-xs font-bold text-green-700">Vendido</span>
                                                    </label>
                                                    <label class="cursor-pointer flex items-center gap-2 px-3 py-1.5 rounded-md border border-blue-200 bg-blue-50 hover:bg-blue-100 transition">
                                                        <input type="radio" name="acao[<?= $item['id'] ?>]" value="devolveu" checked class="text-blue-600 focus:ring-blue-500">
                                                        <span class="text-xs font-bold text-blue-700">Devolveu</span>
                                                    </label>
                                                </div>
                                            <?php else: ?>
                                                <?php if($item['status_item'] == 'VENDIDO'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        VENDIDO
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        DEVOLVIDO
                                                    </span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex justify-end items-center">
                        <span class="text-sm text-gray-500 mr-4 uppercase font-bold tracking-wide">Total Vendido nesta Sacola</span>
                        <span class="text-2xl font-bold text-green-600">R$ <?= number_format($total_vendido, 2, ',', '.') ?></span>
                    </div>
                </div>

                <?php if ($condicional['status'] != 'FINALIZADO'): ?>
                    <div class="mt-8 flex flex-col sm:flex-row justify-end gap-4 pb-10">
                        <button type="submit" name="tipo_acao" value="processar" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-bold py-3 px-6 rounded-lg shadow-sm transition flex items-center justify-center">
                            <i class="bi bi-arrow-repeat mr-2"></i> Processar Parcial
                        </button>
                        
                        <button type="submit" name="tipo_acao" value="faturar" class="bg-roxo-base hover:bg-purple-700 text-white font-bold py-3 px-8 rounded-lg shadow-md hover:shadow-lg transition transform hover:-translate-y-0.5 flex items-center justify-center">
                            <i class="bi bi-check2-circle mr-2 text-lg"></i> Finalizar e Faturar
                        </button>
                    </div>
                <?php endif; ?>

            </form>

        </main>
    </div>

    <script>
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

</body>
</html>