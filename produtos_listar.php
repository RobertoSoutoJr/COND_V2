<?php
require_once 'conexao.php';

$mensagem = '';

// --- PROCESSAMENTO DO FORMULÁRIO (TUDO ACONTECE AQUI) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {

    // 1. AÇÃO: REPOSIÇÃO DE ESTOQUE (O modal pequeno)
    if ($_POST['acao'] === 'adicionar_estoque') {
        try {
            $id_prod = $_POST['id_produto'];
            $qtd_nova = (int) $_POST['quantidade'];

            if ($qtd_nova > 0) {
                $stmt = $pdo->prepare("UPDATE produtos SET estoque_loja = estoque_loja + ? WHERE id = ?");
                $stmt->execute([$qtd_nova, $id_prod]);
                $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>Estoque atualizado com sucesso!</div>";
            }
        } catch (PDOException $e) {
            $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Erro: " . $e->getMessage() . "</div>";
        }
    }

    // 2. AÇÃO: CRIAR NOVO PRODUTO (O modal grande)
    elseif ($_POST['acao'] === 'criar_produto') {
        try {
            // Upload de Imagem
            $caminho_imagem = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
                $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $permitidos = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array(strtolower($extensao), $permitidos)) {
                    $novo_nome = uniqid() . "." . $extensao;
                    $destino = 'uploads/' . $novo_nome;
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                        $caminho_imagem = $novo_nome;
                    }
                }
            }

            // Dados
            $custo = str_replace(',', '.', $_POST['custo']);
            $preco = str_replace(',', '.', $_POST['preco']);

            $sql = "INSERT INTO produtos (nome, descricao, tamanho, cor, preco_custo, preco, estoque_loja, imagem) 
                    VALUES (:nome, :descricao, :tamanho, :cor, :custo, :preco, :estoque, :imagem)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $_POST['nome'],
                ':descricao' => $_POST['descricao'],
                ':tamanho' => $_POST['tamanho'],
                ':cor' => $_POST['cor'],
                ':custo' => $custo,
                ':preco' => $preco,
                ':estoque' => $_POST['estoque'],
                ':imagem' => $caminho_imagem
            ]);

            $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>Novo produto cadastrado com sucesso!</div>";

        } catch (Exception $e) {
            $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Erro ao cadastrar: " . $e->getMessage() . "</div>";
        }
    }
}

// BUSCA PRODUTOS PARA LISTAGEM
try {
    $sql = "SELECT * FROM produtos ORDER BY id DESC"; // Ordenar por ID DESC mostra os novos primeiro
    $stmt = $pdo->query($sql);
    $produtos = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erro ao listar: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Estoque e Financeiro</title>
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

<body class="bg-gray-100 overflow-y-scroll"> <?php include 'menu.php'; ?>

    <div class="container mx-auto px-4 mt-8 mb-20">

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Gestão de Estoque</h2>

            <button onclick="abrirModalCriar()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow font-bold transition flex items-center">
                <span class="text-xl mr-2">+</span> Novo Produto
            </button>
        </div>

        <?= $mensagem ?>

        <div class="bg-white shadow-md rounded-lg overflow-hidden overflow-x-auto">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr>
                        <th
                            class="px-4 py-3 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Foto</th>
                        <th
                            class="px-4 py-3 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Produto</th>
                        <th
                            class="px-4 py-3 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Financeiro</th>
                        <th
                            class="px-4 py-3 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Lucro</th>
                        <th
                            class="px-4 py-3 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Estoque</th>
                        <th
                            class="px-4 py-3 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $p):
                        $custo = $p['preco_custo'];
                        $venda = $p['preco'];
                        $lucro_rs = $venda - $custo;
                        $margem = ($venda > 0) ? ($lucro_rs / $venda) * 100 : 0;
                        ?>
                        <tr class="hover:bg-gray-50 border-b border-gray-200">
                            <td class="px-4 py-4 text-sm">
                                <div
                                    class="w-12 h-12 rounded overflow-hidden border bg-gray-100 flex items-center justify-center">
                                    <?php if (!empty($p['imagem'])): ?>
                                        <img src="uploads/<?= $p['imagem'] ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <span class="text-xl">👗</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm">
                                <p class="text-gray-900 font-bold"><?= htmlspecialchars($p['nome']) ?></p>
                                <p class="text-gray-500 text-xs"><?= $p['tamanho'] ?> | <?= $p['cor'] ?></p>
                            </td>
                            <td class="px-4 py-4 text-center text-sm">
                                <div class="text-xs text-gray-500">Custo: <?= number_format($custo, 2, ',', '.') ?></div>
                                <div class="font-bold text-gray-800">Venda: <?= number_format($venda, 2, ',', '.') ?></div>
                            </td>
                            <td class="px-4 py-4 text-center text-sm">
                                <span class="<?= $lucro_rs >= 0 ? 'text-green-600' : 'text-red-600' ?> font-bold">
                                    R$ <?= number_format($lucro_rs, 2, ',', '.') ?>
                                </span>
                                <div class="text-xs text-gray-400"><?= number_format($margem, 0) ?>%</div>
                            </td>
                            <td class="px-4 py-4 text-center text-sm">
                                <span class="bg-gray-100 text-gray-800 py-1 px-3 rounded-full text-xs font-bold border">
                                    <?= $p['estoque_loja'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center text-sm">
                                <button onclick="abrirModalEstoque(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nome']) ?>')"
                                    class="text-green-600 hover:text-green-900 bg-green-100 hover:bg-green-200 p-2 rounded text-xs font-bold mr-2"
                                    title="Adicionar Estoque">
                                    [+]
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modalEstoque" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
        <div class="bg-white p-6 rounded-lg shadow-xl w-96 animate-fade-in-down">
            <h3 class="text-lg font-bold text-gray-800 mb-2">Repor Estoque</h3>
            <p class="text-gray-600 text-sm mb-4">Produto: <strong id="nomeProdutoModal"
                    class="text-blue-600">...</strong></p>
            <form method="POST" action="">
                <input type="hidden" name="acao" value="adicionar_estoque">
                <input type="hidden" name="id_produto" id="idProdutoModal">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Quantidade a adicionar:</label>
                    <input type="number" name="quantidade" min="1" value="1"
                        class="border rounded w-full py-2 px-3 focus:outline-none focus:border-blue-500" required>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="fecharModalEstoque()"
                        class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Cancelar</button>
                    <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Confirmar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalCriar" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">

            <div class="bg-blue-600 p-4 flex justify-between items-center sticky top-0 z-10">
                <h3 class="text-white font-bold text-lg">Cadastrar Novo Produto</h3>
                <button onclick="fecharModalCriar()"
                    class="text-white hover:text-gray-200 font-bold text-2xl">&times;</button>
            </div>

            <div class="p-8">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="criar_produto">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Nome da Peça *</label>
                                <input class="border rounded w-full py-2 px-3 focus:border-blue-500" name="nome"
                                    type="text" required>
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Descrição</label>
                                <textarea class="border rounded w-full py-2 px-3 focus:border-blue-500" name="descricao"
                                    rows="2"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Tamanho</label>
                                    <select class="border rounded w-full py-2 px-3 bg-white" name="tamanho">
                                        <option value="UNICO">Único</option>
                                        <option value="P">P</option>
                                        <option value="M">M</option>
                                        <option value="G">G</option>
                                        <option value="GG">GG</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Cor</label>
                                    <input class="border rounded w-full py-2 px-3" name="cor" type="text">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Foto</label>
                                <input type="file" name="foto" accept="image/*"
                                    class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:bg-blue-50 file:text-blue-700" />
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded border">
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Estoque Inicial</label>
                                <input class="border rounded w-full py-2 px-3" name="estoque" type="number" min="1"
                                    value="1" required>
                            </div>

                            <hr class="my-4">

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Custo (R$)</label>
                                    <input class="border rounded w-full py-2 px-3" id="custo" name="custo" type="text"
                                        oninput="calcLucro()">
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Venda (R$)</label>
                                    <input class="border rounded w-full py-2 px-3" id="venda" name="preco" type="text"
                                        oninput="calcLucro()" required>
                                </div>
                            </div>

                            <div class="mt-4 p-2 bg-white rounded text-center border">
                                <p class="text-xs text-gray-500 uppercase font-bold">Lucro Estimado</p>
                                <p class="text-xl font-bold text-gray-400" id="res_lucro">R$ 0,00</p>
                                <p class="text-sm text-gray-400" id="res_margem">0%</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end space-x-3">
                        <button type="button" onclick="fecharModalCriar()"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 px-6 rounded">Cancelar</button>
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded shadow">Salvar
                            Produto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // --- Funções do Modal Estoque (Pequeno) ---
        function abrirModalEstoque(id, nome) {
            document.getElementById('idProdutoModal').value = id;
            document.getElementById('nomeProdutoModal').innerText = nome;
            document.getElementById('modalEstoque').classList.remove('hidden');
        }
        function fecharModalEstoque() {
            document.getElementById('modalEstoque').classList.add('hidden');
        }

        // --- Funções do Modal Criar (Grande) ---
        function abrirModalCriar() {
            document.getElementById('modalCriar').classList.remove('hidden');
        }
        function fecharModalCriar() {
            document.getElementById('modalCriar').classList.add('hidden');
        }

        // --- Calculadora Financeira ---
        function calcLucro() {
            let custo = parseFloat(document.getElementById('custo').value.replace(',', '.')) || 0;
            let venda = parseFloat(document.getElementById('venda').value.replace(',', '.')) || 0;
            let lucro = venda - custo;
            let margem = (venda > 0) ? (lucro / venda) * 100 : 0;

            let elLucro = document.getElementById('res_lucro');
            elLucro.innerText = 'R$ ' + lucro.toFixed(2).replace('.', ',');
            elLucro.className = (lucro >= 0) ? 'text-xl font-bold text-green-600' : 'text-xl font-bold text-red-600';

            document.getElementById('res_margem').innerText = margem.toFixed(0).replace('.', ',') + '%';
        }

        // Fechar modais ao clicar fora
        window.onclick = function (event) {
            if (event.target == document.getElementById('modalEstoque')) fecharModalEstoque();
            if (event.target == document.getElementById('modalCriar')) fecharModalCriar();
        }

        document.addEventListener("DOMContentLoaded", function () {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('abrirModal') && urlParams.get('abrirModal') === 'true') {
                abrirModalCriar();
            }
        });
    </script>

</body>

</html>

</body>

</html>