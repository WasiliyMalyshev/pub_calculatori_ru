<?php
/**
 * Виджет боковой колонки (классические виджеты). В редакторе виджетов на блоках доступен и блок MicroCalc.
 *
 * @package MicroCalc_Widget
 */

defined( 'ABSPATH' ) || exit;

class MicroCalc_Sidebar_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'microcalc_widget',
			__( 'MicroCalc calculator', 'microcalc-widget' ),
			array( 'description' => __( 'Step-by-step calculator or trainer from MicroCalc.', 'microcalc-widget' ) )
		);
	}

	public function widget( $args, $instance ) {
		$html = MicroCalc_Render::html(
			array(
				'id'   => $instance['calc_id'] ?? 0,
				'lang' => $instance['lang'] ?? '',
			)
		);
		if ( '' === $html ) {
			return;
		}
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- разметка темы
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . esc_html( apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base ) ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- экранировано в MicroCalc_Render::html()
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function form( $instance ) {
		$title   = $instance['title'] ?? '';
		$calc_id = (int) ( $instance['calc_id'] ?? 0 );
		$lang    = $instance['lang'] ?? '';
		$catalog = MicroCalc_Catalog::get( microcalc_widget_lang( $lang ) );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'microcalc-widget' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'calc_id' ) ); ?>"><?php esc_html_e( 'Calculator or trainer', 'microcalc-widget' ); ?></label>
			<?php if ( $catalog ) : ?>
				<?php MicroCalc_Admin::calc_select( $this->get_field_id( 'calc_id' ), $this->get_field_name( 'calc_id' ), $calc_id, $catalog, 'widefat' ); ?>
			<?php else : ?>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'calc_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'calc_id' ) ); ?>" type="number" min="1" value="<?php echo esc_attr( $calc_id ); ?>">
			<?php endif; ?>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'lang' ) ); ?>"><?php esc_html_e( 'Language', 'microcalc-widget' ); ?></label>
			<?php MicroCalc_Admin::lang_select( $this->get_field_id( 'lang' ), $this->get_field_name( 'lang' ), $lang, 'widefat' ); ?>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array(
			'title'   => sanitize_text_field( $new_instance['title'] ?? '' ),
			'calc_id' => absint( $new_instance['calc_id'] ?? 0 ),
			'lang'    => in_array( $new_instance['lang'] ?? '', array( 'ru', 'en', 'fr' ), true ) ? $new_instance['lang'] : '',
		);
	}
}
