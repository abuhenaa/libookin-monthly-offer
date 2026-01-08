<?php
/**
 * Admin Interface
 *
 * @package Libookin_Monthly_Offer
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin class
 */
class Libookin_MO_Admin {

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
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'admin_init', array( $this, 'handle_csv_export' ) );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Monthly Offer', 'libookin-monthly-offer' ),
            __( 'Monthly Offer', 'libookin-monthly-offer' ),
            'manage_options',
            'libookin-monthly-offer',
            array( $this, 'display_dashboard' ),
            'dashicons-calendar-alt',
            26
        );

        add_submenu_page(
            'libookin-monthly-offer',
            __( 'Dashboard', 'libookin-monthly-offer' ),
            __( 'Dashboard', 'libookin-monthly-offer' ),
            'manage_options',
            'libookin-monthly-offer',
            array( $this, 'display_dashboard' )
        );

        add_submenu_page(
            'libookin-monthly-offer',
            __( 'Manage Charities', 'libookin-monthly-offer' ),
            __( 'Manage Charities', 'libookin-monthly-offer' ),
            'manage_options',
            'edit.php?post_type=libookin_charity'
        );

        add_submenu_page(
            'libookin-monthly-offer',
            __( 'Vote Results', 'libookin-monthly-offer' ),
            __( 'Vote Results', 'libookin-monthly-offer' ),
            'manage_options',
            'libookin-vote-results',
            array( $this, 'display_vote_results' )
        );

        add_submenu_page(
            'libookin-monthly-offer',
            __( 'Add New Charity', 'libookin-monthly-offer' ),
            __( 'Add New Charity', 'libookin-monthly-offer' ),
            'manage_options',
            'post-new.php?post_type=libookin_charity'
        );
        //charity earnings sub menu
        add_submenu_page(
            'libookin-monthly-offer',
            __( 'Charity Earnings', 'libookin-monthly-offer' ),
            __( 'Charity Earnings', 'libookin-monthly-offer' ),
            'manage_options',
            'libookin-charity-earnings',
            array( $this, 'display_charity_earnings' )
        );

        //settings
        add_submenu_page(
            'libookin-monthly-offer',
            __( 'Settings', 'libookin-monthly-offer' ),
            __( 'Settings', 'libookin-monthly-offer' ),
            'manage_options',
            'libookin-mo-settings',
            array( $this, 'display_settings_page' )
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( strpos( $hook, 'libookin' ) === false ) {
            return;
        }

        wp_enqueue_style( 
            'libookin-mo-admin-styles', 
            LIBOOKIN_MO_PLUGIN_URL . 'assets/css/admin-styles.css', 
            array(), 
            LIBOOKIN_MO_VERSION 
        );

        wp_enqueue_script( 
            'libookin-mo-admin-scripts', 
            LIBOOKIN_MO_PLUGIN_URL . 'assets/js/admin-scripts.js', 
            array( 'jquery' ), 
            LIBOOKIN_MO_VERSION, 
            true 
        );
    }

    /**
     * Display dashboard
     */
    public function display_dashboard() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $current_month = date( 'Y-m' );
        $results = Libookin_MO_Database::get_vote_results( $current_month );
        $winner = Libookin_MO_Database::get_winning_charity( $current_month );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Monthly Offer Dashboard', 'libookin-monthly-offer' ); ?></h1>
            
            <div class="libookin-admin-dashboard">
                <div class="libookin-stats-grid">
                    <div class="stat-box">
                        <h3><?php esc_html_e( 'Total Votes This Month', 'libookin-monthly-offer' ); ?></h3>
                        <p class="stat-number">
                            <?php 
                            $total_votes = array_sum( wp_list_pluck( $results, 'vote_count' ) );
                            echo number_format_i18n( $total_votes ); 
                            ?>
                        </p>
                    </div>

                    <div class="stat-box">
                        <h3><?php esc_html_e( 'Active Charities', 'libookin-monthly-offer' ); ?></h3>
                        <p class="stat-number">
                            <?php 
                            $charity_count = wp_count_posts( 'libookin_charity' );
                            echo number_format_i18n( $charity_count->publish ); 
                            ?>
                        </p>
                    </div>

                    <div class="stat-box">
                        <h3><?php esc_html_e( 'Current Leader', 'libookin-monthly-offer' ); ?></h3>
                        <p class="stat-text">
                            <?php 
                            if ( $winner ) {
                                echo esc_html( get_the_title( $winner->charity_id ) );
                            } else {
                                esc_html_e( 'No votes yet', 'libookin-monthly-offer' );
                            }
                            ?>
                        </p>
                    </div>
                </div>

                <div class="libookin-current-results">
                    <h2><?php esc_html_e( 'Current Month Results', 'libookin-monthly-offer' ); ?></h2>
                    <?php if ( ! empty( $results ) ) : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Rank', 'libookin-monthly-offer' ); ?></th>
                                    <th><?php esc_html_e( 'Charity', 'libookin-monthly-offer' ); ?></th>
                                    <th><?php esc_html_e( 'Votes', 'libookin-monthly-offer' ); ?></th>
                                    <th><?php esc_html_e( 'Percentage', 'libookin-monthly-offer' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                foreach ( $results as $result ) : 
                                    $percentage = $total_votes > 0 ? ( $result->vote_count / $total_votes ) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html( $rank++ ); ?></td>
                                        <td>
                                            <?php echo esc_html( get_the_title( $result->charity_id ) ); ?>
                                        </td>
                                        <td><?php echo number_format_i18n( $result->vote_count ); ?></td>
                                        <td><?php echo number_format_i18n( $percentage, 2 ); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p><?php esc_html_e( 'No votes recorded for this month yet.', 'libookin-monthly-offer' ); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Display vote results page
     */
    public function display_vote_results() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Get selected month or current month
        $selected_month = isset( $_GET['month'] ) ? sanitize_text_field( $_GET['month'] ) : date( 'Y-m' );
        $results = Libookin_MO_Database::get_vote_results( $selected_month );
        $votes_details = Libookin_MO_Database::get_votes_with_details( $selected_month );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Vote Results', 'libookin-monthly-offer' ); ?></h1>
            
            <div class="libookin-admin-filters">
                <form method="get">
                    <input type="hidden" name="page" value="libookin-vote-results">
                    <label for="month-select"><?php esc_html_e( 'Select Month:', 'libookin-monthly-offer' ); ?></label>
                    <input type="month" id="month-select" name="month" value="<?php echo esc_attr( $selected_month ); ?>">
                    <button type="submit" class="button"><?php esc_html_e( 'Filter', 'libookin-monthly-offer' ); ?></button>
                    
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=libookin-vote-results&action=export_csv&month=' . $selected_month ) ); ?>" 
                       class="button button-primary">
                        <?php esc_html_e( 'Export CSV', 'libookin-monthly-offer' ); ?>
                    </a>
                </form>
            </div>

            <h2><?php echo esc_html( date( 'F Y', strtotime( $selected_month . '-01' ) ) ); ?></h2>

            <?php if ( ! empty( $results ) ) : ?>
                <h3><?php esc_html_e( 'Summary', 'libookin-monthly-offer' ); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Charity', 'libookin-monthly-offer' ); ?></th>
                            <th><?php esc_html_e( 'Total Votes', 'libookin-monthly-offer' ); ?></th>
                            <th><?php esc_html_e( 'Percentage', 'libookin-monthly-offer' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_votes = array_sum( wp_list_pluck( $results, 'vote_count' ) );
                        foreach ( $results as $result ) : 
                            $percentage = $total_votes > 0 ? ( $result->vote_count / $total_votes ) * 100 : 0;
                            ?>
                            <tr>
                                <td><?php echo esc_html( get_the_title( $result->charity_id ) ); ?></td>
                                <td><?php echo number_format_i18n( $result->vote_count ); ?></td>
                                <td><?php echo number_format_i18n( $percentage, 2 ); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h3><?php esc_html_e( 'Detailed Votes', 'libookin-monthly-offer' ); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Order ID', 'libookin-monthly-offer' ); ?></th>
                            <th><?php esc_html_e( 'Customer Name', 'libookin-monthly-offer' ); ?></th>
                            <th><?php esc_html_e( 'Charity', 'libookin-monthly-offer' ); ?></th>
                            <th><?php esc_html_e( 'Vote Date', 'libookin-monthly-offer' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $votes_details as $vote ) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $vote->order_id . '&action=edit' ) ); ?>">
                                        #<?php echo esc_html( $vote->order_id ); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ( $vote->user_id ) :
                                        $user = get_userdata( $vote->user_id );
                                        $name = $user->first_name . ' ' . $user->last_name;
                                    ?>
                                        <a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $vote->user_id ) ); ?>">
                                            <?php echo esc_html( $name ); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php esc_html_e( 'Guest', 'libookin-monthly-offer' ); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( $vote->charity_name ); ?></td>
                                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $vote->vote_date ) ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e( 'No votes recorded for this month.', 'libookin-monthly-offer' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Handle CSV export
     */
    public function handle_csv_export() {
        if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'export_csv' ) {
            return;
        }

        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'libookin-vote-results' ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $month = isset( $_GET['month'] ) ? sanitize_text_field( $_GET['month'] ) : date( 'Y-m' );
        $votes = Libookin_MO_Database::get_votes_with_details( $month );

        if ( empty( $votes ) ) {
            wp_die( esc_html__( 'No data to export', 'libookin-monthly-offer' ) );
        }

        // Set headers for CSV download
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=vote-results-' . $month . '.csv' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        // Create file pointer
        $output = fopen( 'php://output', 'w' );

        // Add BOM for UTF-8
        fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) );

        // Add headers
        fputcsv( $output, array( 'Order ID', 'User ID', 'Charity', 'Vote Date' ) );

        // Add data
        foreach ( $votes as $vote ) {
            fputcsv( $output, array(
                $vote->order_id,
                $vote->user_id ? $vote->user_id : 'Guest',
                $vote->charity_name,
                $vote->vote_date,
            ) );
        }

        fclose( $output );
        exit;
    }

    /**
     * Display charity earnings page
     */
    public function display_charity_earnings() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $selected_month = isset( $_GET['month'] ) ? sanitize_text_field( $_GET['month'] ) : date( 'Y-m' );
        $earnings = Libookin_MO_Database::get_charity_earnings( $selected_month );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Charity Earnings', 'libookin-monthly-offer' ); ?></h1>
            
            <div class="libookin-charity-earnings">
                <div class="libookin-admin-filters">
                    <form method="get">
                        <input type="hidden" name="page" value="libookin-charity-earnings">
                        <label for="month-select"><?php esc_html_e( 'Select Month:', 'libookin-monthly-offer' ); ?></label>
                        <input type="month" id="month-select" name="month" value="<?php echo esc_attr( $selected_month ); ?>">
                        <button type="submit" class="button"><?php esc_html_e( 'Filter', 'libookin-monthly-offer' ); ?></button>
                    </form>
                </div>
                <div class="libookin-earnings-table">
                    <table class="wp-list-table widefat striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Charity', 'libookin-monthly-offer' ); ?></th>
                                <th><?php esc_html_e( 'Total Earnings', 'libookin-monthly-offer' ); ?></th>
                            </tr>
                        </thead>
                        <?php if ( empty( $earnings ) ) : ?>
                            <tbody>
                                <tr>
                                    <td colspan="2"><?php esc_html_e( 'No earnings recorded for this month.', 'libookin-monthly-offer' ); ?></td>
                                </tr>
                            </tbody>
                        <?php else : ?>
                        <tbody>
                            <?php foreach ( $earnings as $earning ) : ?>
                                <tr>
                                    <td><?php echo esc_html( get_the_title( $earning->charity_id ) ); ?></td>
                                    <td><?php echo wc_price( $earning->total_earnings ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Settings page
     */
    public function display_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Handle form submission
        if ( isset( $_POST['libookin_mo_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['libookin_mo_settings_nonce'] ) ), 'libookin_mo_save_settings' ) ) {
            $popup_link = isset( $_POST['popup_link'] ) ? esc_url_raw( $_POST['popup_link'] ) : '';
            $offer_of_the_month_page_link = isset( $_POST['offer_of_the_month_page_link'] ) ? esc_url_raw( $_POST['offer_of_the_month_page_link'] ) : '';
            $offer_box_tagline = isset( $_POST['offer_box_tagline'] ) ? sanitize_text_field( $_POST['offer_box_tagline'] ) : '';
            $learn_more_title = isset( $_POST['learn_more_title'] ) ? sanitize_text_field( $_POST['learn_more_title'] ) : '';
            $learn_more_description = isset( $_POST['learn_more_description'] ) ? sanitize_textarea_field( $_POST['learn_more_description'] ) : '';

            update_option( 'libookin_popup_link', $popup_link );
            update_option( 'libookin_offer_of_the_month_page_link', $offer_of_the_month_page_link );
            update_option( 'libookin_offer_box_tagline', $offer_box_tagline );
            update_option( 'libookin_learn_more_title', $learn_more_title );
            update_option( 'libookin_learn_more_description', $learn_more_description );

            echo '<div class="updated"><p>' . esc_html__( 'Settings saved.', 'libookin-monthly-offer' ) . '</p></div>';
        }

        $popup_link = get_option( 'libookin_popup_link', '' );
        $offer_of_the_month_page_link = get_option( 'libookin_offer_of_the_month_page_link', '' );
        $offer_box_tagline = get_option( 'libookin_offer_box_tagline', '' );
        $learn_more_title = get_option( 'libookin_learn_more_title', '' );
        $learn_more_description = get_option( 'libookin_learn_more_description', '' );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Monthly Offer Settings', 'libookin-monthly-offer' ); ?></h1>
            <form method="post" action="">
                <?php wp_nonce_field( 'libookin_mo_save_settings', 'libookin_mo_settings_nonce' ); ?>

                <table class="form-table">
                   <tr>
                    <th scope="row"><?php esc_html_e( 'Popup link', 'libookin-monthly-offer' ); ?></th>
                    <td>
                        <input 
                            type="text" 
                            name="popup_link" 
                            value="<?php echo esc_url( $popup_link ); ?>" 
                            class="regular-text" 
                            placeholder="<?php esc_attr_e( 'https://example.com/popup', 'libookin-monthly-offer' ); ?>">
                        <p class="description"><?php esc_html_e( 'Link to the popup content.', 'libookin-monthly-offer' ); ?></p>
                   </tr>
                   <tr>
                    <th scope="row"><?php esc_html_e( 'Offer of the Month Page Link', 'libookin-monthly-offer' ); ?></th>
                    <td>
                        <input 
                            type="text" 
                            name="offer_of_the_month_page_link" 
                            value="<?php echo esc_url( $offer_of_the_month_page_link ); ?>" 
                            class="regular-text" 
                            placeholder="<?php esc_attr_e( 'https://example.com/offer-of-the-month', 'libookin-monthly-offer' ); ?>">
                        <p class="description"><?php esc_html_e( 'Link to the offer of the month page.', 'libookin-monthly-offer' ); ?></p>
                    </td>
                   </tr>
                   <tr>
                    <th scope="row"><?php esc_html_e( 'Offer Box Tagline', 'libookin-monthly-offer' ); ?></th>
                    <td>
                        <input 
                            type="text" 
                            name="offer_box_tagline" 
                            value="<?php echo esc_attr( $offer_box_tagline ); ?>" 
                            class="regular-text" 
                            placeholder="<?php esc_attr_e( 'Et laissez parler votre coeur', 'libookin-monthly-offer' ); ?>">
                        <p class="description"><?php esc_html_e( 'Tagline for the offer box.', 'libookin-monthly-offer' ); ?></p>
                    </td>
                   </tr>
                   <tr>
                    <th scope="row"><?php esc_html_e( 'Learn More Title', 'libookin-monthly-offer' ); ?></th>
                    <td>
                        <input 
                            type="text" 
                            name="learn_more_title" 
                            value="<?php echo esc_attr( $learn_more_title ); ?>" 
                            class="regular-text" 
                            placeholder="<?php esc_attr_e( 'Learn More', 'libookin-monthly-offer' ); ?>">
                        <p class="description"><?php esc_html_e( 'Title for the learn more section.', 'libookin-monthly-offer' ); ?></p>
                    </td>
                   </tr>
                   <tr>
                    <th scope="row"><?php esc_html_e( 'Learn More Description', 'libookin-monthly-offer' ); ?></th>
                    <td>
                        <textarea 
                            name="learn_more_description" 
                            class="regular-text" 
                            placeholder="<?php esc_attr_e( 'Discover more about our special offer.', 'libookin-monthly-offer' ); ?>"
                        ><?php echo esc_textarea( $learn_more_description ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Description for the learn more section.', 'libookin-monthly-offer' ); ?></p>
                    </td>
                   </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Save Settings', 'libookin-monthly-offer' ); ?></th>
                        <td>
                            <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Changes', 'libookin-monthly-offer' ); ?></button>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <?php
    }
}
