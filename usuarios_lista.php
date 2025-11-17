<?php 
require_once 'auth_admin_check.php'; 
require_once 'conexao.php';
$titulo_pagina = "Gestão de Usuários";
$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY nome ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Usuários</title>
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
            .tabela-responsiva td.celula-usuario { display: block; text-align: left; }
            .tabela-responsiva td.celula-usuario::before { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'menu.php'; ?>


    
    <div class="container mx-auto mt-10 px-4 mb-20">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Gestão de Usuários</h2>
            <a href="registrar.php" class="bg-roxo-base hover:bg-purple-700 text-white px-4 py-2 rounded shadow font-bold transition">
                <i class="bi bi-person-plus-fill mr-2"></i> Novo Usuário
            </a>
        </div>
        
        <div class="bg-white md:shadow-md md:rounded-lg overflow-hidden tabela-responsiva">
            <table class="min-w-full leading-normal">
                <thead class="hidden md:table-header-group">
                    <tr class="bg-gray-100 text-gray-600 uppercase text-sm">
                        <th class="py-3 px-6 text-left" colspan="2">Nome</th>
                        <th class="py-3 px-6 text-left">Login</th>
                        <th class="py-3 px-6 text-left">Nível</th>
                        <th class="py-3 px-6 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="md:bg-white">
                    <?php foreach ($usuarios as $u): ?>
                    <tr class="block md:table-row border-b border-gray-200 md:border-b-0 hover:bg-gray-50">
                        <td class="px-5 py-4 md:px-6 md:py-3 md:w-16 celula-usuario">
                            <div class="flex items-center">
                                <img src="uploads/usuarios/<?= $u['foto'] ?: '../img/default_avatar.png' ?>" class="h-10 w-10 rounded-full object-cover">
                                <div class="ml-3 md:hidden">
                                    <p class="font-medium text-gray-800"><?= htmlspecialchars($u['nome']) ?></p>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($u['login']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="hidden md:table-cell py-3 px-6 font-medium"><?= htmlspecialchars($u['nome']) ?></td>
                        <td data-label="Login" class="px-5 py-3 md:py-3 md:px-6 md:table-cell">
                            <?= htmlspecialchars($u['login']) ?>
                        </td>
                        <td data-label="Nível" class="px-5 py-3 md:py-3 md:px-6 md:table-cell">
                            <span class="py-1 px-3 rounded-full text-xs font-bold <?= $u['nivel'] == 'admin' ? 'bg-red-100 text-red-700' : 'bg-gray-200 text-gray-700' ?>">
                                <?= $u['nivel'] ?>
                            </span>
                        </td>
                        <td data-label="Ações" class="px-5 py-3 md:py-3 md:px-6 md:table-cell md:text-center celula-acao">
                            <div class="flex justify-center space-x-2">
                                <a href="usuarios_editar.php?id=<?= $u['id'] ?>" class="text-amber-600 hover:text-amber-900 bg-amber-100 hover:bg-amber-200 p-2 rounded-lg text-xs font-bold" title="Editar Usuário">
                                   <i class="bi bi-pencil-fill"></i> Editar
                                </a>
                                <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                                    <a href="usuarios_excluir.php?id=<?= $u['id'] ?>" onclick="return confirm('Tem certeza?');" class="text-red-600 hover:text-red-900 bg-red-100 hover:bg-red-200 p-2 rounded-lg text-xs font-bold" title="Excluir Usuário">
                                       <i class="bi bi-trash-fill"></i> Excluir
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>