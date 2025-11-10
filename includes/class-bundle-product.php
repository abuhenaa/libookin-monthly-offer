<?php
//prevent direct access

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Libookin_Bundle_Product {

    protected static $instance = null;

    public static function get_instance() {

        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct() {
        add_action( 'woocommerce_order_status_completed', array( $this, 'process_bundle_order' ), 11, 1 );
    }

    public function process_bundle_order( $order_id ) {

        global $wpdb;
       
        $orders              = wc_get_order( $order_id );
        $parent_order_id     = $orders->get_parent_id();
        if( $parent_order_id ) {
            return;
        }
       
        if ( get_post_meta( $order_id, '_libookin_processed', true ) ) {
            return; // already processed
        }
        update_post_meta( $order_id, '_libookin_processed', 1 );

        $price_ht            = $orders->get_subtotal();
        $authors_percentage  = 50;
        $platform_percentage = 40;
        $charity_percentage  = 10;
        $authors_amount      = $price_ht * ( $authors_percentage / 100 );
        $platform_amount     = $price_ht * ( $platform_percentage / 100 );
        $charity_amount      = $price_ht * ( $charity_percentage / 100 );
        $per_author          = $authors_amount / 5;

        foreach ( $orders->get_items() as $item ) {
            $product_id    = $item->get_product_id();
            $product       = $item->get_product();
            $product_title = $item->get_name();
            $parent_id     = $item->get_meta( '_woosb_parent_id', true );
            $vendor_id    = get_post_field('post_author', $product_id);

            //check if the product is bundle product
            if ( $product->get_type() == 'woosb' ) {
                //get all the vendors info in the bundle product
                $charity_id   = get_post_meta( $product_id, '_libookin_charity', true );
                $charity_name = get_the_title( $charity_id );
                $vendor_name  = get_the_title( $vendor_id );
                //insert charity earnings
                $wpdb->insert(
                    $wpdb->prefix . 'libookin_charity_earnings',
                    array(
                        'order_id'       => $order_id,
                        'product_id'     => $product_id,
                        'charity_id'     => $charity_id,
                        'charity_name'   => $charity_name,
                        'amount'         => $charity_amount,
                        'created_at'     => current_time( 'mysql' ),
                    ),
                    array( '%d', '%d', '%d', '%s', '%f', '%s' )
                );
            }
            
            //add royalties for each selected vendors for the bundle product
            if( $parent_id ) {
                // Insert royalty record
                $wpdb->insert(
                    $wpdb->prefix . 'libookin_royalties',
                array(
                    'order_id'        => $order_id,
                    'product_id'      => $product_id,
                    'vendor_id'       => $vendor_id,
                    'price_ht'        => $price_ht,
                    'royalty_percent' => 10,
                    'royalty_amount'  => $per_author,
                    'created_at'      => current_time( 'mysql' ),
                    'payout_status'   => 'pending',
                ),
                array( '%d', '%d', '%d', '%f', '%f', '%f', '%s', '%s' )
            );
            }

        }

    }

}
