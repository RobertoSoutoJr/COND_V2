<?php
// Este ficheiro lê a URL e imprime o HTML do Toast, se existir.

if (isset($_GET['msg']) && !empty($_GET['msg'])) {
    $message = htmlspecialchars($_GET['msg']);
    $type = $_GET['type'] ?? 'info';

    // --- 1. NOVOS ESTILOS (Cores Suaves) ---
    $styles = 'bg-blue-100 border border-blue-300 text-blue-800'; // Padrão
    $icon = '<i class="bi bi-info-circle-fill"></i>';
    
    if ($type === 'success') {
        $styles = 'bg-green-100 border border-green-300 text-green-800';
        $icon = '<i class="bi bi-check-circle-fill"></i>';
    }
    if ($type === 'error') {
        $styles = 'bg-red-100 border border-red-300 text-red-800';
        $icon = '<i class="bi bi-exclamation-triangle-fill"></i>';
    }
    
    // --- 2. NOVAS CLASSES DE POSICIONAMENTO (Centralizado) ---
    echo "
    <div id='auto-toast' 
         class='fixed top-5 left-1/2 -translate-x-1/2 z-[100] p-4 rounded-lg shadow-lg font-bold w-full max-w-md transition-opacity duration-300 $styles'
         role='alert'>
        
        <div class='flex items-center'>
            <span class='text-xl mr-3'>$icon</span>
            <span class='flex-grow'>$message</span>
            <button onclick='document.getElementById(\"auto-toast\").remove()' class='ml-4 text-2xl font-light opacity-70 hover:opacity-100'>&times;</button>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.getElementById('auto-toast');
            if (toast) {
                setTimeout(() => {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 500); 
                }, 4000); // 4 segundos
            }
        });
    </script>
    ";
}
?>