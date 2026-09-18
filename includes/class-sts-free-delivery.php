<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STS_Free_Delivery {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	public function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_action( 'wp_enqueue_scripts',                          array( $this, 'enqueue_scripts' ) );
		add_action( 'woocommerce_cart_collaterals',                array( $this, 'render_bar' ), 5 );
		add_action( 'woocommerce_checkout_before_order_review',    array( $this, 'render_bar' ) );
		add_filter( 'woocommerce_add_to_cart_fragments',           array( $this, 'add_fragment' ) );
		add_filter( 'woocommerce_update_order_review_fragments',   array( $this, 'add_fragment' ) );
		add_action( 'woocommerce_shipping_zone_method_added',      array( $this, 'clear_threshold_cache' ) );
		add_action( 'woocommerce_shipping_zone_method_deleted',    array( $this, 'clear_threshold_cache' ) );
		add_action( 'woocommerce_shipping_zone_method_status_toggled', array( $this, 'clear_threshold_cache' ) );
	}

	public function enqueue_scripts() {
		if ( ! is_cart() && ! is_checkout() ) {
			return;
		}

		wp_enqueue_style(
			'sts-free-delivery',
			STS_FD_PLUGIN_URL . 'assets/css/free-delivery.css',
			array(),
			STS_FD_VERSION
		);

		wp_enqueue_script(
			'sts-free-delivery',
			STS_FD_PLUGIN_URL . 'assets/js/free-delivery.js',
			array( 'jquery' ),
			STS_FD_VERSION,
			true
		);
	}

	private function get_free_shipping_threshold() {
		$cached = get_transient( 'sts_fd_free_shipping_threshold' );
		if ( false !== $cached ) {
			return (float) $cached;
		}

		$threshold = 0.0;

		$zones = WC_Shipping_Zones::get_zones();
		foreach ( $zones as $zone_data ) {
			$zone    = new WC_Shipping_Zone( $zone_data['zone_id'] );
			$methods = $zone->get_shipping_methods( true );
			foreach ( $methods as $method ) {
				if ( 'free_shipping' === $method->id ) {
					$min = (float) $method->get_option( 'min_amount', 0 );
					if ( $min > 0 ) {
						$threshold = $min;
						break 2;
					}
				}
			}
		}

		if ( 0.0 === $threshold ) {
			$zone_0  = new WC_Shipping_Zone( 0 );
			$methods = $zone_0->get_shipping_methods( true );
			foreach ( $methods as $method ) {
				if ( 'free_shipping' === $method->id ) {
					$min = (float) $method->get_option( 'min_amount', 0 );
					if ( $min > 0 ) {
						$threshold = $min;
						break;
					}
				}
			}
		}

		set_transient( 'sts_fd_free_shipping_threshold', $threshold, 12 * HOUR_IN_SECONDS );

		return $threshold;
	}

	private function get_bar_html() {
		if ( ! WC()->cart ) {
			return '';
		}

		$threshold = $this->get_free_shipping_threshold();

		if ( 0.0 === $threshold ) {
			return '';
		}

		$cart_total = (float) WC()->cart->total;
		$reached    = $cart_total >= $threshold;
		$percentage = min( 100, round( ( $cart_total / $threshold ) * 100 ) );
		$remaining  = max( 0, $threshold - $cart_total );

		if ( $reached ) {
			$message = 'קיבלת משלוח חינם! 🎉';
		} else {
			$remaining_formatted = number_format( $remaining, 0, '.', ',' );
			$message             = 'עוד ' . $remaining_formatted . ' ₪ ותקבלי גם משלוח חינם!';
		}

		ob_start();
		?>
		<div class="sts-free-delivery-bar-wrap"
		     data-threshold="<?php echo esc_attr( $threshold ); ?>"
		     data-total="<?php echo esc_attr( $cart_total ); ?>"
		     data-percentage="<?php echo esc_attr( $percentage ); ?>"
		     data-reached="<?php echo $reached ? '1' : '0'; ?>">
			<div class="sts-free-delivery-bar-inner">
				<p class="sts-free-delivery-message<?php echo $reached ? ' sts-free-delivery-message--success' : ''; ?>">
					<?php echo esc_html( $message ); ?>
				</p>
				<div class="sts-free-delivery-track"
				     role="progressbar"
				     aria-valuenow="<?php echo esc_attr( $percentage ); ?>"
				     aria-valuemin="0"
				     aria-valuemax="100">
					<div class="sts-free-delivery-fill"
					     style="width: 0%"
					     data-target-width="<?php echo esc_attr( $percentage ); ?>%">
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function render_bar() {
		echo $this->get_bar_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function add_fragment( $fragments ) {
		$fragments['.sts-free-delivery-bar-wrap'] = $this->get_bar_html();
		return $fragments;
	}

	public function clear_threshold_cache() {
		delete_transient( 'sts_fd_free_shipping_threshold' );
	}
}
