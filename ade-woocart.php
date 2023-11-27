<?php

/**
 * Plugin Name: Ade WooCart
 * Plugin URI:  http://www.adeleyeayodeji.com
 * Author:      Adeleye Ayodeji
 * Author URI:  http://www.adeleyeayodeji.com
 * Description: A simple plugin to add a cart to your website
 * Version:     0.1.2
 * License:     GPL-2.0+
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: ade-woocart
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Include the main class
require_once plugin_dir_path(__FILE__) . 'includes/class-ade-woocart.php';
