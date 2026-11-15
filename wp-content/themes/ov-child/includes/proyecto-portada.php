<?php
/**
 * Genera una portada para los proyectos que todavia no tienen imagen propia.
 *
 * La alternativa era dejar la miniatura vacia (el modulo de portafolio de Divi
 * pinta un hueco gris) o meter fotos de banco que no tienen nada que ver con el
 * trabajo. Aqui se dibuja una portada con el nombre del proyecto sobre la
 * paleta del sitio: queda consistente, no miente sobre lo que hay dentro y se
 * sustituye por la captura real en cuanto exista, sin tocar nada mas.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ruta a la tipografia empaquetada con el tema.
 *
 * @return string|false
 */
function ovc_fuente_portada() {
	$ruta = OVC_DIR . '/assets/fonts/Inter.ttf';
	return file_exists( $ruta ) ? $ruta : false;
}

/**
 * Parejas de color. Se elige una por el nombre del proyecto, asi la rejilla no
 * queda monotona pero todo sigue dentro de la paleta del sitio.
 *
 * @return array
 */
function ovc_paletas_portada() {
	return array(
		array( array( 0, 0, 255 ), array( 70, 31, 239 ) ),
		array( array( 70, 31, 239 ), array( 124, 58, 237 ) ),
		array( array( 30, 27, 120 ), array( 0, 0, 255 ) ),
		array( array( 12, 74, 156 ), array( 70, 31, 239 ) ),
		// El color de marca es siempre el primero del par, así que ninguno puede
		// empezar en gris: la portada saldría apagada al lado de las demás.
		array( array( 30, 41, 122 ), array( 57, 62, 70 ) ),
	);
}

/**
 * Parte un texto en lineas que quepan en un ancho dado.
 *
 * @param string $texto  Texto.
 * @param string $fuente Ruta de la fuente.
 * @param int    $tam    Tamano en puntos.
 * @param int    $ancho  Ancho maximo en px.
 * @return array
 */
function ovc_partir_texto( $texto, $fuente, $tam, $ancho ) {
	$palabras = preg_split( '/\s+/u', trim( $texto ) );
	$lineas   = array();
	$actual   = '';

	foreach ( $palabras as $palabra ) {
		$prueba = '' === $actual ? $palabra : $actual . ' ' . $palabra;
		$caja   = imagettfbbox( $tam, 0, $fuente, $prueba );

		if ( $caja && ( $caja[2] - $caja[0] ) > $ancho && '' !== $actual ) {
			$lineas[] = $actual;
			$actual   = $palabra;
		} else {
			$actual = $prueba;
		}
	}
	if ( '' !== $actual ) {
		$lineas[] = $actual;
	}
	return $lineas;
}

/**
 * Dibuja texto con trazo grueso.
 *
 * GD no sabe aplicar negrita a una fuente que no la trae como archivo aparte,
 * asi que se pinta el mismo texto varias veces desplazado un pixel.
 *
 * @param resource|GdImage $img    Lienzo.
 * @param int              $tam    Tamano.
 * @param int              $x      Posicion X.
 * @param int              $y      Posicion Y.
 * @param int              $color  Color asignado.
 * @param string           $fuente Ruta de la fuente.
 * @param string           $texto  Texto.
 * @param bool             $recio  Si se refuerza el trazo.
 */
function ovc_texto( $img, $tam, $x, $y, $color, $fuente, $texto, $recio = true ) {
	$pasadas = $recio
		? array( array( 0, 0 ), array( 1, 0 ), array( 0, 1 ), array( 1, 1 ) )
		: array( array( 0, 0 ) );

	foreach ( $pasadas as $p ) {
		imagettftext( $img, $tam, 0, $x + $p[0], $y + $p[1], $color, $fuente, $texto );
	}
}

/**
 * Rectángulo con las esquinas redondeadas.
 *
 * GD no trae ninguna primitiva para esto: se compone con un rectángulo central,
 * dos laterales y un cuarto de círculo en cada esquina.
 *
 * @param resource|GdImage $img   Lienzo.
 * @param int              $x1    Izquierda.
 * @param int              $y1    Arriba.
 * @param int              $x2    Derecha.
 * @param int              $y2    Abajo.
 * @param int              $r     Radio.
 * @param int              $color Color asignado.
 */
function ovc_rect_redondo( $img, $x1, $y1, $x2, $y2, $r, $color ) {
	$r = max( 0, min( $r, (int) ( ( $x2 - $x1 ) / 2 ), (int) ( ( $y2 - $y1 ) / 2 ) ) );

	imagefilledrectangle( $img, $x1 + $r, $y1, $x2 - $r, $y2, $color );
	imagefilledrectangle( $img, $x1, $y1 + $r, $x2, $y2 - $r, $color );

	if ( $r > 0 ) {
		$d = $r * 2;
		imagefilledellipse( $img, $x1 + $r, $y1 + $r, $d, $d, $color );
		imagefilledellipse( $img, $x2 - $r, $y1 + $r, $d, $d, $color );
		imagefilledellipse( $img, $x1 + $r, $y2 - $r, $d, $d, $color );
		imagefilledellipse( $img, $x2 - $r, $y2 - $r, $d, $d, $color );
	}
}

/**
 * Número estable entre min y max, derivado de un texto.
 *
 * Da variación sin azar de verdad: el mismo proyecto saca siempre las mismas
 * medidas, así que al regenerar la portada no cambia. Sin esto, los proyectos
 * de una misma categoría salían calcados y la rejilla parecía una plantilla.
 *
 * @param string $semilla Texto base.
 * @param int    $i       Índice del elemento.
 * @param int    $min     Mínimo.
 * @param int    $max     Máximo.
 * @return int
 */
function ovc_variacion( $semilla, $i, $min, $max ) {
	$h = abs( crc32( $semilla . '#' . $i ) );
	return $min + ( $h % ( max( 1, $max - $min + 1 ) ) );
}

/**
 * Dibuja dentro de la ventana un esquema abstracto según la categoría.
 *
 * No pretende parecerse a la aplicación real: es un boceto de interfaz. La
 * gracia es que cada categoría tenga una silueta distinta, para que la rejilla
 * del portafolio no sea la misma figura repetida veinticinco veces.
 *
 * @param resource|GdImage $img       Lienzo.
 * @param array            $caja      x1, y1, x2, y2 del área de contenido.
 * @param string           $categoria Categoría del proyecto.
 * @param array            $tinta     Colores ya asignados.
 */
function ovc_boceto_interfaz( $img, $caja, $categoria, $tinta, $semilla = '' ) {
	list( $x1, $y1, $x2, $y2 ) = $caja;
	$ancho = $x2 - $x1;
	$alto  = $y2 - $y1;

	switch ( ovc_normalizar( $categoria ) ) {

		case 'datos':
			// Barras de distinta altura y un anillo al lado.
			$base   = $y2 - 40;
			$cuantas = ovc_variacion( $semilla, 0, 5, 8 );
			$alturas = array();
			for ( $n = 0; $n < $cuantas; $n++ ) {
				$alturas[] = ovc_variacion( $semilla, $n + 1, 30, 95 ) / 100;
			}
			$paso = (int) ( ( $ancho * .52 ) / count( $alturas ) );
			foreach ( $alturas as $n => $f ) {
				$h  = (int) ( ( $alto - 90 ) * $f );
				$bx = $x1 + 30 + $n * $paso;
				ovc_rect_redondo( $img, $bx, $base - $h, $bx + $paso - 12, $base, 5, $n % 2 ? $tinta['suave'] : $tinta['marca'] );
			}
			$cx = $x2 - (int) ( $ancho * .18 );
			$cy = $y1 + (int) ( $alto * .5 );
			$corte = ovc_variacion( $semilla, 20, 140, 300 );
			imagefilledarc( $img, $cx, $cy, 150, 150, 0, $corte, $tinta['marca'], IMG_ARC_PIE );
			imagefilledarc( $img, $cx, $cy, 150, 150, $corte, 360, $tinta['suave'], IMG_ARC_PIE );
			imagefilledellipse( $img, $cx, $cy, 80, 80, $tinta['papel'] );
			break;

		case 'desarrollo':
			// Barra lateral y renglones de código de largo variable.
			ovc_rect_redondo( $img, $x1, $y1, $x1 + 110, $y2, 8, $tinta['hueso'] );
			for ( $n = 0; $n < 5; $n++ ) {
				ovc_rect_redondo( $img, $x1 + 18, $y1 + 24 + $n * 40, $x1 + 92, $y1 + 38 + $n * 40, 4, $tinta['linea'] );
			}
			$largos = array();
			for ( $n = 0; $n < 9; $n++ ) {
				$largos[] = ovc_variacion( $semilla, $n + 30, 28, 88 ) / 100;
			}
			foreach ( $largos as $n => $f ) {
				$ly = $y1 + 26 + $n * 34;
				if ( $ly > $y2 - 30 ) {
					break;
				}
				ovc_rect_redondo( $img, $x1 + 140, $ly, $x1 + 140 + (int) ( ( $ancho - 170 ) * $f ), $ly + 15, 4, $n % 3 ? $tinta['linea'] : $tinta['marca'] );
			}
			break;

		case 'ecommerce':
		case 'e-commerce':
			// Rejilla de fichas de producto.
			$cols = 3;
			$fils = 2;
			$gw   = (int) ( ( $ancho - 40 ) / $cols );
			$gh   = (int) ( ( $alto - 30 ) / $fils );
			for ( $f = 0; $f < $fils; $f++ ) {
				for ( $c = 0; $c < $cols; $c++ ) {
					$gx = $x1 + $c * $gw;
					$gy = $y1 + $f * $gh;
					ovc_rect_redondo( $img, $gx, $gy, $gx + $gw - 24, $gy + $gh - 40, 10, $tinta['hueso'] );
					ovc_rect_redondo( $img, $gx, $gy, $gx + $gw - 24, $gy + (int) ( $gh * .52 ), 10, $tinta['suave'] );
					ovc_rect_redondo( $img, $gx + 14, $gy + $gh - 32, $gx + $gw - 60, $gy + $gh - 20, 4, $tinta['linea'] );
				}
			}
			break;

		case 'aplicaciones':
			// Silueta de móvil con filas de lista.
			$mw = 190;
			$mx = $x1 + 40;
			ovc_rect_redondo( $img, $mx, $y1, $mx + $mw, $y2, 22, $tinta['hueso'] );
			ovc_rect_redondo( $img, $mx + 14, $y1 + 26, $mx + $mw - 14, $y1 + 96, 8, $tinta['marca'] );
			for ( $n = 0; $n < 4; $n++ ) {
				ovc_rect_redondo( $img, $mx + 14, $y1 + 116 + $n * 42, $mx + $mw - 30, $y1 + 148 + $n * 42, 6, $tinta['suave'] );
			}
			for ( $n = 0; $n < 6; $n++ ) {
				$ly = $y1 + 20 + $n * 46;
				if ( $ly > $y2 - 40 ) {
					break;
				}
				imagefilledellipse( $img, $mx + $mw + 70, $ly + 14, 26, 26, $n % 2 ? $tinta['suave'] : $tinta['marca'] );
				ovc_rect_redondo( $img, $mx + $mw + 100, $ly + 6, $x2 - ( $n % 3 ) * 70, $ly + 22, 5, $tinta['linea'] );
			}
			break;

		case 'marketing':
			// Tres indicadores arriba y una curva debajo.
			$tw = (int) ( ( $ancho - 40 ) / 3 );
			for ( $n = 0; $n < 3; $n++ ) {
				$tx = $x1 + $n * ( $tw + 20 );
				ovc_rect_redondo( $img, $tx, $y1, $tx + $tw, $y1 + 96, 10, $tinta['hueso'] );
				ovc_rect_redondo( $img, $tx + 16, $y1 + 22, $tx + $tw - 40, $y1 + 34, 4, $tinta['linea'] );
				ovc_rect_redondo( $img, $tx + 16, $y1 + 48, $tx + $tw - 70, $y1 + 76, 6, $n ? $tinta['suave'] : $tinta['marca'] );
			}
			$puntos = array();
			for ( $n = 0; $n < 8; $n++ ) {
				$puntos[] = ovc_variacion( $semilla, $n + 50, 25, 95 ) / 100;
			}
			imagesetthickness( $img, 8 );
			$base = $y2 - 20;
			$paso = (int) ( $ancho / ( count( $puntos ) - 1 ) );
			for ( $n = 0; $n < count( $puntos ) - 1; $n++ ) {
				imageline(
					$img,
					$x1 + $n * $paso,
					$base - (int) ( ( $alto * .45 ) * $puntos[ $n ] ),
					$x1 + ( $n + 1 ) * $paso,
					$base - (int) ( ( $alto * .45 ) * $puntos[ $n + 1 ] ),
					$tinta['marca']
				);
			}
			imagesetthickness( $img, 1 );
			break;

		case 'diseno':
			// Muestras de color y formas.
			$sw = 130;
			for ( $n = 0; $n < 3; $n++ ) {
				$sx = $x1 + $n * ( $sw + 22 );
				ovc_rect_redondo( $img, $sx, $y1, $sx + $sw, $y1 + 150, 14, 0 === $n ? $tinta['marca'] : ( 1 === $n ? $tinta['suave'] : $tinta['hueso'] ) );
			}
			imagefilledellipse( $img, $x2 - 110, $y1 + 80, 150, 150, $tinta['suave'] );
			for ( $n = 0; $n < 3; $n++ ) {
				ovc_rect_redondo( $img, $x1, $y2 - 90 + $n * 30, $x1 + (int) ( $ancho * ( .8 - $n * .22 ) ), $y2 - 76 + $n * 30, 5, $tinta['linea'] );
			}
			break;

		default:
			// Cabecera y filas: sirve para cualquier categoría nueva.
			ovc_rect_redondo( $img, $x1, $y1, $x2, $y1 + 70, 10, $tinta['suave'] );
			for ( $n = 0; $n < 6; $n++ ) {
				$ly = $y1 + 96 + $n * 44;
				if ( $ly > $y2 - 24 ) {
					break;
				}
				ovc_rect_redondo( $img, $x1, $ly, $x2 - ( $n % 3 ) * 90, $ly + 22, 6, $n % 2 ? $tinta['hueso'] : $tinta['linea'] );
			}
	}
}

/**
 * Crea el PNG de la portada y lo devuelve como ruta temporal.
 *
 * La primera versión era un degradado de color a pantalla completa con el
 * nombre encima. Se veía como lo que era: un relleno. Y peor aún, quedaba al
 * lado de las capturas reales de los proyectos antiguos, así que la rejilla
 * mezclaba pantallazos con rectángulos de color.
 *
 * Esta versión dibuja una ventana de aplicación con un boceto de interfaz
 * dentro. No finge ser una captura del proyecto -no tiene texto legible ni
 * datos- pero comparte la silueta con las capturas de al lado, así que la
 * rejilla se ve pareja.
 *
 * @param string $titulo    Nombre del proyecto.
 * @param string $categoria Categoría a mostrar.
 * @return string|WP_Error Ruta del archivo o error.
 */
function ovc_dibujar_portada( $titulo, $categoria = '' ) {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return new WP_Error( 'sin_gd', __( 'La extensión GD de PHP no está disponible.', 'ov-child' ) );
	}
	$fuente = ovc_fuente_portada();
	if ( ! $fuente ) {
		return new WP_Error( 'sin_fuente', __( 'Falta assets/fonts/Inter.ttf en el tema hijo.', 'ov-child' ) );
	}

	$ancho = 1200;
	$alto  = 750;
	$img   = imagecreatetruecolor( $ancho, $alto );
	imageantialias( $img, true );

	// El color de marca varía por proyecto, pero el fondo es siempre claro:
	// con fondo oscuro la portada pesaba más que las capturas de al lado.
	$paletas = ovc_paletas_portada();
	$par     = $paletas[ abs( crc32( $titulo ) ) % count( $paletas ) ];
	$marca   = $par[0];

	$tinta = array(
		'fondo'  => imagecolorallocate( $img, 238, 241, 248 ),
		'papel'  => imagecolorallocate( $img, 255, 255, 255 ),
		'hueso'  => imagecolorallocate( $img, 232, 236, 245 ),
		'linea'  => imagecolorallocate( $img, 214, 221, 233 ),
		'marca'  => imagecolorallocate( $img, $marca[0], $marca[1], $marca[2] ),
		'suave'  => imagecolorallocate(
			$img,
			(int) round( $marca[0] + ( 255 - $marca[0] ) * .62 ),
			(int) round( $marca[1] + ( 255 - $marca[1] ) * .62 ),
			(int) round( $marca[2] + ( 255 - $marca[2] ) * .62 )
		),
		'texto'  => imagecolorallocate( $img, 57, 62, 70 ),
		'tenue'  => imagecolorallocate( $img, 122, 130, 144 ),
		'sombra' => imagecolorallocatealpha( $img, 57, 62, 70, 112 ),
	);

	imagefilledrectangle( $img, 0, 0, $ancho, $alto, $tinta['fondo'] );

	// Franja de color arriba: identifica la categoría de un vistazo.
	imagefilledrectangle( $img, 0, 0, $ancho, 10, $tinta['marca'] );

	// Ventana, con una sombra falsa dibujada detrás.
	/*
	 * La ventana ocupa casi todo el lienzo. Antes se dejaba sitio abajo para
	 * el nombre del proyecto, pero la tarjeta de la rejilla ya lo escribe justo
	 * debajo: salía dos veces y encima pegado al borde de la imagen. La
	 * categoría, en la barra de título, basta para identificarla.
	 */
	$vx1 = 70;
	$vy1 = 72;
	$vx2 = $ancho - 70;
	$vy2 = $alto - 72;

	for ( $d = 10; $d > 0; $d-- ) {
		ovc_rect_redondo( $img, $vx1 - $d, $vy1 + $d, $vx2 + $d, $vy2 + $d + 4, 18, $tinta['sombra'] );
	}
	ovc_rect_redondo( $img, $vx1, $vy1, $vx2, $vy2, 16, $tinta['papel'] );

	// Barra de título con los tres puntos.
	ovc_rect_redondo( $img, $vx1, $vy1, $vx2, $vy1 + 54, 16, $tinta['hueso'] );
	imagefilledrectangle( $img, $vx1, $vy1 + 38, $vx2, $vy1 + 54, $tinta['hueso'] );
	foreach ( array( 0, 1, 2 ) as $n ) {
		imagefilledellipse( $img, $vx1 + 30 + $n * 26, $vy1 + 27, 14, 14, $tinta['linea'] );
	}
	if ( $categoria ) {
		ovc_texto( $img, 13, $vx1 + 120, $vy1 + 33, $tinta['tenue'], $fuente, mb_strtoupper( $categoria, 'UTF-8' ), false );
	}

	ovc_boceto_interfaz( $img, array( $vx1 + 40, $vy1 + 96, $vx2 - 40, $vy2 - 40 ), $categoria, $tinta, $titulo );

	$tmp = wp_tempnam( 'ovc-portada' ) . '.png';
	imagepng( $img, $tmp, 8 );
	imagedestroy( $img );

	return $tmp;
}

/**
 * Genera la portada y la deja como imagen destacada del proyecto.
 *
 * @param int    $post_id   Proyecto.
 * @param string $categoria Categoria a rotular.
 * @return int|WP_Error ID del adjunto.
 */
function ovc_asignar_portada( $post_id, $categoria = '' ) {
	if ( has_post_thumbnail( $post_id ) ) {
		return (int) get_post_thumbnail_id( $post_id );
	}

	$titulo = get_the_title( $post_id );
	$png    = ovc_dibujar_portada( $titulo, $categoria );
	if ( is_wp_error( $png ) ) {
		return $png;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$archivo = array(
		'name'     => 'portada-' . sanitize_title( $titulo ) . '.png',
		'type'     => 'image/png',
		'tmp_name' => $png,
		'error'    => 0,
		'size'     => filesize( $png ),
	);

	$adjunto = media_handle_sideload( $archivo, $post_id, $titulo );

	if ( is_wp_error( $adjunto ) ) {
		@unlink( $png ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return $adjunto;
	}

	// Texto alternativo: la portada es decorativa, pero el lector de pantalla
	// debe poder anunciar de que proyecto es.
	update_post_meta( $adjunto, '_wp_attachment_image_alt', $titulo );
	set_post_thumbnail( $post_id, $adjunto );

	return (int) $adjunto;
}

/**
 * Iniciales de una entidad, para la insignia.
 *
 * "Ministerio de las Culturas, las Artes y los Saberes" -> "MC".
 * Se saltan las palabras de enlace para que las iniciales digan algo.
 *
 * @param string $texto Nombre de la entidad.
 * @return string
 */
function ovc_iniciales( $texto ) {
	$vacias = array( 'de', 'del', 'la', 'las', 'el', 'los', 'y', 'e', 'en', 'para', 'por', 'con', 'sa', 'sas', 's.a.', 's.a.s.' );
	$palabras = preg_split( '/[\s\-—·|.,]+/u', trim( wp_strip_all_tags( $texto ) ) );
	$iniciales = '';

	foreach ( $palabras as $palabra ) {
		if ( '' === $palabra || in_array( mb_strtolower( $palabra, 'UTF-8' ), $vacias, true ) ) {
			continue;
		}
		$iniciales .= mb_strtoupper( mb_substr( $palabra, 0, 1, 'UTF-8' ), 'UTF-8' );
		if ( mb_strlen( $iniciales ) >= 2 ) {
			break;
		}
	}

	// Con una sola palabra util ("SENA", "Lunanet") una inicial suelta se lee
	// mal; se completa con la segunda letra.
	if ( 1 === mb_strlen( $iniciales ) ) {
		foreach ( $palabras as $palabra ) {
			if ( '' !== $palabra && ! in_array( mb_strtolower( $palabra, 'UTF-8' ), $vacias, true ) ) {
				$iniciales = mb_strtoupper( mb_substr( $palabra, 0, 2, 'UTF-8' ), 'UTF-8' );
				break;
			}
		}
	}

	return '' !== $iniciales ? $iniciales : mb_strtoupper( mb_substr( trim( $texto ), 0, 2, 'UTF-8' ), 'UTF-8' );
}

/**
 * Dibuja una insignia cuadrada con las iniciales de la entidad.
 *
 * Se usa en experiencia, estudios y reconocimientos cuando no hay un logo
 * subido. No pretende sustituir al logo real: da un ancla visual a cada
 * entrada para que la lista no sea una pared de texto.
 *
 * @param string $entidad Nombre a resumir en iniciales.
 * @return string|WP_Error Ruta del PNG.
 */
function ovc_dibujar_insignia( $entidad ) {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return new WP_Error( 'sin_gd', __( 'La extensión GD de PHP no está disponible.', 'ov-child' ) );
	}
	$fuente = ovc_fuente_portada();
	if ( ! $fuente ) {
		return new WP_Error( 'sin_fuente', __( 'Falta assets/fonts/Inter.ttf en el tema hijo.', 'ov-child' ) );
	}

	$lado = 480;
	$img  = imagecreatetruecolor( $lado, $lado );
	imageantialias( $img, true );

	$paletas = ovc_paletas_portada();
	$par     = $paletas[ abs( crc32( $entidad ) ) % count( $paletas ) ];
	list( $desde, $hasta ) = $par;

	for ( $y = 0; $y < $lado; $y++ ) {
		$t = $y / max( 1, $lado - 1 );
		$c = imagecolorallocate(
			$img,
			(int) round( $desde[0] + ( $hasta[0] - $desde[0] ) * $t ),
			(int) round( $desde[1] + ( $hasta[1] - $desde[1] ) * $t ),
			(int) round( $desde[2] + ( $hasta[2] - $desde[2] ) * $t )
		);
		imagefilledrectangle( $img, 0, $y, $lado, $y, $c );
	}

	$velo = imagecolorallocatealpha( $img, 255, 255, 255, 112 );
	imagesetthickness( $img, 3 );
	imageellipse( $img, $lado - 40, 60, 260, 260, $velo );
	imageellipse( $img, 40, $lado - 40, 200, 200, $velo );

	$blanco    = imagecolorallocate( $img, 255, 255, 255 );
	$iniciales = ovc_iniciales( $entidad );
	$tam       = 150;
	$caja      = imagettfbbox( $tam, 0, $fuente, $iniciales );
	$ancho_txt = $caja[2] - $caja[0];
	$alto_txt  = $caja[1] - $caja[7];

	ovc_texto(
		$img,
		$tam,
		(int) ( ( $lado - $ancho_txt ) / 2 ),
		(int) ( ( $lado + $alto_txt ) / 2 ) - 8,
		$blanco,
		$fuente,
		$iniciales
	);

	$tmp = wp_tempnam( 'ovc-insignia' ) . '.png';
	imagepng( $img, $tmp, 8 );
	imagedestroy( $img );

	return $tmp;
}

/**
 * Genera la insignia y la deja como imagen destacada de la ficha del CV.
 *
 * @param int $post_id Ficha.
 * @return int|WP_Error
 */
function ovc_asignar_insignia( $post_id ) {
	if ( has_post_thumbnail( $post_id ) ) {
		return (int) get_post_thumbnail_id( $post_id );
	}

	$entidad = get_post_meta( $post_id, '_ovc_entidad', true );
	if ( ! $entidad ) {
		$entidad = get_the_title( $post_id );
	}

	$png = ovc_dibujar_insignia( $entidad );
	if ( is_wp_error( $png ) ) {
		return $png;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$adjunto = media_handle_sideload(
		array(
			'name'     => 'insignia-' . sanitize_title( $entidad ) . '.png',
			'type'     => 'image/png',
			'tmp_name' => $png,
			'error'    => 0,
			'size'     => filesize( $png ),
		),
		$post_id,
		$entidad
	);

	if ( is_wp_error( $adjunto ) ) {
		@unlink( $png ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		return $adjunto;
	}

	update_post_meta( $adjunto, '_wp_attachment_image_alt', $entidad );
	set_post_thumbnail( $post_id, $adjunto );

	return (int) $adjunto;
}
