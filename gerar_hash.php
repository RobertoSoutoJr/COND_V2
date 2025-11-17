<?php
// Isso vai pegar a senha 'admin' e criptografar ela do jeito que o SEU PHP entende.
echo password_hash('admin', PASSWORD_DEFAULT);
?>