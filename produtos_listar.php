<?php
require_once 'auth_check.php';
require_once 'conexao.php';

// --- PROCESSAMENTO DE FORMULÁRIOS (COM LÓGICA DE TOAST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    
    $toast_msg = '';
    $toast_type = '';

    // AÇÃO 1: ADICIONAR ESTOQUE (Entrada)
    if ($_POST['acao'] === 'adicionar_estoque') {
        try {
            $quantidade_entrada = (int)$_POST['quantidade'];
            // Blindagem: impede quantidade <= 0
            if ($quantidade_entrada <= 0) {
                throw new Exception("A quantidade de entrada deve ser um número positivo.");
            }
            $stmt = $pdo->prepare("UPDATE produtos SET estoque_loja = estoque_loja + ? WHERE id = ?");
            $stmt->execute([ $quantidade_entrada, (int)$_POST['id_produto'] ]);
            $toast_msg = "Estoque atualizado com sucesso!";
            $toast_type = "success";
        } catch (Exception $e) { 
            $toast_msg = "Erro: " . $e->getMessage();
            $toast_type = "error";
        }
    }

    // AÇÃO 2: CRIAR NOVO PRODUTO
    elseif ($_POST['acao'] === 'criar_produto') {
        try {
            $custo = (float)str_replace(',', '.', $_POST['custo']);
            $preco = (float)str_replace(',', '.', $_POST['preco']);
            $estoque = (int)$_POST['estoque'];

            // Blindagem: impede preços ou estoque negativos/zero
            if ($custo < 0 || $preco < 0) {
                throw new Exception("Preços de custo e venda não podem ser negativos.");
            }
            if ($estoque <= 0) {
                throw new Exception("O estoque inicial deve ser um número positivo.");
            }
            
            // Lógica de Upload
            $caminho_imagem = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
                $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $novo_nome = uniqid() . "." . $extensao;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $novo_nome)) {
                    $caminho_imagem = $novo_nome;
                }
            }
            $sql = "INSERT INTO produtos (nome, descricao, tamanho, cor, preco_custo, preco, estoque_loja, imagem) VALUES (:nome, :descricao, :tamanho, :cor, :custo, :preco, :estoque, :imagem)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $_POST['nome'], ':descricao' => $_POST['descricao'], ':tamanho' => $_POST['tamanho'], ':cor' => $_POST['cor'],
                ':custo' => $custo, ':preco' => $preco, ':estoque' => $estoque, ':imagem' => $caminho_imagem
            ]);
            $toast_msg = "Novo produto cadastrado com sucesso!";
            $toast_type = "success";
        } catch (Exception $e) { 
            $toast_msg = "Erro ao cadastrar: " . $e->getMessage();
            $toast_type = "error";
        }
    }

    // AÇÃO 3: EDITAR PRODUTO
    elseif ($_POST['acao'] === 'editar_produto') {
        try {
            $custo = (float)str_replace(',', '.', $_POST['custo_edit']);
            $preco = (float)str_replace(',', '.', $_POST['preco_edit']);

            // Blindagem: impede preços negativos
            if ($custo < 0 || $preco < 0) {
                throw new Exception("Preços de custo e venda não podem ser negativos.");
            }

            $id_produto_edit = (int)$_POST['id_produto_edit'];
            $caminho_imagem = $_POST['imagem_atual'];
            if (isset($_FILES['foto_edit']) && $_FILES['foto_edit']['error'] === 0) {
                $extensao = pathinfo($_FILES['foto_edit']['name'], PATHINFO_EXTENSION);
                $novo_nome = uniqid() . "." . $extensao;
                if (move_uploaded_file($_FILES['foto_edit']['tmp_name'], 'uploads/' . $novo_nome)) {
                    $caminho_imagem = $novo_nome;
                }
            }
            $sql = "UPDATE produtos SET nome = :nome, descricao = :descricao, tamanho = :tamanho, cor = :cor, preco_custo = :custo, preco = :preco, imagem = :imagem WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nome' => $_POST['nome_edit'], ':descricao' => $_POST['descricao_edit'], ':tamanho' => $_POST['tamanho_edit'], ':cor' => $_POST['cor_edit'],
                ':custo' => $custo, ':preco' => $preco, ':imagem' => $caminho_imagem, ':id' => $id_produto_edit
            ]);
            $toast_msg = "Produto ID #$id_produto_edit atualizado!";
            $toast_type = "success";
        } catch (Exception $e) { 
            $toast_msg = "Erro ao editar: " . $e->getMessage();
            $toast_type = "error";
        }
    }
    
    // Redireciona com a mensagem na URL
    $location = "produtos_listar.php?msg=" . urlencode($toast_msg) . "&type=" . $toast_type;
    header("Location: " . $location);
    exit;
}

// Título da Página
$titulo_pagina = "Estoque";

// --- SQL (Carrega todos os produtos) ---
$produtos = $pdo->query("SELECT * FROM produtos ORDER BY id DESC")->fetchAll();
$total_itens = count($produtos);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Estoque e Financeiro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        @media (max-width: 767px) {
            .tabela-responsiva thead { display: none; }
            .tabela-responsiva, .tabela-responsiva tbody, .tabela-responsiva tr { display: block; width: 100%; }
            .tabela-responsiva tr { margin-bottom: 1rem; border: 1px solid #ddd; border-radius: 0.5rem; overflow: hidden; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
            .tabela-responsiva td { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; border-bottom: 1px solid #eee; text-align: right; width: 100%; }
            .tabela-responsiva td::before { content: attr(data-label); font-weight: bold; text-align: left; padding-right: 1rem; color: #555; flex-shrink: 0; }
            .tabela-responsiva tr td:last-child { border-bottom: 0; }
            .tabela-responsiva td.celula-acao { display: block; }
            .tabela-responsiva td.celula-acao::before { display: none; }
            .tabela-responsiva td.celula-acao > div { justify-content: center; flex-wrap: wrap; gap: 0.5rem; }
            .tabela-responsiva td.celula-produto { display: block; text-align: left; }
            .tabela-responsiva td.celula-produto::before { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100 overflow-y-scroll">

    <?php include 'menu.php'; ?>

    <div class="container mx-auto px-4 mt-8 mb-20">
        
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Gestão de Estoque (<?= $total_itens ?>)</h2>
            <button onclick="abrirModalCriar()" class="bg-roxo-base hover:bg-purple-700 text-white px-4 py-2 rounded shadow font-bold transition flex items-center">
                <i class="bi bi-plus-lg mr-2"></i> Novo Produto
            </button>
        </div>

        <div class="mb-6">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="bi bi-search text-gray-400"></i>
                </div>
                <input type="text" id="filtroBusca" onkeyup="filtrarTabela()" 
                       placeholder="Filtrar por nome ou ID..." 
                       class="w-full border rounded-lg py-2 px-4 pl-10 focus:outline-none focus:border-roxo-base">
            </div>
        </div>

        <div class="bg-white md:shadow-md md:rounded-lg overflow-hidden overflow-x-auto tabela-responsiva">
            <table class="min-w-full leading-normal">
                <thead class="hidden md:table-header-group">
                    <tr>
                        <th class="px-3 py-3 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase">ID</th>
                        <th class="px-3 py-3 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase" colspan="2">Produto</th>
                        <th class="px-3 py-3 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase">Financeiro</th>
                        <th class="px-3 py-3 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase">Lucro</th>
                        <th class="px-3 py-3 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase">Estoque</th>
                        <th class="px-3 py-3 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase">Ações</th>
                    </tr>
                </thead>
                <tbody id="corpoTabelaProdutos">
                    <?php foreach ($produtos as $p): 
                        $custo = $p['preco_custo']; $venda = $p['preco'];
                        $lucro_rs = $venda - $custo;
                        $margem = ($venda > 0) ? ($lucro_rs / $venda) * 100 : 0;
                    ?>
                        <tr class="block md:table-row hover:bg-gray-50 border-b border-gray-200 md:border-b-0"
                            data-nome="<?= strtolower(htmlspecialchars($p['nome'])) ?>" 
                            data-id="#<?= $p['id'] ?>">
                            
                            <td data-label="ID" class="px-5 py-3 md:px-3 md:py-4 text-sm md:table-cell md:text-center">
                                <span class="text-gray-500">#<?= $p['id'] ?></span>
                            </td>
<td class="px-5 py-4 md:px-3 md:py-4 md:w-auto celula-produto">
    <div class="flex items-center">
        <div class="flex-shrink-0 w-12 h-12 rounded overflow-hidden border bg-gray-100 flex items-center justify-center">
            <?php if (!empty($p['imagem'])): ?>
                <img src="uploads/<?= $p['imagem'] ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <i class="bi bi-tshirt-fill text-3xl text-gray-400"></i>
            <?php endif; ?>
        </div>
        <div class="ml-3">
            <p class="text-gray-900 font-bold whitespace-normal"><?= htmlspecialchars($p['nome']) ?></p>
            <p class="text-gray-500 text-xs"><?= $p['tamanho'] ?> | <?= $p['cor'] ?></p>
        </div>
    </div>
</td>
                            <td class="hidden md:table-cell"></td>
                            <td data-label="Financeiro" class="px-5 py-3 md:px-3 md:py-4 text-sm md:table-cell md:text-center">
                                <div>
                                    <div class="text-xs text-gray-500">Custo: <?= number_format($custo, 2, ',', '.') ?></div>
                                    <div class="font-bold text-gray-800">Venda: <?= number_format($venda, 2, ',', '.') ?></div>
                                </div>
                            </td>
                            <td data-label="Lucro" class="px-5 py-3 md:px-3 md:py-4 text-sm md:table-cell md:text-center">
                                <div>
                                    <span class="<?= $lucro_rs >= 0 ? 'text-green-600' : 'text-red-600' ?> font-bold">R$ <?= number_format($lucro_rs, 2, ',', '.') ?></span>
                                    <div class="text-xs text-gray-400"><?= number_format($margem, 0) ?>%</div>
                                </div>
                            </td>
                            <td data-label="Estoque" class="px-5 py-3 md:px-3 md:py-4 text-sm md:table-cell md:text-center">
                                <span class="bg-gray-100 text-gray-800 py-1 px-3 rounded-full text-xs font-bold border">
                                    <?= $p['estoque_loja'] ?>
                                </span>
                            </td>
                            <td data-label="Ação" class="px-5 py-3 md:px-3 md:py-4 text-sm md:table-cell md:text-center celula-acao">
                                <div class="flex justify-center items-center space-x-2">
                                    <button onclick="abrirModalEstoque(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nome']) ?>')" 
                                            class="text-green-600 hover:text-green-900 bg-green-100 hover:bg-green-200 p-2 rounded-lg text-xs font-bold" title="Adicionar Estoque">
                                        <i class="bi bi-plus-circle-fill"></i> Entrada
                                    </button>
                                    <button onclick="abrirModalEditar(this)"
                                            data-id="<?= $p['id'] ?>" data-nome="<?= htmlspecialchars($p['nome'], ENT_QUOTES) ?>" data-descricao="<?= htmlspecialchars($p['descricao'], ENT_QUOTES) ?>"
                                            data-tamanho="<?= $p['tamanho'] ?>" data-cor="<?= htmlspecialchars($p['cor'], ENT_QUOTES) ?>" data-custo="<?= number_format($p['preco_custo'], 2, ',', '.') ?>"
                                            data-venda="<?= number_format($p['preco'], 2, ',', '.') ?>" data-imagem="<?= $p['imagem'] ?>"
                                            class="text-amber-600 hover:text-amber-900 bg-amber-100 hover:bg-amber-200 p-2 rounded-lg text-xs font-bold" title="Editar Produto">
                                        <i class="bi bi-pencil-fill"></i> Editar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="linhaSemResultados" class="hidden">
                         <td colspan="7" class="text-center py-10 text-gray-500">Nenhum produto encontrado.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modalEstoque" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white p-6 rounded-lg shadow-xl w-96">
            <h3 class="text-lg font-bold text-gray-800 mb-2"><i class="bi bi-box-arrow-in-down mr-2"></i>Repor Estoque</h3>
            <p class="text-gray-600 text-sm mb-4">Produto: <strong id="nomeProdutoModal" class="text-roxo-base">...</strong></p>
            <form method="POST" action="">
                <input type="hidden" name="acao" value="adicionar_estoque">
                <input type="hidden" name="id_produto" id="idProdutoModal">
                <div class="mb-4"><label class="block text-gray-700 text-sm font-bold mb-2">Quantidade:</label><input type="number" name="quantidade" min="1" value="1" class="border rounded w-full py-2 px-3" required></div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="fecharModalEstoque()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">Cancelar</button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">Confirmar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalCriar" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="bg-roxo-base p-4 flex justify-between items-center sticky top-0 z-10"><h3 class="text-white font-bold text-lg"><i class="bi bi-box-seam-fill mr-2"></i>Cadastrar Novo Produto</h3><button onclick="fecharModalCriar()" class="text-white hover:text-gray-200 text-2xl">&times;</button></div>
            <div class="p-8">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="criar_produto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <div class="mb-4"><label class="block text-sm font-bold mb-2">Nome*</label><input class="border rounded w-full py-2 px-3" name="nome" type="text" required></div>
                            <div class="mb-4"><label class="block text-sm font-bold mb-2">Descrição</label><textarea class="border rounded w-full py-2 px-3" name="descricao" rows="2"></textarea></div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div><label class="block text-sm font-bold mb-2">Tamanho</label><select class="border rounded w-full py-2 px-3 bg-white" name="tamanho"><option value="UNICO">Único</option><option value="P">P</option><option value="M">M</option><option value="G">G</option><option value="GG">GG</option></select></div>
                                <div><label class="block text-sm font-bold mb-2">Cor</label><input class="border rounded w-full py-2 px-3" name="cor" type="text"></div>
                            </div>
                            <div class="mb-4"><label class="block text-sm font-bold mb-2">Foto</label><input type="file" name="foto" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:bg-blue-50 file:text-blue-700"/></div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded border">
                            <div class="mb-4"><label class="block text-sm font-bold mb-2">Estoque Inicial*</label><input class="border rounded w-full py-2 px-3" name="estoque" type="number" min="1" value="1" required></div>
                            <hr class="my-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="block text-sm font-bold mb-2">Custo (R$)</label><input class="border rounded w-full py-2 px-3" id="custo" name="custo" type="text" oninput="calcLucro('custo', 'venda', 'res_lucro', 'res_margem')"></div>
                                <div><label class="block text-sm font-bold mb-2">Venda (R$)*</label><input class="border rounded w-full py-2 px-3" id="venda" name="preco" type="text" oninput="calcLucro('custo', 'venda', 'res_lucro', 'res_margem')" required></div>
                            </div>
                            <div class="mt-4 p-2 bg-white rounded text-center border"><p class="text-xs text-gray-500 uppercase font-bold">Lucro Estimado</p><p class="text-xl font-bold text-gray-400" id="res_lucro">R$ 0,00</p><p class="text-sm text-gray-400" id="res_margem">0%</p></div>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-end space-x-3">
                        <button type="button" onclick="fecharModalCriar()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 px-6 rounded">Cancelar</button>
                        <button type="submit" class="bg-roxo-base hover:bg-purple-700 text-white font-bold py-3 px-6 rounded shadow">Salvar Produto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modalEditar" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="bg-amber-500 p-4 flex justify-between items-center sticky top-0 z-10"><h3 class="text-white font-bold text-lg"><i class="bi bi-pencil-fill mr-2"></i>Editar Produto</h3><button onclick="fecharModalEditar()" class="text-white hover:text-gray-200 text-2xl">&times;</button></div>
            <div class="p-8">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="editar_produto">
                    <input type="hidden" name="id_produto_edit" id="edit_id_produto">
                    <input type="hidden" name="imagem_atual" id="edit_imagem_atual">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <div class="mb-4"><label class="block text-sm font-bold mb-2">Nome*</label><input class="border rounded w-full py-2 px-3" id="edit_nome" name="nome_edit" type="text" required></div>
                            <div class="mb-4"><label class="block text-sm font-bold mb-2">Descrição</label><textarea class="border rounded w-full py-2 px-3" id="edit_descricao" name="descricao_edit" rows="2"></textarea></div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div><label class="block text-sm font-bold mb-2">Tamanho</label><select class="border rounded w-full py-2 px-3 bg-white" id="edit_tamanho" name="tamanho_edit"><option value="UNICO">Único</option><option value="P">P</option><option value="M">M</option><option value="G">G</option><option value="GG">GG</option></select></div>
                                <div><label class="block text-sm font-bold mb-2">Cor</label><input class="border rounded w-full py-2 px-3" id="edit_cor" name="cor_edit" type="text"></div>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded border">
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="block text-sm font-bold mb-2">Custo (R$)</label><input class="border rounded w-full py-2 px-3" id="edit_custo" name="custo_edit" type="text" oninput="calcLucro('edit_custo', 'edit_venda', 'edit_res_lucro', 'edit_res_margem')"></div>
                                <div><label class="block text-sm font-bold mb-2">Venda (R$)*</label><input class="border rounded w-full py-2 px-3" id="edit_venda" name="preco_edit" type="text" oninput="calcLucro('edit_custo', 'edit_venda', 'edit_res_lucro', 'edit_res_margem')" required></div>
                            </div>
                            <div class="mt-4 p-2 bg-white rounded text-center border"><p class="text-xs text-gray-500 uppercase font-bold">Lucro Estimado</p><p class="text-xl font-bold text-gray-400" id="edit_res_lucro">R$ 0,00</p><p class="text-sm text-gray-400" id="edit_res_margem">0%</p></div>
                            <hr class="my-4">
                            <div class="mb-4"><label class="block text-sm font-bold mb-2">Alterar Foto</label>
                                <input type="file" name="foto_edit" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:bg-blue-50 file:text-blue-700"/>
                                <div class="mt-2 flex items-center text-xs text-gray-500">
                                    <img id="edit_img_preview" src="" class="w-10 h-10 rounded border object-cover mr-2">
                                    <span id="edit_img_nome">...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 flex justify-end space-x-3">
                        <button type="button" onclick="fecharModalEditar()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-3 px-6 rounded">Cancelar</button>
                        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-6 rounded shadow">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        // --- Modais (Estoque, Criar, Editar) ---
        function abrirModalEstoque(id, nome) {
            document.getElementById('idProdutoModal').value = id;
            document.getElementById('nomeProdutoModal').innerText = nome;
            document.getElementById('modalEstoque').classList.remove('hidden');
        }
        function fecharModalEstoque() { document.getElementById('modalEstoque').classList.add('hidden'); }
        function abrirModalCriar() { document.getElementById('modalCriar').classList.remove('hidden'); }
        function fecharModalCriar() { document.getElementById('modalCriar').classList.add('hidden'); }
        
        // Esta é a função que estava a falhar
        function abrirModalEditar(button) {
            try {
                const data = button.dataset;
                document.getElementById('edit_id_produto').value = data.id;
                document.getElementById('edit_nome').value = data.nome;
                document.getElementById('edit_descricao').value = data.descricao;
                document.getElementById('edit_tamanho').value = data.tamanho;
                document.getElementById('edit_cor').value = data.cor;
                document.getElementById('edit_custo').value = data.custo;
                document.getElementById('edit_venda').value = data.venda;
                document.getElementById('edit_imagem_atual').value = data.imagem;
                document.getElementById('edit_img_nome').innerText = data.imagem || "Sem foto";
                document.getElementById('edit_img_preview').src = data.imagem ? 'uploads/' + data.imagem : 'img/default_avatar.png';
                
                // Calcula o lucro inicial
                calcLucro('edit_custo', 'edit_venda', 'edit_res_lucro', 'edit_res_margem');
                
                // Abre o modal
                document.getElementById('modalEditar').classList.remove('hidden');
            } catch (e) {
                console.error("Erro ao abrir o modal de edição:", e);
                alert("Ocorreu um erro ao tentar abrir o modal. Verifique o console (F12).");
            }
        }
        function fecharModalEditar() { document.getElementById('modalEditar').classList.add('hidden'); }

        // --- Calculadora Financeira ---
        function calcLucro(idCusto, idVenda, idResLucro, idResMargem) {
            let custo = parseFloat(document.getElementById(idCusto).value.replace(',', '.')) || 0;
            let venda = parseFloat(document.getElementById(idVenda).value.replace(',', '.')) || 0;
            let lucro = venda - custo;
            let margem = (venda > 0) ? (lucro / venda) * 100 : 0;
            let elLucro = document.getElementById(idResLucro);
            elLucro.innerText = 'R$ ' + lucro.toFixed(2).replace('.', ',');
            elLucro.className = (lucro >= 0) ? 'text-xl font-bold text-green-600' : 'text-xl font-bold text-red-600';
            document.getElementById(idResMargem).innerText = margem.toFixed(0).replace('.', ',') + '%';
        }

        // --- Filtro em Tempo Real ---
        function filtrarTabela() {
            const input = document.getElementById('filtroBusca');
            const tabela = document.getElementById('corpoTabelaProdutos');
            const linhas = tabela.getElementsByTagName('tr');
            const linhaSemResultados = document.getElementById('linhaSemResultados');
            
            const termoBusca = input.value.toLowerCase();
            let resultadosEncontrados = 0;

            for (let i = 0; i < linhas.length; i++) {
                const linha = linhas[i];
                if (linha.id === 'linhaSemResultados') continue;

                const nomeProduto = linha.dataset.nome;
                const idProduto = linha.dataset.id; 

                if (nomeProduto.includes(termoBusca) || idProduto.includes(termoBusca)) {
                    linha.style.display = ""; 
                    resultadosEncontrados++;
                } else {
                    linha.style.display = "none";
                }
            }

            if (resultadosEncontrados === 0) {
                linhaSemResultados.style.display = "";
            } else {
                linhaSemResultados.style.display = "none";
            }
        }

        // --- Fechar modais ao clicar fora ---
        window.onclick = function(event) {
            if (event.target == document.getElementById('modalEstoque')) fecharModalEstoque();
            if (event.target == document.getElementById('modalCriar')) fecharModalCriar();
            if (event.target == document.getElementById('modalEditar')) fecharModalEditar();
        }

        // --- Abrir modal via URL ---
        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('abrirModal') && urlParams.get('abrirModal') === 'true') {
                abrirModalCriar();
            }
        });
    </script>
    
    <?php include 'toast_handler.php'; ?>

</body>
</html>