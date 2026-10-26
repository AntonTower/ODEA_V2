# ODEA - Gestor de Evidencias Académicas

Gestor de evidencias académicas para la evaluación docente, trabajo de tesina en conjunto con la Benemérita Universidad Autónoma de Puebla (BUAP).

Este sistema web permite a los docentes cargar y organizar documentos probatorios, al mismo tiempo que facilita a los evaluadores e institutos revisar, calificar y administrar los instrumentos de evaluación académica (como SNII, PRODEP y ESDEPED).

## Arquitectura y Estructura de Directorios

El sistema está organizado en una arquitectura modular orientada a la separación de responsabilidades (MVC simplificado) y control de accesos basado en roles:

*   **`/assets`**: Recursos multimedia, íconos y favicon del proyecto.
*   **`/components`**: Elementos de interfaz modulares y reutilizables.
    *   `/layout`: Archivos estructurales de la página (header, footer, menús laterales, plantillas base).
    *   `/modals`: Ventanas modales para operaciones de creación, edición y eliminación (CRUD).
    *   `/ui`: Galerías de íconos y componentes visuales menores.
*   **`/config`**: Archivos críticos de configuración, incluyendo la conexión a la base de datos (`conexion.php`) y la capa de seguridad y permisos de sesión (`validacion.php`, `validacion-permiso.php`).
*   **`/controllers`**: Lógica principal del backend y operaciones a la base de datos (APIs, endpoints y controladores). Está dividida estrictamente por los permisos de ejecución:
    *   `/admin`: Gestión profunda del sistema, usuarios, instrumentos y configuración dinámica del menú.
    *   `/comunes`: Endpoints compartidos como autenticación y descarga de archivos.
    *   `/docente`: Carga, actualización y eliminación de evidencias personales.
    *   `/evaluador`: Procesamiento y guardado de calificaciones.
*   **`/docs`**: Documentación técnica del proyecto, manuales de contexto, dependencias y bitácora de cambios (CHANGELOG).
*   **`/fpdf`**: Librería de terceros para la generación dinámica de comprobantes de calificación en formato PDF.
*   **`/public`**: Puntos de acceso públicos que no requieren sesión activa o gestionan el inicio de la misma (login, registro, scripts de acceso).
*   **`/sql`**: Scripts y respaldos de la base de datos, incluyendo la estructura para el entorno de producción (`gestor_evidencia_produccion.sql`) y datos de prueba.
*   **`/styles`**: Hojas de estilos en cascada (CSS) para la personalización de la interfaz.
*   **`/tests`**: Entornos de prueba para el desarrollo de nuevas características.
*   **`/views`**: Interfaces gráficas donde interactúa el usuario final, segmentadas por su nivel de acceso al sistema:
    *   `/admin`: Pantallas de control maestro e inventario de tipos de evidencia.
    *   `/comunes`: Vistas transversales como el dashboard de inicio y la edición del perfil de usuario.
    *   `/docente`: Tableros de trabajo para cargar archivos y visualizar las calificaciones recibidas.
    *   `/evaluador`: Pantallas especializadas para auditar los documentos bajo los criterios de los instrumentos activos.

## Roles del Sistema

El acceso a las vistas y controladores está regulado por cuatro perfiles principales:
1.  **Super Usuario**: Control absoluto del sistema y base de datos.
2.  **Administrador**: Gestión operativa de usuarios, catálogos de evidencias, estructuración de menús e instrumentos.
3.  **Evaluador**: Visualización de evidencias asignadas y capacidad exclusiva para emitir dictámenes y calificaciones.
4.  **Docente**: Perfil estándar enfocado en el cumplimiento de los requerimientos de evaluación y seguimiento de su propio progreso.

## Despliegue Local

1.  Clona el repositorio o ubica la carpeta `gestor_evidencias` dentro de tu entorno de servidor local (ej. `C:\xampp\htdocs\`).
2.  Crea una nueva base de datos en tu gestor MySQL/MariaDB e importa el esquema principal ubicado en `sql/gestor_evidencia_produccion.sql`.
3.  Ajusta las credenciales de acceso a la base de datos en el archivo `config/conexion.php`.
4.  Accede a la plataforma web iniciando en la ruta `public/login.php`.