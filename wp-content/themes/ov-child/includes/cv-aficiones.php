<?php
/**
 * Aficiones.
 *
 * Uso:  [cv_aficiones]
 *       [cv_aficiones titulo="AFICIONES" columnas="4"]
 *
 * El shortcode pinta el titulo y la rejilla, o NADA en absoluto si todavia no
 * hay ninguna afición publicada. Asi la sección se puede dejar puesta en la
 * página «Acerca» desde ya: no aparece un encabezado huérfano mientras la
 * lista está vacía, y se enciende sola en cuanto se publique la primera.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra el tipo de contenido.
 */
function ovc_registrar_cpt_aficion() {
	register_post_type(
		'cv_aficion',
		array(
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'hierarchical'        => false,
			'capability_type'     => 'post',
			'menu_position'       => 22,
			'menu_icon'           => 'dashicons-heart',
			'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
			'description'         => __( 'Intereses y aficiones para la página Acerca.', 'ov-child' ),
			'labels'              => array(
				'name'               => __( 'Aficiones', 'ov-child' ),
				'singular_name'      => __( 'Afición', 'ov-child' ),
				'menu_name'          => __( 'Aficiones', 'ov-child' ),
				'add_new'            => __( 'Añadir afición', 'ov-child' ),
				'add_new_item'       => __( 'Añadir afición', 'ov-child' ),
				'edit_item'          => __( 'Editar afición', 'ov-child' ),
				'new_item'           => __( 'Nueva afición', 'ov-child' ),
				'view_item'          => __( 'Ver afición', 'ov-child' ),
				'search_items'       => __( 'Buscar aficiones', 'ov-child' ),
				'not_found'          => __( 'Todavía no hay aficiones. Añade la primera.', 'ov-child' ),
				'not_found_in_trash' => __( 'No hay aficiones en la papelera.', 'ov-child' ),
			),
		)
	);
}
add_action( 'init', 'ovc_registrar_cpt_aficion' );

/**
 * Campo del icono.
 */
add_action(
	'add_meta_boxes',
	function () {
		add_meta_box(
			'ovc_aficion_icono',
			__( 'Icono', 'ov-child' ),
			'ovc_pintar_metabox_aficion',
			'cv_aficion',
			'side'
		);
	}
);

/**
 * Formulario del icono.
 *
 * @param WP_Post $post Ficha.
 */
function ovc_pintar_metabox_aficion( $post ) {
	wp_nonce_field( 'ovc_guardar_aficion_' . $post->ID, 'ovc_nonce_aficion' );
	$valor = get_post_meta( $post->ID, '_ovc_a_icono', true );
	?>
	<p>
		<label for="_ovc_a_icono"><strong><?php esc_html_e( 'Emoji', 'ov-child' ); ?></strong></label><br />
		<input type="text" id="_ovc_a_icono" name="_ovc_a_icono" maxlength="8"
		       value="<?php echo esc_attr( $valor ); ?>" style="width:100%;font-size:22px;text-align:center" />
		<span class="description">
			<?php esc_html_e( 'Un emoji, por ejemplo 📷 ⚽ 🎧 ✈️ 📚. Si lo dejas vacío se usa la imagen destacada, y si tampoco hay, las iniciales.', 'ov-child' ); ?>
		</span>
	</p>
	<?php
}

/**
 * Guarda el icono.
 *
 * @param int     $post_id Ficha.
 * @param WP_Post $post    Objeto.
 */
function ovc_guardar_metabox_aficion( $post_id, $post ) {
	if ( 'cv_aficion' !== $post->post_type ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! isset( $_POST['ovc_nonce_aficion'] )
		|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ovc_nonce_aficion'] ) ), 'ovc_guardar_aficion_' . $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$valor = isset( $_POST['_ovc_a_icono'] ) ? sanitize_text_field( wp_unslash( $_POST['_ovc_a_icono'] ) ) : '';
	if ( '' === $valor ) {
		delete_post_meta( $post_id, '_ovc_a_icono' );
	} else {
		update_post_meta( $post_id, '_ovc_a_icono', $valor );
	}
}
add_action( 'save_post', 'ovc_guardar_metabox_aficion', 10, 2 );

/**
 * Pinta la sección de aficiones, o nada si está vacía.
 *
 * @param array $atts Atributos.
 * @return string
 */
function ovc_shortcode_aficiones( $atts ) {
	$atts = shortcode_atts(
		array(
			'titulo'   => __( 'AFICIONES', 'ov-child' ),
			'columnas' => 4,
			'limite'   => -1,
		),
		$atts,
		'cv_aficiones'
	);

	$aficiones = get_posts(
		array(
			'post_type'      => 'cv_aficion',
			'post_status'    => 'publish',
			'posts_per_page' => (int) $atts['limite'],
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
		)
	);

	if ( empty( $aficiones ) ) {
		// Ni encabezado ni separador: la sección desaparece entera.
		return current_user_can( 'edit_posts' )
			? '<p class="ovc-vacio">' . esc_html__( 'Sección «Aficiones» lista y vacía: en cuanto publiques la primera desde el escritorio aparecerá aquí. Los visitantes no ven este aviso.', 'ov-child' ) . '</p>'
			: '';
	}

	$columnas = max( 2, min( 5, (int) $atts['columnas'] ) );

	global $post;
	$post_original = $post;

	ob_start();
	?>
	<div class="ovc-aficiones">
		<?php if ( $atts['titulo'] ) : ?>
			<h2 class="ovc-aficiones__titulo"><?php echo esc_html( $atts['titulo'] ); ?></h2>
			<span class="ovc-aficiones__raya" aria-hidden="true"></span>
		<?php endif; ?>

		<div class="ovc-aficiones__rejilla ovc-aficiones--cols-<?php echo esc_attr( $columnas ); ?>">
			<?php
			foreach ( $aficiones as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				setup_postdata( $post );
				$id    = get_the_ID();
				$icono = get_post_meta( $id, '_ovc_a_icono', true );
				$texto = trim( (string) $post->post_content );
				?>
				<article class="ovc-aficion">
					<div class="ovc-aficion__marca" aria-hidden="true">
						<?php if ( $icono ) : ?>
							<span class="ovc-aficion__emoji"><?php echo esc_html( $icono ); ?></span>
						<?php elseif ( has_post_thumbnail( $id ) ) : ?>
							<?php the_post_thumbnail( 'thumbnail', array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
						<?php else : ?>
							<span class="ovc-aficion__emoji"><?php echo esc_html( mb_substr( get_the_title(), 0, 1 ) ); ?></span>
						<?php endif; ?>
					</div>
					<h3 class="ovc-aficion__titulo"><?php the_title(); ?></h3>
					<?php if ( '' !== $texto ) : ?>
						<div class="ovc-aficion__texto">
							<?php echo wpautop( wp_kses_post( $texto ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
	<?php

	$post = $post_original; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	wp_reset_postdata();

	return ob_get_clean();
}
add_shortcode( 'cv_aficiones', 'ovc_shortcode_aficiones' );
