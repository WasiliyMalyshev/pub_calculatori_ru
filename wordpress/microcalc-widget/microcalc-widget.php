<?php
/**
 * Plugin Name:       MicroCalc Widgets
 * Plugin URI:        https://microcalc.io/
 * Description:       Free step-by-step calculators and trainers from MicroCalc on your site: shortcode, block and sidebar widget.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            MicroCalc
 * Author URI:        https://microcalc.io/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       microcalc-widget
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'MICROCALC_WIDGET_VERSION', '1.0.0' );
define( 'MICROCALC_WIDGET_FILE', __FILE__ );
define( 'MICROCALC_WIDGET_DIR', plugin_dir_path( __FILE__ ) );

// Адрес сервиса задаётся только здесь. Можно переопределить в wp-config.php
// (define( 'MICROCALC_WIDGET_BASE', 'https://…/' );) или в настройках плагина.
if ( ! defined( 'MICROCALC_WIDGET_BASE' ) ) {
	define( 'MICROCALC_WIDGET_BASE', 'https://microcalc.io/' );
}

require_once MICROCALC_WIDGET_DIR . 'includes/class-microcalc-catalog.php';
require_once MICROCALC_WIDGET_DIR . 'includes/class-microcalc-render.php';
require_once MICROCALC_WIDGET_DIR . 'includes/class-microcalc-sidebar-widget.php';
require_once MICROCALC_WIDGET_DIR . 'includes/class-microcalc-admin.php';

/**
 * Настройки плагина со значениями по умолчанию.
 *
 * @return array
 */
function microcalc_widget_options() {
	$defaults = array(
		'consent' => 0,
		'lang'    => 'auto',
		'theme'   => 'light',
		'accent'  => '',
		'radius'  => 6,
		'base'    => '',
	);
	$saved = get_option( 'microcalc_widget', array() );
	return array_merge( $defaults, is_array( $saved ) ? $saved : array() );
}

/**
 * Адрес сервиса со слешем в конце: настройка, иначе MICROCALC_WIDGET_BASE.
 *
 * @return string
 */
function microcalc_widget_base() {
	$opt  = microcalc_widget_options();
	$base = ( '' !== $opt['base'] ) ? $opt['base'] : MICROCALC_WIDGET_BASE;
	return trailingslashit( esc_url_raw( $base ) );
}

/**
 * Язык виджета: из настройки или по языку сайта (ru_*, fr_*, иначе en).
 *
 * @param string $lang Язык из шорткода или блока.
 * @return string
 */
function microcalc_widget_lang( $lang = '' ) {
	$allowed = array( 'ru', 'en', 'fr' );
	if ( in_array( $lang, $allowed, true ) ) {
		return $lang;
	}
	$opt = microcalc_widget_options();
	if ( in_array( $opt['lang'], $allowed, true ) ) {
		return $opt['lang'];
	}
	$locale = substr( determine_locale(), 0, 2 );
	return in_array( $locale, $allowed, true ) ? $locale : 'en';
}

add_action(
	'init',
	function () {
		load_plugin_textdomain( 'microcalc-widget', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		add_shortcode( 'microcalc', array( 'MicroCalc_Render', 'shortcode' ) );
		MicroCalc_Render::register_block();
	}
);

add_action(
	'widgets_init',
	function () {
		register_widget( 'MicroCalc_Sidebar_Widget' );
	}
);

add_action(
	'rest_api_init',
	function () {
		// каталог для редактора блоков: только тем, кто может править записи
		register_rest_route(
			'microcalc/v1',
			'/catalog',
			array(
				'methods'             => 'GET',
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
				'args'                => array(
					'lang' => array( 'sanitize_callback' => 'sanitize_key' ),
				),
				'callback'            => function ( WP_REST_Request $request ) {
					$catalog = MicroCalc_Catalog::get( microcalc_widget_lang( (string) $request->get_param( 'lang' ) ) );
					if ( ! $catalog ) {
						return new WP_Error( 'microcalc_catalog', __( 'Could not load the calculator list. Try again later.', 'microcalc-widget' ), array( 'status' => 502 ) );
					}
					$items = array();
					foreach ( $catalog['items'] as $item ) {
						$items[] = array( 'id' => $item['id'], 'name' => $item['name'], 'group' => $item['group'], 'type' => $item['type'] );
					}
					return array( 'groups' => $catalog['groups'], 'items' => $items, 'site' => $catalog['site']['name'] );
				},
			)
		);
	}
);

if ( is_admin() ) {
	MicroCalc_Admin::init();
}
