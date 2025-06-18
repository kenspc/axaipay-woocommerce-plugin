<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Axaipay_Gateway_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'axaipay_gateway';// your payment gateway name

    public function initialize() {
        $this->settings = get_option( 'woocommerce_axaipay_gateway_settings', [] );
        $this->gateway = new Axaipay_Gateway();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {

        wp_register_script(
            'axaipay_gateway-blocks-integration',
            plugin_dir_url(__FILE__) . 'checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
            ],
            null,
            true
        );
        
        return [ 'axaipay_gateway-blocks-integration' ];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'enabled' => $this->gateway->enabled,
            'test_mode' => $this->gateway->test_mode,
            'merchant_id' => $this->gateway->merchant_id,
            'signing_key' => $this->gateway->signing_key,
        ];
    }

}
?>
