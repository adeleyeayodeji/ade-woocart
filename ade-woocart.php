<?php

/**
 * Plugin Name: Ade WooCart
 * Plugin URI:  http://www.adeleyeayodeji.com
 * Author:      Adeleye Ayodeji
 * Author URI:  http://www.adeleyeayodeji.com
 * Description: A simple plugin to add a cart to your website
 * Version:     0.1.3
 * License:     GPL-2.0+
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: ade-woocart
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

//fire when all the evironment has been set up
add_action('woocommerce_init', 'ade_woocommerce_loaded', PHP_INT_MAX);

function ade_woocommerce_loaded()
{
    if (class_exists('WooCommerce')) {
        if (is_null(WC()->cart) && function_exists('wc_load_cart')) {
            wc_load_cart();
        }
        // Include the main class
        require_once plugin_dir_path(__FILE__) . 'includes/class-ade-woocart.php';
    }
}
