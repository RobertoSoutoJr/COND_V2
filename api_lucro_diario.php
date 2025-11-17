<?php
// Define que a resposta será do tipo JSON (para o JavaScript)
header('Content-Type: application/json');
require_once 'conexao.php';

// Pega as datas da URL. Se não vier, usa os últimos 30 dias como padrão.
$data_inicio = $_GET['inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$data_fim = $_GET['fim'] ?? date('Y-m-d');

try {
    // Esta é a consulta SQL complexa:
    // 1. Junta as tabelas
    // 2. Filtra por status 'VENDIDO'
    // 3. Filtra pelo período de data solicitado
    // 4. AGRUPA por dia
    // 5. SOMA o lucro de cada dia
    $stmt = $pdo->prepare("
        SELECT 
            DATE(c.data_saida) as dia, 
            SUM((i.preco_momento * i.quantidade) - (p.preco_custo * i.quantidade)) as lucro_dia
        FROM itens_condicional i
        JOIN produtos p ON i.produto_id = p.id
        JOIN condicionais c ON i.condicional_id = c.id
        WHERE i.status_item = 'VENDIDO' 
          AND c.data_saida BETWEEN ? AND ?
        GROUP BY DATE(c.data_saida)
        ORDER BY dia ASC
    ");
    
    $stmt->execute([$data_inicio, $data_fim]);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Devolve os dados em formato JSON
    echo json_encode($dados);

} catch (PDOException $e) {
    // Se der erro, devolve um JSON de erro
    echo json_encode(['erro' => $e->getMessage()]);
}
?>