<?php
/**
 * Vista secundaria para la tabla de gestión de usuarios (Cargada vía AJAX)
 */

// Definición de constantes de rutas
if (!defined('BASE_DIR')) {
    define('BASE_DIR', realpath(__DIR__ . '/../../'));
    define('CONFIG_DIR', BASE_DIR . '/config');
    define('COMPONENTS_DIR', BASE_DIR . '/components');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', '/ODEA/gestor_evidencias');
}

require_once CONFIG_DIR . '/validacion.php';
require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/validacion-permiso.php';

$role = isset($_GET['role']) ? trim($_GET['role']) : '';
$hasRole = ($role !== '' && is_numeric($role));

// Verificar si existe la tabla evaluador_instrumento para evitar errores de consulta
$hasTableEI = false;
$checkEI = $conn->query("SHOW TABLES LIKE 'evaluador_instrumento'");
if ($checkEI && $checkEI->num_rows > 0) {
    $hasTableEI = true;
}

$subqueryInst = $hasTableEI 
  ? "(SELECT GROUP_CONCAT(i.abreviatura ORDER BY i.id_instrumento SEPARATOR ', ') 
      FROM evaluador_instrumento ei 
      JOIN instrumentos i ON i.id_instrumento = ei.id_instrumento 
      WHERE ei.id_evaluador = u.id_usuario)" 
  : "NULL";

$sql = "SELECT 
          u.id_usuario,
          u.nombre,
          u.apellidop,
          u.apellidom,
          u.correo,
          u.rol,
          {$subqueryInst} AS instrumentos_asignados,
          u.activo,
          r.nombre AS nombrerol,
          u.created_at AS creacion,
          u.updated_at AS actualizacion
        FROM usuarios u
        LEFT JOIN roles r ON r.id_rol = u.rol
        WHERE 1=1 ";

$params = [];
$types = '';

if ($hasRole) {
    $sql .= " AND u.rol = ? ";
    $params[] = (int)$role;
    $types .= 'i';
}

$sql .= " ORDER BY u.id_usuario ASC ";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo '<div class="card-content"><p class="text-muted-foreground">Error al preparar la consulta: ' . htmlspecialchars($conn->error) . '</p></div>';
    exit;
}

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<div class="card-content" style="overflow:hidden;">
  <table id="tabla-usuarios" class="table display nowrap" style="width:100%;">
    <thead>
      <tr>
        <!-- Columna de control para Responsive -->
        <th data-priority="1"></th>

        <th class="dt-orderable" data-priority="2">ID</th>
        <th class="dt-orderable" data-priority="1">Nombre</th>
        <th class="dt-orderable" data-priority="3">Correo</th>
        <th class="dt-orderable" data-priority="3">Rol</th>
        <th class="dt-orderable" data-priority="4">Estado</th>
        <th class="dt-orderable" data-priority="5">Creación</th>
        <th class="dt-orderable" data-priority="5">Actualización</th>
        <th>Instrumentos asignados</th>
        <th data-priority="2">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()):
          $id        = (int)$row['id_usuario'];
          $nombre    = trim(($row['nombre'] ?? '').' '.($row['apellidop'] ?? '').' '.($row['apellidom'] ?? ''));
          $correo    = $row['correo'] ?? '';
          $rolId     = isset($row['rol']) ? (int)$row['rol'] : null;
          $rolNombre = $row['nombrerol'] ?? '—';
          $activo    = (int)($row['activo'] ?? 0);
          $crea      = $row['creacion'] ? date('Y-m-d H:i', strtotime($row['creacion'])) : '—';
          $actu      = $row['actualizacion'] ? date('Y-m-d H:i', strtotime($row['actualizacion'])) : '—';

          $estadoBadge = $activo === 1
            ? '<span class="badge default" title="Usuario activo">Activo</span>'
            : '<span class="badge secondary" title="Usuario inactivo">Inactivo</span>';

          // Botón "Ver perfil"
          $btnPerfil = '
            <button class="btn" type="button" title="Ver perfil"
                    onclick="openVerPerfil('.$id.')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" stroke="#10b981" stroke-width="1.8" fill="none"/>
                <path d="M2 21a10 10 0 0 1 20 0" stroke="#10b981" stroke-width="1.8" fill="none"/>
              </svg>
            </button>';

          // Editar
          $btnEdit = '
            <button class="btn" type="button" title="Editar"
                    onclick="openEditarUsuario('.$id.')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M3 17.25V21h3.75L19.81 7.94l-3.75-3.75L3 17.25z" stroke="#3b82f6" stroke-width="1.8" fill="none"/>
                <path d="M14.06 4.19l3.75 3.75" stroke="#3b82f6" stroke-width="1.8"/>
              </svg>
            </button>';

          // Eliminar
          $safeName = htmlspecialchars($nombre, ENT_QUOTES);
          $btnDelete = '
            <button class="btn" type="button" title="Eliminar"
                    onclick="openEliminarU('.$id.', \''.$safeName.'\')">
              <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true" style="width:18px;height:18px;">
                <path d="M3 6h18M8 6l1-2h6l1 2M6 6l1 14h10l1-14" stroke="#ef4444" stroke-width="1.8" stroke-linecap="round"/>
                <path d="M10 11v6M14 11v6" stroke="#ef4444" stroke-width="1.8" stroke-linecap="round"/>
              </svg>
            </button>';

          $correoHtml = $correo ? '<a href="mailto:'.htmlspecialchars($correo).'" class="link">'.htmlspecialchars($correo).'</a>' : '—';
        ?>
        <tr>
          <td></td>
          <td><?= $id ?></td>
          <td class="font-medium"><?= htmlspecialchars($nombre ?: '—') ?></td>
          <td><?= $correoHtml ?></td>
          <td>
            <span class="badge" title="<?= htmlspecialchars($rolNombre) ?>">
              <?= htmlspecialchars(($rolId !== null ? $rolId : '—').' - '.$rolNombre) ?>
            </span>
          </td>
          <td><?= $estadoBadge ?></td>
          <td><span class="badge" title="<?= htmlspecialchars($row['creacion'] ?? '') ?>"><?= htmlspecialchars($crea) ?></span></td>
          <td><span class="badge" title="<?= htmlspecialchars($row['actualizacion'] ?? '') ?>"><?= htmlspecialchars($actu) ?></span></td>
          <td><?= htmlspecialchars($rolId === 3 ? ($row['instrumentos_asignados'] ?: 'Sin asignaciones') : 'No aplica') ?></td>
          <td style="display:flex; gap:8px;">
            <?= $btnPerfil ?>
            <?= $btnEdit ?>
            <?= $btnDelete ?>
          </td>
        </tr>
        <?php endwhile; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>