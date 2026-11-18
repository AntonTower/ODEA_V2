<?php
/** Reglas de autorización vigentes. Roles: 1 Super Usuario, 2 Administrador,
 * 3 Evaluador, 4 Docente. No sustituye las reglas institucionales del menú. */
require_once __DIR__ . '/sesion.php';

function seguridadUsuarioActual(mysqli $conn): array
{
    iniciarSesionSegura();
    $id = (int)($_SESSION['ID'] ?? 0);
    if ($id <= 0) return ['id_usuario'=>0, 'rol'=>0];
    $stmt = $conn->prepare('SELECT u.id_usuario, u.rol, u.nombre, u.apellidop, u.apellidom, u.correo, r.nombre AS rol_nombre FROM usuarios u JOIN roles r ON r.id_rol=u.rol WHERE u.id_usuario=? AND u.activo=1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$usuario) return ['id_usuario'=>0, 'rol'=>0];
    $_SESSION['ID'] = (int)$usuario['id_usuario'];
    $_SESSION['ROL'] = (int)$usuario['rol'];
    $_SESSION['SESUSUARIO'] = $usuario['nombre'];
    $_SESSION['APELLIDO'] = trim($usuario['apellidop'].' '.$usuario['apellidom']);
    $_SESSION['EMAIL'] = $usuario['correo'];
    $_SESSION['RNOMBRE'] = $usuario['rol_nombre'];
    return ['id_usuario'=>(int)$usuario['id_usuario'], 'rol'=>(int)$usuario['rol']];
}

function seguridadTieneRol(array $usuario, array $roles): bool
{
    return (int)($usuario['id_usuario'] ?? 0) > 0 && in_array((int)($usuario['rol'] ?? 0), $roles, true);
}

function seguridadPuedeAdministrar(array $usuario): bool { return seguridadTieneRol($usuario, [1,2]); }

function seguridadPuedeAccederInstrumento(mysqli $conn, array $usuario, int $instrumento): bool
{
    if ($instrumento <= 0 || (int)($usuario['id_usuario'] ?? 0) <= 0) return false;
    if (seguridadPuedeAdministrar($usuario)) {
        $st=$conn->prepare('SELECT 1 FROM instrumentos WHERE id_instrumento=? AND activo=1');
        $st->bind_param('i',$instrumento); $st->execute(); $ok=$st->get_result()->num_rows>0; $st->close(); return $ok;
    }
    if ((int)$usuario['rol'] === 4) {
        $st=$conn->prepare('SELECT 1 FROM instrumentos WHERE id_instrumento=? AND activo=1');
    } elseif ((int)$usuario['rol'] === 3) {
        $st=$conn->prepare('SELECT 1 FROM evaluador_instrumento ei JOIN instrumentos i ON i.id_instrumento=ei.id_instrumento AND i.activo=1 WHERE ei.id_evaluador=? AND ei.id_instrumento=?');
        $uid=(int)$usuario['id_usuario']; $st->bind_param('ii',$uid,$instrumento); $st->execute(); $ok=$st->get_result()->num_rows>0; $st->close(); return $ok;
    } else return false;
    $st->bind_param('i',$instrumento); $st->execute(); $ok=$st->get_result()->num_rows>0; $st->close(); return $ok;
}

function seguridadPuedeVerEvidencia(mysqli $conn, array $usuario, array $evidencia): bool
{
    $rol=(int)($usuario['rol']??0); $uid=(int)($usuario['id_usuario']??0);
    if (!$uid || (int)($evidencia['ocultar']??0) !== 0) return false;
    if (in_array($rol,[1,2],true)) return true;
    if ($rol===4) return $uid===(int)($evidencia['id_docente']??0);
    if ($rol!==3) return false;
    $st=$conn->prepare('SELECT 1 FROM instrumento_tipo_evidencia ite JOIN evaluador_instrumento ei ON ei.id_instrumento=ite.id_instrumento JOIN instrumentos i ON i.id_instrumento=ite.id_instrumento AND i.activo=1 WHERE ite.id_tipo_evidencia=? AND ei.id_evaluador=? LIMIT 1');
    $tipo=(int)($evidencia['id_tipo_evidencia']??0); $st->bind_param('ii',$tipo,$uid); $st->execute(); $ok=$st->get_result()->num_rows>0; $st->close(); return $ok;
}

function seguridadPuedeEditarEvidencia(array $usuario, array $evidencia): bool
{
    return (int)($usuario['id_usuario']??0)>0 && (((int)($usuario['rol']??0)===1 || (int)$usuario['rol']===2) || ((int)$usuario['rol']===4 && (int)$usuario['id_usuario']===(int)($evidencia['id_docente']??0)));
}

function seguridadPuedeEvaluar(mysqli $conn, array $usuario, array $evidencia, int $instrumento): bool
{
    if (!seguridadPuedeAccederInstrumento($conn,$usuario,$instrumento)) return false;
    if (!in_array((int)($usuario['rol']??0),[1,2,3],true)) return false;
    $st=$conn->prepare('SELECT 1 FROM instrumento_tipo_evidencia WHERE id_instrumento=? AND id_tipo_evidencia=?');
    $tipo=(int)($evidencia['id_tipo_evidencia']??0); $st->bind_param('ii',$instrumento,$tipo); $st->execute(); $ok=$st->get_result()->num_rows>0; $st->close(); return $ok;
}

function seguridadResolverArchivo(string $nombre): ?string
{
    if ($nombre==='' || basename($nombre)!==$nombre || $nombre==='.' || $nombre==='..' || strpos($nombre,"\0")!==false) return null;
    $raiz=realpath(BASE_DIR.'/uploads/files');
    if (!$raiz) return null;
    $ruta=realpath($raiz.DIRECTORY_SEPARATOR.$nombre);
    $prefix=rtrim($raiz,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    if (!$ruta || !is_file($ruta) || !is_readable($ruta) || strncasecmp($ruta,$prefix,strlen($prefix))!==0) return null;
    return $ruta;
}
