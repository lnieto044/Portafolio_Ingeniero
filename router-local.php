<?php
/**
 * Router para el servidor embebido de PHP.
 *
 * Hace lo que hace el .htaccess en Apache: si el fichero pedido existe se sirve
 * tal cual, y si no, se pasa a index.php para que WordPress resuelva el
 * permalink (aqui son del tipo /2020/03/24/nombre/).
 */
$ruta = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$archivo = __DIR__ . $ruta;

// Nunca servir la configuracion ni el volcado, igual que en produccion.
if ( preg_match( '#(wp-config[^/]*\.php|\.sql|\.log|\.bk|\.gf)$#i', $ruta ) ) {
	http_response_code( 403 );
	exit( 'Prohibido' );
}

if ( '/' !== $ruta && file_exists( $archivo ) && ! is_dir( $archivo ) ) {
	return false; // El servidor lo sirve directamente.
}

// Carpetas con su propio index.php (/wp-admin/, /wp-admin/network/...): igual
// que Apache con DirectoryIndex. Sin esto, /wp-admin/ caia en el index.php de
// la raiz y WordPress lo mandaba a la portada: el escritorio nunca abria.
if ( '/' !== $ruta && is_dir( $archivo ) && is_file( rtrim( $archivo, '/' ) . '/index.php' ) ) {
	if ( '/' !== substr( $ruta, -1 ) ) {
		// Con la barra final, los enlaces relativos del escritorio (edit.php...) resuelven bien.
		$qs = $_SERVER['QUERY_STRING'] ?? '';
		header( 'Location: ' . $ruta . '/' . ( '' !== $qs ? '?' . $qs : '' ), true, 301 );
		exit;
	}
	return false; // El servidor embebido sirve el index.php de esa carpeta.
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
