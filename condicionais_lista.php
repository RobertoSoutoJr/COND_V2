<?php 
require_once 'auth_check.php'; 
require_once 'conexao.php';
require_once 'whatsapp_utils.php';
$hoje = date('Y-m-d');

// ... (Todo o seu PHP de Ordenação e SQL permanece o mesmo) ...
$allowedSort = [
    'cliente' => 'cliente_nome', 'saida'   => 'data_saida', 'retorno' => 'data_prevista_retorno',
    'pecas'   => 'qtd_pecas_total', 'valor'   => 'valor_total_sacola', 'status'  => 'status'
];
$sortParam = $_GET['sort'] ?? 'saida';
$sortDir_URL = $_GET['dir'] ?? 'desc';
if (!array_key_exists($sortParam, $allowedSort)) $sortParam = 'status';
$sortDir_SQL = (strtolower($sortDir_URL) === 'desc') ? 'DESC' : 'ASC';
$sortColumnDB = $allowedSort[$sortParam];
if ($sortParam === 'status') {
    $orderBy = "FIELD(c.status, 'ABERTO', 'ATRASADO', 'FINALIZADO') $sortDir_SQL, c.data_prevista_retorno DESC";
} else {
    $orderBy = "$sortColumnDB $sortDir_SQL";
}
function getSortLink($col, $label, $currentParam, $currentDir_URL) {
    $newDir = 'asc'; $arrow = '';
    if ($col === $currentParam) {
        $newDir = ($currentDir_URL === 'asc') ? 'desc' : 'asc';
        $arrow = ($currentDir_URL === 'asc') ? ' ▲' : ' ▼';
    }
    return "<a href='?sort=$col&dir=$newDir' class='text-gray-600 hover:text-gray-900'>$label $arrow</a>";
}
try {
    $sql = "SELECT 
                c.id, c.data_saida, c.data_prevista_retorno, c.status,
                cl.nome as cliente_nome, cl.telefone,
                (SELECT SUM(quantidade) FROM itens_condicional WHERE condicional_id = c.id) as qtd_pecas_total,
                (SELECT SUM(preco_momento * quantidade) FROM itens_condicional WHERE condicional_id = c.id) as valor_total_sacola,
                (SELECT SUM(p.preco_custo * i.quantidade) FROM itens_condicional i JOIN produtos p ON i.produto_id = p.id WHERE i.condicional_id = c.id) as valor_custo_sacola
            FROM condicionais c
            JOIN clientes cl ON c.cliente_id = cl.id
            ORDER BY $orderBy";
    $stmt = $pdo->query($sql);
    $condicionais = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erro ao listar: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciar Condicionais</title>
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
            /* Adiciona o Label (título) antes do conteúdo */
            .tabela-responsiva td::before {
                content: attr(data-label);
                font-weight: bold;
                text-align: left;
                padding-right: 1rem;
                color: #555;
                /* Impede que o label encolha */
                flex-shrink: 0; 
            }
            /* Permite que o conteúdo (valor) quebre a linha */
            .tabela-responsiva td > * {
                text-align: right;
            }

            /* --- MUDANÇAS PARA A CÉLULA DE AÇÃO --- */
            
            /* 1. Tira a célula de ação do modo flex (label/valor) */
            .tabela-responsiva td.celula-acao {
                display: block;
                padding-top: 0.75rem;
                padding-bottom: 0.75rem;
            }
            /* 2. Esconde o label "Ação:" (desnecessário) */
            .tabela-responsiva td.celula-acao::before {
                display: none;
            }
            /* 3. Faz os botões quebrarem a linha se necessário */
            .tabela-responsiva td.celula-acao > div {
                justify-content: center; /* Centraliza */
                flex-wrap: wrap; /* Permite quebrar a linha */
                gap: 0.5rem; /* Adiciona espaço */
            }
        }
    </style>
</head>
<body class="bg-gray-100">

    <?php include 'menu.php'; ?>

    <div class="container mx-auto px-4 mt-8">
        
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Gerenciamento de Sacolas</h2>
            <a href="condicionais_criar.php" class="bg-roxo-base hover:bg-purple-700 text-white px-4 py-2 rounded shadow font-bold transition flex items-center">
                <i class="bi bi-plus-lg mr-2"></i> Nova Sacola
            </a>
        </div>

        <div class="bg-white md:shadow-md md:rounded-lg overflow-x-auto tabela-responsiva">
            <table class="min-w-full leading-normal">
                <thead class="hidden md:table-header-group">
                    <tr>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold uppercase tracking-wider">
                            <?= getSortLink('cliente', 'Cliente / ID', $sortParam, $sortDir_URL) ?>
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold uppercase tracking-wider">
                            <?= getSortLink('saida', 'Data Saída', $sortParam, $sortDir_URL) ?>
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold uppercase tracking-wider">
                            <?= getSortLink('retorno', 'Data Retorno', $sortParam, $sortDir_URL) ?>
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold uppercase tracking-wider">
                            <?= getSortLink('pecas', 'Peças', $sortParam, $sortDir_URL) ?>
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold uppercase tracking-wider">
                            <?= getSortLink('valor', 'Valor Sacola', $sortParam, $sortDir_URL) ?>
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold uppercase tracking-wider">
                            <?= getSortLink('status', 'Status', $sortParam, $sortDir_URL) ?>
                        </th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Ação
                        </th>
                    </tr>
                </thead>
                <tbody class="md:bg-white">
                    <?php foreach ($condicionais as $c): 
                        // ... (Lógica PHP de status e lucro inalterada) ...
                        $classeStatus = 'bg-gray-200 text-gray-700';
                        $textoStatus = $c['status'];
                        if ($c['status'] == 'ABERTO' && $c['data_prevista_retorno'] < $hoje) {
                            $classeStatus = 'bg-red-200 text-red-800 border'; $textoStatus = 'ATRASADO';
                        } elseif ($c['status'] == 'ABERTO') {
                            $classeStatus = 'bg-yellow-100 text-yellow-800 border';
                        } elseif ($c['status'] == 'FINALIZADO') {
                            $classeStatus = 'bg-green-200 text-green-800';
                        }
                        $valor_venda = $c['valor_total_sacola'] ?: 0;
                        $valor_custo = $c['valor_custo_sacola'] ?: 0;
                        $lucro_potencial = $valor_venda - $valor_custo;
                    ?>
                        <tr class="block md:table-row border-b border-gray-200 md:border-b-0 hover:bg-gray-50">
                            
                            <td data-label="Cliente" class="px-5 py-3 md:py-5 text-sm md:table-cell">
                                <div>
                                    <p class="text-gray-900 font-bold"><?= htmlspecialchars($c['cliente_nome']) ?></p>
                                    <p class="text-gray-500 text-xs">ID: #<?= $c['id'] ?></p>
                                </div>
                            </td>
                            <td data-label="Saída" class="px-5 py-3 md:py-5 text-sm md:table-cell">
                                <p><?= date('d/m/Y', strtotime($c['data_saida'])) ?></p>
                            </td>
                            <td data-label="Retorno" class="px-5 py-3 md:py-5 text-sm md:table-cell">
                                <p class="font-bold"><?= date('d/m/Y', strtotime($c['data_prevista_retorno'])) ?></p>
                            </td>
                            <td data-label="Peças" class="px-5 py-3 md:py-5 text-sm md:table-cell md:text-center">
                                <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs font-bold">
                                    <?= $c['qtd_pecas_total'] ?: 0 ?> itens
                                </span>
                            </td>
                            <td data-label="Valor Sacola" class="px-5 py-3 md:py-5 text-sm md:table-cell md:text-center">
                                <div>
                                    <span class="text-gray-800 font-bold">R$ <?= number_format($valor_venda, 2, ',', '.') ?></span>
                                    <span class="block text-gray-500 text-xs mt-1">Custo: R$ <?= number_format($valor_custo, 2, ',', '.') ?></span>
                                    <span class="block text-green-600 font-bold text-xs mt-1">Lucro: R$ <?= number_format($lucro_potencial, 2, ',', '.') ?></span>
                                </div>
                            </td>
                            <td data-label="Status" class="px-5 py-3 md:py-5 text-sm md:table-cell md:text-center">
                                <span class="relative inline-block px-3 py-1 font-semibold leading-tight <?= $classeStatus ?> rounded-full">
                                    <span class="relative"><?= $textoStatus ?></span>
                                </span>
                            </td>
                            <td data-label="Ação" class="px-5 py-3 md:py-5 text-sm md:table-cell md:text-center celula-acao">
                                <div class="flex justify-center items-center space-x-2">
                                    <?php 
                                        // Prepara os dados para a função do WhatsApp
                                        $condicional_data = [
                                            'id' => $c['id'],
                                            'status' => $c['status'],
                                            'data_prevista_retorno' => $c['data_prevista_retorno'],
                                            'cliente_nome' => $c['cliente_nome'],
                                            'telefone' => $c['telefone']
                                        ];
                                        $link_whatsapp = gerarLinkWhatsApp($pdo, $condicional_data);
                                    ?>
                                    <a href="<?= $link_whatsapp ?>" target="_blank" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-3 rounded text-xs shadow" title="Enviar WhatsApp">
                                        <i class="bi bi-whatsapp"></i>
                                    </a>
                                    <?php if($c['status'] !== 'FINALIZADO'): ?>
                                        <a href="condicionais_baixar.php?id=<?= $c['id'] ?>" class="bg-roxo-base hover:bg-purple-700 text-white font-bold py-2 px-3 rounded text-xs shadow" title="Receber/Baixar">
                                            <i class="bi bi-arrow-down-up"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="condicionais_detalhes.php?id=<?= $c['id'] ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-3 rounded text-xs" title="Ver Detalhes">
                                        <i class="bi bi-search"></i>
                                    </a>
                                    <a href="condicionais_imprimir.php?id=<?= $c['id'] ?>" target="_blank" class="bg-gray-600 hover:bg-gray-800 text-white font-bold py-2 px-3 rounded text-xs" title="Imprimir Recibo">
                                        <i class="bi bi-printer-fill"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php include 'toast_handler.php'; ?>
</body>
</html>