<?php
/**
 * Vista de la tabla de evidencias (DataTables + Responsive)
 * - Filtro por tipo de evidencia con ?tipo=ID
 * - Rol 4 (docentes): solo sus evidencias.
 * - Columna "Avance" con % de atributos capturados (valores_evidencia).
 */

// Definición autónoma de constantes de rutas
if (!defined('BASE_DIR')) {
    define('BASE_DIR', realpath(__DIR__ . '/../../'));
    define('CONFIG_DIR', BASE_DIR . '/config');
    define('COMPONENTS_DIR', BASE_DIR . '/components');
}

require_once __DIR__ . '/../../config/paths.php';

require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/validacion.php';
require_once CONFIG_DIR . '/evidencias.php';
require_once CONFIG_DIR . '/procesamiento_documental.php';

$usuarioActual = evidenciaUsuarioActual($conn);
$user_id = (int)$usuarioActual['id_usuario'];
$user_role = (int)$usuarioActual['rol'];
$processingReady=documentoProcesamientoDisponible($conn);
$processingSelect=$processingReady?',pt.id_trabajo AS procesamiento_id,pt.estado AS procesamiento_estado,pt.procesado_en AS procesamiento_fecha':",NULL AS procesamiento_id,NULL AS procesamiento_estado,NULL AS procesamiento_fecha";
$processingJoin=$processingReady?"LEFT JOIN (
          SELECT DISTINCT o.id_evidencia,t.id_trabajo,t.estado,t.procesado_en
          FROM origenes_trabajo_documental o JOIN trabajos_documentales t ON t.id_trabajo=o.id_trabajo
          WHERE t.id_trabajo=(SELECT MAX(t2.id_trabajo) FROM origenes_trabajo_documental o2
            JOIN trabajos_documentales t2 ON t2.id_trabajo=o2.id_trabajo WHERE o2.id_evidencia=o.id_evidencia)
        ) pt ON pt.id_evidencia=e.id_evidencia":"";
if ($user_id <= 0 || !in_array($user_role, [1, 2, 3, 4], true)) {
  http_response_code($user_id <= 0 ? 401 : 403);
  exit('<div class="card-content"><p>No tienes permiso para consultar estas evidencias.</p></div>');
}

$tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
$hasTipo = ($tipo !== '' && is_numeric($tipo));

// Subqueries para total de atributos del tipo y atributos con valor por evidencia
$sql = "SELECT
          e.id_evidencia, e.titulo, e.fecha_subida, e.archivo,
          u.id_usuario AS id_docente, u.nombre, u.apellidop, u.apellidom,
          t.id_tipo_evidencia, t.nombre_tipo,
          COALESCE(ta.total, 0)  AS total_attrs,
          COALESCE(vv.filled, 0) AS filled_attrs
          $processingSelect
        FROM evidencias e
        LEFT JOIN usuarios u ON e.id_docente = u.id_usuario
        LEFT JOIN tipos_de_evidencia t ON t.id_tipo_evidencia = e.id_tipo_evidencia
        LEFT JOIN (
          SELECT id_tipo_evidencia, COUNT(*) AS total
          FROM atributos_tipo_evidencia
          WHERE requerido = 1
          GROUP BY id_tipo_evidencia
        ) ta ON ta.id_tipo_evidencia = e.id_tipo_evidencia
        LEFT JOIN (
          SELECT v.id_evidencia, COUNT(DISTINCT v.id_ate) AS filled
          FROM evidencia_valores_atributo v
          INNER JOIN atributos_tipo_evidencia a ON a.id_ate=v.id_ate AND a.requerido=1
          GROUP BY v.id_evidencia
        ) vv ON vv.id_evidencia = e.id_evidencia
        $processingJoin
        WHERE e.ocultar = 0 ";

$params = [];
$types  = '';

if ($user_role === 4) {
  // Docente: solo sus evidencias
  $sql .= " AND e.id_docente = ? ";
  $params[] = $user_id;
  $types   .= 'i';
} elseif ($user_role === 3) {
  // Evaluador: solo evidencias de tipos asociados a instrumentos que tiene asignados.
  $sql .= " AND EXISTS (
              SELECT 1
              FROM instrumento_tipo_evidencia ite
              INNER JOIN evaluador_instrumento ei ON ei.id_instrumento = ite.id_instrumento
              INNER JOIN instrumentos i ON i.id_instrumento = ite.id_instrumento AND i.activo = 1
              WHERE ite.id_tipo_evidencia = e.id_tipo_evidencia AND ei.id_evaluador = ?
            ) ";
  $params[] = $user_id;
  $types   .= 'i';
}

if ($hasTipo) {
  $sql .= " AND e.id_tipo_evidencia = ? ";
  $params[] = (int)$tipo;
  $types   .= 'i';
}

$sql .= " ORDER BY e.id_evidencia DESC ";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo '<div class="card-content"><p>Error de preparación de consulta.</p></div>';
  exit;
}
if ($params) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<style>
/* Barra simple de progreso para la columna Avance */
.progress {
  width: 120px; height: 10px; border-radius: 999px;
  background: var(--muted, #eee);
  overflow: hidden; display: inline-block; vertical-align: middle;
}
.progress-bar {
  height: 100%;
  background: #10b981; /* verde */
}
</style>

<div class="card-content" style="overflow:hidden;">
  <table id="tabla-evidencias" class="table display nowrap" style="width:100%;">
    <thead>
      <tr>
        <th data-priority="1"></th>
        <th class="dt-orderable" data-priority="2">ID</th>
        <th class="dt-orderable" data-priority="1">Título</th>
        <th class="dt-orderable" data-priority="3">Tipo</th>
        <th class="dt-orderable" data-priority="3">Docente</th>
        <th class="dt-orderable" data-priority="4">Archivo</th>
        <th class="dt-orderable" data-priority="4">Fecha</th>
        <th class="dt-orderable" data-priority="3">Avance</th>
        <th data-priority="3">Procesamiento</th>
        <th data-priority="2">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()):
          $id    = (int)$row['id_evidencia'];
          $tit   = $row['titulo'] ?? '';
          $tipoN = $row['nombre_tipo'] ?? '—';
          $docN  = trim(($row['nombre'] ?? '').' '.($row['apellidop'] ?? '').' '.($row['apellidom'] ?? ''));
          $file  = $row['archivo'] ?? '';
          $processingState=(string)($row['procesamiento_estado']??'SIN_ENCOLAR');
          $processingLabels=['PENDIENTE'=>'Pendiente','EN_PROCESO'=>'En proceso','PROCESADO'=>'Procesado','REQUIERE_REVISION'=>'Requiere revisión','ERROR'=>'Error','CANCELADO'=>'Cancelado','SIN_ENCOLAR'=>'Sin procesar'];
          $processingLabel=$processingLabels[$processingState]??'Estado desconocido';
          $date  = $row['fecha_subida'] ? date('Y-m-d H:i', strtotime($row['fecha_subida'])) : '—';

          $totalAttrs  = (int)$row['total_attrs'];
          $filledAttrs = (int)$row['filled_attrs'];

          // Sintaxis corregida del porcentaje
          $pct = ($totalAttrs > 0) ? round(($filledAttrs * 100) / $totalAttrs) : 100;
          if ($pct < 0) $pct = 0; 
          if ($pct > 100) $pct = 100;

          // Ruta corregida usando BASE_URL
          $href = $file ? (BASE_URL . '/controllers/docente/evidencia-archivo.php?id=' . $id . '&descargar=1') : '';

          // Botón Descargar
          $btnDownload = $file ? '
            <a class="btn" href="'.$href.'" title="Descargar archivo">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M12 3v12M7 10l5 5 5-5M5 19h14" stroke="#111827" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </a>' : '—';

          // Acciones
          $btnDetalles = '
            <button class="btn" type="button" title="Ver detalles"
                    data-evidence-action="details" data-id="'.$id.'">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M4 6h16M4 12h16M4 18h16" stroke="#10b981" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
            </button>';

          // Editar: solo docentes (su propia evidencia)
          $canEdit = in_array($user_role, [1, 2], true)
            || ((int)$user_role === 4 && (int)$row['id_docente'] === $user_id);
          $btnEdit = $canEdit ? '
            <button class="btn" type="button" title="Editar" data-evidence-action="edit" data-id="'.$id.'">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M3 17.25V21h3.75L19.81 7.94l-3.75-3.75L3 17.25z" stroke="#3b82f6" stroke-width="1.8" fill="none"/>
                <path d="M14.06 4.19l3.75 3.75" stroke="#3b82f6" stroke-width="1.8"/>
              </svg>
            </button>' : '';

          // Eliminar (soft): admin cualquiera; docente solo la suya
          $canDelete = in_array($user_role, [1, 2], true)
            || ((int)$user_role === 4 && (int)$row['id_docente'] === $user_id);
          $safeTit = htmlspecialchars($tit, ENT_QUOTES, 'UTF-8');
          $btnDelete = $canDelete ? '
            <button class="btn" type="button" title="Ocultar" data-evidence-action="delete" data-id="'.$id.'" data-title="'.$safeTit.'">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M3 6h18M8 6l1-2h6l1 2M6 6l1 14h10l1-14" stroke="#ef4444" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M10 11v6M14 11v6" stroke="#ef4444" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
            </button>' : '';

        ?>
        <tr>
          <td></td>
          <td><?= $id ?></td>
          <td class="font-medium"><?= htmlspecialchars($tit ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
          <td><span class="badge"><?= htmlspecialchars($tipoN, ENT_QUOTES, 'UTF-8') ?></span></td>
          <td><?= htmlspecialchars($docN ?: '—', ENT_QUOTES, 'UTF-8') ?></td>

          <!-- Botón descargar -->
          <td><?= $btnDownload ?></td>

          <td><span class="badge" title="<?= htmlspecialchars($row['fecha_subida'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></span></td>

          <!-- Avance -->
          <td title="<?= $filledAttrs ?>/<?= $totalAttrs ?> campos obligatorios">
            <div class="progress" aria-label="Avance de captura">
              <div class="progress-bar" style="width: <?= $pct ?>%;"></div>
            </div>
            <span style="margin-left:8px; font-size:12px; color:var(--muted-foreground,#555);"><?= $pct ?>%</span>
          </td>

          <td>
            <span class="badge" title="<?= htmlspecialchars((string)($row['procesamiento_fecha']??''),ENT_QUOTES,'UTF-8') ?>"><?= htmlspecialchars($processingLabel,ENT_QUOTES,'UTF-8') ?></span>
            <button class="btn" type="button" title="Consultar procesamiento" data-evidence-action="processing" data-id="<?= $id ?>">Detalles</button>
          </td>

          <td style="display:flex; gap:8px;">
            <?= $btnDetalles ?>
            <?= $btnEdit ?>
            <?= $btnDelete ?>
          </td>
        </tr>
        <?php endwhile; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
