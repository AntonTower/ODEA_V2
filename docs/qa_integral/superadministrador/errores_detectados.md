# Errores detectados y correcciones

| Caso | Falla inicial reproducida | Causa | Corrección y repetición |
|---|---|---|---|
| SA-07 y SA-08 | La categoría guardada y las versiones en borrador quedaban fuera de selectores. El requisito podía responder 422 por un identificador vacío. | Algunos selectores usaban claves distintas a las colecciones devueltas por el catálogo; un multiselector podía enviar su opción vacía. | Se enlazaron las colecciones correctas y se desactivó la opción vacía en multiselectores. Después se crearon y recargaron las tres jerarquías y fuentes. |
| SA-04 | El periodo duplicado se rechazó con HTTP 409 y un mensaje que incluía el nombre de una restricción SQL. | Se capturaba primero la clase base de la excepción de MariaDB. | Se priorizó la captura de la excepción SQL. La repetición devolvió HTTP 409 con un mensaje de aplicación. |
| SA-02 | Ver perfil abrió una vista con encabezado y sin datos de la cuenta. | La vista carecía de consulta y renderizado. | Se añadió una consulta parametrizada y se mostraron nombre, correo, rol y estado dentro de la autorización que ya aplica a la ruta. Se comprobó el perfil del docente QA y la respuesta con un ID inexistente. |

Las correcciones se verificaron en la copia aislada. Pasaron el lint PHP en los tres archivos modificados y `node tests/criterios_categoria.js`. Las cargas de usuarios, instrumentos, convocatorias, versiones y fuentes se comprobaron de nuevo por navegador.
