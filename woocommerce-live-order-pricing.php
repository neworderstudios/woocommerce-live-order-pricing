<?php
/*----------------------------------------------------------------------------------------------------------------------
Plugin Name: WooCommerce Live Order Pricing
Description: Displays realtime price changes and customer budgets on the admin order screen.
Version: 1.0.0
Author: New Order Studios
Author URI: https://github.com/neworderstudios
----------------------------------------------------------------------------------------------------------------------*/

if ( is_admin() ) {
    new wcLivePricing();
}

class wcLivePricing {
	protected $c;

	public function __construct() {
		load_plugin_textdomain( 'woocommerce-live-order-pricing', false, basename( dirname(__FILE__) ) . '/i18n' );
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'wp_ajax_load_order_budgets', array( $this, 'load_budgets' ) );
	}

	/**
	 * Let's add the pricing metabox.
	 */
	public function add_metabox( $post_type ) {
		$this->c = get_woocommerce_currency_symbol();

		if ( $post_type == 'order' ) {

			add_meta_box(
				'order_live_pricing'
				,__( 'Customer Budget &amp; Order Totals', 'woocommerce-live-order-pricing' )
				,array( $this, 'render_pricing_meta_box' )
				,$post_type
				,'side'
				,'core'
			);
		
		}
	}

	/**
	 * Let's render the pricing box.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_pricing_meta_box( $post ) {

		if ( $post->ID ) {
			/* LOADING ORDER BUDGETS + DISCOUNTS FOR CLIENT */
		} else {
			/* SELECT A CLIENT TO LOAD BUDGETS + DISCOUNTS */
		}

		?>
		<table cellpadding="0" cellspacing="0">
			<tr>
				<td width="50%" align="right"><?php echo __( 'Current Basket', 'woocommerce-live-order-pricing' ); ?>: &nbsp;</td>
				<td width="50%" align="right"><?php echo $this->c; ?><span id="wc_lp_curtotal"></span></td>
			</tr>
			<tr>
				<td width="50%" align="right" style="padding-top:7px;">% <?php echo __( 'Discount', 'woocommerce-live-order-pricing' ); ?>: &nbsp;</td>
				<td width="50%" align="right" style="padding-top:7px;"><?php echo $this->c; ?></td>
			</tr>
			<tr>
				<td colspan="2" style="border-bottom:2px solid #eee;padding-top:7px;"></td>
			</tr>
			<tr>
				<td width="50%" align="right" style="padding-top:7px;"><?php echo __( 'Subtotal', 'woocommerce-live-order-pricing' ); ?>: &nbsp;</td>
				<td width="50%" align="right" style="padding-top:7px;"><?php echo $this->c; ?></td>
			</tr>
			<tr>
				<td width="50%" align="right" style="padding-top:7px;"><?php echo __( 'Budget', 'woocommerce-live-order-pricing' ); ?>: &nbsp;</td>
				<td width="50%" align="right" style="padding-top:7px;"><?php echo $this->c; ?></td>
			</tr>
			<tr>
				<td colspan="2" style="border-bottom:2px solid #eee;padding-top:7px;"></td>
			</tr>
			<tr>
				<td width="50%" align="right" style="padding-top:7px;"><?php echo __( 'Balance', 'woocommerce-live-order-pricing' ); ?>: &nbsp;</td>
				<td width="50%" align="right" style="padding-top:7px;"><?php echo $this->c; ?></td>
			</tr>
		</table>

		<script type="text/javascript">
		jQuery('document').ready(function($){
			
		});
		</script>
		<?php
	}

	/**
	 * AJAX action for saving an adjustment.
	 */
	function load_budgets() {
		
		die();
	}
}
