<?php
/**
 * Iconos sociales de la cabecera y el pie.
 *
 * Este archivo sustituye a Divi/includes/social_icons.php. La version anterior
 * era una copia del archivo del padre de 2019 y arrastraba tres problemas:
 *
 *   1. Mostraba Google+, una red cerrada en abril de 2019.
 *   2. No tenia el icono de Instagram que el padre ya incluye.
 *   3. Pasaba el resultado de et_get_option() directo a esc_url(). Cuando la
 *      opcion no esta guardada devuelve null, y en PHP 8.1 eso llena el
 *      debug.log de "Passing null to parameter of type string is deprecated".
 *      El padre lo resolvio envolviendolo en strval(); aqui se hace igual.
 *
 * Ademas se anaden LinkedIn, GitHub y WhatsApp, que son los enlaces que tienen
 * sentido en un portafolio de desarrollo y que Divi no trae como opcion.
 *
 * @package OV_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redes que Divi no gestiona desde Opciones del tema.
 *
 * Rellena aqui las URL COMPLETAS, hasta el nombre de usuario. La que dejes
 * vacia no se pinta: es preferible que falte el icono a que este y lleve a la
 * portada generica de la red, que es lo que pasa si se deja el dominio suelto.
 * Tambien se puede cambiar desde otro sitio con el filtro 'ovc_redes_extra'.
 *
 * Sobre los iconos: Divi los pinta con un glifo de su fuente ETmodules. Para
 * GitHub eso no sirve -ETmodules no tiene ese glifo- y salia un hueco vacio
 * entre LinkedIn y WhatsApp. El CSS intentaba resolverlo pidiendo la fuente
 * FontAwesome, pero el tema nunca la carga, asi que no cambiaba nada.
 *
 * Por eso 'svg' existe: la red que lo trae se dibuja con ese trazado en linea
 * en vez de con un glifo. No pide ningun archivo mas al servidor, hereda el
 * color del enlace y se ve nitido a cualquier tamano.
 *
 * 'fa' se conserva solo para WhatsApp, cuyo glifo si esta en la fuente.
 */
$ovc_redes_extra = apply_filters(
	'ovc_redes_extra',
	array(
		'linkedin' => array(
			'url'      => 'https://www.linkedin.com/in/luisnieto-agile',
			'etiqueta' => 'LinkedIn',
			'fa'       => false,
		),
		'github'   => array(
			'url'      => 'https://github.com/lnieto44',
			'etiqueta' => 'GitHub',
			'svg'      => 'M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38'
				. ' 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13'
				. '-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07'
				. '-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2'
				. '-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53'
				. '-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87'
				. ' 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8'
				. '.012 8.012 0 0 0 16 8c0-4.42-3.58-8-8-8Z',
		),
		'whatsapp' => array(
			'url'      => '', // Ej.: https://wa.me/57XXXXXXXXXX
			'etiqueta' => 'WhatsApp',
			'fa'       => true,
		),
	)
);
?>
<ul class="et-social-icons">

<?php if ( 'on' === et_get_option( 'divi_show_facebook_icon', 'on' ) ) : ?>
	<li class="et-social-icon et-social-facebook">
		<a href="<?php echo esc_url( strval( et_get_option( 'divi_facebook_url', '#' ) ) ); ?>" class="icon">
			<span><?php esc_html_e( 'Facebook', 'Divi' ); ?></span>
		</a>
	</li>
<?php endif; ?>

<?php if ( 'on' === et_get_option( 'divi_show_twitter_icon', 'on' ) ) : ?>
	<li class="et-social-icon et-social-twitter">
		<a href="<?php echo esc_url( strval( et_get_option( 'divi_twitter_url', '#' ) ) ); ?>" class="icon">
			<span><?php esc_html_e( 'X', 'Divi' ); ?></span>
		</a>
	</li>
<?php endif; ?>

<?php $et_instagram_default = ( function_exists( 'et_divi_is_fresh_install' ) && true === et_divi_is_fresh_install() ) ? 'on' : 'false'; ?>
<?php if ( 'on' === et_get_option( 'divi_show_instagram_icon', $et_instagram_default ) ) : ?>
	<li class="et-social-icon et-social-instagram">
		<a href="<?php echo esc_url( strval( et_get_option( 'divi_instagram_url', '#' ) ) ); ?>" class="icon">
			<span><?php esc_html_e( 'Instagram', 'Divi' ); ?></span>
		</a>
	</li>
<?php endif; ?>

<?php foreach ( $ovc_redes_extra as $ovc_red => $ovc_datos ) : ?>
	<?php if ( empty( $ovc_datos['url'] ) ) { continue; } ?>
	<li class="et-social-icon et-social-<?php echo esc_attr( $ovc_red ); ?><?php echo ! empty( $ovc_datos['fa'] ) ? ' et-pb-social-fa-icon' : ''; ?><?php echo ! empty( $ovc_datos['svg'] ) ? ' ovc-social--svg' : ''; ?>">
		<a href="<?php echo esc_url( $ovc_datos['url'] ); ?>" class="icon" target="_blank" rel="noopener noreferrer me">
			<?php if ( ! empty( $ovc_datos['svg'] ) ) : ?>
				<?php /* aria-hidden: el nombre ya lo da el <span> de debajo. */ ?>
				<svg viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="<?php echo esc_attr( $ovc_datos['svg'] ); ?>" /></svg>
			<?php endif; ?>
			<span><?php echo esc_html( $ovc_datos['etiqueta'] ); ?></span>
		</a>
	</li>
<?php endforeach; ?>

<?php if ( 'on' === et_get_option( 'divi_show_rss_icon', 'on' ) ) : ?>
	<?php
	$et_rss_url = ! empty( et_get_option( 'divi_rss_url' ) )
		? et_get_option( 'divi_rss_url' )
		: get_bloginfo( 'rss2_url' );
	?>
	<li class="et-social-icon et-social-rss">
		<a href="<?php echo esc_url( strval( $et_rss_url ) ); ?>" class="icon">
			<span><?php esc_html_e( 'RSS', 'Divi' ); ?></span>
		</a>
	</li>
<?php endif; ?>

</ul>
