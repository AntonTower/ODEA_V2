# Gestor de Evidencias Académicas

Sistema web para organizar evidencias académicas, administrar tipos de evidencia e instrumentos, y facilitar su evaluación por docentes y evaluadores. El proyecto utiliza PHP, MySQL/MariaDB, HTML, CSS y JavaScript.

## 1. Requisitos

- XAMPP con Apache, PHP 8.1 o superior y MySQL/MariaDB.
- phpMyAdmin para crear o importar la base de datos.
- Extensiones PHP `mysqli`, `fileinfo`, `mbstring` y `zip` habilitadas.

En la configuración actual, PHP intenta conectarse a `127.0.0.1:3308` con el usuario `root` sin contraseña y usa por defecto la base `gestor_evidencia_produccion`. Revisa estos valores en `config/conexion.php` para que coincidan con los de tu XAMPP. Si MySQL usa otro puerto o nombre de base, actualiza `$port` y `$database` en ese archivo.

## 2. Instalación con XAMPP y phpMyAdmin

1. Copia la carpeta `gestor_evidencias` dentro de `C:\xampp\htdocs\`.
2. Abre el panel de XAMPP e inicia **Apache** y **MySQL**.
3. Abre phpMyAdmin en [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
4. Importa el esquema que corresponda a tu instalación:
   - Para una instalación nueva: crea una base vacía `gestor_evidencia_produccion` y selecciona/importa `sql/gestor_evidencia_produccion_v2.sql`.
   - Para agregar datos ficticios de ejemplo, sigue `sql/prueba/README_demo.md` después de instalar v2.
5. En phpMyAdmin, selecciona la base de datos y abre la pestaña **Importar**. Elige el archivo SQL y ejecuta la importación.
6. Comprueba en `config/conexion.php` que host, puerto, nombre de base, usuario y contraseña correspondan a tu instalación local.
7. Asegúrate de que exista `uploads/files` y que Apache pueda guardar archivos en esa carpeta.
8. Abre el sistema en:

   [http://localhost/gestor_evidencias/public/login.php](http://localhost/gestor_evidencias/public/login.php)

El archivo `index.php` redirige a la pantalla de inicio de sesión. No se necesita Node.js ni Composer para ejecutar la aplicación.

## 3. Usuarios de prueba

El volcado SQL contiene cuentas y evidencias de demostración heredadas. No publiques ni uses esos registros en instalaciones accesibles desde Internet. Para una instalación real, importa el esquema únicamente en una base aislada, revisa y sustituye los usuarios de muestra y define contraseñas individuales antes de habilitar el acceso. No se documentan credenciales compartidas.

## 4. Funciones principales

- **Super Usuario y Administrador:** gestión de usuarios, tipos de evidencia e instrumentos.
- **Docente:** carga, organización y edición de evidencias; consulta de calificaciones recibidas.
- **Evaluador:** consulta evidencias disponibles y registra evaluaciones según sus permisos e instrumentos asignados.
- **Todos los perfiles:** acceso al resumen y al perfil, con opciones limitadas por rol.
- Carga y descarga de archivos de evidencia; descarga de paquetes ZIP si la extensión PHP `zip` está activa.
- Generación de reportes PDF mediante FPDF cuando la librería está disponible.

Las opciones visibles están definidas por rol en `config/menu_config.php`; el servidor valida permisos en las vistas y controladores.

## 5. Estructura del proyecto

- `public/`: inicio de sesión, registro, cierre de sesión y acceso al sistema.
- `views/`: pantallas de administración, docentes, evaluación y secciones comunes.
- `controllers/`: operaciones de usuarios, evidencias y evaluaciones.
- `config/`: conexión a base de datos, rutas, validaciones y permisos.
- `components/`: plantillas, menús, encabezados, pies de página y ventanas modales.
- `sql/`: esquemas y respaldos de base de datos.
- `styles/` y `assets/`: estilos y recursos de interfaz.
- `uploads/files/`: archivos adjuntos de evidencias.
- `fpdf/`: librería opcional para generar PDF; debe estar presente para usar esa función.
- `docs/`: documentación técnica y notas del proyecto.

## 6. Base de datos y archivos

MySQL/MariaDB guarda usuarios, roles, evidencias, atributos y calificaciones. Los documentos cargados se guardan como archivos dentro de `uploads/`; no quedan incluidos en los archivos SQL. Conserva respaldos tanto de la base de datos como de esa carpeta para mantener una copia completa.

El límite de carga definido por la aplicación es de 10 MB por archivo. En `php.ini`, configura `upload_max_filesize` en al menos `10M` y `post_max_size` en al menos `12M`.

## 7. Documentación adicional

- [Dependencias e instalación detallada](docs/DEPENDENCIAS.md)
- [Documentación técnica](docs/DOCUMENTACION_TECNICA.md)
- [Instalación y documentación de la base v2](sql/README_produccion.md)
- [Datos de demostración](sql/prueba/README_demo.md)
- [Informe de validación integral](docs/INFORME_VALIDACION_FINAL_ODEA.md)
- [Manual del docente](docs/MANUAL_DOCENTE.md)
- [Manual del evaluador](docs/MANUAL_EVALUADOR.md)
- [Manual administrativo](docs/MANUAL_ADMINISTRADOR.md)

## 8. Seguridad

El dump SQL heredado incluye cuentas de demostración activas. No reutilices esas cuentas fuera de un laboratorio aislado; reemplázalas y rota cualquier credencial coincidente antes de desplegar. Configura un usuario MySQL de privilegio mínimo y secretos privados en el entorno del servicio web.
