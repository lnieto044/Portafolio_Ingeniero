<?php
/**
 * Tipos de contenido del curriculum: Experiencia y Estudios.
 *
 * Antes, la experiencia y la educacion estaban escritas a mano dentro del
 * maquetador de Divi en la pagina "Acerca". Anadir un puesto nuevo obligaba a
 * duplicar modulos y a repetir estilos, y el orden dependia de como quedaran
 * las filas. Con estos dos tipos de contenido cada entrada se edita como una
 * ficha normal del escritorio y se ordena por fecha.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra los dos tipos de contenido.
 *
 * No son publicos: no tienen URL propia ni salen en el buscador. Solo existen
 * para alimentar los shortcodes de la pagina "Acerca".
 */
function ovc_registrar_cpt_cv() {

	$comun = array(
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
		'menu_position'       => 21,
		'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
	);

	register_post_type(
		'cv_experiencia',
		array_merge(
			$comun,
			array(
				'labels'        => array(
					'name'               => __( 'Experiencia', 'ov-child' ),
					'singular_name'      => __( 'Experiencia', 'ov-child' ),
					'menu_name'          => __( 'Experiencia', 'ov-child' ),
					'add_new'            => __( 'Añadir puesto', 'ov-child' ),
					'add_new_item'       => __( 'Añadir puesto', 'ov-child' ),
					'edit_item'          => __( 'Editar puesto', 'ov-child' ),
					'new_item'           => __( 'Nuevo puesto', 'ov-child' ),
					'view_item'          => __( 'Ver puesto', 'ov-child' ),
					'search_items'       => __( 'Buscar puestos', 'ov-child' ),
					'not_found'          => __( 'Todavía no hay puestos. Añade el primero.', 'ov-child' ),
					'not_found_in_trash' => __( 'No hay puestos en la papelera.', 'ov-child' ),
				),
				'menu_icon'     => 'dashicons-portfolio',
				'description'   => __( 'Puestos de trabajo y prácticas para la página Acerca.', 'ov-child' ),
			)
		)
	);

	register_post_type(
		'cv_estudio',
		array_merge(
			$comun,
			array(
				'labels'      => array(
					'name'               => __( 'Estudios', 'ov-child' ),
					'singular_name'      => __( 'Estudio', 'ov-child' ),
					'menu_name'          => __( 'Estudios', 'ov-child' ),
					'add_new'            => __( 'Añadir estudio', 'ov-child' ),
					'add_new_item'       => __( 'Añadir estudio', 'ov-child' ),
					'edit_item'          => __( 'Editar estudio', 'ov-child' ),
					'new_item'           => __( 'Nuevo estudio', 'ov-child' ),
					'view_item'          => __( 'Ver estudio', 'ov-child' ),
					'search_items'       => __( 'Buscar estudios', 'ov-child' ),
					'not_found'          => __( 'Todavía no hay estudios. Añade el primero.', 'ov-child' ),
					'not_found_in_trash' => __( 'No hay estudios en la papelera.', 'ov-child' ),
				),
				'menu_icon'   => 'dashicons-welcome-learn-more',
				'description' => __( 'Títulos, cursos y diplomados para la página Acerca.', 'ov-child' ),
			)
		)
	);

	register_post_type(
		'cv_reconocimiento',
		array_merge(
			$comun,
			array(
				'labels'      => array(
					'name'               => __( 'Reconocimientos', 'ov-child' ),
					'singular_name'      => __( 'Reconocimiento', 'ov-child' ),
					'menu_name'          => __( 'Reconocimientos', 'ov-child' ),
					'add_new'            => __( 'Añadir reconocimiento', 'ov-child' ),
					'add_new_item'       => __( 'Añadir reconocimiento', 'ov-child' ),
					'edit_item'          => __( 'Editar reconocimiento', 'ov-child' ),
					'new_item'           => __( 'Nuevo reconocimiento', 'ov-child' ),
					'view_item'          => __( 'Ver reconocimiento', 'ov-child' ),
					'search_items'       => __( 'Buscar reconocimientos', 'ov-child' ),
					'not_found'          => __( 'Todavía no hay reconocimientos. Añade el primero.', 'ov-child' ),
					'not_found_in_trash' => __( 'No hay reconocimientos en la papelera.', 'ov-child' ),
				),
				'menu_icon'   => 'dashicons-awards',
				'description' => __( 'Premios y distinciones para la página Acerca.', 'ov-child' ),
			)
		)
	);
}
add_action( 'init', 'ovc_registrar_cpt_cv' );

/**
 * Columnas de la lista del escritorio.
 *
 * Sin esto la tabla solo muestra el titulo, que no dice ni la entidad ni las
 * fechas: hay que abrir cada ficha para saber cual es cual.
 *
 * @param array $columnas Columnas por defecto.
 * @return array
 */
function ovc_columnas_cv( $columnas ) {
	$nuevas = array();
	foreach ( $columnas as $clave => $etiqueta ) {
		$nuevas[ $clave ] = $etiqueta;
		if ( 'title' === $clave ) {
			$nuevas['ovc_entidad'] = __( 'Entidad', 'ov-child' );
			$nuevas['ovc_periodo'] = __( 'Periodo', 'ov-child' );
		}
	}
	return $nuevas;
}
add_filter( 'manage_cv_experiencia_posts_columns', 'ovc_columnas_cv' );
add_filter( 'manage_cv_estudio_posts_columns', 'ovc_columnas_cv' );
add_filter( 'manage_cv_reconocimiento_posts_columns', 'ovc_columnas_cv' );

/**
 * Contenido de las columnas anadidas.
 *
 * @param string $columna Columna que se esta pintando.
 * @param int    $post_id Ficha actual.
 */
function ovc_contenido_columnas_cv( $columna, $post_id ) {
	if ( 'ovc_entidad' === $columna ) {
		echo esc_html( get_post_meta( $post_id, '_ovc_entidad', true ) );
	} elseif ( 'ovc_periodo' === $columna ) {
		echo esc_html( ovc_formatear_periodo( $post_id ) );
	}
}
add_action( 'manage_cv_experiencia_posts_custom_column', 'ovc_contenido_columnas_cv', 10, 2 );
add_action( 'manage_cv_estudio_posts_custom_column', 'ovc_contenido_columnas_cv', 10, 2 );
add_action( 'manage_cv_reconocimiento_posts_custom_column', 'ovc_contenido_columnas_cv', 10, 2 );

/**
 * Ordena las listas del escritorio de la mas reciente a la mas antigua,
 * igual que se ven en la web.
 *
 * @param WP_Query $query Consulta actual.
 */
function ovc_orden_admin_cv( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( ! in_array( $query->get( 'post_type' ), array( 'cv_experiencia', 'cv_estudio', 'cv_reconocimiento' ), true ) ) {
		return;
	}
	if ( $query->get( 'orderby' ) ) {
		return; // El usuario ha pulsado una cabecera para ordenar.
	}
	$query->set( 'meta_key', '_ovc_desde' );
	$query->set( 'orderby', array( 'meta_value' => 'DESC', 'menu_order' => 'ASC' ) );
}
add_action( 'pre_get_posts', 'ovc_orden_admin_cv' );
