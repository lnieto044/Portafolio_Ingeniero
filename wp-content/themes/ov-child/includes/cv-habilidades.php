<?php
/**
 * Habilidades.
 *
 * Uso:  [cv_habilidades]
 *       [cv_habilidades titulo="HABILIDADES" columnas="2" blandas="off"]
 *
 * Sustituye a las barras de porcentaje que traía la página desde 2020. Aquellas
 * tenían dos problemas serios de cara a quien contrata:
 *
 *   1. No había ni una sola competencia técnica. Seis barras de Liderazgo,
 *      Perseverancia o Inteligencia emocional, y nada de Power BI, SQL, Python
 *      o PHP. Quien busca un perfil de datos no encontraba lo que busca.
 *
 *   2. Los porcentajes eran autoasignados. «Inteligencia emocional 87 %» no se
 *      puede medir ni comprobar, y todos estaban entre el 87 y el 95, que es
 *      el rango que delata que la cifra es de adorno.
 *
 * Aquí cada competencia sale de algo verificable que ya está en el sitio: una
 * certificación de la sección de educación, un puesto de la de experiencia o
 * la ficha técnica de un proyecto publicado. Sin números, porque el respaldo
 * es el documento, no una barra.
 *
 * Las cualidades personales no desaparecen: bajan a una línea al pie, que es
 * el peso que les corresponde.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Los grupos de competencias.
 *
 * Se declaran aquí, no en la base de datos, por el mismo motivo que los
 * servicios: son cinco y cambian una vez al año, no una vez por proyecto.
 *
 * @return array
 */
function ovc_lista_habilidades() {
	return array(
		/*
		 * La nube va primera a proposito. Es lo que encabeza la hoja de vida
		 * con la que se postula, y lo que respalda el proyecto destacado:
		 * ponerla la cuarta obligaria a bajar para encontrarla.
		 */
		array(
			'icono'  => 'nube',
			'titulo' => __( 'Nube y seguridad de acceso', 'ov-child' ),
			'items'  => array( 'Amazon Cognito', 'MFA TOTP', 'Tokens RS256 / JWKS', 'IAM — mínimo privilegio', 'Gestión de sesiones', 'Hacking ético' ),
		),
		array(
			'icono'  => 'datos',
			'titulo' => __( 'Datos e inteligencia de negocio', 'ov-child' ),
			'items'  => array( 'Power BI', 'Looker Studio', 'SQL Server', 'MySQL', 'Python · pandas', 'ETL / ELT', 'Power Query', 'DAX', 'Excel avanzado', 'Machine Learning' ),
		),
		array(
			'icono'  => 'codigo',
			'titulo' => __( 'Desarrollo e integración', 'ov-child' ),
			'items'  => array( 'Python', 'APIs REST', 'FastAPI', 'ASP.NET Core', 'Spring Boot', 'PHP', 'JavaScript', 'WordPress', 'Git', 'Postman' ),
		),
		array(
			'icono'  => 'engranaje',
			'titulo' => __( 'Automatización', 'ov-child' ),
			'items'  => array( 'Power Automate', 'Scripts en Python', 'PowerShell', 'SharePoint', 'Integración con Microsoft 365', 'Informes programados' ),
		),
		array(
			'icono'  => 'soporte',
			'titulo' => __( 'Soporte y servicios TI', 'ov-child' ),
			'items'  => array( 'Mesa de ayuda N1 y N2', 'Remedy', 'Salesforce', 'ITIL · ANS', 'Redes y VPN', 'Mantenimiento y reparación', 'Inventario de activos', 'Microsoft 365' ),
		),
		array(
			'icono'  => 'validacion',
			'titulo' => __( 'Validación y control de la información', 'ov-child' ),
			'items'  => array( 'Verificación documental', 'Cruce entre sistemas', 'Listas restrictivas', 'Tablas de retención documental', 'Control fiscal participativo' ),
		),
		array(
			'icono'  => 'diseno',
			'titulo' => __( 'Diseño y marca', 'ov-child' ),
			'items'  => array( 'Adobe Photoshop', 'Identidad visual', 'Branding', 'Diseño de interfaces', 'Piezas para impresión y web' ),
		),
		array(
			'icono'  => 'gestion',
			'titulo' => __( 'Gestión', 'ov-child' ),
			'items'  => array( 'Gerencia de proyectos', 'Atención y servicio al cliente', 'Comunicación asertiva', 'Documentación técnica y manuales' ),
		),
	);
}

/**
 * Cualidades personales, sin porcentaje.
 *
 * @return array
 */
function ovc_lista_cualidades() {
	return array(
		__( 'Trabajo en equipo', 'ov-child' ),
		__( 'Liderazgo', 'ov-child' ),
		__( 'Perseverancia', 'ov-child' ),
		__( 'Innovación', 'ov-child' ),
		__( 'Inteligencia emocional', 'ov-child' ),
		__( 'Sentido de pertenencia', 'ov-child' ),
	);
}

/**
 * Idiomas, con el nivel representado.
 *
 * Faltaban por completo, y es de lo primero que se filtra en una oferta.
 *
 * El tercer valor son los tramos que se pintan de cuatro, y no se redondea
 * hacia arriba. Un nivel inflado no aguanta la primera llamada, y en un
 * perfil cuyo argumento es «compruebo que la información sea cierta», que el
 * dato falso esté en su propio currículo es el peor sitio posible.
 *
 * El inglés figura como básico por indicación suya. Conviene que la hoja de
 * vida diga lo mismo: ahí aparece como «B1.1 (Level 6)», que suele ser el
 * número de un nivel de curso y no el nivel que uno habla. Dos documentos
 * con cifras distintas llaman la atención antes que cualquiera de las dos.
 *
 * @return array
 */
function ovc_lista_idiomas() {
	return array(
		array( __( 'Español', 'ov-child' ), __( 'Nativo', 'ov-child' ), 4 ),
		array( __( 'Inglés', 'ov-child' ),  __( 'Básico', 'ov-child' ), 1 ),
		array( __( 'Francés', 'ov-child' ), __( 'Básico', 'ov-child' ), 1 ),
	);
}

/**
 * Trazado de los iconos que no están ya en los servicios.
 *
 * @param string $nombre Icono.
 * @return string
 */
function ovc_icono_habilidad( $nombre ) {
	$propios = array(
		'diseno'    => '<path d="M12 19a7 7 0 1 1 0-14c3.9 0 7 2.7 7 6 0 2-1.6 3-3 3h-1.5a1.5 1.5 0 0 0-1 2.6c.3.3.5.7.5 1.1 0 .7-.6 1.3-1.3 1.3H12Z"/><circle cx="7.5" cy="11.5" r="1"/><circle cx="10.5" cy="8" r="1"/><circle cx="14.5" cy="8.5" r="1"/>',
		'gestion'   => '<path d="M9 3h6v3H9zM4 6h16v15H4z"/><path d="M9 12h6M9 16h4"/>',
		// Nube con candado: la nube sola no dice nada de seguridad.
		'nube'      => '<path d="M7 18a4 4 0 0 1-.4-8 6 6 0 0 1 11.3-1.4A3.8 3.8 0 0 1 21 12.3"/><rect x="12" y="14" width="8" height="6" rx="1.4"/><path d="M14.4 14v-1.6a1.6 1.6 0 0 1 3.2 0V14"/>',
		'engranaje' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
		'idiomas'   => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>',
		'persona'   => '<circle cx="12" cy="8" r="3.4"/><path d="M5 20a7 7 0 0 1 14 0"/>',
	);

	if ( isset( $propios[ $nombre ] ) ) {
		return '<svg class="ovc-hab__icono" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
			. ' stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
			. $propios[ $nombre ] . '</svg>';
	}

	// Los tres restantes ya existen para los servicios: se reaprovechan.
	return str_replace( 'ovc-servicio__icono', 'ovc-hab__icono', ovc_icono_servicio( $nombre ) );
}

/**
 * Pinta la sección.  [cv_habilidades]
 *
 * @param array $atts Atributos.
 * @return string
 */
function ovc_shortcode_habilidades( $atts ) {
	$atts = shortcode_atts(
		array(
			'titulo'   => __( 'HABILIDADES', 'ov-child' ),
			'columnas' => 3,
			'blandas'  => 'on',
		),
		$atts,
		'cv_habilidades'
	);

	$columnas = max( 1, min( 3, (int) $atts['columnas'] ) );

	ob_start();
	?>
	<div class="ovc-habilidades">
		<?php if ( $atts['titulo'] ) : ?>
			<h2 class="ovc-habilidades__titulo"><?php echo esc_html( $atts['titulo'] ); ?></h2>
			<span class="ovc-habilidades__raya" aria-hidden="true"></span>
		<?php endif; ?>

		<div class="ovc-habilidades__rejilla ovc-habilidades--cols-<?php echo esc_attr( $columnas ); ?>">
			<?php foreach ( ovc_lista_habilidades() as $g ) : ?>
				<article class="ovc-hab">
					<span class="ovc-hab__marca">
						<?php echo ovc_icono_habilidad( $g['icono'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<h3 class="ovc-hab__titulo"><?php echo esc_html( $g['titulo'] ); ?></h3>
					<ul class="ovc-hab__items">
						<?php foreach ( $g['items'] as $i ) : ?>
							<li><?php echo esc_html( $i ); ?></li>
						<?php endforeach; ?>
					</ul>
				</article>
			<?php endforeach; ?>
		</div>

		<?php if ( 'on' === $atts['blandas'] ) : ?>
			<?php
			/*
			 * Dos tarjetas, no dos renglones.
			 *
			 * Antes eran dos barras de texto con puntos de separación, justo
			 * debajo de ocho tarjetas con icono y etiquetas. Esa diferencia se
			 * lee como «esto lo añadieron al final», que es exactamente lo que
			 * no debe transmitir un currículo.
			 */
			?>
			<div class="ovc-habilidades__pie">

				<article class="ovc-hab ovc-hab--idiomas">
					<span class="ovc-hab__marca">
						<?php echo ovc_icono_habilidad( 'idiomas' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<h3 class="ovc-hab__titulo"><?php esc_html_e( 'Idiomas', 'ov-child' ); ?></h3>

					<ul class="ovc-idiomas">
						<?php foreach ( ovc_lista_idiomas() as $idioma ) : ?>
							<li class="ovc-idioma">
								<span class="ovc-idioma__nombre"><?php echo esc_html( $idioma[0] ); ?></span>
								<span class="ovc-idioma__tramos" aria-hidden="true">
									<?php for ( $t = 1; $t <= 4; $t++ ) : ?>
										<i class="<?php echo $t <= $idioma[2] ? 'is-lleno' : ''; ?>"></i>
									<?php endfor; ?>
								</span>
								<span class="ovc-idioma__nivel"><?php echo esc_html( $idioma[1] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</article>

				<article class="ovc-hab ovc-hab--cualidades">
					<span class="ovc-hab__marca">
						<?php echo ovc_icono_habilidad( 'persona' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<h3 class="ovc-hab__titulo"><?php esc_html_e( 'En el día a día', 'ov-child' ); ?></h3>
					<ul class="ovc-hab__items">
						<?php foreach ( ovc_lista_cualidades() as $cualidad ) : ?>
							<li><?php echo esc_html( $cualidad ); ?></li>
						<?php endforeach; ?>
					</ul>
				</article>

			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'cv_habilidades', 'ovc_shortcode_habilidades' );
