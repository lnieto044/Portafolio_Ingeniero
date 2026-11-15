<?php
/**
 * Ficha tecnica para los proyectos de Divi.
 *
 * Los 11 proyectos del portafolio explican que hacen, pero no con que estan
 * hechos, en que ano, para quien, ni donde se pueden ver. Estos campos anaden
 * ese dato sin tener que maquetarlo a mano en cada proyecto.
 *
 * Uso dentro del proyecto: [proyecto_ficha]
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Campos de la ficha tecnica.
 *
 * @return array
 */
function ovc_campos_proyecto() {
	return array(
		'_ovc_p_cliente' => array(
			'etiqueta' => __( 'Cliente o contexto', 'ov-child' ),
			'tipo'     => 'text',
			'ayuda'    => __( 'Ej.: Proyecto académico ITFIP, o el nombre del cliente.', 'ov-child' ),
		),
		'_ovc_p_anio'    => array(
			'etiqueta' => __( 'Año', 'ov-child' ),
			'tipo'     => 'number',
			'ayuda'    => __( 'Ej.: 2019', 'ov-child' ),
		),
		'_ovc_p_rol'     => array(
			'etiqueta' => __( 'Mi rol', 'ov-child' ),
			'tipo'     => 'text',
			'ayuda'    => __( 'Ej.: Análisis, diseño y desarrollo.', 'ov-child' ),
		),
		'_ovc_p_stack'   => array(
			'etiqueta' => __( 'Tecnologías', 'ov-child' ),
			'tipo'     => 'text',
			'ayuda'    => __( 'Separadas por comas. Ej.: PHP, MySQL, Bootstrap', 'ov-child' ),
		),
		'_ovc_p_url'     => array(
			'etiqueta' => __( 'Ver en vivo', 'ov-child' ),
			'tipo'     => 'url',
			'ayuda'    => __( 'URL pública del proyecto, si sigue en línea.', 'ov-child' ),
		),
		'_ovc_p_repo'      => array(
			'etiqueta' => __( 'Código fuente', 'ov-child' ),
			'tipo'     => 'url',
			'ayuda'    => __( 'Repositorio de GitHub u otro.', 'ov-child' ),
		),
		'_ovc_p_destacado' => array(
			'etiqueta' => __( 'Mostrar en «Proyectos destacados»', 'ov-child' ),
			'tipo'     => 'checkbox',
			'ayuda'    => __( 'Sale en la rejilla del shortcode [proyectos_destacados].', 'ov-child' ),
		),
	);
}

/**
 * Anade la caja al editor de proyectos.
 */
add_action( 'add_meta_boxes', function () {
	add_meta_box(
		'ovc_ficha_proyecto',
		__( 'Ficha técnica del proyecto', 'ov-child' ),
		'ovc_pintar_metabox_proyecto',
		'project',
		'side',
		'default'
	);
} );

/**
 * Pinta el formulario de la ficha tecnica.
 *
 * @param WP_Post $post Proyecto en edicion.
 */
function ovc_pintar_metabox_proyecto( $post ) {
	wp_nonce_field( 'ovc_guardar_proyecto_' . $post->ID, 'ovc_nonce_proyecto' );
	foreach ( ovc_campos_proyecto() as $clave => $campo ) {
		$valor = get_post_meta( $post->ID, $clave, true );

		if ( 'checkbox' === $campo['tipo'] ) {
			printf(
				'<p><label for="%1$s"><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s /> <strong>%3$s</strong></label><br />
				 <span class="description">%4$s</span></p>',
				esc_attr( $clave ),
				checked( $valor, '1', false ),
				esc_html( $campo['etiqueta'] ),
				esc_html( $campo['ayuda'] )
			);
			continue;
		}

		printf(
			'<p><label for="%1$s"><strong>%2$s</strong></label><br />
			 <input type="%3$s" id="%1$s" name="%1$s" value="%4$s" style="width:100%%" />
			 <span class="description">%5$s</span></p>',
			esc_attr( $clave ),
			esc_html( $campo['etiqueta'] ),
			esc_attr( $campo['tipo'] ),
			esc_attr( $valor ),
			esc_html( $campo['ayuda'] )
		);
	}
}

/**
 * Guarda la ficha tecnica.
 *
 * @param int     $post_id Proyecto.
 * @param WP_Post $post    Objeto.
 */
function ovc_guardar_metabox_proyecto( $post_id, $post ) {
	if ( 'project' !== $post->post_type ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['ovc_nonce_proyecto'] )
		|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ovc_nonce_proyecto'] ) ), 'ovc_guardar_proyecto_' . $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( ovc_campos_proyecto() as $clave => $campo ) {
		if ( 'checkbox' === $campo['tipo'] ) {
			$valor = isset( $_POST[ $clave ] ) ? '1' : '';
		} elseif ( 'url' === $campo['tipo'] ) {
			$valor = isset( $_POST[ $clave ] ) ? esc_url_raw( wp_unslash( $_POST[ $clave ] ) ) : '';
		} elseif ( 'number' === $campo['tipo'] ) {
			$valor = isset( $_POST[ $clave ] ) ? (string) absint( wp_unslash( $_POST[ $clave ] ) ) : '';
			$valor = ( '0' === $valor ) ? '' : $valor;
		} else {
			$valor = isset( $_POST[ $clave ] ) ? sanitize_text_field( wp_unslash( $_POST[ $clave ] ) ) : '';
		}

		if ( '' === $valor ) {
			delete_post_meta( $post_id, $clave );
		} else {
			update_post_meta( $post_id, $clave, $valor );
		}
	}
}
add_action( 'save_post', 'ovc_guardar_metabox_proyecto', 10, 2 );

/**
 * Pinta la ficha tecnica dentro de un proyecto.
 *
 * @param array $atts Atributos (id opcional para pintar otro proyecto).
 * @return string HTML.
 */
function ovc_shortcode_proyecto_ficha( $atts ) {
	$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'proyecto_ficha' );
	$id   = $atts['id'] ? absint( $atts['id'] ) : get_the_ID();

	if ( ! $id || 'project' !== get_post_type( $id ) ) {
		return '';
	}

	$cliente = get_post_meta( $id, '_ovc_p_cliente', true );
	$anio    = get_post_meta( $id, '_ovc_p_anio', true );
	$rol     = get_post_meta( $id, '_ovc_p_rol', true );
	$stack   = get_post_meta( $id, '_ovc_p_stack', true );
	$url     = get_post_meta( $id, '_ovc_p_url', true );
	$repo    = get_post_meta( $id, '_ovc_p_repo', true );

	if ( ! $cliente && ! $anio && ! $rol && ! $stack && ! $url && ! $repo ) {
		return '';
	}

	$filas = array(
		__( 'Cliente', 'ov-child' ) => $cliente,
		__( 'Año', 'ov-child' )     => $anio,
		__( 'Mi rol', 'ov-child' )  => $rol,
	);

	ob_start();
	?>
	<div class="ovc-ficha">
		<h3 class="ovc-ficha__titulo"><?php esc_html_e( 'Ficha técnica', 'ov-child' ); ?></h3>
		<dl class="ovc-ficha__datos">
			<?php foreach ( $filas as $etiqueta => $valor ) : ?>
				<?php if ( $valor ) : ?>
					<dt><?php echo esc_html( $etiqueta ); ?></dt>
					<dd><?php echo esc_html( $valor ); ?></dd>
				<?php endif; ?>
			<?php endforeach; ?>
		</dl>

		<?php if ( $stack ) : ?>
			<ul class="ovc-ficha__stack">
				<?php foreach ( array_filter( array_map( 'trim', explode( ',', $stack ) ) ) as $tec ) : ?>
					<li><?php echo esc_html( $tec ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $url || $repo ) : ?>
			<p class="ovc-ficha__enlaces">
				<?php if ( $url ) : ?>
					<a class="ovc-boton" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ver el proyecto', 'ov-child' ); ?></a>
				<?php endif; ?>
				<?php if ( $repo ) : ?>
					<a class="ovc-boton ovc-boton--linea" href="<?php echo esc_url( $repo ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Código fuente', 'ov-child' ); ?></a>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'proyecto_ficha', 'ovc_shortcode_proyecto_ficha' );

/*
 * La ficha técnica ya no se añade sola al final de los proyectos.
 *
 * Se enganchaba a the_content() para que apareciera en los 36 sin tocarlos uno
 * a uno, pero en la página de proyecto no aporta: los maquetados con el
 * constructor ya muestran sus datos en su propio diseño, y los demás los
 * tienen en la columna derecha de single-project.php. Quedaba un bloque gris
 * repitiendo etiquetas al pie.
 *
 * El shortcode [proyecto_ficha] sigue existiendo por si se quiere colocar a
 * mano en algún sitio concreto.
 */
