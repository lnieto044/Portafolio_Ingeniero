<?php
/**
 * Sirve enteras las imágenes animadas.
 *
 * WordPress genera copias de cada imagen en varios tamaños. Esas copias las
 * hace la librería de imágenes fotograma a fotograma y se queda con el
 * primero: un WebP o un GIF animado redimensionado deja de moverse.
 *
 * El resultado es desconcertante, porque el archivo original sí está: el
 * recorrido de la aplicación se ve congelado en la pantalla de inicio de
 * sesión y parece que la animación está rota, cuando lo que pasa es que se
 * está entregando otro archivo.
 *
 * Aquí los adjuntos marcados con _ovc_animada se entregan siempre en su
 * tamaño original, se pida el tamaño que se pida, y se les quita el srcset
 * -que volvería a ofrecer las copias fijas-.
 *
 * La marca la pone quien importa la imagen, no se adivina: comprobar si un
 * archivo tiene más de un fotograma obliga a abrirlo en cada petición, y eso
 * se paga en cada carga de página para resolver algo que se sabe una sola vez.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ¿Este adjunto está marcado como animado?
 *
 * @param int $id Adjunto.
 * @return bool
 */
function ovc_es_animada( $id ) {
	return '1' === (string) get_post_meta( (int) $id, '_ovc_animada', true );
}

/**
 * Devuelve siempre el archivo original.
 *
 * @param array|false  $imagen  Datos de la imagen: url, ancho, alto, recortada.
 * @param int          $id      Adjunto.
 * @return array|false
 */
function ovc_animada_tamano_completo( $imagen, $id ) {
	if ( ! ovc_es_animada( $id ) ) {
		return $imagen;
	}

	$meta = wp_get_attachment_metadata( $id );
	$url  = wp_get_attachment_url( $id );

	if ( ! $url ) {
		return $imagen;
	}

	return array(
		$url,
		isset( $meta['width'] ) ? (int) $meta['width'] : 0,
		isset( $meta['height'] ) ? (int) $meta['height'] : 0,
		false,
	);
}
add_filter( 'wp_get_attachment_image_src', 'ovc_animada_tamano_completo', 10, 2 );

/**
 * Sin srcset: cada entrada de esa lista es una copia fija.
 *
 * @param array $fuentes Candidatas.
 * @param array $tamano  Tamaño pedido.
 * @param string $src    Origen.
 * @param array $meta    Metadatos.
 * @param int   $id      Adjunto.
 * @return array
 */
function ovc_animada_sin_srcset( $fuentes, $tamano, $src, $meta, $id ) {
	return ovc_es_animada( $id ) ? array() : $fuentes;
}
add_filter( 'wp_calculate_image_srcset', 'ovc_animada_sin_srcset', 10, 5 );

/**
 * Marca la etiqueta para que el CSS pueda tratarla distinto.
 *
 * Una captura de aplicación es alta y estrecha; las tarjetas del portafolio
 * son apaisadas. Recortarla por el centro deja fuera la cabecera y el pie de
 * la pantalla, que es justo donde se entiende de qué va.
 *
 * @param string $html Etiqueta img.
 * @param int    $id   Adjunto.
 * @return string
 */
function ovc_animada_clase( $html, $id ) {
	if ( ! ovc_es_animada( $id ) || false === strpos( $html, 'class="' ) ) {
		return $html;
	}

	return preg_replace( '/class="/', 'class="ovc-animada ', $html, 1 );
}
add_filter( 'wp_get_attachment_image', 'ovc_animada_clase', 10, 2 );
