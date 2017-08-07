<?php

/*
 * Plugin Name: Easy Digital Downloads Putler Connector
 * Plugin URI: http://putler.com/connector/edd/
 * Description: Track Easy Digital Downloads transactions data with Putler. Insightful reporting that grows your business.
 * Version: 2.4
 * Requires at least: 3.3
 * Tested up to: 4.7.5
 * Author: putler, storeapps
 * Author URI: http://putler.com/
 * License: GPL 3.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

register_activation_hook ( __FILE__, 'eddpc_activate' );

/**
 * Registers a plugin function to be run when the plugin is activated.
 */
function eddpc_activate() {
    // Redirect to EDDPC
    update_option( '_eddpc_activation_redirect', 'pending' );
}

function edd_putler_connector_pre_init() {

    // Simple check for EDD being active...
    if (class_exists('Easy_Digital_Downloads')) {

        // Init admin menu for settings etc if we are in admin
        if (is_admin()) {
            edd_putler_connector_init();

            if ( false === get_option( '_eddpc_update_redirect' ) && 'pending' !== get_option( '_eddpc_activation_redirect' ) ) {
                update_option( '_eddpc_update_redirect', 1 ); //flag for redirecting on update
                update_option( '_eddpc_activation_redirect', 'pending' );
            }       

            if ( false !== get_option( '_eddpc_activation_redirect' ) && (current_user_can('import') === true) ) {
                // Delete the redirect transient
                delete_option( '_eddpc_activation_redirect' );
                wp_redirect( admin_url('tools.php?page=putler_connector') );
                exit;
            }
        }

        // If configuration not done, can't track anything...
        if (null != get_option('putler_connector_settings', null)) {
            // On these events, send order data to Putler
            add_action('edd_edit_payment', 'edd_putler_connector_post_order');
            add_action('edd_update_payment_status', 'edd_putler_connector_post_order');
            add_action('edd_payment_saved', 'edd_putler_connector_post_order'); //for handling transaction_id
        }
    }
}

add_action( 'plugins_loaded', 'edd_putler_connector_pre_init' );

function edd_putler_connector_init() {

    include_once 'classes/class.putler-connector.php';
    $GLOBALS['putler_connector'] = Putler_Connector::getInstance();

    include_once 'classes/class.putler-connector-edd.php';
    if (!isset($GLOBALS['edd_putler_connector'])) {
        $GLOBALS['edd_putler_connector'] = new EDD_Putler_Connector();
    }
}

function edd_putler_connector_post_order($order_id) {
    edd_putler_connector_init();
    if (method_exists($GLOBALS['putler_connector'], 'post_order')) {
        $GLOBALS['putler_connector']->post_order(array('order_id' => $order_id));
    }
}
