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

		$this->add_cust_fields();
	}

	/**
	 * Let's add the pricing metabox.
	 */
	public function add_metabox( $post_type ) {
		$this->c = get_woocommerce_currency_symbol();

		if ( $post_type == 'shop_order' ) {

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

	public function add_cust_fields () {

		if( function_exists('register_field_group') ){

			register_field_group(array (
				'key' => 'group_54dde380bc3f0',
				'title' => 'Customer Budgets & Tax Details',
				'fields' => array (
					array (
						'key' => 'field_54ddf46bc10b7',
						'label' => 'Budgets',
						'name' => 'customer_budgets',
						'prefix' => '',
						'type' => 'repeater',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array (
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'min' => '1',
						'max' => '',
						'layout' => 'row',
						'button_label' => 'Add Budget',
						'sub_fields' => array (
							array (
								'key' => 'field_54ddf4a4c10b8',
								'label' => 'Budget name',
								'name' => 'budget_name',
								'prefix' => '',
								'type' => 'text',
								'instructions' => 'Provide a name to identify this budget.',
								'required' => 0,
								'conditional_logic' => 0,
								'wrapper' => array (
									'width' => '',
									'class' => '',
									'id' => '',
								),
								'default_value' => '',
								'placeholder' => '',
								'prepend' => '',
								'append' => '',
								'maxlength' => '',
								'readonly' => 0,
								'disabled' => 0,
							),
							array (
								'key' => 'field_54ddf4bec10b9',
								'label' => 'Budget amount',
								'name' => 'budget_amount',
								'prefix' => '',
								'type' => 'number',
								'instructions' => '',
								'required' => 0,
								'conditional_logic' => 0,
								'wrapper' => array (
									'width' => '',
									'class' => '',
									'id' => '',
								),
								'default_value' => '',
								'placeholder' => '',
								'prepend' => '',
								'append' => '',
								'min' => '',
								'max' => '',
								'step' => '',
								'readonly' => 0,
								'disabled' => 0,
							),
						),
					),
					array (
						'key' => 'field_54ddf58c98df1',
						'label' => 'Discount (%)',
						'name' => 'customer_discount',
						'prefix' => '',
						'type' => 'number',
						'instructions' => 'Specify a regular discount for this user.',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array (
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'min' => '',
						'max' => '',
						'step' => '',
						'readonly' => 0,
						'disabled' => 0,
					),
					array (
						'key' => 'field_54ddf56c98df0',
						'label' => 'VAT ID',
						'name' => 'customer_vat_id',
						'prefix' => '',
						'type' => 'text',
						'instructions' => 'Provide the customer\'s VAT ID if applicable.',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array (
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
						'readonly' => 0,
						'disabled' => 0,
					),
				),
				'location' => array (
					array (
						array (
							'param' => 'user_form',
							'operator' => '==',
							'value' => 'edit',
						),
					),
				),
				'menu_order' => 0,
				'position' => 'normal',
				'style' => 'default',
				'label_placement' => 'top',
				'instruction_placement' => 'label',
				'hide_on_screen' => '',
			));
		}

	}

	/**
	 * Let's render the pricing box.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_pricing_meta_box( $post ) {

		$order = new WC_Order( $post->ID );
		$user = $order->get_user();
		$ini_total = $subtotal = $order->get_subtotal();
		$budgets = array();
		$discount = $budget = 0;

		if ( $user ) {
			$budgets = get_field( 'customer_budgets', $user->ID );
			$discount = get_field( 'customer_discount', $user->ID ) / 100;
			$subtotal = ($ini_total - ($ini_total * $discount));
		}

		?>
		<select style="width:100%;margin-bottom:10px;" id="customerBudgetAmt" <?php echo (!$budgets ? 'disabled' : ''); ?>>
			<option value=""><?php echo __( $budgets ? 'Select a customer budget' : 'No budgets for this customer.', 'woocommerce-live-order-pricing' ); ?></option>
			<?php foreach($budgets as $budget){ ?>
				<option value="<?php echo $budget['budget_amount']; ?>"><?php echo $budget['budget_name']; ?></option>
			<?php } ?>

		</select>
		<table cellpadding="0" cellspacing="0" width="100%">
			<tr>
				<td width="50%" align="left"><?php echo __( 'Current Basket', 'woocommerce-live-order-pricing' ); ?>: &nbsp;</td>
				<td width="50%" align="right" id="wcBcBasket"><?php echo $this->c . $ini_total; ?><span id="wc_lp_curtotal"></span></td>
			</tr>
			<tr>
				<td width="50%" align="left" style="padding-top:7px;">% <?php echo __( 'Discount', 'woocommerce-live-order-pricing' ); ?>: &nbsp;</td>
				<td width="50%" align="right" id="wcBcDiscount" style="padding-top:7px;"><?php echo $this->c . $discount; ?></td>
			</tr>
			<tr>
				<td colspan="2" style="border-bottom:2px solid #eee;padding-top:7px;"></td>
			</tr>
			<tr>
				<td width="50%" align="left" style="padding-top:7px;"><?php echo __( 'Subtotal', 'woocommerce-live-order-pricing' ); ?>: &nbsp;</td>
				<td width="50%" align="right" id="wcBcDiscount" style="padding-top:7px;"><?php echo $this->c . $subtotal; ?></td>
			</tr>
			<tr>
				<td width="50%" align="left" style="padding-top:7px;"><?php echo __( 'Budget', 'woocommerce-live-order-pricing' ); ?>: &nbsp;</td>
				<td width="50%" align="right" id="wcBcBudget" style="padding-top:7px;"><?php echo $this->c . '0'; ?></td>
			</tr>
			<tr>
				<td colspan="2" style="border-bottom:2px solid #eee;padding-top:7px;"></td>
			</tr>
			<tr>
				<td width="50%" align="left" style="padding-top:7px;"><?php echo __( 'Balance', 'woocommerce-live-order-pricing' ); ?>: &nbsp;</td>
				<td width="50%" align="right" id="wcBcBalance" style="padding-top:7px;color:#ff0000;"><?php echo '+' . $this->c . $subtotal; ?></td>
			</tr>
		</table>

		<script type="text/javascript">
		jQuery('document').ready(function($){

			function selectCustomerBudget(){

			}

			function setCustomerBudgets(){

			}

			function updateBudgetSubtotal(){

			}

			function updateBudgetBasket(){

			}

			$('#customerBudgetAmt').change(function(){
				selectCustomerBudget($(this).val());
			});

			$('#customer_user').change(function(){
				setCustomerBudgets($(this).val());
			});

		});
		</script>
		<?php
	}

	/**
	 * AJAX action for retrieving a customer's budget and discount details.
	 */
	function load_budgets() {
		
		die();
	}
}
