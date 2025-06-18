<?php 
class Axaipay_Gateway extends WC_Payment_Gateway {
    // plugin code here

    const TEST_GATEWAY_URL = 'https://staging.axaipay.my/';
    const LIVE_GATEWAY_URL = 'https://secured.axaipay.my/';
    const CHANNEL = 'WOOCOMMERCE_V3';

    /*
     * Class constructor
     */
    public function __construct() {
        $this->plugin_dir = plugin_dir_url(__FILE__);

        $this->id = 'axaipay_gateway'; // payment gateway ID
        $this->icon = apply_filters( 'woocommerce_axaipay_gateway_icon', '' );
        $this->has_fields = true; // for custom form
        $this->title = __( 'Axaipay', 'axaipay-gateway' ); // vertical tab title
        $this->description = __( 'Payment gateway to accept payments on your woocommerce store.', 'axaipay-gateway' ); // vertical tab title
        $this->method_title = __( 'Axaipay', 'axaipay-gateway' ); // payment method name
        $this->method_description = __( 'Payment gateway to accept payments on your woocommerce store.', 'axaipay-gateway' ); // payment method description

        $this->supports = array( 'default_axaipay_gateway_form' );

        // load backend options fields
        $this->init_form_fields();

        // load the settings.
        $this->init_settings();
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->enabled = $this->get_option( 'enabled' );
        $this->test_mode = 'yes' === $this->get_option( 'test_mode' );

        if ($this->test_mode) {
            $this->merchant_id = $this->get_option( 'test_merchant_id' );
            $this->signing_key = $this->get_option( 'test_signing_key' );
        } else {
            $this->merchant_id = $this->get_option( 'live_merchant_id' );
            $this->signing_key = $this->get_option( 'live_signing_key' );
        }

        // Action hook to saves the settings
        if( is_admin()) {
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        add_action( 'woocommerce_api_axaipay_gateway_webhook', array( $this, 'webhook' ) );     
        add_action( 'woocommerce_api_axaipay_gateway_result', array( $this, 'result' ) );     
    }

    /*
    * Plugin options and setting fields
    */
    public function init_form_fields(){
        $this->form_fields = array(
            'enabled' => array(
                'title'       => __( 'Enable/Disable', 'axaipay-gateway' ),
                'label'       => __( 'Enable Axaipay Gateway', 'axaipay-gateway' ),
                'type'        => 'checkbox',
                'description' => __( 'This enable the Axaipay gateway which allow to accept payment through credit card/debit card, FPX, and e-wallet.', 'axaipay-gateway' ),
                'default'     => 'no',
                'desc_tip'    => true
            ),
            'title' => array(
                'title'       => __( 'Title', 'axaipay-gateway'),
                'type'        => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'axaipay-gateway' ),
                'default'     => __( 'Axaipay', 'axaipay-gateway' ),
                'desc_tip'    => true,
            ),
            'description' => array(
                'title'       => __( 'Description', 'axaipay-gateway' ),
                'type'        => 'textarea',
                'description' => __( 'This controls the description which the user sees during checkout.', 'axaipay-gateway' ),
                'default'     => __( 'Pay with your credit card, FPX, or MCash via Axaipay gateway.', 'axaipay-gateway' ),
            ),
            'test_mode' => array(
                'title'       => __( 'Test mode', 'axaipay-gateway' ),
                'label'       => __( 'Enable Test Mode', 'axaipay-gateway' ),
                'type'        => 'checkbox',
                'description' => __( 'Place the payment gateway in test mode using test API keys.', 'axaipay-gateway' ),
                'default'     => 'yes',
                'desc_tip'    => true,
            ),
            'test_merchant_id' => array(
                'title'       => __( 'Test Merchant ID', 'axaipay-gateway' ),
                'type'        => 'text'
            ),
            'test_signing_key' => array(
                'title'       => __( 'Test Signing Key', 'axaipay-gateway' ),
                'type'        => 'text',
            ),
            'live_merchant_id' => array(
                'title'       => __( 'Live Merchant ID', 'axaipay-gateway' ),
                'type'        => 'text'
            ),
            'live_signing_key' => array(
                'title'       => __( 'Live Signing Key', 'axaipay-gateway' ),
                'type'        => 'text',
            ),
        );

    }

    /*
        *  Payment gateway form fields
        */
    public function payment_fields() {

        if ( $this->description ) {
            if ( $this->test_mode ) {
                $this->description .= ' Test mode is enabled. You can use the dummy credit card numbers to test it.';
            }
            echo wpautop( wp_kses_post( $this->description ) );
        }
                
    }

    /*
     * Process the payments here
     */
    public function process_payment( $order_id ) {

        global $woocommerce;
        //$logger = wc_get_logger();

        $payment_url = ( $this->test_mode ? self::TEST_GATEWAY_URL : self::LIVE_GATEWAY_URL ) . 'gateway/v1/payment';

        // get order details
        $order = wc_get_order( $order_id );

        // Add meta to secure payment update
        add_custom_meta_to_order($order_id, []);
        $nonce = get_custom_meta_from_order($order_id, 'nonce');
        //$logger->debug('created nonce: ' . $nonce);
        //wc_add_order_item_meta($order_id,'ipn_nonce',$nonce, true);
    
        // Get the WP_User Object instance
        //$user = $order->get_user();

        // Get the Customer billing email
        $billing_email  = $order->get_billing_email();

        // Get the Customer billing phone
        $billing_phone  = $order->get_billing_phone();

        // Customer billing information details
        $billing_first_name = $order->get_billing_first_name();
        $billing_last_name  = $order->get_billing_last_name();
        $billing_company    = $order->get_billing_company();
        $billing_address_1  = $order->get_billing_address_1();
        $billing_address_2  = $order->get_billing_address_2();
        $billing_city       = $order->get_billing_city();
        $billing_state      = $order->get_billing_state();
        $billing_postcode   = $order->get_billing_postcode();
        $billing_country    = $order->get_billing_country();

        $customer_name = trim($billing_first_name . ' ' . $billing_last_name);

        $order_description  = 'Order #' . $order_id;
        $backend_url        = add_query_arg( [ 'wc-api' => 'axaipay_gateway_webhook', 'order_id' => $order_id, 'nonce' => $nonce ], home_url( '/' ) );
        $cancel_url         = wc_get_cart_url();
        //$cancel_url         = WC()->cart->get_checkout_url();
        //$redirect_url       = $this->get_return_url( $order );
        $redirect_url        = add_query_arg( [ 'wc-api' => 'axaipay_gateway_result' ], home_url( '/' ) );
        

        $string_to_sign     =   $backend_url . 
                                $cancel_url .
                                self::CHANNEL . 
                                $billing_email . // customerEmail
                                $customer_name . // customerName
                                $this->merchant_id . // mchtId
                                $order_id . // mchtTxnId
                                $order_description . // orderDescription
                                $redirect_url . 
                                $order->total  // txnAmount
                                ;
        // Generate signature
        $digest = hash_hmac('sha512', $string_to_sign, $this->signing_key, true);
        //$header = 'Authorization: HmacSHA512 ' . $api_key . ':' . $nonce . ':' . base64_encode($digest);
        $signature = base64_encode($digest);

        $arr_order_params = [
            'c' => self::CHANNEL, // url of payment
            'u' => $payment_url, // url of payment
            'e' => $billing_email, // email of customer
            'm' => $this->merchant_id, // merchant id
            'n' => $customer_name, // name of customer
            'd' => $order_description, // description of order
            'i' => $order_id, // id of order
            'a' => $order->total, // amount
            'r' => $redirect_url, // redirect url,
            //'b' =>  get_bloginfo('url')."/wc-api/axaipay_webhook/?nonce=".$nonce."&order_id=".$order_id, // backend url,
            'b' => $backend_url, // backend url,
            'f' => $cancel_url, // failed transaction url
            's' => $signature // signature
        ];

        // encode to json string
        $json_order_params = json_encode($arr_order_params);

        // encode to base64
        $encoded_order_params = base64_encode($json_order_params);

        $url = home_url( '/' ) . "wp-content/plugins/axaipay-gateway/secured.php?order=$encoded_order_params";

        //echo $this->get_return_url( $order ) . '\n' . wc_get_cart_url() . '\n' 
        //. get_bloginfo('url')."/wc-api/axaipay_webhook/?nonce=".$nonce."&order_id=".$order_id;
        //die();
        
        return [
            'result'   => 'success',
            'redirect' => $url
        ];   
    }

    /*
     * Webhook to update payment status
     */
    public function webhook() {
        $status = 1;
        //$logger = wc_get_logger();

        //$logger->debug('webhook');
        $order_id = isset($_GET['order_id']) ? $_GET['order_id'] : null;
        $status_id = isset($_POST['statusId']) ? $_POST['statusId'] : null;
        $transaction_id = isset($_POST['trxnId']) ? $_POST['trxnId'] : null;
        $nonce = isset($_GET['nonce']) ? $_GET['nonce'] : null;
        $signature = isset($_POST['signature']) ? $_POST['signature'] : null;

        //$logger->debug('order_id: '. $order_id);
        if (is_null($order_id)) {
            //$logger->debug('order_id null');
            header( 'HTTP/1.1 400 Missing Order ID');
            return;
        }

        //$logger->debug('nonce: ' . $nonce);
        if (is_null($nonce)) {
            //$logger->debug('nonce null');
            header( 'HTTP/1.1 400 Missing Nonce');
            return;
        }
        if (is_null($signature)) {
            //$logger->debug('signature null');
            header( 'HTTP/1.1 400 Missing Signature');
            return;
        }

        $existing_nonce = get_custom_meta_from_order($order_id, 'nonce');
        //$logger->debug('existing_nonce: ' . $existing_nonce);
        if ($existing_nonce!=$nonce) {
            //$logger->debug('Invalid Nonce');
            header( 'HTTP/1.1 400 Invalid Nonce');
            return;   
        }

        $string_to_sign    =   $this->merchant_id . // mchtId
                                $nonce . // nonce
                                $order_id . // order_id
                                $status_id . // paid / failed
                                $transaction_id // Axaipay transaction ID
                                ;
        //$logger->debug('string_to_sign: '. $string_to_sign);
        // Generate signature
        $encoded = $this->generate_signature($string_to_sign);

        // Compare signatures
        if ($signature != $encoded) {
            header( 'HTTP/1.1 400 Invalid Signature');
            return;
        } 

        //$logger->debug('status_id: ' . $status_id);
        if ($status_id == 11) {
            //$logger->debug('status_id: 11');
            // Payment successful and can be confirmed
            $order = wc_get_order( $order_id );
            $order->payment_complete();
            $order->add_order_note('Paid via Axaipay with transaction Id of ' . $transaction_id);
            wc_reduce_stock_levels($order_id);
        } else if ($status_id == 22 || $status_id == 23) { // Failed or timeout
            //$logger->debug('status_id: 22');
            $order = wc_get_order( $order_id );
            $order->update_status('failed');
            $order->add_order_note('Failed to pay via Axaipay with transaction Id of ' . $transaction_id);
        } else if ($status_id == 3) { // pending authorization
            $order = wc_get_order( $order_id );
            $order->update_status('on-hold');
            $order->add_order_note('Payment has been initiated via Axaipay with transaction Id of ' . $transaction_id . '. Pending authorization by the customer.');
        }

        header( 'HTTP/1.1 200 OK' );

        return;
    }

    /*
     * Process transaction result sent by Axaipay
     */
    public function result() {
        $order_id           =   $_POST['mchtTxnId'];

        $string_to_sign     =   $_POST['mchtId'] . 
                                $order_id . 
                                $_POST['trxnAcquirer'] . 
                                $_POST['trxnAmount'] . 
                                $_POST['trxnBankName'] . 
                                $_POST['trxnFpxMethod'] . 
                                $_POST['trxnFpxType'] . 
                                $_POST['trxnId'] .  
                                $_POST['trxnPaymentMethod'] . 
                                $_POST['trxnStatus'] . 
                                $_POST['trxnTime'];

        $signature = $_POST['signature'] ;

        // Generate signature
        $encoded = $this->generate_signature($string_to_sign);

        // Validate signature
        if ($signature == $encoded) {
            // Check its status
            if ($_POST['trxnStatus'] == 11) { // successful
                // get order
                $order = wc_get_order($order_id);
                $redirect_url = $this->get_return_url($order);
            } else if ($_POST['trxnStatus'] == 3) { // pending authorization
                wc_add_notice('Pending payment authorization', 'notice' );
                $redirect_url = $this->get_return_url($order);
            } else {
                wc_add_notice('Payment not successful', 'error' );
                $redirect_url = wc_get_cart_url();
            }
        } else {
            wc_add_notice('Unknown payment status. Please contact us.', 'error' );
            $redirect_url = wc_get_cart_url();
        }
        wp_redirect($redirect_url);
        exit();
    }


    /**
     * Hook function that will be auto-called by WC on payment page to show button's icon for this payment gateway button
     * @return string Gateway payment button html tag to render icon images.
     */
    public function get_icon()
    {
        $image_url = $this->plugin_dir.'/assets/axai-fai.png';
        $image_tag = '<img src="'.$image_url.'" alt="Axaipay" style="max-height: 2.1em; max-width: 4.8em;"/> ';
        $image_tag_after_filter = apply_filters( 'midtrans_gateway_icon_before_render', $image_tag);
        $image_tag_after_filter = apply_filters('woocommerce_gateway_icon', $image_tag_after_filter, $this->id);
        return $image_tag_after_filter;
    }

    private function generate_signature( $string_to_sign ) {
        $digest = hash_hmac('sha512', $string_to_sign, $this->signing_key, true);
        return base64_encode($digest);
    }

}
?>