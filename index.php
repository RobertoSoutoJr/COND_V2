<?php
require_once 'conexao.php';

// Pequenas consultas para mostrar números no dashboard
$total_abertos = $pdo->query("SELECT COUNT(*) FROM condicionais WHERE status = 'ABERTO'")->fetchColumn();
$total_atrasados = $pdo->query("SELECT COUNT(*) FROM condicionais WHERE status = 'ABERTO' AND data_prevista_retorno < CURDATE()")->fetchColumn();
$pecas_fora = $pdo->query("SELECT COUNT(*) FROM itens_condicional WHERE status_item = 'EM_CONDICIONAL'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Loja Condicional</title>
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
<body class="bg-gray-100">

    <?php include 'menu.php'; ?>

    <div class="container mx-auto mt-10 px-4">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Bem-vindo(a) à Loja!</h1>
            <p class="text-gray-600">Visão geral do sistema hoje.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                        <span class="text-2xl">🛍️</span>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-bold">Condicionais Abertos</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $total_abertos ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100 text-red-500 mr-4">
                        <span class="text-2xl">⚠️</span>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-bold">Atrasados</p>
                        <p class="text-2xl font-bold text-red-600"><?= $total_atrasados ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-500 mr-4">
                        <span class="text-2xl">👗</span>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-bold">Peças com Clientes</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $pecas_fora ?></p>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="text-xl font-bold text-gray-800 mb-4">Ações Rápidas</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="condicionais_criar.php" class="bg-green-600 hover:bg-green-700 text-white p-6 rounded-lg shadow text-center transition transform hover:scale-105">
                <span class="block text-2xl mb-2">+</span>
                <span class="font-bold">Nova Sacola</span>
            </a>

            <a href="produtos_listar.php" class="bg-blue-600 hover:bg-blue-700 text-white p-6 rounded-lg shadow text-center transition transform hover:scale-105">
                <span class="block text-2xl mb-2">📦</span>
                <span class="font-bold">Cadastrar Produto</span>
            </a>

            <a href="clientes_criar.php" class="bg-purple-600 hover:bg-purple-700 text-white p-6 rounded-lg shadow text-center transition transform hover:scale-105">
                <span class="block text-2xl mb-2">👤</span>
                <span class="font-bold">Novo Cliente</span>
            </a>

             <a href="condicionais_lista.php" class="bg-gray-700 hover:bg-gray-800 text-white p-6 rounded-lg shadow text-center transition transform hover:scale-105">
                <span class="block text-2xl mb-2">📋</span>
                <span class="font-bold">Ver Lista Completa</span>
            </a>
        </div>
    </div>
</body>
</html>