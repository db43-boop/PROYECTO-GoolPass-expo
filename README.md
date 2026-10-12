# GoolPass MVP

Aplicación web local para organizar torneos de fútbol y vender entradas. GoolPass permite administrar equipos y sus jugadores, crear torneos e inscripciones, programar partidos manualmente, registrar estadísticas por partido y consultar o comprar entradas.

Es un MVP desarrollado con **PHP**, **HTML**, **CSS** y **SQLite**, pensado para ejecutarse localmente con **XAMPP**. La interfaz usa CSS propio y no depende de Bootstrap. La administración requiere iniciar sesión; todas las cuentas tienen las mismas funciones y no existen roles. La compra de entradas admite usuarios registrados e invitados.

## Funcionalidades

- Registro, inicio y cierre de sesión. El registro y el inicio aparecen en pantallas separadas.
- Creación y consulta de equipos nacionales e internacionales, selección de liga y opción de indicar una liga personalizada.
- Gestión de jugadores dentro de cada equipo.
- Creación de torneos nacionales o internacionales, con formato descriptivo y controles para abrir o cerrar inscripciones.
- Inscripción de equipos compatibles desde el torneo o desde el detalle del equipo. Cada equipo puede participar en un único torneo y no se puede inscribir dos veces.
- Creación manual de partidos desde el torneo. Se muestran los equipos inscritos y no se permite elegir el mismo equipo como local y visitante.
- Selección de estadios conocidos o ingreso de un estadio personalizado.
- Estadísticas asociadas al jugador y al partido, sin guardarlas como datos acumulados del jugador.
- Compra de entradas con precio y estado de pago, con cuenta o como invitado. Cada entrada reserva una plaza del aforo.
- Historial de entradas protegido por inicio de sesión, disponible de forma general o desde un partido.
- Carga opcional de datos de demostración mediante `seed.php`.

Aplicación local para gestionar equipos, jugadores, torneos, inscripciones, partidos, estadísticas por partido y entradas. Está hecha con PHP, HTML/CSS y SQLite para ejecutarse con XAMPP. Todas las cuentas tienen las mismas funciones; no se usan roles. Las entradas se pueden comprar con una cuenta o como invitado.

## Estructura

`public/index.php` es el único punto de entrada web. Despacha las rutas a controladores de `app/Controllers`; estos validan los formularios, usan modelos PDO de `app/Models` y cargan las plantillas de `app/Views`. El código legado que no pertenece al MVP se retiró.

`storage/goolpass.db` es la base activa del MVP y se crea al inicializar SQLite por primera vez. No se importan datos de la antigua `torneo.db`, que fue retirada. Los escudos cargados por el usuario se guardan bajo `public/uploads/`. Los archivos de `app/` y `storage/` están protegidos contra acceso directo de Apache.

```text
app/
  Controllers/       Coordinan los flujos de la aplicación
  Core/              Funciones compartidas y soporte de la aplicación
  Models/            Consultas y operaciones de la base de datos
  Views/             Plantillas HTML
  Schema.php         Creación y migración del esquema SQLite
public/
  index.php          Único punto de entrada web
  css/app.css        Estilos propios de la interfaz
  uploads/           Escudos de equipos
storage/
  goolpass.db        Base local creada al iniciar la aplicación
seed.php             Datos de demostración (solo CLI)
```

Las vistas envían solicitudes al punto de entrada público. Los controladores validan y coordinan las operaciones, y los modelos encapsulan el acceso PDO a SQLite. Las operaciones que modifican información usan formularios POST y protección CSRF.

## Ejecutar con XAMPP

### Requisitos

- Windows con XAMPP y Apache.
- PHP con las extensiones **PDO** y **PDO SQLite** habilitadas.
- No se necesita un servidor de base de datos aparte: SQLite guarda la información en un archivo local.

1. Coloca el proyecto en `C:\xampp\htdocs\PROYECTO-GoolPass-expo` y activa Apache.
2. Verifica que PHP tenga habilitado PDO SQLite.
3. Abre `http://localhost/PROYECTO-GoolPass-expo/`.
4. Regístrate o inicia sesión. El inicio lleva a la gestión de equipos.

Apache redirige las solicitudes a `public/index.php`. Las rutas se despachan con el parámetro `r`, por ejemplo `index.php?r=torneos`; las vistas generan esos enlaces mediante `url_for()`.

## Inicio de sesión y registro

El acceso y la creación de cuenta son pantallas independientes. `http://localhost/PROYECTO-GoolPass-expo/public/index.php?r=login` muestra solo el formulario de inicio de sesión y enlaza al registro; `http://localhost/PROYECTO-GoolPass-expo/public/index.php?r=register` muestra solo el formulario para crear una cuenta y enlaza al inicio de sesión. Después de iniciar sesión o registrarse, la aplicación abre la gestión de equipos.

## Datos de demostración

El script inserta equipos, jugadores, torneos, inscripciones, partidos, estadísticas y entradas de ejemplo en `storage/goolpass.db`. Es idempotente: se puede ejecutar varias veces y no elimina datos existentes ni duplica los registros de demostración. Solo se ejecuta desde la consola, no como una ruta web.

La cuenta incluida es `demo@goolpass.local` con contraseña `GoolpassDemo2026!`.

Para llenar la base local con registros de ejemplo, abre PowerShell y ejecuta el comando desde la carpeta raíz del proyecto:

```powershell
cd C:\xampp\htdocs\PROYECTO-GoolPass-expo
& C:\xampp\php\php.exe .\seed.php
```

`C:\xampp\php\php.exe` es el intérprete PHP instalado por XAMPP; `seed.php` es el script que está en la raíz de este proyecto. El operador `&` permite a PowerShell ejecutar el programa indicado por una ruta completa. Si `php` ya está agregado al `PATH`, también puedes usar `php .\seed.php`.

## Rutas del MVP

- `login`, `register`, `logout`: autenticación y cierre de sesión.
- `equipos`, `equipos/crear`, `equipos/ver`, `equipos/inscribir`, `equipos/jugadores/crear`: equipos, plantilla e inscripción a un torneo desde el detalle del equipo. `equipos/crear` abre el formulario.
- `torneos`, `torneos/crear`, `torneos/ver`, `torneos/inscribir`, `torneos/inscripciones`: listado, creación, cierre/apertura de inscripciones e inscripciones por torneo.
- `partidos`, `partidos/crear`, `partidos/estadisticas`: partidos manuales y estadísticas por jugador.
- `entradas`, `entradas/comprar`, `entradas/registro`: compra pública e historial privado general o filtrado por partido (requiere iniciar sesión).

## Flujo principal de uso

1. Si ya tienes una cuenta, inicia sesión en la pantalla inicial. Si es tu primera vez, usa el enlace **Crear cuenta** para abrir el registro en su propia pantalla.
2. En **Equipos**, usa **Crear equipo** y registra el origen nacional o internacional.
3. En **Torneos**, crea un torneo. Puedes inscribir equipos compatibles desde el detalle del torneo o desde el detalle del equipo.
4. Desde el detalle del torneo, pulsa **Generar partido**. El formulario ya queda asociado al torneo y solo ofrece sus equipos inscritos; se requieren al menos dos.
5. En **Entradas**, cualquiera puede consultar partidos y registrar una compra como invitado o con cuenta. El historial completo aparece en **Entradas registradas** solo después de iniciar sesión.

Sin iniciar sesión se puede acceder a las pantallas de autenticación y consultar o comprar entradas. Las páginas de gestión y el historial de ventas requieren una sesión activa; si un visitante intenta abrirlas, se le redirige al login.

Las operaciones que cambian datos usan POST y token CSRF. Los controladores usan una conexión PDO compartida y no generan SQL en las vistas.

## Base local y migraciones

La configuración usa `storage/goolpass.db`. En una base vacía, el bootstrap crea el esquema canónico limpio v4 para el MVP: usuarios sin roles, equipos, jugadores sin estadísticas acumuladas, torneos con control de inscripciones abiertas/cerradas, inscripciones, partidos, estadísticas por partido y entradas. No crea tablas heredadas como `torneos_guardados`. La migración v3→v4 agrega el control de inscripciones a bases existentes sin borrar datos. La ruta v2→v3 permanece solo para compatibilidad explícita; no asigna torneos a partidos antiguos.

La creación del esquema se verificó en una base temporal vacía y en `storage/goolpass.db`.

## Reglas y límites del MVP

- Cada equipo puede inscribirse en un solo torneo durante la vida de su registro. Equipos nacionales pueden entrar a torneos nacionales o internacionales; equipos internacionales solo a internacionales.
- El formato del torneo es descriptivo. No se generan fixtures, rondas ni clasificaciones.
- Las inscripciones de cada torneo pueden cerrarse o reabrirse desde su detalle; un torneo cerrado no acepta equipos nuevos.
- Los partidos se crean manualmente. Las estadísticas se registran manualmente por jugador y partido.
- Cada entrada equivale a una persona y reserva una plaza. El modelo controla el aforo de forma transaccional. No hay asientos numerados ni compras agrupadas.
- Se registra estado de pago, pero no hay pasarela bancaria ni cobros reales.
- El formulario del MVP no ofrece actualización del marcador todavía, aunque el modelo ya expone la operación.

## Validación

Se validaron el registro, la creación de equipos y torneos, las inscripciones, los partidos, la compra de entradas y el historial con una copia temporal de SQLite. También se comprobó la creación del esquema en una base vacía y la migración de la versión 3 a la 4. La base local con datos del usuario no se modificó durante esas pruebas.
