<?php
// Visor local y entrega del archivo. Nunca recibe rutas del cliente.
if (!defined('BASE_DIR')) define('BASE_DIR', realpath(__DIR__ . '/../..'));
require_once BASE_DIR . '/config/paths.php';
require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/evidencias.php';
$usuario = evidenciaUsuarioActual($conn);
$id = (int)($_GET['id'] ?? 0);
$rol = (int)$usuario['rol'];
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
function archivoError(int $estado, string $mensaje): void {
    http_response_code($estado);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Documento</title><body><p role="alert">'.htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8').'</p></body></html>';
    exit;
}
if (!$usuario['id_usuario']) archivoError(401, 'Inicia sesión para consultar el documento.');
if ($id <= 0) archivoError(400, 'Evidencia inválida.');
$st = $conn->prepare('SELECT e.archivo, e.titulo, e.id_docente, e.id_tipo_evidencia FROM evidencias e WHERE e.id_evidencia = ? AND e.ocultar = 0');
$st->bind_param('i', $id);
$st->execute();
$evidencia = $st->get_result()->fetch_assoc();
if (!$evidencia) archivoError(404, 'La evidencia no está disponible.');
if (!evidenciaPuedeVer($conn, $usuario, $evidencia)) archivoError(403, 'No tienes acceso al documento de esta evidencia.');
$nombreArchivo = (string)$evidencia['archivo'];
$raiz = realpath(BASE_DIR . '/uploads/files');
$ruta = $raiz ? realpath($raiz.DIRECTORY_SEPARATOR.$nombreArchivo) : false;
if (!$nombreArchivo || !$ruta || !is_file($ruta) || !is_readable($ruta)
    || strncmp($ruta, $raiz.DIRECTORY_SEPARATOR, strlen($raiz)+1) !== 0) {
    archivoError(404, 'El archivo no está disponible. Solicita que se vuelva a cargar.');
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);
$tiposVisibles = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'text/plain'];
$visible = in_array($mime, $tiposVisibles, true);
$descarga = isset($_GET['descargar']);
if ($descarga || isset($_GET['contenido'])) {
    if (!$descarga && !$visible) archivoError(415, 'Este formato no admite vista previa. Utiliza la opción Descargar.');
    header('Content-Type: '.($descarga ? 'application/octet-stream' : $mime));
    header('Content-Disposition: '.($descarga ? 'attachment' : 'inline').'; filename="evidencia.'.$id.'"; filename*=UTF-8\'\''.rawurlencode(basename($ruta)));
    header('Content-Length: '.filesize($ruta));
    session_write_close();
    readfile($ruta);
    exit;
}
session_write_close();
header('Content-Type: text/html; charset=utf-8');
$url = BASE_URL . '/controllers/docente/evidencia-archivo.php?id='.$id;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($evidencia['titulo'], ENT_QUOTES, 'UTF-8') ?> — Documento</title>
  <style>
    :root { color-scheme: light dark; }
    * { box-sizing:border-box; }
    body { margin:0; font:14px system-ui,sans-serif; height:100vh; display:flex; flex-direction:column; }
    header { padding:12px; border-bottom:1px solid #8886; display:flex; flex-wrap:wrap; gap:12px; align-items:center; }
    header strong { flex:1; min-width:0; overflow-wrap:anywhere; }
    a { color:inherit; text-underline-offset:3px; }
    main { flex:1; min-height:0; overflow:auto; }
    iframe { display:block; border:0; width:100%; height:100%; }
    img { display:block; max-width:100%; height:auto; margin:auto; }
    .aviso { padding:24px; line-height:1.6; }
  </style>
</head>
<body>
  <header>
    <strong><?= htmlspecialchars($evidencia['titulo'], ENT_QUOTES, 'UTF-8') ?></strong>
    <a href="<?= $url ?>" target="_blank" rel="noopener">Abrir en otra pestaña</a>
    <a href="<?= $url ?>&amp;descargar=1">Descargar</a>
  </header>
  <main>
    <?php if (!$visible): ?>
      <p class="aviso">Este formato no se puede visualizar en el navegador. Descarga el archivo para revisarlo en su aplicación correspondiente. Puedes mantener abierta la evaluación.</p>
    <?php elseif (strpos($mime, 'image/') === 0): ?>
      <img src="<?= $url ?>&amp;contenido=1" alt="Documento de la evidencia">
    <?php else: ?>
      <iframe src="<?= $url ?>&amp;contenido=1" title="Contenido del documento"></iframe>
    <?php endif; ?>
  </main>
</body>
</html>
