<?php
require_once __DIR__.'/../../config/paths.php';
require_once CONFIG_DIR.'/validacion.php';
require_once CONFIG_DIR.'/conexion.php';
require_once CONFIG_DIR.'/seguridad.php';
require_once CONFIG_DIR.'/tableros.php';

$usuarioActual=seguridadUsuarioActual($conn);
if((int)$usuarioActual['id_usuario']<=0){http_response_code(401);exit('Sesión no válida.');}
include COMPONENTS_DIR.'/layout/header.php';
include COMPONENTS_DIR.'/layout/left-menu.php';

if(in_array((int)$usuarioActual['rol'],[1,2],true)) include __DIR__.'/dashboard-administrador.php';
elseif((int)$usuarioActual['rol']===3) include __DIR__.'/dashboard-evaluador.php';
elseif((int)$usuarioActual['rol']===4) include __DIR__.'/dashboard-docente.php';
else { http_response_code(403); echo '<section class="card"><div class="card-content">Sin acceso al tablero.</div></section>'; }

include COMPONENTS_DIR.'/layout/footer.php';
?>
