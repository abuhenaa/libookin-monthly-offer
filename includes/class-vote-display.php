<?php
/**
 * Vote Display - Thank You Page and Popups
 *
 * @package Libookin_Monthly_Offer
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Vote Display class
 */
class Libookin_Vote_Display {

    /**
     * Single instance
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
        add_action( 'woocommerce_thankyou', array( $this, 'display_vote_section' ), 10, 1 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_footer', array( $this, 'display_results_popup' ) );
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style( 
            'libookin-mo-styles', 
            LIBOOKIN_MO_PLUGIN_URL . 'assets/css/styles.css', 
            array(), 
            LIBOOKIN_MO_VERSION 
        );

        wp_enqueue_script( 
            'libookin-mo-scripts', 
            LIBOOKIN_MO_PLUGIN_URL . 'assets/js/scripts.js', 
            array( 'jquery' ), 
            LIBOOKIN_MO_VERSION, 
            true 
        );

        wp_localize_script( 'libookin-mo-scripts', 'libookinMO', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'libookin_vote_nonce' ),
            'strings'  => array(
                'voting'    => __( 'Submitting vote...', 'libookin-monthly-offer' ),
                'error'     => __( 'An error occurred. Please try again.', 'libookin-monthly-offer' ),
                'success'   => __( 'Thank you for voting!', 'libookin-monthly-offer' ),
            ),
        ) );
    }

    /**
     * Display vote section on thank you page
     *
     * @param int $order_id Order ID
     */
    public function display_vote_section( $order_id ) {

        if ( ! $order_id ) {
            return;
        }

        // Check if order is eligible
        if ( ! Libookin_Vote_Handler::is_order_eligible( $order_id ) ) {
            return;
        }

        $order = wc_get_order( $order_id );
        $charities = Libookin_Charity_Post_Type::get_active_charities( 3 );

        if ( empty( $charities ) ) {
            return;
        }

        ?>
        <div class="libookin-vote-section">
            <h2><?php esc_html_e( 'Vote for Next Month\'s Charity', 'libookin-monthly-offer' ); ?></h2>
            <p><?php esc_html_e( 'Thank you for your purchase! Please vote for the charity you\'d like us to support next month.', 'libookin-monthly-offer' ); ?></p>
            
            <div class="libookin-vote-options" id="libookin-vote-form">
                <?php foreach ( $charities as $charity ) : ?>
                    <div class="libookin-charity-option">
                        <?php if ( has_post_thumbnail( $charity->ID ) ) : ?>
                            <div class="charity-logo">
                                <?php echo get_the_post_thumbnail( $charity->ID, 'thumbnail' ); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="charity-info">
                            <h3><?php echo esc_html( $charity->post_title ); ?></h3>
                            <div class="charity-description">
                                <?php echo wp_kses_post( wpautop( $charity->post_content ) ); ?>
                            </div>
                        </div>
                        
                        <button 
                            type="button" 
                            class="libookin-vote-btn" 
                            data-charity-id="<?php echo esc_attr( $charity->ID ); ?>"
                            data-order-id="<?php echo esc_attr( $order_id ); ?>"
                            data-order-key="<?php echo esc_attr( $order->get_order_key() ); ?>">
                            <?php esc_html_e( 'Vote', 'libookin-monthly-offer' ); ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="libookin-vote-message" id="libookin-vote-message" style="display: none;"></div>
        </div>
        <?php
    }

    /**
     * Display results popup
     */
    public function display_results_popup() {
        // Only show if user should see results
        if ( ! $this->should_show_results_popup() ) {
            return;
        }

        $current_month = date( 'Y-m' );
        $winner = Libookin_MO_Database::get_winning_charity( $current_month );

        if ( ! $winner ) {
            return;
        }

        $charity = get_post( $winner->charity_id );
        if ( ! $charity ) {
            return;
        }

        ?>
        <div id="libookin-results-popup" class="libookin-popup-overlay">
            <div class="libookin-popup-content">
                <button class="libookin-popup-close">&times;</button>
                
                <h2><?php esc_html_e( 'This Month\'s Winning Charity', 'libookin-monthly-offer' ); ?></h2>
                
                <?php if ( has_post_thumbnail( $charity->ID ) ) : ?>
                    <div class="popup-charity-logo">
                        <?php echo get_the_post_thumbnail( $charity->ID, 'medium' ); ?>
                    </div>
                <?php endif; ?>
                
                <h3><?php echo esc_html( $charity->post_title ); ?></h3>
                
                <div class="popup-charity-description">
                    <?php echo wp_kses_post( wpautop( $charity->post_content ) ); ?>
                </div>
                
                <p class="popup-vote-count">
                    <?php 
                    printf( 
                        esc_html__( 'Received %s votes this month', 'libookin-monthly-offer' ), 
                        '<strong>' . number_format_i18n( $winner->vote_count ) . '</strong>' 
                    ); 
                    ?>
                </p>
                
                <a href="/monthly-offer" class="libookin-popup-btn">
                    <?php esc_html_e( 'View Monthly Offer', 'libookin-monthly-offer' ); ?>
                </a>
            </div>
        </div>
        <?php

        // Mark as shown
        $this->mark_results_popup_shown();
    }

    /**
     * Check if results popup should be shown
     *
     * @return bool
     */
    private function should_show_results_popup() {
        $current_month = date( 'Y-m' );
        
        // Check cookie
        $cookie_name = 'libookin_vote_result_shown_' . str_replace( '-', '_', $current_month );
        if ( isset( $_COOKIE[ $cookie_name ] ) ) {
            return false;
        }

        // Check user meta for logged-in users
        if ( is_user_logged_in() ) {
            $shown = get_user_meta( get_current_user_id(), 'libookin_vote_result_shown_' . $current_month, true );
            if ( $shown ) {
                return false;
            }
        }

        // Check if we're past the 1st of the month (results announcement date)
        $day_of_month = intval( date( 'd' ) );
        if ( $day_of_month < 1 ) {
            return false;
        }

        return true;
    }

    /**
     * Mark results popup as shown
     */
    private function mark_results_popup_shown() {
        $current_month = date( 'Y-m' );
        
        // Set cookie (30 days)
        $cookie_name = 'libookin_vote_result_shown_' . str_replace( '-', '_', $current_month );
        setcookie( $cookie_name, '1', time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );

        // Set user meta for logged-in users
        if ( is_user_logged_in() ) {
            update_user_meta( get_current_user_id(), 'libookin_vote_result_shown_' . $current_month, true );
        }
    }
}
