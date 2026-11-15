<?php
/**
 * Asigna imágenes reales a los proyectos desde una carpeta.
 *
 * Las capturas de los proyectos están en el perfil de Workana, que sirve tras
 * Cloudflare y no se puede descargar desde aquí. Y las capturas de pantalla
 * enviadas por chat no son archivos: son recortes dentro de una imagen mayor y,
 * aunque se pudieran extraer, saldrían a muy baja resolución.
 *
 * Así que el camino corto es este: se dejan los archivos en una carpeta y esta
 * pantalla los empareja con el proyecto que les toca por el nombre del archivo.
 *
 * Carpeta:  wp-content/uploads/proyectos-originales/
 * Nombre:   el título del proyecto, o parte de él. Da igual mayúsculas,
 *           tildes, espacios o guiones.
 *
 *   agrosmartic.jpg                      -> AgroSmartic: Plataforma Inteligente...
 *   pokedex-enciclopedia.png             -> Pokédex: Enciclopedia Interactiva...
 *   Urban Style.webp                     -> Urban Style: Plataforma E-commerce...
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ruta de la carpeta donde se dejan las imágenes.
 *
 * @return string
 */
function ovc_carpeta_originales() {
	$subidas = wp_upload_dir();
	return trailingslashit( $subidas['basedir'] ) . 'proyectos-originales';
}

/**
 * Busca el proyecto que mejor encaja con un nombre de archivo.
 *
 * @param string $nombre    Nombre del archivo sin extensión.
 * @param array  $proyectos Proyectos indexados por ID => título.
 * @return int|false ID del proyecto.
 */
function ovc_emparejar_proyecto( $nombre, $proyectos ) {
	// Se quita el sufijo numérico: "centro_operaciones_1" y "..._2" son dos
	// capturas del mismo proyecto.
	$clave = preg_replace( '/[\s._-]*\d+$/', '', (string) $nombre );

	$mejor  = false;
	$puntos = 0;

	foreach ( $proyectos as $id => $titulo ) {
		$p = ovc_puntuar_coincidencia( $clave, $titulo );
		if ( $p > $puntos ) {
			$mejor  = $id;
			$puntos = $p;
		}
	}

	return $mejor;
}

/**
 * ¿El proyecto ya tiene puesta esa misma imagen?
 *
 * @param int    $post_id Proyecto.
 * @param string $archivo Nombre del archivo en la carpeta.
 * @return bool
 */
function ovc_imagen_ya_puesta( $post_id, $archivo ) {
	$adjunto = (int) get_post_thumbnail_id( $post_id );
	if ( ! $adjunto ) {
		return false;
	}
	return get_post_meta( $adjunto, '_ovc_origen', true ) === $archivo;
}

/**
 * Añade la pantalla a Herramientas.
 */
add_action(
	'admin_menu',
	function () {
		add_management_page(
			__( 'Imágenes de proyectos', 'ov-child' ),
			__( 'Imágenes de proyectos', 'ov-child' ),
			'manage_options',
			'ovc-imagenes-proyectos',
			'ovc_pantalla_imagenes_proyectos'
		);
	}
);

/**
 * Pantalla de asignación de imágenes.
 */
function ovc_pantalla_imagenes_proyectos() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos para esta página.', 'ov-child' ) );
	}

	$carpeta   = ovc_carpeta_originales();
	$resultado = null;

	if ( isset( $_POST['ovc_asignar_imagenes'] ) && check_admin_referer( 'ovc_asignar_imagenes' ) ) {
		$resultado = ovc_asignar_imagenes_proyectos();
	}

	$archivos = is_dir( $carpeta ) ? glob( $carpeta . '/*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP}', GLOB_BRACE ) : array();
	$archivos = $archivos ? $archivos : array();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Imágenes reales de los proyectos', 'ov-child' ); ?></h1>

		<?php if ( is_array( $resultado ) ) : ?>
			<div class="notice notice-<?php echo empty( $resultado['sin_pareja'] ) ? 'success' : 'warning'; ?>">
				<p>
					<?php
					printf(
						/* translators: 1: asignadas, 2: sin pareja */
						esc_html__( '%1$d imágenes asignadas. %2$d sin proyecto que encaje.', 'ov-child' ),
						(int) $resultado['asignadas'],
						count( $resultado['sin_pareja'] )
					);
					?>
				</p>
				<?php if ( ! empty( $resultado['sin_pareja'] ) ) : ?>
					<ul style="list-style:disc;margin-left:20px">
						<?php foreach ( $resultado['sin_pareja'] as $f ) : ?>
							<li><code><?php echo esc_html( $f ); ?></code></li>
						<?php endforeach; ?>
					</ul>
					<p><?php esc_html_e( 'Renombra esos archivos con el título del proyecto (o parte) y vuelve a pulsar.', 'ov-child' ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Cómo se usa', 'ov-child' ); ?></h2>
		<ol>
			<li>
				<?php esc_html_e( 'Sube las capturas por FTP o por el gestor de archivos del hosting a esta carpeta:', 'ov-child' ); ?><br />
				<code><?php echo esc_html( $carpeta ); ?></code>
			</li>
			<li><?php esc_html_e( 'Nombra cada archivo con el título del proyecto o una parte suya. No importan tildes, mayúsculas, espacios ni guiones.', 'ov-child' ); ?></li>
			<li><?php esc_html_e( 'Pulsa el botón. Cada imagen se convierte en la imagen destacada de su proyecto y sustituye a la portada generada.', 'ov-child' ); ?></li>
		</ol>
		<p class="description">
			<?php esc_html_e( 'También puedes hacerlo uno a uno desde Proyectos → editar → Imagen destacada. Esta pantalla solo ahorra tiempo cuando son muchas.', 'ov-child' ); ?>
		</p>

		<h2>
			<?php
			printf(
				/* translators: %d: archivos encontrados */
				esc_html__( 'Archivos en la carpeta ahora mismo: %d', 'ov-child' ),
				count( $archivos )
			);
			?>
		</h2>

		<?php if ( $archivos ) : ?>
			<?php
			$proyectos = array();
			foreach ( get_posts( array( 'post_type' => 'project', 'posts_per_page' => -1 ) ) as $p ) {
				$proyectos[ $p->ID ] = $p->post_title;
			}
			?>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Archivo', 'ov-child' ); ?></th><th><?php esc_html_e( 'Se asignaría a', 'ov-child' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $archivos as $ruta ) : ?>
					<?php
					$base  = pathinfo( $ruta, PATHINFO_FILENAME );
					$id    = ovc_emparejar_proyecto( $base, $proyectos );
					?>
					<tr>
						<td><code><?php echo esc_html( basename( $ruta ) ); ?></code></td>
						<td>
							<?php if ( $id ) : ?>
								<strong><?php echo esc_html( $proyectos[ $id ] ); ?></strong>
							<?php else : ?>
								<em style="color:#b32d2e"><?php esc_html_e( 'sin pareja — renómbralo', 'ov-child' ); ?></em>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<form method="post" style="margin-top:16px">
				<?php wp_nonce_field( 'ovc_asignar_imagenes' ); ?>
				<button type="submit" name="ovc_asignar_imagenes" value="1" class="button button-primary">
					<?php esc_html_e( 'Asignar estas imágenes', 'ov-child' ); ?>
				</button>
			</form>
		<?php else : ?>
			<p><?php esc_html_e( 'La carpeta está vacía o todavía no existe. Créala y deja ahí las capturas.', 'ov-child' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Recorre la carpeta y asigna cada imagen a su proyecto.
 *
 * @return array Contadores.
 */
function ovc_asignar_imagenes_proyectos() {
	$carpeta = ovc_carpeta_originales();
	if ( ! is_dir( $carpeta ) ) {
		return array( 'asignadas' => 0, 'sin_pareja' => array() );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$proyectos = array();
	foreach ( get_posts( array( 'post_type' => 'project', 'posts_per_page' => -1 ) ) as $p ) {
		$proyectos[ $p->ID ] = $p->post_title;
	}

	$archivos   = glob( $carpeta . '/*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP}', GLOB_BRACE );
	$archivos   = $archivos ? $archivos : array();
	sort( $archivos );

	$asignadas  = 0;
	$sin_pareja = array();
	$ya_puestos = array(); // Un proyecto solo tiene una imagen destacada.

	foreach ( $archivos as $ruta ) {
		$base = pathinfo( $ruta, PATHINFO_FILENAME );
		$id   = ovc_emparejar_proyecto( $base, $proyectos );

		/*
		 * Si ya se le asignó una imagen en esta misma pasada, se deja la
		 * primera. Antes la última ganaba en silencio, así que con dos
		 * capturas del mismo proyecto no había forma de saber cuál quedaba.
		 */
		if ( $id && isset( $ya_puestos[ $id ] ) ) {
			$sin_pareja[] = basename( $ruta ) . ' (sobra: ese proyecto ya tiene imagen)';
			continue;
		}

		/*
		 * Y si el proyecto ya tiene puesta exactamente esta imagen, no se
		 * vuelve a subir: sin esto, cada pulsación duplicaba la biblioteca.
		 */
		if ( $id && ovc_imagen_ya_puesta( $id, basename( $ruta ) ) ) {
			$ya_puestos[ $id ] = true;
			continue;
		}

		if ( ! $id ) {
			$sin_pareja[] = basename( $ruta );
			continue;
		}

		// Se copia, no se mueve: el original se queda en la carpeta por si hay
		// que repetir la operación.
		$copia = wp_tempnam( basename( $ruta ) );
		if ( ! copy( $ruta, $copia ) ) {
			$sin_pareja[] = basename( $ruta ) . ' (no se pudo leer)';
			continue;
		}

		$adjunto = media_handle_sideload(
			array(
				'name'     => basename( $ruta ),
				'tmp_name' => $copia,
				'error'    => 0,
				'size'     => filesize( $copia ),
			),
			$id,
			$proyectos[ $id ]
		);

		if ( is_wp_error( $adjunto ) ) {
			@unlink( $copia ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$sin_pareja[] = basename( $ruta ) . ': ' . $adjunto->get_error_message();
			continue;
		}

		update_post_meta( $adjunto, '_wp_attachment_image_alt', $proyectos[ $id ] );
		update_post_meta( $adjunto, '_ovc_origen', basename( $ruta ) );
		set_post_thumbnail( $id, $adjunto );
		$ya_puestos[ $id ] = true;
		++$asignadas;
	}

	return array( 'asignadas' => $asignadas, 'sin_pareja' => $sin_pareja );
}
