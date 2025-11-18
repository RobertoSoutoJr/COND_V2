<?php

/**
 * Gera o link wa.me com a mensagem pré-preenchida, adaptada ao status do condicional.
 * 
 * @param PDO $pdo Objeto de conexão PDO.
 * @param array $condicional Dados do condicional (deve conter id, status, data_prevista_retorno, cliente_nome, telefone).
 * @return string O link 'https://wa.me/' pronto para ser usado.
 */
function gerarLinkWhatsApp(PDO $pdo, array $condicional) {
    // 1. Definir o status real (incluindo a lógica de ATRAZADO)
    $status_real = $condicional['status'];
    $hoje = date('Y-m-d');

    // A lógica de ATRAZADO é baseada na data de retorno e no status 'ABERTO'
    if ($status_real == 'ABERTO' && $condicional['data_prevista_retorno'] < $hoje) {
        $status_real = 'ATRASADO';
    }

    // 2. Buscar os itens do condicional para montar a lista de peças
    $lista_pecas = '';
    $total_sacola = 0;
    
    try {
        $stmt_itens = $pdo->prepare("
            SELECT i.quantidade, p.nome, i.preco_momento
            FROM itens_condicional i 
            JOIN produtos p ON i.produto_id = p.id
            WHERE i.condicional_id = ?
        ");
        $stmt_itens->execute([$condicional['id']]);
        $itens = $stmt_itens->fetchAll();

        foreach ($itens as $item) {
            $preco_formatado = number_format($item['preco_momento'], 2, ',', '.');
            $lista_pecas .= "- " . $item['quantidade'] . "x " . $item['nome'] . " - R$ " . $preco_formatado . "\n";
            $total_sacola += $item['preco_momento'] * $item['quantidade'];
        }
    } catch (PDOException $e) {
        // Em caso de erro, a lista fica vazia, mas o link ainda pode ser gerado.
        error_log("Erro ao buscar itens para WhatsApp: " . $e->getMessage());
        $lista_pecas = "Não foi possível carregar a lista de peças.\n";
    }

    // 3. Montar o corpo da mensagem
    $nome_cliente = htmlspecialchars($condicional['cliente_nome']);
    $id_condicional = $condicional['id'];
    $data_retorno = date('d/m/Y', strtotime($condicional['data_prevista_retorno']));
    $valor_total_formatado = number_format($total_sacola, 2, ',', '.');

    $mensagem = "🛍️ Olá, {$nome_cliente}! 🛍️\n\n";
    
    switch ($status_real) {
        case 'ABERTO':
            $mensagem .= "Seu condicional (ID #{$id_condicional}) foi gerado com sucesso!\n\n";
            $mensagem .= "*Peças que você levou:*\n";
            $mensagem .= $lista_pecas;
            $mensagem .= "*Valor Total:* R$ {$valor_total_formatado}\n";
            $mensagem .= "Data prevista para retorno: {$data_retorno}\n\n";
            $mensagem .= "Experimente com carinho e nos vemos em breve! 💜";
            break;

        case 'ATRASADO':
            $mensagem .= "🚨 *ATENÇÃO: Condicional Atrasado!* 🚨\n";
            $mensagem .= "O prazo de retorno do seu condicional (ID #{$id_condicional}) expirou em {$data_retorno}.\n\n";
            $mensagem .= "Por favor, entre em contato conosco o mais rápido possível para agendar a devolução ou finalização da compra.\n\n";
            $mensagem .= "Agradecemos a sua atenção! 🙏";
            break;

        case 'FINALIZADO':
            $mensagem .= "✅ *Condicional Finalizado com Sucesso!* ✅\n";
            $mensagem .= "O seu condicional (ID #{$id_condicional}) foi finalizado em nosso sistema.\n\n";
            $mensagem .= "Obrigado por sua preferência! Esperamos te ver em breve. 😊";
            break;
            
        default:
            // Mensagem padrão para qualquer outro status
            $mensagem .= "Seu condicional (ID #{$id_condicional}) está com o status: *{$status_real}*.\n\n";
            $mensagem .= "Para mais detalhes, entre em contato conosco. 😉";
            break;
    }

    // 4. Codificar a mensagem para URL e montar o link
    $mensagem_codificada = urlencode($mensagem);
    
    // Remove caracteres não-numéricos do telefone (incluindo +)
    $telefone_limpo = preg_replace('/[^0-9]/', '', $condicional['telefone']);

    $link = "https://wa.me/{$telefone_limpo}?text={$mensagem_codificada}";

    return $link;
}

?>
