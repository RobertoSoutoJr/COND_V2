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
<body class="bg-gray-100 pb-20">

    <?php include 'menu.php'; ?>

    <div class="container mx-auto px-4 mt-8">
        
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Catálogo de Peças Disponíveis</h2>
        <p class="text-gray-600 mb-6">Visualização para pedidos futuros. Total de <?= count($produtos) ?> itens em stock.</p>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            
            <?php if (count($produtos) > 0): ?>
                <?php foreach ($produtos as $p): ?>
                    
                    <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition duration-300 overflow-hidden flex flex-col">
                        
                        <div class="relative pt-[100%] bg-gray-200 flex items-center justify-center">
                            
                            <?php if (!empty($p['imagem'])): ?>
                                <img src="uploads/<?= $p['imagem'] ?>" 
                                     alt="<?= htmlspecialchars($p['nome']) ?>" 
                                     class="absolute inset-0 w-full h-full object-cover">
                            <?php else: ?>
                                <i class="bi bi-tshirt-fill text-6xl text-gray-400"></i>
                            <?php endif; ?>

                            <span class="absolute top-2 left-2 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                <?= $p['estoque_loja'] ?> un
                            </span>
                        </div>
                        
                        <div class="p-3 flex flex-col flex-grow">
                            <p class="text-sm font-semibold text-gray-800 mb-1 leading-tight"><?= htmlspecialchars($p['nome']) ?></p>
                            
                            <div class="mt-auto">
                                <p class="text-lg font-bold text-roxo-base">
                                    R$ <?= number_format($p['preco'], 2, ',', '.') ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <?= $p['tamanho'] ?> / <?= htmlspecialchars($p['cor']) ?>
                                </p>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-10 text-gray-500">
                    Nenhum produto em estoque para exibir no catálogo.
                </div>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>