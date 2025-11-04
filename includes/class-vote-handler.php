<?php
/**
 * Vote Handler - AJAX and Vote Processing
 *
 * @package Libookin_Monthly_Offer
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Vote Handler class
 */
class Libookin_Vote_Handler {

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
        add_action( 'wp_ajax_libookin_submit_vote', array( $this, 'handle_vote_submission' ) );
        add_action( 'wp_ajax_nopriv_libookin_submit_vote', array( $this, 'handle_vote_submission' ) );
    }

    /**
     * Handle AJAX vote submission
     */
    public function handle_vote_submission() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'libookin_vote_nonce' ) ) {
            wp_send_json_error( array( 
                'message' => __( 'Security check failed.', 'libookin-monthly-offer' ) 
            ) );
        }

        // Get and validate data
        $order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
        $charity_id = isset( $_POST['charity_id'] ) ? intval( $_POST['charity_id'] ) : 0;

        if ( ! $order_id || ! $charity_id ) {
            wp_send_json_error( array( 
                'message' => __( 'Invalid data provided.', 'libookin-monthly-offer' ) 
            ) );
        }

        // Validate order exists
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            wp_send_json_error( array( 
                'message' => __( 'Order not found.', 'libookin-monthly-offer' ) 
            ) );
        }

        // Check if order is completed
        if ( ! in_array( $order->get_status(), array( 'completed', 'processing' ) ) ) {
            wp_send_json_error( array( 
                'message' => __( 'Order must be completed to vote.', 'libookin-monthly-offer' ) 
            ) );
        }

        // Verify order ownership
        $user_id = get_current_user_id();
        $order_user_id = $order->get_user_id();
        
        // Allow guest orders by checking order key in session
        $valid_order = false;
        if ( $user_id && $user_id === $order_user_id ) {
            $valid_order = true;
        } elseif ( ! $user_id ) {
            // For guest checkout, verify via session or order key
            $order_key = isset( $_POST['order_key'] ) ? sanitize_text_field( $_POST['order_key'] ) : '';
            if ( $order_key && $order->get_order_key() === $order_key ) {
                $valid_order = true;
                $user_id = 0; // Guest user
            }
        }

        if ( ! $valid_order ) {
            wp_send_json_error( array( 
                'message' => __( 'You do not have permission to vote for this order.', 'libookin-monthly-offer' ) 
            ) );
        }

        // Check if already voted for this order
        if ( Libookin_MO_Database::has_voted( $order_id ) ) {
            wp_send_json_error( array( 
                'message' => __( 'You have already voted for this order.', 'libookin-monthly-offer' ) 
            ) );
        }

        // Validate charity exists
        $charity = get_post( $charity_id );
        if ( ! $charity || $charity->post_type !== 'libookin_charity' ) {
            wp_send_json_error( array( 
                'message' => __( 'Invalid charity selected.', 'libookin-monthly-offer' ) 
            ) );
        }

        // Check if order contains bundle products
        if ( ! $this->order_has_bundle_product( $order ) ) {
            wp_send_json_error( array( 
                'message' => __( 'Only bundle product orders are eligible for voting.', 'libookin-monthly-offer' ) 
            ) );
        }

        // Record the vote
        $vote_id = Libookin_MO_Database::record_vote( $order_id, $user_id, $charity_id );

        if ( $vote_id ) {
            wp_send_json_success( array( 
                'message' => __( 'Thank you for voting!', 'libookin-monthly-offer' ),
                'charity_name' => get_the_title( $charity_id ),
            ) );
        } else {
            wp_send_json_error( array( 
                'message' => __( 'Failed to record vote. Please try again.', 'libookin-monthly-offer' ) 
            ) );
        }
    }

    /**
     * Check if order contains bundle product (woosb type)
     *
     * @param WC_Order $order Order object
     * @return bool
     */
    private function order_has_bundle_product( $order ) {
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            if ( ! $product ) {
                continue;
            }

            // Check for WooCommerce Product Bundles plugin
            if ( $product->get_type() === 'woosb' ) {
                return true;
            }

            // Also check for native WooCommerce bundle type
            if ( $product->get_type() === 'bundle' ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if order is eligible for voting
     *
     * @param int $order_id Order ID
     * @return bool
     */
    public static function is_order_eligible( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return false;
        }

        // Check order status
        if ( ! in_array( $order->get_status(), array( 'completed', 'processing' ) ) ) {
            return false;
        }

        // Check if already voted
        if ( Libookin_MO_Database::has_voted( $order_id ) ) {
            return false;
        }

        // Check if order contains bundle
        $handler = self::get_instance();
        if ( ! $handler->order_has_bundle_product( $order ) ) {
            return false;
        }

        return true;
    }
}
