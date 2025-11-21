<?php
session_start();
include 'conexao.php';
include 'toast_handler.php';

$titulo_pagina = "Contas a Receber";

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo_pagina ?> - COND V2</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .roxo-base {
            background-color: #5a32a8;
        }

        .text-roxo-base {
            color: #5a32a8;
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php include 'menu.php'; ?>

    <div class="container mx-auto p-4">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800"><?= $titulo_pagina ?></h1>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md">
            <p class="text-gray-600">Esta pagina se encontra em desenvolvimento!!</p>
        </div>
    </div>

    <?php exibe_toast(); ?>
</body>

</html>
