<?php
/**
 * Campos de las fichas de Experiencia y Estudios.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Definicion de los campos. Se declara una sola vez y la usan tanto el
 * formulario como el guardado, para que no puedan quedar desincronizados.
 *
 * @param string $post_type Tipo de contenido.
 * @return array
 */
function ovc_campos_cv( $post_type ) {
	// Los tres tipos comparten estructura; solo cambian las etiquetas.
	switch ( $post_type ) {
		case 'cv_estudio':
			$etq_entidad  = __( 'Institución', 'ov-child' );
			$ayu_entidad  = __( 'Ej.: Universidad ITFIP', 'ov-child' );
			$etq_rol      = __( 'Título obtenido', 'ov-child' );
			$ayu_rol      = __( 'Ej.: Ingeniero de Sistemas', 'ov-child' );
			break;
		case 'cv_reconocimiento':
			$etq_entidad  = __( 'Otorgado por', 'ov-child' );
			$ayu_entidad  = __( 'Ej.: Contraloría General de la República', 'ov-child' );
			$etq_rol      = __( 'Distinción', 'ov-child' );
			$ayu_rol      = __( 'Ej.: Categoría Académico, o Primer Lugar', 'ov-child' );
			break;
		default:
			$etq_entidad  = __( 'Empresa', 'ov-child' );
			$ayu_entidad  = __( 'Ej.: Kuxan S.A.S.', 'ov-child' );
			$etq_rol      = __( 'Cargo', 'ov-child' );
			$ayu_rol      = __( 'Ej.: Desarrollador .NET', 'ov-child' );
	}
	$es_estudio      = ( 'cv_estudio' === $post_type );
	$es_experiencia  = ( 'cv_experiencia' === $post_type );

	return array(
		'_ovc_entidad'  => array(
			'etiqueta' => $etq_entidad,
			'tipo'     => 'text',
			'ayuda'    => $ayu_entidad,
		),
		'_ovc_rol'      => array(
			'etiqueta' => $etq_rol,
			'tipo'     => 'text',
			'ayuda'    => $ayu_rol,
		),
		'_ovc_lugar'    => array(
			'etiqueta' => __( 'Ciudad', 'ov-child' ),
			'tipo'     => 'text',
			'ayuda'    => __( 'Ej.: Espinal, Tolima', 'ov-child' ),
		),
		/*
		 * Los puestos de trabajo siempre tienen mes, asi que usan el selector
		 * nativo. Los cursos y los premios muchas veces solo llevan el ano
		 * ("Talento Tech, 2025"), y un input type=month no puede guardar un
		 * ano suelto: para esos se usa un campo de texto que acepta las dos
		 * formas.
		 */
		'_ovc_desde'    => array(
			'etiqueta' => __( 'Desde', 'ov-child' ),
			'tipo'     => $es_experiencia ? 'month' : 'periodo',
			'ayuda'    => $es_experiencia
				? __( 'Mes de inicio.', 'ov-child' )
				: __( 'Año (2025) o año y mes (2025-08).', 'ov-child' ),
		),
		'_ovc_hasta'    => array(
			'etiqueta' => __( 'Hasta', 'ov-child' ),
			'tipo'     => $es_experiencia ? 'month' : 'periodo',
			'ayuda'    => __( 'Déjalo vacío si es una fecha única o si marcas la casilla.', 'ov-child' ),
		),
		'_ovc_academico' => array(
			'etiqueta' => __( 'Es una titulación académica', 'ov-child' ),
			'tipo'     => 'checkbox',
			'ayuda'    => __( 'Márcala en las carreras y títulos. Salen antes que los cursos, como en un currículum.', 'ov-child' ),
			'solo'     => array( 'cv_estudio' ),
		),
		'_ovc_actual'   => array(
			'etiqueta' => $es_estudio ? __( 'En curso', 'ov-child' ) : __( 'Trabajo aquí actualmente', 'ov-child' ),
			'tipo'     => 'checkbox',
			'ayuda'    => __( 'Si está marcada, el periodo termina en "Actualidad".', 'ov-child' ),
		),
		'_ovc_url'      => array(
			'etiqueta' => __( 'Web de la entidad', 'ov-child' ),
			'tipo'     => 'url',
			'ayuda'    => __( 'Opcional.', 'ov-child' ),
		),
		'_ovc_foto_1'   => array(
			'etiqueta' => __( 'Foto 1', 'ov-child' ),
			'tipo'     => 'imagen',
			'ayuda'    => __( 'Foto que ilustra esta etapa. Se muestra al lado del texto.', 'ov-child' ),
		),
		'_ovc_foto_2'   => array(
			'etiqueta' => __( 'Foto 2', 'ov-child' ),
			'tipo'     => 'imagen',
			'ayuda'    => __( 'Segunda foto, montada sobre la primera.', 'ov-child' ),
		),
		'_ovc_soporte'  => array(
			'etiqueta' => __( 'Enlace al soporte', 'ov-child' ),
			'tipo'     => 'url',
			'ayuda'    => __( 'Certificado, diploma o carta laboral (Drive, PDF...).', 'ov-child' ),
		),
	);
}

/**
 * Anade la caja de campos a las dos pantallas de edicion.
 */
function ovc_registrar_metabox_cv() {
	foreach ( array( 'cv_experiencia', 'cv_estudio', 'cv_reconocimiento' ) as $post_type ) {
		add_meta_box(
			'ovc_datos_cv',
			__( 'Datos de la ficha', 'ov-child' ),
			'ovc_pintar_metabox_cv',
			$post_type,
			'normal',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'ovc_registrar_metabox_cv' );

/**
 * Pinta el formulario.
 *
 * @param WP_Post $post Ficha en edicion.
 */
function ovc_pintar_metabox_cv( $post ) {
	wp_nonce_field( 'ovc_guardar_cv_' . $post->ID, 'ovc_nonce_cv' );
	// Necesario para abrir la biblioteca de medios desde los botones "Elegir".
	wp_enqueue_media();
	$campos = ovc_campos_cv( $post->post_type );

	// Hay campos que solo tienen sentido en un tipo de ficha.
	foreach ( $campos as $clave => $campo ) {
		if ( isset( $campo['solo'] ) && ! in_array( $post->post_type, (array) $campo['solo'], true ) ) {
			unset( $campos[ $clave ] );
		}
	}
	?>
	<style>
		.ovc-campos { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
		.ovc-campo label { display: block; font-weight: 600; margin-bottom: 4px; }
		.ovc-campo input[type="text"], .ovc-campo input[type="url"], .ovc-campo input[type="month"] { width: 100%; }
		.ovc-campo p.description { margin-top: 4px; }
		.ovc-imagen__vista { min-height: 90px; margin-bottom: 6px; }
		.ovc-imagen__vista img { max-width: 130px; height: auto; border-radius: 8px; display: block; }
		.ovc-imagen__quitar { color: #b32d2e; margin-left: 10px; }
	</style>
	<script>
	( function () {
		document.addEventListener( 'click', function ( e ) {
			var caja = e.target.closest( '.ovc-imagen' );
			if ( ! caja ) { return; }

			if ( e.target.classList.contains( 'ovc-imagen__elegir' ) ) {
				e.preventDefault();
				var marco = wp.media( { title: 'Elegir foto', multiple: false, library: { type: 'image' } } );
				marco.on( 'select', function () {
					var img = marco.state().get( 'selection' ).first().toJSON();
					var src = ( img.sizes && img.sizes.thumbnail ) ? img.sizes.thumbnail.url : img.url;
					caja.querySelector( 'input[type=hidden]' ).value = img.id;
					caja.querySelector( '.ovc-imagen__vista' ).innerHTML = '<img src="' + src + '" alt="" />';
				} );
				marco.open();
			}

			if ( e.target.classList.contains( 'ovc-imagen__quitar' ) ) {
				e.preventDefault();
				caja.querySelector( 'input[type=hidden]' ).value = '';
				caja.querySelector( '.ovc-imagen__vista' ).innerHTML = '';
			}
		} );
	} )();
	</script>
	<div class="ovc-campos">
		<?php foreach ( $campos as $clave => $campo ) : ?>
			<?php $valor = get_post_meta( $post->ID, $clave, true ); ?>
			<div class="ovc-campo">
				<?php if ( 'checkbox' === $campo['tipo'] ) : ?>
					<label for="<?php echo esc_attr( $clave ); ?>">
						<input type="checkbox"
						       id="<?php echo esc_attr( $clave ); ?>"
						       name="<?php echo esc_attr( $clave ); ?>"
						       value="1" <?php checked( $valor, '1' ); ?> />
						<?php echo esc_html( $campo['etiqueta'] ); ?>
					</label>
				<?php elseif ( 'imagen' === $campo['tipo'] ) : ?>
					<label for="<?php echo esc_attr( $clave ); ?>"><?php echo esc_html( $campo['etiqueta'] ); ?></label>
					<div class="ovc-imagen" data-destino="<?php echo esc_attr( $clave ); ?>">
						<div class="ovc-imagen__vista">
							<?php if ( $valor && wp_get_attachment_image( (int) $valor, 'thumbnail' ) ) : ?>
								<?php echo wp_get_attachment_image( (int) $valor, 'thumbnail' ); ?>
							<?php endif; ?>
						</div>
						<input type="hidden" id="<?php echo esc_attr( $clave ); ?>"
						       name="<?php echo esc_attr( $clave ); ?>" value="<?php echo esc_attr( $valor ); ?>" />
						<button type="button" class="button ovc-imagen__elegir"><?php esc_html_e( 'Elegir', 'ov-child' ); ?></button>
						<button type="button" class="button-link ovc-imagen__quitar"><?php esc_html_e( 'Quitar', 'ov-child' ); ?></button>
					</div>
				<?php else : ?>
					<label for="<?php echo esc_attr( $clave ); ?>"><?php echo esc_html( $campo['etiqueta'] ); ?></label>
					<input type="<?php echo esc_attr( 'periodo' === $campo['tipo'] ? 'text' : $campo['tipo'] ); ?>"
					       id="<?php echo esc_attr( $clave ); ?>"
					       name="<?php echo esc_attr( $clave ); ?>"
					       value="<?php echo esc_attr( $valor ); ?>"
						<?php if ( 'periodo' === $campo['tipo'] ) : ?>
					       pattern="[0-9]{4}(-(0[1-9]|1[0-2]))?" placeholder="2025 o 2025-08"
						<?php endif; ?>
					        />
				<?php endif; ?>
				<p class="description"><?php echo esc_html( $campo['ayuda'] ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
	<p class="description">
		<?php esc_html_e( 'El texto largo (que hiciste en el puesto o que cubre el estudio) va en el editor de arriba.', 'ov-child' ); ?>
	</p>
	<?php
}

/**
 * Guarda los campos.
 *
 * @param int     $post_id Ficha.
 * @param WP_Post $post    Objeto de la ficha.
 */
function ovc_guardar_metabox_cv( $post_id, $post ) {
	if ( ! in_array( $post->post_type, array( 'cv_experiencia', 'cv_estudio', 'cv_reconocimiento' ), true ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['ovc_nonce_cv'] )
		|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ovc_nonce_cv'] ) ), 'ovc_guardar_cv_' . $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( ovc_campos_cv( $post->post_type ) as $clave => $campo ) {
		if ( isset( $campo['solo'] ) && ! in_array( $post->post_type, (array) $campo['solo'], true ) ) {
			continue;
		}
		if ( 'checkbox' === $campo['tipo'] ) {
			$valor = isset( $_POST[ $clave ] ) ? '1' : '';
		} elseif ( 'imagen' === $campo['tipo'] ) {
			$id    = isset( $_POST[ $clave ] ) ? absint( wp_unslash( $_POST[ $clave ] ) ) : 0;
			// Solo se guarda si el adjunto existe y es una imagen.
			$valor = ( $id && wp_attachment_is_image( $id ) ) ? (string) $id : '';
		} elseif ( 'url' === $campo['tipo'] ) {
			$valor = isset( $_POST[ $clave ] ) ? esc_url_raw( wp_unslash( $_POST[ $clave ] ) ) : '';
		} elseif ( 'month' === $campo['tipo'] || 'periodo' === $campo['tipo'] ) {
			$bruto = isset( $_POST[ $clave ] ) ? sanitize_text_field( wp_unslash( $_POST[ $clave ] ) ) : '';
			// Se guarda como AAAA o AAAA-MM: asi ordena bien comparando cadenas.
			$patron = ( 'month' === $campo['tipo'] )
				? '/^\d{4}-(0[1-9]|1[0-2])$/'
				: '/^\d{4}(-(0[1-9]|1[0-2]))?$/';
			$valor = preg_match( $patron, $bruto ) ? $bruto : '';
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
add_action( 'save_post', 'ovc_guardar_metabox_cv', 10, 2 );
