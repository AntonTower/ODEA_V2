<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/paths.php';
require_once CONFIG_DIR . '/conexion.php';
require_once CONFIG_DIR . '/gestion_academica.php';

function academicaApiError(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['status'=>'error','message'=>$message], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    academicaUsuarioAdmin($conn);
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        header('Allow: GET');
        academicaApiError(405, 'Método no permitido.');
    }
    if (!academicaTablasInstaladas($conn)) academicaApiError(503, 'El modelo académico todavía no está instalado en esta base. Revisa y aplica la migración en el entorno de desarrollo autorizado.');

    $data = [];
    $queries = [
        'instrumentos' => 'SELECT id_instrumento,abreviatura,nombre_completo,activo FROM instrumentos WHERE activo=1 ORDER BY abreviatura',
        'tipos_documentales' => 'SELECT id_tipo_evidencia,nombre_tipo FROM tipos_de_evidencia ORDER BY nombre_tipo',
        'periodos' => 'SELECT p.*,i.abreviatura AS instrumento FROM periodos_academicos p JOIN instrumentos i USING(id_instrumento) ORDER BY p.id_periodo DESC',
        'convocatorias' => 'SELECT c.*,p.codigo AS periodo_codigo,i.abreviatura AS instrumento FROM convocatorias c JOIN periodos_academicos p ON p.id_periodo=c.id_periodo JOIN instrumentos i ON i.id_instrumento=c.id_instrumento ORDER BY c.id_convocatoria DESC',
        'versiones' => 'SELECT v.*,c.codigo AS convocatoria_codigo,c.nombre AS convocatoria,i.abreviatura AS instrumento FROM versiones_criterios v JOIN convocatorias c USING(id_convocatoria) JOIN instrumentos i USING(id_instrumento) ORDER BY v.id_version DESC',
        'categorias' => 'SELECT ca.*,v.codigo_version,v.id_convocatoria FROM categorias_academicas ca JOIN versiones_criterios v USING(id_version) ORDER BY ca.id_categoria DESC',
        'criterios' => 'SELECT cr.*,ca.nombre AS categoria,v.codigo_version,v.id_convocatoria FROM criterios_academicos cr JOIN categorias_academicas ca ON ca.id_categoria=cr.id_categoria JOIN versiones_criterios v ON v.id_version=cr.id_version ORDER BY cr.id_criterio DESC',
        'subcriterios' => 'SELECT s.*,cr.nombre AS criterio,v.codigo_version,v.id_convocatoria FROM subcriterios_academicos s JOIN criterios_academicos cr ON cr.id_criterio=s.id_criterio JOIN versiones_criterios v ON v.id_version=s.id_version ORDER BY s.id_subcriterio DESC',
        'requisitos' => 'SELECT r.*,v.codigo_version,v.id_convocatoria FROM requisitos_documentales r JOIN versiones_criterios v USING(id_version) ORDER BY r.id_requisito DESC',
        'fuentes' => 'SELECT f.*,v.codigo_version,v.id_convocatoria,CONCAT_WS(" ",u.nombre,u.apellidop,u.apellidom) AS responsable FROM fuentes_normativas f JOIN versiones_criterios v USING(id_version) LEFT JOIN usuarios u ON u.id_usuario=f.id_responsable_validacion ORDER BY f.id_fuente DESC'
    ];
    foreach ($queries as $key=>$sql) {
        $result=$conn->query($sql);
        $data[$key]=$result->fetch_all(MYSQLI_ASSOC);
    }
    echo json_encode(['status'=>'ok','data'=>$data], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $e) {
    if (http_response_code() === 403) academicaApiError(403, 'No tienes permisos para consultar la configuración académica.');
    error_log('Error en catálogo académico: '.$e->getMessage());
    academicaApiError(500, 'No se pudo consultar la configuración académica.');
}
