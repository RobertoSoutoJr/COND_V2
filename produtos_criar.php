<?php
require_once 'conexao.php';

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 1. UPLOAD DE IMAGEM
        $caminho_imagem = null; // Padrão é nulo se não enviar foto
        
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
            $extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $permitidos = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array(strtolower($extensao), $permitidos)) {
                // Gera nome único: ex: 65a9f8_vestido.jpg
                $novo_nome = uniqid() . "." . $extensao;
                $destino = 'uploads/' . $novo_nome;
                
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
                    $caminho_imagem = $novo_nome;
                }
            } else {
                throw new Exception("Formato de imagem inválido. Use JPG ou PNG.");
            }
        }

        // 2. DADOS DO FORMULÁRIO
        $nome = $_POST['nome'];
        $descricao = $_POST['descricao'];
        $tamanho = $_POST['tamanho'];
        $cor = $_POST['cor'];
        
        // Tratamento de valores (troca vírgula por ponto)
        $custo = str_replace(',', '.', $_POST['custo']);
        $preco = str_replace(',', '.', $_POST['preco']);
        $estoque = $_POST['estoque'];

        // 3. INSERÇÃO NO BANCO
        $sql = "INSERT INTO produtos (nome, descricao, tamanho, cor, preco_custo, preco, estoque_loja, imagem) 
                VALUES (:nome, :descricao, :tamanho, :cor, :custo, :preco, :estoque, :imagem)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome' => $nome,
            ':descricao' => $descricao,
            ':tamanho' => $tamanho,
            ':cor' => $cor,
            ':custo' => $custo,
            ':preco' => $preco,
            ':estoque' => $estoque,
            ':imagem' => $caminho_imagem
        ]);

        $mensagem = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4'>Produto cadastrado com sucesso!</div>";

    } catch (Exception $e) {
        $mensagem = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4'>Erro: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Novo Produto - Financeiro</title>
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
<body class="bg-gray-100 pb-20">

    <?php include 'menu.php'; ?>

    <div class="container mx-auto mt-10 px-4">
        <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
            
            <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">Cadastrar Produto</h2>
            <?= $mensagem ?>

            <form method="POST" action="" enctype="multipart/form-data">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <div>
                        <h3 class="font-bold text-blue-600 mb-4">Detalhes da Peça</h3>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Nome da Peça *</label>
                            <input class="border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-blue-500" 
                                   name="nome" type="text" placeholder="Ex: Vestido Longo Florido" required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Descrição</label>
                            <textarea class="border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-blue-500" 
                                      name="descricao" rows="3"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Tamanho</label>
                                <select class="border rounded w-full py-2 px-3 text-gray-700 bg-white" name="tamanho">
                                    <option value="P">P</option>
                                    <option value="M">M</option>
                                    <option value="G">G</option>
                                    <option value="GG">GG</option>
                                    <option value="UNICO">Único</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2">Cor</label>
                                <input class="border rounded w-full py-2 px-3 text-gray-700" name="cor" type="text">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Foto do Produto</label>
                            <input type="file" name="foto" accept="image/*" 
                                   class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"/>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-bold text-green-600 mb-4">Financeiro & Estoque</h3>
                        
                        <div class="bg-green-50 p-6 rounded-lg border border-green-100">
                            
                            <div class="mb-6">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Estoque Inicial (Entrada)</label>
                                <input class="border rounded w-full py-2 px-3 text-gray-700" 
                                       name="estoque" type="number" min="1" value="1" required>
                            </div>

                            <hr class="border-green-200 mb-6">

                            <div class="grid grid-cols-2 gap-4">
                                
                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Preço de Custo (R$)</label>
                                    <input class="border rounded w-full py-2 px-3 text-gray-700" 
                                           id="custo" name="custo" type="text" placeholder="0,00" 
                                           oninput="calcularLucro()">
                                </div>

                                <div>
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Preço de Venda (R$)</label>
                                    <input class="border rounded w-full py-2 px-3 text-gray-700 font-bold text-green-700" 
                                           id="venda" name="preco" type="text" placeholder="0,00" required 
                                           oninput="calcularLucro()">
                                </div>
                            </div>

                            <div class="mt-6 grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs text-gray-500 font-bold uppercase">Lucro Estimado</label>
                                    <input class="w-full bg-transparent text-gray-800 font-bold text-lg border-none p-0 focus:ring-0" 
                                           id="lucro_rs" type="text" readonly value="R$ 0,00">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 font-bold uppercase">Margem %</label>
                                    <input class="w-full bg-transparent text-gray-800 font-bold text-lg border-none p-0 focus:ring-0" 
                                           id="margem_pct" type="text" readonly value="0%">
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <div class="mt-8">
                    <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded focus:outline-none focus:shadow-outline transition duration-300" 
                            type="submit">
                        Salvar Produto
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
        function calcularLucro() {
            // Pega os valores e troca vírgula por ponto
            let custo = document.getElementById('custo').value.replace(',', '.');
            let venda = document.getElementById('venda').value.replace(',', '.');

            // Converte para número (Float)
            custo = parseFloat(custo) || 0;
            venda = parseFloat(venda) || 0;

            // Calcula
            let lucro = venda - custo;
            let margem = 0;

            if (venda > 0) {
                margem = (lucro / venda) * 100;
            }

            // Atualiza a tela
            document.getElementById('lucro_rs').value = 'R$ ' + lucro.toFixed(2).replace('.', ',');
            document.getElementById('margem_pct').value = margem.toFixed(1).replace('.', ',') + '%';

            // Muda a cor se der prejuízo
            if (lucro < 0) {
                document.getElementById('lucro_rs').classList.add('text-red-600');
                document.getElementById('lucro_rs').classList.remove('text-green-600');
            } else {
                document.getElementById('lucro_rs').classList.add('text-green-600');
                document.getElementById('lucro_rs').classList.remove('text-red-600');
            }
        }
    </script>
</body>
</html>