<?php
// Lógica para marcar o botão como ativo
$pagina_atual = basename($_SERVER['PHP_SELF']);

function checkAtivo($pagina, $pagina_atual) {
    if ($pagina == $pagina_atual) {
        // Cor de fundo mais escura para o item ativo
        return "bg-purple-900 text-white"; 
    }
    // Cor de texto mais clara para itens inativos
    return "text-purple-100 hover:bg-purple-700 hover:text-white";
}
?>

<nav class="bg-roxo-base shadow-lg">
    <div class="container mx-auto px-4">
        <div class="flex justify-between">
            
            <div class="flex space-x-7">
                <a href="index.php" class="flex items-center py-4 px-2">
                    
                    <img src="img/logo.png" alt="Logo Loja" class="h-8 w-8 mr-2"> 
                    
                    <span class="font-bold text-white text-lg">Loja Condicional</span>
                </a>
            </div>

            <div class="hidden md:flex items-center space-x-1">
                
                <a href="index.php" 
                   class="py-4 px-4 font-semibold transition duration-300 <?= checkAtivo('index.php', $pagina_atual) ?>">
                   <i class="bi bi-house-door-fill mr-1"></i> Início
                </a>

                <a href="condicionais_lista.php" 
                   class="py-4 px-4 font-semibold transition duration-300 <?= checkAtivo('condicionais_lista.php', $pagina_atual) ?>">
                   <i class="bi bi-bag-check-fill mr-1"></i> Sacolas
                </a>

                <a href="clientes_lista.php" 
                   class="py-4 px-4 font-semibold transition duration-300 <?= checkAtivo('clientes_lista.php', $pagina_atual) ?>">
                   <i class="bi bi-people-fill mr-1"></i> Clientes
                </a>

                <a href="produtos_listar.php" 
                   class="py-4 px-4 font-semibold transition duration-300 <?= checkAtivo('produtos_listar.php', $pagina_atual) ?>">
                   <i class="bi bi-box-seam-fill mr-1"></i> Estoque
                </a>
            </div>

            <div class="md:hidden flex items-center">
                <button class="text-white hover:text-gray-200 font-bold">
                    <i class="bi bi-list text-2xl"></i>
                </button>
            </div>
        </div>
    </div>
</nav>