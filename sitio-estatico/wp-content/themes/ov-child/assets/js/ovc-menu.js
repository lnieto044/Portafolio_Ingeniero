/**
 * Velo y bloqueo de desplazamiento para el menú deslizante.
 *
 * Divi abre el panel encima de la página y no hace nada más: el contenido
 * sigue a la vista por debajo, medio tapado, y se puede seguir desplazando
 * detrás del menú. El resultado es que no se sabe si el menú está abierto o si
 * la página se rompió.
 *
 * Aquí se añaden las tres cosas que se esperan de un panel lateral:
 *
 *   - un velo oscuro que apaga la página y deja claro dónde hay que mirar,
 *   - cerrar al pulsar fuera o con la tecla Escape,
 *   - y que la página de detrás no se mueva mientras el menú está abierto.
 *
 * Todo cuelga de la clase que pone el propio Divi, así que si algún día se
 * cambia el estilo de cabecera desde el escritorio, esto deja de aplicarse
 * solo en vez de estorbar.
 */
( function () {
	'use strict';

	var CLASE_ABIERTO = 'et_pb_slide_menu_active';

	/*
	 * Solo con el estilo de cabecera «panel lateral». La cabecera de este sitio
	 * pasó a menú horizontal, pero Divi sigue dejando el marcado del panel en
	 * la página: sin esta comprobación se le añadiría un botón de cerrar a un
	 * panel que nadie va a abrir.
	 */
	if ( ! document.body.classList.contains( 'et_header_style_slide' ) ) {
		return;
	}

	var panel = document.querySelector( '.et_slide_in_menu_container' );
	if ( ! panel ) {
		return;
	}

	var velo = document.createElement( 'div' );
	velo.className = 'ovc-velo';
	velo.setAttribute( 'aria-hidden', 'true' );
	document.body.appendChild( velo );

	/*
	 * Un boton de cerrar DENTRO del panel.
	 *
	 * El aspa de Divi vive en la cabecera, y segun el ancho de la pantalla el
	 * propio panel se le pone encima: queda un menu abierto sin forma visible
	 * de cerrarlo. Pulsar fuera y la tecla Escape ya funcionan, pero la gente
	 * busca el aspa, y si no la ve da por hecho que no la hay.
	 */
	var cerrarBtn = document.createElement( 'button' );
	cerrarBtn.type = 'button';
	cerrarBtn.className = 'ovc-menu-cerrar';
	cerrarBtn.setAttribute( 'aria-label', 'Cerrar el menú' );
	cerrarBtn.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor"'
		+ ' stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>';

	var franja = panel.querySelector( '.et_slide_menu_top' ) || panel;
	franja.appendChild( cerrarBtn );

	cerrarBtn.addEventListener( 'click', function () {
		cerrar();
	} );

	var desplazamiento = 0;

	function estaAbierto() {
		return document.body.classList.contains( CLASE_ABIERTO );
	}

	function cerrar() {
		var boton = document.querySelector( '.et_toggle_slide_menu' );
		if ( boton ) {
			boton.click(); // Se deja cerrar a Divi, para no descuadrar su estado.
		}
	}

	function bloquear() {
		desplazamiento = window.pageYOffset || document.documentElement.scrollTop;
		/*
		 * Fijar el body y compensar con top es la única forma que respetan los
		 * navegadores móviles: con overflow:hidden a secas, Safari sigue
		 * desplazando la página por detrás del panel.
		 */
		document.body.style.position = 'fixed';
		document.body.style.top      = ( -desplazamiento ) + 'px';
		document.body.style.width    = '100%';
	}

	function soltar() {
		document.body.style.position = '';
		document.body.style.top      = '';
		document.body.style.width    = '';
		window.scrollTo( 0, desplazamiento );
	}

	// Divi no avisa de nada, así que se vigila el atributo class del body.
	var vigia = new MutationObserver( function () {
		var abierto = estaAbierto();

		if ( abierto && ! velo.classList.contains( 'is-visible' ) ) {
			velo.classList.add( 'is-visible' );
			bloquear();
		} else if ( ! abierto && velo.classList.contains( 'is-visible' ) ) {
			velo.classList.remove( 'is-visible' );
			soltar();
		}
	} );

	vigia.observe( document.body, { attributes: true, attributeFilter: [ 'class' ] } );

	velo.addEventListener( 'click', function () {
		if ( estaAbierto() ) {
			cerrar();
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key && estaAbierto() ) {
			cerrar();
		}
	} );

	// Al pulsar un enlace del menú, cerrarlo: si no, el panel se queda abierto
	// encima de la página nueva hasta que se recarga.
	panel.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( 'a[href]' ) ) {
			soltar(); // Se suelta ya, para que la página nueva cargue en su sitio.
		}
	} );
} )();
