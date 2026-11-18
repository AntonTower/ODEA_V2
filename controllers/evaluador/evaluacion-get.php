<?php
// evaluacion-get.php
header('Content-Type: application/json; charset=utf-8');
if (!defined('BASE_DIR')) define('BASE_DIR', realpath(__DIR__ . '/../..'));
require_once BASE_DIR . '/config/paths.php';
require_once CONFIG_DIR . '/validacion.php';
require_once CONFIG_DIR . '/conexion.php';
require_once BASE_DIR . '/views/evaluador/permisos-instrumentos.php';
$usuarioActual = usuarioActualInstrumentos($conn);
$alcance = alcanceInstrumentos($usuarioActual);

function jexit($ok,$msg='',$extra=[]){
  echo json_encode(array_merge(['status'=>$ok?'ok':'error','message'=>$msg],$extra));
  exit;
}

$user_id  = isset($_SESSION['ID'])  ? (int)$_SESSION['ID']  : 0;
$user_role = (int)$usuarioActual['rol'];
$id       = isset($_GET['id'])      ? (int)$_GET['id']      : 0;

if ($id <= 0) jexit(false,'Parámetros inválidos');

// Datos de la evidencia
$sqlE = "SELECT e.id_evidencia, e.id_docente, e.id_tipo_evidencia, t.nombre_tipo
         FROM evidencias e
         LEFT JOIN tipos_de_evidencia t ON t.id_tipo_evidencia = e.id_tipo_evidencia
         WHERE e.id_evidencia = ? AND e.ocultar = 0";
$stmtE = $conn->prepare($sqlE);
$stmtE->bind_param('i', $id);
$stmtE->execute();
$re = $stmtE->get_result();
$evi = $re ? $re->fetch_assoc() : null;
$stmtE->close();

if (!$evi) jexit(false,'Evidencia no encontrada');

if (!in_array($user_role, [1, 2, 3, 4], true)) jexit(false, 'No autorizado.');

// Docente solo su evidencia
if ($user_role === 4 && (int)$evi['id_docente'] !== (int)$user_id) {
  jexit(false,'No autorizado');
}

// OJO: la tabla puente correcta es instrumento_tipo_evidencia
$sql = "SELECT
          i.id_instrumento,
          i.abreviatura,
          i.nombre_completo,
          /* Derivados para que el frontend NO cambie */
          CASE WHEN i.tipo_calificacion = 'NUMERICA' THEN 1 ELSE 0 END AS es_numerico,
          CASE WHEN i.tipo_calificacion = 'NUMERICA' THEN COALESCE(i.min_calificacion, 0)  ELSE 0  END AS cal_min,
          CASE WHEN i.tipo_calificacion = 'NUMERICA' THEN COALESCE(i.max_calificacion, 10) ELSE 1  END AS cal_max,
          COALESCE(ev.resultado,ce.resultado) AS resultado,
          COALESCE(ev.comentario,ce.comentario) AS comentario,
          ev.id_estado, COALESCE(ee.codigo,IF(ce.id_calificacion IS NULL,NULL,'PENDIENTE_REVISION')) AS estado_codigo, ee.nombre AS estado_nombre,
          ce.calificado_en,
          ce.actualizado_en
        FROM instrumento_tipo_evidencia ite
        JOIN instrumentos i
              ON i.id_instrumento = ite.id_instrumento AND i.activo = 1
        LEFT JOIN calificacion_evidencia ce
              ON ce.id_instrumento = i.id_instrumento
             AND ce.id_evidencia   = ?
        LEFT JOIN evaluaciones_versionadas ev
              ON ev.id_instrumento=i.id_instrumento AND ev.id_evidencia=? AND ev.id_version IS NULL
        LEFT JOIN estados_evaluacion ee ON ee.id_estado=ev.id_estado
        WHERE ($alcance) AND ite.id_tipo_evidencia = ?
        ORDER BY i.id_instrumento ASC";

$stmt = $conn->prepare($sql);
$tid  = (int)$evi['id_tipo_evidencia'];
$stmt->bind_param('iii', $id, $id, $tid);
$stmt->execute();
$rs = $stmt->get_result();

$rows = [];
if ($rs) {
  while ($r = $rs->fetch_assoc()) {
    // Normaliza tipos para el JSON
    $r['puede_evaluar'] = in_array($user_role, [1,2,3], true);
    $r['es_numerico'] = (int)$r['es_numerico'];
    if (array_key_exists('resultado', $r)) {
      if (is_null($r['resultado'])) {
        $r['resultado'] = null;
      } else {
        // Si es numérico lo regresamos float; si es aprobación (0/1) va como int pero no rompemos front.
        $r['resultado'] = ($r['es_numerico'] === 1) ? (float)$r['resultado'] : (int)$r['resultado'];
      }
    }
    $rows[] = $r;
  }
}
$stmt->close();

$estados=$conn->query('SELECT codigo,nombre FROM estados_evaluacion WHERE activo=1 ORDER BY orden,id_estado')->fetch_all(MYSQLI_ASSOC);
$versiones=$conn->query("SELECT v.id_version,v.id_convocatoria,v.codigo_version,v.nombre,
    c.id_instrumento,c.nombre AS convocatoria,p.nombre AS periodo
    FROM versiones_criterios v JOIN convocatorias c ON c.id_convocatoria=v.id_convocatoria
    JOIN periodos_academicos p ON p.id_periodo=c.id_periodo
    WHERE v.estado='PUBLICADA' AND c.estado='ACTIVA' AND p.estado='ACTIVO'")->fetch_all(MYSQLI_ASSOC);
$versionesPorInstrumento=[];
foreach ($versiones as $version) $versionesPorInstrumento[(int)$version['id_instrumento']][]=$version;
foreach ($rows as &$r) $r['versiones']=$versionesPorInstrumento[(int)$r['id_instrumento']]??[];
unset($r);
jexit(true, '', ['evidencia'=>$evi, 'instrumentos'=>$rows, 'estados'=>$estados]);
