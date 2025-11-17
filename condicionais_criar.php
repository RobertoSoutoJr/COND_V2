<?php require_once 'auth_check.php'; ?>
<?php
require_once 'conexao.php';

$mensagem = '';

// Buscar Clientes
$stmt_cli = $pdo->query("SELECT id, nome, cpf FROM clientes ORDER BY nome ASC");
$clientes = $stmt_cli->fetchAll();

// Buscar Produtos (agora com a IMAGEM)
$stmt_prod = $pdo->query("SELECT id, nome, tamanho, cor, preco, imagem FROM produtos WHERE estoque_loja > 0 ORDER BY nome ASC");
$produtos = $stmt_prod->fetchAll();

// Captura ID do cliente vindo da URL (para pré-seleção)
$id_pre_selecionado = $_GET['cliente_id'] ?? '';

// Processar o Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // 1. Criar o Condicional (Cabeçalho)
        $sql_cond = "INSERT INTO condicionais (cliente_id, data_prevista_retorno, observacoes) 
                     VALUES (:cliente_id, :data_retorno, :obs)";
        $stmt = $pdo->prepare($sql_cond);
        $stmt->execute([
            ':cliente_id' => $_POST['cliente_id'],
            ':data_retorno' => $_POST['data_retorno'],
            ':obs' => $_POST['observacoes']
        ]);
        $condicional_id = $pdo->lastInsertId();

        // 2. Processar cada produto
        $produtos_selecionados = $_POST['produtos'] ?? [];
        $quantidades = $_POST['quantidades'] ?? [];

        foreach ($produtos_selecionados as $index => $produto_id) {
            if (empty($produto_id))
                continue;
            $qtd = $quantidades[$index];

            // Pega o preço (já tínhamos)
            $stmt_preco = $pdo->prepare("SELECT preco FROM produtos WHERE id = ?");
            $stmt_preco->execute([$produto_id]);
            $prod_dados = $stmt_preco->fetch();

            // Inserir Item
            $sql_item = "INSERT INTO itens_condicional (condicional_id, produto_id, quantidade, preco_momento) 
                         VALUES (:cond_id, :prod_id, :qtd, :preco)";
            $stmt_item = $pdo->prepare($sql_item);
            $stmt_item->execute([
                ':cond_id' => $condicional_id,
                ':prod_id' => $produto_id,
                ':qtd' => $qtd,
                ':preco' => $prod_dados['preco']
            ]);

            // 3. Baixar Estoque
            $sql_estoque = "UPDATE produtos SET estoque_loja = estoque_loja - :qtd WHERE id = :prod_id";
            $stmt_estoque = $pdo->prepare($sql_estoque);
            $stmt_estoque->execute([':qtd' => $qtd, ':prod_id' => $produto_id]);
        }

        $pdo->commit();

        // Redireciona para uma página de sucesso (ou a lista)
        header("Location: condicionais_lista.php?msg=sucesso");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "<div class='bg-red-100 text-red-700 p-4 rounded mb-4'>Erro: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Novo Condicional</title>
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
        <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">

            <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">Nova Sacola (Condicional)</h2>
            <?= $mensagem ?>

            <form method="POST" action="">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Cliente *</label>
                        <select name="cliente_id" class="w-full border rounded p-2 bg-white" required>
                            <option value="">Selecione um cliente...</option>
                            <?php foreach ($clientes as $cli): ?>
                                <?php $selected = ($cli['id'] == $id_pre_selecionado) ? 'selected' : ''; ?>
                                <option value="<?= $cli['id'] ?>" <?= $selected ?>>
                                    <?= htmlspecialchars($cli['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">Data Prevista Retorno *</label>
                        <input type="date" name="data_retorno" class="w-full border rounded p-2"
                            min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 font-bold mb-2">Observações</label>
                        <input type="text" name="observacoes" class="w-full border rounded p-2"
                            placeholder="Ex: Cliente levou para provar...">
                    </div>
                </div>

                <h3 class="text-xl font-bold text-gray-800 mb-4">Itens da Sacola</h3>

                <div id="lista-produtos">
                    <div class="produto-row flex flex-wrap md:flex-nowrap gap-4 mb-4 items-center border-b pb-4">

                        <div
                            class="w-16 h-16 bg-gray-100 rounded border flex items-center justify-center text-gray-400 flex-shrink-0">
                            <img src="" class="produto-imagem-preview w-full h-full object-cover rounded hidden">
                            <span class="emoji-preview text-2xl">👗</span>
                        </div>

                        <div class="w-full md:w-3/4">
                            <label class="block text-sm font-bold text-gray-600 mb-1">Produto</label>
                            <select name="produtos[]" class="w-full border rounded p-2 bg-white" required
                                onchange="atualizarImagemPreview(this)">
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
                            <input type="number" name="quantidades[]" value="1" min="1"
                                class="w-full border rounded p-2 text-center" required>
                        </div>

                        <div class="self-end pb-1">
                            <button type="button" onclick="removerLinha(this)"
                                class="text-red-500 hover:text-red-700 font-bold text-sm p-2">
                                X
                            </button>
                        </div>
                    </div>
                </div>

                <button type="button" onclick="adicionarProduto()"
                    class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded text-sm mb-8">
                    + Adicionar Outra Peça
                </button>

                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-800 text-white font-bold py-4 px-6 rounded-lg text-lg shadow-lg transition">
                    Gerar Condicional e Baixar Estoque
                </button>
            </form>
        </div>
    </div>

    <script>
        // Função Nova: Atualiza a imagem ao lado
        function atualizarImagemPreview(selectElement) {
            // Pega o <option> selecionado
            const option = selectElement.options[selectElement.selectedIndex];

            // Pega o nome da imagem que escondemos no 'data-imagem'
            const nomeImagem = option.getAttribute('data-imagem');

            // Acha os elementos de preview (img e emoji) DENTRO da mesma linha
            const linha = selectElement.closest('.produto-row');
            const imgPreview = linha.querySelector('.produto-imagem-preview');
            const emojiPreview = linha.querySelector('.emoji-preview');

            if (nomeImagem) {
                // Se o produto tem foto, mostra
                imgPreview.src = 'uploads/' + nomeImagem;
                imgPreview.classList.remove('hidden');
                emojiPreview.classList.add('hidden');
            } else {
                // Se não tem foto, mostra o emoji
                imgPreview.src = '';
                imgPreview.classList.add('hidden');
                emojiPreview.classList.remove('hidden');
            }
        }

        // Função Antiga (Clonar Linha) - com um pequeno ajuste
        function adicionarProduto() {
            const primeiraLinha = document.querySelector('.produto-row');
            const novaLinha = primeiraLinha.cloneNode(true);

            // Limpa os valores clonados
            novaLinha.querySelector('select').value = '';
            novaLinha.querySelector('input[type="number"]').value = '1';

            // Reseta a imagem para o padrão (emoji)
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

    <?php include 'toast_handler.php'; ?>
</body>



</html>