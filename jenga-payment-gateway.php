<?php
/**
 * Created by PhpStorm.
 * User: denis
 * Date: 11/19/18
 * Time: 2:38 PM
 */

/*
Plugin Name: Jenga Payment Gateway Wordpress Plugin
Plugin URI: https://github.com/finserveafrica/pgw_woocommerce_plugin/
Description: Jenga Payment Gateway WooCommerce custom plugin.
Version: 1.0
Author: Finserve Africa
Author URI: https://www.finserve.africa/
License: GPL
*/


// Exit if accessed directly.
if ( !defined( 'ABSPATH' ) ){
    exit;
}

// Check if  WP_List_Table class exist and if not require it
if ( !class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/**
 * Installation hook callback creates plugin settings
 */

// Hook called when plugin is activated to create jenga payment transactions table in the database
register_activation_hook( __FILE__, 'jpgw_jpgwtrx_install' );
function jpgw_jpgwtrx_install()
{
// Create Table for Jenga PGW Transactions
    global $wpdb;

    global $trx_db_version;

    $trx_db_version = '1.0';

    $table_name = $wpdb->prefix .'jpgw_trx';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (

		id mediumint(9) NOT NULL AUTO_INCREMENT,

		order_id varchar(150) DEFAULT '' NULL,

		status varchar(150) DEFAULT '' NULL,

		trx_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,

		transaction_id varchar(150) DEFAULT '' NULL,

		payment_method varchar(150) DEFAULT '' NULL,

		amount varchar(150) DEFAULT '' NULL,

		PRIMARY KEY  (id)

	) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    dbDelta( $sql );

    add_option( 'trx_db_version', $trx_db_version );
}


add_action( 'plugins_loaded', 'jenga_payment_gateway_init', 0 );
function jenga_payment_gateway_init() {
    //if condition used to do nothing while WooCommerce is not installed
    if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

    define( 'JPGW_DIR', plugin_dir_path( __FILE__ ) );
    define( 'JPGW_INC_DIR', JPGW_DIR.'includes/' );
    define( 'WC_JPGW_VERSION', '1.1.1' );

// Admin Menus
require_once( JPGW_INC_DIR.'menu.php' );

// Payments Menu
require_once( JPGW_INC_DIR.'jpgwpayments.php');

// Include file with custom Jenga Payment Gateway class
include_once( 'jenga-payment-gateway-woocommerce.php' );


// add custom class methods  to WooCommerce
add_filter( 'woocommerce_payment_gateways', 'jenga_add_payment_gateway' );
function jenga_add_payment_gateway( $methods ) {
    $methods[] = 'Jenga_Payment_Gateway';
    return $methods;
}
}
// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'jpgw_action_links' );
function jpgw_action_links( $links ) {
    return array_merge( $links, [ '<a href="'.admin_url( 'admin.php?page=wc-settings&tab=checkout&section=jpgw' ).'">&nbsp;Preferences</a>' ] );

}
// Add action to trim zeros in woocommerce price
add_filter( 'woocommerce_price_trim_zeros', '__return_true' );



