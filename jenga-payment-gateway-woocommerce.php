<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 11/26/18
 * Time: 3:27 PM
 */

class Jenga_Payment_Gateway extends  WC_Payment_Gateway {

    public $jpgw_username;
    public $jpgw_password;
    public $jpgw_grant_type = 'password';
    public $jpgw_api_key;
    public $jpgw_token;
    public $jpgw_amount;
    public $jpgw_currency;
    public $jpgw_outletcode;
    public $jpgw_merchantname;
    public $jpgw_cstnames;
    public $jpgw_totalamount;
    public $environment;
    public $configured_token_environment;
    public $configured_launch_environment;


    function __construct() {

        // global ID
        $this->id = "jpgw";

        // Show Title
        $this->method_title = __( "Jenga Payment Gateway", 'jpgw' );

        // Show Description
        $this->method_description = __( "Jenga Payment Gateway Plug-in for WooCommerce", 'jpgw' );

        // vertical tab title
        $this->title = __( "Jenga Payment Gateway", 'jpgw' );

        // Path to Payment Gateway Icon
        $this->icon = apply_filters( 'woocommerce_jpgw_icon', plugins_url( 'jpwpesa.png', __FILE__ ) );;

        $this->has_fields = false;

        $this->jpgw_username = $this->get_option( 'username' );
        $this->jpgw_password = $this->get_option( 'password' );
        $this->jpgw_grant_type = $this->get_option( 'grant_type' );
        $this->jpgw_api_key = $this->get_option( 'api_key' );
        $this->jpgw_outletcode = $this->get_option('outletCode');
        $this->jpgw_merchantname = $this->get_option('merchantName');
        $this->environment = $this->get_option('environment');

        // setting defines
        $this->init_form_fields();

        // load time variable setting
        $this->init_settings();

        // Turn these settings into variables we can use
        foreach ( $this->settings as $setting_key => $value ) {
            $this->$setting_key = $value;
        }

        // further check of SSL if you want
        add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );

        // Add receipt page
        add_action( 'woocommerce_receipt_jpgw', array( $this, 'receipt_page' ));

        // Add custom thankyou page content on woocommerce_thankyou action trigger
        add_action( 'woocommerce_thankyou', array( $this, 'custom_content_thankyou'));

        // Insert Jenga Payment Transactions on woocommerce_thankyou action trigger
        add_action( 'woocommerce_thankyou', array( $this,'woojpgw_insert_transaction'));


        // Save settings
        if ( is_admin() ) {
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

        }

    } // Here is the  End __construct()

    // function to Add custom content on thankyou page
    function custom_content_thankyou($order_id)
    {

        global $woocommerce;

        // Check payment status
        if (isset($_GET['status']) == 'paid') {

            $status = $_GET['status'];
            $transactionId = $_GET['transactionId'];
            $date= $_GET['date'];
            $desc= $_GET['desc'];
            $amount= $_GET['amount'];
            $merchantOrderRef = $_GET['merchantOrderRef'];

            $convertedAmount = number_format((float)$amount, 2, '.', '');
            $converted_desc = strtoupper($desc);

            $order = new WC_Order( $order_id );
            // Complete payment
            $order->payment_complete();

            // Add Order Note with payment order details
            $order->add_order_note('Order  with transaction id'.' '.$transactionId.' '.'has successfully been paid', true);

            // Reduce stock levels
            $order->reduce_order_stock();

            // Remove cart
            $woocommerce->cart->empty_cart();

            $_SESSION['status'] = $status;
            $_SESSION['tranid'] = $transactionId;
            $_SESSION['date'] = $date;
            $_SESSION['desc'] = $converted_desc;
            $_SESSION['amount'] = $convertedAmount;
            $_SESSION['orderid'] = $merchantOrderRef;

            // Add custom section  content to thankyou page
            ?>
            <h2>Payment Details</h2>
            <table class="woocommerce-table shop_table gift_info">
                <tbody>
                <tr>
                    <th>Status</th>
                    <td><?php echo $status; ?></td>
                </tr>
                <tr>
                    <th>Transaction ID</th>
                    <td><?php echo $transactionId; ?></td>
                </tr>
                <tr>
                    <th>Payment Method</th>
                    <td><?php echo strtoupper($desc); ?></td>
                </tr>
                <tr>
                    <th>Paid Amount</th>
                    <td><?php echo number_format((float)$amount, 2, '.', ''); ?></td>
                </tr>

                <tr>
                    <th>Payment Date</th>
                    <td><?php echo date('F j, Y', strtotime($date)); ?></td>
                </tr>
                </tbody>
            </table>
            <?php


        }

    }


    // Function to insert Jenga Payment Gateway Transactions
    function woojpgw_insert_transaction()

    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'jpgw_trx';

        $wpdb->insert(
            $table_name,
            array(

                'order_id' => $_SESSION['orderid'],
                'status'=>  $_SESSION['status'],
                'trx_time' => $_SESSION['date'],
                'transaction_id' => $_SESSION['tranid'],
                'payment_method' => $_SESSION['desc'],
                'amount' => $_SESSION['amount'],
            )
        );


    }

    // Initialize custom jenga payment plugin settings page fields
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'		=> __( 'Enable / Disable', 'jpgw' ),
                'label'		=> __( 'Enable this payment gateway', 'jpgw' ),
                'type'		=> 'checkbox',
                'default'	=> 'no',
            ),

             'environment' =>  array(
                'title'		=> __( 'Environment', 'jpgw' ),
                 'label' 	=> 'Environment Setup',
                 'type' 	=> 'select',
                 'default'	=> 'Sandbox',
                 'options'	=> array(
                    'Sandbox' 	=> 'Sandbox',
                    'Production' => 'Production',
                ),
            ),

            'title' => array(
                'title'		=> __( 'Title', 'jpgw' ),
                'type'		=> 'text',
                'desc_tip'	=> __( 'Payment title of checkout process.', 'jpgw' ),
                'default'	=> __( 'Jenga JPGW', 'jpgw' ),
            ),
            'description' => array(
                'title'		=> __( 'Description', 'jpgw' ),
                'type'		=> 'textarea',
                'desc_tip'	=> __( 'Payment description of checkout process.', 'cwoa-authorizenet-aim' ),
                'default'	=> __( 'Jenga Payment Gateway.', 'cwoa-authorizenet-aim' ),
                'css'		=> 'max-width:450px;'
            ),
            'username' => array(
                'title'		=> __( 'Username', 'jpgw' ),
                'type'		=> 'text',
                'desc_tip'	=> __( 'This is the username found when you register in Jenga HQ Under view Keys Menu .', 'jpgw' ),
            ),
            'password' => array(
                'title'		=> __( 'Password', 'jpgw' ),
                'type'		=> 'password',
                'desc_tip'	=> __( 'This is the password found when you register in Jenga HQ Under view Keys Menu .', 'jpgw'),
            ),

            'outletCode' => array(
                'title'		=> __( 'Outlet Code', 'jpgw' ),
                'type'		=> 'text',
                'desc_tip'	=> __( 'This is the outlet code  which identifies your business which you can create in Jenga HQ.', 'jpgw'),
            ),

            'merchantName' => array(
                'title'		=> __( 'Merchant Name', 'jpgw' ),
                'type'		=> 'text',
                'desc_tip'	=> __( 'This is the merchant name assigned to you when you register in Jenga HQ.', 'jpgw'),
            ),

            'api_key' => array(
                'title'		=> __( 'API Key', 'jpgw' ),
                'type'		=> 'text',
                'desc_tip'	=> __( 'This is the api key found when you register in Jenga HQ Under view Keys Menu .', 'jpgw'),
            ),

        );
    }

    //  Function to process payments
    public function process_payment ($order_id) {

        global $woocommerce;

        $order = new WC_Order( $order_id );

        $this->jpgw_cstnames = $order->get_billing_first_name();

        $this->jpgw_totalamount = $order->get_total();


        $_SESSION["orderID"] = $order->get_id();

        // Redirect to checkout/pay page
        $checkout_url = $order->get_checkout_payment_url(true);

        $checkout_edited_url = $checkout_url."&transactionType=checkout";

        return array(

            'result' => 'success',

            'redirect' => add_query_arg('order', $order->get_id(),

                add_query_arg('key', $order->order_key, $checkout_edited_url))

        );

    }

 // Generate token required to launch checkout
    public function tokenInitializer () {

        if ($this->environment == 'Sandbox'){
            $this->configured_token_environment = 'https://api-test.equitybankgroup.com/v1/token';
            $this->configured_launch_environment = 'https://api-test.equitybankgroup.com/v2/checkout/launch';
        } else {
            $this->configured_token_environment = 'https://api.equitybankgroup.com/v1/token';
            $this->configured_launch_environment = 'https://api.equitybankgroup.com/v2/checkout/launch';
        }


        $endpoint = $this->configured_token_environment;
        // Payload Details
        $payload = array(
            'username' => $this->jpgw_username,
            'password' 	=> $this->jpgw_password,
            'grant_type' => $this->jpgw_grant_type,
            'merchantCode' 	=> $this->jpgw_username
        );
        // Wordpress method to perfrom the http post
        $response = wp_remote_post( $endpoint, array(
            'method     '    => 'POST',
            'headers' => array("Content-type" => "application/x-www-form-urlencoded;charset=UTF-8",
                'Authorization' => $this->jpgw_api_key
                ),
            'body'      => http_build_query( $payload ),
            'timeout'   => 90,
            'sslverify' => false,
        ) );

      //  return $response;
        if ( is_wp_error( $response ) )
            throw new Exception( __( 'There is an issue connecting to the payment gateway. Sorry for the inconvenience.', 'jpgw' ) );

        if ( empty( $response['body'] ) )
            throw new Exception( __( 'Jenga PGW Response did not get any data.', 'jpgw' ));

        // get body response while get not error
        $response_body = wp_remote_retrieve_body( $response );

        foreach ( preg_split( "/\r?\n/", $response_body ) as $line ) {
            $resp = explode( "|", $line );
        }
        // decode the response object to obtain the token
        $respobj = json_decode($resp[0]);

        $arr = (array)$respobj;
        $tokeni = $arr['payment-token'];

        $valtoken =  explode( "{", $tokeni );
        // return the token value
        return $valtoken[0];

    }

    public function receipt_page( $order_id ) {

        echo $this->woompesa_generate_iframe( $order_id );

    }


    public function woompesa_generate_iframe( $order_id ) {

        global $woocommerce;

        $order = new WC_Order( $order_id );

        $this->jpgw_cstnames = $order->get_billing_first_name(). ' '.$order->get_billing_last_name();

        $this->jpgw_totalamount = (($order->get_total() * 100) / 100);

        $token = $this->tokenInitializer();

        /**

         * Make the payment here by clicking on pay button and confirm by clicking on complete order button

         */

        if ($_GET['transactionType']=='checkout') {

            echo "<h4>Payment Instructions:</h4>";

            echo "

		  1. Click on the <b>Pay</b> button in order to initiate the Jenga Payment Gateway.<br/>

		  2. This will redirect you to the Gateway Page where you will be able to make payments.<br/>

    	  3. After succesful payments, you will be redirected back to this site<br/>     	

    	  4. You will then be able to see the payment details you made for this order<br/>";


            echo "<br/>";?>

<!--            <form action="https://api-test.equitybankgroup.com/v2/checkout/launch" method="post">-->
            <form action="<?php echo $this->configured_launch_environment ?>" method="post">
                <input type="hidden" name="token" value="<?php echo $token ?>">
                <input type="hidden" name="amount" value="<?php echo $this->jpgw_totalamount * 100 ?>">
                <input type="hidden" name="currency" value="<?php echo $order->get_currency() ?>">
                <input type="hidden" name="orderReference" value="<?php echo $order->get_order_number() ?>">
                <input type="hidden" name="popupLogo" value="https://pixy.org/ph/transp/000000/frame/cross/480x320.png">
                <input type="hidden" name="merchantCode" value="<?php echo $this->jpgw_username ?>">
                <input type="hidden" name="outletCode" value="<?php echo $this->jpgw_outletcode ?>">
                <input type="hidden" name="merchant" value="<?php echo $this->jpgw_merchantname  ?>">
                <input type="hidden" name="expiry" value="2025-02-17T19:00:00">
                <input type="hidden" name="custName" value="<?php echo $this->jpgw_cstnames ?>">
                <input type="hidden" name="ez1_callbackurl" value="<?php echo $this->get_return_url( $order ) ?>">
                <input type="hidden" name="ez2_callbackurl" value="<?php echo $this->get_return_url( $order ) ?>">
                <button type="submit" id="submit-form"> Proceed to Pay</button>

            </form>

            <?php

            echo "<br/>";

            // Mark as on-hold (we're awaiting the cheque)


        }


    }

  // Validate fields
    public function validate_fields() {
        return true;
    }

    // Check for ssl and if not enabled notify user
    public function do_ssl_check()
    {
        if ($this->enabled == "yes") {
            if (get_option('woocommerce_force_ssl_checkout') == "no") {
                echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>"), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>";
            }
        }

    }




}