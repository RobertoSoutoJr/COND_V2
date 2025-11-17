<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

$titulo_pagina = "Dashboard"; 

// --- 4. KPIs (Lógica inalterada) ---
$data_30_dias = date('Y-m-d', strtotime('-30 days'));
try {
    // Lucro 30d
    $stmt_lucro = $pdo->prepare("SELECT SUM((i.preco_momento * i.quantidade) - (p.preco_custo * i.quantidade)) FROM itens_condicional i JOIN produtos p ON i.produto_id = p.id JOIN condicionais c ON i.condicional_id = c.id WHERE i.status_item = 'VENDIDO' AND c.data_finalizacao >= ?");
    $stmt_lucro->execute([$data_30_dias]);
    $lucro_mes = $stmt_lucro->fetchColumn();
    
    // Valor na Rua
    $valor_rua = $pdo->query("SELECT SUM(i.preco_momento * i.quantidade) FROM itens_condicional i JOIN condicionais c ON i.condicional_id = c.id WHERE c.status IN ('ABERTO', 'ATRASADO') AND i.status_item = 'EM_CONDICIONAL'")->fetchColumn();
    
    // Top Peça
    $stmt_top_peca = $pdo->prepare("SELECT p.nome, SUM(i.quantidade) as total_vendido FROM itens_condicional i JOIN produtos p ON i.produto_id = p.id JOIN condicionais c ON i.condicional_id = c.id WHERE i.status_item = 'VENDIDO' AND c.data_finalizacao >= ? GROUP BY p.id, p.nome ORDER BY total_vendido DESC LIMIT 1");
    $stmt_top_peca->execute([$data_30_dias]);
    $top_peca = $stmt_top_peca->fetch();
    
    // Top Cliente
    $stmt_top_cliente = $pdo->prepare("SELECT cl.nome, SUM(i.preco_momento * i.quantidade) as total_comprado FROM itens_condicional i JOIN condicionais c ON i.condicional_id = c.id JOIN clientes cl ON c.cliente_id = cl.id WHERE i.status_item = 'VENDIDO' AND c.data_finalizacao >= ? GROUP BY cl.id, cl.nome ORDER BY total_comprado DESC LIMIT 1");
    $stmt_top_cliente->execute([$data_30_dias]);
    $top_cliente = $stmt_top_cliente->fetch();
    
    // KPIs Operacionais
    $total_abertos = $pdo->query("SELECT COUNT(*) FROM condicionais WHERE status = 'ABERTO'")->fetchColumn();
    $total_atrasados = $pdo->query("SELECT COUNT(*) FROM condicionais WHERE status = 'ABERTO' AND data_prevista_retorno < CURDATE()")->fetchColumn();
    $pecas_fora = $pdo->query("SELECT SUM(quantidade) FROM itens_condicional WHERE status_item = 'EM_CONDICIONAL'")->fetchColumn();

} catch (PDOException $e) { 
    die("Erro ao carregar Dashboard: " . $e->getMessage()); 
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Loja Condicional</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">

    <?php 
    include 'menu.php'; 
    ?>

    <div class="container mx-auto mt-10 px-4">
        
        <div class="mb-8 flex items-center">
            <img src="<?= $caminho_foto_perfil ?>" alt="Foto Perfil" class="h-16 w-16 md:h-20 md:w-20 rounded-full object-cover mr-5 border-4 border-roxo-base shadow">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
                <p class="text-gray-600 text-lg">
                    Bem-vindo(a) de volta, <strong class="text-roxo-base"><?= htmlspecialchars($usuario_nome) ?></strong>!
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <button id="abrirModalLucro" class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500 text-left transition transform hover:scale-105 hover:shadow-lg">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="bi bi-graph-up-arrow text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-bold uppercase">
                            Lucro 
                            <span class="text-roxo-base font-extrabold">(Ver Mais)</span>
                        </p> 
                        <p class="text-3xl font-bold text-gray-800">R$ <?= number_format($lucro_mes ?: 0, 2, ',', '.') ?></p>
                    </div>
                </div>
            </button>

            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-amber-500">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-amber-100 text-amber-600 mr-4">
                        <i class="bi bi-truck text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-bold uppercase">Valor na Rua</p>
                        <p class="text-3xl font-bold text-gray-800">R$ <?= number_format($valor_rua ?: 0, 2, ',', '.') ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="bi bi-star-fill text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-bold uppercase">Top Peça (30d)</p>
                        <p class="text-xl font-bold text-gray-800 truncate" title="<?= $top_peca['nome'] ?? 'N/A' ?>">
                            <?= $top_peca['nome'] ?? 'N/A' ?>
                        </p>
                        <p class="text-sm text-gray-500"><?= $top_peca['total_vendido'] ?? '0' ?> un.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-roxo-base">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-roxo-base mr-4">
                        <i class="bi bi-person-check-fill text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm font-bold uppercase">Top Cliente (30d)</p>
                        <p class="text-xl font-bold text-gray-800 truncate" title="<?= $top_cliente['nome'] ?? 'N/A' ?>">
                            <?= $top_cliente['nome'] ?? 'N/A' ?>
                        </p>
                        <p class="text-sm text-gray-500">R$ <?= number_format($top_cliente['total_comprado'] ?? 0, 2, ',', '.') ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <h2 class="text-xl font-bold text-gray-800 mb-4">Situação Operacional</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-white rounded-lg shadow p-5">
                <p class="text-gray-500 text-sm font-bold uppercase">Sacolas Abertas (Total)</p>
                <p class="text-4xl font-bold text-gray-800 mt-2"><?= $total_abertos ?></p>
            </div>
            <button id="abrirModalAtrasados" class="bg-white rounded-lg shadow p-5 text-left transition transform hover:scale-105 hover:shadow-lg <?= $total_atrasados > 0 ? 'border-2 border-red-500' : '' ?>">
                <p class="text-gray-500 text-sm font-bold uppercase">Sacolas Atrasadas</p>
                <p class="text-4xl font-bold mt-2 <?= $total_atrasados > 0 ? 'text-red-600 animate-pulse' : 'text-gray-800' ?>">
                    <i class="bi bi-exclamation-triangle-fill mr-2"></i> <?= $total_atrasados ?>
                </p>
            </button>
            <div class="bg-white rounded-lg shadow p-5">
                <p class="text-gray-500 text-sm font-bold uppercase">Peças na Rua</p>
                <p class="text-4xl font-bold text-gray-800 mt-2"><?= $pecas_fora ?: 0 ?></p>
            </div>
        </div>

        <h2 class="text-xl font-bold text-gray-800 mb-4">Ações Rápidas</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-20">
            <a href="condicionais_criar.php" class="bg-green-600 hover:bg-green-700 text-white p-6 rounded-lg shadow text-center"><span class="block text-2xl mb-2"><i class="bi bi-bag-plus-fill"></i></span><span class="font-bold">Nova Sacola</span></a>
            <a href="produtos_listar.php?abrirModal=true" class="bg-roxo-base hover:bg-purple-700 text-white p-6 rounded-lg shadow text-center"><span class="block text-2xl mb-2"><i class="bi bi-box-seam-fill"></i></span><span class="font-bold">Cadastrar Produto</span></a>
            <a href="clientes_lista.php" class="bg-blue-600 hover:bg-blue-700 text-white p-6 rounded-lg shadow text-center"><span class="block text-2xl mb-2"><i class="bi bi-person-plus-fill"></i></span><span class="font-bold">Novo Cliente</span></a>
            <a href="condicionais_lista.php" class="bg-gray-700 hover:bg-gray-800 text-white p-6 rounded-lg shadow text-center"><span class="block text-2xl mb-2"><i class="bi bi-list-task"></i></span><span class="font-bold">Ver Condicionais</span></a>
        </div>
    </div>

    <div id="modalLucro" class="fixed inset-0 bg-black bg-opacity-70 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
            <div class="bg-gray-100 p-4 flex justify-between items-center sticky top-0 z-10 border-b">
                <h3 class="text-gray-800 font-bold text-lg"><i class="bi bi-graph-up-arrow text-green-600 mr-2"></i>Linha do Tempo de Lucro</h3>
                <button id="fecharModalLucro" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap items-end space-x-4 mb-4">
                    <div><label for="dataInicio" class="block text-sm font-medium text-gray-700">Data Início:</label><input type="date" id="dataInicio" class="border rounded px-3 py-2 mt-1" value="<?= $data_30_dias ?>"></div>
                    <div><label for="dataFim" class="block text-sm font-medium text-gray-700">Data Fim:</label><input type="date" id="dataFim" class="border rounded px-3 py-2 mt-1" value="<?= date('Y-m-d') ?>"></div>
                    <button id="filtrarDatas" class="bg-roxo-base text-white px-4 py-2 rounded shadow hover:bg-purple-700"><i class="bi bi-search mr-1"></i> Filtrar</button>
                </div>
                <div class="relative w-full h-96"><canvas id="graficoLucro"></canvas></div>
            </div>
        </div>
    </div>

    <div id="modalAtrasados" class="fixed inset-0 bg-black bg-opacity-70 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
            <div class="bg-gray-100 p-4 flex justify-between items-center sticky top-0 z-10 border-b">
                <h3 class="text-gray-800 font-bold text-lg"><i class="bi bi-exclamation-triangle-fill text-red-600 mr-2"></i>Sacolas em Aberto</h3>
                <button id="fecharModalAtrasados" class="text-gray-500 hover:text-gray-800 text-2xl">&times;</button>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase">Data Retorno</th>
                                <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="py-2 px-3 text-center text-xs font-medium text-gray-500 uppercase">Ação</th>
                            </tr>
                        </thead>
                        <tbody id="listaSacolasAtrasadas" class="divide-y divide-gray-200">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        let meuGraficoLucro;
        const modalLucro = document.getElementById('modalLucro');
        const btnAbrirLucro = document.getElementById('abrirModalLucro');
        const btnFecharLucro = document.getElementById('fecharModalLucro');
        const btnFiltrarLucro = document.getElementById('filtrarDatas');
        const ctxLucro = document.getElementById('graficoLucro').getContext('2d');

        async function carregarGrafico() {
            const dataInicio = document.getElementById('dataInicio').value;
            const dataFim = document.getElementById('dataFim').value;
            ctxLucro.clearRect(0, 0, ctxLucro.canvas.width, ctxLucro.canvas.height);
            ctxLucro.fillText("Carregando dados...", ctxLucro.canvas.width / 2, ctxLucro.canvas.height / 2);
            try {
                const response = await fetch(`api_lucro_diario.php?inicio=${dataInicio}&fim=${dataFim}`);
                const dadosAPI = await response.json();
                const labels = dadosAPI.map(item => item.dia);
                const lucros = dadosAPI.map(item => item.lucro_dia);
                if (meuGraficoLucro) meuGraficoLucro.destroy();
                meuGraficoLucro = new Chart(ctxLucro, {
                    type: 'line', data: { labels: labels, datasets: [{ label: 'Lucro Diário', data: lucros, borderColor: '#6753d8', backgroundColor: 'rgba(103, 83, 216, 0.1)', fill: true, tension: 0.1 }] },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { callback: (value) => 'R$ ' + value.toFixed(2) } } } }
                });
            } catch (error) { console.error("Erro gráfico:", error); }
        }
        btnAbrirLucro.addEventListener('click', () => { modalLucro.classList.remove('hidden'); carregarGrafico(); });
        btnFecharLucro.addEventListener('click', () => { modalLucro.classList.add('hidden'); if (meuGraficoLucro) meuGraficoLucro.destroy(); });
        btnFiltrarLucro.addEventListener('click', carregarGrafico);
        modalLucro.addEventListener('click', (e) => { if (e.target === modalLucro) btnFecharLucro.click(); });

        // --- CÓDIGO DO MODAL DE SACOLAS ATRASADAS ---
        const modalAtrasados = document.getElementById('modalAtrasados');
        const btnAbrirAtrasados = document.getElementById('abrirModalAtrasados');
        const btnFecharAtrasados = document.getElementById('fecharModalAtrasados');
        const corpoTabelaAtrasados = document.getElementById('listaSacolasAtrasadas');
        function formatarData(dataISO) {
            const dataApenas = dataISO.split(' ')[0];
            const [ano, mes, dia] = dataApenas.split('-');
            return `${dia}/${mes}/${ano}`;
        }
        async function carregarSacolasAtrasadas() {
            corpoTabelaAtrasados.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-gray-500">Carregando...</td></tr>';
            try {
                const response = await fetch('api_sacolas_abertas.php');
                if (!response.ok) throw new Error('Falha ao buscar dados');
                const sacolas = await response.json();
                if (sacolas.length === 0) {
                    corpoTabelaAtrasados.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-gray-500">Nenhuma sacola em aberto!</td></tr>';
                    return;
                }
                corpoTabelaAtrasados.innerHTML = '';
                sacolas.forEach(sacola => {
                    const isAtrasado = sacola.status_real === 'ATRASADO';
                    const statusClass = isAtrasado ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800';
                    const linhaHTML = `
                        <tr class="${isAtrasado ? 'bg-red-50' : 'bg-white'}">
                            <td class="py-3 px-3 text-sm font-medium text-gray-900">#${sacola.id}</td>
                            <td class="py-3 px-3 text-sm text-gray-700">${sacola.cliente_nome}</td>
                            <td class="py-3 px-3 text-sm text-gray-700">${formatarData(sacola.data_prevista_retorno)}</td>
                            <td class="py-3 px-3 text-xs">
                                <span class="${statusClass} font-bold py-1 px-2 rounded-full">
                                    ${sacola.status_real}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-center">
                                <a href="condicionais_baixar.php?id=${sacola.id}" class="bg-roxo-base text-white py-1 px-3 rounded text-xs font-bold hover:bg-purple-700">
                                    Resolver
                                </a>
                            </td>
                        </tr>
                    `;
                    corpoTabelaAtrasados.innerHTML += linhaHTML;
                });
            } catch (error) {
                console.error("Erro ao buscar sacolas:", error);
                corpoTabelaAtrasados.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-red-500">Erro ao carregar dados.</td></tr>';
            }
        }
        btnAbrirAtrasados.addEventListener('click', () => { modalAtrasados.classList.remove('hidden'); carregarSacolasAtrasadas(); });
        btnFecharAtrasados.addEventListener('click', () => { modalAtrasados.classList.add('hidden'); corpoTabelaAtrasados.innerHTML = ''; });
        modalAtrasados.addEventListener('click', (e) => { if (e.target === modalAtrasados) btnFecharAtrasados.click(); });
    </script>
</body>
</html>