<?php
require_once 'auth_check.php';
require_once 'conexao.php';

// Título da Página
$titulo_pagina = "Novo Produto";
$toast_msg = '';
$toast_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. BLINDAGEM (Preços e Estoque)
        $custo = (float)str_replace(',', '.', $_POST['custo']);
        $preco = (float)str_replace(',', '.', $_POST['preco']);
        $estoque = (int)$_POST['estoque'];

        if ($custo < 0 || $preco < 0) throw new Exception("Preços não podem ser negativos.");
        if ($estoque <= 0) throw new Exception("O estoque inicial deve ser positivo.");

        // 2. UPLOAD
        $caminho_imagem = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $permitidos = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array(strtolower($extensao), $permitidos)) {
                $novo_nome = uniqid() . "." . $extensao;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/' . $novo_nome)) {
                    $caminho_imagem = $novo_nome;
                }
            } else {
                throw new Exception("Formato de imagem inválido. Use JPG ou PNG.");
            }
        }

        // 3. SALVAR
        $sql = "INSERT INTO produtos (nome, descricao, tamanho, cor, preco_custo, preco, estoque_loja, imagem) 
                VALUES (:nome, :descricao, :tamanho, :cor, :custo, :preco, :estoque, :imagem)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome' => $_POST['nome'], ':descricao' => $_POST['descricao'], ':tamanho' => $_POST['tamanho'], ':cor' => $_POST['cor'],
            ':custo' => $custo, ':preco' => $preco, ':estoque' => $estoque, ':imagem' => $caminho_imagem
        ]);

        $msg_sucesso = "Produto cadastrado com sucesso!";
        header("Location: produtos_listar.php?msg=" . urlencode($msg_sucesso) . "&type=success");
        exit;

    } catch (Exception $e) {
        $toast_msg = "Erro: " . $e->getMessage();
        $toast_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Novo Produto - COND</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-50 font-sans text-gray-900">

    <?php include 'menu.php'; ?>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($toast_msg)): ?>
        <div id='auto-toast' class='fixed top-5 right-5 z-[100] p-4 rounded-lg shadow-lg font-bold w-full max-w-sm transition-all duration-500 border bg-red-100 border-red-200 text-red-800' role='alert'>
            <div class='flex items-center'>
                <span class='text-xl mr-3'><i class="bi bi-exclamation-triangle-fill"></i></span>
                <span class='flex-grow text-sm'><?= $toast_msg ?></span>
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
                    <h1 class="text-2xl font-bold text-gray-800">Cadastrar Produto</h1>
                    <p class="text-sm text-gray-500">Adicione novos itens ao seu catálogo.</p>
                </div>
                <a href="produtos_listar.php" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-roxo-base transition-colors">
                    <i class="bi bi-arrow-left mr-2"></i> Voltar para Estoque
                </a>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 md:p-8 h-full">
                        <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-3 text-sm">1</div>
                            Detalhes da Peça
                        </h3>
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nome da Peça *</label>
                                <input class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" 
                                       name="nome" type="text" placeholder="Ex: Vestido Longo Florido" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                                <textarea class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" 
                                          name="descricao" rows="3"></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tamanho</label>
                                    <select class="w-full border border-gray-300 rounded-lg px-4 py-2 bg-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" name="tamanho">
                                        <option value="P">P</option>
                                        <option value="M">M</option>
                                        <option value="G">G</option>
                                        <option value="GG">GG</option>
                                        <option value="UNICO">Único</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cor</label>
                                    <input class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" name="cor" type="text">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Foto do Produto</label>
                                <div class="flex items-center justify-center w-full">
                                    <label class="flex flex-col w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 hover:border-roxo-base transition-colors">
                                        <div class="flex flex-col items-center justify-center pt-7">
                                            <i class="bi bi-cloud-upload text-3xl text-gray-400 mb-2"></i>
                                            <p class="text-sm text-gray-500">Clique para enviar imagem</p>
                                        </div>
                                        <input type="file" name="foto" accept="image/*" class="opacity-0" />
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 md:p-8 h-full flex flex-col">
                        <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center">
                            <div class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center mr-3 text-sm">2</div>
                            Financeiro & Estoque
                        </h3>
                        
                        <div class="space-y-6 flex-grow">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Estoque Inicial (Entrada) *</label>
                                <input class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" 
                                       name="estoque" type="number" min="1" value="1" required>
                            </div>

                            <hr class="border-gray-100">

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Preço de Custo (R$)</label>
                                    <input class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all" 
                                           id="custo" name="custo" type="text" placeholder="0,00" 
                                           oninput="calcularLucro()">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Preço de Venda (R$)</label>
                                    <input class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all font-bold text-green-700" 
                                           id="venda" name="preco" type="text" placeholder="0,00" required 
                                           oninput="calcularLucro()">
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 grid grid-cols-2 gap-4 text-center">
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase">Lucro Estimado</p>
                                    <input class="w-full bg-transparent text-center font-bold text-lg border-none p-0 focus:ring-0 text-gray-800" 
                                           id="lucro_rs" type="text" readonly value="R$ 0,00">
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-gray-400 uppercase">Margem %</p>
                                    <input class="w-full bg-transparent text-center font-bold text-lg border-none p-0 focus:ring-0 text-gray-800" 
                                           id="margem_pct" type="text" readonly value="0%">
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-100">
                            <button class="w-full bg-roxo-base hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-lg shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5" 
                                    type="submit">
                                Salvar Produto
                            </button>
                        </div>
                    </div>

                </div>
            </form>
        </main>
    </div>

    <script>
        function calcularLucro() {
            let custo = document.getElementById('custo').value.replace(',', '.');
            let venda = document.getElementById('venda').value.replace(',', '.');
            custo = parseFloat(custo) || 0;
            venda = parseFloat(venda) || 0;
            let lucro = venda - custo;
            let margem = 0;
            if (venda > 0) { margem = (lucro / venda) * 100; }
            
            const lucroEl = document.getElementById('lucro_rs');
            lucroEl.value = 'R$ ' + lucro.toFixed(2).replace('.', ',');
            
            if (lucro < 0) {
                lucroEl.classList.add('text-red-600');
                lucroEl.classList.remove('text-green-600');
            } else {
                lucroEl.classList.add('text-green-600');
                lucroEl.classList.remove('text-red-600');
            }
            document.getElementById('margem_pct').value = margem.toFixed(1).replace('.', ',') + '%';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.getElementById('auto-toast');
            if (toast) {
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 500); 
                }, 4000);
            }
        });
    </script>
    <?php include 'toast_handler.php'; ?>
</body>
</html>