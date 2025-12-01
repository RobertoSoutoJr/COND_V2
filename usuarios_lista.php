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
    <title>Gestão de Usuários - COND</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        @media (max-width: 767px) {
            .tabela-responsiva thead { display: none; }
            .tabela-responsiva, .tabela-responsiva tbody, .tabela-responsiva tr { display: block; width: 100%; }
            .tabela-responsiva tr { margin-bottom: 1rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; overflow: hidden; background: #fff; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05); }
            .tabela-responsiva td { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; text-align: right; width: 100%; }
            .tabela-responsiva td::before { content: attr(data-label); font-weight: 600; text-align: left; padding-right: 1rem; color: #6b7280; flex-shrink: 0; font-size: 0.875rem; }
            .tabela-responsiva tr td:last-child { border-bottom: 0; }
            .tabela-responsiva td.celula-acao { display: block; }
            .tabela-responsiva td.celula-acao::before { display: none; }
            .tabela-responsiva td.celula-acao > div { justify-content: center; flex-wrap: wrap; gap: 0.5rem; }
            .tabela-responsiva td.celula-usuario { display: block; text-align: left; background-color: #f9fafb; border-bottom: 1px solid #e5e7eb; }
            .tabela-responsiva td.celula-usuario::before { display: none; }
        }
    </style>
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
                    <h1 class="text-2xl font-bold text-gray-800">Gestão de Usuários</h1>
                    <p class="text-sm text-gray-500">Administre quem tem acesso ao sistema.</p>
                </div>
                <a href="registrar.php" class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-medium text-white transition-colors bg-roxo-base rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 shadow-md">
                    <i class="bi bi-person-plus-fill mr-2"></i> Novo Usuário
                </a>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden overflow-x-auto tabela-responsiva">
                <table class="min-w-full leading-normal">
                    <thead class="hidden md:table-header-group bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider" colspan="2">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Login</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nível</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="md:bg-white divide-y divide-gray-100">
                        <?php foreach ($usuarios as $u): ?>
                        <tr class="block md:table-row hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-4 md:w-16 celula-usuario">
                                <div class="flex items-center">
                                    <img src="uploads/usuarios/<?= $u['foto'] ?: '../img/default_avatar.png' ?>" class="h-10 w-10 rounded-full object-cover border border-gray-200">
                                    <div class="ml-3 md:hidden">
                                        <p class="font-medium text-gray-800"><?= htmlspecialchars($u['nome']) ?></p>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($u['login']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden md:table-cell px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars($u['nome']) ?></td>

                            <td data-label="Login" class="px-6 py-4 md:table-cell text-sm text-gray-600">
                                <?= htmlspecialchars($u['login']) ?>
                            </td>
                            <td data-label="Nível" class="px-6 py-4 md:table-cell">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $u['nivel'] == 'admin' ? 'bg-purple-100 text-roxo-base' : 'bg-gray-100 text-gray-600' ?>">
                                    <?= ucfirst($u['nivel']) ?>
                                </span>
                            </td>
                            <td data-label="Ações" class="px-6 py-4 md:table-cell md:text-center celula-acao">
                                <div class="flex justify-center items-center gap-2">
                                    <a href="usuarios_editar.php?id=<?= $u['id'] ?>" class="p-2 text-amber-600 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors" title="Editar Usuário">
                                       <i class="bi bi-pencil-fill text-lg"></i>
                                    </a>
                                    <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                                        <a href="usuarios_excluir.php?id=<?= $u['id'] ?>" onclick="return confirm('Tem certeza?');" class="p-2 text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 rounded-lg transition-colors" title="Excluir Usuário">
                                           <i class="bi bi-trash-fill text-lg"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <?php include 'toast_handler.php'; ?>
</body>
</html>