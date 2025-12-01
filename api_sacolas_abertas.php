<?php
header('Content-Type: application/json');
require_once 'conexao.php';

// Data de hoje para comparação
$hoje = date('Y-m-d');

try {
    // 1. Busca APENAS sacolas Atrasadas (Data Prevista < Hoje)
    // Mantemos o status = 'ABERTO' porque 'ATRASADO' é um estado calculado no nosso sistema
    $sql = "
        SELECT 
            c.id, 
            c.data_saida, 
            c.data_prevista_retorno,
            cl.nome as cliente_nome,
            'ATRASADO' as status_real
        FROM condicionais c
        JOIN clientes cl ON c.cliente_id = cl.id
        WHERE c.status = 'ABERTO' 
          AND c.data_prevista_retorno < ?
        ORDER BY 
            c.data_prevista_retorno ASC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hoje]);
    $sacolas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($sacolas);

} catch (PDOException $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
?>