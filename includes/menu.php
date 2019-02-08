<?php

/**
 * Created by PhpStorm.
 * User: denis
 * Date: 11/19/18
 * Time: 2:38 PM
 */

// Add custom admin menu
add_action( 'admin_menu', 'jenga_pgw_menu' );
add_action( 'admin_menu', 'jpgw_settings_pref', 103);

function jenga_pgw_menu()
{
    //create custom top-level menu
    add_menu_page(
        'JPGW Payment',
        'Jenga JPGW',
        'manage_options',
        'jpgw',
        'jpgw_transactions_menu_about',
        'dashicons-money',
        58
    );


}

//Add  custom sub menu admin settings for Jenga Payment Gateway
function jpgw_settings_pref ()
{
    add_submenu_page(
        'jpgw',
        'JPGW Preferences',
        'Settings',
        'manage_options',
        'jpgw_preferences',
        'jpgw_transactions_menu_pref'
    );

}
// Function to populate content for Jenga Payment Gateway Admin about menu
function jpgw_transactions_menu_about()
{ ?>
    <div class="wrap">
        <h1>About Jenga PGW for WooCommerce</h1>

        <h3>The Plugin</h3>
        <article>
            <p>This plugin is a simple plug-n-play implementation for integrating Jenga PGW Payments into online stores built with WooCommerce and WordPress.</p>
        </article>

        <h3>Integration(Going Live)</h3>
        <article>
            <p>
                Get in touch with our Sales team to start accepting live payments using Jenga Payment Gateway or Jenga API. Drop an email to sales@jengahq.io. To find out more about our pricing, visit:
            </p>

            <ol>
                <li>Jenga Payment Gateway: https://jengapgw.io/pricing</li>
                <li> Jenga API: https://jengaapi.io/pricing </li>
            </ol>
        </article>

        <h3>Next Steps</h3>
        <article>
            <p>To start accepting live payments and make live transactions, ensure that you've read through our documentation and run some integration tests of your own on our test environment. Not sure how? Check out our quick guidelines:</p>
            <ol>
                <li>Jenga Payment Gateway: https://developer.jengapgw.io/docs/get-started </li>
                <li> Jenga API: https://developer.jengaapi.io/docs/get-started </li>
            </ol>
            <p>While you're testing your integration, you can also submit details about your business to set up a merchant account and start accepting payments and push transactions with us. To do this, you'll need to visit https://jengahq.io/#!/credentials and follow the instructions on screen. Once complete and confirmed, you'll get an email from our team as we welcome you to your live Jenga HQ!</p>
        </article>
         <p>To read more about our APIs and documentation for Jenga Payment Gateway and Jenga API, visit:</p>
        <ol>
            <li>Jenga Payment Gateway: https://developer.jengapgw.io</li>
            <li>Jenga API: https://developer.jengaapi.io</li>
        </ol>

    </div><?php
}

// Function to redirect user to Jenga Payment Gateway woocomerce setings
function jpgw_transactions_menu_pref()
{
    wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=jpgw' ) );
}


