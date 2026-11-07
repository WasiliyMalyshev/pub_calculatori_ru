<?php
/**
 * Вывод виджета: шорткод [microcalc], блок microcalc/widget и виджет боковой колонки.
 * HTML собирается на сервере (spec/embed.md, раздел 2): ссылку должен видеть поисковый робот.
 *
 * @package MicroCalc_Widget
 */

defined( 'ABSPATH' ) || exit;

class MicroCalc_Render {

	/**
	 * Параметры оформления (spec/embed.md, раздел 3).
	 */
	const COLORS = array( 'accent', 'bg', 'text', 'border', 'link' );
	const RANGES = array(
		'radius' => array( 0, 24, 6 ),
		'size'   => array( 12, 20, 16 ),
		'pad'    => array( 0, 40, 12 ),
	);
	const FONTS  = array( 'system', 'inter', 'arial', 'verdana', 'tahoma', 'georgia', 'times', 'mono' );

	/**
	 * Шорткод: [microcalc id="318" lang="ru" theme="dark" accent="ff8800" width="600"].
	 *
	 * @param array|string $atts Атрибуты.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'lang'   => '',
				'theme'  => '',
				'accent' => '',
				'bg'     => '',
				'text'   => '',
				'border' => '',
				'link'   => '',
				'radius' => '',
				'size'   => '',
				'pad'    => '',
				'font'   => '',
				'head'   => '',
				'frame'  => '',
				'width'  => 0,
			),
			$atts,
			'microcalc'
		);
		return self::html( $atts );
	}

	/**
	 * Блок редактора: тот же вывод, атрибуты из block.json.
	 */
	public static function register_block() {
		$opt = microcalc_widget_options();
		register_block_type(
			MICROCALC_WIDGET_DIR . 'blocks/widget',
			array(
				'render_callback' => function ( $attributes ) {
					return self::html(
						array(
							'id'     => $attributes['calcId'] ?? 0,
							'lang'   => $attributes['lang'] ?? '',
							'theme'  => $attributes['theme'] ?? '',
							'accent' => $attributes['accent'] ?? '',
							'width'  => $attributes['width'] ?? 0,
						)
					);
				},
			)
		);
		// тексты редактора блока: из переводов плагина, без сборки JSON-файлов
		wp_add_inline_script(
			'microcalc-widget-editor-script',
			'window.microcalcWidget = ' . wp_json_encode(
				array(
					'consent'  => (int) $opt['consent'],
					'settings' => admin_url( 'options-general.php?page=microcalc-widget' ),
					'i18n'     => array(
						'title'      => __( 'MicroCalc calculator', 'microcalc-widget' ),
						'calc'       => __( 'Calculator or trainer', 'microcalc-widget' ),
						'search'     => __( 'Start typing a name', 'microcalc-widget' ),
						'lang'       => __( 'Language', 'microcalc-widget' ),
						'auto'       => __( 'Site language', 'microcalc-widget' ),
						'theme'      => __( 'Theme', 'microcalc-widget' ),
						'default'    => __( 'From plugin settings', 'microcalc-widget' ),
						'light'      => __( 'Light', 'microcalc-widget' ),
						'dark'       => __( 'Dark', 'microcalc-widget' ),
						'system'     => __( 'System', 'microcalc-widget' ),
						'accent'     => __( 'Button color', 'microcalc-widget' ),
						'width'      => __( 'Max width, px (0 — full width)', 'microcalc-widget' ),
						'choose'     => __( 'Choose a calculator in the block settings on the right.', 'microcalc-widget' ),
						'preview'    => __( 'The calculator is shown on the published page.', 'microcalc-widget' ),
						'loading'    => __( 'Loading the calculator list…', 'microcalc-widget' ),
						'noConsent'  => __( 'Before the widget is shown, accept the terms in Settings → MicroCalc.', 'microcalc-widget' ),
						'openSettings' => __( 'Open settings', 'microcalc-widget' ),
					),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * HTML виджета или пустая строка (администратору — подсказка, почему виджета нет).
	 *
	 * @param array $atts id, lang, параметры оформления, width.
	 * @return string
	 */
	public static function html( $atts ) {
		$opt  = microcalc_widget_options();
		$id   = absint( $atts['id'] ?? 0 );
		$lang = microcalc_widget_lang( (string) ( $atts['lang'] ?? '' ) );

		if ( ! $opt['consent'] ) {
			return self::notice( __( 'MicroCalc widget is hidden: accept the terms in Settings → MicroCalc.', 'microcalc-widget' ) );
		}
		if ( ! $id ) {
			return self::notice( __( 'MicroCalc widget: choose a calculator (id attribute).', 'microcalc-widget' ) );
		}
		$catalog = MicroCalc_Catalog::get( $lang );
		$item    = $catalog ? MicroCalc_Catalog::item( $id, $lang ) : null;
		if ( ! $item ) {
			return self::notice( $catalog ? __( 'MicroCalc widget: calculator not found.', 'microcalc-widget' ) : __( 'MicroCalc widget: could not load the calculator list.', 'microcalc-widget' ) );
		}

		$query        = self::style_query( $atts, $opt );
		$query['src'] = 'wp';
		$src          = add_query_arg( $query, $item['widget_src'] );
		$width        = absint( $atts['width'] ?? 0 );

		wp_enqueue_script( 'microcalc-widget', $catalog['site']['script'], array(), null, array( 'in_footer' => true, 'strategy' => 'async' ) );

		return '<div class="calc-widget" data-src="' . esc_url( $src ) . '"' . ( $width ? ' style="max-width:' . $width . 'px"' : '' ) . '></div>' . "\n"
			. '<p class="calc-widget-link"><a href="' . esc_url( $item['page_url'] ) . '">' . esc_html( $item['name'] ) . '</a> — ' . esc_html( $catalog['site']['name'] ) . '</p>' . "\n";
	}

	/**
	 * Параметры оформления: из атрибутов, иначе из настроек; только допустимые значения и только отличия от умолчаний.
	 *
	 * @param array $atts Атрибуты.
	 * @param array $opt  Настройки плагина.
	 * @return array
	 */
	public static function style_query( $atts, $opt ) {
		$q     = array();
		$theme = ( '' !== (string) ( $atts['theme'] ?? '' ) ) ? $atts['theme'] : $opt['theme'];
		if ( in_array( $theme, array( 'dark', 'auto' ), true ) ) {
			$q['theme'] = $theme;
		}
		if ( '' === (string) ( $atts['accent'] ?? '' ) ) {
			$atts['accent'] = $opt['accent'];
		}
		foreach ( self::COLORS as $key ) {
			$v = strtolower( ltrim( trim( (string) ( $atts[ $key ] ?? '' ) ), '#' ) );
			if ( preg_match( '/^([0-9a-f]{3}|[0-9a-f]{6})$/', $v ) ) {
				$q[ $key ] = $v;
			}
		}
		if ( '' === (string) ( $atts['radius'] ?? '' ) ) {
			$atts['radius'] = $opt['radius'];
		}
		foreach ( self::RANGES as $key => $r ) {
			$v = $atts[ $key ] ?? '';
			if ( is_numeric( $v ) && (int) $v >= $r[0] && (int) $v <= $r[1] && (int) $v !== $r[2] ) {
				$q[ $key ] = (int) $v;
			}
		}
		if ( in_array( $atts['font'] ?? '', self::FONTS, true ) && 'system' !== $atts['font'] ) {
			$q['font'] = $atts['font'];
		}
		foreach ( array( 'head', 'frame' ) as $key ) {
			if ( '0' === (string) ( $atts[ $key ] ?? '' ) ) {
				$q[ $key ] = 0;
			}
		}
		return $q;
	}

	/**
	 * Подсказка только для тех, кто может это исправить; посетители ничего не видят.
	 *
	 * @param string $text Текст.
	 * @return string
	 */
	private static function notice( $text ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return '';
		}
		return '<p class="microcalc-widget-notice" style="border:1px dashed #d63638;padding:8px 12px;color:#d63638">' . esc_html( $text ) . '</p>';
	}
}
