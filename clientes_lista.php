<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

// --- SQL (Carrega todos os clientes) ---
try {
    $sql = "SELECT 
                c.id, c.nome, c.cpf, c.telefone, c.email,
                (SELECT COUNT(*) FROM condicionais WHERE cliente_id = c.id) as total_historico,
                (SELECT COUNT(*) FROM condicionais WHERE cliente_id = c.id AND status IN ('ABERTO', 'ATRASADO')) as em_aberto
            FROM clientes c
            ORDER BY c.nome ASC";
    $stmt = $pdo->query($sql);
    $clientes = $stmt->fetchAll();
    $total_itens = count($clientes);
} catch (PDOException $e) {
    die("Erro ao listar clientes: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meus Clientes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        @media (max-width: 767px) {
            .tabela-responsiva thead {
                display: none;
            }
            .tabela-responsiva, .tabela-responsiva tbody, .tabela-responsiva tr {
                display: block;
                width: 100%;
            }
            .tabela-responsiva tr {
                margin-bottom: 1rem;
                border: 1px solid #ddd;
                border-radius: 0.5rem;
                overflow: hidden;
                background: #fff;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            .tabela-responsiva td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.75rem 1rem;
                border-bottom: 1px solid #eee;
                text-align: right;
                width: 100%;
            }
            .tabela-responsiva td::before {
                content: attr(data-label);
                font-weight: bold;
                text-align: left;
                padding-right: 1rem;
                color: #555;
                flex-shrink: 0;
            }
            .tabela-responsiva tr td:last-child {
                border-bottom: 0;
            }
            /* Célula de Ação */
            .tabela-responsiva td.celula-acao {
                display: block;
            }
            .tabela-responsiva td.celula-acao::before {
                display: none; /* Esconde o label "Ação:" */
            }
            .tabela-responsiva td.celula-acao > div {
                justify-content: center;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            /* Célula Cliente (Avatar + Nome) */
            .tabela-responsiva td.celula-cliente {
                display: block;
                text-align: left;
            }
            .tabela-responsiva td.celula-cliente::before {
                display: none; /* Esconde o label "Cliente:" */
            }
        }
    </style>
</head>
<body class="bg-gray-100">

    <?php include 'menu.php'; ?>

    <div class="container mx-auto px-4 mt-8 mb-20">
        
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Carteira de Clientes (<?= $total_itens ?>)</h2>
            <a href="clientes_criar.php" class="bg-roxo-base hover:bg-purple-700 text-white px-4 py-2 rounded shadow font-bold transition">
                <i class="bi bi-person-plus-fill mr-2"></i> Novo Cliente
            </a>
        </div>

        <div class="mb-6">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="bi bi-search text-gray-400"></i>
                </div>
                <input type="text" 
                       id="filtroBusca" 
                       onkeyup="filtrarTabela()" 
                       placeholder="Filtrar por nome ou CPF..." 
                       class="w-full border rounded-lg py-2 px-4 pl-10 focus:outline-none focus:border-roxo-base">
            </div>
        </div>

        <div class="bg-white md:shadow-md md:rounded-lg overflow-hidden overflow-x-auto tabela-responsiva">
            <table class="min-w-full leading-normal">
                <thead class="hidden md:table-header-group">
                    <tr class="bg-gray-100 text-gray-600 uppercase text-sm">
                        <th class="py-3 px-6 text-left" colspan="2">Cliente</th>
                        <th class="py-3 px-6 text-left">Contato</th>
                        <th class="py-3 px-6 text-center">Histórico</th>
                        <th class="py-3 px-6 text-center">Situação Atual</th>
                        <th class="py-3 px-6 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="md:bg-white" id="corpoTabelaClientes">
                    <?php if (count($clientes) > 0): ?>
                        <?php foreach ($clientes as $cli): ?>
                            <tr class="block md:table-row hover:bg-gray-50 border-b border-gray-200 md:border-b-0" 
                                data-nome="<?= strtolower(htmlspecialchars($cli['nome'])) ?>" 
                                data-cpf="<?= htmlspecialchars($cli['cpf']) ?>">
                                
                                <td class="px-5 py-4 md:table-cell md:w-16 celula-cliente">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-roxo-base font-bold uppercase">
                                            <?= substr($cli['nome'], 0, 2) ?>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-gray-900 font-bold whitespace-normal"><?= htmlspecialchars($cli['nome']) ?></p>
                                            <p class="text-gray-500 text-xs">CPF: <?= $cli['cpf'] ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden md:table-cell"></td>

                                <td data-label="Contato" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell">
                                    <div class="text-right md:text-left">
                                        <p class="text-gray-900"><i class="bi bi-telephone-fill mr-1 text-xs"></i> <?= $cli['telefone'] ?: '-' ?></p>
                                        <p class="text-gray-500 text-xs"><i class="bi bi-envelope-fill mr-1 text-xs"></i> <?= $cli['email'] ?: '-' ?></p>
                                    </div>
                                </td>
                                <td data-label="Histórico" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-center">
                                    <div>
                                        <span class="text-gray-700 font-bold"><?= $cli['total_historico'] ?></span>
                                        <span class="text-gray-500 text-xs block md:inline">sacolas</span>
                                    </div>
                                </td>
                                <td data-label="Situação" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-center">
                                    <?php if ($cli['em_aberto'] > 0): ?>
                                        <span class="bg-orange-100 text-orange-800 py-1 px-3 rounded-full text-xs font-bold border border-orange-200">
                                            <i class="bi bi-exclamation-triangle-fill"></i> <?= $cli['em_aberto'] ?> em aberto
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-green-100 text-green-800 py-1 px-3 rounded-full text-xs font-bold border border-green-200">
                                            <i class="bi bi-check-circle-fill"></i> Limpo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Ação" class="px-5 py-3 md:py-4 md:px-6 text-sm md:table-cell md:text-center celula-acao">
                                    <div class="flex justify-center items-center space-x-2">
                                        <a href="condicionais_criar.php?cliente_id=<?= $cli['id'] ?>" class="text-roxo-base hover:text-purple-900 bg-purple-100 hover:bg-purple-200 p-2 rounded-lg text-xs font-bold" title="Nova Sacola"><i class="bi bi-bag-plus-fill"></i></a>
                                        <a href="clientes_editar.php?id=<?= $cli['id'] ?>" class="text-amber-600 hover:text-amber-900 bg-amber-100 hover:bg-amber-200 p-2 rounded-lg text-xs font-bold" title="Editar Dados"><i class="bi bi-pencil-fill"></i></a>
                                        <?php if ($cli['total_historico'] == 0): ?>
                                            <a href="clientes_excluir.php?id=<?= $cli['id'] ?>" onclick="return confirm('Tem certeza?');" class="text-red-600 hover:text-red-900 bg-red-100 hover:bg-red-200 p-2 rounded-lg text-xs font-bold" title="Excluir Cliente"><i class="bi bi-trash-fill"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-10 text-gray-500">Nenhum cliente cadastrado.</td>
                        </tr>
                    <?php endif; ?>
                    
                    <tr id="linhaSemResultados" class="hidden">
                         <td colspan="6" class="text-center py-10 text-gray-500">Nenhum cliente encontrado.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
    </div>

    <script>
        function filtrarTabela() {
            const input = document.getElementById('filtroBusca');
            const tabela = document.getElementById('corpoTabelaClientes');
            const linhas = tabela.getElementsByTagName('tr');
            const linhaSemResultados = document.getElementById('linhaSemResultados');
            const termoBusca = input.value.toLowerCase();
            let resultadosEncontrados = 0;
            for (let i = 0; i < linhas.length; i++) {
                const linha = linhas[i];
                if (linha.id === 'linhaSemResultados') continue;
                const nomeCliente = linha.dataset.nome;
                const cpfCliente = linha.dataset.cpf;
                if (nomeCliente.includes(termoBusca) || cpfCliente.includes(termoBusca)) {
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
    </script>

    <?php include 'toast_handler.php'; ?>
</body>
</html>