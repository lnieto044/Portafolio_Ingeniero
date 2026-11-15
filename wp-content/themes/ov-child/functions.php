<?php
/**
 * OV Child - funciones del tema hijo.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OVC_VERSION', '2.0.0' );
define( 'OVC_DIR', get_stylesheet_directory() );
define( 'OVC_URI', get_stylesheet_directory_uri() );

/**
 * Divi ya encola el style.css del padre ('divi-style-parent') y el del hijo
 * ('divi-style') en su propio functions.php. La version anterior de este
 * archivo volvia a encolar el style.css del padre con el handle 'parent-style',
 * asi que el navegador descargaba y parseaba ~1 MB de CSS dos veces en cada
 * pagina, y ademas nunca cargaba el style.css del hijo. Por eso aqui no hay
 * ningun wp_enqueue_style del padre: solo se anade la version al hijo para
 * poder invalidar la cache al publicar cambios.
 */
function ovc_stylesheet_version( $src, $handle ) {
	if ( 'divi-style' === $handle ) {
		/*
		 * La version sale de la fecha del propio style.css, no de una
		 * constante escrita a mano.
		 *
		 * Con la constante fija habia que acordarse de subirla en cada cambio
		 * de estilos, y si no, la URL no cambiaba y el navegador seguia
		 * sirviendo la hoja antigua: se editaba el CSS y en pantalla no pasaba
		 * nada. Con filemtime, cualquier guardado invalida la cache solo.
		 *
		 * Se anade, no se sustituye 'ver': LiteSpeed usa la URL completa como
		 * clave de cache y quitarle parametros confunde su invalidacion.
		 */
		$hoja    = OVC_DIR . '/style.css';
		$version = file_exists( $hoja ) ? (string) filemtime( $hoja ) : OVC_VERSION;

		$src = add_query_arg( 'ovc', $version, $src );
	}
	return $src;
}
add_filter( 'style_loader_src', 'ovc_stylesheet_version', 10, 2 );

/**
 * Normaliza un texto para compararlo: sin tildes, sin signos, en minusculas.
 *
 * La usan las dos pantallas que emparejan archivos con fichas por su nombre.
 * Vive aqui, y no dentro de una de ellas, porque functions.php se carga
 * siempre: si se queda en un archivo que solo se incluye en el escritorio, la
 * otra pantalla revienta con "undefined function" en cuanto se usa fuera de
 * ese contexto.
 *
 * @param string $texto Texto.
 * @return string
 */
function ovc_normalizar( $texto ) {
	$texto = remove_accents( (string) $texto );
	$texto = strtolower( $texto );
	return preg_replace( '/[^a-z0-9]+/', '', $texto );
}

/**
 * Carga el script de apariciones y contadores.
 *
 * Va en el pie y sin dependencias: no usa jQuery ni ninguna libreria, asi que
 * no anade peso a la pagina mas alla de sus propios 2 KB.
 */
function ovc_encolar_animaciones() {
	$ruta = OVC_DIR . '/assets/js/ovc-animaciones.js';
	if ( ! file_exists( $ruta ) ) {
		return;
	}
	wp_enqueue_script(
		'ovc-animaciones',
		OVC_URI . '/assets/js/ovc-animaciones.js',
		array(),
		(string) filemtime( $ruta ),
		true
	);

	// El velo del menu lateral.
	$menu = OVC_DIR . '/assets/js/ovc-menu.js';
	if ( file_exists( $menu ) ) {
		wp_enqueue_script(
			'ovc-menu',
			OVC_URI . '/assets/js/ovc-menu.js',
			array(),
			(string) filemtime( $menu ),
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'ovc_encolar_animaciones' );

/**
 * Compara un nombre de archivo contra un texto, palabra a palabra.
 *
 * Devuelve una puntuacion: 0 si falta alguna palabra, y si estan todas, la
 * suma de sus longitudes -asi gana la coincidencia mas especifica-.
 *
 * Comparar las cadenas enteras no sirve: "tienda_ecomerce_productos_frescos"
 * no aparece dentro de "Tiendita: E-commerce de productos frescos" porque
 * cambian dos letras. Por palabras, "productos" y "frescos" sí encajan.
 *
 * @param string $clave Nombre del archivo, sin extension.
 * @param string $texto Texto contra el que se compara.
 * @return int Puntuacion.
 */
function ovc_puntuar_coincidencia( $clave, $texto ) {
	$tokens = array_values(
		array_filter(
			array_map( 'ovc_normalizar', preg_split( '/[\s._+-]+/u', (string) $clave ) )
		)
	);

	$t = ovc_normalizar( $texto );
	if ( empty( $tokens ) || '' === $t ) {
		return 0;
	}

	$suma = 0;
	foreach ( $tokens as $token ) {
		// Las palabras de una o dos letras generan falsos positivos.
		if ( strlen( $token ) < 3 ) {
			continue;
		}
		if ( false === strpos( $t, $token ) ) {
			return 0;
		}
		$suma += strlen( $token );
	}

	return $suma;
}

/**
 * Precarga en memoria las imagenes destacadas de una lista de fichas.
 *
 * Sin esto, cada the_post_thumbnail() dentro de un bucle dispara dos consultas
 * propias -una para el adjunto y otra para su metadata-. Medido en local: la
 * pagina de Portafolio hacia 126 consultas, de las cuales 41 eran "SELECT *
 * FROM wp_posts WHERE ID = N" sueltas, y Acerca llegaba a 167.
 *
 * _prime_post_caches() las trae todas de golpe en dos consultas.
 *
 * @param array $posts Fichas ya recuperadas.
 */
function ovc_precargar_miniaturas( $posts ) {
	if ( empty( $posts ) ) {
		return;
	}

	$ids = wp_list_pluck( $posts, 'ID' );
	update_meta_cache( 'post', $ids );

	$adjuntos = array();
	foreach ( $ids as $id ) {
		foreach ( array( '_thumbnail_id', '_ovc_foto_1', '_ovc_foto_2' ) as $clave ) {
			$adjunto = (int) get_post_meta( $id, $clave, true );
			if ( $adjunto ) {
				$adjuntos[] = $adjunto;
			}
		}
	}

	if ( $adjuntos ) {
		_prime_post_caches( array_unique( $adjuntos ), false, true );
	}
}

/* -------------------------------------------------------------------------
 * Modulos del curriculum y de los proyectos
 * ---------------------------------------------------------------------- */
require_once OVC_DIR . '/includes/cv-post-types.php';
require_once OVC_DIR . '/includes/cv-metaboxes.php';
require_once OVC_DIR . '/includes/cv-shortcodes.php';
require_once OVC_DIR . '/includes/cv-aficiones.php';
require_once OVC_DIR . '/includes/proyecto-meta.php';
require_once OVC_DIR . '/includes/proyecto-portada.php';
require_once OVC_DIR . '/includes/proyectos-rejilla.php';
require_once OVC_DIR . '/includes/inicio-bloques.php';

// Depende de ovc_icono_servicio(), que define inicio-bloques.php.
require_once OVC_DIR . '/includes/cv-habilidades.php';
require_once OVC_DIR . '/includes/contacto.php';
require_once OVC_DIR . '/includes/captcha.php';
require_once OVC_DIR . '/includes/imagenes-animadas.php';

// La pantalla de importacion solo tiene sentido en el escritorio.
if ( is_admin() ) {
	require_once OVC_DIR . '/includes/cv-importar.php';
	require_once OVC_DIR . '/includes/proyectos-importar.php';
	require_once OVC_DIR . '/includes/proyectos-imagenes.php';
	require_once OVC_DIR . '/includes/cv-fotos.php';
	require_once OVC_DIR . '/includes/aplicar-diseno.php';
}

/* -------------------------------------------------------------------------
 * Limpieza de cabecera y pequenas mejoras
 * ---------------------------------------------------------------------- */

/**
 * Quita la version de WordPress de la cabecera y de los feeds.
 * No es una defensa por si sola, pero evita anunciar la version exacta.
 */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/**
 * Desactiva los metodos XML-RPC de autenticacion.
 * El .htaccess ya bloquea xmlrpc.php; esto cubre el caso de que esa regla
 * se pierda al regenerar el archivo.
 */
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'wp_headers', function ( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
} );

/**
 * Mensaje de error de acceso generico.
 * Por defecto WordPress distingue "usuario desconocido" de "contrasena
 * incorrecta", lo que confirma a un atacante que un usuario existe.
 */
add_filter( 'login_errors', function () {
	return __( 'Los datos introducidos no son correctos.', 'ov-child' );
} );

/**
 * Año del pie de página.
 *
 * El pie decía "Copyright © 2020" porque el texto de los créditos de Divi es
 * plano y no ejecuta PHP: alguien tiene que acordarse de cambiarlo cada enero.
 * Con esto, escribiendo {año} en Divi > Opciones del tema > Pie de página >
 * Créditos, el año se pone solo. Además, si quedó escrito un año suelto y ya
 * pasó, se corrige al vuelo en vez de mostrar una fecha caducada.
 *
 * @param string $creditos Texto guardado.
 * @return string
 */
function ovc_anio_pie( $creditos ) {
	$anio = gmdate( 'Y' );

	$creditos = str_replace( array( '{año}', '{ano}', '{year}' ), $anio, $creditos );

	// Un año de cuatro cifras pegado al símbolo de copyright que ya pasó.
	$creditos = preg_replace_callback(
		'/(©\s*)(19|20)\d{2}/u',
		function ( $m ) use ( $anio ) {
			return $m[1] . $anio;
		},
		$creditos
	);

	return $creditos;
}
// Divi no filtra et_get_footer_credits(), pero et_get_option() sí dispara
// et_get_option_{opcion} justo antes de devolver el valor.
add_filter( 'et_get_option_et_divi_custom_footer_credits', 'ovc_anio_pie' );

/**
 * Textos e idioma del tema hijo.
 */
add_action( 'after_setup_theme', function () {
	load_child_theme_textdomain( 'ov-child', OVC_DIR . '/languages' );
} );
