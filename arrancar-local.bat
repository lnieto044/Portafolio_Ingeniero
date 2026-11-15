@echo off
REM ===================================================================
REM  Arranca la copia local del portafolio.
REM  Cierra esta ventana para apagar el servidor web.
REM
REM  PHP vive donde lo instalo WinGet, no en Temp. La instalacion
REM  anterior estaba en %LOCALAPPDATA%\Temp y Windows la borro al
REM  limpiar la carpeta: el sitio dejo de arrancar sin explicacion.
REM  Esta ruta sobrevive a esa limpieza.
REM
REM  El php.ini vive en %USERPROFILE%\portafolio-entorno, FUERA de
REM  Descargas. Estuvo dentro y desaparecio dos veces: Descargas es
REM  una carpeta que se limpia, y sin ese archivo PHP arranca sin
REM  extensiones y WordPress no conecta con la base. Tampoco va dentro
REM  de la carpeta de PHP: WinGet la reemplaza en cada actualizacion.
REM ===================================================================
setlocal

set PHP=%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe
set INI=%USERPROFILE%\portafolio-entorno\php.ini
set EXTRA=
set RAIZ=%~dp0

REM --- Que PHP usar -----------------------------------------------------
REM  Primero el 8.4 de WinGet con su php.ini. Si falta cualquiera de los dos
REM  (en noviembre de 2026 faltaban ambos), se usa el PHP de XAMPP con su
REM  propio php.ini, activando GD e intl aqui sin tocar la configuracion de
REM  XAMPP. GD hace falta para generar las portadas de los proyectos.
if exist "%PHP%" if exist "%INI%" goto php_listo
set PHP=C:\xampp\php\php.exe
set INI=C:\xampp\php\php.ini
set EXTRA=-d extension=gd -d extension=intl
if not exist "%PHP%" (
    echo.
    echo  ERROR: no hay PHP. Ni el de WinGet ni el de XAMPP en %PHP%
    echo.
    echo  Para reinstalar el de WinGet, abre una consola y escribe:
    echo     winget install --id PHP.PHP.8.4 --exact
    echo.
    pause
    exit /b 1
)
echo  Aviso: falta el PHP 8.4 de WinGet o su php.ini. Uso el PHP de XAMPP.
echo.
:php_listo

REM --- MariaDB ---------------------------------------------------------
REM  Escucha en el 3307: el 3306 lo ocupa el servicio MySQL80 de Windows.
REM  Se comprueba el PUERTO y no el proceso: MySQL80 tambien se llama
REM  mysqld.exe y el script daba MariaDB por arrancada cuando no lo estaba.
echo [1/2] Arrancando MariaDB de XAMPP en el puerto 3307...
call :puerto_3307
if errorlevel 1 (
    start "MariaDB" /MIN C:\xampp\mysql\bin\mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini --standalone
    timeout /t 8 /nobreak >nul
    call :puerto_3307
    if errorlevel 1 goto mariadb_fallo
) else (
    echo     ya estaba corriendo.
)

echo [2/2] Arrancando el servidor web en http://localhost:8080
echo.
for /f "delims=" %%v in ('"%PHP%" -c "%INI%" -r "echo PHP_VERSION;"') do set VER=%%v
if defined EXTRA set VER=%VER% de XAMPP
echo     PHP:        %VER%
echo     Sitio:      http://localhost:8080
echo     Escritorio: http://localhost:8080/wp-admin
echo     Usuario:    Guillermo
echo     Clave:      local1234
echo.
echo     Deja esta ventana abierta: es el servidor.
echo.
set PHP_CLI_SERVER_WORKERS=4
"%PHP%" -c "%INI%" %EXTRA% -S localhost:8080 -t "%RAIZ%." "%RAIZ%router-local.php"
exit /b

REM --- Subrutinas ------------------------------------------------------
:puerto_3307
REM  errorlevel 0 si algo escucha en el 3307. PowerShell y no netstat:
REM  netstat dice LISTENING o ESCUCHANDO segun el idioma de Windows.
powershell -NoProfile -Command "if (Get-NetTCPConnection -LocalPort 3307 -State Listen -ErrorAction SilentlyContinue) { exit 0 } else { exit 1 }"
exit /b %errorlevel%

:mariadb_fallo
echo.
echo  ERROR: MariaDB no ha arrancado. Mira el final de:
echo     C:\xampp\mysql\data\mysql_error.log
echo.
echo  Si dice "Aria recovery failed" -suele pasar tras apagar el equipo
echo  de golpe-, borra estos dos archivos de C:\xampp\mysql\data
echo     aria_log_control
echo     aria_log.00000001
echo  y vuelve a abrir este script.
echo.
pause
exit /b 1
