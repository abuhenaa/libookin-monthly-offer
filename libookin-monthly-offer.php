<?php
/**
 * Plugin Name: LiBookin Monthly Offer
 * Plugin URI: https://libookin.com
 * Description: Monthly ebook bundle system with charity voting integration for WooCommerce Product Bundles
 * Version: 1.0.0
 * Author: Abu Hena
 * Author URI: https://www.example.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: libookin-monthly-offer
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'LIBOOKIN_MO_VERSION', '1.0.0' );
define( 'LIBOOKIN_MO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LIBOOKIN_MO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LIBOOKIN_MO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Plugin Class
 */
class Libookin_Monthly_Offer {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {

        require_once LIBOOKIN_MO_PLUGIN_DIR . 'functions.php';
        require_once LIBOOKIN_MO_PLUGIN_DIR . 'includes/class-database.php';
        require_once LIBOOKIN_MO_PLUGIN_DIR . 'includes/class-charity-post-type.php';
        require_once LIBOOKIN_MO_PLUGIN_DIR . 'includes/class-vote-handler.php';
        require_once LIBOOKIN_MO_PLUGIN_DIR . 'includes/class-vote-display.php';
        require_once LIBOOKIN_MO_PLUGIN_DIR . 'includes/class-admin.php';
        require_once LIBOOKIN_MO_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once LIBOOKIN_MO_PLUGIN_DIR . 'includes/class-cron.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
        
        add_action( 'plugins_loaded', array( $this, 'init' ) );
        add_action( 'init', array( $this, 'load_textdomain' ) );
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
            return;
        }

        // Initialize components
        Libookin_Charity_Post_Type::get_instance();
        Libookin_Vote_Handler::get_instance();
        Libookin_Vote_Display::get_instance();
        Libookin_MO_Admin::get_instance();
        Libookin_MO_Shortcodes::get_instance();
        Libookin_MO_Cron::get_instance();
    }

    /**
     * Plugin activation
     */
    public function activate() {
        Libookin_MO_Database::create_tables();
        Libookin_MO_Cron::schedule_events();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        Libookin_MO_Cron::clear_scheduled_events();
        flush_rewrite_rules();
    }

    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'libookin-monthly-offer', false, dirname( LIBOOKIN_MO_PLUGIN_BASENAME ) . '/languages' );
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php esc_html_e( 'LiBookin Monthly Offer requires WooCommerce to be installed and active.', 'libookin-monthly-offer' ); ?></p>
        </div>
        <?php
    }
}

// Initialize plugin
Libookin_Monthly_Offer::get_instance();
