<?php
require_once 'conexao.php';
$hoje = date('Y-m-d');

// --- LÓGICA DE ORDENAÇÃO (Mantida) ---
$allowedSort = [
    'cliente' => 'cliente_nome',
    'saida'   => 'data_saida', // Coluna nova para ordenar
    'retorno' => 'data_prevista_retorno', // Coluna nova
    'pecas'   => 'qtd_pecas_total',
    'valor'   => 'valor_total_sacola',
    'status'  => 'status'
];
$sortParam = $_GET['sort'] ?? 'status';
$sortDir_URL = $_GET['dir'] ?? 'asc';
if (!array_key_exists($sortParam, $allowedSort)) $sortParam = 'status';
$sortDir_SQL = (strtolower($sortDir_URL) === 'desc') ? 'DESC' : 'ASC';
$sortColumnDB = $allowedSort[$sortParam];

if ($sortParam === 'status') {
    $orderBy = "FIELD(c.status, 'ABERTO', 'ATRASADO', 'FINALIZADO') $sortDir_SQL, c.data_prevista_retorno DESC";
} else {
    $orderBy = "$sortColumnDB $sortDir_SQL";
}

function getSortLink($col, $label, $currentParam, $currentDir_URL) {
    $newDir = 'asc';
    $arrow = '';
    if ($col === $currentParam) {
        $newDir = ($currentDir_URL === 'asc') ? 'desc' : 'asc';
        $arrow = ($currentDir_URL === 'asc') ? ' ▲' : ' ▼';
    }
    return "<a href='?sort=$col&dir=$newDir' class='text-gray-600 hover:text-gray-900'>$label $arrow</a>";
}

// --- SQL (Mantido) ---
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

    <div class="container mx-auto px-4 mt-8">
        
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Gerenciamento de Condicionais</h2>
            <a href="condicionais_criar.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow font-bold transition flex items-center">
                <span class="text-xl mr-2">+</span> Nova Sacola
            </a>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-hidden overflow-x-auto">
            <table class="min-w-full leading-normal">
                <thead>
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
                <tbody>
                    <?php foreach ($condicionais as $c): 
                        $classeStatus = 'bg-gray-200 text-gray-700';
                        $textoStatus = $c['status'];
                        if ($c['status'] == 'ABERTO' && $c['data_prevista_retorno'] < $hoje) {
                            $classeStatus = 'bg-red-200 text-red-800 border'; $textoStatus = 'ATRASADO';
                        } elseif ($c['status'] == 'ABERTO') {
                            $classeStatus = 'bg-yellow-100 text-yellow-800 border';
                        } elseif ($c['status'] == 'FINALIZADO') {
                            $classeStatus = 'bg-green-200 text-green-800';
                        }
                    ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="px-5 py-5 text-sm">
                                <p class="text-gray-900 font-bold whitespace-no-wrap"><?= htmlspecialchars($c['cliente_nome']) ?></p>
                                <p class="text-gray-500 text-xs">ID: #<?= $c['id'] ?></p>
                            </td>
                            <td class="px-5 py-5 text-sm">
                                <p><?= date('d/m/Y', strtotime($c['data_saida'])) ?></p>
                            </td>
                            <td class="px-5 py-5 text-sm font-bold">
                                <p><?= date('d/m/Y', strtotime($c['data_prevista_retorno'])) ?></p>
                            </td>
                            <td class="px-5 py-5 text-center text-sm">
                                <span class="bg-blue-100 text-blue-800 py-1 px-3 rounded-full text-xs font-bold">
                                    <?= $c['qtd_pecas_total'] ?: 0 ?> itens
                                </span>
                            </td>
                            <td class="px-5 py-5 text-center text-sm">
                                <span class="text-gray-800 font-bold">R$ <?= number_format($c['valor_total_sacola'] ?: 0, 2, ',', '.') ?></span>
                                <span class="block text-gray-500 text-xs mt-1">Custo: R$ <?= number_format($c['valor_custo_sacola'] ?: 0, 2, ',', '.') ?></span>
                            </td>
                            <td class="px-5 py-5 text-center text-sm">
                                <span class="relative inline-block px-3 py-1 font-semibold leading-tight <?= $classeStatus ?> rounded-full">
                                    <span class="relative"><?= $textoStatus ?></span>
                                </span>
                            </td>
                            <td class="px-5 py-5 text-center text-sm">
                                <div class="flex flex-col space-y-2">
                                    <?php if($c['status'] !== 'FINALIZADO'): ?>
                                        <a href="condicionais_baixar.php?id=<?= $c['id'] ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-3 rounded text-xs shadow">
                                            Receber / Baixar
                                        </a>
                                    <?php endif; ?>
                                    <a href="condicionais_detalhes.php?id=<?= $c['id'] ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-3 rounded text-xs">
                                        Ver Detalhes
                                    </a>
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