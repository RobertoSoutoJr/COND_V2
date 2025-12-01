<?php
require_once 'auth_check.php';
require_once 'conexao.php';

// --- PROCESSAMENTO DE FORMULÁRIOS (AÇÕES) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    
    $toast_msg = '';
    $toast_type = '';

    // AÇÃO 1: ADICIONAR ESTOQUE
    if ($_POST['acao'] === 'adicionar_estoque') {
        try {
            $quantidade_entrada = (int)$_POST['quantidade'];
            if ($quantidade_entrada <= 0) throw new Exception("A quantidade deve ser positiva.");
            
            $stmt = $pdo->prepare("UPDATE produtos SET estoque_loja = estoque_loja + ? WHERE id = ?");
            $stmt->execute([ $quantidade_entrada, (int)$_POST['id_produto'] ]);
            $toast_msg = "Estoque atualizado com sucesso!";
            $toast_type = "success";
        } catch (Exception $e) { 
            $toast_msg = "Erro: " . $e->getMessage();
            $toast_type = "error";
        }
    }
    // AÇÃO 2: CRIAR PRODUTO
    elseif ($_POST['acao'] === 'criar_produto') {
        try {
            $custo = (float)str_replace(',', '.', $_POST['custo']);
            $preco = (float)str_replace(',', '.', $_POST['preco']);
            $estoque = (int)$_POST['estoque'];

            if ($custo < 0 || $preco < 0) throw new Exception("Preços não podem ser negativos.");
            if ($estoque <= 0) throw new Exception("Estoque inicial deve ser positivo.");
            
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
            $stmt->execute([':nome' => $_POST['nome'], ':descricao' => $_POST['descricao'], ':tamanho' => $_POST['tamanho'], ':cor' => $_POST['cor'], ':custo' => $custo, ':preco' => $preco, ':estoque' => $estoque, ':imagem' => $caminho_imagem]);
            $toast_msg = "Produto cadastrado com sucesso!";
            $toast_type = "success";
        } catch (Exception $e) { 
            $toast_msg = "Erro: " . $e->getMessage();
            $toast_type = "error";
        }
    }
    // AÇÃO 3: EDITAR PRODUTO
    elseif ($_POST['acao'] === 'editar_produto') {
        try {
            $custo = (float)str_replace(',', '.', $_POST['custo_edit']);
            $preco = (float)str_replace(',', '.', $_POST['preco_edit']);
            if ($custo < 0 || $preco < 0) throw new Exception("Preços não podem ser negativos.");

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
            $stmt->execute([':nome' => $_POST['nome_edit'], ':descricao' => $_POST['descricao_edit'], ':tamanho' => $_POST['tamanho_edit'], ':cor' => $_POST['cor_edit'], ':custo' => $custo, ':preco' => $preco, ':imagem' => $caminho_imagem, ':id' => $id_produto_edit]);
            $toast_msg = "Produto atualizado com sucesso!";
            $toast_type = "success";
        } catch (Exception $e) { 
            $toast_msg = "Erro: " . $e->getMessage();
            $toast_type = "error";
        }
    }
    
    header("Location: produtos_listar.php?msg=" . urlencode($toast_msg) . "&type=" . $toast_type);
    exit;
}

$titulo_pagina = "Estoque";
$produtos = $pdo->query("SELECT * FROM produtos ORDER BY id DESC")->fetchAll();
$total_itens = count($produtos);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Estoque - COND</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        @media (max-width: 767px) {
            .tabela-responsiva thead { display: none; }
            .tabela-responsiva, .tabela-responsiva tbody, .tabela-responsiva tr { display: block; width: 100%; }
            .tabela-responsiva tr { margin-bottom: 1rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; overflow: hidden; background: #fff; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
            .tabela-responsiva td { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; text-align: right; width: 100%; }
            .tabela-responsiva td::before { content: attr(data-label); font-weight: 600; text-align: left; padding-right: 1rem; color: #6b7280; flex-shrink: 0; font-size: 0.875rem; }
            .tabela-responsiva tr td:last-child { border-bottom: 0; }
            .tabela-responsiva td.celula-acao { display: block; }
            .tabela-responsiva td.celula-acao::before { display: none; }
            .tabela-responsiva td.celula-acao > div { justify-content: center; flex-wrap: wrap; gap: 0.5rem; }
            .tabela-responsiva td.celula-produto { display: block; text-align: left; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; }
            .tabela-responsiva td.celula-produto::before { display: none; }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-900 overflow-y-scroll">

    <?php include 'menu.php'; ?>

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
                    <h1 class="text-2xl font-bold text-gray-800">Gestão de Estoque</h1>
                    <p class="text-sm text-gray-500">Total de <?= $total_itens ?> produtos cadastrados.</p>
                </div>
                <button onclick="abrirModalCriar()" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white transition-colors bg-roxo-base rounded-lg hover:bg-purple-700 shadow-md">
                    <i class="bi bi-plus-lg mr-2"></i> Novo Produto
                </button>
            </div>

            <div class="mb-6 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="bi bi-search text-gray-400"></i>
                </div>
                <input type="text" id="filtroBusca" onkeyup="filtrarTabela()" 
                       placeholder="Filtrar por nome ou ID..." 
                       class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all bg-white shadow-sm text-sm">
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden overflow-x-auto tabela-responsiva">
                <table class="min-w-full leading-normal">
                    <thead class="hidden md:table-header-group bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" colspan="2">Produto</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Financeiro</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Lucro</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Estoque</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 md:bg-white" id="corpoTabelaProdutos">
                        <?php foreach ($produtos as $p): 
                            $custo = $p['preco_custo']; $venda = $p['preco'];
                            $lucro_rs = $venda - $custo;
                            $margem = ($venda > 0) ? ($lucro_rs / $venda) * 100 : 0;
                        ?>
                            <tr class="block md:table-row hover:bg-gray-50 transition-colors"
                                data-nome="<?= strtolower(htmlspecialchars($p['nome'])) ?>" 
                                data-id="#<?= $p['id'] ?>">
                                
                                <td data-label="ID" class="px-6 py-4 text-sm text-gray-500 md:table-cell md:text-center">
                                    #<?= $p['id'] ?>
                                </td>
                                <td class="px-6 py-4 md:table-cell md:w-auto celula-produto">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-12 h-12 rounded-lg overflow-hidden border border-gray-200 bg-gray-50 flex items-center justify-center">
                                            <?php if (!empty($p['imagem'])): ?>
                                                <img src="uploads/<?= $p['imagem'] ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <i class="bi bi-tshirt-fill text-2xl text-gray-300"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($p['nome']) ?></p>
                                            <p class="text-xs text-gray-500"><?= $p['tamanho'] ?> | <?= $p['cor'] ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden md:table-cell"></td>

                                <td data-label="Financeiro" class="px-6 py-4 text-sm md:table-cell md:text-center">
                                    <div class="flex flex-col items-end md:items-center">
                                        <span class="font-bold text-gray-800">R$ <?= number_format($venda, 2, ',', '.') ?></span>
                                        <span class="text-xs text-gray-500">Custo: R$ <?= number_format($custo, 2, ',', '.') ?></span>
                                    </div>
                                </td>
                                <td data-label="Lucro" class="px-6 py-4 text-sm md:table-cell md:text-center">
                                    <div class="flex flex-col items-end md:items-center">
                                        <span class="<?= $lucro_rs >= 0 ? 'text-green-600' : 'text-red-600' ?> font-bold">R$ <?= number_format($lucro_rs, 2, ',', '.') ?></span>
                                        <span class="text-xs text-gray-400"><?= number_format($margem, 0) ?>%</span>
                                    </div>
                                </td>
                                <td data-label="Estoque" class="px-6 py-4 text-sm md:table-cell md:text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
                                        <?= $p['estoque_loja'] ?> un
                                    </span>
                                </td>
                                <td data-label="Ação" class="px-6 py-4 text-sm md:table-cell md:text-center celula-acao">
                                    <div class="flex justify-center items-center space-x-2">
                                        <button onclick="abrirModalEstoque(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nome']) ?>')" 
                                                class="p-2 text-green-600 hover:text-green-800 bg-green-50 hover:bg-green-100 rounded-lg transition-colors" title="Repor Estoque">
                                            <i class="bi bi-plus-circle-fill text-lg"></i>
                                        </button>
                                        <button onclick="abrirModalEditar(this)"
                                                data-id="<?= $p['id'] ?>" data-nome="<?= htmlspecialchars($p['nome'], ENT_QUOTES) ?>" data-descricao="<?= htmlspecialchars($p['descricao'], ENT_QUOTES) ?>"
                                                data-tamanho="<?= $p['tamanho'] ?>" data-cor="<?= htmlspecialchars($p['cor'], ENT_QUOTES) ?>" data-custo="<?= number_format($p['preco_custo'], 2, ',', '.') ?>"
                                                data-venda="<?= number_format($p['preco'], 2, ',', '.') ?>" data-imagem="<?= $p['imagem'] ?>"
                                                class="p-2 text-amber-600 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors" title="Editar Produto">
                                            <i class="bi bi-pencil-fill text-lg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="linhaSemResultados" class="hidden">
                             <td colspan="7" class="text-center py-12 text-gray-500">
                                <i class="bi bi-search text-4xl mb-2 block text-gray-300"></i>
                                Nenhum produto encontrado.
                             </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="modalEstoque" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white p-6 rounded-xl shadow-xl w-96 border border-gray-100">
            <h3 class="text-lg font-bold text-gray-800 mb-2 flex items-center"><i class="bi bi-box-arrow-in-down mr-2 text-green-600"></i> Repor Estoque</h3>
            <p class="text-sm text-gray-500 mb-4">Produto: <strong id="nomeProdutoModal" class="text-gray-800">...</strong></p>
            <form method="POST" action="">
                <input type="hidden" name="acao" value="adicionar_estoque">
                <input type="hidden" name="id_produto" id="idProdutoModal">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantidade</label>
                    <input type="number" name="quantidade" min="1" value="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" required>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="fecharModalEstoque()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">Confirmar</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalCriar" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto border border-gray-100">
            <div class="bg-roxo-base p-4 flex justify-between items-center sticky top-0 z-10 rounded-t-xl">
                <h3 class="text-white font-bold text-lg flex items-center"><i class="bi bi-box-seam-fill mr-2"></i> Cadastrar Produto</h3>
                <button onclick="fecharModalCriar()" class="text-white hover:text-purple-200 transition-colors"><i class="bi bi-x-lg text-xl"></i></button>
            </div>
            <div class="p-6">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="criar_produto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Nome*</label><input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" name="nome" type="text" required></div>
                            <div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label><textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" name="descricao" rows="2"></textarea></div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div><label class="block text-sm font-medium text-gray-700 mb-1">Tamanho</label><select class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-purple-500" name="tamanho"><option value="UNICO">Único</option><option value="P">P</option><option value="M">M</option><option value="G">G</option><option value="GG">GG</option></select></div>
                                <div><label class="block text-sm font-medium text-gray-700 mb-1">Cor</label><input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" name="cor" type="text"></div>
                            </div>
                            <div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Foto</label><input type="file" name="foto" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-roxo-base hover:file:bg-purple-100 cursor-pointer"/></div>
                        </div>
                        <div class="bg-gray-50 p-6 rounded-xl border border-gray-100">
                            <div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Estoque Inicial*</label><input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" name="estoque" type="number" min="1" value="1" required></div>
                            <hr class="my-4 border-gray-200">
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="block text-sm font-medium text-gray-700 mb-1">Custo (R$)</label><input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500" id="custo" name="custo" type="text" oninput="calcLucro('custo', 'venda', 'res_lucro', 'res_margem')"></div>
                                <div><label class="block text-sm font-medium text-gray-700 mb-1">Venda (R$)*</label><input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 font-bold text-gray-800" id="venda" name="preco" type="text" oninput="calcLucro('custo', 'venda', 'res_lucro', 'res_margem')" required></div>
                            </div>
                            <div class="mt-4 p-3 bg-white rounded-lg text-center border border-gray-200"><p class="text-xs text-gray-400 uppercase font-bold">Lucro Estimado</p><p class="text-xl font-bold text-gray-400" id="res_lucro">R$ 0,00</p><p class="text-sm text-gray-400" id="res_margem">0%</p></div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3 pt-4 border-t border-gray-100">
                        <button type="button" onclick="fecharModalCriar()" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">Cancelar</button>
                        <button type="submit" class="px-5 py-2.5 bg-roxo-base text-white rounded-lg hover:bg-purple-700 shadow-md font-medium transition-colors">Salvar Produto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modalEditar" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto border border-gray-100">
            <div class="bg-amber-500 p-4 flex justify-between items-center sticky top-0 z-10 rounded-t-xl"><h3 class="text-white font-bold text-lg flex items-center"><i class="bi bi-pencil-square mr-2"></i> Editar Produto</h3><button onclick="fecharModalEditar()" class="text-white hover:text-amber-200 transition-colors"><i class="bi bi-x-lg text-xl"></i></button></div>
            <div class="p-6">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="editar_produto">
                    <input type="hidden" name="id_produto_edit" id="edit_id_produto">
                    <input type="hidden" name="imagem_atual" id="edit_imagem_atual">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Nome*</label><input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent" id="edit_nome" name="nome_edit" type="text" required></div>
                            <div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label><textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent" id="edit_descricao" name="descricao_edit" rows="2"></textarea></div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div><label class="block text-sm font-medium text-gray-700 mb-1">Tamanho</label><select class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-amber-500" id="edit_tamanho" name="tamanho_edit"><option value="UNICO">Único</option><option value="P">P</option><option value="M">M</option><option value="G">G</option><option value="GG">GG</option></select></div>
                                <div><label class="block text-sm font-medium text-gray-700 mb-1">Cor</label><input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500" id="edit_cor" name="cor_edit" type="text"></div>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-6 rounded-xl border border-gray-100">
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="block text-sm font-medium text-gray-700 mb-1">Custo (R$)</label><input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500" id="edit_custo" name="custo_edit" type="text" oninput="calcLucro('edit_custo', 'edit_venda', 'edit_res_lucro', 'edit_res_margem')"></div>
                                <div><label class="block text-sm font-medium text-gray-700 mb-1">Venda (R$)*</label><input class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 font-bold text-gray-800" id="edit_venda" name="preco_edit" type="text" oninput="calcLucro('edit_custo', 'edit_venda', 'edit_res_lucro', 'edit_res_margem')" required></div>
                            </div>
                            <div class="mt-4 p-3 bg-white rounded-lg text-center border border-gray-200"><p class="text-xs text-gray-400 uppercase font-bold">Lucro Estimado</p><p class="text-xl font-bold text-gray-400" id="edit_res_lucro">R$ 0,00</p><p class="text-sm text-gray-400" id="edit_res_margem">0%</p></div>
                            <hr class="my-4 border-gray-200">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Alterar Foto</label>
                                <input type="file" name="foto_edit" accept="image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-600 hover:file:bg-amber-100 cursor-pointer"/>
                                <div class="mt-3 flex items-center text-xs text-gray-500 bg-white p-2 rounded border border-gray-200">
                                    <img id="edit_img_preview" src="" class="w-10 h-10 rounded border object-cover mr-3">
                                    <span id="edit_img_nome" class="truncate">...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3 pt-4 border-t border-gray-100">
                        <button type="button" onclick="fecharModalEditar()" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">Cancelar</button>
                        <button type="submit" class="px-5 py-2.5 bg-amber-500 text-white rounded-lg hover:bg-amber-600 shadow-md font-medium transition-colors">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        function abrirModalEstoque(id, nome) {
            document.getElementById('idProdutoModal').value = id;
            document.getElementById('nomeProdutoModal').innerText = nome;
            document.getElementById('modalEstoque').classList.remove('hidden');
        }
        function fecharModalEstoque() { document.getElementById('modalEstoque').classList.add('hidden'); }
        function abrirModalCriar() { document.getElementById('modalCriar').classList.remove('hidden'); }
        function fecharModalCriar() { document.getElementById('modalCriar').classList.add('hidden'); }
        
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
                
                calcLucro('edit_custo', 'edit_venda', 'edit_res_lucro', 'edit_res_margem');
                document.getElementById('modalEditar').classList.remove('hidden');
            } catch (e) {
                console.error("Erro modal:", e);
            }
        }
        function fecharModalEditar() { document.getElementById('modalEditar').classList.add('hidden'); }

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

        window.onclick = function(event) {
            if (event.target == document.getElementById('modalEstoque')) fecharModalEstoque();
            if (event.target == document.getElementById('modalCriar')) fecharModalCriar();
            if (event.target == document.getElementById('modalEditar')) fecharModalEditar();
        }

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