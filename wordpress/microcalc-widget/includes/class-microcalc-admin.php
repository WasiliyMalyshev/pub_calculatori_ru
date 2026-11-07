<?php
/**
 * Настройки → MicroCalc: согласие с условиями, оформление по умолчанию, генератор шорткода, проверка ссылки.
 *
 * @package MicroCalc_Widget
 */

defined( 'ABSPATH' ) || exit;

class MicroCalc_Admin {

	const PAGE = 'microcalc-widget';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_microcalc_check', array( __CLASS__, 'check' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MICROCALC_WIDGET_FILE ), array( __CLASS__, 'links' ) );
		add_action( 'admin_notices', array( __CLASS__, 'consent_notice' ) );
	}

	public static function menu() {
		add_options_page( 'MicroCalc', 'MicroCalc', 'manage_options', self::PAGE, array( __CLASS__, 'page' ) );
	}

	public static function links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::PAGE ) ) . '">' . esc_html__( 'Settings', 'microcalc-widget' ) . '</a>' );
		return $links;
	}

	public static function register() {
		register_setting(
			'microcalc_widget',
			'microcalc_widget',
			array( 'sanitize_callback' => array( __CLASS__, 'sanitize' ) )
		);
	}

	/**
	 * Проверка значений формы настроек.
	 *
	 * @param array $in Отправленные значения.
	 * @return array
	 */
	public static function sanitize( $in ) {
		$old = microcalc_widget_options();
		$in  = is_array( $in ) ? $in : array();
		$out = array(
			'consent' => empty( $in['consent'] ) ? 0 : 1,
			'lang'    => in_array( $in['lang'] ?? '', array( 'auto', 'ru', 'en', 'fr' ), true ) ? $in['lang'] : 'auto',
			'theme'   => in_array( $in['theme'] ?? '', array( 'light', 'dark', 'auto' ), true ) ? $in['theme'] : 'light',
			'accent'  => preg_match( '/^[0-9a-f]{6}$/', strtolower( ltrim( (string) ( $in['accent'] ?? '' ), '#' ) ) ) ? strtolower( ltrim( $in['accent'], '#' ) ) : '',
			'radius'  => min( 24, max( 0, (int) ( $in['radius'] ?? 6 ) ) ),
			'base'    => '',
		);
		if ( ! empty( $in['accent_default'] ) ) {
			$out['accent'] = '';
		}
		$base = trim( (string) ( $in['base'] ?? '' ) );
		if ( '' !== $base && trailingslashit( $base ) !== trailingslashit( MICROCALC_WIDGET_BASE ) ) {
			if ( wp_http_validate_url( $base ) && 0 === strpos( $base, 'https://' ) ) {
				$out['base'] = trailingslashit( esc_url_raw( $base ) );
			} else {
				add_settings_error( 'microcalc_widget', 'base', __( 'Service address must be a full https:// address.', 'microcalc-widget' ) );
				$out['base'] = $old['base'];
			}
		}
		if ( $out['base'] !== $old['base'] ) {
			MicroCalc_Catalog::flush();
		}
		return $out;
	}

	/**
	 * Пока нет согласия, виджеты не выводятся: напоминание на страницах админки.
	 */
	public static function consent_notice() {
		$screen = get_current_screen();
		if ( ! current_user_can( 'manage_options' ) || microcalc_widget_options()['consent'] || ( $screen && 'settings_page_' . self::PAGE === $screen->id ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'MicroCalc widgets are not shown yet: accept the terms in the plugin settings.', 'microcalc-widget' )
			. ' <a href="' . esc_url( admin_url( 'options-general.php?page=' . self::PAGE ) ) . '">' . esc_html__( 'Open settings', 'microcalc-widget' ) . '</a></p></div>';
	}

	/**
	 * Выпадающий список калькуляторов по разделам.
	 */
	public static function calc_select( $id, $name, $selected, $catalog, $class = '' ) {
		$by_group = array();
		foreach ( $catalog['items'] as $item ) {
			$by_group[ $item['group'] ][] = $item;
		}
		echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="' . esc_attr( $class ) . '">';
		echo '<option value="0">' . esc_html__( '— choose —', 'microcalc-widget' ) . '</option>';
		foreach ( $catalog['groups'] as $group ) {
			if ( empty( $by_group[ $group['id'] ] ) ) {
				continue;
			}
			echo '<optgroup label="' . esc_attr( $group['name'] ) . '">';
			foreach ( $by_group[ $group['id'] ] as $item ) {
				echo '<option value="' . esc_attr( $item['id'] ) . '"' . selected( $selected, $item['id'], false ) . '>' . esc_html( $item['name'] ) . '</option>';
			}
			echo '</optgroup>';
		}
		echo '</select>';
	}

	public static function lang_select( $id, $name, $selected, $class = '', $auto = '' ) {
		$langs = array( ( '' === $auto ? '' : $auto ) => __( 'Site language', 'microcalc-widget' ), 'ru' => 'Русский', 'en' => 'English', 'fr' => 'Français' );
		echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" class="' . esc_attr( $class ) . '">';
		foreach ( $langs as $k => $v ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $selected, $k, false ) . '>' . esc_html( $v ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Проверка ссылки на странице сайта: запрос к сервису с сервера WordPress (spec/embed.md, раздел 4).
	 */
	public static function check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'microcalc-widget' ) );
		}
		check_admin_referer( 'microcalc_check' );
		$page   = esc_url_raw( wp_unslash( $_POST['page_url'] ?? '' ) );
		$result = array( 'ok' => false, 'text' => __( 'Enter the full address of a page on your site.', 'microcalc-widget' ), 'href' => '', 'url' => $page );
		if ( $page ) {
			$lang     = microcalc_widget_lang();
			$catalog  = MicroCalc_Catalog::get( $lang );
			$endpoint = $catalog ? $catalog['site']['check'] : microcalc_widget_base() . 'calc/widget_check.php';
			$response = wp_remote_post(
				$endpoint,
				array(
					'timeout' => 20,
					'headers' => array( 'Content-Type' => 'application/json' ),
					'body'    => wp_json_encode( array( 'page' => $page, 'lang' => $lang ) ),
				)
			);
			$data = is_wp_error( $response ) ? null : json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $data ) && isset( $data['ok'] ) ) {
				$result = array(
					'ok'   => (bool) $data['ok'],
					'text' => sanitize_text_field( $data['text'] ?? '' ),
					'href' => esc_url_raw( $data['href'] ?? '' ),
					'url'  => $page,
				);
			} else {
				$result['text'] = __( 'The service did not respond. Try again later.', 'microcalc-widget' );
			}
		}
		set_transient( 'microcalc_check_' . get_current_user_id(), $result, 5 * MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'options-general.php?page=' . self::PAGE . '#microcalc-check' ) );
		exit;
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$opt     = microcalc_widget_options();
		$lang    = microcalc_widget_lang();
		$catalog = MicroCalc_Catalog::get( $lang );
		$check   = get_transient( 'microcalc_check_' . get_current_user_id() );
		delete_transient( 'microcalc_check_' . get_current_user_id() );
		$site = $catalog ? $catalog['site']['name'] : wp_parse_url( microcalc_widget_base(), PHP_URL_HOST );
		?>
		<div class="wrap">
			<h1>MicroCalc</h1>
			<p><?php esc_html_e( 'Free step-by-step calculators and trainers on your site. Insert them with the MicroCalc block, the [microcalc] shortcode or the sidebar widget.', 'microcalc-widget' ); ?></p>
			<?php settings_errors( 'microcalc_widget' ); ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'microcalc_widget' ); ?>
				<h2><?php esc_html_e( 'Terms of use', 'microcalc-widget' ); ?></h2>
				<?php if ( $catalog && $catalog['terms'] ) : ?>
					<ul style="list-style:disc;padding-left:20px">
						<?php foreach ( $catalog['terms'] as $term ) : ?>
							<li><?php echo esc_html( $term ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<p>
					<label>
						<input type="checkbox" name="microcalc_widget[consent]" value="1" <?php checked( $opt['consent'], 1 ); ?>>
						<?php
						/* translators: %s: service site name */
						echo esc_html( sprintf( __( 'I agree to show a link to %s under each widget. Without the link the widget does not calculate.', 'microcalc-widget' ), $site ) );
						?>
					</label>
				</p>

				<h2><?php esc_html_e( 'Default style', 'microcalc-widget' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="microcalc-lang"><?php esc_html_e( 'Language', 'microcalc-widget' ); ?></label></th>
						<td><?php self::lang_select( 'microcalc-lang', 'microcalc_widget[lang]', $opt['lang'], '', 'auto' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="microcalc-theme"><?php esc_html_e( 'Theme', 'microcalc-widget' ); ?></label></th>
						<td>
							<select id="microcalc-theme" name="microcalc_widget[theme]">
								<option value="light" <?php selected( $opt['theme'], 'light' ); ?>><?php esc_html_e( 'Light', 'microcalc-widget' ); ?></option>
								<option value="dark" <?php selected( $opt['theme'], 'dark' ); ?>><?php esc_html_e( 'Dark', 'microcalc-widget' ); ?></option>
								<option value="auto" <?php selected( $opt['theme'], 'auto' ); ?>><?php esc_html_e( 'System', 'microcalc-widget' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="microcalc-accent"><?php esc_html_e( 'Button color', 'microcalc-widget' ); ?></label></th>
						<td>
							<input type="color" id="microcalc-accent" name="microcalc_widget[accent]" value="<?php echo esc_attr( '#' . ( $opt['accent'] ? $opt['accent'] : 'dbd657' ) ); ?>">
							<label><input type="checkbox" name="microcalc_widget[accent_default]" value="1" <?php checked( '', $opt['accent'] ); ?>> <?php esc_html_e( 'Service default', 'microcalc-widget' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="microcalc-radius"><?php esc_html_e( 'Corner radius, px', 'microcalc-widget' ); ?></label></th>
						<td><input type="number" id="microcalc-radius" name="microcalc_widget[radius]" min="0" max="24" value="<?php echo esc_attr( $opt['radius'] ); ?>" class="small-text"></td>
					</tr>
					<tr>
						<th scope="row"><label for="microcalc-base"><?php esc_html_e( 'Service address', 'microcalc-widget' ); ?></label></th>
						<td>
							<input type="url" id="microcalc-base" name="microcalc_widget[base]" value="<?php echo esc_attr( $opt['base'] ); ?>" placeholder="<?php echo esc_attr( MICROCALC_WIDGET_BASE ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Leave empty. Change only if the service moves to another address.', 'microcalc-widget' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e( 'Shortcode', 'microcalc-widget' ); ?></h2>
			<?php if ( $catalog ) : ?>
				<p><?php self::calc_select( 'microcalc-pick', '', 0, $catalog ); ?></p>
				<p><input type="text" id="microcalc-shortcode" class="large-text code" readonly value="[microcalc id=&quot;…&quot;]" onfocus="this.select()"></p>
				<script>
				document.getElementById( 'microcalc-pick' ).addEventListener( 'change', function () {
					document.getElementById( 'microcalc-shortcode' ).value = this.value !== '0' ? '[microcalc id="' + this.value + '"]' : '[microcalc id="…"]';
				} );
				</script>
				<p class="description"><?php esc_html_e( 'Optional attributes: lang, theme (light, dark, auto), accent, bg, text, border, link (hex color), radius, size, pad, font, head="0", frame="0", width.', 'microcalc-widget' ); ?></p>
			<?php else : ?>
				<div class="notice notice-error inline"><p><?php esc_html_e( 'Could not load the calculator list. Try again later.', 'microcalc-widget' ); ?></p></div>
			<?php endif; ?>

			<h2 id="microcalc-check"><?php esc_html_e( 'Check installation', 'microcalc-widget' ); ?></h2>
			<p><?php esc_html_e( 'Enter the address of a published page with the widget: the service opens it like a search robot and looks for the link.', 'microcalc-widget' ); ?></p>
			<?php if ( is_array( $check ) ) : ?>
				<div class="notice <?php echo $check['ok'] ? 'notice-success' : 'notice-error'; ?> inline">
					<p><strong><?php echo esc_html( $check['text'] ); ?></strong><?php echo $check['href'] ? '<br>' . esc_html( $check['href'] ) : ''; ?></p>
				</div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="microcalc_check">
				<?php wp_nonce_field( 'microcalc_check' ); ?>
				<input type="url" name="page_url" class="regular-text" required placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" value="<?php echo esc_attr( is_array( $check ) ? $check['url'] : '' ); ?>">
				<?php submit_button( __( 'Check', 'microcalc-widget' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}
}
