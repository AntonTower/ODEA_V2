<?php
// Actualiza metadatos de archivos existentes. Por omisión solo informa; --apply escribe en DB.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/paths.php';
require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/documentos.php';
require_once CONFIG_DIR . '/seguridad.php';

$aplicar = in_array('--apply', $argv, true);
$procesados = 0; $omitidos = 0;
$principal = $conn->query('SELECT id_evidencia, archivo FROM evidencias WHERE archivo_sha256 IS NULL');
$actualizarE = $conn->prepare('UPDATE evidencias SET archivo_sha256=?, archivo_mime=?, archivo_bytes=? WHERE id_evidencia=? AND archivo_sha256 IS NULL');
while ($row = $principal->fetch_assoc()) {
    $ruta = seguridadResolverArchivo((string)$row['archivo']);
    if (!$ruta) { echo "Evidencia {$row['id_evidencia']}: archivo ausente o fuera de uploads/files\n"; $omitidos++; continue; }
    try { $meta = documentoAnalizar($ruta, (string)$row['archivo'], (int)filesize($ruta)); }
    catch (Throwable $e) { echo "Evidencia {$row['id_evidencia']}: {$e->getMessage()}\n"; $omitidos++; continue; }
    echo "Evidencia {$row['id_evidencia']}: {$meta['sha256']} ({$meta['bytes']} bytes)" . ($aplicar ? " actualizada\n" : " [simulación]\n");
    if ($aplicar) { $id=(int)$row['id_evidencia']; $actualizarE->bind_param('ssii',$meta['sha256'],$meta['mime'],$meta['bytes'],$id); $actualizarE->execute(); }
    $procesados++;
}
$principal->free(); $actualizarE->close();

$atributos = $conn->query("SELECT v.id_eva, v.id_evidencia, e.id_docente, v.valor_archivo FROM evidencia_valores_atributo v JOIN evidencias e ON e.id_evidencia=v.id_evidencia WHERE v.valor_archivo IS NOT NULL AND v.valor_archivo<>'' AND v.valor_archivo_sha256 IS NULL");
$actualizarV = $conn->prepare('UPDATE evidencia_valores_atributo SET valor_archivo_sha256=?, valor_archivo_mime=?, valor_archivo_bytes=? WHERE id_eva=? AND valor_archivo_sha256 IS NULL');
while ($row = $atributos->fetch_assoc()) {
    $ruta = seguridadResolverArchivo((string)$row['valor_archivo']);
    if (!$ruta) { echo "Valor {$row['id_eva']}: archivo ausente o fuera de uploads/files\n"; $omitidos++; continue; }
    try { $meta = documentoAnalizar($ruta, (string)$row['valor_archivo'], (int)filesize($ruta)); }
    catch (Throwable $e) { echo "Valor {$row['id_eva']}: {$e->getMessage()}\n"; $omitidos++; continue; }
    echo "Valor {$row['id_eva']}: {$meta['sha256']} ({$meta['bytes']} bytes)" . ($aplicar ? " actualizado\n" : " [simulación]\n");
    if ($aplicar) { $id=(int)$row['id_eva']; $actualizarV->bind_param('ssii',$meta['sha256'],$meta['mime'],$meta['bytes'],$id); $actualizarV->execute(); }
    $procesados++;
}
$atributos->free(); $actualizarV->close();
echo "Fin: {$procesados} procesables; {$omitidos} omitidos. Modo: ".($aplicar?'aplicar':'simulación').".\n";
