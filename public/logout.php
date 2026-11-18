<?php
require_once __DIR__ . '/../config/paths.php';
require_once CONFIG_DIR . '/csrf.php';

iniciarSesionSegura();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !csrf_token_valido()) {
    http_response_code(419);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'La sesión o el token de seguridad venció. Recarga la página e inténtalo nuevamente.';
    exit();
}
destruirSesionSegura();

header('Location: ' . BASE_URL . '/public/login.php?logout=1');
exit();
