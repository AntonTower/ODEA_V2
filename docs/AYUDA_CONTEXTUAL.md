# Ayuda contextual en ODEA

## Inventario cubierto

El componente reutilizable `components/ui/ayuda-contextual.php` presenta una guía desplegable por ruta: panel según rol; usuarios; instrumentos; tipos de evidencia; atributos; periodos; convocatorias; criterios; fuentes normativas; gestión de evidencias; evaluación; expediente; revisión de metadatos; aplicaciones académicas; calificaciones; detalle de evidencia; asignaciones de instrumentos y perfil.

Las cuatro subpestañas académicas tienen descripción bajo su título y una guía específica. La ayuda administrativa no se muestra a docentes ni evaluadores. Los paneles de docente y evaluador describen sus respectivos alcances.

## Componentes

- Desplegable nativo `details/summary`, navegable por teclado y adaptable a pantallas pequeñas.
- Catálogo contextual en PHP con objetivo, requisitos previos, pasos, resultado y siguiente acción.
- Enlace a la pantalla siguiente cuando existe una ruta coherente.
- Ejemplos en placeholders, nunca como valores iniciales. Se conservan etiquetas, valores capturados y validaciones.
- Ayuda breve en campos identificados (versión, obligatoriedad y fuente normativa).
- Mensajes de estado vacío específicos para las tablas de gestión académica.

Los ejemplos se identifican como ilustrativos y no son reglas institucionales. Las guías describen lo que actualmente se observa en las vistas y no amplían permisos ni acciones.

## Comprobación

Ejecuta `php tests/ayuda_contextual.php` desde la raíz del repositorio. La prueba comprueba cobertura de rutas, separación por rol, etiquetas de pasos, accesibilidad nativa y que los ejemplos solo sean placeholders. También se verificó la sintaxis PHP de los componentes.

## Cobertura pendiente

Los mensajes vacíos internos de DataTables y modales antiguos mantienen sus textos actuales; se complementan con la guía contextual. `campos-instrumentos-evaluador.php` y algunos alias antiguos no tienen guía independiente porque no se confirmó una ruta activa desde el menú. La ayuda de campos se añade solo a controles con nombres identificables; no sustituye una revisión de cada atributo dinámico personalizado.
