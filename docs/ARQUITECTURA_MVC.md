# Guía de arquitectura MVC de GoolPass

## Flujo de una petición

1. El navegador entra en `partidos.php` o cualquier punto de entrada PHP de la raíz.
2. `app/bootstrap.php` carga la configuración, la conexión PDO y aplica migraciones pendientes.
3. El controlador `app/Controllers/pages/partidos.php` verifica sesión, permisos y valida la petición.
4. El controlador llama a `Partido`, `Equipo` y `Entrada`, dentro de `app/Models/`, para consultar o guardar información.
5. `app/Views/pages/partidos.php` representa los datos mediante HTML.
6. Los recursos CSS, JS y escudos se sirven desde `public/`.

## Responsabilidades

| Capa | Responsabilidad | Ejemplo |
|---|---|---|
| Config | Parámetros técnicos sin consultas SQL | `app/Config/database.php` |
| Core | PDO, sesión, roles y protección CSRF | `app/Core/Database.php` |
| Migraciones | CREATE TABLE, ALTER TABLE, índices y triggers | `app/Database/Migrations/Schema.php` |
| Catalogs | Datos fijos del negocio | `app/Catalogs/catalogo_ligas.php` |
| Models | Consultas parametrizadas y métodos CRUD | `app/Models/Equipo.php` |
| Controllers | Procesar GET/POST, autorización, validación y redirecciones | `app/Controllers/pages/editar.php` |
| Views | Mostrar los resultados y formularios | `app/Views/pages/ficha.php` |
| Services | Validación de imágenes y formación sobre cancha | `app/Services/escudos.php` |
| Public | CSS/JS e imágenes visibles por el navegador | `public/css`, `public/js`, `public/uploads` |
| Storage | Archivos privados de persistencia | `storage/torneo.db` |

## Límites de la arquitectura

El proyecto usa MVC sin framework y **controladores procedimentales**, además de modelos organizados en clases. Es una implementación MVC propia, no Laravel ni un sistema de enrutamiento completamente orientado a objetos. Los archivos PHP de la raíz se conservan para mantener la compatibilidad con los formularios del equipo.

Los modelos son el lugar para futuras consultas, y las modificaciones de estructura deberán versionarse en `Schema.php` actualizando el valor de `PRAGMA user_version`. No edites las tablas en la vista ni introduzcas SQL nuevo en los controladores.

## Agregar una función nueva

Si quieres, por ejemplo, crear un ranking de equipos:

1. Crear consultas en `app/Models/Equipo.php` o un modelo `Clasificacion.php`.
2. Añadir acciones de control en `app/Controllers/pages/clasificacion.php`.
3. Mostrar tarjetas/tabla en `app/Views/pages/clasificacion.php`.
4. Crear el punto de entrada `clasificacion.php` en la raíz.
5. Agregar, si hace falta, CSS en `public/css/`.
6. Si requiere tablas adicionales, modificar `app/Database/Migrations/Schema.php` con una nueva versión.
