<?php
require_once 'conexao.php';

try {
    // SQL Poderoso:
    // 1. Seleciona dados do cliente
    // 2. Conta quantos condicionais ele já fez na vida (total_historico)
    // 3. Conta quantos estão COM status ABERTO ou ATRASADO agora (em_aberto)
    $sql = "SELECT 
                c.id, c.nome, c.cpf, c.telefone, c.email,
                (SELECT COUNT(*) FROM condicionais WHERE cliente_id = c.id) as total_historico,
                (SELECT COUNT(*) FROM condicionais WHERE cliente_id = c.id AND status IN ('ABERTO', 'ATRASADO')) as em_aberto
            FROM clientes c
            ORDER BY em_aberto DESC, c.nome ASC";

    $stmt = $pdo->query($sql);
    $clientes = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Erro ao listar clientes: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Meus Clientes</title>
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
            <h2 class="text-2xl font-bold text-gray-800">Carteira de Clientes</h2>
            <a href="clientes_criar.php"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow font-bold transition">
                + Novo Cliente
            </a>
        </div>

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Contato
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Histórico
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Situação Atual
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Ações
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cli): ?>
                        <tr class="hover:bg-gray-50 border-b border-gray-200">
                            <td class="px-5 py-5 text-sm">
                                <div class="flex items-center">
                                    <div
                                        class="flex-shrink-0 w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 font-bold uppercase">
                                        <?= substr($cli['nome'], 0, 2) ?>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-gray-900 font-bold whitespace-no-wrap">
                                            <?= htmlspecialchars($cli['nome']) ?>
                                        </p>
                                        <p class="text-gray-500 text-xs">CPF: <?= $cli['cpf'] ?></p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-5 text-sm">
                                <p class="text-gray-900 whitespace-no-wrap">
                                    📱 <?= $cli['telefone'] ?: '<span class="text-gray-400">-</span>' ?>
                                </p>
                                <p class="text-gray-500 text-xs">
                                    📧 <?= $cli['email'] ?: '-' ?>
                                </p>
                            </td>

                            <td class="px-5 py-5 text-center text-sm">
                                <span class="text-gray-700 font-bold"><?= $cli['total_historico'] ?></span>
                                <span class="text-gray-500 text-xs block">sacolas feitas</span>
                            </td>

                            <td class="px-5 py-5 text-center text-sm">
                                <?php if ($cli['em_aberto'] > 0): ?>
                                    <span
                                        class="bg-orange-100 text-orange-800 py-1 px-3 rounded-full text-xs font-bold border border-orange-200">
                                        ⚠️ <?= $cli['em_aberto'] ?> em aberto
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="bg-green-100 text-green-800 py-1 px-3 rounded-full text-xs font-bold border border-green-200">
                                        ✅ Nada consta
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="px-5 py-5 text-center text-sm">
                                <div class="flex justify-center items-center space-x-3">

                                    <a href="condicionais_criar.php?cliente_id=<?= $cli['id'] ?>"
                                        class="text-blue-600 hover:text-blue-900 bg-blue-100 hover:bg-blue-200 p-2 rounded transition"
                                        title="Nova Sacola">
                                        🛍️
                                    </a>

                                    <a href="clientes_editar.php?id=<?= $cli['id'] ?>"
                                        class="text-amber-600 hover:text-amber-900 bg-amber-100 hover:bg-amber-200 p-2 rounded transition"
                                        title="Editar Dados">
                                        ✏️
                                    </a>

                                    <a href="clientes_excluir.php?id=<?= $cli['id'] ?>"
                                        onclick="return confirm('Tem certeza que deseja excluir <?= htmlspecialchars($cli['nome']) ?>? Essa ação não pode ser desfeita.');"
                                        class="text-red-600 hover:text-red-900 bg-red-100 hover:bg-red-200 p-2 rounded transition"
                                        title="Excluir Cliente">
                                        🗑️
                                    </a>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (count($clientes) == 0): ?>
                <div class="p-6 text-center text-gray-500">
                    Nenhum cliente cadastrado.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>