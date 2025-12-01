<?php
require_once 'auth_check.php';
require_once 'conexao.php';

// Título da Página
$titulo_pagina = "Contas a Receber";
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Contas a Receber - COND</title>
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

        <main class="flex-1 p-6 flex flex-col">
            
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Contas a Receber</h1>
                <p class="text-sm text-gray-500">Gestão financeira de recebimentos futuros.</p>
            </div>

            <div class="flex-1 flex items-center justify-center min-h-[400px] bg-white rounded-xl shadow-sm border border-gray-100 border-dashed">
                <div class="text-center p-8 max-w-md">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-purple-50 text-roxo-base mb-6">
                        <i class="bi bi-cone-striped text-4xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800 mb-2">Módulo em Desenvolvimento</h2>
                    <p class="text-gray-500 mb-8">
                        Em breve, você poderá gerenciar todas as suas contas a receber, faturas e parcelas de clientes diretamente por esta tela.
                    </p>
                    <a href="index.php" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white transition-colors bg-roxo-base rounded-lg hover:bg-purple-700 shadow-md">
                        <i class="bi bi-house-door-fill mr-2"></i> Voltar ao Dashboard
                    </a>
                </div>
            </div>

        </main>
    </div>

    <?php include 'toast_handler.php'; ?>
</body>
</html>