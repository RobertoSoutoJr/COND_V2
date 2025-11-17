<?php
require_once 'auth_check.php';
require_once 'conexao.php';

if (!isset($_GET['id'])) {
    die("ID do condicional não fornecido.");
}
$cond_id = $_GET['id'];

try {
    // 1. Pega dados da Sacola, Cliente e Endereço
    $stmt_cond = $pdo->prepare("
        SELECT c.*, cl.nome, cl.cpf, cl.telefone,
               e.logradouro, e.numero, e.bairro, e.cidade, e.estado
        FROM condicionais c 
        JOIN clientes cl ON c.cliente_id = cl.id
        LEFT JOIN enderecos e ON cl.id = e.cliente_id
        WHERE c.id = ?
    ");
    $stmt_cond->execute([$cond_id]);
    $condicional = $stmt_cond->fetch();
    if (!$condicional) die("Condicional não encontrado.");

    // 2. Pega os Itens da Sacola (SEM fotos)
    $stmt_itens = $pdo->prepare("
        SELECT i.*, p.nome, p.tamanho, p.cor 
        FROM itens_condicional i
        JOIN produtos p ON i.produto_id = p.id
        WHERE i.condicional_id = ?
    ");
    $stmt_itens->execute([$cond_id]);
    $itens = $stmt_itens->fetchAll();

} catch (PDOException $e) {
    die("Erro ao buscar dados: " + $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recibo Condicional #<?= $cond_id ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { 'roxo-base': '#6753d8' } } } }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f0f0f0;
            font-size: 12px; /* Fonte base menor */
        }
        .recibo-container {
            width: 18cm; /* Mais estreito que A4 (21cm) */
            min-height: 14cm; /* Altura de meia folha A4 */
            margin: 10px auto;
            padding: 20px; /* Padding reduzido */
            border: 1px solid #eee;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header-recibo {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #333;
            padding-bottom: 5px; /* Reduzido */
        }
        .header-recibo img {
            max-height: 60px; /* Reduzido */
        }
        .header-recibo h2 {
            margin: 0;
            font-size: 20px; /* Reduzido */
        }
        .dados-cliente {
            margin-top: 15px; /* Reduzido */
            border: 1px solid #ccc;
            padding: 10px; /* Reduzido */
            background: #f9f9f9;
        }
        .dados-cliente h3 {
            margin: 0 0 5px 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
        }
        .dados-cliente p {
            margin: 3px 0;
        }
        .tabela-itens {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px; /* Reduzido */
        }
        .tabela-itens th, .tabela-itens td {
            border: 1px solid #ccc;
            padding: 6px; /* Reduzido */
            text-align: left;
            font-size: 11px; /* Reduzido */
        }
        .tabela-itens th {
            background: #eee;
        }
        .footer-recibo {
            margin-top: 20px; /* Reduzido */
            border-top: 1px solid #ccc;
            padding-top: 10px; /* Reduzido */
            font-size: 13px; /* Reduzido */
        }
        .termo {
            font-size: 9px; /* Reduzido */
            color: #555;
            background: #f5f5f5;
            padding: 8px;
            border: 1px dashed #ccc;
        }
        .assinatura {
            margin-top: 40px; /* Reduzido */
            text-align: center;
        }
        .assinatura p {
            border-top: 1px solid #000;
            display: inline-block;
            padding: 5px 40px 0 40px;
            font-size: 12px;
        }
        
        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }
        
        /* ESTILOS DE IMPRESSÃO */
        @media print {
            body {
                background: #fff;
            }
            .recibo-container {
                width: 100%;
                min-height: 0;
                margin: 0;
                padding: 0;
                border: none;
                box-shadow: none;
            }
            .no-print {
                display: none; /* Esconde o botão */
            }
        }
    </style>
</head>
<body>

    <div class="recibo-container">
        
        <div class="no-print">
            <button onclick="window.print()" class="bg-roxo-base text-white font-bold py-3 px-6 rounded shadow-lg transition hover:bg-purple-700">
                <i class="bi bi-printer-fill mr-2"></i> Imprimir Recibo
            </button>
        </div>

        <header class="header-recibo">
            <div>
                <h2>Recibo de Condicional #<?= $cond_id ?></h2>
                <p>Status: <strong><?= $condicional['status'] ?></strong></p>
            </div>
            <img src="img/logo.png" alt="Logo da Loja">
        </header>

        <section class="dados-cliente">
            <h3>Dados do Cliente</h3>
            <p><strong>Nome:</strong> <?= htmlspecialchars($condicional['nome']) ?></p>
            <p><strong>CPF:</strong> <?= htmlspecialchars($condicional['cpf']) ?> | <strong>Telefone:</strong> <?= htmlspecialchars($condicional['telefone']) ?></p>
            <p><strong>Endereço:</strong> <?= htmlspecialchars($condicional['logradouro']) ?>, <?= htmlspecialchars($condicional['numero']) ?> - <?= htmlspecialchars($condicional['bairro']) ?></p>
        </section>

        <section class="footer-recibo">
            <p><strong>Data de Retirada:</strong> <?= date('d/m/Y', strtotime($condicional['data_saida'])) ?></p>
            <p><strong>DATA LIMITE DE DEVOLUÇÃO:</strong> <strong style="font-size: 16px; background: #eee; padding: 4px;"><?= date('d/m/Y', strtotime($condicional['data_prevista_retorno'])) ?></strong></p>
        </section>

        <main>
            <table class="tabela-itens">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Tam/Cor</th>
                        <th>Qtd.</th>
                        <th>Valor Unit.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_pecas = 0;
                    $total_valor = 0;
                    foreach ($itens as $item): 
                        $total_pecas += $item['quantidade'];
                        $total_valor += $item['preco_momento'] * $item['quantidade'];
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['nome']) ?></td>
                        <td><?= htmlspecialchars($item['tamanho']) ?> / <?= htmlspecialchars($item['cor']) ?></td>
                        <td><?= $item['quantidade'] ?></td>
                        <td>R$ <?= number_format($item['preco_momento'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #eee; font-weight: bold;">
                        <td colspan="2" style="text-align: right;">TOTAIS:</td> <td><?= $total_pecas ?> Peças</td>
                        <td>R$ <?= number_format($total_valor, 2, ',', '.') ?></td>
                    </tr>
                </tfoot>
            </table>
        </main>

        <footer class="footer-recibo">
            <div class="termo">
                <strong>Termo de Responsabilidade:</strong> Declaro que retirei os produtos listados acima, os quais permanecem como propriedade da loja até a quitação ou devolução. Comprometo-me a devolvê-los até a data limite estipulada, sob pena de serem considerados como compra definitiva. Em caso de dano ou não devolução, autorizo a cobrança do valor total dos itens.
            </div>
            
            <div class="assinatura">
                <p>Assinatura do Cliente</p>
            </div>
        </footer>

    </div>
    
    <?php include 'toast_handler.php'; ?>
</body>
</html>