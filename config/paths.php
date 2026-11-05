<?php
// Rutas físicas del servidor (Para usar con include o require)
if (!defined('BASE_DIR')) {
    define('BASE_DIR', realpath(__DIR__ . '/..'));
}
if (!defined('COMPONENTS_DIR')) {
    define('COMPONENTS_DIR', BASE_DIR . '/components');
}
if (!defined('CONFIG_DIR')) {
    define('CONFIG_DIR', BASE_DIR . '/config');
}
if (!defined('CONTROLLERS_DIR')) {
    define('CONTROLLERS_DIR', BASE_DIR . '/controllers');
}

// Rutas web (Para usar en href, src, form action y redirecciones)
if (!defined('BASE_URL')) {
    $baseDirectory = str_replace('\\', '/', realpath(BASE_DIR));
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT'])
        ? realpath($_SERVER['DOCUMENT_ROOT'])
        : false;

    if ($documentRoot !== false) {
        $documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
        $normalizedBaseDirectory = rtrim($baseDirectory, '/');
        $rootPrefix = $documentRoot . '/';

        if (strcasecmp($normalizedBaseDirectory, $documentRoot) === 0) {
            $baseUrl = '';
        } elseif (stripos($normalizedBaseDirectory, $rootPrefix) === 0) {
            $baseUrl = substr($normalizedBaseDirectory, strlen($documentRoot));
        } else {
            $baseUrl = null;
        }
    } else {
        $baseUrl = null;
    }

    if ($baseUrl === null) {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $matched = preg_match('~^(.*?)/(?:public|views|controllers|components)/~i', $scriptName, $matches);
        $baseUrl = $matched ? $matches[1] : '';
    }

    define('BASE_URL', rtrim($baseUrl, '/'));
}
?>