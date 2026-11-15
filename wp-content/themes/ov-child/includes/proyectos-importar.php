<?php
/**
 * Carga los proyectos destacados del perfil de Workana como fichas de Divi.
 *
 * Se ejecuta desde Herramientas > Importar proyectos. Comprueba por titulo, asi
 * que se puede pulsar las veces que haga falta sin duplicar.
 *
 * Los datos salen de las capturas del perfil aportadas en septiembre de 2026.
 * Los titulos que en la captura aparecian cortados con puntos suspensivos se
 * guardan solo con la parte legible: es preferible un titulo corto a uno
 * inventado. Estan marcados con 'incompleto' => true para poder listarlos.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Los 25 proyectos del perfil.
 *
 * @return array
 */
function ovc_datos_proyectos() {
	return array(
		array(
			'titulo'    => 'Arquitectura de Inteligencia de Datos',
			'categoria' => 'Datos',
			'stack'     => 'Data Science, Big Data',
		),
		array(
			'titulo'    => 'Marketing Analytics: Inteligencia de Datos',
			'categoria' => 'Datos',
			'stack'     => 'Marketing Online, Analytics',
		),
		array(
			'titulo'    => 'Centro de Operaciones y Automatización Administrativa',
			'categoria' => 'Datos',
			'stack'     => 'Microsoft Excel, Asistente de administración',
		),
		array(
			'titulo'    => 'AYUDA (PEV): Plataforma Integral para la Prevención y Erradicación de la Violencia',
			'categoria' => 'Aplicaciones',
			'stack'     => 'Python, Flutter',
		),
		array(
			'titulo'      => 'AgroSmartic: Plataforma Inteligente para Gestión Agrícola',
			'categoria'   => 'Aplicaciones',
			'stack'       => 'Python, Flutter',
			'incompleto'  => true,
		),
		array(
			'titulo'     => 'DrogueriasApp: Sistema de Gestión Integral para Droguerías',
			'categoria'  => 'Aplicaciones',
			'stack'      => 'HTML, Responsive Web Design',
			'incompleto' => true,
		),
		array(
			'titulo'     => 'BandMusic: Aplicación de Gestión de Bandas y Eventos',
			'categoria'  => 'Desarrollo',
			'stack'      => 'Diseño Gráfico, SQL Server',
			'incompleto' => true,
		),
		array(
			'titulo'    => 'Salud+ EPS: Plataforma de Gestión de Servicios de Salud',
			'categoria' => 'Desarrollo',
			'stack'     => 'PHP, WordPress',
		),
		array(
			'titulo'    => 'IMC Salud: Calculadora de Índice de Masa Corporal',
			'categoria' => 'Desarrollo',
			'stack'     => 'PHP, WordPress',
		),
		array(
			'titulo'     => 'Conversor de Moneda: Seguimiento y Análisis',
			'categoria'  => 'Desarrollo',
			'stack'      => 'JavaScript, HTML',
			'incompleto' => true,
		),
		array(
			'titulo'    => 'Pokédex: Enciclopedia Interactiva de Pokémon',
			'categoria' => 'Desarrollo',
			'stack'     => 'HTML, JavaScript',
		),
		array(
			'titulo'    => 'M Movies: Plataforma de Streaming de Entretenimiento',
			'categoria' => 'Desarrollo',
			'stack'     => 'JavaScript, Diseño Gráfico',
		),
		array(
			'titulo'    => 'Ecosistema Digital Multicanal',
			'categoria' => 'Desarrollo',
			'stack'     => 'PHP, WordPress',
		),
		array(
			'titulo'    => 'Tiendita: E-commerce de productos frescos',
			'categoria' => 'E-commerce',
			'stack'     => 'Diseño Gráfico, MySQL',
		),
		array(
			'titulo'     => 'Urban Style: Plataforma E-commerce y Gestión',
			'categoria'  => 'E-commerce',
			'stack'      => 'PHP, Tiendas Online (e-commerce)',
			'incompleto' => true,
		),
		array(
			'titulo'     => 'Store Fashion Colombia: E-commerce Infantil y Gestión',
			'categoria'  => 'E-commerce',
			'stack'      => 'WordPress, PHP',
			'incompleto' => true,
		),
		array(
			'titulo'     => 'LiveStream Pro: Gestión y Producción de Contenido',
			'categoria'  => 'Marketing',
			'stack'      => 'Marketing Online, Google Analytics',
			'incompleto' => true,
		),
		array(
			'titulo'    => 'Estrategia de Marketing Omnicanal',
			'categoria' => 'Marketing',
			'stack'     => 'Marketing, Marketing Online',
		),
		array(
			'titulo'    => 'Sistema de Gestión Comercial y Almacén (Rediseño UI/UX)',
			'categoria' => 'Diseño',
			'stack'     => 'WordPress, Diseño Gráfico',
		),
		array(
			'titulo'    => 'Innovate Tech: Identidad y Branding Visual',
			'categoria' => 'Diseño',
			'stack'     => 'Identidad de marca corporativa, Branding',
		),
		array(
			'titulo'    => 'Diseño de Identidad Visual – Tecnológicas',
			'categoria' => 'Diseño',
			'stack'     => 'Diseño Gráfico, Adobe Photoshop',
		),
		array(
			'titulo'    => 'Diseño de Identidad Visual – Band Music',
			'categoria' => 'Diseño',
			'stack'     => 'Diseño Gráfico, Adobe Photoshop',
		),
		array(
			'titulo'    => 'Diseño de Identidad Visual – Ice Cream\'s House',
			'categoria' => 'Diseño',
			'stack'     => 'Adobe Photoshop, Branding',
		),
		array(
			'titulo'    => 'Zootecnia: Bienestar en la Producción',
			'categoria' => 'Diseño',
			'stack'     => 'Diseño Gráfico, Adobe Photoshop',
		),
		array(
			'titulo'    => 'Tarjeta de Presentación Tecnológica',
			'categoria' => 'Diseño',
			'stack'     => 'Adobe Photoshop, Branding',
		),
	);
}

/**
 * Añade la pantalla a Herramientas.
 */
add_action(
	'admin_menu',
	function () {
		add_management_page(
			__( 'Importar proyectos', 'ov-child' ),
			__( 'Importar proyectos', 'ov-child' ),
			'manage_options',
			'ovc-importar-proyectos',
			'ovc_pantalla_importar_proyectos'
		);
	}
);

/**
 * Pantalla de importación de proyectos.
 */
function ovc_pantalla_importar_proyectos() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos para esta página.', 'ov-child' ) );
	}

	$resultado = null;
	if ( isset( $_POST['ovc_importar_proyectos'] ) && check_admin_referer( 'ovc_importar_proyectos' ) ) {
		$resultado = ovc_importar_proyectos();
	}

	$datos = ovc_datos_proyectos();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Importar proyectos destacados', 'ov-child' ); ?></h1>

		<?php if ( is_array( $resultado ) ) : ?>
			<div class="notice notice-success">
				<p>
					<?php
					printf(
						/* translators: 1: creados, 2: omitidos, 3: portadas */
						esc_html__( 'Listo: %1$d proyectos creados, %2$d ya existían, %3$d portadas generadas.', 'ov-child' ),
						(int) $resultado['creados'],
						(int) $resultado['omitidos'],
						(int) $resultado['portadas']
					);
					?>
				</p>
			</div>
			<?php if ( ! empty( $resultado['errores'] ) ) : ?>
				<div class="notice notice-warning">
					<p><strong><?php esc_html_e( 'Avisos:', 'ov-child' ); ?></strong></p>
					<ul style="list-style:disc;margin-left:20px">
						<?php foreach ( $resultado['errores'] as $e ) : ?>
							<li><?php echo esc_html( $e ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<p>
			<?php
			printf(
				/* translators: %d: número de proyectos */
				esc_html__( 'Crea %d proyectos con su categoría y sus tecnologías. A los que no tengan imagen se les dibuja una portada con el nombre del proyecto sobre la paleta del sitio, para que la rejilla del portafolio no quede con huecos grises.', 'ov-child' ),
				count( $datos )
			);
			?>
		</p>
		<p>
			<strong><?php esc_html_e( 'Sustituye esas portadas por capturas reales en cuanto las tengas:', 'ov-child' ); ?></strong>
			<?php esc_html_e( 'basta con cambiar la imagen destacada del proyecto. La portada generada solo se usa si no hay ninguna.', 'ov-child' ); ?>
		</p>

		<form method="post">
			<?php wp_nonce_field( 'ovc_importar_proyectos' ); ?>
			<p>
				<button type="submit" name="ovc_importar_proyectos" value="1" class="button button-primary">
					<?php esc_html_e( 'Importar ahora', 'ov-child' ); ?>
				</button>
			</p>
		</form>

		<h2><?php esc_html_e( 'Títulos que quedaron cortados en la captura', 'ov-child' ); ?></h2>
		<p><?php esc_html_e( 'Estos aparecían con puntos suspensivos. Se guardaron con la parte legible; complétalos desde Proyectos.', 'ov-child' ); ?></p>
		<ul style="list-style:disc;margin-left:20px">
			<?php foreach ( $datos as $p ) : ?>
				<?php if ( ! empty( $p['incompleto'] ) ) : ?>
					<li><?php echo esc_html( $p['titulo'] ); ?></li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}

/**
 * Crea los proyectos.
 *
 * @return array Contadores.
 */
function ovc_importar_proyectos() {
	$creados  = 0;
	$omitidos = 0;
	$portadas = 0;
	$errores  = array();
	$orden    = 0;

	foreach ( ovc_datos_proyectos() as $p ) {
		++$orden;

		$existente = get_posts(
			array(
				'post_type'        => 'project',
				'title'            => $p['titulo'],
				'post_status'      => 'any',
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		if ( ! empty( $existente ) ) {
			++$omitidos;
			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'project',
				'post_status' => 'publish',
				'post_title'  => $p['titulo'],
				'menu_order'  => $orden,
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			$errores[] = $p['titulo'] . ': ' . $post_id->get_error_message();
			continue;
		}

		wp_set_object_terms( $post_id, $p['categoria'], 'project_category' );
		update_post_meta( $post_id, '_ovc_p_stack', $p['stack'] );
		update_post_meta( $post_id, '_ovc_p_destacado', '1' );
		if ( ! empty( $p['incompleto'] ) ) {
			update_post_meta( $post_id, '_ovc_p_revisar', '1' );
		}

		$portada = ovc_asignar_portada( $post_id, $p['categoria'] );
		if ( is_wp_error( $portada ) ) {
			$errores[] = $p['titulo'] . ': ' . $portada->get_error_message();
		} else {
			++$portadas;
		}

		++$creados;
	}

	return array(
		'creados'  => $creados,
		'omitidos' => $omitidos,
		'portadas' => $portadas,
		'errores'  => $errores,
	);
}
