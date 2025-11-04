<?php
/**
 * Charity Custom Post Type
 *
 * @package Libookin_Monthly_Offer
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Charity Post Type class
 */
class Libookin_Charity_Post_Type {

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
        add_action( 'init', array( $this, 'register_post_type' ) );
    }

    /**
     * Register charity post type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x( 'Charities', 'Post Type General Name', 'libookin-monthly-offer' ),
            'singular_name'         => _x( 'Charity', 'Post Type Singular Name', 'libookin-monthly-offer' ),
            'menu_name'             => __( 'Charities', 'libookin-monthly-offer' ),
            'name_admin_bar'        => __( 'Charity', 'libookin-monthly-offer' ),
            'archives'              => __( 'Charity Archives', 'libookin-monthly-offer' ),
            'attributes'            => __( 'Charity Attributes', 'libookin-monthly-offer' ),
            'parent_item_colon'     => __( 'Parent Charity:', 'libookin-monthly-offer' ),
            'all_items'             => __( 'All Charities', 'libookin-monthly-offer' ),
            'add_new_item'          => __( 'Add New Charity', 'libookin-monthly-offer' ),
            'add_new'               => __( 'Add New', 'libookin-monthly-offer' ),
            'new_item'              => __( 'New Charity', 'libookin-monthly-offer' ),
            'edit_item'             => __( 'Edit Charity', 'libookin-monthly-offer' ),
            'update_item'           => __( 'Update Charity', 'libookin-monthly-offer' ),
            'view_item'             => __( 'View Charity', 'libookin-monthly-offer' ),
            'view_items'            => __( 'View Charities', 'libookin-monthly-offer' ),
            'search_items'          => __( 'Search Charity', 'libookin-monthly-offer' ),
            'not_found'             => __( 'Not found', 'libookin-monthly-offer' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'libookin-monthly-offer' ),
            'featured_image'        => __( 'Charity Logo', 'libookin-monthly-offer' ),
            'set_featured_image'    => __( 'Set charity logo', 'libookin-monthly-offer' ),
            'remove_featured_image' => __( 'Remove charity logo', 'libookin-monthly-offer' ),
            'use_featured_image'    => __( 'Use as charity logo', 'libookin-monthly-offer' ),
            'insert_into_item'      => __( 'Insert into charity', 'libookin-monthly-offer' ),
            'uploaded_to_this_item' => __( 'Uploaded to this charity', 'libookin-monthly-offer' ),
            'items_list'            => __( 'Charities list', 'libookin-monthly-offer' ),
            'items_list_navigation' => __( 'Charities list navigation', 'libookin-monthly-offer' ),
            'filter_items_list'     => __( 'Filter charities list', 'libookin-monthly-offer' ),
        );

        $args = array(
            'label'                 => __( 'Charity', 'libookin-monthly-offer' ),
            'description'           => __( 'Monthly offer charities', 'libookin-monthly-offer' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'thumbnail' ),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => false, // We'll add it to our custom menu
            'menu_position'         => 25,
            'menu_icon'             => 'dashicons-heart',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );

        register_post_type( 'libookin_charity', $args );
    }

    /**
     * Get active charities for voting
     *
     * @param int $limit Number of charities to return
     * @return array
     */
    public static function get_active_charities( $limit = 3 ) {
        $args = array(
            'post_type'      => 'libookin_charity',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'orderby'        => 'rand',
        );

        $charities = get_posts( $args );
        return $charities;
    }

    /**
     * Get all charities
     *
     * @return array
     */
    public static function get_all_charities() {
        $args = array(
            'post_type'      => 'libookin_charity',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        return get_posts( $args );
    }

    /**
     * Get charity with vote count
     *
     * @param int $charity_id Charity ID
     * @return object|null
     */
    public static function get_charity_with_votes( $charity_id ) {
        $charity = get_post( $charity_id );
        if ( ! $charity ) {
            return null;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'libookin_vote_results';
        $month_year = date( 'Y-m' );

        $vote_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT vote_count FROM $table WHERE charity_id = %d AND month_year = %s",
            $charity_id,
            $month_year
        ) );

        $charity->vote_count = $vote_count ? intval( $vote_count ) : 0;
        return $charity;
    }
}
