# Registro de productos

Formulario con HTML, CSS y JavaScript nativos, PHP y PostgreSQL, sin frameworks.

## Requisitos y versiones

- PHP 8.3.35 con las extensiones `pdo_pgsql` y `mbstring` habilitadas en `php.ini`.
- PostgreSQL 16.15 iniciado y con acceso a una cuenta administradora.
- PHP, psql y Git disponibles en el PATH.

## Instalación

1. Clone el repositorio y entre a la carpeta del proyecto:

   ```bat
   git clone https://github.com/das3110/Prueba-Desis.git
   cd Prueba-Desis
   ```

   Ejecute los pasos siguientes desde esta carpeta, donde se encuentra `index.html`.
   Puede utilizar cualquier editor; los comandos de inicio siguientes usan CMD
   de Windows, también disponible como terminal integrada del editor.

2. Cree el usuario y una base vacía. Abra psql con la cuenta administradora:

   ```bat
   psql -h 127.0.0.1 -p 5432 -U postgres
   ```

   Dentro de psql, ejecute estas líneas. `\password` permite definir la contraseña
   de `registro` sin incluirla en el código:

   ```text
   CREATE ROLE registro LOGIN;
   \password registro
   CREATE DATABASE registro_productos OWNER registro ENCODING 'UTF8' TEMPLATE template0;
   \q
   ```

3. Importe los archivos de la carpeta `SQL` en este orden, usando la contraseña
   del usuario `registro`:

   ```bat
   psql -h 127.0.0.1 -p 5432 -U registro -d registro_productos -v ON_ERROR_STOP=1 -f SQL/01_estructura.sql
   psql -h 127.0.0.1 -p 5432 -U registro -d registro_productos -v ON_ERROR_STOP=1 -f SQL/02_datos_iniciales.sql
   ```

   El primer archivo crea las tablas y restricciones; el segundo carga los
   catálogos. Ejecute estos pasos una sola vez sobre una base nueva.

4. Configure la conexión en la terminal CMD. Después de `set /p`, ingrese la
   contraseña de `registro` y pulse Enter antes de continuar:

   ```bat
   set "DB_HOST=127.0.0.1"
   set "DB_PORT=5432"
   set "DB_NAME=registro_productos"
   set "DB_USER=registro"
   set /p "DB_PASSWORD=Contraseña de registro: "
   ```

   Ajuste estos valores a su entorno. Si cambia el host o puerto, ajuste también
   `-h` y `-p` en los comandos psql anteriores.

5. Inicie el servidor desde la raíz del proyecto, en la misma terminal:

   ```bat
   php -d display_errors=Off -S 127.0.0.1:8000 -t . router.php
   ```

   Abra [http://127.0.0.1:8000/index.html](http://127.0.0.1:8000/index.html) y mantenga la terminal abierta.
   `router.php` debe incluirse en el comando: restringe el acceso a los archivos
   públicos de la aplicación. Para detener el servidor, pulse Ctrl+C.

Para volver a ejecutar el proyecto, mantenga PostgreSQL iniciado y repita
solo los pasos 4 y 5. Los datos guardados se conservan.
