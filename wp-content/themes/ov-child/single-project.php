<?php
/**
 * Página de un proyecto.
 *
 * Sustituye a Divi/single-project.php por dos motivos.
 *
 * El primero: cuando un proyecto no se maquetó con el constructor —los 25
 * importados del perfil de Workana— Divi cae a su plantilla por defecto, que
 * saca la barra lateral de WordPress con el buscador, «Comentarios recientes»,
 * «Feed de entradas» y un enlace a WordPress.org. Eso en una página que se le
 * enseña a un cliente.
 *
 * El segundo: los 11 proyectos antiguos tenían una maqueta mejor —descripción
 * a un lado y los datos del proyecto al otro— hecha a mano con el constructor.
 * Aquí se reproduce esa misma estructura para todos, pero leyendo los datos de
 * la ficha en vez de escribirlos a mano proyecto por proyecto.
 *
 * @package OV_Child
 */

get_header();

$ovc_con_builder = et_pb_is_pagebuilder_used( get_the_ID() );

/**
 * Enlaces al proyecto anterior y al siguiente, dentro de la misma categoría.
 *
 * Los parámetros de WordPress hacen el trabajo: el tercero pide que sea del
 * mismo término y el quinto dice en qué taxonomía mirar. Sin ellos, desde un
 * proyecto de Soporte se saltaba a uno de Desarrollo y de ahí a uno de Datos,
 * que no es una secuencia que le sirva a nadie.
 *
 * Si es el primero o el último de su categoría, ese lado no se pinta.
 */
function ovc_nav_proyecto() {
	$anterior = get_previous_post_link( '%link', '&larr; %title', true, '', 'project_category' );
	$siguiente = get_next_post_link( '%link', '%title &rarr;', true, '', 'project_category' );
	?>
	<nav class="ovc-proyecto-single__nav" aria-label="<?php esc_attr_e( 'Otros proyectos de la misma categoría', 'ov-child' ); ?>">
		<span class="ovc-proyecto-single__anterior">
			<?php echo wp_kses_post( $anterior ); ?>
		</span>

		<span class="ovc-proyecto-single__siguiente">
			<?php echo wp_kses_post( $siguiente ); ?>
		</span>
	</nav>
	<?php
}
?>

<div id="main-content">

	<?php
	while ( have_posts() ) :
		the_post();

		$ovc_id = get_the_ID();
		?>

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'ovc-proyecto-single' ); ?>>

			<?php if ( $ovc_con_builder ) : ?>

				<?php // Los que ya tienen diseño propio se quedan como están. ?>
				<div class="entry-content"><?php the_content(); ?></div>

				<div class="ovc-contenedor"><?php ovc_nav_proyecto(); ?></div>

			<?php else : ?>

				<?php
				$ovc_terms = get_the_terms( $ovc_id, 'project_category' );
				$ovc_cat   = ( is_array( $ovc_terms ) && $ovc_terms ) ? $ovc_terms[0]->name : '';

				$ovc_cliente = get_post_meta( $ovc_id, '_ovc_p_cliente', true );
				$ovc_anio    = get_post_meta( $ovc_id, '_ovc_p_anio', true );
				$ovc_rol     = get_post_meta( $ovc_id, '_ovc_p_rol', true );
				$ovc_stack   = get_post_meta( $ovc_id, '_ovc_p_stack', true );
				$ovc_url     = get_post_meta( $ovc_id, '_ovc_p_url', true );
				$ovc_repo    = get_post_meta( $ovc_id, '_ovc_p_repo', true );

				$ovc_tecs = $ovc_stack
					? array_filter( array_map( 'trim', explode( ',', $ovc_stack ) ) )
					: array();

				// Los datos de la columna derecha, solo los que estén rellenos.
				$ovc_datos = array_filter(
					array(
						__( 'Cliente', 'ov-child' )   => $ovc_cliente,
						__( 'Categoría', 'ov-child' ) => $ovc_cat,
						__( 'Año', 'ov-child' )       => $ovc_anio,
						__( 'Mi rol', 'ov-child' )    => $ovc_rol,
					)
				);

				$ovc_texto = trim( wp_strip_all_tags( get_the_content() ) );
				?>

				<header class="ovc-banda ovc-proyecto-banda">
					<div class="ovc-contenedor">
						<?php if ( $ovc_cat ) : ?>
							<p class="ovc-proyecto-single__cat"><?php echo esc_html( $ovc_cat ); ?></p>
						<?php endif; ?>
						<h1 class="ovc-proyecto-single__titulo"><?php the_title(); ?></h1>
					</div>
				</header>

				<div class="ovc-contenedor ovc-proyecto-single__cuerpo">

					<?php if ( has_post_thumbnail() ) : ?>
						<figure class="ovc-proyecto-single__imagen">
							<?php the_post_thumbnail( 'large', array( 'alt' => get_the_title() ) ); ?>
						</figure>
					<?php endif; ?>

					<div class="ovc-proyecto-single__detalle">

						<div class="ovc-proyecto-single__principal">
							<h2 class="ovc-proyecto-single__sub"><?php the_title(); ?></h2>

							<?php if ( '' !== $ovc_texto ) : ?>
								<div class="ovc-proyecto-single__texto"><?php the_content(); ?></div>
							<?php else : ?>
								<?php if ( current_user_can( 'edit_posts' ) ) : ?>
									<p class="ovc-vacio">
										<?php esc_html_e( 'Este proyecto no tiene descripción. Añádela desde el editor: dos o tres frases sobre qué problema resolvía.', 'ov-child' ); ?>
									</p>
								<?php endif; ?>
							<?php endif; ?>

							<?php if ( $ovc_url || $ovc_repo ) : ?>
								<p class="ovc-proyecto-single__enlaces">
									<?php if ( $ovc_url ) : ?>
										<a class="ovc-boton" href="<?php echo esc_url( $ovc_url ); ?>" target="_blank" rel="noopener noreferrer">
											<?php esc_html_e( 'Ver el proyecto', 'ov-child' ); ?>
										</a>
									<?php endif; ?>
									<?php if ( $ovc_repo ) : ?>
										<a class="ovc-boton ovc-boton--linea" href="<?php echo esc_url( $ovc_repo ); ?>" target="_blank" rel="noopener noreferrer">
											<?php esc_html_e( 'Código fuente', 'ov-child' ); ?>
										</a>
									<?php endif; ?>
								</p>
							<?php endif; ?>
						</div>

						<aside class="ovc-proyecto-single__meta">
							<?php foreach ( $ovc_datos as $ovc_etiqueta => $ovc_valor ) : ?>
								<div class="ovc-proyecto-single__dato">
									<span><?php echo esc_html( $ovc_etiqueta ); ?></span>
									<strong><?php echo esc_html( $ovc_valor ); ?></strong>
								</div>
							<?php endforeach; ?>

							<?php if ( $ovc_tecs ) : ?>
								<div class="ovc-proyecto-single__dato">
									<span><?php esc_html_e( 'Tecnologías', 'ov-child' ); ?></span>
									<ul class="ovc-proyecto-single__tecs">
										<?php foreach ( $ovc_tecs as $ovc_tec ) : ?>
											<li><?php echo esc_html( $ovc_tec ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						</aside>

					</div>

					<?php ovc_nav_proyecto(); ?>

				</div>

			<?php endif; ?>

		</article>

		<?php
	endwhile;
	?>

</div>

<?php
get_footer();
