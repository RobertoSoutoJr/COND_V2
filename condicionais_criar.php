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

// --- Buscar Clientes e Produtos para preencher os <select> ---
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
        $pdo->beginTransaction(); // Começa a Transação

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

            // --- INÍCIO DA BLINDAGEM DE STOCK ---
            $stmt_check = $pdo->prepare("SELECT nome, estoque_loja, preco FROM produtos WHERE id = ?");
            $stmt_check->execute([$produto_id]);
            $prod_dados = $stmt_check->fetch();

            if (!$prod_dados) {
                throw new Exception("Produto ID #$produto_id não encontrado.");
            }
            if ($qtd <= 0) {
                throw new Exception("A quantidade para '{$prod_dados['nome']}' deve ser positiva.");
            }
            if ($qtd > $prod_dados['estoque_loja']) {
                // ERRO: Stock insuficiente. Para a transação.
                throw new Exception("Stock insuficiente para '{$prod_dados['nome']}'. (Disponível: {$prod_dados['estoque_loja']})");
            }
            // --- FIM DA BLINDAGEM ---
            
            // Inserir Item no Condicional
            $sql_item = "INSERT INTO itens_condicional (condicional_id, produto_id, quantidade, preco_momento) 
                         VALUES (:cond_id, :prod_id, :qtd, :preco)";
            $stmt_item = $pdo->prepare($sql_item);
            $stmt_item->execute([
                ':cond_id' => $condicional_id,
                ':prod_id' => $produto_id,
                ':qtd' => $qtd,
                ':preco' => $prod_dados['preco'] // Pega o preço atual
            ]);

            // 3. Baixar Estoque (Agora 100% seguro)
            $sql_estoque = "UPDATE produtos SET estoque_loja = estoque_loja - :qtd WHERE id = :prod_id";
            $stmt_estoque = $pdo->prepare($sql_estoque);
            $stmt_estoque->execute([':qtd' => $qtd, ':prod_id' => $produto_id]);
        }

        $pdo->commit(); // Salva tudo
        
        // SUCESSO: Redireciona para a lista com Toast
        $_POST = array(); // Limpa o "sticky form"
        $msg_sucesso = "Sacola #$condicional_id criada com sucesso!";
        header("Location: condicionais_lista.php?msg=" . urlencode($msg_sucesso) . "&type=success");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack(); // Desfaz qualquer alteração no banco
        // ERRO: Prepara o Toast para esta página
        $toast_msg = "Erro: " . $e->getMessage();
        $toast_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Novo Condicional</title>
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
        <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
            
            <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">Nova Sacola (Condicional)</h2>
            <form method="POST" action="">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Cliente *</label>
                        <select name="cliente_id" class="w-full border rounded p-2 bg-white" required>
                            <option value="">Selecione um cliente...</option>
                            <?php 
                            // Lógica do sticky form + pré-seleção da URL
                            $cliente_selecionado = valor('cliente_id') ?: $id_pre_selecionado; 
                            ?>
                            <?php foreach ($clientes as $cli): ?>
                                <?php $selected = ($cli['id'] == $cliente_selecionado) ? 'selected' : ''; ?>
                                <option value="<?= $cli['id'] ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($cli['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Data Prevista Retorno *</label>
                        <input type="date" name="data_retorno" class="w-full border rounded p-2" 
                               min="<?= date('Y-m-d') ?>" value="<?= valor('data_retorno') ?>" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-bold mb-2">Observações</label>
                        <input type="text" name="observacoes" class="w-full border rounded p-2" 
                               placeholder="Ex: Cliente levou para provar..." value="<?= valor('observacoes') ?>">
                    </div>
                </div>

                <h3 class="text-xl font-bold text-gray-800 mb-4">Itens da Sacola</h3>
                
                <div id="lista-produtos">
                    <div class="produto-row flex flex-wrap md:flex-nowrap gap-4 mb-4 items-center border-b pb-4">
                        
                        <div class="w-16 h-16 bg-gray-100 rounded border flex items-center justify-center text-gray-400 flex-shrink-0">
                            <img src="" class="produto-imagem-preview w-full h-full object-cover rounded hidden">
                            <span class="emoji-preview text-2xl">👗</span>
                        </div>

                        <div class="w-full md:w-3/4">
                            <label class="block text-sm font-bold text-gray-600 mb-1">Produto</label>
                            <select name="produtos[]" class="w-full border rounded p-2 bg-white" required onchange="atualizarImagemPreview(this)">
                                <option value="">Selecione...</option>
                                <?php foreach ($produtos as $prod): ?>
                                    <option value="<?= $prod['id'] ?>" data-imagem="<?= $prod['imagem'] ?>">
                                        <?= htmlspecialchars($prod['nome']) ?> 
                                        (Tam: <?= $prod['tamanho'] ?> | R$ <?= $prod['preco'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="w-24">
                            <label class="block text-sm font-bold text-gray-600 mb-1">Qtd.</label>
                            <input type="number" name="quantidades[]" value="1" min="1" class="w-full border rounded p-2 text-center" required>
                        </div>

                        <div class="self-end pb-1">
                            <button type="button" onclick="removerLinha(this)" class="text-red-500 hover:text-red-700 font-bold text-sm p-2">
                                X
                            </button>
                        </div>
                    </div>
                </div>

                <button type="button" onclick="adicionarProduto()" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded text-sm mb-8">
                    + Adicionar Outra Peça
                </button>

                <button type="submit" class="w-full bg-roxo-base hover:bg-purple-700 text-white font-bold py-4 px-6 rounded-lg text-lg shadow-lg transition">
                    Gerar Condicional e Baixar Estoque
                </button>
            </form>
        </div>
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
            const primeiraLinha = document.querySelector('.produto-row');
            const novaLinha = primeiraLinha.cloneNode(true);
            novaLinha.querySelector('select').value = '';
            novaLinha.querySelector('input[type="number"]').value = '1';
            novaLinha.querySelector('.produto-imagem-preview').classList.add('hidden');
            novaLinha.querySelector('.emoji-preview').classList.remove('hidden');
            document.getElementById('lista-produtos').appendChild(novaLinha);
        }
        function removerLinha(botao) {
            const lista = document.getElementById('lista-produtos');
            if (lista.children.length > 1) {
                botao.closest('.produto-row').remove();
            } else {
                alert("A sacola precisa ter pelo menos 1 item.");
            }
        }
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.getElementById('auto-toast');
            if (toast) {
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 500); 
                }, 4000); // 4 segundos
            }
        });
    </script>
</body>
</html>