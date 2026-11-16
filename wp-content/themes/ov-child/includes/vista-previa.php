<?php
/**
 * Etiquetas para la vista previa al compartir un enlace (Open Graph y X).
 *
 * WhatsApp, LinkedIn, Facebook, X y Telegram leen estas etiquetas para pintar
 * la tarjeta del enlace: titulo, descripcion e imagen. Ni WordPress ni Divi las
 * ponen por defecto, y sin ellas el enlace al portafolio se compartia pelado,
 * sin foto ni resumen. Tambien se pone la meta description, que es lo que
 * Google suele mostrar bajo el titulo.
 *
 * Imagen: la destacada de la entrada (los proyectos la tienen). Si no hay -las
 * paginas no tienen-, la foto del Premio Alfonso Palacio Rudas. Se usa el
 * recorte horizontal de Divi (1080x675, unos 130 KB): WhatsApp descarta las
 * imagenes pesadas y LinkedIn recorta a 1,91:1, asi que una foto vertical
 * entera saldria cortada por la cabeza.
 *
 * Descripcion: el extracto; si no hay, el primer parrafo con cuerpo del
 * contenido (sin los shortcodes de Divi); si tampoco, el lema del sitio.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Imagen por defecto: foto del premio (adjunto 969). */
define( 'OVC_VISTA_PREVIA_IMAGEN', 969 );

/**
 * Nombre que firma las tarjetas.
 *
 * @return string
 */
function ovc_vp_nombre() {
	return apply_filters( 'ovc_nombre_publico', 'Guillermo Nieto' );
}

/**
 * Corta un texto en el ultimo espacio antes de $max caracteres.
 *
 * @param string $texto Texto.
 * @param int    $max   Longitud maxima.
 * @return string
 */
function ovc_vp_recortar( $texto, $max = 160 ) {
	$texto = trim( preg_replace( '/\s+/u', ' ', $texto ) );

	if ( mb_strlen( $texto ) <= $max ) {
		return $texto;
	}

	$corte   = mb_substr( $texto, 0, $max - 1 );
	$espacio = mb_strrpos( $corte, ' ' );

	// Regex /u y no rtrim(): rtrim va byte a byte y partiria letras con tilde.
	return preg_replace( '/[\s,;:.\x{2014}-]+$/u', '', mb_substr( $corte, 0, $espacio ? $espacio : $max - 1 ) ) . '…';
}

/**
 * Descripcion de la entrada.
 *
 * @param WP_Post|null $post    Entrada.
 * @param bool         $portada Si no hay nada util, usar la de la portada.
 * @return string
 */
function ovc_vp_descripcion( $post, $portada = true ) {
	// Un extracto con llaves es codigo, no texto: la pagina Portafolio tiene
	// CSS pegado en el extracto desde 2020 y salia "@media (min-width...".
	if ( $post && has_excerpt( $post ) && false === strpbrk( $post->post_excerpt, '{}' ) ) {
		return ovc_vp_recortar( wp_strip_all_tags( get_the_excerpt( $post ) ) );
	}

	if ( $post ) {
		$c = $post->post_content;
		// Lo que no es texto para leer: modulos de codigo, estilos y scripts
		// sueltos, y el contenido dinamico de Divi (@ET-DC@...@, en base64).
		$c = preg_replace( '#\[et_pb_code\b[^\]]*\].*?\[/et_pb_code\]#is', ' ', $c );
		$c = preg_replace( '#<(style|script)\b[^>]*>.*?</\1>#is', ' ', $c );
		$c = preg_replace( '#@ET-DC@[A-Za-z0-9+/=]+@#', ' ', $c );
		// Fuera los shortcodes de Divi pero no el texto que envuelven.
		$html = wpautop( preg_replace( '/\[\/?[a-z][a-z0-9_]*[^\]]*\]/i', "\n\n", $c ) );

		if ( preg_match_all( '#<p\b[^>]*>(.*?)</p>#is', $html, $m ) ) {
			foreach ( $m[1] as $parrafo ) {
				$texto = trim( html_entity_decode( wp_strip_all_tags( $parrafo ), ENT_QUOTES, 'UTF-8' ) );
				// Un parrafo de verdad, no un rotulo ni un subtitulo.
				if ( mb_strlen( $texto ) >= 80 && preg_match( '/[.!?…]/u', $texto ) ) {
					return ovc_vp_recortar( $texto );
				}
			}
		}
	}

	$id_portada = (int) get_option( 'page_on_front' );
	if ( $portada && $id_portada && ( ! $post || $post->ID !== $id_portada ) ) {
		return ovc_vp_descripcion( get_post( $id_portada ), false );
	}

	return get_bloginfo( 'description' );
}

/**
 * Imagen de la tarjeta.
 *
 * @param WP_Post|null $post Entrada.
 * @return array|null url, ancho, alto, tipo, alt.
 */
function ovc_vp_imagen( $post ) {
	$id = ( $post && has_post_thumbnail( $post ) ) ? get_post_thumbnail_id( $post ) : OVC_VISTA_PREVIA_IMAGEN;

	$src = false;
	foreach ( array( 'et-pb-post-main-image-fullwidth', 'large' ) as $tam ) {
		if ( image_get_intermediate_size( $id, $tam ) ) {
			$src = wp_get_attachment_image_src( $id, $tam );
			break;
		}
	}
	if ( ! $src ) {
		$src = wp_get_attachment_image_src( $id, 'full' );
	}
	if ( ! $src ) {
		return null;
	}

	$alt = get_post_meta( $id, '_wp_attachment_image_alt', true );

	return array(
		'url'   => $src[0],
		'ancho' => (int) $src[1],
		'alto'  => (int) $src[2],
		'tipo'  => get_post_mime_type( $id ),
		'alt'   => $alt ? $alt : ( $post ? get_the_title( $post ) : ovc_vp_nombre() ),
	);
}

/**
 * Pinta las etiquetas en <head>.
 */
function ovc_vista_previa() {
	if ( ! is_front_page() && ! is_singular() ) {
		return; // 404, archivos...: sin tarjeta propia.
	}

	$post   = get_queried_object() instanceof WP_Post ? get_queried_object() : null;
	$nombre = ovc_vp_nombre();

	if ( is_front_page() ) {
		$titulo = $nombre . ' · ' . get_bloginfo( 'description' );
		$url    = home_url( '/' );
	} else {
		$titulo = single_post_title( '', false ) . ' · ' . $nombre;
		$url    = wp_get_canonical_url( $post );
	}

	$meta = array(
		array( 'name', 'description', ovc_vp_descripcion( $post ) ),
		array( 'property', 'og:locale', get_locale() ),
		array( 'property', 'og:type', is_singular( 'project' ) ? 'article' : 'website' ),
		array( 'property', 'og:site_name', $nombre . ' · Portafolio' ),
		array( 'property', 'og:title', $titulo ),
		array( 'property', 'og:description', ovc_vp_descripcion( $post ) ),
		array( 'property', 'og:url', $url ),
	);

	$img = ovc_vp_imagen( $post );
	if ( $img ) {
		$meta[] = array( 'property', 'og:image', $img['url'] );
		$meta[] = array( 'property', 'og:image:type', $img['tipo'] );
		$meta[] = array( 'property', 'og:image:width', $img['ancho'] );
		$meta[] = array( 'property', 'og:image:height', $img['alto'] );
		$meta[] = array( 'property', 'og:image:alt', $img['alt'] );
	}

	$meta[] = array( 'name', 'twitter:card', $img ? 'summary_large_image' : 'summary' );
	$meta[] = array( 'name', 'twitter:title', $titulo );
	$meta[] = array( 'name', 'twitter:description', ovc_vp_descripcion( $post ) );
	if ( $img ) {
		$meta[] = array( 'name', 'twitter:image', $img['url'] );
		$meta[] = array( 'name', 'twitter:image:alt', $img['alt'] );
	}

	echo "\n<!-- Vista previa al compartir (ov-child/includes/vista-previa.php) -->\n";
	foreach ( $meta as list( $atributo, $clave, $valor ) ) {
		if ( '' === (string) $valor ) {
			continue;
		}
		$es_url = in_array( $clave, array( 'og:url', 'og:image', 'twitter:image' ), true );
		printf(
			"<meta %s=\"%s\" content=\"%s\" />\n",
			$atributo,
			esc_attr( $clave ),
			$es_url ? esc_url( $valor ) : esc_attr( $valor )
		);
	}
}
add_action( 'wp_head', 'ovc_vista_previa', 5 );
