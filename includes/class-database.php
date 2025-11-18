<?php
/**
 * Database Handler
 *
 * @package Libookin_Monthly_Offer
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Database class
 */
class Libookin_MO_Database {

    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Votes table
        $votes_table = $wpdb->prefix . 'libookin_votes';
        $votes_sql = "CREATE TABLE IF NOT EXISTS $votes_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            charity_id BIGINT(20) UNSIGNED NOT NULL,
            vote_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY order_id (order_id),
            KEY user_id (user_id),
            KEY charity_id (charity_id)
        ) $charset_collate;";

        // Vote results table
        $results_table = $wpdb->prefix . 'libookin_vote_results';
        $results_sql = "CREATE TABLE IF NOT EXISTS $results_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            charity_id BIGINT(20) UNSIGNED NOT NULL,
            month_year VARCHAR(7) NOT NULL,
            vote_count INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY charity_month (charity_id, month_year),
            KEY month_year (month_year)
        ) $charset_collate;";

        //charity earnings table
        $earnings_table = $wpdb->prefix . 'libookin_charity_earnings';
        $charity_earnings_query = "CREATE TABLE $earnings_table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            charity_id BIGINT(20) UNSIGNED NOT NULL,
            charity_name VARCHAR(255) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            month_year VARCHAR(7) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY charity_id (charity_id),
            KEY month_year (month_year)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $votes_sql );
        dbDelta( $results_sql );
        dbDelta( $charity_earnings_query );
    }

    /**
     * Check if user has voted for order
     *
     * @param int $order_id Order ID
     * @return bool
     */
    public static function has_voted( $order_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'libookin_votes';
        
        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE order_id = %d",
            $order_id
        ) );

        return $count > 0;
    }

    /**
     * Record vote
     *
     * @param int $order_id Order ID
     * @param int $user_id User ID
     * @param int $charity_id Charity ID
     * @return bool|int
     */
    public static function record_vote( $order_id, $user_id, $charity_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'libookin_votes';

        $result = $wpdb->insert(
            $table,
            array(
                'order_id'   => $order_id,
                'user_id'    => $user_id,
                'charity_id' => $charity_id,
                'vote_date'  => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%d', '%s' )
        );

        if ( $result ) {
            self::increment_vote_count( $charity_id );
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Increment vote count for charity
     *
     * @param int $charity_id Charity ID
     */
    public static function increment_vote_count( $charity_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'libookin_vote_results';
        $month_year = date( 'Y-m' );

        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table WHERE charity_id = %d AND month_year = %s",
            $charity_id,
            $month_year
        ) );

        if ( $existing ) {
            $wpdb->query( $wpdb->prepare(
                "UPDATE $table SET vote_count = vote_count + 1 WHERE charity_id = %d AND month_year = %s",
                $charity_id,
                $month_year
            ) );
        } else {
            $wpdb->insert(
                $table,
                array(
                    'charity_id' => $charity_id,
                    'month_year' => $month_year,
                    'vote_count' => 1,
                ),
                array( '%d', '%s', '%d' )
            );
        }
    }

    /**
     * Get vote results for current month
     *
     * @param string $month_year Month year (Y-m format)
     * @return array
     */
    public static function get_vote_results( $month_year = null ) {
        global $wpdb;
        $table = $wpdb->prefix . 'libookin_vote_results';

        if ( null === $month_year ) {
            $month_year = date( 'Y-m' );
        }

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table WHERE month_year = %s ORDER BY vote_count DESC",
            $month_year
        ) );

        return $results;
    }

    /**
     * Get winning charity for month
     *
     * @param string $month_year Month year (Y-m format)
     * @return object|null
     */
    public static function get_winning_charity( $month_year = null ) {
        global $wpdb;
        $table = $wpdb->prefix . 'libookin_vote_results';

        if ( null === $month_year ) {
            $month_year = date( 'Y-m' );
        }

        $winner = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE month_year = %s ORDER BY vote_count DESC LIMIT 1",
            $month_year
        ) );

        return $winner;
    }

    /**
     * Reset vote counts for new cycle
     */
    public static function reset_vote_counts() {
        // We don't delete old data, just start fresh counts for new month
        // Historical data is preserved
    }

    /**
     * Get all votes with charity info
     *
     * @param string $month_year Month year (Y-m format)
     * @return array
     */
    public static function get_votes_with_details( $month_year = null ) {
        global $wpdb;
        $votes_table = $wpdb->prefix . 'libookin_votes';
        $posts_table = $wpdb->posts;

        if ( null === $month_year ) {
            $month_year = date( 'Y-m' );
        }

        $start_date = $month_year . '-01 00:00:00';
        $end_date = date( 'Y-m-t 23:59:59', strtotime( $start_date ) );

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT v.*, p.post_title as charity_name 
            FROM $votes_table v 
            LEFT JOIN $posts_table p ON v.charity_id = p.ID 
            WHERE v.vote_date >= %s AND v.vote_date <= %s 
            ORDER BY v.vote_date DESC",
            $start_date,
            $end_date
        ) );

        return $results;
    }

    /**
     * Get charity earnings for current month
     *
     * @param string $month_year Month year (Y-m format)
     * @return array
     */
    public static function get_charity_earnings( $month_year = null ) {
        global $wpdb;
        $table = $wpdb->prefix . 'libookin_charity_earnings';

        if ( null === $month_year ) {
            $month_year = date( 'Y-m' );
        }

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT charity_id, charity_name, SUM(amount) as total_earnings FROM $table where month_year = %s GROUP BY charity_id",
            $month_year
        ) );

        return $results;
    }
}
