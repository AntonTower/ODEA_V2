<?php
// Pruebas unitarias de validación documental, sin mutar la base configurada.
if (PHP_SAPI !== 'cli') exit;
require_once __DIR__ . '/../config/paths.php';
require_once CONFIG_DIR . '/documentos.php';
$checks = 0;
function comprobarDocumento($ok, $mensaje) {
    global $checks;
    if (!$ok) throw new RuntimeException($mensaje);
    $checks++;
    echo "OK: $mensaje\n";
}
function rechazaDocumento(callable $fn): bool {
    try { $fn(); return false; } catch (Throwable $e) { return true; }
}
$fixture = realpath(__DIR__ . '/../sql/prueba/Evidencia de prueba.pdf');
$dirTmp = sys_get_temp_dir() . '/odea_doc_' . bin2hex(random_bytes(6));
mkdir($dirTmp);
try {
    $meta = documentoAnalizar($fixture, 'subida.pdf', (int)filesize($fixture));
    $dest = $dirTmp . '/' . documentoNombreFisico($meta['extension']);
    comprobarDocumento(copy($fixture, $dest) && hash_file('sha256', $dest) === $meta['sha256'], 'Carga normal conserva integridad y calcula SHA-256');
    $renamed = $dirTmp . '/copia_renombrada.pdf';
    copy($fixture, $renamed);
    $metaRenamed = documentoAnalizar($renamed, basename($renamed), (int)filesize($renamed));
    comprobarDocumento($meta['sha256'] === $metaRenamed['sha256'], 'Archivo renombrado conserva la huella de contenido');
    $fake = $dirTmp . '/extension_falsa.png';
    copy($fixture, $fake);
    comprobarDocumento(rechazaDocumento(fn() => documentoAnalizar($fake, basename($fake), (int)filesize($fake))), 'Extensión falsa rechazada por MIME/contenido');
    $large = $dirTmp . '/grande.pdf';
    file_put_contents($large, str_repeat('x', DOCUMENTO_TAMANO_MAXIMO + 1));
    comprobarDocumento(rechazaDocumento(fn() => documentoAnalizar($large, basename($large), (int)filesize($large))), 'Archivo mayor de 10 MB rechazado');
    $truncated = $dirTmp . '/truncado.pdf';
    file_put_contents($truncated, file_get_contents($fixture, false, null, 0, 100));
    comprobarDocumento(rechazaDocumento(fn() => documentoAnalizar($truncated, basename($truncated), (int)filesize($truncated))), 'Documento truncado rechazado');
    comprobarDocumento(preg_match('/^evi_[a-f0-9]{40}\.pdf$/', documentoNombreFisico('pdf')) === 1, 'Nombre físico aleatorio y sin datos del usuario');
    echo "$checks pruebas documentales correctas.\n";
} finally {
    foreach (glob($dirTmp . '/*') ?: [] as $file) if (is_file($file)) unlink($file);
    rmdir($dirTmp);
}
