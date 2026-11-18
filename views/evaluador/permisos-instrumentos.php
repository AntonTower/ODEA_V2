<?php
require_once __DIR__ . '/../../config/csrf.php';
require_once __DIR__ . '/../../config/seguridad.php';
// Consultar siempre la cuenta vigente: una sesion antigua no conserva permisos revocados.
function usuarioActualInstrumentos(mysqli $conn): array {
    return seguridadUsuarioActual($conn);
}

function exigirGestion(mysqli $conn, string $pagina): void {
    $u = usuarioActualInstrumentos($conn);
    $rol = (int)$u['rol'];
    $permitido = seguridadPuedeAdministrar($u) && $rol === 1;
    if (!$permitido && $rol === 2) {
        require_once __DIR__ . '/../../config/menu_config.php';
        foreach ($MENU_CONFIG as $item) {
            if (strtolower(basename($item['ruta'])) === strtolower(basename($pagina))
                && in_array($rol, $item['roles'], true)) {
                $permitido = true;
                break;
            }
        }
    }
    if (!$permitido) {
        http_response_code(403);
        echo json_encode(['status'=>'error', 'message'=>'No autorizado para administrar este catalogo.']);
        exit;
    }
    if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        csrf_exigir();
    }
}

// Fragmento SQL interno; alias e identificadores provienen exclusivamente del codigo.
function alcanceInstrumentos(array $usuario, string $alias = 'i'): string {
    $rol = (int)$usuario['rol'];
    $id = (int)$usuario['id_usuario'];
    if (in_array($rol, [1, 2, 4], true)) return '1=1';
    if ($rol !== 3 || !$id) return '1=0';
    return "EXISTS (SELECT 1 FROM evaluador_instrumento ei WHERE ei.id_evaluador=$id AND ei.id_instrumento=$alias.id_instrumento)";
}

function guardarInstrumentosEvaluador(mysqli $conn, int $id, int $rol, $instrumentos): void {
    $st = $conn->prepare('DELETE FROM evaluador_instrumento WHERE id_evaluador=?');
    $st->bind_param('i', $id);
    $st->execute();
    $st->close();

    // Las asignaciones solo existen para cuentas con rol Evaluador (3).
    if ($rol !== 3) return;
    if (!is_array($instrumentos)) throw new InvalidArgumentException('Instrumentos invalidos.');
    $ids = [];
    foreach ($instrumentos as $valor) {
        if (!is_scalar($valor) || !ctype_digit((string)$valor) || (int)$valor <= 0) {
            throw new InvalidArgumentException('Instrumento invalido.');
        }
        $ids[(int)$valor] = (int)$valor;
    }
    $st = $conn->prepare('INSERT INTO evaluador_instrumento (id_evaluador, id_instrumento) VALUES (?, ?)');
    foreach ($ids as $iid) {
        $st->bind_param('ii', $id, $iid);
        $st->execute(); // La FK rechaza IDs inexistentes; el llamador revierte toda la transaccion.
    }
    $st->close();
}
