<?php
/*
 * Plugin Name: Axaipay - WooCommerce Payment Gateway
 * Description: Payment gateway to accept payments on your woocommerce store.
 * Author: Axaipay
 * Author URI: https://axaipay.my
 * Version: 4.0.3
 * Text Domain: axaipay-gateway
 */

//if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;


add_action( 'plugins_loaded', 'initialize_axaipay_gateway_class', 0);

function initialize_axaipay_gateway_class(){
    if (!class_exists('WC_Payment_Gateway'))
        return; // if the WC payment gateway class 

    include(plugin_dir_path(__FILE__) . 'class-gateway.php');
}

add_filter( 'woocommerce_payment_gateways', 'add_axaipay_gateway_class' );

function add_axaipay_gateway_class( $gateways ) {
    $gateways[] = 'Axaipay_Gateway'; // payment gateway class name
    return $gateways;
}


/**
 * Custom function to declare compatibility with cart_checkout_blocks feature 
*/
function declare_axaipay_cart_checkout_blocks_compatibility() {
    // Check if the required class exists
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}

// Hook the custom function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', 'declare_axaipay_cart_checkout_blocks_compatibility');


// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action( 'woocommerce_blocks_loaded', 'woocommerce_axaipay_gateway_block_support' );

/**
 * Custom function to register a payment method type

 */
function woocommerce_axaipay_gateway_block_support() {
    // Check if the required class exists
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    // Include the custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . 'class-block.php';

    // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            // Register an instance of My_Custom_Gateway_Blocks
            $payment_method_registry->register( new Axaipay_Gateway_Blocks );
        }
    );
}

/**
 * Add meta data to an order from a payment gateway plugin.
 *
 * @param int $order_id The ID of the order.
 * @param array $data    Additional data to be stored as meta data.
 */
function add_custom_meta_to_order($order_id, $data) {
    // Update or add meta data to the order
    $nonce = substr(str_shuffle(MD5(microtime())), 0, 12);
            
    update_post_meta($order_id, '_' . 'nonce', $nonce);
}

// Hook into the payment processing completion or any relevant action
add_action('woocommerce_payment_complete', 'custom_process_payment_completion');

/**
 * Retrieve custom meta data from an order.
 *
 * @param int $order_id The ID of the order.
 * @param string $key   The meta data key.
 * @return mixed        The value of the meta data or false if not found.
 */
function get_custom_meta_from_order($order_id, $key) {
    return get_post_meta($order_id, '_' . $key, true);
}

?>