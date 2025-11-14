<?php
require_once 'conexao.php';

$mensagem = '';

// --- FUNÇÕES AUXILIARES ---

// 1. Validação CPF
function validaCPF($cpf) {
    $cpf = preg_replace( '/[^0-9]/is', '', $cpf );
    if (strlen($cpf) != 11) return false;
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) return false;
    }
    return true;
}

// 2. Função "Sticky": Mantém o valor do campo se der erro
function valor($campo) {
    return isset($_POST[$campo]) ? htmlspecialchars($_POST[$campo]) : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nome = $_POST['nome'];
        $cpf_bruto = $_POST['cpf'];
        $email = $_POST['email'];
        $telefone = $_POST['telefone'];
        
        // Limpeza
        $cpf_limpo = preg_replace('/[^0-9]/', '', $cpf_bruto);
        $cep_limpo = preg_replace('/[^0-9]/', '', $_POST['cep']);
        
        // --- VALIDAÇÕES PHP ---
        $erros = [];

        if (!validaCPF($cpf_limpo)) {
            $erros[] = "O CPF informado é inválido.";
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erros[] = "O formato do e-mail está incorreto.";
        }

        // Se houver erros, lança exceção para cair no catch (e não limpa o POST)
        if (count($erros) > 0) {
            $lista_erros = implode('<br>', $erros);
            throw new Exception($lista_erros);
        }

        // --- SALVAMENTO ---
        $pdo->beginTransaction();

        $sql_cliente = "INSERT INTO clientes (nome, cpf, telefone, email) VALUES (:nome, :cpf, :telefone, :email)";
        $stmt = $pdo->prepare($sql_cliente);
        $stmt->execute([
            ':nome' => $nome,
            ':cpf' => $cpf_limpo,
            ':telefone' => $telefone,
            ':email' => $email
        ]);

        $cliente_id = $pdo->lastInsertId();

        $sql_endereco = "INSERT INTO enderecos (cliente_id, cep, logradouro, numero, complemento, bairro, cidade, estado) 
                         VALUES (:cliente_id, :cep, :logradouro, :numero, :complemento, :bairro, :cidade, :estado)";
        $stmt = $pdo->prepare($sql_endereco);
        $stmt->execute([
            ':cliente_id' => $cliente_id,
            ':cep' => $cep_limpo,
            ':logradouro' => $_POST['logradouro'],
            ':numero' => $_POST['numero'],
            ':complemento' => $_POST['complemento'],
            ':bairro' => $_POST['bairro'],
            ':cidade' => $_POST['cidade'],
            ':estado' => $_POST['estado']
        ]);

        $pdo->commit();
        
        // SUCESSO! Limpa os campos para permitir novo cadastro
        $_POST = array(); 
        $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>Cliente cadastrado com sucesso!</div>";

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        
        // ERRO! Não limpamos o $_POST, então a função valor() vai preencher os inputs
        if (isset($stmt) && $stmt->errorCode() == '23000') {
            $erro_msg = "Este CPF já está cadastrado no sistema.";
        } else {
            $erro_msg = $e->getMessage();
        }
        $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'><b>Atenção:</b> <br> $erro_msg</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Novo Cliente - Sistema Condicional</title>
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
            
            <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">Cadastrar Cliente</h2>
            <?= $mensagem ?>

            <form method="POST" action="" id="formCliente">
                
                <h3 class="text-lg font-semibold text-roxo-base mb-4">Dados Pessoais</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Nome Completo *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-roxo-base" 
                               name="nome" type="text" required value="<?= valor('nome') ?>">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">CPF *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-roxo-base" 
                               id="cpf" name="cpf" type="text" maxlength="14" placeholder="000.000.000-00"
                               oninput="mascaraCPF(this)" onblur="validarCPF(this)" required value="<?= valor('cpf') ?>">
                        <p id="erro-cpf" class="text-xs text-red-500 hidden mt-1 font-bold">CPF Inválido!</p>
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">Celular (WhatsApp)</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-roxo-base" 
                               name="telefone" type="text" maxlength="15" placeholder="(DD) 90000-0000"
                               oninput="mascaraTelefone(this)" value="<?= valor('telefone') ?>">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">E-mail</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-roxo-base" 
                               name="email" type="email" placeholder="cliente@email.com" value="<?= valor('email') ?>">
                    </div>
                </div>

                <h3 class="text-lg font-semibold text-roxo-base mb-4">Endereço</h3>
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2">CEP *</label>
                        <div class="relative">
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-roxo-base" 
                                   id="cep" name="cep" type="text" placeholder="00000-000" maxlength="9"
                                   oninput="mascaraCep(this)" onblur="buscarCep(this.value)" required value="<?= valor('cep') ?>">
                            <div id="loading-cep" class="absolute right-0 top-0 mt-2 mr-2 hidden">
                                <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Logradouro *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-50" 
                               id="logradouro" name="logradouro" type="text" required readonly value="<?= valor('logradouro') ?>">
                    </div>

                    <div class="md:col-span-1">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Número *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-roxo-base" 
                               id="numero" name="numero" type="text" required value="<?= valor('numero') ?>">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Bairro *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-50" 
                               id="bairro" name="bairro" type="text" required readonly value="<?= valor('bairro') ?>">
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Cidade *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-50" 
                               id="cidade" name="cidade" type="text" required readonly value="<?= valor('cidade') ?>">
                    </div>

                    <div class="md:col-span-1">
                        <label class="block text-gray-700 text-sm font-bold mb-2">UF *</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-50" 
                               id="estado" name="estado" type="text" required readonly value="<?= valor('estado') ?>">
                    </div>

                    <div class="md:col-span-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Complemento</label>
                        <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-roxo-base" 
                               name="complemento" type="text" placeholder="Apto, Bloco..." value="<?= valor('complemento') ?>">
                    </div>
                </div>

                <div class="mt-6">
                    <button class="w-full bg-roxo-base hover:bg-roxo-base text-white font-bold py-3 px-4 rounded focus:outline-none focus:shadow-outline transition duration-300" 
                            type="submit" id="btn-salvar">
                        Salvar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function mascaraCPF(i) {
            var v = i.value;
            if(isNaN(v[v.length-1])){
                i.value = v.substring(0, v.length-1);
                return;
            }
            i.setAttribute("maxlength", "14");
            v = v.replace(/\D/g, "")
            v = v.replace(/(\d{3})(\d)/, "$1.$2")
            v = v.replace(/(\d{3})(\d)/, "$1.$2")
            v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2")
            i.value = v;
        }

        function validarCPF(el) {
            const cpfLimpo = el.value.replace(/\D/g, '');
            const erroMsg = document.getElementById('erro-cpf');
            const btnSalvar = document.getElementById('btn-salvar');

            if(cpfLimpo.length !== 11 || /^(\d)\1+$/.test(cpfLimpo)) {
                marcarCPFInvalido(el, erroMsg, btnSalvar);
                return;
            }

            let soma = 0;
            let resto;
            for (let i = 1; i <= 9; i++) soma = soma + parseInt(cpfLimpo.substring(i-1, i)) * (11 - i);
            resto = (soma * 10) % 11;
            if ((resto == 10) || (resto == 11)) resto = 0;
            if (resto != parseInt(cpfLimpo.substring(9, 10)) ) { marcarCPFInvalido(el, erroMsg, btnSalvar); return; }

            soma = 0;
            for (let i = 1; i <= 10; i++) soma = soma + parseInt(cpfLimpo.substring(i-1, i)) * (12 - i);
            resto = (soma * 10) % 11;
            if ((resto == 10) || (resto == 11)) resto = 0;
            if (resto != parseInt(cpfLimpo.substring(10, 11) ) ) { marcarCPFInvalido(el, erroMsg, btnSalvar); return; }

            el.classList.remove('border-red-500');
            el.classList.add('border-green-500');
            erroMsg.classList.add('hidden');
            btnSalvar.disabled = false;
            btnSalvar.classList.remove('opacity-50', 'cursor-not-allowed');
        }

        function marcarCPFInvalido(el, msg, btn) {
            el.classList.add('border-red-500');
            el.classList.remove('border-green-500');
            msg.classList.remove('hidden');
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        }

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
                document.getElementById('loading-cep').classList.remove('hidden');
                
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('loading-cep').classList.add('hidden');
                        if (!data.erro) {
                            document.getElementById('logradouro').value = data.logradouro;
                            document.getElementById('bairro').value = data.bairro;
                            document.getElementById('cidade').value = data.localidade;
                            document.getElementById('estado').value = data.uf;
                            document.getElementById('numero').focus();
                        } else {
                            alert("CEP não encontrado.");
                            limparFormularioCep();
                        }
                    })
                    .catch(() => {
                        document.getElementById('loading-cep').classList.add('hidden');
                        alert("Erro ao buscar CEP.");
                    });
            }
        }

        function limparFormularioCep() {
            document.getElementById('logradouro').value = "";
            document.getElementById('bairro').value = "";
            document.getElementById('cidade').value = "";
            document.getElementById('estado').value = "";
        }
    </script>
</body>
</html>