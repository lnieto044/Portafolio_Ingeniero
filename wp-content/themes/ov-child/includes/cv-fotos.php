<?php
/**
 * Carga por lotes las fotos de experiencia, educación y reconocimientos.
 *
 * Cada ficha admite dos fotos. En vez de subirlas de una en una desde el
 * editor, se dejan todas en una carpeta y esta pantalla las reparte.
 *
 * Carpeta:  wp-content/uploads/cv-fotos/
 *
 * Nombre:   <parte del nombre de la entidad o del título>-<1 o 2>.<extensión>
 *
 *   sonda-1.jpg           -> SONDA Colombia S.A., foto 1
 *   sonda-2.jpg           -> SONDA Colombia S.A., foto 2
 *   colpensiones-1.png    -> UT Coltemp HQ5 | Colpensiones, foto 1
 *   ministerio-2.webp     -> Ministerio de las Culturas..., foto 2
 *
 * Sin sufijo también vale: los archivos que encajen con la misma ficha se
 * reparten en el orden en que aparecen.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Carpeta donde se dejan las fotos.
 *
 * @return string
 */
function ovc_carpeta_cv_fotos() {
	$subidas = wp_upload_dir();
	return trailingslashit( $subidas['basedir'] ) . 'cv-fotos';
}

/**
 * Todas las fichas del currículum, con los textos por los que se pueden buscar.
 *
 * @return array ID => array( 'titulo' => .., 'claves' => array(..) )
 */
function ovc_fichas_cv() {
	$fichas = array();

	$posts = get_posts(
		array(
			'post_type'      => array( 'cv_experiencia', 'cv_estudio', 'cv_reconocimiento' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
		)
	);

	foreach ( $posts as $p ) {
		$entidad = (string) get_post_meta( $p->ID, '_ovc_entidad', true );
		$rol     = (string) get_post_meta( $p->ID, '_ovc_rol', true );

		$fichas[ $p->ID ] = array(
			'titulo' => $p->post_title,
			'tipo'   => $p->post_type,
			/*
			 * Se compara contra la entidad, el título, el cargo, y las
			 * combinaciones de los tres.
			 *
			 * El cargo importa más de lo que parece: la gente nombra las fotos
			 * por lo que sale en ellas, no por el nombre de la empresa.
			 * "soporte_1.png" es de SONDA pero "soporte" solo aparece en el
			 * cargo ("Técnico de Soporte 1A"); igual "tecnologo.png" y
			 * "ingeniero.png". Sin esta clave se quedaban sin pareja.
			 */
			'claves' => array_filter(
				array(
					$entidad,
					$p->post_title,
					$rol,
					trim( $entidad . ' ' . $p->post_title ),
					trim( $entidad . ' ' . $rol ),
					trim( $p->post_title . ' ' . $rol ),
				)
			),
		);
	}

	return $fichas;
}

/**
 * Separa el nombre del archivo en clave y número de foto.
 *
 * "colpensiones-2" -> array( 'colpensiones', 2 )
 * "sonda"          -> array( 'sonda', 0 )
 *
 * @param string $base Nombre sin extensión.
 * @return array
 */
function ovc_partir_nombre_foto( $base ) {
	// Se aceptan también 3, 4... aunque solo haya dos huecos: así el archivo
	// se empareja igual y la pantalla puede avisar de que sobra, en vez de
	// dejarlo como "sin pareja" y que parezca un fallo de nombre.
	if ( preg_match( '/^(.*?)[\s._-]*([1-9])$/u', $base, $m ) && '' !== trim( $m[1] ) ) {
		return array( $m[1], (int) $m[2] );
	}
	return array( $base, 0 );
}

/**
 * Busca la ficha que mejor encaja con una clave.
 *
 * @param string $clave  Texto del nombre de archivo.
 * @param array  $fichas Fichas de ovc_fichas_cv().
 * @return int|false
 */
function ovc_emparejar_ficha( $clave, $fichas ) {
	/*
	 * Se compara palabra a palabra, no como una sola cadena.
	 *
	 * Hay dos fichas que se llaman "Consultor Independiente", una de 2026 y
	 * otra de 2024. Comparando cadenas enteras, "consultor-2026" se convierte
	 * en "consultor2026", que no aparece dentro de
	 * "consultorindependiente2026actualidad" y no encajaba con ninguna. Por
	 * palabras, ["consultor","2026"] sí distingue una de otra.
	 */
	$tokens = array_values(
		array_filter(
			array_map( 'ovc_normalizar', preg_split( '/[\s._-]+/u', (string) $clave ) )
		)
	);

	if ( empty( $tokens ) || strlen( implode( '', $tokens ) ) < 3 ) {
		return false; // Demasiado corto: emparejaría con cualquier cosa.
	}

	$mejor  = false;
	$puntos = 0;

	foreach ( $fichas as $id => $ficha ) {
		foreach ( $ficha['claves'] as $texto ) {
			$t = ovc_normalizar( $texto );
			if ( '' === $t ) {
				continue;
			}

			$suma = 0;
			foreach ( $tokens as $token ) {
				if ( false === strpos( $t, $token ) ) {
					$suma = 0;
					break; // Todas las palabras tienen que aparecer.
				}
				$suma += strlen( $token );
			}

			if ( $suma > $puntos ) {
				$mejor  = $id;
				$puntos = $suma;
			}
		}
	}

	return $mejor;
}

/**
 * Lista los archivos de la carpeta con la ficha que les tocaría.
 *
 * @return array
 */
function ovc_plan_fotos_cv() {
	$carpeta = ovc_carpeta_cv_fotos();
	if ( ! is_dir( $carpeta ) ) {
		return array();
	}

	$archivos = glob( $carpeta . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE );
	$archivos = $archivos ? $archivos : array();
	sort( $archivos );

	$fichas = ovc_fichas_cv();
	$plan   = array();
	$usados = array(); // Para repartir 1 y 2 cuando no hay sufijo.

	foreach ( $archivos as $ruta ) {
		$base = pathinfo( $ruta, PATHINFO_FILENAME );
		list( $clave, $num ) = ovc_partir_nombre_foto( $base );

		$id = ovc_emparejar_ficha( $clave, $fichas );

		if ( $id && ! $num ) {
			$usados[ $id ] = isset( $usados[ $id ] ) ? $usados[ $id ] + 1 : 1;
			$num           = min( 2, $usados[ $id ] );
		}

		$plan[] = array(
			'ruta'    => $ruta,
			'archivo' => basename( $ruta ),
			'id'      => $id,
			'num'     => $num ? $num : 1,
			'ficha'   => $id ? $fichas[ $id ]['titulo'] : '',
		);
	}

	return $plan;
}

/**
 * ¿Este archivo ya está puesto en ese hueco de esa ficha?
 *
 * La pantalla recorre la carpeta entera cada vez que se pulsa el botón. Sin
 * esta comprobación, volver a pulsarla -por ejemplo para cargar tres fotos
 * nuevas- volvía a subir las veinticuatro anteriores y dejaba la biblioteca
 * llena de duplicados.
 *
 * @param int    $post_id Ficha.
 * @param int    $num     Hueco (1 o 2).
 * @param string $archivo Nombre del archivo en la carpeta.
 * @return bool
 */
function ovc_foto_ya_puesta( $post_id, $num, $archivo ) {
	$adjunto = (int) get_post_meta( $post_id, '_ovc_foto_' . $num, true );
	if ( ! $adjunto ) {
		return false;
	}

	// Al subir se guarda de qué archivo vino: es la comparación exacta.
	$origen_guardado = get_post_meta( $adjunto, '_ovc_origen', true );
	if ( $origen_guardado ) {
		return $origen_guardado === $archivo;
	}

	/*
	 * Respaldo para las fotos que se subieron antes de guardar ese dato.
	 * WordPress añade sufijos (-1, -2) cuando el nombre ya existe, y el propio
	 * nombre puede terminar en número ("sonda-1"). Se recortan los grupos
	 * finales de guion+dígitos EN LOS DOS lados, si no "sonda-1" contra
	 * "sonda-1" acababa comparando "sonda" contra "sonda1" y nunca coincidía.
	 */
	$ruta = get_post_meta( $adjunto, '_wp_attached_file', true );
	if ( ! $ruta ) {
		return false;
	}

	$limpiar = function ( $nombre ) {
		// sanitize_file_name() es lo que aplica WordPress al subir: convierte
		// los espacios en guiones. Sin esto, "consultor 2024-1.png" se guarda
		// como "consultor-2024-1.png" y los dos nombres nunca coincidían.
		$nombre = sanitize_file_name( $nombre );
		return ovc_normalizar( preg_replace( '/(?:-\d+)+$/', '', $nombre ) );
	};

	return $limpiar( pathinfo( $ruta, PATHINFO_FILENAME ) ) === $limpiar( pathinfo( $archivo, PATHINFO_FILENAME ) );
}

/**
 * Añade la pantalla a Herramientas.
 */
add_action(
	'admin_menu',
	function () {
		add_management_page(
			__( 'Fotos del currículum', 'ov-child' ),
			__( 'Fotos del currículum', 'ov-child' ),
			'manage_options',
			'ovc-fotos-cv',
			'ovc_pantalla_fotos_cv'
		);
	}
);

/**
 * Pantalla de carga de fotos.
 */
function ovc_pantalla_fotos_cv() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos para esta página.', 'ov-child' ) );
	}

	$resultado = null;
	if ( isset( $_POST['ovc_cargar_fotos'] ) && check_admin_referer( 'ovc_cargar_fotos' ) ) {
		$resultado = ovc_cargar_fotos_cv();
	}

	$carpeta = ovc_carpeta_cv_fotos();
	$plan    = ovc_plan_fotos_cv();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Fotos de experiencia, educación y reconocimientos', 'ov-child' ); ?></h1>

		<?php if ( is_array( $resultado ) ) : ?>
			<div class="notice notice-<?php echo empty( $resultado['fallos'] ) ? 'success' : 'warning'; ?>">
				<p>
					<?php
					printf(
						/* translators: 1: cargadas, 2: ya estaban, 3: fallos */
						esc_html__( '%1$d fotos nuevas asignadas. %2$d ya estaban puestas. %3$d sin ficha que encaje.', 'ov-child' ),
						(int) $resultado['cargadas'],
						(int) $resultado['saltadas'],
						count( $resultado['fallos'] )
					);
					?>
				</p>
				<?php if ( ! empty( $resultado['fallos'] ) ) : ?>
					<ul style="list-style:disc;margin-left:20px">
						<?php foreach ( $resultado['fallos'] as $f ) : ?>
							<li><code><?php echo esc_html( $f ); ?></code></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Cómo se usa', 'ov-child' ); ?></h2>
		<ol>
			<li>
				<?php esc_html_e( 'Sube las fotos a esta carpeta:', 'ov-child' ); ?><br />
				<code><?php echo esc_html( $carpeta ); ?></code>
			</li>
			<li>
				<?php esc_html_e( 'Nombra cada archivo con la entidad (o parte del título) y el número de foto:', 'ov-child' ); ?><br />
				<code>sonda-1.jpg</code> &nbsp; <code>sonda-2.jpg</code> &nbsp; <code>colpensiones-1.png</code>
			</li>
			<li><?php esc_html_e( 'Comprueba la tabla de abajo y pulsa el botón.', 'ov-child' ); ?></li>
		</ol>
		<p class="description">
			<?php esc_html_e( 'Las fotos no sustituyen a la insignia de la entidad: la insignia sigue siendo el círculo pequeño de la izquierda y las fotos van al lado del texto.', 'ov-child' ); ?>
		</p>

		<?php if ( $plan ) : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Archivo', 'ov-child' ); ?></th>
						<th><?php esc_html_e( 'Ficha', 'ov-child' ); ?></th>
						<th><?php esc_html_e( 'Posición', 'ov-child' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $plan as $f ) : ?>
						<tr>
							<td><code><?php echo esc_html( $f['archivo'] ); ?></code></td>
							<td>
								<?php if ( $f['id'] ) : ?>
									<strong><?php echo esc_html( $f['ficha'] ); ?></strong>
								<?php else : ?>
									<em style="color:#b32d2e"><?php esc_html_e( 'sin pareja — renómbralo', 'ov-child' ); ?></em>
								<?php endif; ?>
							</td>
							<td><?php echo $f['id'] ? esc_html( 'Foto ' . $f['num'] ) : '—'; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<form method="post" style="margin-top:16px">
				<?php wp_nonce_field( 'ovc_cargar_fotos' ); ?>
				<button type="submit" name="ovc_cargar_fotos" value="1" class="button button-primary">
					<?php esc_html_e( 'Cargar estas fotos', 'ov-child' ); ?>
				</button>
			</form>
		<?php else : ?>
			<p><?php esc_html_e( 'La carpeta está vacía o no existe todavía.', 'ov-child' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Sube las fotos y las asigna a su ficha.
 *
 * @return array
 */
function ovc_cargar_fotos_cv() {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$cargadas = 0;
	$saltadas = 0;
	$fallos   = array();

	foreach ( ovc_plan_fotos_cv() as $f ) {
		if ( ! $f['id'] ) {
			$fallos[] = $f['archivo'];
			continue;
		}

		if ( ovc_foto_ya_puesta( $f['id'], $f['num'], $f['archivo'] ) ) {
			++$saltadas;
			continue;
		}

		// Se copia: el original se queda en la carpeta por si hay que repetir.
		$copia = wp_tempnam( $f['archivo'] );
		if ( ! copy( $f['ruta'], $copia ) ) {
			$fallos[] = $f['archivo'] . ' (no se pudo leer)';
			continue;
		}

		$adjunto = media_handle_sideload(
			array(
				'name'     => $f['archivo'],
				'tmp_name' => $copia,
				'error'    => 0,
				'size'     => filesize( $copia ),
			),
			$f['id'],
			$f['ficha']
		);

		if ( is_wp_error( $adjunto ) ) {
			@unlink( $copia ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$fallos[] = $f['archivo'] . ': ' . $adjunto->get_error_message();
			continue;
		}

		update_post_meta( $adjunto, '_wp_attachment_image_alt', $f['ficha'] );
		// De qué archivo de la carpeta salió: permite volver a pulsar el botón
		// sin duplicar nada.
		update_post_meta( $adjunto, '_ovc_origen', $f['archivo'] );
		update_post_meta( $f['id'], '_ovc_foto_' . $f['num'], (string) $adjunto );
		++$cargadas;
	}

	return array(
		'cargadas' => $cargadas,
		'saltadas' => $saltadas,
		'fallos'   => $fallos,
	);
}
