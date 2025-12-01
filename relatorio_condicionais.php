<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';

$titulo_pagina = "Relatório de Sacolas";

// --- CARREGAR CLIENTES ---
$clientes = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome ASC")->fetchAll();

// --- FILTROS ---
$data_inicio = $_GET['inicio'] ?? date('Y-m-01');
$data_fim = $_GET['fim'] ?? date('Y-m-t');
$cliente_id = $_GET['cliente_id'] ?? '';
$status_filtro = $_GET['status'] ?? 'TODOS';

try {
    // 1. BUSCA AS SACOLAS
    $sql = "SELECT 
                c.id, c.data_saida, c.data_prevista_retorno, c.status,
                cl.nome as cliente_nome, cl.telefone, cl.cpf,
                e.logradouro, e.numero, e.bairro, e.cidade, e.estado,
                (SELECT SUM(preco_momento * quantidade) FROM itens_condicional WHERE condicional_id = c.id) as total_valor
            FROM condicionais c
            JOIN clientes cl ON c.cliente_id = cl.id
            LEFT JOIN enderecos e ON cl.id = e.cliente_id
            WHERE c.data_saida BETWEEN :inicio AND :fim";
    
    $params = [':inicio' => $data_inicio . ' 00:00:00', ':fim' => $data_fim . ' 23:59:59'];

    if (!empty($cliente_id)) {
        $sql .= " AND c.cliente_id = :cliente_id";
        $params[':cliente_id'] = $cliente_id;
    }

    if ($status_filtro !== 'TODOS') {
        if ($status_filtro === 'FINALIZADO') { $sql .= " AND c.status = 'FINALIZADO'"; }
        elseif ($status_filtro === 'ATRASADO') { $sql .= " AND c.status = 'ABERTO' AND c.data_prevista_retorno < CURDATE()"; }
        elseif ($status_filtro === 'ABERTO_EM_DIA') { $sql .= " AND c.status = 'ABERTO' AND c.data_prevista_retorno >= CURDATE()"; }
    }

    $sql .= " ORDER BY c.data_saida DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $condicionais = $stmt->fetchAll();

    // 2. BUSCA OS ITENS
    $itens_por_sacola = [];
    if (count($condicionais) > 0) {
        $ids = array_column($condicionais, 'id');
        $ids_str = implode(',', $ids);
        
        $sql_itens = "SELECT i.condicional_id, p.nome, i.quantidade 
                      FROM itens_condicional i 
                      JOIN produtos p ON i.produto_id = p.id 
                      WHERE i.condicional_id IN ($ids_str)";
        $res_itens = $pdo->query($sql_itens)->fetchAll();

        foreach ($res_itens as $item) {
            $itens_por_sacola[$item['condicional_id']][] = $item['quantidade'] . "x " . $item['nome'];
        }
    }

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}

// TOTAIS
$total_registros = count($condicionais);
$soma_valor = 0;
foreach ($condicionais as $c) {
    $soma_valor += $c['total_valor'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Sacolas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* --- ESTILOS DE TELA (Mobile Cards) --- */
        @media screen {
            @media (max-width: 767px) {
                .tabela-responsiva thead { display: none; }
                .tabela-responsiva tr { display: block; margin-bottom: 1rem; border: 1px solid #ddd; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
                .tabela-responsiva td { display: flex; justify-content: space-between; padding: 0.75rem 1rem; border-bottom: 1px solid #eee; text-align: right; }
                .tabela-responsiva td::before { content: attr(data-label); font-weight: bold; text-align: left; color: #555; }
            }
        }

        /* --- ESTILOS DE IMPRESSÃO (A4 Blocos) --- */
        @media print {
            @page { margin: 1cm; size: A4; }
            
            body { 
                background: white; 
                color: black; 
                font-family: 'Segoe UI', Arial, sans-serif;
                font-size: 10pt;
                line-height: 1.3;
            }
            
            .no-print, #sidebar, #mobile-header, .filtros-box, .titulo-tela, .area-tabela-tela { display: none !important; }
            .md\:ml-64 { margin-left: 0 !important; }
            .container { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
            
            /* Cabeçalho */
            #print-header { 
                border-bottom: 2px solid #000; 
                padding-bottom: 10px;
                margin-bottom: 20px;
                display: flex !important;
                justify-content: space-between;
            }
            
            /* Blocos */
            .registro-item {
                border: 1px solid #ccc;
                border-radius: 4px;
                margin-bottom: 10px;
                padding: 8px;
                page-break-inside: avoid;
            }
            .reg-header {
                display: flex; justify-content: space-between;
                border-bottom: 1px dashed #ccc; padding-bottom: 4px; margin-bottom: 4px;
                font-weight: bold; font-size: 11pt; background-color: #f0f0f0; -webkit-print-color-adjust: exact; padding: 5px;
            }
            .reg-meta { display: flex; justify-content: space-between; font-size: 9pt; color: #444; margin-bottom: 4px; }
            .reg-itens { font-size: 9pt; font-style: italic; color: #333; margin-top: 4px; padding-top: 4px; border-top: 1px dotted #ddd; }
            .status-print { text-transform: uppercase; font-size: 8pt; border: 1px solid #000; padding: 1px 4px; margin-left: 5px; }

            #print-footer { margin-top: 20px; border-top: 2px solid #000; padding-top: 5px; text-align: right; font-weight: bold; font-size: 12pt; display: block !important; }
        }
        
        #print-header, #print-footer, .area-impressao-blocos { display: none; }
        
        @media print {
            .area-impressao-blocos { display: block !important; }
        }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-900">

    <?php include 'menu.php'; ?>

    <div class="md:ml-64 transition-all duration-300 flex flex-col min-h-screen">
        
        <div id="mobile-header" class="bg-white shadow-sm p-4 md:hidden flex justify-between items-center sticky top-0 z-30 no-print">
            <span class="font-bold text-xl text-roxo-base">COND</span>
            <button onclick="toggleSidebar()" class="text-gray-600 focus:outline-none"><i class="bi bi-list text-3xl"></i></button>
        </div>

        <main class="flex-1 p-6">
            
            <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4 no-print titulo-tela">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Relatório de Sacolas</h1>
                    <p class="text-sm text-gray-500">Análise gerencial de saídas e retorno.</p>
                </div>
                <button onclick="window.print()" class="inline-flex items-center justify-center px-5 py-2 text-sm font-medium text-white bg-roxo-base rounded-lg hover:bg-purple-700 shadow-sm transition-colors">
                    <i class="bi bi-printer-fill mr-2"></i> Imprimir Relatório
                </button>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-8 no-print filtros-box">
                <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Início</label><input type="date" name="inicio" value="<?= $data_inicio ?>" class="w-full px-3 py-2 border rounded-lg"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Fim</label><input type="date" name="fim" value="<?= $data_fim ?>" class="w-full px-3 py-2 border rounded-lg"></div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border rounded-lg bg-white">
                            <option value="TODOS" <?= $status_filtro == 'TODOS' ? 'selected' : '' ?>>Todos</option>
                            <option value="ATRASADO" <?= $status_filtro == 'ATRASADO' ? 'selected' : '' ?>>Atrasados</option>
                            <option value="ABERTO_EM_DIA" <?= $status_filtro == 'ABERTO_EM_DIA' ? 'selected' : '' ?>>Abertos</option>
                            <option value="FINALIZADO" <?= $status_filtro == 'FINALIZADO' ? 'selected' : '' ?>>Finalizados</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cliente</label>
                        <select name="cliente_id" class="w-full px-3 py-2 border rounded-lg bg-white">
                            <option value="">Todos</option>
                            <?php foreach ($clientes as $cli): ?>
                                <option value="<?= $cli['id'] ?>" <?= $cliente_id == $cli['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cli['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="bg-gray-800 text-white px-5 py-2 rounded-lg hover:bg-gray-900 h-[42px] font-medium w-full"><i class="bi bi-funnel-fill mr-1"></i> Filtrar</button>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden overflow-x-auto area-tabela-tela no-print">
                <table class="min-w-full leading-normal">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">ID</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Datas</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Cliente</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-600 uppercase">Total</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-600 uppercase">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($condicionais as $c): 
                            // --- LÓGICA DE CORES RESTAURADA ---
                            $hoje = date('Y-m-d');
                            $bg_status = 'bg-gray-100 text-gray-700';
                            $texto_status = $c['status'];
                            
                            if ($c['status'] == 'ABERTO') {
                                if ($c['data_prevista_retorno'] < $hoje) { 
                                    $bg_status = 'bg-red-50 text-red-700 border border-red-200'; 
                                    $texto_status = 'ATRASADO';
                                } else { 
                                    $bg_status = 'bg-yellow-50 text-yellow-700 border border-yellow-200'; 
                                }
                            } elseif ($c['status'] == 'FINALIZADO') {
                                $bg_status = 'bg-green-50 text-green-700 border border-green-200';
                            }
                        ?>
                            <tr class="hover:bg-gray-50">
                                <td data-label="ID" class="px-4 py-3 text-sm text-gray-500">#<?= $c['id'] ?></td>
                                <td data-label="Datas" class="px-4 py-3 text-sm text-gray-600">
                                    S: <?= date('d/m', strtotime($c['data_saida'])) ?> <br>
                                    R: <?= date('d/m', strtotime($c['data_prevista_retorno'])) ?>
                                </td>
                                <td data-label="Cliente" class="px-4 py-3 text-sm font-bold text-gray-800"><?= htmlspecialchars($c['cliente_nome']) ?></td>
                                <td data-label="Total" class="px-4 py-3 text-sm font-bold text-gray-800 text-right">R$ <?= number_format($c['total_valor'], 2, ',', '.') ?></td>
                                <td data-label="Status" class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase <?= $bg_status ?>">
                                        <?= $texto_status ?>
                                    </span>
                                </td>
                                <td data-label="Ação" class="px-4 py-3 text-center">
                                    <a href="condicionais_detalhes.php?id=<?= $c['id'] ?>" target="_blank" class="text-gray-400 hover:text-roxo-base"><i class="bi bi-eye-fill text-lg"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="print-area" class="area-impressao-blocos">
                <div id="print-header">
                    <div>
                        <h1 style="font-size: 16pt; font-weight: bold; margin: 0;">RELATÓRIO DE SACOLAS</h1>
                        <p style="margin: 2px 0 0 0;">Filtro: <?= date('d/m/y', strtotime($data_inicio)) ?> a <?= date('d/m/y', strtotime($data_fim)) ?> | Status: <?= $status_filtro ?></p>
                    </div>
                    <div style="text-align: right;">
                        <img src="img/logo.png" alt="Logo" style="height: 35px;">
                        <p style="margin: 0;">Emitido: <?= date('d/m/Y H:i') ?></p>
                    </div>
                </div>

                <?php if ($total_registros > 0): ?>
                    <?php foreach ($condicionais as $c): 
                        $itens_desta_sacola = $itens_por_sacola[$c['id']] ?? [];
                        $lista_itens_str = implode(', ', $itens_desta_sacola);
                        // Reutiliza lógica de status para o texto (sem cor)
                        $hoje = date('Y-m-d');
                        $status_txt = $c['status'];
                        if ($c['status'] == 'ABERTO' && $c['data_prevista_retorno'] < $hoje) $status_txt = 'ATRASADO';
                    ?>
                        <div class="registro-item">
                            <div class="reg-header">
                                <span>#<?= $c['id'] ?> - <?= htmlspecialchars($c['cliente_nome']) ?></span>
                                <span>
                                    R$ <?= number_format($c['total_valor'], 2, ',', '.') ?>
                                    <span class="status-print"><?= $status_txt ?></span>
                                </span>
                            </div>
                            <div class="reg-meta">
                                <span>CPF: <?= $c['cpf'] ?> | Tel: <?= $c['telefone'] ?></span>
                                <span>Saída: <?= date('d/m/y', strtotime($c['data_saida'])) ?> | Prev. Retorno: <?= date('d/m/y', strtotime($c['data_prevista_retorno'])) ?></span>
                            </div>
                            <div class="reg-meta">
                                <span>End: <?= $c['logradouro'] ?>, <?= $c['numero'] ?> - <?= $c['bairro'] ?></span>
                            </div>
                            <div class="reg-itens">
                                <strong>Itens:</strong> <?= htmlspecialchars($lista_itens_str) ?: 'Nenhum item listado.' ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; margin-top: 50px;">Nenhum registro encontrado.</p>
                <?php endif; ?>

                <div id="print-footer">
                    Total de Registros: <?= $total_registros ?> | VALOR TOTAL: R$ <?= number_format($soma_valor, 2, ',', '.') ?>
                </div>
            </div>

        </main>
    </div>

    <?php include 'toast_handler.php'; ?>
</body>
</html>