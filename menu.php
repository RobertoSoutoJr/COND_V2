<?php
// REMOVEMOS O session_start() DAQUI.

$pagina_atual = basename($_SERVER['PHP_SELF']);

function checkAtivo($pagina, $pagina_atual) {
    if ($pagina == $pagina_atual) return "bg-purple-900 text-white"; 
    return "text-purple-100 hover:bg-purple-700 hover:text-white";
}

// Pega os dados da Sessão (com verificação para evitar erros)
$usuario_id_logado = $_SESSION['usuario_id'] ?? null;
$usuario_nome = $_SESSION['usuario_nome'] ?? 'Visitante';
$usuario_foto = $_SESSION['usuario_foto'] ?? null;
$usuario_nivel = $_SESSION['usuario_nivel'] ?? null;

// Caminho da foto
$caminho_foto_perfil = 'uploads/usuarios/' . $usuario_foto;
if (!file_exists($caminho_foto_perfil) || empty($usuario_foto)) {
    $caminho_foto_perfil = 'img/default_avatar.png';
}
?>

<nav class="bg-roxo-base shadow-lg relative z-50">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center">
            <div class="flex space-x-4">
                <a href="index.php" class="flex items-center py-3 px-2">
                    <img src="<?= $caminho_foto_perfil ?>" alt="Foto Perfil" class="h-9 w-9 rounded-full object-cover mr-3 border-2 border-purple-300"> 
                    <span class="font-bold text-white text-lg"><?= htmlspecialchars($usuario_nome) ?></span>
                </a>
                <div class="hidden md:flex items-center space-x-1">
                    <a href="index.php" class="py-4 px-4 font-semibold transition <?= checkAtivo('index.php', $pagina_atual) ?>"><i class="bi bi-house-door-fill mr-1"></i> Início</a>
                    <a href="condicionais_lista.php" class="py-4 px-4 font-semibold transition <?= checkAtivo('condicionais_lista.php', $pagina_atual) ?>"><i class="bi bi-bag-check-fill mr-1"></i> Sacolas</a>
                    <a href="clientes_lista.php" class="py-4 px-4 font-semibold transition <?= checkAtivo('clientes_lista.php', $pagina_atual) ?>"><i class="bi bi-people-fill mr-1"></i> Clientes</a>
                    <a href="produtos_listar.php" class="py-4 px-4 font-semibold transition <?= checkAtivo('produtos_listar.php', $pagina_atual) ?>"><i class="bi bi-box-seam-fill mr-1"></i> Estoque</a>
                    <div class="relative group">
                        <?php
                            $is_relatorio_ativo = ($pagina_atual == 'relatorio_vendas.php' || $pagina_atual == 'relatorio_inventario.php');
                            $classe_ativo_relatorio = $is_relatorio_ativo ? 'bg-purple-900 text-white' : 'text-purple-100 hover:bg-purple-700 hover:text-white';
                        ?>
                        <button class="py-4 px-4 font-semibold transition flex items-center <?= $classe_ativo_relatorio ?>">
                            <i class="bi bi-graph-up mr-1"></i> Relatórios <i class="bi bi-chevron-down text-xs ml-1 transition-transform group-hover:rotate-180"></i>
                        </button>
                        <div class="absolute hidden group-hover:block bg-white shadow-lg rounded-b-md z-50 w-56 top-full left-0">
                            <a href="relatorio_vendas.php" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 hover:text-roxo-base font-medium"><i class="bi bi-graph-up-arrow mr-2 text-roxo-base"></i>Relatório de Vendas</a>
                            <a href="relatorio_inventario.php" class="block px-4 py-3 text-sm text-gray-700 hover:bg-gray-100 hover:text-roxo-base font-medium"><i class="bi bi-archive-fill mr-2 text-roxo-base"></i>Relatório de Estoque</a>
                        </div>
                    </div>
                    <?php if ($usuario_nivel == 'admin'): ?>
                        <a href="usuarios_lista.php" class="py-4 px-4 font-semibold transition <?= checkAtivo('usuarios_lista.php', $pagina_atual) ?>"><i class="bi bi-shield-lock-fill mr-1"></i> Admin</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hidden md:flex items-center space-x-3">
                <a href="perfil_editar.php" class="py-2 px-3 font-semibold text-purple-200 hover:bg-purple-700 hover:text-white transition rounded" title="Editar meu perfil">
                    <i class="bi bi-gear-fill text-lg"></i>
                </a>
                <a href="logout.php" class="py-2 px-3 font-semibold text-purple-200 hover:bg-red-600 hover:text-white transition rounded" title="Sair">
                    <i class="bi bi-box-arrow-right text-lg"></i>
                </a>
            </div>
            <div class="md:hidden flex items-center">
                <button id="hamburger-button" class="text-white p-2 focus:outline-none hover:text-purple-200">
                    <i class="bi bi-list text-3xl"></i>
                </button>
            </div>
        </div>
        <div id="mobile-menu" class="hidden md:hidden pb-3">
            <div class="px-4 py-3 border-b border-purple-500">
                <span class="text-white font-bold text-lg">
                    <?php echo $titulo_pagina ?? 'Menu'; ?>
                </span>
            </div>
            <a href="index.php" class="block py-2 px-4 text-sm text-purple-100 hover:bg-purple-700 hover:text-white rounded">Início</a>
            <a href="condicionais_lista.php" class="block py-2 px-4 text-sm text-purple-100 hover:bg-purple-700 hover:text-white rounded">Sacolas</a>
            <a href="clientes_lista.php" class="block py-2 px-4 text-sm text-purple-100 hover:bg-purple-700 hover:text-white rounded">Clientes</a>
            <a href="produtos_listar.php" class="block py-2 px-4 text-sm text-purple-100 hover:bg-purple-700 hover:text-white rounded">Estoque</a>
            <a href="relatorio_vendas.php" class="block py-2 px-4 text-sm text-purple-100 hover:bg-purple-700 hover:text-white rounded">Relatório de Vendas</a>
            <a href="relatorio_inventario.php" class="block py-2 px-4 text-sm text-purple-100 hover:bg-purple-700 hover:text-white rounded">Relatório de Estoque</a>
            <?php if ($usuario_nivel == 'admin'): ?>
                <a href="usuarios_lista.php" class="block py-2 px-4 text-sm text-purple-100 hover:bg-purple-700 hover:text-white rounded">Admin</a>
            <?php endif; ?>
            <hr class="border-purple-500 my-2">
            <a href="perfil_editar.php" class="block py-2 px-4 text-sm text-purple-100 hover:bg-purple-700 hover:text-white rounded">Meu Perfil</a>
            <a href="logout.php" class="block py-2 px-4 text-sm text-red-300 hover:bg-red-600 hover:text-white rounded">Sair</a>
        </div>
    </div>
    <script>
        const hamburgerBtn = document.getElementById('hamburger-button');
        const mobileMenu = document.getElementById('mobile-menu');
        hamburgerBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    </script>
</nav>