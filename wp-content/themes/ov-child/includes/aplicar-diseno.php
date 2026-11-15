<?php
/**
 * Aplica de una vez todos los cambios de maquetación en las páginas.
 *
 * Durante el trabajo en local, cada cambio de página se hizo con un script
 * suelto. Repetir eso a mano en el servidor es la parte que más fácil se
 * rompe, así que aquí están todos juntos, en una pantalla, y con vuelta atrás.
 *
 * Antes de tocar nada guarda una copia del contenido original de cada página
 * en su propia metadata (_ovc_respaldo_contenido). El botón «Deshacer» la
 * restaura tal cual estaba.
 *
 * Todo es idempotente: si un cambio ya está aplicado, se salta.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Busca una página por su slug y devuelve su ID.
 *
 * @param string $slug Slug.
 * @return int
 */
function ovc_pagina( $slug ) {
	$p = get_page_by_path( $slug );
	return $p ? (int) $p->ID : 0;
}

/**
 * Guarda una copia del contenido antes de modificarlo, si no hay ya una.
 *
 * @param int $post_id Página.
 */
function ovc_respaldar_pagina( $post_id ) {
	if ( ! get_post_meta( $post_id, '_ovc_respaldo_contenido', true ) ) {
		$p = get_post( $post_id );
		update_post_meta( $post_id, '_ovc_respaldo_contenido', $p->post_content );
	}
}

/**
 * Escribe el contenido de una página sin pasar por kses.
 *
 * Los shortcodes de Divi llevan atributos que el filtro de contenido recorta
 * cuando el guardado no viene de un usuario con unfiltered_html.
 *
 * @param int    $post_id   Página.
 * @param string $contenido Contenido nuevo.
 */
function ovc_guardar_pagina( $post_id, $contenido ) {
	global $wpdb;
	$wpdb->update( $wpdb->posts, array( 'post_content' => $contenido ), array( 'ID' => $post_id ) );
	clean_post_cache( $post_id );
}

/**
 * Devuelve la definición de todos los pasos.
 *
 * Cada paso tiene: etiqueta, comprobación de si ya está hecho, y acción.
 *
 * @return array
 */
function ovc_pasos_diseno() {
	$v       = '4.27.4';
	$acerca  = ovc_pagina( 'acerca' );
	$portafo = ovc_pagina( 'portafolio' );

	return array(

		'reconocimientos' => array(
			'titulo' => __( 'Sección RECONOCIMIENTOS antes de EXPERIENCIA', 'ov-child' ),
			'pagina' => $acerca,
			'hecho'  => function () use ( $acerca ) {
				return $acerca && false !== strpos( get_post( $acerca )->post_content, '[cv_reconocimientos]' );
			},
			'accion' => function () use ( $acerca, $v ) {
				$c   = get_post( $acerca )->post_content;
				$pos = strpos( $c, '<h2>EXPERIENCIA</h2>' );
				if ( false === $pos ) {
					return __( 'No encuentro el encabezado EXPERIENCIA.', 'ov-child' );
				}
				$ini = strrpos( substr( $c, 0, $pos ), '[et_pb_section' );
				if ( false === $ini ) {
					return __( 'No encuentro el inicio de esa sección.', 'ov-child' );
				}
				$seccion = ovc_seccion_divi( 'RECONOCIMIENTOS', '[cv_reconocimientos]', $v, '80px||40px||false|false' );
				ovc_guardar_pagina( $acerca, substr( $c, 0, $ini ) . $seccion . substr( $c, $ini ) );
				return true;
			},
		),

		'experiencia' => array(
			'titulo' => __( 'Los módulos de EXPERIENCIA pasan a [cv_experiencia]', 'ov-child' ),
			'pagina' => $acerca,
			'hecho'  => function () use ( $acerca ) {
				return $acerca && false !== strpos( get_post( $acerca )->post_content, '[cv_experiencia]' );
			},
			'accion' => function () use ( $acerca, $v ) {
				$c = get_post( $acerca )->post_content;
				$n = 0;
				$c = preg_replace(
					'/\[et_pb_text[^\]]*\]<h4>COMPUTEL.*?<h4>KUXAN.*?<h4>UNIVERSIDAD ITFIP.*?\[\/et_pb_text\]/s',
					'[et_pb_text _builder_version="' . $v . '"][cv_experiencia][/et_pb_text]',
					$c,
					1,
					$n
				);
				if ( ! $n ) {
					return __( 'No encuentro los tres módulos de experiencia originales.', 'ov-child' );
				}
				ovc_guardar_pagina( $acerca, $c );
				return true;
			},
		),

		'educacion' => array(
			'titulo' => __( 'EDUCACIÓN se parte en FORMAL y NO FORMAL', 'ov-child' ),
			'pagina' => $acerca,
			'hecho'  => function () use ( $acerca ) {
				return $acerca && false !== strpos( get_post( $acerca )->post_content, '[cv_educacion_formal]' );
			},
			'accion' => function () use ( $acerca, $v ) {
				$c = get_post( $acerca )->post_content;
				$n = 0;

				// Primero los módulos originales pasan al shortcode completo.
				if ( false === strpos( $c, '[cv_estudios]' ) ) {
					$c = preg_replace(
						'/\[et_pb_text[^\]]*\]<h4>INGENIERO DE SISTEMAS.*?GESTIÓN SERVICIO AL CLIENTE.*?DIPLOMADO MANTENIMIENTO.*?\[\/et_pb_text\]/s',
						'[et_pb_text _builder_version="' . $v . '"][cv_estudios][/et_pb_text]',
						$c,
						1,
						$n
					);
					if ( ! $n ) {
						return __( 'No encuentro los tres módulos de educación originales.', 'ov-child' );
					}
				}

				$c = str_replace( '<h2>EDUCACIÓN</h2>', '<h2>EDUCACIÓN FORMAL</h2>', $c );

				$bloque = '[/et_pb_text]'
					. ovc_encabezado_divi( 'EDUCACIÓN NO FORMAL', $v, '70px||5px||false|false' )
					. '[et_pb_text _builder_version="' . $v . '" custom_margin="40px|||||"][cv_educacion_no_formal]';

				$c = str_replace( '[cv_estudios]', '[cv_educacion_formal]' . $bloque, $c, $n );
				if ( ! $n ) {
					return __( 'No encuentro el listado de estudios.', 'ov-child' );
				}
				ovc_guardar_pagina( $acerca, $c );
				return true;
			},
		),

		'proyectos_acerca' => array(
			'titulo' => __( 'Sección PROYECTOS DESTACADOS en Acerca', 'ov-child' ),
			'pagina' => $acerca,
			'hecho'  => function () use ( $acerca ) {
				return $acerca && false !== strpos( get_post( $acerca )->post_content, '[proyectos_destacados' );
			},
			'accion' => function () use ( $acerca, $v ) {
				$c   = get_post( $acerca )->post_content;
				$pos = strpos( $c, '<h2>HABILIDADES</h2>' );
				$ini = ( false !== $pos ) ? strrpos( substr( $c, 0, $pos ), '[et_pb_section' ) : false;

				$seccion = ovc_seccion_divi(
					'PROYECTOS DESTACADOS',
					'[proyectos_destacados limite="6" columnas="3"]',
					$v,
					'80px||80px||false|false',
					' background_color="#f5f8fa"'
				);

				$nuevo = ( false !== $ini )
					? substr( $c, 0, $ini ) . $seccion . substr( $c, $ini )
					: $c . $seccion;

				ovc_guardar_pagina( $acerca, $nuevo );
				return true;
			},
		),

		'aficiones' => array(
			'titulo' => __( 'Sección AFICIONES al final de Acerca (se oculta si está vacía)', 'ov-child' ),
			'pagina' => $acerca,
			'hecho'  => function () use ( $acerca ) {
				return $acerca && false !== strpos( get_post( $acerca )->post_content, '[cv_aficiones]' );
			},
			'accion' => function () use ( $acerca, $v ) {
				$c       = get_post( $acerca )->post_content;
				$seccion = '[et_pb_section fb_built="1" _builder_version="' . $v . '" custom_padding="20px||80px||false|false"]'
					. '[et_pb_row _builder_version="' . $v . '"][et_pb_column type="4_4" _builder_version="' . $v . '"]'
					. '[et_pb_text _builder_version="' . $v . '"][cv_aficiones][/et_pb_text]'
					. '[/et_pb_column][/et_pb_row][/et_pb_section]';
				ovc_guardar_pagina( $acerca, $c . $seccion );
				return true;
			},
		),

		'ancho_completo' => array(
			'titulo' => __( 'Experiencia y educación pasan a ancho completo', 'ov-child' ),
			'pagina' => $acerca,
			'hecho'  => function () use ( $acerca ) {
				if ( ! $acerca ) {
					return false;
				}
				$c   = get_post( $acerca )->post_content;
				$pos = strpos( $c, '[cv_experiencia]' );
				if ( false === $pos ) {
					return false;
				}
				$ini = strrpos( substr( $c, 0, $pos ), '[et_pb_row' );
				$fin = strpos( $c, '[/et_pb_row]', $pos );
				return ! preg_match( '/type="(?!4_4")[0-9_]+"/', substr( $c, $ini, $fin - $ini ) );
			},
			'accion' => function () use ( $acerca ) {
				$c = get_post( $acerca )->post_content;
				foreach ( array( '[cv_experiencia]', '[cv_educacion_formal]' ) as $sc ) {
					$c = ovc_fila_ancho_completo( $c, $sc );
				}
				ovc_guardar_pagina( $acerca, $c );
				return true;
			},
		),

		'portafolio' => array(
			'titulo' => __( 'La página Portafolio usa la rejilla con filtros', 'ov-child' ),
			'pagina' => $portafo,
			'hecho'  => function () use ( $portafo ) {
				return $portafo && false !== strpos( get_post( $portafo )->post_content, '[proyectos_destacados' );
			},
			'accion' => function () use ( $portafo, $v ) {
				if ( ! $portafo ) {
					return __( 'No encuentro la página Portafolio.', 'ov-child' );
				}
				$c = get_post( $portafo )->post_content;
				$n = 0;
				$c = preg_replace(
					'/\[et_pb_filterable_portfolio\b.*?\[\/et_pb_filterable_portfolio\]/s',
					'[et_pb_text _builder_version="' . $v . '"][proyectos_destacados filtros="on" limite="-1" columnas="3" destacados="off"][/et_pb_text]',
					$c,
					1,
					$n
				);
				if ( ! $n ) {
					return __( 'No encuentro el módulo de portafolio filtrable de Divi.', 'ov-child' );
				}
				ovc_guardar_pagina( $portafo, $c );
				return true;
			},
		),
	);
}

/**
 * Pasa a ancho completo la fila que contiene un shortcode.
 *
 * Esas filas venían del diseño viejo: media columna de texto y media de fotos
 * decorativas. Con tres entradas cuadraba; con diez, el texto queda apretado en
 * un lado y el otro se queda vacío media pantalla. Ahora cada ficha lleva sus
 * propias fotos, así que la columna decorativa sobra.
 *
 * @param string $c         Contenido de la página.
 * @param string $shortcode Shortcode que marca la fila.
 * @param string $v         Versión del builder.
 * @return string Contenido con la fila reconstruida.
 */
function ovc_fila_ancho_completo( $c, $shortcode, $v = '4.27.4' ) {
	$pos = strpos( $c, $shortcode );
	if ( false === $pos ) {
		return $c;
	}

	$ini = strrpos( substr( $c, 0, $pos ), '[et_pb_row' );
	$fin = strpos( $c, '[/et_pb_row]', $pos );
	if ( false === $ini || false === $fin ) {
		return $c;
	}
	$fin += strlen( '[/et_pb_row]' );

	$fila = substr( $c, $ini, $fin - $ini );

	// Cualquier columna que no sea 4_4 significa que la fila está partida.
	// (Antes solo se miraba 1_2 y se escapaban las filas 2_3 + 1_3.)
	if ( ! preg_match( '/type="(?!4_4")[0-9_]+"/', $fila ) ) {
		return $c; // Ya estaba a lo ancho.
	}

	// Se conservan solo los módulos de texto con shortcodes o encabezados.
	preg_match_all( '/\[et_pb_text[^\]]*\](?:(?!\[et_pb_text).)*?\[\/et_pb_text\]/s', $fila, $m );
	$utiles = array();
	foreach ( $m[0] as $mod ) {
		if ( preg_match( '/\[(cv_|proyectos_)/', $mod ) || preg_match( '/<h[1-6]>/', $mod ) ) {
			$utiles[] = $mod;
		}
	}
	if ( ! $utiles ) {
		return $c;
	}

	$nueva = '[et_pb_row _builder_version="' . $v . '"][et_pb_column type="4_4" _builder_version="' . $v . '"]'
		. implode( '', $utiles ) . '[/et_pb_column][/et_pb_row]';

	return substr( $c, 0, $ini ) . $nueva . substr( $c, $fin );
}

/**
 * Encabezado de sección con el estilo del sitio.
 *
 * @param string $titulo Texto.
 * @param string $v      Versión del builder.
 * @param string $margen Margen del módulo de texto.
 * @return string
 */
function ovc_encabezado_divi( $titulo, $v, $margen = '||5px' ) {
	return '[et_pb_text _builder_version="' . $v . '" header_font="|700|||||||" header_font_size="36px"'
		. ' header_font_size_tablet="28px" header_font_size_phone="24px" header_font_size_last_edited="on|phone"'
		. ' header_letter_spacing="4px" custom_margin="' . $margen . '"'
		. ' animation_style="bounce" animation_direction="left"]<h2>' . $titulo . '</h2>[/et_pb_text]'
		. '[et_pb_divider divider_weight="5px" _builder_version="' . $v . '" width="150px"'
		. ' animation_style="bounce" animation_direction="left"][/et_pb_divider]';
}

/**
 * Sección completa: encabezado, separador y contenido.
 *
 * @param string $titulo    Encabezado.
 * @param string $shortcode Contenido.
 * @param string $v         Versión del builder.
 * @param string $padding   Padding de la sección.
 * @param string $extra     Atributos extra de la sección.
 * @return string
 */
function ovc_seccion_divi( $titulo, $shortcode, $v, $padding, $extra = '' ) {
	return '[et_pb_section fb_built="1" _builder_version="' . $v . '"' . $extra
		. ' custom_padding="' . $padding . '"]'
		. '[et_pb_row _builder_version="' . $v . '"][et_pb_column type="4_4" _builder_version="' . $v . '"]'
		. ovc_encabezado_divi( $titulo, $v )
		. '[et_pb_text _builder_version="' . $v . '" custom_margin="40px|||||"]' . $shortcode . '[/et_pb_text]'
		. '[/et_pb_column][/et_pb_row][/et_pb_section]';
}

/**
 * Convierte los iconos de Divi 3.x al formato actual.
 *
 * El formato %%indice%% pasa por una tabla cuyas entidades vienen ya
 * escapadas, y acaba escribiendo data-icon="&amp;#x24;": el navegador lo lee
 * como texto y pinta seis caracteres sueltos en vez del icono.
 *
 * @return int Iconos convertidos.
 */
function ovc_convertir_iconos() {
	global $wpdb;

	if ( ! function_exists( 'et_pb_get_font_icon_symbols' ) ) {
		return 0;
	}

	$simbolos = et_pb_get_font_icon_symbols();
	$filas    = $wpdb->get_results( "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_content LIKE '%_icon=\"%%%'" );
	$total    = 0;

	foreach ( $filas as $f ) {
		$n     = 0;
		$nuevo = preg_replace_callback(
			'/([a-z_]*icon)="%%(\d+)%%"/',
			function ( $m ) use ( $simbolos, &$n ) {
				if ( ! isset( $simbolos[ (int) $m[2] ] ) ) {
					return $m[0];
				}
				++$n;
				return $m[1] . '="' . html_entity_decode( $simbolos[ (int) $m[2] ], ENT_QUOTES, 'UTF-8' ) . '||divi||400"';
			},
			$f->post_content
		);

		if ( $n ) {
			$wpdb->update( $wpdb->posts, array( 'post_content' => $nuevo ), array( 'ID' => $f->ID ) );
			clean_post_cache( $f->ID );
			$total += $n;
		}
	}

	return $total;
}

/**
 * Corrige las erratas de texto del sitio.
 *
 * @return int Filas tocadas.
 */
function ovc_corregir_erratas() {
	global $wpdb;

	$pares = array(
		'Contácto'          => 'Contacto',
		'contácto'          => 'contacto',
		'Acerca de mi<'     => 'Acerca de mí<',
		'Tranajo en Equipo' => 'Trabajo en Equipo',
		'sentído de pertenencía, responsabílídad, eficíencía y comprometído' => 'sentido de pertenencia, responsabilidad, eficiencia y comprometido',
		'facílídad para laborar en equípo, líderazgo en la realízacíón de accíones y solucíón' => 'facilidad para laborar en equipo, liderazgo en la realización de acciones y solución',
	);

	$n = 0;
	foreach ( $pares as $mal => $bien ) {
		foreach ( array( 'post_content', 'post_title' ) as $campo ) {
			$n += (int) $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->posts} SET {$campo} = REPLACE({$campo}, %s, %s) WHERE {$campo} LIKE %s",
					$mal,
					$bien,
					'%' . $wpdb->esc_like( $mal ) . '%'
				)
			);
		}
	}

	return $n;
}

/**
 * Añade la pantalla a Herramientas.
 */
add_action(
	'admin_menu',
	function () {
		add_management_page(
			__( 'Aplicar diseño del CV', 'ov-child' ),
			__( 'Aplicar diseño del CV', 'ov-child' ),
			'manage_options',
			'ovc-aplicar-diseno',
			'ovc_pantalla_aplicar_diseno'
		);
	}
);

/**
 * Pantalla de aplicación.
 */
function ovc_pantalla_aplicar_diseno() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'No tienes permisos para esta página.', 'ov-child' ) );
	}

	$aviso = '';

	if ( isset( $_POST['ovc_aplicar'] ) && check_admin_referer( 'ovc_aplicar_diseno' ) ) {
		$hechos  = 0;
		$fallos  = array();

		foreach ( ovc_pasos_diseno() as $paso ) {
			if ( ! $paso['pagina'] || call_user_func( $paso['hecho'] ) ) {
				continue;
			}
			ovc_respaldar_pagina( $paso['pagina'] );
			$r = call_user_func( $paso['accion'] );
			if ( true === $r ) {
				++$hechos;
			} else {
				$fallos[] = $paso['titulo'] . ' — ' . $r;
			}
		}

		$iconos  = ovc_convertir_iconos();
		$erratas = ovc_corregir_erratas();

		if ( function_exists( 'et_core_clear_wp_cache' ) ) {
			et_core_clear_wp_cache();
		}

		$aviso = sprintf(
			/* translators: 1: pasos, 2: iconos, 3: erratas */
			__( '%1$d pasos aplicados, %2$d iconos convertidos, %3$d erratas corregidas.', 'ov-child' ),
			$hechos,
			$iconos,
			$erratas
		);
		if ( $fallos ) {
			$aviso .= ' — ' . implode( ' | ', $fallos );
		}
	}

	if ( isset( $_POST['ovc_deshacer'] ) && check_admin_referer( 'ovc_aplicar_diseno' ) ) {
		$n = 0;
		foreach ( array( ovc_pagina( 'acerca' ), ovc_pagina( 'portafolio' ) ) as $id ) {
			$copia = $id ? get_post_meta( $id, '_ovc_respaldo_contenido', true ) : '';
			if ( $copia ) {
				ovc_guardar_pagina( $id, $copia );
				delete_post_meta( $id, '_ovc_respaldo_contenido' );
				++$n;
			}
		}
		$aviso = sprintf(
			/* translators: %d: páginas */
			__( '%d páginas restauradas a como estaban.', 'ov-child' ),
			$n
		);
	}

	$pasos = ovc_pasos_diseno();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Aplicar el diseño del currículum a las páginas', 'ov-child' ); ?></h1>

		<?php if ( $aviso ) : ?>
			<div class="notice notice-info"><p><?php echo esc_html( $aviso ); ?></p></div>
		<?php endif; ?>

		<p>
			<?php esc_html_e( 'Sustituye los módulos escritos a mano de las páginas «Acerca» y «Portafolio» por los shortcodes, y añade las secciones nuevas. Antes de tocar nada guarda una copia del contenido original de cada página, así que se puede volver atrás.', 'ov-child' ); ?>
		</p>

		<table class="widefat striped" style="max-width:760px">
			<thead><tr><th><?php esc_html_e( 'Paso', 'ov-child' ); ?></th><th style="width:120px"><?php esc_html_e( 'Estado', 'ov-child' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $pasos as $paso ) : ?>
					<tr>
						<td><?php echo esc_html( $paso['titulo'] ); ?></td>
						<td>
							<?php if ( ! $paso['pagina'] ) : ?>
								<span style="color:#b32d2e"><?php esc_html_e( 'falta la página', 'ov-child' ); ?></span>
							<?php elseif ( call_user_func( $paso['hecho'] ) ) : ?>
								<strong style="color:#1e7e34"><?php esc_html_e( 'ya aplicado', 'ov-child' ); ?></strong>
							<?php else : ?>
								<?php esc_html_e( 'pendiente', 'ov-child' ); ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				<tr>
					<td><?php esc_html_e( 'Iconos de Divi 3.x al formato actual, y erratas de texto', 'ov-child' ); ?></td>
					<td><?php esc_html_e( 'cada vez', 'ov-child' ); ?></td>
				</tr>
			</tbody>
		</table>

		<form method="post" style="margin-top:18px">
			<?php wp_nonce_field( 'ovc_aplicar_diseno' ); ?>
			<button type="submit" name="ovc_aplicar" value="1" class="button button-primary">
				<?php esc_html_e( 'Aplicar los pasos pendientes', 'ov-child' ); ?>
			</button>
			<button type="submit" name="ovc_deshacer" value="1" class="button"
			        onclick="return confirm('<?php echo esc_js( __( '¿Restaurar las páginas Acerca y Portafolio a como estaban antes?', 'ov-child' ) ); ?>');">
				<?php esc_html_e( 'Deshacer', 'ov-child' ); ?>
			</button>
		</form>

		<h2><?php esc_html_e( 'Orden recomendado', 'ov-child' ); ?></h2>
		<ol>
			<li><?php esc_html_e( 'Herramientas → Importar CV', 'ov-child' ); ?></li>
			<li><?php esc_html_e( 'Herramientas → Importar proyectos', 'ov-child' ); ?></li>
			<li><?php esc_html_e( 'Esta pantalla: Aplicar los pasos pendientes', 'ov-child' ); ?></li>
			<li><?php esc_html_e( 'Divi → Opciones del tema → Constructor → Avanzado → Vaciar caché', 'ov-child' ); ?></li>
		</ol>
	</div>
	<?php
}
