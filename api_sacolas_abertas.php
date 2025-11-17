<?php
header('Content-Type: application/json');
require_once 'conexao.php';

// Data de hoje para calcular o status
$hoje = date('Y-m-d');

try {
    // 1. Busca sacolas ABERTAS e junta com o nome do CLIENTE
    // 2. Cria uma coluna virtual 'status_real' (Atrasado ou Em Dia)
    // 3. Ordena pelas Atrasadas primeiro (status_real DESC)
    // 4. Depois, ordena pela data de retorno (as mais antigas primeiro)
    $stmt = $pdo->prepare("
        SELECT 
            c.id, 
            c.data_saida, 
            c.data_prevista_retorno,
            cl.nome as cliente_nome,
            CASE 
                WHEN c.data_prevista_retorno < ? THEN 'ATRASADO'
                ELSE 'EM DIA'
            END as status_real
        FROM condicionais c
        JOIN clientes cl ON c.cliente_id = cl.id
        WHERE c.status = 'ABERTO'
        ORDER BY 
            status_real DESC, 
            c.data_prevista_retorno ASC
    ");
    
    $stmt->execute([$hoje]);
    $sacolas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($sacolas);

} catch (PDOException $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
?>