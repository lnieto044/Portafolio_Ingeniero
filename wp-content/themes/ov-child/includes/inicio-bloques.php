<?php
/**
 * Bloques de la portada: cifras y servicios.
 *
 * La página de inicio era una foto, un párrafo largo y un hueco. Le faltaba lo
 * que un empresario busca en los primeros diez segundos: qué hace esta
 * persona, qué respalda lo que dice, y dónde escribirle.
 *
 * Las cifras se cuentan de la base de datos, no se escriben a mano: el día que
 * se publique un proyecto más, la portada lo refleja sola.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Año de inicio de la trayectoria, según la ficha de experiencia más antigua.
 *
 * @return int
 */
function ovc_anio_inicio() {
	$anios = array();

	foreach ( get_posts( array( 'post_type' => 'cv_experiencia', 'posts_per_page' => -1 ) ) as $p ) {
		$desde = (string) get_post_meta( $p->ID, '_ovc_desde', true );
		if ( preg_match( '/^(\d{4})/', $desde, $m ) ) {
			$anios[] = (int) $m[1];
		}
	}

	return $anios ? min( $anios ) : (int) gmdate( 'Y' );
}

/**
 * Cuenta las entidades distintas en las que ha trabajado.
 *
 * Se cuentan entidades, no fichas: hay dos etapas como consultor independiente
 * y contarlas dos veces inflaría la cifra.
 *
 * @return int
 */
function ovc_cuantas_entidades() {
	$vistas = array();

	foreach ( get_posts( array( 'post_type' => 'cv_experiencia', 'posts_per_page' => -1 ) ) as $p ) {
		$e = (string) get_post_meta( $p->ID, '_ovc_entidad', true );
		if ( '' !== $e ) {
			$vistas[ ovc_normalizar( $e ) ] = true;
		}
	}

	return count( $vistas );
}

/**
 * Tira de cifras.  [ovc_cifras]
 *
 * @return string
 */
function ovc_shortcode_cifras() {
	$anio = ovc_anio_inicio();

	/*
	 * 'contar' marca las que se animan subiendo desde cero. El año no se
	 * cuenta: ver 2013 trepando desde 0 se lee como un error, no como un
	 * efecto.
	 */
	$cifras = array(
		array(
			'dato'   => (string) wp_count_posts( 'project' )->publish,
			'texto'  => __( 'proyectos entregados', 'ov-child' ),
			'contar' => true,
		),
		array(
			'dato'   => (string) ovc_cuantas_entidades(),
			'texto'  => __( 'entidades y empresas', 'ov-child' ),
			'contar' => true,
		),
		array(
			'dato'   => (string) wp_count_posts( 'cv_reconocimiento' )->publish,
			'texto'  => __( 'reconocimientos', 'ov-child' ),
			'contar' => true,
		),
		array(
			'dato'   => (string) $anio,
			'texto'  => __( 'en tecnología desde', 'ov-child' ),
			'contar' => false,
		),
	);

	ob_start();
	?>
	<div class="ovc-cifras">
		<?php foreach ( $cifras as $c ) : ?>
			<div class="ovc-cifra">
				<span class="ovc-cifra__dato"
					<?php if ( ! empty( $c['contar'] ) ) : ?>
						data-contar="<?php echo esc_attr( $c['dato'] ); ?>"
					<?php endif; ?>
				><?php echo esc_html( $c['dato'] ); ?></span>
				<span class="ovc-cifra__texto"><?php echo esc_html( $c['texto'] ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ovc_cifras', 'ovc_shortcode_cifras' );

/**
 * Los servicios que se ofrecen.
 *
 * Se declaran aquí y no en la base de datos porque son cuatro y no cambian con
 * la frecuencia de un proyecto. Editar este array los cambia en la portada y
 * en la página de Servicios a la vez.
 *
 * @return array
 */
function ovc_lista_servicios() {
	return array(
		array(
			'titulo' => __( 'Análisis de Datos e Inteligencia de Negocio', 'ov-child' ),
			'texto'  => __( 'Extraigo, depuro y analizo datos de múltiples fuentes, y construyo tableros de indicadores para decidir sobre cifras y no sobre impresiones.', 'ov-child' ),
			'tecs'   => 'Power BI, SQL Server, MySQL, Python, Excel avanzado',
			'icono'  => 'datos',
		),
		array(
			'titulo' => __( 'Validación y Calidad de Información', 'ov-child' ),
			'texto'  => __( 'Verifico veracidad, consistencia y trazabilidad: cruces entre sistemas, detección de inconsistencias y duplicidades, consulta en listas restrictivas y verificación de títulos y experiencia laboral.', 'ov-child' ),
			'tecs'   => 'Metodología reconocida con el Premio Alfonso Palacio Rudas',
			'icono'  => 'validacion',
		),
		array(
			'titulo' => __( 'Desarrollo Web y de Software', 'ov-child' ),
			'texto'  => __( 'Diseño, desarrollo y pruebas de aplicaciones con bases de datos relacionales, con documentación técnica y manuales de usuario.', 'ov-child' ),
			'tecs'   => '.NET, PHP, WordPress, SQL',
			'icono'  => 'codigo',
		),
		array(
			'titulo' => __( 'Soporte Técnico y Gestión de Servicios TI', 'ov-child' ),
			'texto'  => __( 'Mesa de ayuda bajo buenas prácticas ITIL: registro de casos, seguimiento de indicadores y cumplimiento de Acuerdos de Nivel de Servicio.', 'ov-child' ),
			'tecs'   => 'Microsoft 365, SharePoint, inventario de activos',
			'icono'  => 'soporte',
		),
	);
}

/**
 * Trazado SVG de cada icono.
 *
 * En SVG y no con la fuente de iconos de Divi: así se controla el grosor y el
 * color desde el CSS y no dependen de que cargue una fuente externa.
 *
 * @param string $nombre Icono.
 * @return string
 */
function ovc_icono_servicio( $nombre ) {
	$trazos = array(
		'datos'      => '<path d="M4 20V10M10 20V4M16 20v-7M22 20V8"/>',
		'validacion' => '<path d="M20 6 9 17l-5-5"/>',
		'codigo'     => '<path d="m8 6-6 6 6 6M16 6l6 6-6 6"/>',
		'soporte'    => '<path d="M4 15v-3a8 8 0 0 1 16 0v3M4 15a2 2 0 0 0 2 2h1v-6H6a2 2 0 0 0-2 2zM20 15a2 2 0 0 1-2 2h-1v-6h1a2 2 0 0 1 2 2z"/>',
	);

	$d = isset( $trazos[ $nombre ] ) ? $trazos[ $nombre ] : $trazos['datos'];

	return '<svg class="ovc-servicio__icono" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
		. ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

/**
 * Rejilla de servicios.  [ovc_servicios]
 *
 * @param array $atts Atributos.
 * @return string
 */
function ovc_shortcode_servicios( $atts ) {
	$atts = shortcode_atts(
		array(
			'columnas' => 2,
			'limite'   => -1,
			'contacto' => 'off', // 'on' añade un enlace a Contacto en cada tarjeta.
		),
		$atts,
		'ovc_servicios'
	);

	$servicios = ovc_lista_servicios();
	if ( (int) $atts['limite'] > 0 ) {
		$servicios = array_slice( $servicios, 0, (int) $atts['limite'] );
	}

	$columnas = max( 1, min( 4, (int) $atts['columnas'] ) );

	ob_start();
	?>
	<div class="ovc-servicios ovc-servicios--cols-<?php echo esc_attr( $columnas ); ?>">
		<?php foreach ( $servicios as $n => $s ) : ?>
			<article class="ovc-servicio">
				<span class="ovc-servicio__marca">
					<?php echo ovc_icono_servicio( $s['icono'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
				<span class="ovc-servicio__numero"><?php echo esc_html( sprintf( '%02d', $n + 1 ) ); ?></span>
				<h3 class="ovc-servicio__titulo"><?php echo esc_html( $s['titulo'] ); ?></h3>
				<p class="ovc-servicio__texto"><?php echo esc_html( $s['texto'] ); ?></p>
				<p class="ovc-servicio__tecs"><?php echo esc_html( $s['tecs'] ); ?></p>
				<?php if ( 'on' === $atts['contacto'] ) : ?>
					<a class="ovc-servicio__enlace" href="<?php echo esc_url( home_url( '/contacto/' ) ); ?>">
						<?php esc_html_e( 'Hablemos de esto', 'ov-child' ); ?>
					</a>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ovc_servicios', 'ovc_shortcode_servicios' );

/**
 * Cifras vivas dentro del texto de un botón de Divi.
 *
 * El botón de la portada decía «Ver los 36 proyectos», escrito a mano. En
 * cuanto se publicó uno más pasó a mentir, y nadie se entera hasta que un
 * visitante cuenta las tarjetas.
 *
 * Ahora en el maquetador se escribe «Ver los %proyectos% proyectos» y el
 * número lo pone la base de datos en cada carga. Los módulos de Divi no
 * ejecutan shortcodes en el texto de un botón, así que se sustituye sobre la
 * salida ya montada.
 *
 * @param string $salida Lo que devolvió el shortcode.
 * @param string $tag    Nombre del shortcode.
 * @return string
 */
function ovc_cifras_en_botones( $salida, $tag ) {
	if ( 'et_pb_button' !== $tag || false === strpos( $salida, '%' ) ) {
		return $salida;
	}

	$fichas = array(
		'%proyectos%'       => (string) wp_count_posts( 'project' )->publish,
		'%reconocimientos%' => (string) wp_count_posts( 'cv_reconocimiento' )->publish,
	);

	return str_replace( array_keys( $fichas ), array_values( $fichas ), $salida );
}
add_filter( 'do_shortcode_tag', 'ovc_cifras_en_botones', 10, 2 );
