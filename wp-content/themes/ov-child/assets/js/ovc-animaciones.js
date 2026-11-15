/**
 * Aparición al desplazar, contadores y barra de progreso.
 *
 * Todo es mejora progresiva: el estado oculto lo pone este archivo, nunca el
 * CSS. Si el JavaScript falla o no llega a cargar, el contenido se ve igual en
 * lugar de quedarse invisible para siempre, que es el fallo clásico de este
 * tipo de efecto.
 *
 * Se respeta prefers-reduced-motion: quien pida menos movimiento en su sistema
 * ve la página entera de golpe, sin animación.
 */
( function () {
	'use strict';

	var quieto = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	if ( ! ( 'IntersectionObserver' in window ) || quieto ) {
		return;
	}

	/* ------------------------------------------------------------------
	 * Qué se anima y cómo
	 *
	 * Cada entrada es un selector y la variante con la que entra. El orden
	 * importa: un elemento solo se apunta una vez, en la primera regla que lo
	 * recoja, para que una tarjeta dentro de una sección no acabe entrando dos
	 * veces con dos movimientos distintos.
	 *
	 *   arriba  sube desde abajo          (lo general)
	 *   izq     entra desde la izquierda  (columnas de texto)
	 *   der     entra desde la derecha    (columnas de apoyo)
	 *   zoom    crece un poco             (tarjetas y fichas)
	 *   raya    se dibuja de cero a su ancho (los subrayados azules)
	 * --------------------------------------------------------------- */
	var reglas = [
		// Los subrayados azules de cada sección se dibujan solos.
		[ '.ovc-aficiones__raya, .ovc-habilidades__raya', 'raya' ],

		// Titulares de sección.
		[ '.ovc-aficiones__titulo, .ovc-habilidades__titulo, .ovc-contacto__titulo', 'arriba' ],

		// Tarjetas: crecen un pelo al aparecer.
		[ '.ovc-cifra, .ovc-servicio, .ovc-proyecto, .ovc-aficion, .ovc-hab, .ovc-ficha, .ovc-idioma', 'zoom' ],

		// Contacto: los datos por un lado, el formulario por el otro.
		[ '.ovc-vcard, .ovc-contacto__bajada, .ovc-contacto__notas', 'izq' ],
		[ '.ovc-formulario', 'der' ],

		// Currículo: cada hito de la línea de tiempo entra lateralmente.
		[ '.ovc-cv__item', 'izq' ],

		// Página de un proyecto.
		[ '.ovc-proyecto-single__meta', 'der' ],
		[ '.ovc-proyecto-single__principal, .ovc-proyecto-single__imagen', 'arriba' ],

		// Y por último lo que pinta Divi y no tiene clase propia: imagen,
		// galería o resumen que no lleven ya animación suya.
		[ '.et_pb_image, .et_pb_gallery, .et_pb_blurb, .et_pb_divider', 'arriba' ]
	];


	var bloques = [];

	function yaApuntado( el ) {
		return el.classList.contains( 'ovc-revelar' );
	}

	reglas.forEach( function ( regla ) {
		var lista;

		try {
			lista = document.querySelectorAll( regla[0] );
		} catch ( e ) {
			return; // Un selector que el navegador no entienda no tumba el resto.
		}

		Array.prototype.forEach.call( lista, function ( el ) {
			if ( yaApuntado( el ) ) {
				return;
			}

			/*
			 * Divi trae animaciones propias en los módulos que se maquetaron en
			 * 2020, y las marca con et_animated o et-waypoint. Esos se dejan en
			 * paz: dos efectos sobre el mismo elemento se pelean por la misma
			 * propiedad y el resultado es un parpadeo.
			 */
			var clases = String( el.className );
			if ( clases.indexOf( 'et_animated' ) !== -1
				|| clases.indexOf( 'et-waypoint' ) !== -1
				|| clases.indexOf( 'et_pb_animation_' ) !== -1
				|| el.getAttribute( 'data-animation-style' ) ) {
				return;
			}

			/*
			 * Si un antepasado ya se anima, este no. Si no, la tarjeta entra
			 * dentro de una sección que a su vez está entrando y el conjunto se
			 * ve como un temblor.
			 */
			var padre = el.parentElement;
			while ( padre && padre !== document.body ) {
				if ( yaApuntado( padre ) ) {
					return;
				}
				padre = padre.parentElement;
			}

			el.classList.add( 'ovc-revelar', 'ovc-revelar--' + regla[1] );
			bloques.push( el );
		} );
	} );

	/*
	 * Los párrafos entran uno detrás de otro, no el bloque entero de golpe.
	 *
	 * Un módulo de texto con cuatro párrafos apareciendo a la vez se lee como
	 * un cartel que se enciende. Encadenándolos, el ojo sigue la lectura en el
	 * mismo orden en que va a leerlos, y el conjunto se percibe compuesto en
	 * vez de volcado.
	 *
	 * El retraso lo calcula el escalonado común de más abajo, el mismo que usan
	 * las tarjetas: un solo mecanismo en vez de dos que puedan discrepar.
	 */
	var parrafos = [];

	Array.prototype.forEach.call(
		document.querySelectorAll( '.et_pb_text, .ovc-cv__texto, .ovc-proyecto-single__texto' ),
		function ( bloque ) {
			var clases = String( bloque.className );

			// Los que Divi ya anima se dejan en paz, como el resto.
			if ( clases.indexOf( 'et_animated' ) !== -1 || clases.indexOf( 'et-waypoint' ) !== -1 ) {
				return;
			}

			/*
			 * Divi no deja los párrafos sueltos dentro del módulo: los mete en
			 * un .et_pb_text_inner. Sin bajar a esa caja, el selector no
			 * encuentra nada y acaba animando el bloque entero, que es justo
			 * lo que se quería evitar.
			 */
			var caja  = bloque.querySelector( ':scope > .et_pb_text_inner' ) || bloque;
			var hijos = caja.querySelectorAll( ':scope > p, :scope > h1, :scope > h2, :scope > h3, :scope > h4, :scope > ul, :scope > ol' );

			if ( ! hijos.length ) {
				// Sin hijos reconocibles se anima el bloque, como antes.
				parrafos.push( bloque );
				return;
			}

			Array.prototype.forEach.call( hijos, function ( hijo ) {
				parrafos.push( hijo );
			} );
		}
	);

	parrafos.forEach( function ( el ) {
		if ( yaApuntado( el ) ) {
			return;
		}

		/*
		 * Si la ficha que lo contiene ya se anima, el párrafo no.
		 *
		 * Las entradas del currículo entran enteras desde un lado; animar
		 * además su texto por dentro suma dos movimientos sobre el mismo
		 * contenido y se ve como un temblor. Es la misma regla que aplica el
		 * bucle principal, y aquí faltaba.
		 */
		var padre = el.parentElement;
		while ( padre && padre !== document.body ) {
			if ( yaApuntado( padre ) ) {
				return;
			}
			padre = padre.parentElement;
		}

		el.classList.add( 'ovc-revelar', 'ovc-revelar--texto' );
		bloques.push( el );
	} );

	/*
	 * Escalonado por filas.
	 *
	 * El retraso sale de la posición del elemento dentro de su padre, no de su
	 * orden en la lista global: así los cuatro de una fila entran uno detrás de
	 * otro, pero la fila de abajo vuelve a empezar en cero en vez de acumular
	 * medio segundo de espera.
	 */
	bloques.forEach( function ( el ) {
		var puesto = el.parentElement
			? Array.prototype.indexOf.call( el.parentElement.children, el )
			: 0;
		el.style.transitionDelay = ( ( puesto % 4 ) * 80 ) + 'ms';
	} );

	var ojo = new IntersectionObserver(
		function ( entradas ) {
			entradas.forEach( function ( e ) {
				if ( ! e.isIntersecting ) {
					return;
				}
				e.target.classList.add( 'ovc-visible' );
				ojo.unobserve( e.target ); // Una vez visible, se deja en paz.
			} );
		},
		{ rootMargin: '0px 0px -10% 0px', threshold: 0.06 }
	);

	bloques.forEach( function ( el ) {
		ojo.observe( el );
	} );

	/*
	 * Red de seguridad.
	 *
	 * IntersectionObserver depende de que el navegador componga fotogramas. Si
	 * no llega a dispararse -una pestaña en segundo plano, el renderizado
	 * pausado, o un fallo-, los bloques se quedarían en opacidad cero para
	 * siempre y el visitante vería media página en blanco.
	 *
	 * La versión anterior resolvía eso revelándolo TODO a los dos segundos, y
	 * con ello se cargaba el efecto entero: en una página de dieciséis mil
	 * píxeles, a los dos segundos quedaba mostrado lo que estaba a diez mil de
	 * distancia, de modo que al bajar ya no se animaba nada. Medido: 1 de 81
	 * bloques visibles a los 1,5 s, y 81 de 81 a los 3 s.
	 *
	 * Ahora se comprueba primero si el observador está vivo. Si reveló aunque
	 * sea un bloque, funciona: se le deja trabajar y los demás entran cuando
	 * les toque. Solo si no reveló ninguno se da por roto y se muestra todo.
	 */
	window.setTimeout( function () {
		var algunoVisible = bloques.some( function ( el ) {
			return el.classList.contains( 'ovc-visible' );
		} );

		if ( algunoVisible ) {
			return; // El observador responde: no hay nada que rescatar.
		}

		bloques.forEach( function ( el ) {
			el.style.transitionDelay = '0ms';
			el.classList.add( 'ovc-visible' );
		} );
	}, 2000 );

	/*
	 * Segunda red, esta sí por elemento.
	 *
	 * Cubre el caso raro de que el observador funcione para unos bloques y se
	 * pierda otros: al desplazar, cualquier bloque que ya esté dentro de la
	 * ventana y siga oculto se revela. No sustituye al observador -se nota
	 * menos fino-, pero garantiza que ningún contenido quede invisible.
	 *
	 * Se ejecuta como mucho una vez por fotograma; sin eso, el navegador
	 * recalcula posiciones en cada píxel de desplazamiento.
	 */
	var revisando = false;

	function rescatarVisibles() {
		revisando = false;
		var alto = window.innerHeight;

		for ( var i = bloques.length - 1; i >= 0; i-- ) {
			var el = bloques[ i ];

			if ( el.classList.contains( 'ovc-visible' ) ) {
				bloques.splice( i, 1 ); // Ya está: fuera de la lista.
				continue;
			}

			var r = el.getBoundingClientRect();
			if ( r.top < alto && r.bottom > 0 ) {
				el.classList.add( 'ovc-visible' );
			}
		}
	}

	window.addEventListener(
		'scroll',
		function () {
			if ( ! revisando ) {
				revisando = true;
				window.requestAnimationFrame( rescatarVisibles );
			}
		},
		{ passive: true }
	);

	/* ------------------------------------------------------------------
	 * Contadores
	 * --------------------------------------------------------------- */
	function contar( el ) {
		var destino = parseInt( el.getAttribute( 'data-contar' ), 10 );
		if ( isNaN( destino ) ) {
			return;
		}

		var duracion = 1100;
		var inicio   = null;

		function paso( ahora ) {
			if ( inicio === null ) {
				inicio = ahora;
			}
			var t = Math.min( ( ahora - inicio ) / duracion, 1 );
			// Desaceleración: arranca rápido y frena al final.
			var suave = 1 - Math.pow( 1 - t, 3 );
			el.textContent = String( Math.round( destino * suave ) );

			if ( t < 1 ) {
				window.requestAnimationFrame( paso );
			} else {
				el.textContent = String( destino );
			}
		}

		window.requestAnimationFrame( paso );
	}

	var numeros = document.querySelectorAll( '[data-contar]' );

	var ojoNumeros = new IntersectionObserver(
		function ( entradas ) {
			entradas.forEach( function ( e ) {
				if ( ! e.isIntersecting ) {
					return;
				}
				e.target.dataset.ovcContado = '1';
				contar( e.target );
				ojoNumeros.unobserve( e.target );
			} );
		},
		{ threshold: 0.5 }
	);

	Array.prototype.forEach.call( numeros, function ( el ) {
		ojoNumeros.observe( el );
	} );

	// Misma red para los contadores: si no se animan, al menos se quedan con su
	// valor real, que ya está escrito en el HTML.
	window.setTimeout( function () {
		Array.prototype.forEach.call( numeros, function ( el ) {
			if ( ! el.dataset.ovcContado ) {
				el.dataset.ovcContado = '1';
				contar( el );
			}
		} );
	}, 2500 );

	/* ------------------------------------------------------------------
	 * Barra de progreso de lectura
	 *
	 * Una línea de tres píxeles arriba del todo que se llena según se baja. En
	 * páginas largas -Acerca pasa de trece mil píxeles- le dice al visitante
	 * cuánto le queda, que es la diferencia entre seguir bajando y cerrar la
	 * pestaña.
	 * --------------------------------------------------------------- */
	var barra = document.createElement( 'div' );
	barra.className = 'ovc-progreso';
	barra.setAttribute( 'aria-hidden', 'true' );
	document.body.appendChild( barra );

	var pendiente = false;

	function pintarProgreso() {
		var alto = document.documentElement.scrollHeight - window.innerHeight;
		var y    = window.pageYOffset || document.documentElement.scrollTop;
		barra.style.transform = 'scaleX(' + ( alto > 0 ? Math.min( y / alto, 1 ) : 0 ) + ')';
		pendiente = false;
	}

	/*
	 * Se pinta como mucho una vez por fotograma. Sin esto el navegador rehace
	 * el cálculo en cada píxel de desplazamiento y la página se arrastra en los
	 * móviles, que es justo lo contrario de lo que se busca.
	 */
	window.addEventListener(
		'scroll',
		function () {
			if ( ! pendiente ) {
				pendiente = true;
				window.requestAnimationFrame( pintarProgreso );
			}
		},
		{ passive: true }
	);

	pintarProgreso();
} )();
