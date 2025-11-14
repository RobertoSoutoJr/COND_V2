<?php
require_once 'conexao.php';

// Verifica ID
if (!isset($_GET['id'])) {
    header("Location: clientes_lista.php");
    exit;
}

$id = $_GET['id'];
$mensagem = '';

// --- BUSCAR DADOS ATUAIS NO BANCO ---
try {
    $stmt = $pdo->prepare("
        SELECT c.*, e.cep, e.logradouro, e.numero, e.complemento, e.bairro, e.cidade, e.estado 
        FROM clientes c 
        JOIN enderecos e ON c.id = e.cliente_id 
        WHERE c.id = ?
    ");
    $stmt->execute([$id]);
    $cliente_atual = $stmt->fetch();

    if (!$cliente_atual) {
        die("Cliente não encontrado.");
    }
} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}

// --- FUNÇÃO AUXILIAR PARA PREENCHER INPUTS (Lógica Híbrida) ---
// Se tiver $_POST (erro de validação), usa $_POST.
// Se não, usa o dado do banco ($cliente_atual).
function getVal($campo, $banco_dados) {
    if (isset($_POST[$campo])) {
        return htmlspecialchars($_POST[$campo]);
    }
    return htmlspecialchars($banco_dados[$campo] ?? '');
}

// --- PROCESSAR O SALVAMENTO (Igual ao criar, mas com UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Recebe dados
        $nome = $_POST['nome'];
        $cpf_bruto = $_POST['cpf'];
        $email = $_POST['email'];
        $telefone = $_POST['telefone'];
        
        $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf_bruto);
        $cep_limpo = preg_replace('/[^0-9]/', '', $_POST['cep']);
        
        // Validações (Você pode reutilizar a função validaCPF aqui se quiser)
        // ... (Código de validação omitido para brevidade, mas recomendado manter igual ao criar) ...

        $pdo->beginTransaction();

        // 1. Atualiza Cliente
        $sql_cliente = "UPDATE clientes SET nome = :nome, cpf = :cpf, telefone = :telefone, email = :email WHERE id = :id";
        $stmt = $pdo->prepare($sql_cliente);
        $stmt->execute([
            ':nome' => $nome,
            ':cpf' => $cpf_limpo,
            ':telefone' => $telefone,
            ':email' => $email,
            ':id' => $id
        ]);

        // 2. Atualiza Endereço
        $sql_endereco = "UPDATE enderecos SET cep = :cep, logradouro = :log, numero = :num, 
                         complemento = :comp, bairro = :bairro, cidade = :cid, estado = :est 
                         WHERE cliente_id = :id";
        $stmt = $pdo->prepare($sql_endereco);
        $stmt->execute([
            ':cep' => $cep_limpo,
            ':log' => $_POST['logradouro'],
            ':num' => $_POST['numero'],
            ':comp' => $_POST['complemento'],
            ':bairro' => $_POST['bairro'],
            ':cid' => $_POST['cidade'],
            ':est' => $_POST['estado'],
            ':id' => $id
        ]);

        $pdo->commit();
        
        // Recarrega os dados do banco para mostrar atualizado
        header("Location: clientes_lista.php?msg=atualizado");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "<div class='bg-red-100 text-red-700 p-4 rounded mb-4'>Erro ao atualizar: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente</title>
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

    <div class="container mx-auto mt-10 px-4 mb-10">
        <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
            
            <div class="flex justify-between items-center border-b pb-2 mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Editar Cliente #<?= $id ?></h2>
                <a href="clientes_lista.php" class="text-blue-600 hover:underline">Voltar</a>
            </div>

            <?= $mensagem ?>

            <form method="POST" action="">
                
                <h3 class="text-lg font-semibold text-amber-600 mb-4">Dados Pessoais</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nome Completo *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                               name="nome" type="text" required value="<?= getVal('nome', $cliente_atual) ?>">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">CPF *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-100" 
                               name="cpf" type="text" readonly value="<?= getVal('cpf', $cliente_atual) ?>">
                        <p class="text-xs text-gray-500">CPF não pode ser alterado.</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Celular</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                               name="telefone" type="text" oninput="mascaraTelefone(this)" maxlength="15"
                               value="<?= getVal('telefone', $cliente_atual) ?>">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">E-mail</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                               name="email" type="email" value="<?= getVal('email', $cliente_atual) ?>">
                    </div>
                </div>

                <h3 class="text-lg font-semibold text-amber-600 mb-4">Endereço</h3>
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2">CEP *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                               id="cep" name="cep" type="text" maxlength="9" required 
                               onblur="buscarCep(this.value)" oninput="mascaraCep(this)"
                               value="<?= getVal('cep', $cliente_atual) ?>">
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Logradouro *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-50" 
                               id="logradouro" name="logradouro" type="text" readonly required
                               value="<?= getVal('logradouro', $cliente_atual) ?>">
                    </div>

                    <div class="md:col-span-1">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Número *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                               name="numero" type="text" required
                               value="<?= getVal('numero', $cliente_atual) ?>">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Bairro *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-50" 
                               id="bairro" name="bairro" type="text" readonly required
                               value="<?= getVal('bairro', $cliente_atual) ?>">
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Cidade *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-50" 
                               id="cidade" name="cidade" type="text" readonly required
                               value="<?= getVal('cidade', $cliente_atual) ?>">
                    </div>

                    <div class="md:col-span-1">
                        <label class="block text-gray-700 text-sm font-bold mb-2">UF *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-50" 
                               id="estado" name="estado" type="text" readonly required
                               value="<?= getVal('estado', $cliente_atual) ?>">
                    </div>

                    <div class="md:col-span-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Complemento</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                               name="complemento" type="text"
                               value="<?= getVal('complemento', $cliente_atual) ?>">
                    </div>
                </div>

                <div class="mt-6 flex space-x-4">
                    <button class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-4 rounded focus:outline-none transition duration-300" 
                            type="submit">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function mascaraTelefone(i) {
            var v = i.value;
            v = v.replace(/\D/g, "");
            v = v.replace(/^(\d{2})(\d)/g, "($1) $2");
            v = v.replace(/(\d)(\d{4})$/, "$1-$2");
            i.value = v;
        }

        function mascaraCep(i) {
            var v = i.value;
            v = v.replace(/\D/g, "");                
            v = v.replace(/^(\d{5})(\d)/, "$1-$2"); 
            i.value = v;
        }

        function buscarCep(cep) {
            cep = cep.replace(/\D/g, '');
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.erro) {
                            document.getElementById('logradouro').value = data.logradouro;
                            document.getElementById('bairro').value = data.bairro;
                            document.getElementById('cidade').value = data.localidade;
                            document.getElementById('estado').value = data.uf;
                        }
                    });
            }
        }
    </script>
</body>
</html>