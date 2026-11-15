<?php
/**
 * Configuracion SOLO para la copia local de desarrollo.
 *
 * wp-config.php carga este archivo antes que nada y todos sus define de
 * produccion estan protegidos con ! defined(), asi que lo de aqui manda.
 *
 * NO SUBIR AL SERVIDOR. Si por error se sube, el .htaccess lo bloquea, pero
 * lo correcto es que este archivo no salga de esta maquina.
 */

/* ---------- Base de datos local (MariaDB de XAMPP) ---------- */
define( 'DB_NAME',     'portafolio_local' );
define( 'DB_USER',     'root' );
define( 'DB_PASSWORD', '' );
// Puerto 3307: el 3306 lo ocupa el servicio MySQL80 de Windows.
define( 'DB_HOST',     '127.0.0.1:3307' );
define( 'DB_CHARSET',  'utf8mb4' );
define( 'DB_COLLATE',  '' );

/* ---------- URL local ---------- */
define( 'WP_HOME',    'http://localhost:8080' );
define( 'WP_SITEURL', 'http://localhost:8080' );

/* ---------- En local si queremos ver los errores ---------- */
define( 'WP_DEBUG',         true );
define( 'WP_DEBUG_DISPLAY', true );
define( 'WP_DEBUG_LOG',     __DIR__ . '/wp-content/debug-local.log' );
define( 'SCRIPT_DEBUG',     false );

/* ---------- Cosas que estorban en local ---------- */
define( 'FORCE_SSL_ADMIN',    false ); // No hay certificado en localhost.
define( 'DISALLOW_FILE_EDIT', false );
define( 'WP_MEMORY_LIMIT',    '512M' );
define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'WP_CACHE',           false ); // LiteSpeed no pinta nada aqui.

/*
 * El servidor embebido de PHP atiende de uno en uno. wp-cron se dispara con una
 * peticion del sitio a si mismo y, sin varios workers, esa peticion se queda
 * esperando a que termine la que la lanzo: la pagina se cuelga.
 */
define( 'DISABLE_WP_CRON', true );
