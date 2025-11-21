<?php
require_once 'auth_check.php';
require_once 'conexao.php';

// Título da Página
$titulo_pagina = "Nova Entrada de Produtos";

// --- Variáveis para Toasts de Validação ---
$toast_msg = '';
$toast_type = '';

// --- Função "Sticky Form" ---
function valor($campo) {
    return isset($_POST[$campo]) ? htmlspecialchars($_POST[$campo]) : '';
}

// --- Buscar Fornecedores e Produtos para preencher os <select> ---
try {
    $stmt_forn = $pdo->query("SELECT id, nome, cnpj_cpf FROM fornecedores ORDER BY nome ASC");
    $fornecedores = $stmt_forn->fetchAll();
    // Não precisamos checar estoque_loja > 0, pois é uma entrada
    $stmt_prod = $pdo->query("SELECT id, nome, tamanho, cor, preco_custo, preco FROM produtos ORDER BY nome ASC");
    $produtos = $stmt_prod->fetchAll();
} catch (PDOException $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}
$id_pre_selecionado = $_GET['fornecedor_id'] ?? '';


// --- Processar o Formulário ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction(); // Começa a Transação

        $fornecedor_id = $_POST['fornecedor_id'];
        $data_vencimento = $_POST['data_vencimento'];
        $numero_nota = $_POST['numero_nota'];
        $numero_nfe = $_POST['numero_nfe'];
        $serie_nfe = $_POST['serie_nfe'];
        $chave_acesso = preg_replace('/[^0-9]/', '', $_POST['chave_acesso']);
        $observacoes = $_POST['observacoes'];
        $produtos_selecionados = $_POST['produtos'] ?? [];
        $quantidades = $_POST['quantidades'] ?? [];
        $precos_custo = $_POST['precos_custo'] ?? [];

        if (empty($produtos_selecionados) || empty($produtos_selecionados[0])) {
            throw new Exception("Você precisa adicionar pelo menos um produto à entrada.");
        }

        $valor_total = 0;
        $produtos_para_entrada = [];

        // 1. Pré-processar e validar os itens
        foreach ($produtos_selecionados as $index => $produto_id) {
            if (empty($produto_id)) continue;
            $qtd = (int)$quantidades[$index];
            // Remove pontos de milhar e substitui vírgula por ponto decimal
            $custo_formatado = str_replace(',', '.', str_replace('.', '', $precos_custo[$index]));
            $custo = (float)$custo_formatado;

            if ($qtd <= 0) {
                throw new Exception("A quantidade para o produto ID #$produto_id deve ser positiva.");
            }
            if ($custo <= 0) {
                // Permite custo zero, mas não negativo.
                // throw new Exception("O preço de custo para o produto ID #$produto_id deve ser positivo.");
            }

            $valor_total += ($qtd * $custo);
            $produtos_para_entrada[] = [
                'produto_id' => $produto_id,
                'quantidade' => $qtd,
                'preco_custo_momento' => $custo
            ];
        }

        // 2. Criar o Registro de Entrada (Cabeçalho - Contas a Pagar)
        $sql_entrada = "INSERT INTO entradas_produto (fornecedor_id, data_vencimento, numero_nota, numero_nfe, serie_nfe, chave_acesso, valor_total, observacoes) 
                         VALUES (:fornecedor_id, :data_vencimento, :numero_nota, :numero_nfe, :serie_nfe, :chave_acesso, :valor_total, :obs)";
        $stmt = $pdo->prepare($sql_entrada);
        $stmt->execute([
            ':fornecedor_id' => $fornecedor_id,
            ':data_vencimento' => $data_vencimento ?: null, // Permite NULL
            ':numero_nota' => $numero_nota,
            ':numero_nfe' => $numero_nfe,
            ':serie_nfe' => $serie_nfe,
            ':chave_acesso' => $chave_acesso,
            ':valor_total' => $valor_total,
            ':obs' => $observacoes
        ]);
        $entrada_id = $pdo->lastInsertId();

        // 3. Processar cada produto e atualizar estoque
        $sql_item = "INSERT INTO itens_entrada (entrada_id, produto_id, quantidade, preco_custo_momento) 
                     VALUES (:entrada_id, :prod_id, :qtd, :preco_custo)";
        // Atualiza estoque e o preco_custo do produto com o último custo de entrada
        $sql_estoque = "UPDATE produtos SET estoque_loja = estoque_loja + :qtd, preco_custo = :preco_custo WHERE id = :prod_id";

        foreach ($produtos_para_entrada as $item) {
            // Inserir Item na Entrada
            $stmt_item = $pdo->prepare($sql_item);
            $stmt_item->execute([
                ':entrada_id' => $entrada_id,
                ':prod_id' => $item['produto_id'],
                ':qtd' => $item['quantidade'],
                ':preco_custo' => $item['preco_custo_momento']
            ]);

            // Atualizar Estoque e Preço de Custo
            $stmt_estoque = $pdo->prepare($sql_estoque);
            $stmt_estoque->execute([
                ':qtd' => $item['quantidade'], 
                ':preco_custo' => $item['preco_custo_momento'],
                ':prod_id' => $item['produto_id']
            ]);
        }

        $pdo->commit(); // Salva tudo
        
        // SUCESSO: Redireciona para a lista com Toast
        $_POST = array(); // Limpa o "sticky form"
        $msg_sucesso = "Entrada #$entrada_id registrada com sucesso! Estoque atualizado e Contas a Pagar gerado.";
        header("Location: entradas_lista.php?msg=" . urlencode($msg_sucesso) . "&type=success");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack(); // Desfaz qualquer alteração no banco
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
    <title>Nova Entrada de Produtos</title>
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
            
            <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">Nova Entrada de Produtos</h2>
            <form method="POST" action="">
                
                <h3 class="text-xl font-bold text-gray-800 mb-4">Dados da Entrada e Pagamento</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Fornecedor *</label>
                        <select name="fornecedor_id" class="w-full border rounded p-2 bg-white" required>
                            <option value="">Selecione um fornecedor...</option>
                            <?php 
                            // Lógica do sticky form + pré-seleção da URL
                            $fornecedor_selecionado = valor('fornecedor_id') ?: $id_pre_selecionado; 
                            ?>
                            <?php foreach ($fornecedores as $forn): ?>
                                <?php $selected = ($forn['id'] == $fornecedor_selecionado) ? 'selected' : ''; ?>
                                <option value="<?= $forn['id'] ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($forn['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Data de Vencimento</label>
                        <input type="date" name="data_vencimento" class="w-full border rounded p-2" 
                               value="<?= valor('data_vencimento') ?>">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Número da Nota/Pedido</label>
                        <input type="text" name="numero_nota" class="w-full border rounded p-2" 
                               placeholder="Ex: Pedido 123" value="<?= valor('numero_nota') ?>">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Número da NF-e</label>
                        <input type="text" name="numero_nfe" class="w-full border rounded p-2" 
                               placeholder="Ex: 123456" value="<?= valor('numero_nfe') ?>">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Série da NF-e</label>
                        <input type="text" name="serie_nfe" class="w-full border rounded p-2" 
                               placeholder="Ex: 1 ou 001" value="<?= valor('serie_nfe') ?>">
                    </div>
                    <div class="md:col-span-4">
                        <label class="block text-gray-700 font-bold mb-2">Chave de Acesso (44 dígitos)</label>
                        <input type="text" name="chave_acesso" class="w-full border rounded p-2" 
                               placeholder="Ex: 35230100000000000000550010000000000000000000" maxlength="44" oninput="this.value = this.value.replace(/[^0-9]/g, '')" value="<?= valor('chave_acesso') ?>">
                    </div>
                    <div class="md:col-span-4">
                        <label class="block text-gray-700 font-bold mb-2">Observações</label>
                        <input type="text" name="observacoes" class="w-full border rounded p-2" 
                               placeholder="Ex: Condições de pagamento, frete, etc." value="<?= valor('observacoes') ?>">
                    </div>
                </div>

                <h3 class="text-xl font-bold text-gray-800 mb-4 border-t pt-4">Itens da Entrada</h3>
                
                <div id="lista-produtos">
                    <div class="produto-row flex flex-wrap md:flex-nowrap gap-4 mb-4 items-center border-b pb-4">
                        
                        <div class="w-full md:w-1/2">
                            <label class="block text-sm font-bold text-gray-600 mb-1">Produto *</label>
                            <select name="produtos[]" class="w-full border rounded p-2 bg-white" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($produtos as $prod): ?>
                                    <option value="<?= $prod['id'] ?>">
                                        <?= htmlspecialchars($prod['nome']) ?> 
                                        (Tam: <?= $prod['tamanho'] ?> | Cor: <?= $prod['cor'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="w-full md:w-1/4">
                            <label class="block text-sm font-bold text-gray-600 mb-1">Qtd. *</label>
                            <input type="number" name="quantidades[]" value="1" min="1" class="w-full border rounded p-2 text-center" required>
                        </div>

                        <div class="w-full md:w-1/4">
                            <label class="block text-sm font-bold text-gray-600 mb-1">Preço de Custo (Un.) *</label>
                            <input type="text" name="precos_custo[]" class="w-full border rounded p-2 text-right" 
                                   placeholder="0,00" oninput="mascaraMoeda(this)" required>
                        </div>

                        <div class="self-end pb-1">
                            <button type="button" onclick="removerLinha(this)" class="text-red-500 hover:text-red-700 font-bold text-sm p-2">
                                X
                            </button>
                        </div>
                    </div>
                </div>

                <button type="button" onclick="adicionarProduto()" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded text-sm mb-8">
                    + Adicionar Outro Produto
                </button>

                <button type="submit" class="w-full bg-roxo-base hover:bg-purple-700 text-white font-bold py-4 px-6 rounded-lg text-lg shadow-lg transition">
                    Registrar Entrada e Atualizar Estoque
                </button>
            </form>
        </div>
    </div>

    <script>
        function mascaraMoeda(e) {
            var t = e.value.replace(/\D/g, "");
            t = t.replace(/(\d)(\d{2})$/, "$1,$2");
            t = t.replace(/(\d+)(\d{3},\d{2})$/, "$1.$2");
            e.value = t;
        }

        function adicionarProduto() {
            const lista = document.getElementById('lista-produtos');
            const primeiraLinha = lista.querySelector('.produto-row');
            const novaLinha = primeiraLinha.cloneNode(true);
            
            // Limpa os valores da nova linha
            novaLinha.querySelector('select').value = '';
            novaLinha.querySelector('input[type="number"]').value = '1';
            novaLinha.querySelector('input[type="text"]').value = '';
            
            lista.appendChild(novaLinha);
        }

        function removerLinha(botao) {
            const lista = document.getElementById('lista-produtos');
            if (lista.children.length > 1) {
                botao.closest('.produto-row').remove();
            } else {
                alert("A entrada precisa ter pelo menos 1 item.");
            }
        }
        
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
