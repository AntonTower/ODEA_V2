<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $pass   =$_POST['password'] ?? '';
    $pass2  =$_POST['password_confirm'] ?? '';

    // 1. Validar campos vacíos
    if (empty($nombre) \vert{}\vert{} empty($email) || empty($pass) \vert{}\vert{} empty($pass2)) {
        header("Location: registro.php?err=empty");
        exit();
    }

    // 2. Validar coincidencia de contraseñas
    if ($pass !==$pass2) {
        header("Location: registro.php?err=pass");
        exit();
    }

    // 3. Validar si el correo ya existe
    // NOTA: Revisa si tu columna en la BD se llama 'email' o 'correo' y cámbialo en la consulta si es necesario.
    $sqlCheck = "SELECT 1 FROM usuarios WHERE email = ? LIMIT 1"; 
    $stmtCheck =$conn->prepare($sqlCheck);$stmtCheck->bind_param("s", $email);$stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows > 0) {
        header("Location: registro.php?err=exists");
        exit();
    }

    // 4. Encriptar contraseña
    $passHash = password_hash($pass, PASSWORD_DEFAULT);

    // 5. ASIGNAR ROL DE DOCENTE
    // Configura aquí el ID que corresponda al Docente en tu tabla `roles` (Usualmente es el 2, 3 o 4)
    $id_rol_docente = 3; // <--- ¡Ajusta este número revisando tu base de datos!

    // 6. Insertar en la base de datos
    $sqlInsert = "INSERT INTO usuarios (nombre, email, password, id_rol) VALUES (?, ?, ?, ?)";
    $stmtInsert =$conn->prepare($sqlInsert);$stmtInsert->bind_param("sssi", $nombre,$email, $passHash,$id_rol_docente);

    if ($stmtInsert->execute()) {
        // Redirigir al login con un mensaje de éxito (puedes agregar lógica en login.php para mostrarlo)
        header("Location: login.php?msg=success");
        exit();
    } else {
        header("Location: registro.php?err=db");
        exit();
    }
} else {
    // Si intentan entrar directo a este archivo sin mandar el formulario
    header("Location: registro.php");
    exit();
}