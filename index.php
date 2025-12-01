<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

$titulo_pagina = "Dashboard"; 

// --- DATAS ---
$data_30_dias = date('Y-m-d', strtotime('-30 days'));
$hoje = date('Y-m-d');
$daqui_7_dias = date('Y-m-d', strtotime('+7 days'));

try {
    // =================================================
    // 1. KPIs DE VENDAS (JÁ EXISTIAM)
    // =================================================
    
    // Lucro 30d (Regime de Caixa)
    $stmt_lucro = $pdo->prepare("SELECT SUM((i.preco_momento * i.quantidade) - (p.preco_custo * i.quantidade)) FROM itens_condicional i JOIN produtos p ON i.produto_id = p.id JOIN condicionais c ON i.condicional_id = c.id WHERE i.status_item = 'VENDIDO' AND c.data_finalizacao >= ?");
    $stmt_lucro->execute([$data_30_dias]);
    $lucro_mes = $stmt_lucro->fetchColumn();
    
    // Valor na Rua (Potencial de Venda)
    $valor_rua = $pdo->query("SELECT SUM(i.preco_momento * i.quantidade) FROM itens_condicional i JOIN condicionais c ON i.condicional_id = c.id WHERE c.status IN ('ABERTO', 'ATRASADO') AND i.status_item = 'EM_CONDICIONAL'")->fetchColumn();
    
    // Top Peça
    $stmt_top_peca = $pdo->prepare("SELECT p.nome, SUM(i.quantidade) as total_vendido FROM itens_condicional i JOIN produtos p ON i.produto_id = p.id JOIN condicionais c ON i.condicional_id = c.id WHERE i.status_item = 'VENDIDO' AND c.data_finalizacao >= ? GROUP BY p.id, p.nome ORDER BY total_vendido DESC LIMIT 1");
    $stmt_top_peca->execute([$data_30_dias]);
    $top_peca = $stmt_top_peca->fetch();
    
    // Top Cliente
    $stmt_top_cliente = $pdo->prepare("SELECT cl.nome, SUM(i.preco_momento * i.quantidade) as total_comprado FROM itens_condicional i JOIN condicionais c ON i.condicional_id = c.id JOIN clientes cl ON c.cliente_id = cl.id WHERE i.status_item = 'VENDIDO' AND c.data_finalizacao >= ? GROUP BY cl.id, cl.nome ORDER BY total_comprado DESC LIMIT 1");
    $stmt_top_cliente->execute([$data_30_dias]);
    $top_cliente = $stmt_top_cliente->fetch();
    
    // Operacional Vendas
    $total_abertos = $pdo->query("SELECT COUNT(*) FROM condicionais WHERE status = 'ABERTO'")->fetchColumn();
    $total_atrasados = $pdo->query("SELECT COUNT(*) FROM condicionais WHERE status = 'ABERTO' AND data_prevista_retorno < CURDATE()")->fetchColumn();
    $pecas_fora = $pdo->query("SELECT SUM(quantidade) FROM itens_condicional WHERE status_item = 'EM_CONDICIONAL'")->fetchColumn();

    // =================================================
    // 2. NOVOS KPIs FINANCEIROS (ENTRADAS / CONTAS A PAGAR)
    // =================================================

    // Contas a Pagar URGENTES (Vencidas ou Vencendo Hoje)
    $stmt_pagar_hoje = $pdo->prepare("
        SELECT SUM(valor_total) 
        FROM entradas_produto 
        WHERE status_pagamento = 'PENDENTE' 
        AND data_vencimento <= ?
    ");
    $stmt_pagar_hoje->execute([$hoje]);
    $pagar_urgente = $stmt_pagar_hoje->fetchColumn();

    // Contas a Pagar (Próximos 7 dias)
    $stmt_pagar_semana = $pdo->prepare("
        SELECT SUM(valor_total) 
        FROM entradas_produto 
        WHERE status_pagamento = 'PENDENTE' 
        AND data_vencimento > ? AND data_vencimento <= ?
    ");
    $stmt_pagar_semana->execute([$hoje, $daqui_7_dias]);
    $pagar_semana = $stmt_pagar_semana->fetchColumn();

} catch (PDOException $e) { 
    die("Erro ao carregar Dashboard: " . $e->getMessage()); 
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - COND</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">

    <?php include 'menu.php'; ?>

    <div class="md:ml-64 transition-all duration-300 flex flex-col min-h-screen">
        
        <div class="bg-white shadow-sm p-4 md:hidden flex justify-between items-center sticky top-0 z-30">
            <span class="font-bold text-xl text-roxo-base">COND</span>
            <button onclick="toggleSidebar()" class="text-gray-600 focus:outline-none">
                <i class="bi bi-list text-3xl"></i>
            </button>
        </div>

        <main class="p-6 flex-1">
            
            <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-800">Visão Geral</h1>
                    <p class="text-gray-500 mt-1">Bem-vindo de volta, <?= htmlspecialchars($_SESSION['usuario_nome']) ?> 👋</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="condicionais_criar.php" class="inline-flex items-center justify-center px-5 py-2 text-sm font-medium text-white transition-colors bg-roxo-base rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 shadow-lg">
                        <i class="bi bi-plus-lg mr-2"></i> Nova Sacola
                    </a>
                </div>
            </header>

            <h2 class="text-lg font-semibold text-gray-700 mb-4 border-l-4 border-roxo-base pl-3">Alertas Financeiros</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                
                <a href="entradas_lista.php?sort=vencimento&dir=asc" class="block bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition group">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-red-500 uppercase tracking-wide">A Pagar (Hoje/Vencido)</p>
                            <p class="text-2xl font-black text-gray-800 mt-2">R$ <?= number_format($pagar_urgente ?: 0, 2, ',', '.') ?></p>
                        </div>
                        <div class="p-3 bg-red-50 rounded-lg text-red-500 group-hover:bg-red-500 group-hover:text-white transition">
                            <i class="bi bi-exclamation-circle-fill text-xl"></i>
                        </div>
                    </div>
                </a>

                <a href="entradas_lista.php" class="block bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition group">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-yellow-600 uppercase tracking-wide">A Pagar (7 Dias)</p>
                            <p class="text-2xl font-black text-gray-800 mt-2">R$ <?= number_format($pagar_semana ?: 0, 2, ',', '.') ?></p>
                        </div>
                        <div class="p-3 bg-yellow-50 rounded-lg text-yellow-600 group-hover:bg-yellow-500 group-hover:text-white transition">
                            <i class="bi bi-calendar-week text-xl"></i>
                        </div>
                    </div>
                </a>

                <button id="abrirModalLucro" class="w-full text-left bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition group">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-green-600 uppercase tracking-wide">
                                Lucro (30d) <span class="text-roxo-base font-extrabold ml-1">(Ver Mais)</span>
                            </p>
                            <p class="text-2xl font-black text-gray-800 mt-2">R$ <?= number_format($lucro_mes ?: 0, 2, ',', '.') ?></p>
                        </div>
                        <div class="p-3 bg-green-50 rounded-lg text-green-600 group-hover:bg-green-500 group-hover:text-white transition">
                            <i class="bi bi-graph-up-arrow text-xl"></i>
                        </div>
                    </div>
                </button>
            </div>

            <h2 class="text-lg font-semibold text-gray-700 mb-4 border-l-4 border-blue-500 pl-3">Operação de Vendas</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase">Na Rua (Venda)</p>
                            <p class="text-xl font-bold text-gray-800 mt-1">R$ <?= number_format($valor_rua ?: 0, 2, ',', '.') ?></p>
                        </div>
                        <i class="bi bi-truck text-2xl text-gray-300"></i>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                     <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase">Sacolas Ativas</p>
                            <p class="text-xl font-bold text-gray-800 mt-1"><?= $total_abertos ?: 0 ?></p>
                        </div>
                        <i class="bi bi-bag text-2xl text-gray-300"></i>
                    </div>
                </div>

                <button id="abrirModalAtrasados" class="w-full text-left bg-white rounded-xl shadow-sm p-6 border border-gray-100 hover:bg-red-50 transition group">
                     <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-gray-400 group-hover:text-red-600 uppercase">Atrasados</p>
                            <p class="text-xl font-bold text-gray-800 mt-1"><?= $total_atrasados ?: 0 ?></p>
                        </div>
                        <i class="bi bi-exclamation-triangle text-2xl text-gray-300 group-hover:text-red-500 <?= $total_atrasados > 0 ? 'animate-pulse text-red-500' : '' ?>"></i>
                    </div>
                </button>

                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                     <div class="flex items-center justify-between">
                        <div class="overflow-hidden">
                            <p class="text-xs font-bold text-gray-400 uppercase">Top Peça</p>
                            <p class="text-lg font-bold text-gray-800 mt-1 truncate" title="<?= $top_peca['nome'] ?? 'N/A' ?>">
                                <?= $top_peca['nome'] ?? '-' ?>
                            </p>
                            <p class="text-xs text-gray-400 mt-1"><?= $top_peca['total_vendido'] ?? 0 ?> un. vendidas</p>
                        </div>
                        <i class="bi bi-trophy text-2xl text-yellow-400"></i>
                    </div>
                </div>
            </div>

            <h2 class="text-xl font-bold text-gray-800 mb-4">Acesso Rápido</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-20">
                <a href="condicionais_criar.php" class="bg-white border border-gray-200 hover:border-roxo-base hover:text-roxo-base text-gray-600 p-4 rounded-lg shadow-sm text-center transition group">
                    <i class="bi bi-bag-plus text-3xl mb-2 block group-hover:scale-110 transition-transform"></i>
                    <span class="font-bold text-sm">Nova Sacola</span>
                </a>
                
                <a href="produtos_listar.php?abrirModal=true" class="bg-white border border-gray-200 hover:border-roxo-base hover:text-roxo-base text-gray-600 p-4 rounded-lg shadow-sm text-center transition group">
                    <i class="bi bi-box-seam text-3xl mb-2 block group-hover:scale-110 transition-transform"></i>
                    <span class="font-bold text-sm">Novo Produto</span>
                </a>
                
                <a href="entradas_criar.php" class="bg-white border border-gray-200 hover:border-roxo-base hover:text-roxo-base text-gray-600 p-4 rounded-lg shadow-sm text-center transition group">
                    <i class="bi bi-box-arrow-in-down text-3xl mb-2 block group-hover:scale-110 transition-transform"></i>
                    <span class="font-bold text-sm">Nova Entrada</span>
                </a>
                
                <a href="clientes_criar.php" class="bg-white border border-gray-200 hover:border-roxo-base hover:text-roxo-base text-gray-600 p-4 rounded-lg shadow-sm text-center transition group">
                    <i class="bi bi-person-plus text-3xl mb-2 block group-hover:scale-110 transition-transform"></i>
                    <span class="font-bold text-sm">Novo Cliente</span>
                </a>
            </div>

        </main>
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
                        <tbody id="listaSacolasAtrasadas" class="divide-y divide-gray-200"></tbody>
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

        // Modal Atrasados
        const modalAtrasados = document.getElementById('modalAtrasados');
        const btnAbrirAtrasados = document.getElementById('abrirModalAtrasados');
        const btnFecharAtrasados = document.getElementById('fecharModalAtrasados');
        const corpoTabelaAtrasados = document.getElementById('listaSacolasAtrasadas');

        function formatarData(dataISO) {
            const [ano, mes, dia] = dataISO.split(' ')[0].split('-');
            return `${dia}/${mes}/${ano}`;
        }

        async function carregarSacolasAtrasadas() {
            corpoTabelaAtrasados.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-gray-500">Carregando...</td></tr>';
            try {
                const response = await fetch('api_sacolas_abertas.php');
                if (!response.ok) throw new Error('Falha ao buscar dados');
                const sacolas = await response.json();
                if (sacolas.length === 0) {
                    corpoTabelaAtrasados.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-gray-500">Nenhuma sacola atrasada.</td></tr>';
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
                            <td class="py-3 px-3 text-xs"><span class="${statusClass} font-bold py-1 px-2 rounded-full">${sacola.status_real}</span></td>
                            <td class="py-3 px-3 text-center"><a href="condicionais_baixar.php?id=${sacola.id}" class="bg-roxo-base text-white py-1 px-3 rounded text-xs font-bold hover:bg-purple-700">Resolver</a></td>
                        </tr>`;
                    corpoTabelaAtrasados.innerHTML += linhaHTML;
                });
            } catch (error) {
                console.error("Erro:", error);
                corpoTabelaAtrasados.innerHTML = '<tr><td colspan="5" class="text-center p-4 text-red-500">Erro ao carregar dados.</td></tr>';
            }
        }
        btnAbrirAtrasados.addEventListener('click', () => { modalAtrasados.classList.remove('hidden'); carregarSacolasAtrasadas(); });
        btnFecharAtrasados.addEventListener('click', () => { modalAtrasados.classList.add('hidden'); corpoTabelaAtrasados.innerHTML = ''; });
        modalAtrasados.addEventListener('click', (e) => { if (e.target === modalAtrasados) btnFecharAtrasados.click(); });
    </script>

    <?php include 'toast_handler.php'; ?>
</body>
</html>