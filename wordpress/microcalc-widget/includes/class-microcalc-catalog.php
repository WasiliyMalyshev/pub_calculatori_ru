<?php
/**
 * Каталог калькуляторов с сервера MicroCalc (spec/embed.md, раздел 1).
 * Хранится 12 часов во временной записи; последняя удачная копия — в настройке без автозагрузки,
 * чтобы виджеты не пропали, пока сервер недоступен.
 *
 * @package MicroCalc_Widget
 */

defined( 'ABSPATH' ) || exit;

class MicroCalc_Catalog {

	const VERSION = 1;
	const TTL     = 12 * HOUR_IN_SECONDS;

	/**
	 * Каталог на языке $lang или null.
	 *
	 * @param string $lang ru, en или fr.
	 * @return array|null
	 */
	public static function get( $lang ) {
		$key    = 'microcalc_catalog_' . $lang;
		$cached = get_transient( $key );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$fresh = self::fetch( $lang );
		if ( $fresh ) {
			set_transient( $key, $fresh, self::TTL );
			update_option( 'microcalc_catalog_stale_' . $lang, $fresh, false );
			return $fresh;
		}
		// сервер недоступен: последняя удачная копия, повторная попытка через час
		$stale = get_option( 'microcalc_catalog_stale_' . $lang );
		if ( is_array( $stale ) ) {
			set_transient( $key, $stale, HOUR_IN_SECONDS );
			return $stale;
		}
		return null;
	}

	/**
	 * Запись каталога по id или null.
	 *
	 * @param int    $id   id калькулятора или тренажёра.
	 * @param string $lang Язык.
	 * @return array|null
	 */
	public static function item( $id, $lang ) {
		$catalog = self::get( $lang );
		if ( ! $catalog ) {
			return null;
		}
		foreach ( $catalog['items'] as $item ) {
			if ( (int) $item['id'] === (int) $id ) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * Сброс кэша (после смены адреса сервиса).
	 */
	public static function flush() {
		foreach ( array( 'ru', 'en', 'fr' ) as $lang ) {
			delete_transient( 'microcalc_catalog_' . $lang );
			delete_option( 'microcalc_catalog_stale_' . $lang );
		}
	}

	/**
	 * Загрузка и проверка каталога: адреса должны вести на настроенный сервис.
	 *
	 * @param string $lang Язык.
	 * @return array|null
	 */
	private static function fetch( $lang ) {
		$base     = microcalc_widget_base();
		$response = wp_remote_get(
			add_query_arg( array( 'v' => self::VERSION, 'lang' => $lang ), $base . 'calc/widget_catalog.php' ),
			array( 'timeout' => 10 )
		);
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || self::VERSION !== (int) ( $data['version'] ?? 0 ) || empty( $data['items'] ) || empty( $data['site'] ) ) {
			return null;
		}
		$starts = function ( $url ) use ( $base ) {
			return is_string( $url ) && 0 === strpos( $url, $base );
		};
		if ( ! $starts( $data['site']['script'] ?? '' ) ) {
			return null;
		}
		$items = array();
		foreach ( $data['items'] as $item ) {
			if ( ! isset( $item['id'], $item['name'], $item['page_url'], $item['widget_src'] ) ) {
				continue;
			}
			if ( ! $starts( $item['page_url'] ) || ! $starts( $item['widget_src'] ) ) {
				continue;
			}
			$items[] = array(
				'id'         => (int) $item['id'],
				'type'       => sanitize_key( $item['type'] ?? 'calc' ),
				'group'      => (int) ( $item['group'] ?? 0 ),
				'name'       => sanitize_text_field( $item['name'] ),
				'page_url'   => esc_url_raw( $item['page_url'] ),
				'widget_src' => esc_url_raw( $item['widget_src'] ),
			);
		}
		$groups = array();
		foreach ( (array) ( $data['groups'] ?? array() ) as $group ) {
			$groups[] = array( 'id' => (int) ( $group['id'] ?? 0 ), 'name' => sanitize_text_field( $group['name'] ?? '' ) );
		}
		$terms = array();
		foreach ( (array) ( $data['terms'] ?? array() ) as $term ) {
			$terms[] = sanitize_text_field( $term );
		}
		return array(
			'site'   => array(
				'name'   => sanitize_text_field( $data['site']['name'] ?? '' ),
				'script' => esc_url_raw( $data['site']['script'] ),
				'setup'  => esc_url_raw( $data['site']['setup'] ?? $base ),
				'check'  => esc_url_raw( $data['site']['check'] ?? $base . 'calc/widget_check.php' ),
			),
			'terms'  => $terms,
			'groups' => $groups,
			'items'  => $items,
		);
	}
}
