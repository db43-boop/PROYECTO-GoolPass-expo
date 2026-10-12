# GoolPass MVP

Aplicación local para gestionar equipos, jugadores, torneos, inscripciones, partidos, estadísticas por partido y entradas. Está hecha con PHP, HTML/CSS y SQLite para ejecutarse con XAMPP. Todas las cuentas tienen las mismas funciones; no se usan roles. Las entradas se pueden comprar con una cuenta o como invitado.

## Estructura

`public/index.php` es el único punto de entrada web. Despacha las rutas a controladores de `app/Controllers`; estos validan los formularios, usan modelos PDO de `app/Models` y cargan las plantillas de `app/Views`. El código legado que no pertenece al MVP se retiró.

`storage/goolpass.db` es la base activa del MVP y se crea al inicializar SQLite por primera vez. No se importan datos de la antigua `torneo.db`, que fue retirada. Los escudos cargados por el usuario se guardan bajo `public/uploads/`. Los archivos de `app/` y `storage/` están protegidos contra acceso directo de Apache.

## Ejecutar con XAMPP

1. Coloca el proyecto en `C:\xampp\htdocs\PROYECTO-GoolPass-expo` y activa Apache.
2. Verifica que PHP tenga habilitado PDO SQLite.
3. Abre `http://localhost/PROYECTO-GoolPass-expo/`.
4. Regístrate o inicia sesión. El inicio lleva a la gestión de equipos.

Apache redirige las solicitudes a `public/index.php`. Las rutas se despachan con el parámetro `r`, por ejemplo `index.php?r=torneos`; las vistas generan esos enlaces mediante `url_for()`.

## Inicio de sesión y registro

El acceso y la creación de cuenta son pantallas independientes. `http://localhost/PROYECTO-GoolPass-expo/public/index.php?r=login` muestra solo el formulario de inicio de sesión y enlaza al registro; `http://localhost/PROYECTO-GoolPass-expo/public/index.php?r=register` muestra solo el formulario para crear una cuenta y enlaza al inicio de sesión. Después de iniciar sesión o registrarse, la aplicación abre la gestión de equipos.

## Rutas del MVP

- `login`, `register`, `logout`: autenticación y cierre de sesión.
- `equipos`, `equipos/crear`, `equipos/ver`, `equipos/jugadores/crear`: equipos y plantilla. `equipos/crear` abre el formulario.
- `torneos`, `torneos/crear`, `torneos/ver`, `torneos/inscribir`: listado, creación, detalle e inscripciones por torneo.
- `partidos`, `partidos/crear`, `partidos/estadisticas`: partidos manuales y estadísticas por jugador.
- `entradas`, `entradas/comprar`, `entradas/registro`: compra pública e historial privado (requiere iniciar sesión).

## Flujo principal de uso

1. Si ya tienes una cuenta, inicia sesión en la pantalla inicial. Si es tu primera vez, usa el enlace **Crear cuenta** para abrir el registro en su propia pantalla.
2. En **Equipos**, usa **Crear equipo** y registra el origen nacional o internacional.
3. En **Torneos**, crea un torneo e ingresa en él para inscribir equipos compatibles.
4. Desde el detalle del torneo, pulsa **Generar partido**. El formulario ya queda asociado al torneo y solo ofrece sus equipos inscritos; se requieren al menos dos.
5. En **Entradas**, cualquiera puede consultar partidos y registrar una compra como invitado o con cuenta. El historial completo aparece en **Entradas registradas** solo después de iniciar sesión.

Sin iniciar sesión se puede acceder a las pantallas de autenticación y consultar o comprar entradas. Las páginas de gestión y el historial de ventas requieren una sesión activa; si un visitante intenta abrirlas, se le redirige al login.

Las operaciones que cambian datos usan POST y token CSRF. Los controladores usan una conexión PDO compartida y no generan SQL en las vistas.

## Base local y migraciones

La configuración usa `storage/goolpass.db`. En una base vacía, el bootstrap crea el esquema canónico limpio v3 para el MVP: usuarios sin roles, equipos, jugadores sin estadísticas acumuladas, torneos, inscripciones, partidos, estadísticas por partido y entradas. No crea tablas heredadas como `torneos_guardados`. La ruta v2→v3 permanece solo para compatibilidad explícita; no es la ruta activa de instalación y no asigna torneos a partidos antiguos.

La creación del esquema se verificó en una base temporal vacía y en `storage/goolpass.db`.

## Reglas y límites del MVP

- Cada equipo puede inscribirse en un solo torneo durante la vida de su registro. Equipos nacionales pueden entrar a torneos nacionales o internacionales; equipos internacionales solo a internacionales.
- El formato del torneo es descriptivo. No se generan fixtures, rondas ni clasificaciones.
- Los partidos se crean manualmente. Las estadísticas se registran manualmente por jugador y partido.
- Cada entrada equivale a una persona y reserva una plaza. El modelo controla el aforo de forma transaccional. No hay asientos numerados ni compras agrupadas.
- Se registra estado de pago, pero no hay pasarela bancaria ni cobros reales.
- El formulario del MVP no ofrece actualización del marcador todavía, aunque el modelo ya expone la operación.

## Validación

Se probó el recorrido de registro, creación de equipos y torneo, inscripción, creación de partido, compra e historial en una copia temporal de SQLite. La base de datos real no se modificó durante esa prueba.a
