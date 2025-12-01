<?php
require_once 'auth_check.php';
require_once 'conexao.php';

// Título da Página
$titulo_pagina = "Catálogo de Produtos";

try {
    // Busca todos os produtos com estoque disponível
    $sql = "SELECT id, nome, imagem, preco, estoque_loja, tamanho, cor FROM produtos WHERE estoque_loja > 0 ORDER BY nome ASC";
    $produtos = $pdo->query($sql)->fetchAll();

} catch (PDOException $e) {
    die("Erro ao buscar catálogo: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Catálogo - COND</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-50 font-sans text-gray-900">

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
                    <h1 class="text-2xl font-bold text-gray-800">Catálogo de Peças</h1>
                    <p class="text-sm text-gray-500">Visualização de produtos disponíveis (<?= count($produtos) ?> itens).</p>
                </div>
                 <a href="condicionais_lista.php" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 shadow-sm transition-colors">
                    <i class="bi bi-bag-check-fill mr-2"></i> Ver Sacolas
                </a>
            </div>

            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                
                <?php if (count($produtos) > 0): ?>
                    <?php foreach ($produtos as $p): ?>
                        
                        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 border border-gray-100 overflow-hidden flex flex-col group">
                            
                            <div class="relative pt-[100%] bg-gray-100 group-hover:opacity-95 transition-opacity">
                                
                                <?php if (!empty($p['imagem'])): ?>
                                    <img src="uploads/<?= $p['imagem'] ?>" 
                                         alt="<?= htmlspecialchars($p['nome']) ?>" 
                                         class="absolute inset-0 w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <i class="bi bi-tshirt-fill text-5xl text-gray-300"></i>
                                    </div>
                                <?php endif; ?>

                                <span class="absolute top-2 left-2 bg-green-500 bg-opacity-90 text-white text-[10px] font-bold px-2 py-1 rounded-full shadow-sm">
                                    <?= $p['estoque_loja'] ?> un
                                </span>
                            </div>
                            
                            <div class="p-3 flex flex-col flex-grow">
                                <h3 class="text-sm font-semibold text-gray-800 mb-1 leading-tight line-clamp-2" title="<?= htmlspecialchars($p['nome']) ?>">
                                    <?= htmlspecialchars($p['nome']) ?>
                                </h3>
                                
                                <div class="mt-auto pt-2">
                                    <p class="text-lg font-bold text-roxo-base">
                                        R$ <?= number_format($p['preco'], 2, ',', '.') ?>
                                    </p>
                                    <div class="flex justify-between items-center mt-1">
                                        <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                                            <?= $p['tamanho'] ?>
                                        </span>
                                        <span class="text-xs text-gray-400 truncate max-w-[60%]">
                                            <?= htmlspecialchars($p['cor']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full py-16 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                            <i class="bi bi-box2 text-3xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">Nenhum produto em estoque</h3>
                        <p class="mt-1 text-gray-500">Cadastre novos produtos ou realize entradas de estoque para vê-los aqui.</p>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <?php include 'toast_handler.php'; ?>
</body>
</html>