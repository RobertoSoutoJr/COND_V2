<?php
require_once 'auth_check.php';
require_once 'conexao.php';

// Título da Página
$titulo_pagina = "Nova Sacola";

// --- Variáveis para Toasts de Validação ---
$toast_msg = '';
$toast_type = '';

// --- Função "Sticky Form" ---
function valor($campo) {
    return isset($_POST[$campo]) ? htmlspecialchars($_POST[$campo]) : '';
}

// --- Buscar Clientes e Produtos ---
try {
    $stmt_cli = $pdo->query("SELECT id, nome, cpf FROM clientes ORDER BY nome ASC");
    $clientes = $stmt_cli->fetchAll();
    $stmt_prod = $pdo->query("SELECT id, nome, tamanho, cor, preco, imagem FROM produtos WHERE estoque_loja > 0 ORDER BY nome ASC");
    $produtos = $stmt_prod->fetchAll();
} catch (PDOException $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}
$id_pre_selecionado = $_GET['cliente_id'] ?? '';


// --- Processar o Formulário ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction(); 

        $cliente_id = $_POST['cliente_id'];
        $data_retorno = $_POST['data_retorno'];
        $observacoes = $_POST['observacoes'];
        $produtos_selecionados = $_POST['produtos'] ?? [];
        $quantidades = $_POST['quantidades'] ?? [];

        if (empty($produtos_selecionados) || empty($produtos_selecionados[0])) {
            throw new Exception("Você precisa adicionar pelo menos um produto à sacola.");
        }

        // 1. Criar o Condicional (Cabeçalho)
        $sql_cond = "INSERT INTO condicionais (cliente_id, data_prevista_retorno, observacoes) 
                     VALUES (:cliente_id, :data_retorno, :obs)";
        $stmt = $pdo->prepare($sql_cond);
        $stmt->execute([
            ':cliente_id' => $cliente_id,
            ':data_retorno' => $data_retorno,
            ':obs' => $observacoes
        ]);
        $condicional_id = $pdo->lastInsertId();

        // 2. Processar cada produto
        foreach ($produtos_selecionados as $index => $produto_id) {
            if (empty($produto_id)) continue;
            $qtd = (int)$quantidades[$index];

            // Blindagem de Estoque
            $stmt_check = $pdo->prepare("SELECT nome, estoque_loja, preco FROM produtos WHERE id = ?");
            $stmt_check->execute([$produto_id]);
            $prod_dados = $stmt_check->fetch();

            if (!$prod_dados) throw new Exception("Produto ID #$produto_id não encontrado.");
            if ($qtd <= 0) throw new Exception("A quantidade para '{$prod_dados['nome']}' deve ser positiva.");
            if ($qtd > $prod_dados['estoque_loja']) throw new Exception("Stock insuficiente para '{$prod_dados['nome']}'. (Disponível: {$prod_dados['estoque_loja']})");
            
            // Inserir Item
            $sql_item = "INSERT INTO itens_condicional (condicional_id, produto_id, quantidade, preco_momento) 
                         VALUES (:cond_id, :prod_id, :qtd, :preco)";
            $stmt_item = $pdo->prepare($sql_item);
            $stmt_item->execute([
                ':cond_id' => $condicional_id, ':prod_id' => $produto_id, ':qtd' => $qtd, ':preco' => $prod_dados['preco']
            ]);

            // Baixar Estoque
            $sql_estoque = "UPDATE produtos SET estoque_loja = estoque_loja - :qtd WHERE id = :prod_id";
            $stmt_estoque = $pdo->prepare($sql_estoque);
            $stmt_estoque->execute([':qtd' => $qtd, ':prod_id' => $produto_id]);
        }

        $pdo->commit();
        $_POST = array(); 
        $msg_sucesso = "Sacola #$condicional_id criada com sucesso!";
        header("Location: condicionais_lista.php?msg=" . urlencode($msg_sucesso) . "&type=success");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $toast_msg = "Erro: " . $e->getMessage();
        $toast_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Nova Sacola - COND</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-50 font-sans text-gray-900">

    <?php include 'menu.php'; ?>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($toast_msg)) {
        $bgColor = ($toast_type === 'error') ? 'bg-red-100 border-red-200 text-red-800' : 'bg-blue-100 border-blue-200 text-blue-800';
        $icon = ($toast_type === 'error') ? '<i class="bi bi-exclamation-triangle-fill"></i>' : '<i class="bi bi-info-circle-fill"></i>';
        echo "
        <div id='auto-toast' class='fixed top-5 right-5 z-[100] p-4 rounded-lg shadow-lg font-bold w-full max-w-sm transition-all duration-500 border $bgColor' role='alert'>
            <div class='flex items-center'>
                <span class='text-xl mr-3'>$icon</span>
                <span class='flex-grow text-sm'>$toast_msg</span>
                <button onclick='document.getElementById(\"auto-toast\").remove()' class='ml-4 text-xl opacity-60 hover:opacity-100'>&times;</button>
            </div>
        </div>";
    }
    ?>

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
                    <h1 class="text-2xl font-bold text-gray-800">Nova Sacola</h1>
                    <p class="text-sm text-gray-500">Selecione o cliente e os produtos para criar um novo condicional.</p>
                </div>
                <a href="condicionais_lista.php" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-roxo-base transition-colors">
                    <i class="bi bi-arrow-left mr-2"></i> Voltar para Lista
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 md:p-8">
                    
                    <form method="POST" action="">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                                <select name="cliente_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" required>
                                    <option value="">Selecione um cliente...</option>
                                    <?php 
                                    $cliente_selecionado = valor('cliente_id') ?: $id_pre_selecionado; 
                                    foreach ($clientes as $cli): 
                                        $selected = ($cli['id'] == $cliente_selecionado) ? 'selected' : ''; 
                                    ?>
                                        <option value="<?= $cli['id'] ?>" <?= $selected ?>>
                                            <?= htmlspecialchars($cli['nome']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data Prevista de Retorno *</label>
                                <input type="date" name="data_retorno" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" 
                                       min="<?= date('Y-m-d') ?>" value="<?= valor('data_retorno') ?>" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                                <input type="text" name="observacoes" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" 
                                       placeholder="Ex: Cliente levou para provar..." value="<?= valor('observacoes') ?>">
                            </div>
                        </div>

                        <hr class="border-gray-100 my-8">

                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="bi bi-cart3 text-roxo-base mr-2"></i> Itens da Sacola
                        </h3>
                        
                        <div id="lista-produtos" class="space-y-4 mb-8">
                            <div class="produto-row bg-gray-50 p-4 rounded-lg border border-gray-200 flex flex-wrap md:flex-nowrap gap-4 items-center">
                                
                                <div class="w-12 h-12 bg-white rounded-lg border border-gray-300 flex items-center justify-center text-gray-400 flex-shrink-0 overflow-hidden">
                                    <img src="" class="produto-imagem-preview w-full h-full object-cover hidden">
                                    <span class="emoji-preview text-lg"><i class="bi bi-tshirt"></i></span>
                                </div>

                                <div class="flex-grow w-full md:w-auto">
                                    <label class="block text-xs font-bold text-gray-500 mb-1 uppercase">Produto</label>
                                    <select name="produtos[]" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all text-sm" required onchange="atualizarImagemPreview(this)">
                                        <option value="">Selecione...</option>
                                        <?php foreach ($produtos as $prod): ?>
                                            <option value="<?= $prod['id'] ?>" data-imagem="<?= $prod['imagem'] ?>">
                                                <?= htmlspecialchars($prod['nome']) ?> 
                                                (<?= $prod['tamanho'] ?> | R$ <?= $prod['preco'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="w-24 flex-shrink-0">
                                    <label class="block text-xs font-bold text-gray-500 mb-1 uppercase">Qtd.</label>
                                    <input type="number" name="quantidades[]" value="1" min="1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-center focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all text-sm" required>
                                </div>

                                <div class="self-end pb-1">
                                    <button type="button" onclick="removerLinha(this)" class="text-red-400 hover:text-red-600 transition-colors p-2" title="Remover Item">
                                        <i class="bi bi-trash-fill text-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="button" onclick="adicionarProduto()" class="text-sm font-bold text-roxo-base hover:text-purple-800 hover:bg-purple-50 px-4 py-2 rounded-lg transition-colors border border-purple-200 flex items-center mb-8">
                            <i class="bi bi-plus-lg mr-2"></i> Adicionar Outra Peça
                        </button>

                        <div class="flex justify-end pt-6 border-t border-gray-100">
                            <button class="bg-roxo-base hover:bg-purple-700 text-white font-bold py-3 px-8 rounded-lg shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center" type="submit">
                                <i class="bi bi-check2-circle mr-2 text-xl"></i> Gerar Condicional
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </main>
    </div>

    <script>
        function atualizarImagemPreview(selectElement) {
            const option = selectElement.options[selectElement.selectedIndex];
            const nomeImagem = option.getAttribute('data-imagem');
            const linha = selectElement.closest('.produto-row');
            const imgPreview = linha.querySelector('.produto-imagem-preview');
            const emojiPreview = linha.querySelector('.emoji-preview');
            if (nomeImagem) {
                imgPreview.src = 'uploads/' + nomeImagem;
                imgPreview.classList.remove('hidden');
                emojiPreview.classList.add('hidden');
            } else {
                imgPreview.src = '';
                imgPreview.classList.add('hidden');
                emojiPreview.classList.remove('hidden');
            }
        }
        function adicionarProduto() {
            const lista = document.getElementById('lista-produtos');
            const primeiraLinha = lista.querySelector('.produto-row'); // Pega sempre o modelo original
            const novaLinha = primeiraLinha.cloneNode(true);
            
            // Limpa os valores
            novaLinha.querySelector('select').value = '';
            novaLinha.querySelector('input[type="number"]').value = '1';
            
            // Reseta imagem
            novaLinha.querySelector('.produto-imagem-preview').classList.add('hidden');
            novaLinha.querySelector('.emoji-preview').classList.remove('hidden');
            
            lista.appendChild(novaLinha);
        }
        function removerLinha(botao) {
            const lista = document.getElementById('lista-produtos');
            if (lista.children.length > 1) {
                botao.closest('.produto-row').remove();
            } else {
                // Opcional: Mostrar um aviso visual em vez de alert
                // alert("A sacola precisa ter pelo menos 1 item.");
            }
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
</body>
</html>