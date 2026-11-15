<?php
/**
 * Rejilla de proyectos destacados.
 *
 * Uso:
 *   [proyectos_destacados]
 *   [proyectos_destacados limite="6" columnas="3" categoria="Datos"]
 *   [proyectos_destacados filtros="on" limite="-1"]
 *
 * Todo sale de la base de datos: cada tarjeta se arma con la imagen destacada,
 * la categoría y las tecnologías de la ficha del proyecto. Añadir o quitar un
 * proyecto no toca esta plantilla ni el maquetador.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pinta la rejilla.
 *
 * @param array $atts Atributos del shortcode.
 * @return string
 */
function ovc_shortcode_proyectos( $atts ) {
	$atts = shortcode_atts(
		array(
			'limite'     => 9,
			'columnas'   => 3,
			'categoria'  => '',   // Slug o nombre de una categoría de proyecto.
			'destacados' => 'on', // 'off' para traer todos, marcados o no.
			'filtros'    => 'off',
			'orden'      => 'menu_order',
		),
		$atts,
		'proyectos_destacados'
	);

	$consulta = array(
		'post_type'      => 'project',
		'post_status'    => 'publish',
		'posts_per_page' => (int) $atts['limite'],
		'orderby'        => ( 'azar' === $atts['orden'] ) ? 'rand' : array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'no_found_rows'  => true,
	);

	if ( 'on' === $atts['destacados'] ) {
		$consulta['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			array(
				'key'     => '_ovc_p_destacado',
				'value'   => '1',
				'compare' => '=',
			),
		);
	}

	if ( $atts['categoria'] ) {
		$consulta['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			array(
				'taxonomy' => 'project_category',
				'field'    => is_numeric( $atts['categoria'] ) ? 'term_id' : 'slug',
				'terms'    => sanitize_title( $atts['categoria'] ),
			),
		);
	}

	$proyectos = get_posts( $consulta );
	ovc_precargar_miniaturas( $proyectos );

	if ( empty( $proyectos ) ) {
		return current_user_can( 'edit_posts' )
			? '<p class="ovc-vacio">' . esc_html__( 'No hay proyectos que mostrar. Márcalos como destacados en la ficha de cada proyecto.', 'ov-child' ) . '</p>'
			: '';
	}

	$columnas = max( 1, min( 4, (int) $atts['columnas'] ) );
	$con_filtros = ( 'on' === $atts['filtros'] );

	// Categorías presentes entre los proyectos que se van a pintar, para que el
	// filtro no ofrezca una categoría que deje la rejilla vacía.
	$categorias = array();
	if ( $con_filtros ) {
		foreach ( $proyectos as $p ) {
			foreach ( (array) get_the_terms( $p->ID, 'project_category' ) as $t ) {
				if ( $t instanceof WP_Term ) {
					$categorias[ $t->slug ] = $t->name;
				}
			}
		}
		asort( $categorias );
	}

	$id_bloque = 'ovc-proyectos-' . wp_rand( 1000, 9999 );

	global $post;
	$post_original = $post;

	ob_start();
	?>
	<div class="ovc-proyectos" id="<?php echo esc_attr( $id_bloque ); ?>">

		<?php if ( $con_filtros && count( $categorias ) > 1 ) : ?>
			<div class="ovc-proyectos__filtros" role="group" aria-label="<?php esc_attr_e( 'Filtrar proyectos por categoría', 'ov-child' ); ?>">
				<button type="button" class="ovc-filtro is-activo" data-filtro="*" aria-pressed="true">
					<?php esc_html_e( 'Todos', 'ov-child' ); ?>
				</button>
				<?php foreach ( $categorias as $slug => $nombre ) : ?>
					<button type="button" class="ovc-filtro" data-filtro="<?php echo esc_attr( $slug ); ?>" aria-pressed="false">
						<?php echo esc_html( $nombre ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="ovc-proyectos__rejilla ovc-proyectos--cols-<?php echo esc_attr( $columnas ); ?>">
			<?php
			foreach ( $proyectos as $post ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				setup_postdata( $post );
				$id      = get_the_ID();
				$terminos = get_the_terms( $id, 'project_category' );
				$slugs   = array();
				$nombre_cat = '';
				if ( is_array( $terminos ) ) {
					foreach ( $terminos as $t ) {
						$slugs[] = $t->slug;
						if ( '' === $nombre_cat ) {
							$nombre_cat = $t->name;
						}
					}
				}
				$stack = get_post_meta( $id, '_ovc_p_stack', true );
				$anio  = get_post_meta( $id, '_ovc_p_anio', true );
				$tecs  = $stack ? array_filter( array_map( 'trim', explode( ',', $stack ) ) ) : array();
				?>
				<article class="ovc-proyecto" data-categorias="<?php echo esc_attr( implode( ' ', $slugs ) ); ?>">
					<a class="ovc-proyecto__enlace" href="<?php the_permalink(); ?>">
						<div class="ovc-proyecto__imagen">
							<?php if ( has_post_thumbnail( $id ) ) : ?>
								<?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'alt' => get_the_title() ) ); ?>
							<?php else : ?>
								<span class="ovc-proyecto__sinimagen" aria-hidden="true"><?php echo esc_html( mb_substr( get_the_title(), 0, 2 ) ); ?></span>
							<?php endif; ?>
							<?php if ( $nombre_cat ) : ?>
								<span class="ovc-proyecto__categoria"><?php echo esc_html( $nombre_cat ); ?></span>
							<?php endif; ?>
						</div>

						<div class="ovc-proyecto__cuerpo">
							<h3 class="ovc-proyecto__titulo"><?php the_title(); ?></h3>
							<?php if ( $anio ) : ?>
								<p class="ovc-proyecto__anio"><?php echo esc_html( $anio ); ?></p>
							<?php endif; ?>
							<?php if ( $tecs ) : ?>
								<ul class="ovc-proyecto__tecs">
									<?php foreach ( array_slice( $tecs, 0, 3 ) as $tec ) : ?>
										<li><?php echo esc_html( $tec ); ?></li>
									<?php endforeach; ?>
									<?php if ( count( $tecs ) > 3 ) : ?>
										<li class="ovc-proyecto__mas">+<?php echo (int) ( count( $tecs ) - 3 ); ?></li>
									<?php endif; ?>
								</ul>
							<?php endif; ?>
						</div>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>

	<?php if ( $con_filtros && count( $categorias ) > 1 ) : ?>
		<script>
		( function () {
			var bloque = document.getElementById( <?php echo wp_json_encode( $id_bloque ); ?> );
			if ( ! bloque ) { return; }
			bloque.addEventListener( 'click', function ( e ) {
				var boton = e.target.closest( '.ovc-filtro' );
				if ( ! boton ) { return; }
				var filtro = boton.getAttribute( 'data-filtro' );
				bloque.querySelectorAll( '.ovc-filtro' ).forEach( function ( b ) {
					var activo = b === boton;
					b.classList.toggle( 'is-activo', activo );
					b.setAttribute( 'aria-pressed', activo ? 'true' : 'false' );
				} );
				bloque.querySelectorAll( '.ovc-proyecto' ).forEach( function ( card ) {
					var cats = ( card.getAttribute( 'data-categorias' ) || '' ).split( ' ' );
					card.hidden = ( '*' !== filtro && cats.indexOf( filtro ) === -1 );
				} );
			} );
		} )();
		</script>
	<?php endif; ?>
	<?php

	$post = $post_original; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	wp_reset_postdata();

	return ob_get_clean();
}
add_shortcode( 'proyectos_destacados', 'ovc_shortcode_proyectos' );
