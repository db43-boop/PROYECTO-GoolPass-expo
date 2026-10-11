# Instalación y migración de GoolPass MVC

## Método recomendado: instalar en una carpeta nueva

No reemplaces directamente tu carpeta de trabajo original hasta haber probado la nueva estructura.

1. Cierra Apache y respalda `torneo.db` y `uploads/` del GoolPass anterior.
2. Descomprime el ZIP como `C:\xampp\htdocs\GoolPass-MVC`.
3. Copia **tu base anterior** `C:\xampp\htdocs\PROYECTO-GoolPass-expo\torneo.db` a `C:\xampp\htdocs\GoolPass-MVC\storage\torneo.db` si deseas tus registros locales. Esto sustituye solo la base de demostración.
4. Copia tus escudos de `...\PROYECTO-GoolPass-expo\uploads\` a `...\GoolPass-MVC\public\uploads\`.
5. Enciende Apache y visita `http://localhost/GoolPass-MVC/login.php`.
6. Verifica tus equipos, fichas, entradas, usuarios, partidos y torneos.

Si ya estabas utilizando la primera estructura reorganizada, tu base puede estar en `storage/torneo.db` y tus escudos en `public/uploads/`; conserva esos archivos en las mismas rutas.

## Instalación sobre la carpeta antigua

Si prefieres mantener la URL previa, haz antes una copia del proyecto entero. La extracción de un ZIP **no elimina los antiguos** `css/`, `js/`, `uploads/` ni los archivos PHP que se movieron a `app/`. Una vez verificada la versión nueva, puedes retirar las copias antiguas y la carpeta `datos_prueba`.

## Script de migración

En una instalación que tenga todavía un `torneo.db` en la raíz, puedes ejecutar desde Git Bash:

```bash
cd /c/xampp/htdocs/PROYECTO-GoolPass-expo
/c/xampp/php/php.exe scripts/migrar_datos_anteriores.php --confirmar
```

**Atención:** este comando copia la base antigua sobre `storage/torneo.db` y primero hace una copia de seguridad de la base nueva. Debes detener Apache para evitar conexiones abiertas durante la copia. No lo ejecutes si estás trabajando sobre la base correcta en `storage/`.

## Qué se conserva y qué se elimina

Se conservan los módulos de login, fichas, equipos, partidos, entradas, torneos y su diseño. La base de demostración del ZIP incluye 8 equipos ficticios, 88 jugadores, 2 cuentas y 3 partidos. Se elimina la carpeta `datos_prueba` y se separa la lógica SQL en modelos.

**`scripts/nuevo_setup.php` puede borrar los datos. No lo ejecutes para instalar ni actualizar la aplicación.**
