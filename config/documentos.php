<?php
require_once __DIR__ . '/paths.php';

const DOCUMENTO_TAMANO_MAXIMO = 10485760;
const DOCUMENTO_TIPOS_VALIDOS = [
    'application/pdf' => ['pdf'],
    'image/jpeg' => ['jpg','jpeg'],
    'image/png' => ['png'],
    'image/webp' => ['webp'],
];

/** Valida contenido, MIME real, extensión y tamaño y calcula metadatos. */
function documentoAnalizar(string $rutaTemporal, string $nombreOriginal, int $tamanoReportado, int $maximo = DOCUMENTO_TAMANO_MAXIMO): array
{
    if (!is_file($rutaTemporal) || !is_readable($rutaTemporal)) throw new RuntimeException('El archivo recibido no existe o no se puede leer.');
    $tamano = filesize($rutaTemporal);
    if ($tamano === false || $tamano <= 0 || $tamano > $maximo || ($tamanoReportado > 0 && $tamano !== $tamanoReportado)) {
        throw new RuntimeException('El archivo está vacío, excede 10 MB o su tamaño recibido no coincide.');
    }
    $extension = strtolower(pathinfo(basename($nombreOriginal), PATHINFO_EXTENSION));
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($rutaTemporal);
    if (!$mime || !isset(DOCUMENTO_TIPOS_VALIDOS[$mime]) || !in_array($extension, DOCUMENTO_TIPOS_VALIDOS[$mime], true)) {
        throw new RuntimeException('El contenido y la extensión no coinciden. Usa PDF, JPG, PNG o WEBP válidos.');
    }
    if ($mime === 'application/pdf') {
        $fh = fopen($rutaTemporal, 'rb');
        $head = $fh ? fread($fh, 8) : false;
        if ($fh) { fseek($fh, max(0, $tamano - 2048)); $tail = stream_get_contents($fh); fclose($fh); }
        else $tail = false;
        if (!is_string($head) || strpos($head, '%PDF-') !== 0 || !is_string($tail) || strpos($tail, '%%EOF') === false) {
            throw new RuntimeException('El PDF está incompleto o no tiene una estructura reconocible.');
        }
    } else {
        $img = @getimagesize($rutaTemporal);
        $esperado = ['image/jpeg'=>IMAGETYPE_JPEG, 'image/png'=>IMAGETYPE_PNG, 'image/webp'=>IMAGETYPE_WEBP][$mime] ?? 0;
        if (!$img || (int)$img[2] !== $esperado) throw new RuntimeException('La imagen está incompleta o no coincide con su MIME.');
    }
    $sha = hash_file('sha256', $rutaTemporal);
    if (!$sha) throw new RuntimeException('No se pudo calcular la huella SHA-256.');
    return ['sha256'=>$sha, 'mime'=>$mime, 'bytes'=>(int)$tamano, 'extension'=>$extension];
}

function documentoNombreFisico(string $extension): string
{
    if (!in_array($extension, ['pdf','jpg','jpeg','png','webp'], true)) throw new InvalidArgumentException('Extensión documental inválida.');
    return 'evi_' . bin2hex(random_bytes(20)) . '.' . $extension;
}

/** Comprueba duplicados sin devolver IDs, nombres ni datos de terceros. */
function documentoCoincidencias(mysqli $conn, string $sha256, int $docenteId, int $excluirEvidencia = 0): array
{
    $sql = "SELECT d.id_docente FROM (
              SELECT id_docente, id_evidencia FROM evidencias WHERE archivo_sha256=?
              UNION ALL
              SELECT e.id_docente, e.id_evidencia FROM evidencia_valores_atributo v JOIN evidencias e ON e.id_evidencia=v.id_evidencia WHERE v.valor_archivo_sha256=?
            ) d WHERE d.id_evidencia<>?";
    $st=$conn->prepare($sql);
    if (!$st) throw new RuntimeException('Falta aplicar la migración de metadatos documentales.');
    $st->bind_param('ssi',$sha256,$sha256,$excluirEvidencia);
    $st->execute(); $res=$st->get_result(); $propio=false; $externo=false;
    while ($row=$res->fetch_assoc()) {
        if ((int)$row['id_docente']===$docenteId) $propio=true; else $externo=true;
    }
    $st->close();
    return ['mismo_docente'=>$propio, 'posible_coincidencia'=>$externo];
}

function documentoDestinoSeguro(string $nombre): string
{
    if ($nombre === '' || basename($nombre) !== $nombre || $nombre === '.' || $nombre === '..') throw new RuntimeException('Nombre físico inválido.');
    $dir = BASE_DIR . '/uploads/files';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) throw new RuntimeException('No se pudo crear la carpeta documental.');
    $realDir=realpath($dir); $realBase=realpath(BASE_DIR);
    if (!$realDir || !$realBase || strncasecmp($realDir, rtrim($realBase, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR, strlen(rtrim($realBase, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR))!==0) {
        throw new RuntimeException('La ruta de almacenamiento documental no es válida.');
    }
    return $realDir . DIRECTORY_SEPARATOR . $nombre;
}
