<?php
namespace PyApiDemo;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin Loader - loads all classes and registers hooks.
 */
class Plugin_Loader {

    /**
     * Singleton instance
     * @var Plugin_Loader
     */
    private static $instance = null;

    /**
     * Get instance
     * @return Plugin_Loader
     */
    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
            self::$instance->includes();
            self::$instance->init_hooks();
        }
        return self::$instance;
    }

    /**
     * Include required class files
     */
    private function includes() {
        require_once plugin_dir_path( __DIR__ ) . 'includes/class-api-client.php';
        require_once plugin_dir_path( __DIR__ ) . 'includes/class-shortcode-products.php';
        require_once plugin_dir_path( __DIR__ ) . 'includes/class-admin-page.php';
    }

    /**
     * Register WordPress hooks
     */
    private function init_hooks() {
        // Register shortcode on init.
        add_action( 'init', array( '\PyApiDemo\Shortcode_Products', 'register' ) );

        // Add admin menu for settings.
        add_action( 'admin_menu', array( '\PyApiDemo\Admin_Page', 'register_menu' ) );
        add_action( 'admin_init', array( '\PyApiDemo\Admin_Page', 'register_settings' ) );
    }
}
