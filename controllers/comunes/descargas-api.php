<?php
// descargas-api.php
declare(strict_types=1);

// --- Endpoints limpios: JSON / ZIP, sin HTML mezclado ---
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
if (function_exists('header_remove')) { header_remove('X-Powered-By'); }

// Arranca buffers para poder limpiar cualquier salida previa
if (ob_get_level() === 0) { ob_start(); }

require_once __DIR__ . '/../../config/paths.php';
require_once CONFIG_DIR . '/sesion.php';
require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/seguridad.php';
require_once CONFIG_DIR . '/reportes.php';
$usuarioActual = seguridadUsuarioActual($conn);
if (!(int)$usuarioActual['id_usuario']) json_out(['status'=>'error','message'=>'Sesión no autorizada'], 401);

// ---------- Utilidades ----------
function safe_filename(string $name): string {
  // Mantiene letras con acentos (unicode) y caracteres seguros
  $name = preg_replace('/[^\p{L}\p{N} _\.\-]/u', '', $name);
  $name = trim(preg_replace('/\s+/', ' ', $name));
  if ($name === '') $name = 'archivo';
  if (mb_strlen($name) > 100) $name = mb_substr($name, 0, 100);
  return $name;
}

function json_out($data, int $code = 200): void {
  // Limpia cualquier salida previa (evita que rompa el JSON)
  while (ob_get_level() > 0) { ob_end_clean(); }
  http_response_code($code);
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function text_out(string $text, int $code = 200): void {
  while (ob_get_level() > 0) { ob_end_clean(); }
  http_response_code($code);
  header('Content-Type: text/plain; charset=UTF-8');
  echo $text;
  exit;
}

function reportesArchivos(mysqli $conn, array $usuario, int $instrumento, bool $soloAceptadas): array {
  $filtros = reportesFiltrosContexto($_GET);
  $filtros['solo_aceptadas'] = $soloAceptadas || isset($_GET['solo_aceptadas']) || in_array(strtoupper($filtros['estado']), ['', 'ACEPTADA'], true);
  $items = [];
  foreach (reportesConsultarDictamenes($conn, $usuario, $instrumento, 0, $filtros) as $row) {
    if (empty($row['archivo'])) continue;
    $row['id'] = (int)$row['id_evidencia'];
    $row['inst'] = ['id'=>(int)$row['id_instrumento'], 'abbr'=>$row['abreviatura'],
      'name'=>$row['nombre_completo'], 'tipo'=>$row['tipo_calificacion']];
    $items[] = $row;
  }
  return $items;
}

// ---------- Router ----------
$action = $_GET['action'] ?? '';

if ($action === 'list') {
  $instId = isset($_GET['instrumento']) ? (int)$_GET['instrumento'] : 0;
  if ($instId <= 0) json_out(['status'=>'error','message'=>'Instrumento inválido'], 400);
  if (!seguridadPuedeAccederInstrumento($conn, $usuarioActual, $instId)) json_out(['status'=>'error','message'=>'No autorizado para este instrumento'], 403);

  $items = reportesArchivos($conn, $usuarioActual, $instId, false);

  // Trae título/abbr del instrumento (aunque no haya aprobadas)
  $instAbbr = ''; $instName = '';
  if ($q = $conn->prepare("SELECT abreviatura, nombre_completo FROM instrumentos WHERE id_instrumento=?")) {
    $q->bind_param('i', $instId);
    $q->execute();
    if ($r = $q->get_result()->fetch_assoc()) {
      $instAbbr = (string)$r['abreviatura']; $instName = (string)$r['nombre_completo'];
    }
    $q->close();
  }

  json_out([
    'status' => 'ok',
    'instrumento' => ['id'=>$instId, 'abbr'=>$instAbbr, 'name'=>$instName],
    'items' => $items
  ]);
}

if ($action === 'zip') {
  $instId = isset($_GET['instrumento']) ? (int)$_GET['instrumento'] : 0;
  $idsStr = $_GET['ids'] ?? '';
  if ($instId <= 0 || $idsStr === '') text_out('Parámetros inválidos', 400);
  if (!seguridadPuedeAccederInstrumento($conn, $usuarioActual, $instId)) text_out('No autorizado para este instrumento', 403);

  $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $idsStr)), fn($v)=>$v>0)));
  if (empty($ids)) text_out('IDs inválidos', 400);

  $aprobadas = reportesArchivos($conn, $usuarioActual, $instId, true);
  if (empty($aprobadas)) text_out('No hay aprobadas', 404);

  $idx = [];
  foreach ($aprobadas as $it) $idx[$it['id']] = $it;
  $seleccion = [];
  foreach ($ids as $i) if (isset($idx[$i])) $seleccion[] = $idx[$i];
  if (empty($seleccion)) text_out('Ninguna evidencia válida', 404);

  // Si no hay ZipArchive, fallback: descargas múltiples
  if (!class_exists('ZipArchive')) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: text/html; charset=UTF-8');
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Descargas</title></head><body>";
    foreach ($seleccion as $s) {
      $href = BASE_URL.'/controllers/docente/evidencia-archivo.php?id='.(int)$s['id'].'&descargar=1';
      $href = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
      echo "<a href=\"{$href}\" download style=\"display:none;\" class=\"dl\"></a>";
    }
    echo "<script>document.querySelectorAll('.dl').forEach((a,i)=>setTimeout(()=>a.click(), i*400));</script>";
    echo "<p>Iniciando descargas… Puedes cerrar esta pestaña.</p>";
    echo "</body></html>";
    exit;
  }

  // Crear ZIP temporal
  $tmpBase = tempnam(sys_get_temp_dir(), 'odea_zip_');
  @unlink($tmpBase);
  $zipPath = $tmpBase . '.zip';

  $zip = new ZipArchive();
  if ($zip->open($zipPath, ZipArchive::CREATE|ZipArchive::OVERWRITE) !== true) {
    text_out('No se pudo crear el ZIP', 500);
  }

  $added = 0;
  foreach ($seleccion as $s) {
    $abs = seguridadResolverArchivo((string)$s['archivo']);
    if (!$abs) continue;
    $ext  = pathinfo($abs, PATHINFO_EXTENSION);
    $name = safe_filename($s['titulo']);
    $entry = sprintf('%03d - %s%s', (int)$s['id'], $name, $ext ? ('.'.$ext) : '');
    if ($zip->addFile($abs, $entry)) $added++;
  }
  $zip->close();

  if ($added === 0 || !is_file($zipPath)) {
    @is_file($zipPath) && @unlink($zipPath);
    text_out('No se agregaron archivos', 404);
  }

  // Nombre del ZIP con abreviatura del instrumento
  $abbr = '';
  if ($q = $conn->prepare("SELECT abreviatura FROM instrumentos WHERE id_instrumento=?")) {
    $q->bind_param('i', $instId);
    $q->execute();
    if ($r = $q->get_result()->fetch_assoc()) $abbr = (string)$r['abreviatura'];
    $q->close();
  }

  while (ob_get_level() > 0) { ob_end_clean(); }

  $fname = 'evidencias_'.$abbr.'_'.date('Ymd_His').'.zip';
  header('Content-Type: application/zip');
  header('Content-Length: '.filesize($zipPath));
  header('Content-Disposition: attachment; filename="'.$fname.'"');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  readfile($zipPath);
  @unlink($zipPath);
  exit;
}

// Acción no válida
text_out('Acción inválida', 400);
