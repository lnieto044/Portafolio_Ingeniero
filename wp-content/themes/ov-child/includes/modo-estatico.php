<?php
/**
 * Modo estatico: lo que cambia al generar la copia HTML que se publica en Render.
 *
 * herramientas/exportar-estatico.py pide cada pagina con la cabecera
 * «X-OVC-Estatico: 1». Solo entonces, y solo si la peticion sale de esta misma
 * maquina, se adapta lo que en un sitio sin PHP no funcionaria:
 *
 *   - El formulario de contacto. Se queda igual a la vista, pero en vez de
 *     enviarse a WordPress se envia a FormSubmit (https://formsubmit.co), un
 *     servicio gratuito que reenvia el mensaje al correo de ovc_datos_contacto().
 *     El captcha propio necesita PHP para comprobar la respuesta, asi que aqui
 *     lo sustituye un campo trampa invisible (los robots lo rellenan, las
 *     personas no lo ven).
 *   - El buscador de la cabecera (va a /?s=, que en estatico no existe).
 *   - Los enlaces a la API REST, oEmbed, RSD y feeds que WordPress pone en <head>.
 *
 * Visitando el sitio normalmente no cambia nada.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Si esta peticion es del exportador.
 *
 * @return bool
 */
function ovc_modo_estatico() {
	static $activo = null;

	if ( null === $activo ) {
		$ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
		$activo = ! empty( $_SERVER['HTTP_X_OVC_ESTATICO'] )
			&& in_array( $ip, array( '127.0.0.1', '::1' ), true );
	}

	return $activo;
}

if ( ! ovc_modo_estatico() ) {
	return;
}

/**
 * A donde se envia el formulario en la version estatica.
 *
 * Tras activar FormSubmit, su correo de bienvenida da una direccion aleatoria
 * que oculta el correo real; para usarla, cambiala aqui o con el filtro.
 *
 * @return string
 */
function ovc_formulario_endpoint() {
	return apply_filters(
		'ovc_formulario_endpoint',
		'https://formsubmit.co/ajax/' . ovc_datos_contacto()['correo']
	);
}

/* ---- Formulario: mismo aspecto, envio por FormSubmit --------------------- */

// El captcha propio se valida en PHP: aqui no sirve.
remove_filter( 'do_shortcode_tag', 'ovc_captcha_inyectar', 10 );
remove_action( 'wp_enqueue_scripts', 'ovc_captcha_script' );

add_filter(
	'do_shortcode_tag',
	function ( $salida, $tag ) {
		if ( 'et_pb_contact_form' !== $tag ) {
			return $salida;
		}

		// Marca el formulario con su destino.
		$salida = preg_replace(
			'/<form class="et_pb_contact_form/',
			'<form data-ovc-envio="' . esc_url( ovc_formulario_endpoint() ) . '" class="et_pb_contact_form',
			$salida,
			1
		);

		// Campo trampa antes del boton de enviar.
		$trampa = '<p class="ovc-trampa" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">'
			. '<label>No rellenar <input type="text" name="_honey" tabindex="-1" autocomplete="off"></label></p>';

		return preg_replace( '/(<div class="et_contact_bottom_container)/', $trampa . '$1', $salida, 1 );
	},
	20,
	2
);

add_action(
	'wp_footer',
	function () {
		$correo = ovc_datos_contacto()['correo'];
		?>
<script id="ovc-formulario-estatico">
(function () {
	var CORREO = <?php echo wp_json_encode( $correo ); ?>;
	var EMAIL_OK = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

	function aviso(form, html) {
		var caja = form.closest('.et_pb_contact_form_container');
		var msg = caja ? caja.querySelector('.et-pb-contact-message') : null;
		if (msg) { msg.innerHTML = html; }
	}

	/*
	 * Fase de captura en document: se ejecuta ANTES que el manejador de Divi,
	 * que esta en el propio <form>, y lo detiene. Sin esto Divi intentaria
	 * enviar por AJAX a la misma pagina, que en un sitio estatico no responde.
	 */
	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form || !form.matches || !form.matches('form[data-ovc-envio]')) { return; }
		e.preventDefault();
		e.stopImmediatePropagation();

		if (form.getAttribute('data-ovc-enviando')) { return; }

		var campos = form.querySelectorAll('[data-original_id]');
		var datos = {};
		var faltan = [];
		campos.forEach(function (c) {
			var v = (c.value || '').trim();
			var id = c.getAttribute('data-original_id');
			var mal = ('required' === c.getAttribute('data-required_mark') && '' === v)
				|| ('email' === c.getAttribute('data-field_type') && '' !== v && !EMAIL_OK.test(v));
			c.classList.toggle('et_contact_error', mal);
			if (mal) { faltan.push(c.getAttribute('placeholder') || id); }
			datos[id] = v;
		});

		if (faltan.length) {
			aviso(form, '<p>Revise estos campos:</p><ul><li>' + faltan.join('</li><li>') + '</li></ul>');
			return;
		}

		var trampa = form.querySelector('[name="_honey"]');
		if (trampa && trampa.value) { return; }

		var boton = form.querySelector('.et_pb_contact_submit');
		var textoBoton = boton ? boton.textContent : '';
		form.setAttribute('data-ovc-enviando', '1');
		if (boton) { boton.disabled = true; boton.textContent = 'Enviando…'; }
		aviso(form, '');

		fetch(form.getAttribute('data-ovc-envio'), {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
			body: JSON.stringify({
				nombre: datos.name || '',
				email: datos.email || '',
				mensaje: datos.message || '',
				_subject: 'Nuevo mensaje desde el portafolio',
				_template: 'table',
				_captcha: 'false'
			})
		})
			.then(function (r) { return r.json(); })
			.then(function (r) {
				if ('true' !== String(r.success)) { throw new Error(r.message || 'sin exito'); }
				form.reset();
				form.style.display = 'none';
				aviso(form, '<p>Gracias por escribir. Le respondo en menos de 24 horas hábiles.</p>');
			})
			.catch(function () {
				aviso(form, '<p>No se pudo enviar el mensaje. Escríbame directamente a <a href="mailto:' + CORREO + '">' + CORREO + '</a>.</p>');
			})
			.then(function () {
				form.removeAttribute('data-ovc-enviando');
				if (boton) { boton.disabled = false; boton.textContent = textoBoton; }
			});
	}, true);
})();
</script>
		<?php
	},
	99
);

/* ---- Buscador de la cabecera --------------------------------------------- */

add_action(
	'wp_head',
	function () {
		echo "<style id=\"ovc-estatico\">#et_top_search,.et_search_outer{display:none!important}</style>\n";
	},
	99
);

/* ---- Enlaces de <head> que apuntan a PHP --------------------------------- */

remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );
