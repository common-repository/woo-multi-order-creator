<?php /*
Plugin Name: Woo Product Wise Multi Order Creator
Description: Create multiple orders in woocommerce product wise.
Plugin URI: http://www.thewpexperts.co.uk
Version: 2.0.0
Author: TheWPexperts 
Author URI: http://www.thewpexperts.com/ 
*/

 /*

	Copyright 2017  The WP Experts

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	Permission is hereby granted, free of charge, to any person obtaining a copy of this
	software and associated documentation files (the "Software"), to deal in the Software
	without restriction, including without limitation the rights to use, copy, modify, merge,
	publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons
	to whom the Software is furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in all copies or
	substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	THE SOFTWARE.

*/ 
define( 'WOO_MULTI_ORDER_URL', plugins_url('/', __FILE__) );
define( 'WOO_MULTI_ORDER_DIR', dirname(__FILE__) );
if ( ! class_exists( 'WC_CPInstallCheck' ) ) {
    class WC_CPInstallCheck {
		static function install() {
			if ( !is_plugin_active('woocommerce/woocommerce.php')){
				deactivate_plugins(__FILE__);
				$error_message = __('This plugin requires <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a>  plugins to be active!', 'woocommerce');
				die($error_message);
			}
		}
	}
}
register_activation_hook( __FILE__, array('WC_CPInstallCheck', 'install') );
add_action('init','woo_multi_order_load_function');
function woo_multi_order_load_function() {
    $enableMultiOrder = get_option('enableMultiOrder');	
	$customOrderPermit = get_option('customOrderPermit');	
	add_action('admin_menu', 'register_multi_order_setting_submenu',99);
	if($enableMultiOrder == 1){
		add_action('woocommerce_multiorder_checkout_before_order_info', 'destinationStep');
		add_action( 'woocommerce_thankyou', 'multi_order_generator', 10, 1 );
			if($customOrderPermit ==1){
			add_action( 'woocommerce_after_order_notes', 'woo_order_dates_checkout_field' ); 
			add_action( 'wp_head', 'date_picker_scripts' );
			add_action( 'woocommerce_checkout_update_order_meta', 'checkout_update_delivery_meta' );
			add_filter("manage_edit-shop_order_columns", "woo_order_extra_columns");
			add_action("manage_posts_custom_column",  "woo_order_extra_columns_content");
			add_filter( 'manage_edit-shop_order_sortable_columns', 'my_sortable_cake_column' );
		}
	}
} 
function your_plugin_settings_link($links) { 
  $settings_link = '<a href="admin.php?page=multi-order-setting">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'your_plugin_settings_link' );
function register_multi_order_setting_submenu() {
	add_submenu_page( 'woocommerce', 'Multi-order Setting', 'Multi-order Setting', 'manage_options', 'multi-order-setting', 'multi_order_setting' ); 
} 
function multi_order_setting(){
	include(WOO_MULTI_ORDER_DIR.'/admin/settings.php');
}
function date_picker_scripts() {
	$customOrderPermit = get_option('customOrderPermit');	
	if(is_page('checkout')){
	  wp_enqueue_script( 'jquery' );
	  wp_enqueue_script( 'jquery-ui-datepicker', array( 'jquery' ) );
	  wp_register_style('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css');
	  wp_enqueue_style( 'jquery-ui' );   	   
	}
}
function checkout_update_delivery_meta( $order_id ) {
	$dateArray = array();
	$customOrderPermit = get_option('customOrderPermit');	
	$dayDifference = get_option('dayDifference');	
	$dt = date('d/m/y', strtotime("+".$dayDifference." days"));
    if ( count($_POST['customDatePicker'])>0) {
		foreach($_POST['customDatePicker'] as $customDatePicker){
			if(!empty($customDatePicker)){
				$dateArray[]  = $customDatePicker;
			}else{
				$dateArray[]  = $dt;
			}
		}
    }
	$dateValue = join( ', ', $dateArray ); 
	update_post_meta( $order_id, 'Delivery_Dates', $dateValue);
} 
function woo_order_dates_checkout_field( $checkout ) {
	global $woocommerce,$orderRate;
	$customOrderPermit = get_option('customOrderPermit');	
	$dayDifference = get_option('dayDifference');	
	$dt = date('Y-m-d', strtotime("+".$dayDifference." days"));
	$items = $woocommerce->cart->get_cart();
    foreach($items as $item => $values) { 
        $_product = $values['data']->post; 
		$price = get_post_meta($values['product_id'] , '_price', true);
        echo "<b>".$_product->post_title."</b>&nbsp;&nbsp;";
		echo '<input type="text" class="deliveryDate" id="datepicker'.$_product->ID.'" name="customDatePicker[]" placeholder="Choose dates">'; ?> 
		<script type="text/javascript">	
			jQuery(document).ready(function() {
				jQuery("#datepicker<?php echo $_product->ID;?>").datepicker({ 
					dateFormat: 'dd/mm/yy', 
					minDate: new Date('<?php echo $dt; ?>'),
				});
			});
		</script>	
		<?php 
    } 
}
function multi_order_generator($order_id) {
	$customOrderPermit = get_option('customOrderPermit');	
    $order = new WC_Order( $order_id );
	$items = $order->get_items();
	$count =0;
	$productID = array();
	$productQty = array();
	$deliveryDate = get_post_meta( $order_id, 'Delivery_Dates',true);
	$dateArray = explode(',',$deliveryDate);
	foreach($items as $order_item_id => $item){
		if($count >0){
			$productName = get_the_title($item['product_id']);
			$orderValueID = createNewOrderAndRecordPayment($order_id);
			addProductWithOrder($orderValueID,$item['product_id'],$item['qty']);
			if($customOrderPermit == 1){
					update_post_meta($orderValueID,'_Delivery_Date',$dateArray[$count]);
			}
			wc_delete_order_item($order_item_id);
		}else{
			if($customOrderPermit == 1){
					update_post_meta($order_id,'_Delivery_Date',$dateArray[0]);
			}
		}
		$count++;
	}
}
function addProductWithOrder($orderID,$productID,$productQty){
	$ProOrderItem = new WC_Order($orderID);	
	$ProOrderItem->add_product(get_product($productID),$productQty); 
}
function createNewOrderAndRecordPayment($orderID) {
    global $wpdb;
    global $woocommerce;
    $original_order = new WC_Order($orderID);
    $currentUser = wp_get_current_user();
    //1 Create Order
    $order_data =  array(
        'post_type'     => 'shop_order',
        'post_status'   => 'publish',
        'ping_status'   => 'closed',
        'post_author'   => $currentUser->ID, 
		'post_excerpt' => $original_order->customer_message, 
        'post_password' => uniqid( 'order_' )   // Protects the post just in case
    );
    $order_id = wp_insert_post( $order_data, true );
    if ( is_wp_error( $order_id ) ){
        $msg = "Unable to create order:" . $order_id->get_error_message();;
        throw new Exception( $msg );
    } else {
        $order = new WC_Order($order_id);
        //2 Update Order Header	
        update_post_meta( $order_id, '_order_shipping', get_post_meta($orderID, '_order_shipping', true) );
        update_post_meta( $order_id, '_order_discount', get_post_meta($orderID, '_order_discount', true) );
        update_post_meta( $order_id, '_cart_discount',  get_post_meta($orderID, '_cart_discount', true) );
        update_post_meta( $order_id, '_order_tax',              get_post_meta($orderID, '_order_tax', true) );
        update_post_meta( $order_id, '_order_shipping_tax',     get_post_meta($orderID, '_order_shipping_tax', true) );
        update_post_meta( $order_id, '_order_total',            get_post_meta($orderID, '_order_total', true) );

        update_post_meta( $order_id, '_order_key',              'wc_' . apply_filters('woocommerce_generate_order_key', uniqid('order_') ) );
        update_post_meta( $order_id, '_customer_user',          get_post_meta($orderID, '_customer_user', true) );
        update_post_meta( $order_id, '_order_currency',         get_post_meta($orderID, '_order_currency', true) );
        update_post_meta( $order_id, '_prices_include_tax',     get_post_meta($orderID, '_prices_include_tax', true) );
        update_post_meta( $order_id, '_customer_ip_address',    get_post_meta($orderID, '_customer_ip_address', true) );
        update_post_meta( $order_id, '_customer_user_agent',    get_post_meta($orderID, '_customer_user_agent', true) );
        
		//3 Add Billing Fields

        update_post_meta( $order_id, '_billing_city',           get_post_meta($orderID, '_billing_city', true));
        update_post_meta( $order_id, '_billing_state',          get_post_meta($orderID, '_billing_state', true));
        update_post_meta( $order_id, '_billing_postcode',       get_post_meta($orderID, '_billing_postcode', true));
        update_post_meta( $order_id, '_billing_email',          get_post_meta($orderID, '_billing_email', true));
        update_post_meta( $order_id, '_billing_phone',          get_post_meta($orderID, '_billing_phone', true));
        update_post_meta( $order_id, '_billing_address_1',      get_post_meta($orderID, '_billing_address_1', true));
        update_post_meta( $order_id, '_billing_address_2',      get_post_meta($orderID, '_billing_address_2', true));
        update_post_meta( $order_id, '_billing_country',        get_post_meta($orderID, '_billing_country', true));
        update_post_meta( $order_id, '_billing_first_name',     get_post_meta($orderID, '_billing_first_name', true));
        update_post_meta( $order_id, '_billing_last_name',      get_post_meta($orderID, '_billing_last_name', true));
        update_post_meta( $order_id, '_billing_company',        get_post_meta($orderID, '_billing_company', true));

        //4 Add Shipping Fields

        update_post_meta( $order_id, '_shipping_country',       get_post_meta($orderID, '_shipping_country', true));
        update_post_meta( $order_id, '_shipping_first_name',    get_post_meta($orderID, '_shipping_first_name', true));
        update_post_meta( $order_id, '_shipping_last_name',     get_post_meta($orderID, '_shipping_last_name', true));
        update_post_meta( $order_id, '_shipping_company',       get_post_meta($orderID, '_shipping_company', true));
        update_post_meta( $order_id, '_shipping_address_1',     get_post_meta($orderID, '_shipping_address_1', true));
        update_post_meta( $order_id, '_shipping_address_2',     get_post_meta($orderID, '_shipping_address_2', true));
        update_post_meta( $order_id, '_shipping_city',          get_post_meta($orderID, '_shipping_city', true));
        update_post_meta( $order_id, '_shipping_state',         get_post_meta($orderID, '_shipping_state', true));
        update_post_meta( $order_id, '_shipping_postcode',      get_post_meta($orderID, '_shipping_postcode', true));

        //6 Copy shipping items and shipping item meta from original order
        $original_order_shipping_items = $original_order->get_items('shipping');
        foreach ( $original_order_shipping_items as $original_order_shipping_item ) {
            $item_id = wc_add_order_item( $order_id, array(
                'order_item_name'       => $original_order_shipping_item['name'],
                'order_item_type'       => 'shipping'
            ) );
            if ( $item_id ) {
                wc_add_order_item_meta( $item_id, 'method_id', $original_order_shipping_item['method_id'] );
                wc_add_order_item_meta( $item_id, 'cost', wc_format_decimal( $original_order_shipping_item['cost'] ) );
            }
        }
        
        // Store coupons
        $original_order_coupons = $original_order->get_items('coupon');
        foreach ( $original_order_coupons as $original_order_coupon ) {
            $item_id = wc_add_order_item( $order_id, array(
                'order_item_name'       => $original_order_coupon['name'],
                'order_item_type'       => 'coupon'
            ) );
            // Add line item meta
            if ( $item_id ) {
                wc_add_order_item_meta( $item_id, 'discount_amount', $original_order_coupon['discount_amount'] );
            }
        }

        //Payment Info
        update_post_meta( $order_id, '_payment_method',         get_post_meta($orderID, '_payment_method', true) );
        update_post_meta( $order_id, '_payment_method_title',   get_post_meta($orderID, '_payment_method_title', true) );
        update_post_meta( $order->id, 'Transaction ID',         get_post_meta($orderID, 'Transaction ID', true) );
        $order->payment_complete();

        //6 Set Order Status to processing to trigger initial emails to end user and vendor
        $order->update_status('processing'); 
    }
	return $order_id;
}
function woo_order_extra_columns($columns)
{
   $newcolumns = array(
		"cb"       		=> "<input type  = \"checkbox\" />",
		"delivery"    => esc_html__('Delivery', 'woocommerce'),
	);
 	$columns = array_merge($newcolumns, $columns);
	return $columns;
}
function woo_order_extra_columns_content($column)
{
	global $post;
	$order_id = $post->ID;
	switch ($column)
	{
		case "delivery":
		$daliveryDate = get_post_meta($order_id,'_Delivery_Date',true);
		if ( empty( $daliveryDate ) )
				echo __( '-' );
		else
			printf( __( '%s' ), $daliveryDate );
		break;
	}
}

function my_sortable_cake_column( $columns ) {
$columns['delivery'] = 'delivery';
    return $columns;
}