<?php
/*----------------------------------------------------------------------------------------------------------------------
Plugin Name: WooCommerce Live Order Pricing
Description: Displays realtime price changes and customer budgets on the admin order screen.
Version: 1.2.0
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
		add_action( 'wp_ajax_load_order_budgets', array( $this, 'render_pricing_meta_box' ) );

		$this->add_cust_fields();
	}

	/**
	 * Let's add the pricing metabox.
	 */
	public function add_metabox( $post_type ) {
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
				'title' => __( 'Customer Budgets & Tax Details', 'woocommerce-live-order-pricing' ),
				'fields' => array (
					array (
						'key' => 'field_54ddf46bc10b7',
						'label' => __( 'Budgets', 'woocommerce-live-order-pricing' ),
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
						'button_label' => __( 'Add Budget', 'woocommerce-live-order-pricing' ),
						'sub_fields' => array (
							array (
								'key' => 'field_54ddf4a4c10b8',
								'label' => __( 'Budget name', 'woocommerce-live-order-pricing' ),
								'name' => 'budget_name',
								'prefix' => '',
								'type' => 'text',
								'instructions' => __( 'Provide a name to identify this budget.', 'woocommerce-live-order-pricing' ),
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
								'label' => __( 'Budget amount', 'woocommerce-live-order-pricing' ),
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
						'label' => __( 'Discount (%)', 'woocommerce-live-order-pricing' ),
						'name' => 'customer_discount',
						'prefix' => '',
						'type' => 'number',
						'instructions' => __( 'Specify a regular discount for this user.', 'woocommerce-live-order-pricing' ),
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
						'label' => __( 'VAT ID', 'woocommerce-live-order-pricing' ),
						'name' => 'customer_vat_id',
						'prefix' => '',
						'type' => 'text',
						'instructions' => __( 'Provide the customer\'s VAT ID if applicable.', 'woocommerce-live-order-pricing' ),
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
						'key' => 'field_54ddf56c98df0_cid',
						'label' => __( 'Customer ID', 'woocommerce-live-order-pricing' ),
						'name' => 'customer_internal_id',
						'prefix' => '',
						'type' => 'text',
						'instructions' => __( 'Optionally specify an internal ID code for this customer.', 'woocommerce-live-order-pricing' ),
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
	public function render_pricing_meta_box( $post = NULL ) {

		$this->c = get_woocommerce_currency_symbol();
		$dec = wc_get_price_decimal_separator();
		$tho = wc_get_price_thousand_separator();

		$pid = $post ? $post->ID : $_REQUEST['post_ID'];
		$order = new WC_Order( $pid );
		$user = $order->get_user();
		$ini_total = $subtotal = $order->get_subtotal();
		$budgets = array();
		$discount = $budget = 0;

		$uid = $user ? $user->ID : @$_REQUEST['user_ID'];

		if ( $uid ) {
			$budgets = get_field( 'customer_budgets', "user_{$uid}" );
			$discount = get_field( 'customer_discount', "user_{$uid}" ) / 100;
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
				<td width="50%" align="right" id="wcBcBasket" data-amount="<?php echo $ini_total; ?>"><?php echo $this->c . number_format( $ini_total, 2 ); ?><span id="wc_lp_curtotal"></span></td>
			</tr>
			<tr>
				<td width="50%" align="left" style="padding-top:7px;"><?php echo number_format( $discount * 100, 2 ); ?>% <?php echo __( 'Discount', 'woocommerce-live-order-pricing' ); ?>: &nbsp;</td>
				<td width="50%" align="right" id="wcBcDiscount" data-amount="<?php echo $discount; ?>" style="padding-top:7px;"><?php echo $this->c . number_format( $discount * $ini_total, 2 ); ?></td>
			</tr>
			<tr>
				<td colspan="2" style="border-bottom:2px solid #eee;padding-top:7px;"></td>
			</tr>
			<tr>
				<td width="50%" align="left" style="padding-top:7px;"><?php echo __( 'Subtotal', 'woocommerce-live-order-pricing' ); ?>: &nbsp;</td>
				<td width="50%" align="right" id="wcBcSubtotal" style="padding-top:7px;"><?php echo $this->c . number_format( $subtotal, 2 ); ?></td>
			</tr>
			<tr>
				<td width="50%" align="left" style="padding-top:7px;"><?php echo __( 'Budget', 'woocommerce-live-order-pricing' ); ?>: &nbsp;</td>
				<td width="50%" align="right" id="wcBcBudget" style="padding-top:7px;"><?php echo $this->c . '0.00'; ?></td>
			</tr>
			<tr>
				<td colspan="2" style="border-bottom:2px solid #eee;padding-top:7px;"></td>
			</tr>
			<tr>
				<td width="50%" align="left" style="padding-top:7px;"><?php echo __( 'Balance', 'woocommerce-live-order-pricing' ); ?>: &nbsp;</td>
				<td width="50%" align="right" id="wcBcBalance" style="padding-top:7px;color:#ff0000;"><?php echo '+' . $this->c . number_format( $subtotal, 2); ?></td>
			</tr>
		</table>

		<script type="text/javascript">
		function rmCurFormat(v){
			var symbols = {'<?php echo $dec; ?>':'.','<?php echo $tho; ?>':','};
			return v.replace(/<?php echo ($dec == '.' ? '\\' : '') . $dec; ?>|<?php echo ($tho == '.' ? '\\' : '') . $tho; ?>/gi, function(matched){ return symbols[matched]; });
		}

		function addCurFormat(v){
			var symbols = {'.':'<?php echo $dec; ?>',',':'<?php echo $tho; ?>'};
			return v.replace(/\.|,/gi, function(matched){ return symbols[matched]; });
		}

		jQuery('document').ready(function($){

			function selectCustomerBudget(){
				amt = parseFloat($('#customerBudgetAmt').val() ? $('#customerBudgetAmt').val() : 0);
				discount = $('#wcBcDiscount').data('amount') * $('#wcBcBasket').data('amount');
				discounted = $('#wcBcBasket').data('amount') - discount;
				balance = amt - discounted;
				$('#wcBcBudget').html('<?php echo $this->c; ?>' + addCurFormat(amt.toFixed(2)));
				$('#wcBcSubtotal').html('<?php echo $this->c; ?>' + addCurFormat(discounted.toFixed(2)));
				$('#wcBcBalance').html((balance < 0 ? '+' : '-') + '<?php echo $this->c; ?>' + addCurFormat(Math.abs(balance).toFixed(2)));
				$('#wcBcBalance').css('color',balance < 0 ? '#ff0000' : '#66cd00');

				// Update discount amount
				$('#wcBcDiscount').html('<?php echo $this->c; ?>' + addCurFormat(Math.abs(discount).toFixed(2)));
			}

			function setCustomerBudgets(id){
				$('#order_live_pricing .inside')
					.html('<p style="padding:10px;text-align:center;"><img src="images/loading.gif" /></p>')
					.load(ajaxurl + '?action=load_order_budgets',{user_ID:id,post_ID:<?php echo $pid; ?>});
			}

			function updateBudgetBasket(){
				var total = 0;
				$('.line_cost .line_total').each(function(){
					lineTotal = $(this).val() || 0;
					total += parseFloat(rmCurFormat(lineTotal.replace('&nbsp','').replace('<?php echo html_entity_decode($this->c); ?>','')));
				});
				$('#wcBcBasket').html('<?php echo $this->c; ?>' + addCurFormat(total.toFixed(2))).data('amount',total);
				selectCustomerBudget();
			}

			$('#customerBudgetAmt').change(function(){
				selectCustomerBudget();
			});

			$('#customer_user').change(function(){
				setCustomerBudgets($(this).val());
			});

			$(document).ajaxComplete(function(){
				setTimeout(function(){ updateBudgetBasket(); },1);
			});

		});
		</script>
		<?php

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) die();
	}
}
