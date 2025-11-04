<?php
/**
 * Uninstall Script
 * 
 * Fired when the plugin is uninstalled.
 *
 * @package Libookin_Monthly_Offer
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Remove plugin data on uninstall
 */
function libookin_mo_uninstall() {
    global $wpdb;

    // Delete custom tables
    $tables = array(
        $wpdb->prefix . 'libookin_votes',
        $wpdb->prefix . 'libookin_vote_results',
    );

    foreach ( $tables as $table ) {
        $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
    }

    // Delete charities and their meta
    $charities = get_posts( array(
        'post_type'      => 'libookin_charity',
        'posts_per_page' => -1,
        'post_status'    => 'any',
    ) );

    foreach ( $charities as $charity ) {
        wp_delete_post( $charity->ID, true );
    }

    // Delete options
    $options = array(
        'libookin_mo_version',
        'libookin_mo_db_version',
    );

    foreach ( $options as $option ) {
        delete_option( $option );
    }

    // Delete transients
    delete_transient( 'libookin_current_month_results' );

    // Delete user meta for popup flags
    $wpdb->query( 
        "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'libookin_vote_result_shown_%'" 
    );

    // Delete all winner options
    $wpdb->query( 
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'libookin_winner_%'" 
    );

    // Clear scheduled cron events
    $timestamp = wp_next_scheduled( 'libookin_daily_cache_refresh' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'libookin_daily_cache_refresh' );
    }

    $timestamp = wp_next_scheduled( 'libookin_monthly_process' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'libookin_monthly_process' );
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}

// Run uninstall
libookin_mo_uninstall();
