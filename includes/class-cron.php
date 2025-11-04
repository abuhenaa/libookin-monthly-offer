<?php
/**
 * Cron Jobs
 *
 * @package Libookin_Monthly_Offer
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cron class
 */
class Libookin_MO_Cron {

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
        add_action( 'libookin_daily_cache_refresh', array( $this, 'refresh_vote_cache' ) );
        add_action( 'libookin_monthly_process', array( $this, 'process_monthly_results' ) );
    }

    /**
     * Schedule cron events
     */
    public static function schedule_events() {
        // Daily cache refresh
        if ( ! wp_next_scheduled( 'libookin_daily_cache_refresh' ) ) {
            wp_schedule_event( time(), 'daily', 'libookin_daily_cache_refresh' );
        }

        // Monthly processing on 1st of each month
        if ( ! wp_next_scheduled( 'libookin_monthly_process' ) ) {
            // Schedule for 1st of next month at midnight
            $next_month = strtotime( 'first day of next month midnight' );
            wp_schedule_event( $next_month, 'monthly', 'libookin_monthly_process' );
        }
    }

    /**
     * Clear scheduled events
     */
    public static function clear_scheduled_events() {
        $timestamp = wp_next_scheduled( 'libookin_daily_cache_refresh' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'libookin_daily_cache_refresh' );
        }

        $timestamp = wp_next_scheduled( 'libookin_monthly_process' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'libookin_monthly_process' );
        }
    }

    /**
     * Refresh vote cache
     * Runs daily to keep vote results updated
     */
    public function refresh_vote_cache() {
        // Get current month results
        $current_month = date( 'Y-m' );
        $results = Libookin_MO_Database::get_vote_results( $current_month );

        // Store in transient for quick access
        set_transient( 'libookin_current_month_results', $results, DAY_IN_SECONDS );

        // Log for debugging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'LiBookin Monthly Offer: Daily cache refresh completed for ' . $current_month );
        }
    }

    /**
     * Process monthly results
     * Runs on 1st of each month
     */
    public function process_monthly_results() {
        $previous_month = date( 'Y-m', strtotime( 'first day of last month' ) );
        
        // Get winning charity
        $winner = Libookin_MO_Database::get_winning_charity( $previous_month );

        if ( $winner ) {
            // Store winner for the month
            update_option( 'libookin_winner_' . $previous_month, array(
                'charity_id' => $winner->charity_id,
                'vote_count' => $winner->vote_count,
                'processed_date' => current_time( 'mysql' ),
            ) );

            // Clear user meta flags so popup can show for new month
            $this->clear_popup_flags();

            // Send notification email to admin (optional)
            $this->send_winner_notification( $winner, $previous_month );

            // Log for debugging
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'LiBookin Monthly Offer: Monthly processing completed. Winner: ' . $winner->charity_id );
            }
        }

        // Clear cache
        delete_transient( 'libookin_current_month_results' );
        
        // Schedule next monthly event
        $next_month = strtotime( 'first day of next month midnight' );
        if ( ! wp_next_scheduled( 'libookin_monthly_process' ) ) {
            wp_schedule_event( $next_month, 'monthly', 'libookin_monthly_process' );
        }
    }

    /**
     * Clear popup shown flags for all users
     */
    private function clear_popup_flags() {
        global $wpdb;
        
        $current_month = date( 'Y-m' );
        $meta_key_pattern = 'libookin_vote_result_shown_%';
        
        // Delete old popup flags
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
            $meta_key_pattern
        ) );
    }

    /**
     * Send winner notification email to admin
     *
     * @param object $winner Winner data
     * @param string $month_year Month year
     */
    private function send_winner_notification( $winner, $month_year ) {
        $charity = get_post( $winner->charity_id );
        if ( ! $charity ) {
            return;
        }

        $admin_email = get_option( 'admin_email' );
        $site_name = get_bloginfo( 'name' );
        
        $subject = sprintf(
            __( '[%s] Monthly Charity Winner - %s', 'libookin-monthly-offer' ),
            $site_name,
            date( 'F Y', strtotime( $month_year . '-01' ) )
        );

        $message = sprintf(
            __( "The winning charity for %s is:\n\n%s\n\nTotal Votes: %s\n\nView details in your dashboard:\n%s", 'libookin-monthly-offer' ),
            date( 'F Y', strtotime( $month_year . '-01' ) ),
            $charity->post_title,
            number_format_i18n( $winner->vote_count ),
            admin_url( 'admin.php?page=libookin-monthly-offer' )
        );

        wp_mail( $admin_email, $subject, $message );
    }

    /**
     * Get cached results
     *
     * @return array|false
     */
    public static function get_cached_results() {
        $cached = get_transient( 'libookin_current_month_results' );
        
        if ( false === $cached ) {
            // Cache miss, refresh
            $current_month = date( 'Y-m' );
            $results = Libookin_MO_Database::get_vote_results( $current_month );
            set_transient( 'libookin_current_month_results', $results, DAY_IN_SECONDS );
            return $results;
        }

        return $cached;
    }

    /**
     * Manual trigger for testing
     * Only accessible by administrators
     */
    public static function manual_trigger_monthly_process() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }

        $cron = self::get_instance();
        $cron->process_monthly_results();
        
        return true;
    }
}
