<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pagina_atual = basename($_SERVER['PHP_SELF']);

// Função para estilo do link
function checkAtivo($pagina, $pagina_atual) {
    if ($pagina == $pagina_atual) {
        return "bg-white bg-opacity-20 text-white border-l-4 border-white"; 
    }
    return "text-purple-100 hover:bg-purple-700 hover:text-white hover:bg-opacity-50";
}

$usuario_nome = $_SESSION['usuario_nome'] ?? 'Visitante';
$usuario_foto = $_SESSION['usuario_foto'] ?? null;
$usuario_nivel = $_SESSION['usuario_nivel'] ?? null;

$caminho_foto_perfil = 'uploads/usuarios/' . $usuario_foto;
if (!file_exists($caminho_foto_perfil) || empty($usuario_foto)) {
    $caminho_foto_perfil = 'img/default_avatar.png';
}
?>

<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden" onclick="toggleSidebar()"></div>

<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-roxo-base text-white transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out shadow-xl flex flex-col h-screen">
    
    <div class="p-6 border-b border-purple-500 flex items-center justify-center">
        <div class="flex items-center space-x-3">
            <img src="img/cond_logo.png" alt="Logo" class="h-10 w-10 bg-white rounded-full p-1">
            <span class="text-2xl font-bold tracking-wider">COND</span>
        </div>
        <button onclick="toggleSidebar()" class="md:hidden absolute right-4 text-white focus:outline-none">
            <i class="bi bi-x-lg text-2xl"></i>
        </button>
    </div>

    <div class="p-6 flex items-center space-x-3 border-b border-purple-500 bg-purple-800 bg-opacity-30">
        <img src="<?= $caminho_foto_perfil ?>" class="h-12 w-12 rounded-full object-cover border-2 border-white">
        <div class="overflow-hidden">
            <p class="text-sm font-bold truncate"><?= htmlspecialchars($usuario_nome) ?></p>
            <p class="text-xs text-purple-200 uppercase tracking-wide"><?= $usuario_nivel ?></p>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 scrollbar-thin scrollbar-thumb-purple-500 scrollbar-track-transparent">
        <ul class="space-y-1">
            
            <li class="px-6 py-2 text-xs font-bold text-purple-300 uppercase tracking-wider">Principal</li>
            <li>
                <a href="index.php" class="flex items-center px-6 py-3 transition <?= checkAtivo('index.php', $pagina_atual) ?>">
                    <i class="bi bi-grid-1x2-fill mr-3 text-lg"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="condicionais_lista.php" class="flex items-center px-6 py-3 transition <?= checkAtivo('condicionais_lista.php', $pagina_atual) ?>">
                    <i class="bi bi-bag-check-fill mr-3 text-lg"></i> Sacolas
                </a>
            </li>
            <li>
                <a href="condicionais_pedidos.php" class="flex items-center px-6 py-3 transition <?= checkAtivo('condicionais_pedidos.php', $pagina_atual) ?>">
                    <i class="bi bi-images mr-3 text-lg"></i> Catálogo
                </a>
            </li>

            <li class="px-6 py-2 mt-4 text-xs font-bold text-purple-300 uppercase tracking-wider">Gestão</li>
            <li>
                <a href="clientes_lista.php" class="flex items-center px-6 py-3 transition <?= checkAtivo('clientes_lista.php', $pagina_atual) ?>">
                    <i class="bi bi-people-fill mr-3 text-lg"></i> Clientes
                </a>
            </li>
            <li>
                <a href="produtos_listar.php" class="flex items-center px-6 py-3 transition <?= checkAtivo('produtos_listar.php', $pagina_atual) ?>">
                    <i class="bi bi-box-seam-fill mr-3 text-lg"></i> Estoque
                </a>
            </li>
            <li>
                <a href="fornecedores_lista.php" class="flex items-center px-6 py-3 transition <?= checkAtivo('fornecedores_lista.php', $pagina_atual) ?>">
                    <i class="bi bi-truck mr-3 text-lg"></i> Fornecedores
                </a>
            </li>
             <li>
                <a href="entradas_lista.php" class="flex items-center px-6 py-3 transition <?= checkAtivo('entradas_lista.php', $pagina_atual) ?>">
                    <i class="bi bi-arrow-down-square-fill mr-3 text-lg"></i> Entradas
                </a>
            </li>

            <li class="px-6 py-2 mt-4 text-xs font-bold text-purple-300 uppercase tracking-wider">Financeiro</li>
            <li>
                <a href="contas_a_receber_lista.php" class="flex items-center px-6 py-3 transition <?= checkAtivo('contas_a_receber_lista.php', $pagina_atual) ?>">
                    <i class="bi bi-wallet2 mr-3 text-lg"></i> A Receber
                </a>
            </li>
            <li>
                <a href="relatorio_vendas.php" class="flex items-center px-6 py-3 transition <?= checkAtivo('relatorio_vendas.php', $pagina_atual) ?>">
                    <i class="bi bi-graph-up-arrow mr-3 text-lg"></i> Rel. Vendas
                </a>
            </li>
            <li>
                <a href="relatorio_condicionais.php" class="flex items-center px-6 py-3 transition <?= checkAtivo('relatorio_condicionais.php', $pagina_atual) ?>">
                    <i class="bi bi-bag-plus-fill mr-3 text-lg"></i> Rel. Sacolas
                </a>
            </li>
            <li>
                <a href="relatorio_top_clientes.php" class="flex items-center px-6 py-3 transition <?= checkAtivo('relatorio_top_clientes.php', $pagina_atual) ?>">
                    <i class="bi bi-trophy-fill mr-3 text-lg"></i> Top Clientes
                </a>
            </li>
            <li>
                <a href="relatorio_inventario.php" class="flex items-center px-6 py-3 transition <?= checkAtivo('relatorio_inventario.php', $pagina_atual) ?>">
                    <i class="bi bi-archive-fill mr-3 text-lg"></i> Rel. Estoque
                </a>
            </li>

            <?php if ($usuario_nivel == 'admin'): ?>
                <li class="px-6 py-2 mt-4 text-xs font-bold text-purple-300 uppercase tracking-wider">Sistema</li>
                <li>
                    <a href="usuarios_lista.php" class="flex items-center px-6 py-3 transition <?= checkAtivo('usuarios_lista.php', $pagina_atual) ?>">
                        <i class="bi bi-shield-lock-fill mr-3 text-lg"></i> Admin
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="p-4 border-t border-purple-500 bg-roxo-base">
        <div class="flex justify-between items-center">
            <a href="perfil_editar.php" class="text-sm hover:text-white text-purple-200 transition flex items-center">
                <i class="bi bi-gear-fill mr-2"></i> Config
            </a>
            <a href="logout.php" class="text-sm hover:text-red-300 text-purple-200 transition flex items-center">
                Sair <i class="bi bi-box-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        
        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    }
</script>