<?php
// atributo-tipo-get.php
header('Content-Type: application/json; charset=utf-8');
if (!defined('BASE_DIR')) {
  define('BASE_DIR', realpath(__DIR__ . '/../..'));
}
require_once BASE_DIR . '/config/paths.php';
require_once CONFIG_DIR . '/conexion.php';
require_once BASE_DIR . '/views/evaluador/permisos-instrumentos.php';
exigirGestion($conn, 'tipos-evidencia.php');

function jexit($ok, $msg = '', $extra = []) {
  echo json_encode(array_merge(['status' => $ok ? 'ok' : 'error', 'message' => $msg], $extra));
  exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) jexit(false, 'ID inválido.');

$sql = "SELECT
          id_ate, id_tipo_evidencia, id_tipo_atributo, nombre_atributo, slug,
          descripcion, orden, requerido, unico_por_evidencia, multiple,
          min_longitud, max_longitud, min_valor, max_valor, opciones_json
        FROM atributos_tipo_evidencia
        WHERE id_ate = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
  jexit(true, '', ['atributo' => $row]);
}
jexit(false, 'Atributo no encontrado.');
