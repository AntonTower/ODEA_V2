<?php
header('Content-Type: application/json; charset=utf-8');
if (!defined('BASE_DIR')) define('BASE_DIR', realpath(__DIR__ . '/../..'));
require_once BASE_DIR . '/config/paths.php';
require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/validacion.php';
require_once CONFIG_DIR . '/evidencias.php';
require_once CONFIG_DIR . '/documentos.php';
require_once CONFIG_DIR . '/procesamiento_documental.php';

$usuarioActual = evidenciaUsuarioActual($conn);
$user_id = (int)$usuarioActual['id_usuario'];

$id_evidencia = isset($_POST['id_evidencia']) ? (int)$_POST['id_evidencia'] : 0;
if ($id_evidencia <= 0) { echo json_encode(['status'=>'error','message'=>'Falta id_evidencia']); exit; }

// evidence
$sql = "SELECT e.id_evidencia, e.id_docente, e.id_tipo_evidencia FROM evidencias e WHERE e.id_evidencia = ? AND e.ocultar = 0";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_evidencia);
$stmt->execute();
$re = $stmt->get_result();
$evi = $re ? $re->fetch_assoc() : null;
$stmt->close();

if (!$evi) { echo json_encode(['status'=>'error','message'=>'Evidencia no encontrada']); exit; }
if (!evidenciaPuedeEditar($usuarioActual, $evi)) {
  echo json_encode(['status'=>'error','message'=>'No autorizado para editar']); exit;
}

// attributes for this type
$sqlA = "SELECT a.*,
                ta.id_tipo_atributo, ta.slug AS tipo_slug, ta.grupo_storage, ta.validador_regex
         FROM atributos_tipo_evidencia a
         INNER JOIN tipos_atributo ta ON ta.id_tipo_atributo = a.id_tipo_atributo
         WHERE a.id_tipo_evidencia = ?";
$stmt = $conn->prepare($sqlA);
$stmt->bind_param('i', $evi['id_tipo_evidencia']);
$stmt->execute();
$ra = $stmt->get_result();
$attrs = $ra ? $ra->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

$conn->begin_transaction();

try {
  $baseUploadDir = BASE_DIR . '/uploads/files';
  if (!is_dir($baseUploadDir) && !mkdir($baseUploadDir, 0775, true) && !is_dir($baseUploadDir)) {
    throw new Exception('No se pudo crear la carpeta de archivos.');
  }
  $stmtOldFiles = $conn->prepare('SELECT valor_archivo FROM evidencia_valores_atributo WHERE id_evidencia = ? AND valor_archivo IS NOT NULL AND valor_archivo <> ?');
  $emptyFilename = '';
  $stmtOldFiles->bind_param('is', $id_evidencia, $emptyFilename);
  $stmtOldFiles->execute();
  $oldFileNames = array_column($stmtOldFiles->get_result()->fetch_all(MYSQLI_ASSOC), 'valor_archivo');
  $stmtOldFiles->close();
  $newlyUploadedFiles = [];
  $documentosEncolables = [];
  $duplicateWarnings = [];

  foreach ($attrs as $a) {
    $aid  = (int)$a['id_ate'];
    $gs   = $a['grupo_storage'];
    $req  = (int)$a['requerido'] === 1;
    $unico = (int)$a['unico_por_evidencia'] === 1;
    $multi = (int)$a['multiple'] === 1;
    $minL = isset($a['min_longitud']) ? (int)$a['min_longitud'] : null;
    $maxL = isset($a['max_longitud']) ? (int)$a['max_longitud'] : null;
    $minV = isset($a['min_valor']) && $a['min_valor'] !== null ? (float)$a['min_valor'] : null;
    $maxV = isset($a['max_valor']) && $a['max_valor'] !== null ? (float)$a['max_valor'] : null;
    $regex = $a['validador_regex'];

    $indices = $_POST['indice'][$aid] ?? [];
    $cnt = is_array($indices) ? count($indices) : 0;

    // Recoger valores por grupo
    $arr_texto   = $_POST['valor_texto'][$aid]   ?? null;
    $arr_largo   = $_POST['valor_largo'][$aid]   ?? null;
    $arr_int     = $_POST['valor_int'][$aid]     ?? null;
    $arr_decimal = $_POST['valor_decimal'][$aid] ?? null;
    $arr_fecha   = $_POST['valor_fecha'][$aid]   ?? null;
    $arr_bool    = $_POST['valor_bool'][$aid]    ?? null;
    $arr_json    = $_POST['valor_json'][$aid]    ?? null;

    // Archivos: estructura $_FILES['valor_archivo']['name'][$aid][$i]
    $has_files = isset($_FILES['valor_archivo']) &&
                 isset($_FILES['valor_archivo']['name'][$aid]) &&
                 is_array($_FILES['valor_archivo']['name'][$aid]);

    // Validaciones previas
    if (($unico || !$multi) && $cnt > 1) {
      throw new Exception("El atributo {$a['nombre_atributo']} es único y no puede tener múltiples valores.");
    }

    $existingFilesByIndex = [];
    if ($gs === 'archivo') {
      $old = $conn->prepare('SELECT indice, valor_archivo, valor_archivo_sha256, valor_archivo_mime, valor_archivo_bytes FROM evidencia_valores_atributo WHERE id_evidencia = ? AND id_ate = ?');
      $old->bind_param('ii', $id_evidencia, $aid);
      $old->execute();
      foreach ($old->get_result()->fetch_all(MYSQLI_ASSOC) as $oldValue) {
        $existingFilesByIndex[(int)$oldValue['indice']] = $oldValue;
      }
      $old->close();
    }

    // Borrar existentes
    $del = $conn->prepare("DELETE FROM evidencia_valores_atributo WHERE id_evidencia=? AND id_ate=?");
    $del->bind_param('ii', $id_evidencia, $aid);
    $del->execute();
    $del->close();

    $insert = $conn->prepare("INSERT INTO evidencia_valores_atributo
      (id_evidencia, id_ate, indice, valor_texto, valor_largo, valor_int, valor_decimal, valor_fecha, valor_bool, valor_archivo, valor_archivo_sha256, valor_archivo_mime, valor_archivo_bytes, valor_json)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    $insert_cnt = 0;

    for ($i=0; $i<$cnt; $i++) {
      $indice = (int)$indices[$i];

      $val_texto = $val_largo = $val_fecha = $val_archivo = $val_json = null;
      $val_archivo_sha256 = $val_archivo_mime = null;
      $val_archivo_bytes = null;
      $metaEncolable = null;
      $val_int = $val_bool = null;
      $val_decimal = null;

      if ($gs === 'texto_corto') {
        $val_texto = trim((string)($arr_texto[$i] ?? ''));
        if ($val_texto === '' && $req) throw new Exception("{$a['nombre_atributo']}: valor requerido.");
        if ($val_texto === '' && !$req) continue; // permitir vacío no requerido
        if ($maxL && mb_strlen($val_texto) > $maxL) throw new Exception("{$a['nombre_atributo']}: supera longitud máxima.");
        if ($minL && mb_strlen($val_texto) < $minL) throw new Exception("{$a['nombre_atributo']}: por debajo de longitud mínima.");
        if ($regex && $val_texto !== '' && !preg_match('/'.$regex.'/i', $val_texto)) throw new Exception("{$a['nombre_atributo']}: formato inválido.");
      } elseif ($gs === 'texto_largo') {
        $val_largo = trim((string)($arr_largo[$i] ?? ''));
        if ($val_largo === '' && $req) throw new Exception("{$a['nombre_atributo']}: valor requerido.");
        if ($val_largo === '' && !$req) continue;
        if ($maxL && mb_strlen($val_largo) > $maxL) throw new Exception("{$a['nombre_atributo']}: supera longitud máxima.");
        if ($minL && mb_strlen($val_largo) < $minL) throw new Exception("{$a['nombre_atributo']}: por debajo de longitud mínima.");
      } elseif ($gs === 'entero') {
        $raw = $arr_int[$i] ?? '';
        if ($raw === '' || $raw === null) {
          if ($req) throw new Exception("{$a['nombre_atributo']}: valor requerido.");
          else continue;
        }
        if (!is_numeric($raw)) throw new Exception("{$a['nombre_atributo']}: debe ser entero.");
        $val_int = (int)$raw;
        if ($minV !== null && $val_int < $minV) throw new Exception("{$a['nombre_atributo']}: menor que mínimo.");
        if ($maxV !== null && $val_int > $maxV) throw new Exception("{$a['nombre_atributo']}: mayor que máximo.");
      } elseif ($gs === 'decimal') {
        $raw = $arr_decimal[$i] ?? '';
        if ($raw === '' || $raw === null) {
          if ($req) throw new Exception("{$a['nombre_atributo']}: valor requerido.");
          else continue;
        }
        if (!is_numeric($raw)) throw new Exception("{$a['nombre_atributo']}: debe ser número.");
        $val_decimal = (float)$raw;
        if ($minV !== null && $val_decimal < $minV) throw new Exception("{$a['nombre_atributo']}: menor que mínimo.");
        if ($maxV !== null && $val_decimal > $maxV) throw new Exception("{$a['nombre_atributo']}: mayor que máximo.");
      } elseif ($gs === 'fecha') {
        $raw = $arr_fecha[$i] ?? '';
        if ($raw === '' || $raw === null) {
          if ($req) throw new Exception("{$a['nombre_atributo']}: fecha requerida.");
          else continue;
        }
        $val_fecha = $raw;
      } elseif ($gs === 'booleano') {
        $raw = $arr_bool[$i] ?? '';
        if ($raw === '' || $raw === null) {
          if ($req) throw new Exception("{$a['nombre_atributo']}: requerido.");
          else continue;
        }
        $val_bool = ($raw === '1') ? 1 : 0;
      } elseif ($gs === 'archivo') {
        // Al editar se conserva el archivo existente si no se selecciona uno nuevo.
        $existingFile = $existingFilesByIndex[$indice] ?? null;
        $fileName = is_array($existingFile) ? (string)$existingFile['valor_archivo'] : null;
        $val_archivo_sha256 = is_array($existingFile) ? $existingFile['valor_archivo_sha256'] : null;
        $val_archivo_mime = is_array($existingFile) ? $existingFile['valor_archivo_mime'] : null;
        $val_archivo_bytes = is_array($existingFile) ? $existingFile['valor_archivo_bytes'] : null;
        if ($has_files) {
          $fn   = $_FILES['valor_archivo']['name'][$aid][$i] ?? null;
          $tmp  = $_FILES['valor_archivo']['tmp_name'][$aid][$i] ?? null;
          $err  = $_FILES['valor_archivo']['error'][$aid][$i] ?? UPLOAD_ERR_NO_FILE;
          $size = $_FILES['valor_archivo']['size'][$aid][$i] ?? 0;

          if ($err !== UPLOAD_ERR_NO_FILE && $err !== UPLOAD_ERR_OK) throw new Exception("{$a['nombre_atributo']}: error al recibir el archivo (código {$err}).");
          if ($err === UPLOAD_ERR_OK && $tmp && is_uploaded_file($tmp)) {
            $meta = documentoAnalizar((string)$tmp, (string)$fn, (int)$size);
            $matches = documentoCoincidencias($conn, $meta['sha256'], (int)$usuarioActual['id_usuario'], $id_evidencia);
            if ($matches['mismo_docente']) $duplicateWarnings['own']=true;
            if ($matches['posible_coincidencia']) $duplicateWarnings['other']=true;
            $fileName = documentoNombreFisico($meta['extension']);
            $dest = documentoDestinoSeguro($fileName);
            if (!move_uploaded_file($tmp, $dest)) throw new Exception("{$a['nombre_atributo']}: no se pudo guardar el archivo.");
            if (hash_file('sha256', $dest) !== $meta['sha256']) { @unlink($dest); throw new Exception("{$a['nombre_atributo']}: falló la comprobación de integridad."); }
            $val_archivo_sha256 = $meta['sha256'];
            $val_archivo_mime = $meta['mime'];
            $val_archivo_bytes = $meta['bytes'];
            $newlyUploadedFiles[] = $fileName;
            $metaEncolable = $meta;
          }
        }
        if (!$fileName) {
          if ($req) {
            throw new Exception("{$a['nombre_atributo']}: archivo requerido.");
          } else {
            continue;
          }
        }
        $val_archivo = $fileName;
      } elseif ($gs === 'json') {
        $raw = $arr_json[$i] ?? '';
        if ($raw === '' || $raw === null) {
          if ($req) throw new Exception("{$a['nombre_atributo']}: requerido.");
          else continue;
        }
        json_decode($raw);
        if (json_last_error() !== JSON_ERROR_NONE) throw new Exception("{$a['nombre_atributo']}: JSON inválido.");
        $val_json = $raw;
      } else {
        // fallback texto
        $val_texto = trim((string)($arr_texto[$i] ?? ''));
        if ($val_texto === '' && $req) throw new Exception("{$a['nombre_atributo']}: valor requerido.");
        if ($val_texto === '' && !$req) continue;
      }

      $insert->bind_param(
        'iiissidsisssis',
        $id_evidencia,
        $aid,
        $indice,
        $val_texto,
        $val_largo,
        $val_int,
        $val_decimal,
        $val_fecha,
        $val_bool,
        $val_archivo,
        $val_archivo_sha256,
        $val_archivo_mime,
        $val_archivo_bytes,
        $val_json
      );
      $insert->execute();
      if ($gs==='archivo' && is_array($metaEncolable)) {
        $documentosEncolables[]=['id_origen'=>(int)$insert->insert_id,'meta'=>$metaEncolable];
      }
      $insert_cnt++;
    }

    $insert->close();

    // Si es requerido y no insertó nada -> error
    if ($req && $insert_cnt === 0) {
      throw new Exception("{$a['nombre_atributo']}: debes capturar al menos un valor.");
    }
  }

  $stmtCurrentFiles = $conn->prepare('SELECT valor_archivo FROM evidencia_valores_atributo WHERE id_evidencia = ? AND valor_archivo IS NOT NULL AND valor_archivo <> ?');
  $stmtCurrentFiles->bind_param('is', $id_evidencia, $emptyFilename);
  $stmtCurrentFiles->execute();
  $currentFileNames = array_fill_keys(array_column($stmtCurrentFiles->get_result()->fetch_all(MYSQLI_ASSOC), 'valor_archivo'), true);
  $stmtCurrentFiles->close();
  $conn->commit();
  $resultadosProcesamiento=[];
  foreach ($documentosEncolables as $documento) {
    $resultadosProcesamiento[]=documentoProcesamientoIntentarEncolar($conn,$id_evidencia,'ATRIBUTO',$documento['id_origen'],
      $documento['meta']['sha256'],$documento['meta']['mime'],$documento['meta']['bytes']);
  }
  foreach (array_unique($oldFileNames) as $oldFileName) {
    if (!isset($currentFileNames[$oldFileName])) {
      $oldPath = seguridadResolverArchivo((string)$oldFileName);
      if ($oldPath) @unlink($oldPath);
    }
  }
  $warnings=[];
  if (!empty($duplicateWarnings['own'])) $warnings[]='Ya existe un archivo idéntico entre tus evidencias.';
  if (!empty($duplicateWarnings['other'])) $warnings[]='El archivo coincide con otro registro. No se muestran datos de otros usuarios.';
  echo json_encode(['status'=>'ok','advertencias'=>$warnings,'procesamiento'=>$resultadosProcesamiento]);
} catch (Exception $ex) {
  $conn->rollback();
  foreach ($newlyUploadedFiles ?? [] as $newFileName) {
    $newPath = ($baseUploadDir ?? '') . '/' . basename($newFileName);
    if ($baseUploadDir && is_file($newPath)) @unlink($newPath);
  }
  echo json_encode(['status'=>'error','message'=>$ex->getMessage()]);
}
