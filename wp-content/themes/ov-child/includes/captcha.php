<?php
/**
 * Captcha de verdad para el formulario de contacto.
 *
 * El que trae Divi es decorativo. Escribe los dos sumandos en el propio HTML
 * -data-first_digit y data-second_digit sobre el campo- y la suma la comprueba
 * su JavaScript. Del lado del servidor, Divi solo mira que el campo no esté
 * vacío:
 *
 *     if ( 'on' === $captcha && ( ! isset( $_POST[...] ) || empty( $_POST[...] ) ) )
 *
 * Es decir: un programa que envíe el formulario directamente, sin abrir la
 * página, pasa poniendo cualquier cosa. Y uno que sí la abra tiene la
 * respuesta escrita delante.
 *
 * Aquí la pregunta se genera en el servidor, la respuesta se guarda en un
 * transitorio con un identificador de un solo uso, y al HTML solo baja ese
 * identificador. Sin la respuesta en la página no hay nada que leer, y sin
 * pasar por el servidor no hay forma de acertar.
 *
 * Divi no ofrece ningún punto donde engancharse antes de enviar el correo -su
 * única acción, et_pb_contact_form_submit, salta después-, así que la
 * comprobación se hace en template_redirect: si falla, se retira de $_POST la
 * clave con la que Divi reconoce el envío y el formulario nunca llega a
 * procesarse.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cuánto vive una pregunta sin usar.
 */
const OVC_CAPTCHA_VIDA = 30 * MINUTE_IN_SECONDS;

/**
 * Los números en letra.
 *
 * Escritos con palabras y no con cifras: un programa que rastrea la página
 * buscando dos números y un signo de sumar no encuentra nada que sumar.
 *
 * @return array
 */
function ovc_captcha_palabras() {
	return array(
		1 => __( 'uno', 'ov-child' ),
		2 => __( 'dos', 'ov-child' ),
		3 => __( 'tres', 'ov-child' ),
		4 => __( 'cuatro', 'ov-child' ),
		5 => __( 'cinco', 'ov-child' ),
		6 => __( 'seis', 'ov-child' ),
		7 => __( 'siete', 'ov-child' ),
		8 => __( 'ocho', 'ov-child' ),
		9 => __( 'nueve', 'ov-child' ),
	);
}

/**
 * Crea una pregunta nueva y guarda su respuesta.
 *
 * @return array {
 *     @type string $token    Identificador de un solo uso.
 *     @type string $pregunta Texto que se le enseña a la persona.
 * }
 */
function ovc_captcha_nueva() {
	$palabras = ovc_captcha_palabras();

	$a = wp_rand( 1, 9 );
	$b = wp_rand( 1, 9 );

	// Sumar o restar, y la resta nunca da negativo.
	$resta = (bool) wp_rand( 0, 1 ) && $a > $b;

	if ( $resta ) {
		$respuesta = $a - $b;
		/* translators: 1: primer número en letra, 2: segundo número en letra. */
		$pregunta = sprintf( __( '%1$s menos %2$s', 'ov-child' ), $palabras[ $a ], $palabras[ $b ] );
	} else {
		$respuesta = $a + $b;
		/* translators: 1: primer número en letra, 2: segundo número en letra. */
		$pregunta = sprintf( __( '%1$s más %2$s', 'ov-child' ), $palabras[ $a ], $palabras[ $b ] );
	}

	$token = wp_generate_password( 24, false, false );
	set_transient( 'ovc_captcha_' . $token, (string) $respuesta, OVC_CAPTCHA_VIDA );

	return array(
		'token'    => $token,
		'pregunta' => $pregunta,
	);
}

/**
 * Comprueba una respuesta y quema el identificador.
 *
 * Se borra el transitorio tanto si acierta como si falla: así una misma
 * pregunta no se puede reintentar a fuerza de repetir envíos hasta dar con el
 * número, que con respuestas de una cifra sería cuestión de segundos.
 *
 * @param string $token     Identificador.
 * @param string $respuesta Lo que ha escrito la persona.
 * @return bool
 */
function ovc_captcha_comprobar( $token, $respuesta ) {
	$token = preg_replace( '/[^A-Za-z0-9]/', '', (string) $token );

	if ( '' === $token ) {
		return false;
	}

	$esperada = get_transient( 'ovc_captcha_' . $token );
	delete_transient( 'ovc_captcha_' . $token );

	if ( false === $esperada ) {
		return false; // Caducada, ya usada, o inventada.
	}

	return (string) $esperada === trim( (string) $respuesta );
}

/**
 * Mensaje de error que hay que enseñar en esta petición, si lo hay.
 *
 * @param string|null $poner Texto a guardar.
 * @return string
 */
function ovc_captcha_error( $poner = null ) {
	static $mensaje = '';

	if ( null !== $poner ) {
		$mensaje = (string) $poner;
	}

	return $mensaje;
}

/**
 * Intercepta el envío antes de que Divi lo procese.
 */
function ovc_captcha_interceptar() {
	if ( empty( $_POST ) ) {
		return;
	}

	// ¿Es un envío del formulario de contacto de Divi?
	$numero = null;
	foreach ( array_keys( $_POST ) as $clave ) {
		if ( 0 === strpos( $clave, 'et_pb_contactform_submit_' ) ) {
			$numero = substr( $clave, strlen( 'et_pb_contactform_submit_' ) );
			break;
		}
	}

	if ( null === $numero ) {
		return;
	}

	// El nonce lo valida Divi igualmente; aquí solo se mira el captcha.
	$token     = isset( $_POST['ovc_captcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['ovc_captcha_token'] ) ) : '';
	$respuesta = isset( $_POST['ovc_captcha_respuesta'] ) ? sanitize_text_field( wp_unslash( $_POST['ovc_captcha_respuesta'] ) ) : '';

	if ( ovc_captcha_comprobar( $token, $respuesta ) ) {
		return; // Adelante: Divi sigue con lo suyo.
	}

	/*
	 * Al quitar esta clave, la condición con la que Divi reconoce el envío
	 * deja de cumplirse y el formulario no se procesa: ni se valida, ni se
	 * envía correo. El resto de $_POST se deja intacto para que los campos
	 * mantengan lo que la persona ya había escrito.
	 */
	unset( $_POST[ 'et_pb_contactform_submit_' . $numero ] );

	ovc_captcha_error(
		'' === $respuesta
			? __( 'Falta la comprobación de seguridad.', 'ov-child' )
			: __( 'La comprobación de seguridad no es correcta. Aquí tiene otra.', 'ov-child' )
	);
}
add_action( 'template_redirect', 'ovc_captcha_interceptar', 1 );

/**
 * Mete el campo en el formulario que pinta Divi.
 *
 * Se filtra la salida del shortcode en lugar de tocar la plantilla de Divi:
 * así una actualización del tema padre no se lleva esto por delante.
 *
 * @param string $salida Lo que ha devuelto el shortcode.
 * @param string $tag    Nombre del shortcode.
 * @return string
 */
function ovc_captcha_inyectar( $salida, $tag ) {
	if ( 'et_pb_contact_form' !== $tag || false === strpos( $salida, 'et_pb_contact_submit' ) ) {
		return $salida;
	}

	$c = ovc_captcha_nueva();

	ob_start();
	?>
	<div class="ovc-captcha">
		<?php if ( ovc_captcha_error() ) : ?>
			<p class="ovc-captcha__error" role="alert"><?php echo esc_html( ovc_captcha_error() ); ?></p>
		<?php endif; ?>

		<label class="ovc-captcha__etiqueta" for="ovc-captcha-respuesta">
			<?php esc_html_e( 'Para comprobar que no es un programa:', 'ov-child' ); ?>
		</label>

		<div class="ovc-captcha__fila">
			<span class="ovc-captcha__pregunta" data-ovc-pregunta>
				<?php echo esc_html( $c['pregunta'] ); ?>
			</span>
			<span class="ovc-captcha__igual" aria-hidden="true">=</span>
			<input type="text" id="ovc-captcha-respuesta" name="ovc_captcha_respuesta"
			       class="ovc-captcha__campo" inputmode="numeric" autocomplete="off"
			       maxlength="2" required
			       aria-label="<?php esc_attr_e( 'Resultado de la operación', 'ov-child' ); ?>" />
			<input type="hidden" name="ovc_captcha_token" value="<?php echo esc_attr( $c['token'] ); ?>" data-ovc-token />
			<button type="button" class="ovc-captcha__otra" data-ovc-otra
			        aria-label="<?php esc_attr_e( 'Cambiar la pregunta', 'ov-child' ); ?>">
				<?php esc_html_e( 'Otra', 'ov-child' ); ?>
			</button>
		</div>
	</div>
	<?php
	$campo = ob_get_clean();

	// Justo antes del contenedor del botón de enviar.
	$ancla = '<div class="et_contact_bottom_container">';
	if ( false !== strpos( $salida, $ancla ) ) {
		return str_replace( $ancla, $campo . $ancla, $salida );
	}

	return $salida . $campo;
}
add_filter( 'do_shortcode_tag', 'ovc_captcha_inyectar', 10, 2 );

/**
 * Devuelve una pregunta nueva sin recargar la página.
 */
function ovc_captcha_ajax() {
	$c = ovc_captcha_nueva();
	wp_send_json_success( $c );
}
add_action( 'wp_ajax_ovc_captcha', 'ovc_captcha_ajax' );
add_action( 'wp_ajax_nopriv_ovc_captcha', 'ovc_captcha_ajax' );

/**
 * El script del botón «Otra».
 */
function ovc_captcha_script() {
	if ( ! is_page() ) {
		return;
	}

	wp_register_script( 'ovc-captcha', '', array(), OVC_VERSION, true );
	wp_enqueue_script( 'ovc-captcha' );
	wp_add_inline_script(
		'ovc-captcha',
		"(function(){
	var url = " . wp_json_encode( admin_url( 'admin-ajax.php' ) ) . ";
	document.addEventListener('click', function (e) {
		var boton = e.target.closest('[data-ovc-otra]');
		if (!boton) { return; }
		var caja = boton.closest('.ovc-captcha');
		boton.disabled = true;
		fetch(url + '?action=ovc_captcha', { credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (d) {
				if (!d || !d.success) { return; }
				caja.querySelector('[data-ovc-pregunta]').textContent = d.data.pregunta;
				caja.querySelector('[data-ovc-token]').value = d.data.token;
				var campo = caja.querySelector('.ovc-captcha__campo');
				campo.value = '';
				campo.focus();
			})
			.catch(function () {})
			.then(function () { boton.disabled = false; });
	});
})();"
	);
}
add_action( 'wp_enqueue_scripts', 'ovc_captcha_script' );
