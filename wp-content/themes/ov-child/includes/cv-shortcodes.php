<?php
/**
 * Shortcodes que pintan la experiencia y los estudios en la pagina "Acerca".
 *
 * Uso dentro de un modulo de texto de Divi:
 *   [cv_experiencia]
 *   [cv_estudios]
 *   [cv_lista tipo="experiencia" limite="3" columnas="2"]
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Convierte AAAA-MM en "Noviembre 2019" usando los nombres de mes de WordPress.
 *
 * @param string $aaaa_mm Valor guardado.
 * @return string
 */
function ovc_mes_legible( $aaaa_mm ) {
	$valor = (string) $aaaa_mm;

	// Solo el ano: los cursos y premios se suelen fechar asi.
	if ( preg_match( '/^\d{4}$/', $valor ) ) {
		return $valor;
	}
	if ( ! preg_match( '/^(\d{4})-(\d{2})$/', $valor, $m ) ) {
		return '';
	}
	global $wp_locale;
	$mes = $wp_locale ? $wp_locale->get_month( $m[2] ) : $m[2];
	return ucfirst( $mes ) . ' ' . $m[1];
}

/**
 * Texto del periodo de una ficha: "Mayo 2019 - Octubre 2019", "Agosto 2017"
 * o "Noviembre 2019 - Actualidad".
 *
 * @param int $post_id Ficha.
 * @return string
 */
function ovc_formatear_periodo( $post_id ) {
	$desde  = ovc_mes_legible( get_post_meta( $post_id, '_ovc_desde', true ) );
	$hasta  = ovc_mes_legible( get_post_meta( $post_id, '_ovc_hasta', true ) );
	$actual = '1' === get_post_meta( $post_id, '_ovc_actual', true );

	if ( $actual ) {
		$hasta = __( 'Actualidad', 'ov-child' );
	}
	if ( $desde && $hasta ) {
		return $desde . ' - ' . $hasta;
	}
	return $desde ? $desde : $hasta;
}

/**
 * Shortcode generico.
 *
 * @param array $atts Atributos.
 * @return string HTML.
 */
function ovc_shortcode_cv_lista( $atts ) {
	$atts = shortcode_atts(
		array(
			'tipo'       => 'experiencia', // experiencia | estudios | reconocimientos
			'limite'     => -1,
			'columnas'   => 1,
			/*
			 * Solo aplica a estudios:
			 *   'formal'    -> titulaciones (carrera, tecnología, técnico)
			 *   'no-formal' -> cursos, bootcamps, diplomados y certificaciones
			 *   'todo'      -> las dos cosas, formales primero (por defecto)
			 */
			'formacion'  => 'todo',
			/*
			 * Cómo se presentan las dos fotos de cada entrada:
			 *   'una'          -> solo la primera, grande y alineada arriba
			 *   'dos'          -> las dos, mismo tamaño, apiladas y alineadas
			 *   'superpuestas' -> la segunda montada sobre la primera
			 *   'ninguna'      -> sin fotos
			 */
			'fotos'      => 'dos',
			/*
			 * Acabado de la foto:
			 *   'acento'  -> bloque de color detrás, desplazado y alternando lado
			 *   'tarjeta' -> marco blanco grueso, como una foto impresa
			 *   'limpio'  -> solo esquinas redondeadas y sombra suave
			 */
			'marco'      => 'acento',
		),
		$atts,
		'cv_lista'
	);

	$mapa = array(
		'estudios'         => 'cv_estudio',
		'estudio'          => 'cv_estudio',
		'reconocimientos'  => 'cv_reconocimiento',
		'reconocimiento'   => 'cv_reconocimiento',
		'premios'          => 'cv_reconocimiento',
	);
	$post_type = isset( $mapa[ $atts['tipo'] ] ) ? $mapa[ $atts['tipo'] ] : 'cv_experiencia';

	// Se piden todas y se ordenan en PHP. Ordenar por meta_key en SQL dejaria
	// fuera las fichas que aun no tienen fecha, y el numero de entradas de un
	// curriculum es pequeno: no compensa complicar la consulta.
	$fichas = get_posts(
		array(
			'post_type'        => $post_type,
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'orderby'          => 'menu_order',
			'order'            => 'ASC',
			'suppress_filters' => false,
		)
	);

	ovc_precargar_miniaturas( $fichas );

	usort(
		$fichas,
		function ( $a, $b ) {
			/*
			 * Las titulaciones academicas van primero, pase lo que pase con las
			 * fechas: en un curriculum la carrera encabeza la seccion aunque sea
			 * de 2017 y los cursos sean de 2026.
			 */
			$aa = get_post_meta( $a->ID, '_ovc_academico', true ) ? 1 : 0;
			$ab = get_post_meta( $b->ID, '_ovc_academico', true ) ? 1 : 0;
			if ( $aa !== $ab ) {
				return $ab <=> $aa;
			}

			// Sin fecha van al final: '' es menor que cualquier '2019-05'.
			$fa = (string) get_post_meta( $a->ID, '_ovc_desde', true );
			$fb = (string) get_post_meta( $b->ID, '_ovc_desde', true );
			if ( $fa === $fb ) {
				return $a->menu_order <=> $b->menu_order;
			}
			return strcmp( $fb, $fa ); // Mas reciente primero.
		}
	);

	// Separar educación formal de la complementaria.
	$formacion = strtolower( (string) $atts['formacion'] );
	if ( 'cv_estudio' === $post_type && in_array( $formacion, array( 'formal', 'no-formal', 'no_formal', 'complementaria' ), true ) ) {
		$quiere_formal = ( 'formal' === $formacion );
		$fichas        = array_values(
			array_filter(
				$fichas,
				function ( $f ) use ( $quiere_formal ) {
					$es_formal = (bool) get_post_meta( $f->ID, '_ovc_academico', true );
					return $es_formal === $quiere_formal;
				}
			)
		);
	}

	$limite = (int) $atts['limite'];
	if ( $limite > 0 ) {
		$fichas = array_slice( $fichas, 0, $limite );
	}

	if ( empty( $fichas ) ) {
		return current_user_can( 'edit_posts' )
			? '<p class="ovc-vacio">' . esc_html__( 'Todavía no hay fichas publicadas en esta sección. Añádelas desde el escritorio.', 'ov-child' ) . '</p>'
			: '';
	}

	$columnas = max( 1, min( 3, (int) $atts['columnas'] ) );

	$estilos_validos = array( 'una', 'dos', 'superpuestas', 'ninguna' );
	$estilo_fotos    = in_array( $atts['fotos'], $estilos_validos, true ) ? $atts['fotos'] : 'dos';

	$marcos_validos = array( 'acento', 'tarjeta', 'limpio' );
	$marco          = in_array( $atts['marco'], $marcos_validos, true ) ? $atts['marco'] : 'acento';

	ob_start();
	printf(
		'<div class="ovc-cv ovc-cv--cols-%s ovc-cv--marco-%s">',
		esc_attr( $columnas ),
		esc_attr( $marco )
	);

	global $post;
	$post_original = $post;

	foreach ( $fichas as $post ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		setup_postdata( $post );
		$id       = get_the_ID();
		$entidad  = get_post_meta( $id, '_ovc_entidad', true );
		$rol      = get_post_meta( $id, '_ovc_rol', true );
		$lugar    = get_post_meta( $id, '_ovc_lugar', true );
		$url      = get_post_meta( $id, '_ovc_url', true );
		$soporte  = get_post_meta( $id, '_ovc_soporte', true );
		$periodo  = ovc_formatear_periodo( $id );

		// Las dos fotos que ilustran la etapa. Puede haber una, dos o ninguna.
		$fotos = array_values(
			array_filter(
				array(
					(int) get_post_meta( $id, '_ovc_foto_1', true ),
					(int) get_post_meta( $id, '_ovc_foto_2', true ),
				)
			)
		);

		if ( 'ninguna' === $estilo_fotos ) {
			$fotos = array();
		} elseif ( 'una' === $estilo_fotos ) {
			$fotos = array_slice( $fotos, 0, 1 );
		}

		/*
		 * Que va en la linea grande cambia segun el tipo:
		 *
		 *   - Experiencia: manda la empresa, y el cargo va al lado.
		 *   - Estudios y reconocimientos: manda el titulo de la ficha, porque
		 *     ahi es donde esta el nombre del curso o del premio; la entidad
		 *     que lo expide va debajo. Antes se pintaba la entidad en grande
		 *     y el nombre del premio no aparecia por ningun lado.
		 */
		if ( 'cv_experiencia' === $post_type ) {
			$titular   = $entidad ? $entidad : get_the_title();
			$acompana  = $rol;
			$secundaria = '';
		} else {
			$titular    = get_the_title();
			$acompana   = '';
			$secundaria = $entidad;
		}
		?>
		<?php
		$clases = 'ovc-cv__item';
		if ( has_post_thumbnail( $id ) ) {
			$clases .= ' ovc-cv__item--con-imagen';
		}
		if ( $fotos ) {
			$clases .= ' ovc-cv__item--con-fotos';
			// Con una sola foto la columna puede ser más ancha: se ve de verdad.
			$clases .= ' ovc-cv__item--fotos-' . $estilo_fotos;
		}
		?>
		<article class="<?php echo esc_attr( $clases ); ?>">
			<?php if ( has_post_thumbnail( $id ) ) : ?>
				<div class="ovc-cv__logo">
					<?php
					// Si hay logo real subido se usa ese; si no, la insignia generada.
					echo get_the_post_thumbnail(
						$id,
						'thumbnail',
						array(
							'loading' => 'lazy',
							'alt'     => $entidad ? $entidad : get_the_title(),
						)
					);
					?>
				</div>
			<?php endif; ?>

			<div class="ovc-cv__datos">
			<h4 class="ovc-cv__titulo">
				<?php if ( $url ) : ?>
					<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $titular ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $titular ); ?>
				<?php endif; ?>
				<?php if ( $acompana ) : ?>
					<span class="ovc-cv__sep">||</span> <span class="ovc-cv__rol"><?php echo esc_html( $acompana ); ?></span>
				<?php endif; ?>
			</h4>

			<?php if ( $secundaria ) : ?>
				<p class="ovc-cv__entidad">
					<?php echo esc_html( $secundaria ); ?>
					<?php if ( $rol ) : ?>
						<span class="ovc-cv__etiqueta"><?php echo esc_html( $rol ); ?></span>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<?php if ( $periodo || $lugar ) : ?>
				<h6 class="ovc-cv__periodo">
					<?php echo esc_html( $periodo ); ?>
					<?php if ( $lugar ) : ?>
						<span class="ovc-cv__lugar"><?php echo esc_html( ( $periodo ? ' · ' : '' ) . $lugar ); ?></span>
					<?php endif; ?>
				</h6>
			<?php endif; ?>

			<?php
			/*
			 * Se formatea el contenido a mano en vez de llamar a the_content().
			 * Divi engancha su maquetador en el filtro 'the_content', y llamarlo
			 * aqui dentro (el shortcode ya se ejecuta durante ese filtro) hace
			 * que Divi intente procesar la ficha como si fuera una pagina del
			 * builder. wp_kses_post ademas limita el HTML a lo permitido.
			 */
			$texto = trim( (string) $post->post_content );
			?>
			<?php if ( '' !== $texto ) : ?>
				<div class="ovc-cv__texto">
					<?php echo wpautop( wp_kses_post( do_shortcode( $texto ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>

			<?php if ( $soporte ) : ?>
				<p class="ovc-cv__soporte">
					<a href="<?php echo esc_url( $soporte ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Ver soporte', 'ov-child' ); ?>
					</a>
				</p>
			<?php endif; ?>
			</div>

			<?php if ( $fotos ) : ?>
				<div class="ovc-cv__fotos ovc-cv__fotos--<?php echo count( $fotos ); ?> ovc-cv__fotos--<?php echo esc_attr( $estilo_fotos ); ?>">
					<?php foreach ( $fotos as $n => $foto_id ) : ?>
						<figure class="ovc-cv__foto ovc-cv__foto--<?php echo (int) ( $n + 1 ); ?>">
							<?php
							/*
							 * El <span> no es decorativo: es el que recorta la
							 * imagen cuando se acerca bajo el raton. Si el
							 * recorte se pusiera en el <figure>, se llevaria
							 * por delante el bloque de color desplazado que
							 * dibuja su ::before, que es el acabado de todas
							 * las fotos del sitio.
							 */
							?>
							<span class="ovc-cv__marco">
								<?php
								echo wp_get_attachment_image(
									$foto_id,
									'medium_large',
									false,
									array(
										'loading' => 'lazy',
										'alt'     => $entidad ? $entidad : get_the_title(),
									)
								);
								?>
							</span>
						</figure>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</article>
		<?php
	}

	echo '</div>';

	// Devuelve el bucle principal a su sitio: si no, los modulos de Divi que
	// vengan despues en la pagina pintarian los datos de la ultima ficha.
	$post = $post_original; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	wp_reset_postdata();

	return ob_get_clean();
}
add_shortcode( 'cv_lista', 'ovc_shortcode_cv_lista' );

/** Atajos con nombre para no tener que recordar el atributo "tipo". */
add_shortcode( 'cv_experiencia', function ( $atts ) {
	$atts = is_array( $atts ) ? $atts : array();
	$atts['tipo'] = 'experiencia';
	return ovc_shortcode_cv_lista( $atts );
} );

add_shortcode( 'cv_estudios', function ( $atts ) {
	$atts = is_array( $atts ) ? $atts : array();
	$atts['tipo'] = 'estudios';
	return ovc_shortcode_cv_lista( $atts );
} );

add_shortcode( 'cv_educacion_formal', function ( $atts ) {
	$atts = is_array( $atts ) ? $atts : array();
	$atts['tipo']      = 'estudios';
	$atts['formacion'] = 'formal';
	return ovc_shortcode_cv_lista( $atts );
} );

add_shortcode( 'cv_educacion_no_formal', function ( $atts ) {
	$atts = is_array( $atts ) ? $atts : array();
	$atts['tipo']      = 'estudios';
	$atts['formacion'] = 'no-formal';
	return ovc_shortcode_cv_lista( $atts );
} );

add_shortcode( 'cv_reconocimientos', function ( $atts ) {
	$atts = is_array( $atts ) ? $atts : array();
	$atts['tipo'] = 'reconocimientos';
	return ovc_shortcode_cv_lista( $atts );
} );
