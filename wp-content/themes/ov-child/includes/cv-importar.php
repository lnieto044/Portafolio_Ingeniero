<?php
/**
 * Carga inicial del curriculum: experiencia, estudios y reconocimientos.
 *
 * Se ejecuta desde Herramientas > Importar CV. Es idempotente: comprueba por
 * titulo y tipo, asi que se puede pulsar las veces que haga falta sin duplicar.
 *
 * Los datos salen del CV aportado en septiembre de 2026. No hay ninguna entrada
 * inventada. Donde el CV no daba un dato -el mes de dos de los reconocimientos-
 * el campo se deja vacio en vez de rellenarlo a ojo.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Envuelve una lista de logros en el HTML de la ficha.
 *
 * @param array $puntos Frases.
 * @return string
 */
function ovc_lista( array $puntos ) {
	return "<ul>\n\t<li>" . implode( "</li>\n\t<li>", $puntos ) . "</li>\n</ul>";
}

/**
 * Datos del curriculum.
 *
 * @return array
 */
function ovc_datos_cv_iniciales() {
	return array(

		/* -----------------------------------------------------------------
		 * EXPERIENCIA
		 * -------------------------------------------------------------- */
		'cv_experiencia'    => array(
			array(
				'titulo'  => 'Registraduría Nacional del Estado Civil',
				'entidad' => 'Registraduría Nacional del Estado Civil',
				'rol'     => 'Delegado Funcional',
				'desde'   => '2026-06',
				'hasta'   => '2026-06',
				'texto'   => ovc_lista(
					array(
						'Verifiqué y validé información institucional del proceso electoral contrastando registros contra fuentes oficiales, bajo protocolos de reserva legal y trazabilidad documental.',
						'Detecté y documenté inconsistencias y novedades, escalando los hallazgos a las instancias de decisión con el soporte probatorio correspondiente.',
						'Consolidé reportes de gestión con criterios de exactitud, oportunidad y cadena de custodia de la información.',
					)
				),
			),
			array(
				'titulo'  => 'Consultor Independiente (2026 – actualidad)',
				'entidad' => 'Ejercicio profesional independiente',
				'rol'     => 'Consultor en Tecnología y Analítica de Datos',
				'desde'   => '2026-02',
				'actual'  => true,
				'texto'   => ovc_lista(
					array(
						'Asesoro a Indico Arquitectura S.A.S. en la estructuración de su plataforma de trabajo sobre Microsoft 365 y SharePoint: organización documental, control de accesos y estandarización de procesos de información.',
						'Desarrollo procesos de extracción, depuración y análisis de datos, y construyo tableros de indicadores para apoyar decisiones operativas.',
						'Formalicé la metodología de triangulación de fuentes públicas que recibió el Premio Alfonso Palacio Rudas de la Contraloría General de la República en agosto de 2026.',
						'Elaboro documentación técnica, manuales de usuario y pruebas de validación de las soluciones implementadas.',
					)
				),
			),
			array(
				'titulo'  => 'SONDA Colombia S.A.',
				'entidad' => 'SONDA Colombia S.A.',
				'rol'     => 'Técnico de Soporte 1A',
				'desde'   => '2025-04',
				'hasta'   => '2026-02',
				'texto'   => ovc_lista(
					array(
						'Registré y documenté casos en herramienta de mesa de ayuda garantizando calidad, consistencia y trazabilidad del dato para su análisis posterior.',
						'Hice seguimiento a indicadores de servicio y niveles de atención, apoyando la elaboración de reportes estadísticos periódicos para el cliente.',
						'Administré información de usuarios y activos tecnológicos conforme a las políticas de seguridad de la información establecidas.',
					)
				),
			),
			array(
				'titulo'  => 'Consultor Independiente (2024 – 2025)',
				'entidad' => 'Ejercicio profesional independiente',
				'rol'     => 'Consultor en Tecnología y Analítica de Datos',
				'desde'   => '2024-10',
				'hasta'   => '2025-04',
				'texto'   => ovc_lista(
					array(
						'Diseñé procesos de integración, transformación y análisis de datos para optimizar procesos organizacionales.',
						'Construí tableros de control e indicadores de gestión y automaticé tareas repetitivas de consolidación de información.',
					)
				),
			),
			array(
				'titulo'  => 'UT Coltemp HQ5 | Colpensiones',
				'entidad' => 'UT Coltemp HQ5 | Colpensiones',
				'rol'     => 'Profesional II — Gerencia de Prevención del Fraude',
				'desde'   => '2023-11',
				'hasta'   => '2024-10',
				'texto'   => ovc_lista(
					array(
						'Validé información académica y laboral de afiliados —certificaciones, historias laborales y soportes documentales— contrastándola contra las bases de datos institucionales.',
						'Atendí solicitudes de consulta en listas restrictivas y preventivas para procesos de vinculación de funcionarios y contratistas requeridos por Recursos Humanos y Nómina.',
						'Ejecuté cruces de registros entre sistemas para identificar inconsistencias, duplicidades y novedades que afectaban el reconocimiento de derechos.',
						'Diseñé y desarrollé los modelos de seguimiento, control de reparto y cifras que alimentaban el tablero de la Gerencia.',
						'Optimicé los procesos de las bases de datos del área y mantuve actualizada información personal sujeta a reserva legal, bajo los protocolos de confidencialidad de la entidad.',
						'Elaboré informes de seguimiento sobre el estado y la calidad de la información procesada.',
					)
				),
			),
			array(
				'titulo'  => 'Ministerio de las Culturas, las Artes y los Saberes',
				'entidad' => 'Ministerio de las Culturas, las Artes y los Saberes',
				'rol'     => 'Ingeniero de Sistemas — Grupo de Tecnologías y Sistemas de Información',
				'desde'   => '2022-04',
				'hasta'   => '2023-10',
				'texto'   => ovc_lista(
					array(
						'Administré, actualicé y controlé bases de datos e información de los sistemas institucionales, generando reportes mensuales de gestión e indicadores para las áreas usuarias.',
						'Realicé el soporte funcional de los sistemas de información, portales y aplicativos institucionales, documentando casos y cumpliendo los Acuerdos de Nivel de Servicio (ANS).',
						'Participé en el control y actualización del inventario de hardware y software, verificando el cumplimiento de las políticas de licenciamiento.',
						'Apliqué los procedimientos de resguardo, manejo y protección de la información oficial de la entidad.',
					)
				),
			),
			array(
				'titulo'  => 'Computel System',
				'entidad' => 'Computel System',
				'rol'     => 'Técnico de Sistemas',
				'desde'   => '2019-11',
				'hasta'   => '2022-01',
				'texto'   => ovc_lista(
					array(
						'Mantuve el inventario de equipos tecnológicos y partes, y los registros de mantenimiento de la infraestructura.',
						'Dicté capacitaciones a usuarios en SharePoint y OneDrive, y atendí requerimientos tecnológicos de las áreas.',
					)
				),
			),
			array(
				'titulo'  => 'Kuxan S.A.S.',
				'entidad' => 'Kuxan S.A.S.',
				'rol'     => 'Desarrollador .NET',
				'desde'   => '2019-05',
				'hasta'   => '2019-10',
				'texto'   => ovc_lista(
					array(
						'Desarrollé aplicaciones con modelado y consulta de bases de datos relacionales mediante SQL.',
						'Automaticé procesos de extracción, validación y depuración de datos, reduciendo el trabajo manual y el margen de error.',
					)
				),
			),
			array(
				'titulo'  => 'ITFIP — Instituto Tolimense de Formación Técnica Profesional',
				'entidad' => 'ITFIP — Instituto Tolimense de Formación Técnica Profesional',
				'rol'     => 'Ingeniero de Sistemas',
				'desde'   => '2016-02',
				'hasta'   => '2018-08',
				'texto'   => ovc_lista(
					array(
						'Administré recursos tecnológicos institucionales y participé en el desarrollo y mantenimiento de los sitios web de la institución.',
					)
				),
			),
			array(
				'titulo'  => 'Lunanet',
				'entidad' => 'Lunanet',
				'rol'     => 'Administrador de Negocio',
				'desde'   => '2013-07',
				'hasta'   => '2015-08',
				'texto'   => ovc_lista(
					array(
						'Administré la operación y la información comercial: registro de clientes, control de inventario y consolidación de indicadores de gestión.',
					)
				),
			),
		),

		/* -----------------------------------------------------------------
		 * RECONOCIMIENTOS
		 * -------------------------------------------------------------- */
		'cv_reconocimiento' => array(
			array(
				'titulo'  => 'Premio Alfonso Palacio Rudas 2026 — Categoría Académico',
				'entidad' => 'Contraloría General de la República',
				'rol'     => 'Décima Versión',
				'desde'   => '2026-08',
				'texto'   => '<p>Distinción nacional por una metodología que aplica análisis de datos a la verificación de veracidad, calidad y consistencia de la información reportada por entidades públicas, triangulando fuentes independientes (SECOP II, informes de control fiscal, actos administrativos y estados financieros) para detectar inconsistencias y sustentar hallazgos con trazabilidad documental.</p>',
			),
			array(
				// El CV no indica la fecha de este reconocimiento: se deja vacía.
				'titulo'  => 'Primer Lugar — Hackathon Talento Tech, Región 7 (Boyacá – Cundinamarca)',
				'entidad' => 'Ministerio TIC de Colombia',
				'rol'     => 'Politécnico Grancolombiano, CENDI y Cultivos Feidaia S.A.S.',
				'texto'   => '<p>Primer puesto regional en el hackathon del programa nacional Talento Tech del MinTIC, con reconocimiento expreso a la excelencia, creatividad e innovación del proyecto. Los retos se resuelven bajo presión de tiempo y con datos reales de la operación.</p>',
			),
			array(
				// El CV da el año pero no el mes.
				'titulo'  => 'Hackathon Colsubsidio × 30X 2026 — Reto de Hotelería',
				'entidad' => 'Colsubsidio × 30X',
				'rol'     => 'Solución CuentaVoz · Equipo StockXperts',
				'desde'   => '2026',
				'texto'   => '<p>Sistema de captura y validación de inventario construido sobre datos reales de la operación: 53 bodegas, 1.040 artículos y 1.405 registros procesados, con detección automática de 79 inconsistencias de existencias antes de su cargue al sistema corporativo. El componente central fue el motor de validación y conciliación de datos.</p>',
			),
		),

		/* -----------------------------------------------------------------
		 * ESTUDIOS Y FORMACION COMPLEMENTARIA
		 * -------------------------------------------------------------- */
		'cv_estudio'        => array(
			/* ---- Formación académica ---- */
			array(
				'titulo'    => 'Ingeniería de Sistemas',
				'entidad'   => 'Universidad ITFIP',
				'rol'       => 'Ingeniero de Sistemas',
				'desde'     => '2017-08',
				'academico' => true,
				'texto'     => '<p>Analiza, diseña y desarrolla proyectos relacionados con tecnologías de la información que involucran componentes distribuidos.</p>',
			),
			array(
				'titulo'    => 'Tecnología en Desarrollo de Sistemas de Información',
				'entidad'   => 'SENA',
				'rol'       => 'Tecnólogo en Desarrollo de Sistemas de Información',
				'desde'     => '2023-03',
				'academico' => true,
			),
			array(
				// La institución sigue sin confirmar.
				'titulo'    => 'Técnico en Contabilidad y Operaciones Comerciales y Financieras',
				'rol'       => 'Técnico',
				'desde'     => '2019-04',
				'academico' => true,
			),

			/* ---- Formación complementaria ---- */
			array(
				'titulo'  => 'Hacker Ético — Cisco Networking Academy',
				'entidad' => 'EDUTEK · Cisco Networking Academy',
				'rol'     => 'Certificación',
				'desde'   => '2026',
			),
			array(
				'titulo'  => 'Cursos de Excel Avanzado y Fundamentos de Power BI (48 h)',
				'entidad' => 'TEC Alianza — Colsubsidio Educación Tecnológica',
				'rol'     => 'Curso',
				'desde'   => '2026',
			),
			array(
				'titulo'  => 'Bootcamp en IA, Machine Learning y Analítica Avanzada (100 h)',
				'entidad' => 'Cámara de Comercio de Bogotá y Cymetria Group',
				'rol'     => 'Bootcamp · Programa EmerTech 2.0',
				'desde'   => '2026',
			),
			array(
				'titulo'  => 'Curso en Control Fiscal Participativo',
				'entidad' => 'Contraloría General de la República',
				'rol'     => 'Curso',
				'desde'   => '2026',
			),
			array(
				'titulo'  => 'Tablas de Retención Documental y ABC del Acuerdo Único de la Función Archivística (80 h)',
				'entidad' => 'Archivo General de la Nación Jorge Palacios Preciado',
				'rol'     => 'Curso',
				'desde'   => '2026',
			),
			array(
				'titulo'  => 'Bootcamp en Análisis de Datos, Nivel Intermedio (159 h)',
				'entidad' => 'Ministerio TIC — Programa Talento Tech',
				'rol'     => 'Bootcamp',
				'desde'   => '2025',
			),
			array(
				'titulo'  => 'Curso Intermedio en Modelamiento de Datos y Analítica (40 h)',
				'entidad' => 'Universidad de los Andes — Educación Continua y Colsubsidio',
				'rol'     => 'Curso',
				'desde'   => '2025',
			),
			array(
				'titulo'  => 'Diplomado en Gerencia de Proyectos',
				'entidad' => 'Registrado en Libro de Actas 0020250307, Medellín',
				'rol'     => 'Diplomado',
				'desde'   => '2025',
			),
			array(
				'titulo'  => 'Certificado de Competencia Laboral: reparar equipos de cómputo según procedimiento técnico',
				'entidad' => 'SENA — Centro Nacional Colombo Alemán',
				'rol'     => 'Certificado de competencia laboral',
				'desde'   => '2024',
			),
			array(
				'titulo'  => 'Curso de Asesor de Servicio al Cliente con énfasis en comunicación asertiva (40 h)',
				'entidad' => 'Colsubsidio Educación Tecnológica',
				'rol'     => 'Curso',
				'desde'   => '2024',
			),
			array(
				'titulo'  => 'Gestión del Servicio al Cliente',
				'entidad' => 'SENA',
				'rol'     => 'Certificación',
				'desde'   => '2015-11',
				'texto'   => '<p>Relación entre un proveedor de productos o servicios y el cliente para satisfacer sus expectativas.</p>',
			),
			array(
				'titulo'  => 'Diplomado en Mantenimiento y Reparación de Portátiles',
				'entidad' => 'Universidad ITFIP',
				'rol'     => 'Diplomado',
				'desde'   => '2013-11',
				'texto'   => '<p>Electrónica básica, arquitectura de hardware, reparación de portátiles y conceptos de redes.</p>',
			),
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
			__( 'Importar CV', 'ov-child' ),
			__( 'Importar CV', 'ov-child' ),
			'manage_options',
			'ovc-importar-cv',
			'ovc_pantalla_importar_cv'
		);
	}
);

/**
 * Pantalla de importación.
 */
function ovc_pantalla_importar_cv() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos para esta página.', 'ov-child' ) );
	}

	$resultado = null;
	if ( isset( $_POST['ovc_importar'] ) && check_admin_referer( 'ovc_importar_cv' ) ) {
		$resultado = ovc_importar_cv();
	}

	$datos  = ovc_datos_cv_iniciales();
	$titulos = array(
		'cv_experiencia'    => __( 'Experiencia', 'ov-child' ),
		'cv_reconocimiento' => __( 'Reconocimientos', 'ov-child' ),
		'cv_estudio'        => __( 'Estudios y formación', 'ov-child' ),
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Importar el currículum', 'ov-child' ); ?></h1>

		<?php if ( is_array( $resultado ) ) : ?>
			<div class="notice notice-success">
				<p>
					<?php
					printf(
						/* translators: 1: fichas creadas, 2: fichas omitidas */
						esc_html__( 'Listo: %1$d fichas creadas, %2$d ya existían y se han dejado como estaban.', 'ov-child' ),
						(int) $resultado['creadas'],
						(int) $resultado['omitidas']
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<p>
			<?php esc_html_e( 'Crea las fichas del currículum. Se puede pulsar varias veces sin miedo: comprueba por título y no duplica nada.', 'ov-child' ); ?>
		</p>

		<table class="widefat striped" style="max-width:640px">
			<thead><tr><th><?php esc_html_e( 'Sección', 'ov-child' ); ?></th><th><?php esc_html_e( 'Fichas', 'ov-child' ); ?></th><th><?php esc_html_e( 'Ya publicadas', 'ov-child' ); ?></th></tr></thead>
			<tbody>
			<?php foreach ( $titulos as $pt => $etiqueta ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $etiqueta ); ?></strong></td>
					<td><?php echo (int) count( $datos[ $pt ] ); ?></td>
					<td><?php echo (int) wp_count_posts( $pt )->publish; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<form method="post">
			<?php wp_nonce_field( 'ovc_importar_cv' ); ?>
			<p>
				<button type="submit" name="ovc_importar" value="1" class="button button-primary">
					<?php esc_html_e( 'Importar ahora', 'ov-child' ); ?>
				</button>
			</p>
		</form>

		<h2><?php esc_html_e( 'Después de importar', 'ov-child' ); ?></h2>
		<ol>
			<li><?php esc_html_e( 'Edita la página «Acerca» con el maquetador de Divi.', 'ov-child' ); ?></li>
			<li><?php esc_html_e( 'Deja un solo módulo de texto en cada sección con el shortcode que le toca: [cv_experiencia], [cv_estudios] o [cv_reconocimientos].', 'ov-child' ); ?></li>
			<li><?php esc_html_e( 'A partir de ahí, cada entrada nueva se añade desde el menú lateral, sin tocar el maquetador.', 'ov-child' ); ?></li>
		</ol>
	</div>
	<?php
}

/**
 * Crea las fichas.
 *
 * @return array Contador de creadas y omitidas.
 */
function ovc_importar_cv() {
	$creadas  = 0;
	$omitidas = 0;

	foreach ( ovc_datos_cv_iniciales() as $post_type => $entradas ) {
		// menu_order conserva el orden del CV para las fichas sin fecha.
		$orden = 0;

		foreach ( $entradas as $entrada ) {
			++$orden;

			$existente = get_posts(
				array(
					'post_type'        => $post_type,
					'title'            => $entrada['titulo'],
					'post_status'      => 'any',
					'posts_per_page'   => 1,
					'fields'           => 'ids',
					'suppress_filters' => false,
				)
			);

			if ( ! empty( $existente ) ) {
				++$omitidas;
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'    => $post_type,
					'post_status'  => 'publish',
					'post_title'   => $entrada['titulo'],
					'post_content' => isset( $entrada['texto'] ) ? $entrada['texto'] : '',
					'menu_order'   => $orden,
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			$meta = array(
				'_ovc_entidad' => isset( $entrada['entidad'] ) ? $entrada['entidad'] : '',
				'_ovc_rol'     => isset( $entrada['rol'] ) ? $entrada['rol'] : '',
				'_ovc_desde'   => isset( $entrada['desde'] ) ? $entrada['desde'] : '',
				'_ovc_hasta'   => isset( $entrada['hasta'] ) ? $entrada['hasta'] : '',
				'_ovc_actual'  => ! empty( $entrada['actual'] ) ? '1' : '',
				'_ovc_academico' => ! empty( $entrada['academico'] ) ? '1' : '',
			);

			foreach ( $meta as $clave => $valor ) {
				if ( '' !== $valor ) {
					update_post_meta( $post_id, $clave, $valor );
				}
			}

			++$creadas;
		}
	}

	return array(
		'creadas'  => $creadas,
		'omitidas' => $omitidas,
	);
}
