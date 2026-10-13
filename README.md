# GoolPass MVC — proyecto completo con cuenta de administrador lista

Esta versión **incluye** `storage/torneo.db`, a diferencia de los paquetes anteriores.
El archivo está copiado del proyecto original aportado para la reestructuración.
Incluye cuentas de prueba, ocho equipos, 88 jugadores, tres partidos y los escudos correspondientes.

## Instalación limpia en XAMPP

1. Detén Apache desde XAMPP.
2. **Respalda** la carpeta anterior y en especial su `storage/torneo.db` si has realizado registros nuevos.
3. Renombra o mueve la carpeta antigua `C:\xampp\htdocs\GoolPass` a `GoolPass_anterior`. No mezcles carpetas antiguas.
4. Extrae **la carpeta `GoolPass` que viene dentro de este ZIP** en `C:\xampp\htdocs\`. Debe quedar exactamente `C:\xampp\htdocs\GoolPass\public\index.php` y `C:\xampp\htdocs\GoolPass\storage\torneo.db`.
5. Enciende Apache en XAMPP. No hace falta arrancar MySQL porque usa SQLite.
6. Abre `http://localhost/GoolPass/public/index.php?r=login`.

## Cuentas de DEMOSTRACIÓN

- **Admin:** `admin@goolpass.com` — contraseña `admin123`.
- **Usuario:** `usuario@goolpass.com` — contraseña `12345678`.

No necesitas ejecutar `seed.php` para iniciar: `torneo.db` ya está incluido y preparado.
Si seguías conectado con una cuenta de usuario, cierra sesión para entrar con el administrador.

## Base de datos y respaldos

- `storage/torneo.db` ya tiene los registros. **Reemplazarla sobrescribe los datos locales anteriores**; guarda una copia si importan.
- `seed.php` sirve para configurar/restaurar datos de demostración desde la terminal, pero **no es obligatorio** para esta versión.
- Las credenciales son exclusivas para pruebas en un entorno local. Cámbialas antes de publicar el sistema en Internet. No subas la base de datos demo a un repositorio público.

## Notas técnicas

- Usa PHP con `pdo_sqlite` habilitado (por defecto en muchas instalaciones de XAMPP).
- La carpeta `app` y `storage` están protegidas con `.htaccess`.
- Si aparece `Forbidden` al entrar en la raíz, prueba la URL explícita `/GoolPass/public/index.php?r=login`.

---

# GoolPass — tu proyecto original reorganizado en MVC

**Origen:** `PROYECTO-GoolPass-mio.rar` entregado en esta conversación. No es una aplicación nueva.
Se conservaron las pantallas originales (portada, login/registro, equipos, ficha, edición, gestión de jugadores, sorteos, partidos, compra simulada, mis entradas, usuarios, perfil) y la lógica de sus modelos.

## Ejecutar en XAMPP

1. Extrae la carpeta `GoolPass_MVC_Misma_Funcionalidad` en `C:\xampp\htdocs\` sin sobrescribir tu proyecto anterior.
2. Inicia Apache (MySQL NO es necesario, se utiliza SQLite).
3. En la terminal, estando dentro de `GoolPass_MVC_Misma_Funcionalidad`, ejecuta `C:\xampp\php\php.exe seed.php` **una sola vez**.
4. Visita `http://localhost/GoolPass_MVC_Misma_Funcionalidad/public/` o `http://localhost/GoolPass_MVC_Misma_Funcionalidad/public/index.php?r=login`.

El archivo `seed.php` restaura los mismos registros de la **base original** y ocho escudos originales, que no se incluyen como archivos separados en el ZIP para respetar exactamente el árbol solicitado. Los seis archivos de nombre hexadecimal en `public/uploads` son copias de muestra. El instalador **no sobrescribe** bases de datos existentes.

## Cuentas del entorno local

- Administrador: `admin@goolpass.com` / `admin123`
- Usuario: `usuario@goolpass.com` / `12345678`

Estas credenciales son únicamente de demostración. **No publiques esta base precargada ni `seed.php` sin retirar primero las cuentas ficticias y revisar los datos.**

## Correspondencias desde los archivos antiguos

- `app/Controllers/pages/*.php` y `app/Controllers/actions/*.php` se trasladaron a los cinco `*Controller.php`.
- `app/Database/Migrations/Schema.php` → `app/Schema.php`.
- `app/Core/auth.php` → funciones de autenticación en `app/Controllers/AuthController.php`.
- `app/Catalogs/catalogo_ligas.php` y `app/Services/escudos.php` → `app/Models/Equipo.php`.
- `app/Services/formacion.php` → `app/Models/Jugador.php`.
- Páginas antiguas → `app/Views/*.php`. Los formularios crear/editar comparten `equipo_form.php`.
- Scripts originales de `public/js/` → incorporados en `app/Core/View.php` y emitidos en cada página.
- CSS originales → fusionados en `public/css/app.css`.
- `public/index.php` recibe las rutas `?r=crear`, `?r=ver`, `?r=ficha&id=1`, `?r=partidos`, `?r=torneo`, `?r=usuarios`, etc.
- `registro_entradas.php` conserva el modal original de compra de entradas.
- `torneo_form.php` y `torneo_detalle.php` separan las etapas originales del torneo.
- Los dos modelos adicionales solicitados (`Inscripcion.php`, `EstadisticaPartido.php`) consultan las tablas originales. No inventan relaciones ni estructuras nuevas.

## Nota de pruebas

Se han comprobado la estructura, los archivos y la sintaxis PHP. Comprueba los flujos en tu XAMPP con `pdo_sqlite` habilitado: en este entorno de empaquetado PHP no tiene el driver `pdo_sqlite`, por lo que no se ha realizado una prueba completa HTTP/SQLite ni de la interfaz en navegador.


## Cambio de estructura, no de funcionalidades

La lógica y las vistas provienen del proyecto `PROYECTO-GoolPass-mio(1).rar`. Los archivos fueron agrupados bajo los nombres solicitados; las rutas de login, navegación y APIs ahora usan el controlador frontal `public/index.php`.

**Aviso:** `seed.php` contiene la base de datos de demostración original comprimida; úsala solo en entornos de pruebas y no la publiques con datos personales.

## Si no aparece el administrador

Antes de ejecutar `seed.php`, haz una copia de seguridad de `storage/torneo.db` y cierra Apache en XAMPP.

```powershell
C:\xampp\php\php.exe seed.php
```

- Si no existe la base, importa los datos originales: 8 equipos, 88 jugadores, 3 partidos y las cuentas demo.
- Si existe una base completamente sin registros, primero crea un respaldo `torneo.db.backup_FECHA` y después importa los datos originales.
- Si existen registros, los conserva y solamente añade la cuenta `admin@goolpass.com` cuando falta.
- Si el admin existe pero su contraseña se cambió y quieres restablecerla **intencionalmente** a `admin123`, usa:

```powershell
C:\xampp\php\php.exe seed.php --restaurar-admin
```

Cuentas **solo para el entorno local**:

- Admin: `admin@goolpass.com` / `admin123`
- Usuario: `usuario@goolpass.com` / `12345678`

**No publiques una base real ni sus contraseñas.**


## Diseño adaptable (actualización de vistas)

Se corrigió el tamaño del escudo en las tarjetas pequeñas de portada (`64×64`), que
se había agrandado al fusionar varias hojas de estilo en `public/css/app.css`. Los
escudos del listado de equipos ahora mantienen sus proporciones (`object-fit: contain`).

En Equipos y Partidos el diseño utiliza hasta 4 columnas según el ancho disponible:
4 en ventanas amplias (incluido zoom del 80 % en pantallas de escritorio habituales),
3 en pantallas intermedias, 2 en tabletas y 1 en móviles. Mis Entradas y los grupos
de Torneo tienen reglas equivalentes; las eliminatorias pueden desplazarse horizontalmente
sin desbordar la pantalla. La navegación móvil permite desplazamiento horizontal.

El código PHP, las funciones MVC, `seed.php` y `storage/torneo.db` no se han cambiado.
Se recomienda extraer en una carpeta nueva o respaldar `storage/torneo.db` antes
de reemplazar el proyecto, pues el ZIP incluye la base de datos de demostración.


## Dirección correcta en XAMPP (Windows)

La URL depende de **cómo se llama la carpeta extraída bajo `C:\xampp\htdocs`**.
Si la carpeta es `PROYECTO-GoolPass-mio2`, abre directamente:

`http://localhost/PROYECTO-GoolPass-mio2/public/index.php`

También funciona `http://localhost/PROYECTO-GoolPass-mio2/public/` si Apache permite DirectoryIndex index.php.
El botón **Admin** de Apache en XAMPP abre el panel de localhost, no un enlace especial de GoolPass. Si desde ese panel un enlace genera `localhost/C:/xampp/htdocs/...`, no es una ruta válida de esta aplicación: usa la URL anterior y comprueba `DocumentRoot` de Apache si falla.

### Ajuste de vista
En `public/css/app.css` la sección final `MIS ENTRADAS: ENCABEZADO CENTRADO` coloca el título y el subtítulo en el centro para administrador y usuario, con el botón debajo. No se han cambiado modelos, controladores ni registros SQLite.
