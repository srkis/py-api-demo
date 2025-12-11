<?php

/**
 * Plugin Name: Python API Demo
 * Description: Demo plugin that fetches products from an external Python API and displays them via shortcode [python_products]. OOP, secure and documented.
 * Version: 0.1.0
 * Author: Srki Mafia
 * Text Domain: python-api-demo
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('PYAPI_DEMO_DIR', plugin_dir_path(__FILE__));
define('PYAPI_DEMO_URL', plugin_dir_url(__FILE__));
define('PYAPI_DEMO_VERSION', '0.1.0');

// Require loader.
require_once PYAPI_DEMO_DIR . 'includes/class-plugin-loader.php';

// Initialize plugin.
function pyapi_demo_init_plugin()
{
    \PyApiDemo\Plugin_Loader::get_instance();
}

add_action('plugins_loaded', 'pyapi_demo_init_plugin');
