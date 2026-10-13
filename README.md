# GoolPass — Sistema de gestión de torneos de fútbol

GoolPass es una aplicación web desarrollada en **PHP** con arquitectura **MVC (Modelo–Vista–Controlador)** y base de datos **SQLite**. Permite administrar equipos y jugadores, organizar torneos nacionales e internacionales, gestionar partidos y resultados y realizar compras simuladas de entradas.

> **Entorno previsto:** demostración académica y pruebas locales. Los pagos de entradas son simulados y no representan cobros reales.

## 1. Requisitos

- Windows con XAMPP y Apache habilitado.
- PHP 8.2 o compatible, con las extensiones **PDO** y **pdo_sqlite** activadas.
- Navegador web actualizado.
- Permisos de lectura y escritura en `storage/` y `public/uploads/`.
- MySQL y phpMyAdmin **no son necesarios**: los datos se almacenan en SQLite.

## 2. Estructura del proyecto

```text
GoolPass/
├── .htaccess
├── .gitignore
├── README.md
├── seed.php
├── app/
│   ├── bootstrap.php
│   ├── Schema.php
│   ├── Config/
│   │   ├── app.php
│   │   └── database.php
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── EntradaController.php
│   │   ├── EquipoController.php
│   │   ├── PartidoController.php
│   │   └── TorneoController.php
│   ├── Core/
│   │   ├── Database.php
│   │   └── View.php
│   ├── Models/
│   └── Views/
├── public/
│   ├── index.php
│   ├── css/app.css
│   └── uploads/
└── storage/
    └── torneo.db        # se crea/restaura en una instalación de demostración
```

**Función de cada capa:**

| Elemento | Responsabilidad |
| --- | --- |
| `public/index.php` | Punto de entrada y selección de controladores mediante `?r=`. |
| `app/Controllers/` | Validación de solicitudes, autenticación y operaciones de cada módulo. |
| `app/Models/` | Consultas SQL y operaciones sobre equipos, jugadores, partidos, torneos, entradas y usuarios. |
| `app/Views/` | Generación del HTML y formularios de la aplicación. |
| `app/Core/Database.php` | Conexión PDO a SQLite. |
| `app/Config/database.php` | Configuración de la ubicación de `storage/torneo.db`. |
| `app/Schema.php` | Creación y migración de tablas y relaciones. |
| `app/bootstrap.php` | Inicialización de configuración, modelos, controladores y esquema. |
| `seed.php` | Carga o recuperación de datos de demostración desde la terminal. |
| `public/uploads/` | Archivos de escudos de los equipos. |

## 3. Instalación local con XAMPP

1. Copiar la carpeta completa del proyecto a `C:\xampp\htdocs\`.
2. Verificar que la estructura contenga `public/index.php`, `app/bootstrap.php` y `storage/`.
3. Iniciar **Apache** desde XAMPP. No es necesario iniciar MySQL.
4. Acceder mediante la siguiente dirección, reemplazando `NOMBRE_CARPETA` por el nombre real de la carpeta del proyecto:

   ```text
   http://localhost/NOMBRE_CARPETA/public/index.php
   ```

5. Para ingresar directamente a la pantalla de autenticación:

   ```text
   http://localhost/NOMBRE_CARPETA/public/index.php?r=login
   ```

**Importante:** `C:\xampp\htdocs\` es una ruta del disco, no parte de la URL. Una dirección como `http://localhost/C:/xampp/htdocs/...` es incorrecta.

## 4. Base de datos SQLite

La base de datos de la aplicación se ubica en `storage/torneo.db`. La conexión se realiza con PDO y SQLite; las definiciones de tablas se encuentran en `app/Schema.php`.

### Instalación con una base de demostración existente

Si `storage/torneo.db` ya contiene equipos, jugadores, partidos y usuarios de prueba, iniciar Apache y abrir la aplicación. No es necesario ejecutar el instalador de datos.

### Instalación sin datos de demostración

Si el archivo de base de datos no está presente o la base está vacía, ejecutar desde una terminal ubicada en la raíz del proyecto:

```powershell
C:\xampp\php\php.exe seed.php
```

**Precaución:** realizar un respaldo de `storage/torneo.db` antes de ejecutar tareas de restauración. `seed.php` está destinado a entornos de demostración; su comportamiento ante bases ya existentes debe revisarse antes de utilizarlo con información importante.

### Respaldo

1. Detener Apache para evitar escrituras durante el respaldo.
2. Copiar `storage/torneo.db` a una ubicación segura.
3. Conservar una copia anterior antes de sustituir el proyecto o la base de datos.

## 5. Acceso de demostración

Las siguientes cuentas corresponden a los datos de prueba de la instalación académica, siempre que la base de datos utilizada las incluya:

| Rol | Correo | Contraseña de prueba |
| --- | --- | --- |
| Administrador | `admin@goolpass.com` | `admin123` |
| Usuario | `usuario@goolpass.com` | `12345678` |

El administrador puede gestionar contenidos y operaciones del sistema; el usuario normal accede a las funciones de consulta y compra simulada que le correspondan.

**Seguridad:** estas credenciales son solo para localhost. No deben reutilizarse en servicios reales ni mantenerse en un sitio expuesto a Internet. La inclusión de `torneo.db` en Git debe evaluarse para evitar publicar información privada.

## 6. Funcionalidades principales

- **Portada:** acceso a las secciones principales y visualización de información general.
- **Equipos:** consulta, registro, edición y fichas de equipos y jugadores, según permisos.
- **Torneos:** sorteos, grupos, eliminatorias y resultados nacionales e internacionales.
- **Partidos:** programación, consulta de encuentros y actualización de resultados.
- **Entradas:** compra simulada de entradas y consulta del importe pagado en **soles (S/)**.
- **Autenticación:** acceso con perfiles de administrador y usuario.

## 7. Escudos de prueba para una exposición

La carpeta adicional `escudos_prueba/` contiene doce escudos **ficticios** en formato PNG, de 512 × 512 píxeles, diseñados para demostrar la creación de equipos. No pertenecen a clubes reales.

### Incorporación desde el formulario

1. Iniciar sesión como administrador.
2. Abrir la opción **Registrar equipo**.
3. Ingresar los datos del nuevo equipo.
4. En el campo **Escudo Oficial**, seleccionar un archivo PNG desde `escudos_prueba/`.
5. Completar los campos obligatorios y guardar el equipo.
6. Abrir la sección **Equipos** y comprobar que el escudo se muestra en la tarjeta y en la ficha.

El formulario admite extensiones `.jpg`, `.jpeg`, `.png` y `.webp`; los archivos de ejemplo están en PNG. La propia aplicación guarda las imágenes recibidas en `public/uploads/` cuando se realiza el registro.

### Escudos incluidos

`andes_fc.png`, `atlas_fc.png`, `bosque_fc.png`, `condor_fc.png`, `estrella_fc.png`, `halcon_fc.png`, `laguna_fc.png`, `real_fc.png`, `pantera_fc.png`, `titanes_fc.png`, `tiburones_fc.png` y `zorros_fc.png`.

Los escudos son recursos de prueba. Agregar una imagen a una carpeta **no crea automáticamente** un equipo en SQLite: el registro debe realizarse desde la interfaz del sistema.

## 8. Secuencia sugerida de demostración

1. Iniciar sesión con una cuenta administradora de prueba.
2. Registrar un equipo ficticio y adjuntar uno de los nuevos escudos.
3. Verificar su tarjeta en **Equipos** y abrir su ficha.
4. Agregar o consultar jugadores y revisar sus estadísticas.
5. Configurar un torneo y mostrar grupos o eliminatorias.
6. Consultar un partido y realizar una compra simulada de entrada.
7. Comprobar **Mis entradas**, el código de acceso y el importe mostrado en `S/`.
8. Ingresar con la cuenta de usuario para mostrar las diferencias de permisos.

## 9. Resolución de problemas

| Problema | Comprobación recomendada |
| --- | --- |
| **403 Forbidden** | Abrir `.../public/index.php` directamente y verificar `.htaccess`. |
| **No conecta SQLite** | Revisar `pdo_sqlite`, la existencia de `storage/` y los permisos de escritura. |
| **No aparece el administrador** | Verificar qué archivo `storage/torneo.db` está utilizando el proyecto y si contiene los registros demo. |
| **No aparecen escudos** | Comprobar que `public/uploads/` tiene permisos de escritura y que el formulario guardó la imagen. |
| **No cambian los estilos** | Recargar con `Ctrl + F5` para actualizar `public/css/app.css`. |
| **URL con `C:/xampp/htdocs/`** | Utilizar `http://localhost/NOMBRE_CARPETA/public/`, sin la ruta física del disco. |

## 10. Recomendaciones para el trabajo en GitHub

- Mantener los cambios en una rama de desarrollo y solicitar revisión antes de integrarlos a `main`.
- Revisar los archivos preparados con `git status` y `git diff --cached --stat` antes de crear el commit.
- Evitar subir credenciales privadas y datos reales de usuarios o compras.
- Si se decide compartir una base de demostración, utilizar datos ficticios y documentar que SQLite es un archivo binario que no se fusiona automáticamente entre ramas.

---

**GoolPass — documentación de instalación, arquitectura y pruebas del proyecto académico.**
