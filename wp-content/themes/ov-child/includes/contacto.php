<?php
/**
 * Datos y bloque de contacto.
 *
 * Uso:  [ovc_contacto]
 *       [ovc_contacto titulo="Hablemos"]
 *
 * El teléfono y el correo se declaran UNA vez, aquí. Antes vivían escritos a
 * mano dentro del contenido de la página: cambiar de número obligaba a entrar
 * al maquetador, y el enlace «tel:» se quedaba con el número viejo si a uno se
 * le olvidaba tocarlo. Ahora se cambia esta lista y se actualiza la página de
 * contacto, el enlace de WhatsApp y el icono del pie a la vez.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Los datos de contacto.
 *
 * El teléfono se guarda en crudo, solo dígitos con indicativo de país, porque
 * es lo que necesitan «tel:» y WhatsApp. Lo que se le enseña al visitante se
 * compone a partir de ahí.
 *
 * @return array
 */
function ovc_datos_contacto() {
	return apply_filters(
		'ovc_datos_contacto',
		array(
			'telefono'      => '573133898638',
			'correo'        => 'guillermonieto.2003@gmail.com',
			'ciudad'        => __( 'Bogotá y Fusagasugá', 'ov-child' ),
			'modalidad'     => __( 'Presencial, híbrido o remoto', 'ov-child' ),
			'disponible'    => __( 'Proyectos y vinculación laboral', 'ov-child' ),
			'respuesta'     => __( 'Respondo en menos de 24 horas hábiles', 'ov-child' ),
		)
	);
}

/**
 * El teléfono con el formato que se lee: +57 313 389 8638.
 *
 * @return string
 */
function ovc_telefono_legible() {
	$d = preg_replace( '/\D+/', '', ovc_datos_contacto()['telefono'] );

	// Número colombiano: 57 + 10 dígitos.
	if ( 12 === strlen( $d ) && '57' === substr( $d, 0, 2 ) ) {
		return sprintf( '+57 %s %s %s', substr( $d, 2, 3 ), substr( $d, 5, 3 ), substr( $d, 8, 4 ) );
	}

	return '+' . $d;
}

/**
 * Enciende el icono de WhatsApp del pie con el número de arriba.
 *
 * Así no hay dos sitios donde tener el mismo número.
 */
add_filter(
	'ovc_redes_extra',
	function ( $redes ) {
		$tel = preg_replace( '/\D+/', '', ovc_datos_contacto()['telefono'] );

		if ( $tel && isset( $redes['whatsapp'] ) ) {
			$redes['whatsapp']['url'] = 'https://wa.me/' . $tel;
		}

		return $redes;
	}
);

/**
 * Iconos del bloque.
 *
 * @param string $nombre Icono.
 * @return string
 */
function ovc_icono_contacto( $nombre ) {
	$trazos = array(
		'telefono' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2 4.2 2 2 0 0 1 4 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.4 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
		'whatsapp' => '<path d="M3 21l1.7-5A8.5 8.5 0 1 1 8 19.3L3 21z"/><path d="M8.6 9.2c.2-.5.4-.5.6-.5h.5c.2 0 .4 0 .6.5l.7 1.6c.1.2.1.4 0 .6l-.4.5c-.1.2-.2.3-.1.5a6 6 0 0 0 2.6 2.3c.2.1.4 0 .5-.1l.5-.6c.2-.2.3-.2.5-.1l1.6.8c.2.1.3.2.3.4 0 .8-.6 1.5-1.4 1.6a7 7 0 0 1-5.3-2.6 6.4 6.4 0 0 1-1.4-3.1c0-.7.2-1.4.7-1.8z"/>',
		'correo'   => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m3 6 9 6 9-6"/>',
		'lugar'    => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
		'maletin'  => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M2 13h20"/>',
	);

	$d = isset( $trazos[ $nombre ] ) ? $trazos[ $nombre ] : $trazos['correo'];

	return '<svg class="ovc-vcard__icono" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
		. ' stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

/**
 * Pinta el bloque de contacto.  [ovc_contacto]
 *
 * @param array $atts Atributos.
 * @return string
 */
function ovc_shortcode_contacto( $atts ) {
	$atts = shortcode_atts(
		array(
			'titulo' => __( 'Hablemos', 'ov-child' ),
			'bajada' => __( 'Cuénteme qué necesita resolver —un informe que no cuadra, un proceso que se lleva a mano, un sistema que no rinde— y le respondo con una propuesta concreta.', 'ov-child' ),
		),
		$atts,
		'ovc_contacto'
	);

	$d       = ovc_datos_contacto();
	$tel     = preg_replace( '/\D+/', '', $d['telefono'] );
	$legible = ovc_telefono_legible();

	/*
	 * Cuatro tarjetas, cada una con un dato distinto.
	 *
	 * Antes había una de WhatsApp y otra de Teléfono con EL MISMO número
	 * debajo. Repetir el dato no da dos formas de contactar: da la impresión
	 * de que se ha rellenado el hueco por rellenarlo. Ahora el número va una
	 * vez, en la tarjeta destacada, con el enlace de llamar al lado.
	 */
	$tarjetas = array(
		array(
			'icono'   => 'whatsapp',
			'rotulo'  => __( 'WhatsApp y teléfono', 'ov-child' ),
			'valor'   => $legible,
			'url'     => 'https://wa.me/' . $tel,
			'nuevo'   => true,
			'fuerte'  => true,
			/*
			 * Copiar, no marcar.
			 *
			 * Antes era un enlace «tel:». En un ordenador Windows ese esquema
			 * lo atrapa la aplicación que esté registrada para llamadas, que
			 * casi siempre es WhatsApp o Skype: se pulsaba «Llamar» y se abría
			 * WhatsApp, lo mismo que ya hacía la tarjeta. Dos botones para lo
			 * mismo no son dos opciones, son una duda.
			 *
			 * Copiar el número al portapapeles sí hace algo distinto, y hace
			 * lo mismo en el móvil y en el ordenador.
			 */
			'copiar' => $legible,
		),
		array(
			'icono'  => 'correo',
			'rotulo' => __( 'Correo', 'ov-child' ),
			'valor'  => $d['correo'],
			'url'    => 'mailto:' . $d['correo'],
			'nuevo'  => false,
			'fuerte' => false,
			'largo'  => true, // Se pinta más pequeño para que quepa entero.
		),
		array(
			'icono'  => 'lugar',
			'rotulo' => __( 'Dónde estoy', 'ov-child' ),
			'valor'  => $d['ciudad'],
			'url'    => '',
			'nuevo'  => false,
			'fuerte' => false,
		),
		array(
			'icono'  => 'maletin',
			'rotulo' => __( 'Disponibilidad', 'ov-child' ),
			'valor'  => $d['disponible'],
			'url'    => '',
			'nuevo'  => false,
			'fuerte' => false,
		),
	);

	ob_start();
	?>
	<div class="ovc-contacto">

		<h2 class="ovc-contacto__titulo"><?php echo esc_html( $atts['titulo'] ); ?></h2>
		<p class="ovc-contacto__bajada"><?php echo esc_html( $atts['bajada'] ); ?></p>

		<div class="ovc-vcards">
			<?php foreach ( $tarjetas as $t ) : ?>
				<?php
				$etiqueta = $t['url'] ? 'a' : 'div';
				$clases   = 'ovc-vcard'
			. ( $t['fuerte'] ? ' ovc-vcard--fuerte' : '' )
			. ( empty( $t['largo'] ) ? '' : ' ovc-vcard--largo' );
				?>
				<<?php echo esc_attr( $etiqueta ); ?>
					class="<?php echo esc_attr( $clases ); ?>"
					<?php if ( $t['url'] ) : ?>
						href="<?php echo esc_url( $t['url'] ); ?>"
						<?php if ( $t['nuevo'] ) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
					<?php endif; ?>
				>
					<span class="ovc-vcard__marca">
						<?php echo ovc_icono_contacto( $t['icono'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<span class="ovc-vcard__cuerpo">
						<span class="ovc-vcard__rotulo"><?php echo esc_html( $t['rotulo'] ); ?></span>
						<span class="ovc-vcard__valor"><?php echo esc_html( $t['valor'] ); ?></span>
					</span>
				</<?php echo esc_attr( $etiqueta ); ?>>

				<?php if ( ! empty( $t['copiar'] ) ) : ?>
					<?php // Fuera de la tarjeta: un botón dentro de un enlace no es HTML válido. ?>
					<button type="button" class="ovc-vcard__segundo"
					        data-ovc-copiar="<?php echo esc_attr( $t['copiar'] ); ?>">
						<?php esc_html_e( 'Copiar número', 'ov-child' ); ?>
					</button>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>

		<ul class="ovc-contacto__notas">
			<li><?php echo esc_html( $d['respuesta'] ); ?></li>
			<li><?php echo esc_html( $d['modalidad'] ); ?></li>
			<li><?php esc_html_e( 'Lo que me cuente queda entre nosotros', 'ov-child' ); ?></li>
		</ul>

	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ovc_contacto', 'ovc_shortcode_contacto' );

/**
 * El botón de copiar el número.
 *
 * Se registra con el script de las animaciones ya encolado para no añadir otra
 * petición por una función de diez líneas.
 *
 * navigator.clipboard solo existe en conexiones seguras. En local, que va por
 * http, no está, así que hay un plan B con un campo de texto oculto: sin él, el
 * botón no haría nada al probarlo aquí y parecería roto.
 */
function ovc_contacto_script() {
	if ( ! is_page() ) {
		return;
	}

	wp_register_script( 'ovc-contacto', '', array(), OVC_VERSION, true );
	wp_enqueue_script( 'ovc-contacto' );
	wp_add_inline_script(
		'ovc-contacto',
		"(function(){
	function avisar(boton, texto) {
		var antes = boton.textContent;
		boton.textContent = texto;
		boton.classList.add('is-hecho');
		window.setTimeout(function () {
			boton.textContent = antes;
			boton.classList.remove('is-hecho');
		}, 1800);
	}

	function copiarViejo(texto) {
		var campo = document.createElement('textarea');
		campo.value = texto;
		campo.setAttribute('readonly', '');
		campo.style.position = 'fixed';
		campo.style.opacity = '0';
		document.body.appendChild(campo);
		campo.select();
		var bien = false;
		try { bien = document.execCommand('copy'); } catch (e) { bien = false; }
		document.body.removeChild(campo);
		return bien;
	}

	document.addEventListener('click', function (e) {
		var boton = e.target.closest('[data-ovc-copiar]');
		if (!boton) { return; }
		var texto = boton.getAttribute('data-ovc-copiar');

		if (navigator.clipboard && window.isSecureContext) {
			navigator.clipboard.writeText(texto).then(
				function () { avisar(boton, 'Copiado'); },
				function () { avisar(boton, copiarViejo(texto) ? 'Copiado' : 'No se pudo'); }
			);
			return;
		}

		avisar(boton, copiarViejo(texto) ? 'Copiado' : 'No se pudo');
	});
})();"
	);
}
add_action( 'wp_enqueue_scripts', 'ovc_contacto_script' );
