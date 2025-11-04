<?php
/**
 * Shortcodes
 *
 * @package Libookin_Monthly_Offer
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcodes class
 */
class Libookin_MO_Shortcodes {

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
        add_shortcode( 'libookin_vote_counter', array( $this, 'display_vote_counter' ) );
        add_shortcode( 'libookin_current_charity', array( $this, 'display_current_charity' ) );
    }

    /**
     * Display vote counter widget
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function display_vote_counter( $atts ) {
        $atts = shortcode_atts( array(
            'limit' => 3,
        ), $atts );

        $current_month = date( 'Y-m' );
        $results = Libookin_MO_Database::get_vote_results( $current_month );

        if ( empty( $results ) ) {
            return '<div class="libookin-vote-counter"><p>' . esc_html__( 'No votes yet this month.', 'libookin-monthly-offer' ) . '</p></div>';
        }

        // Calculate total votes
        $total_votes = array_sum( wp_list_pluck( $results, 'vote_count' ) );

        // Limit results
        $results = array_slice( $results, 0, intval( $atts['limit'] ) );

        ob_start();
        ?>
        <div class="libookin-vote-counter">
            <h3 class="vote-counter-title"><?php esc_html_e( 'Vote for Next Month\'s Charity', 'libookin-monthly-offer' ); ?></h3>
            
            <div class="vote-counter-items">
                <?php foreach ( $results as $result ) : 
                    $charity = get_post( $result->charity_id );
                    if ( ! $charity ) {
                        continue;
                    }
                    
                    $percentage = $total_votes > 0 ? ( $result->vote_count / $total_votes ) * 100 : 0;
                    ?>
                    <div class="vote-counter-item">
                        <div class="charity-header">
                            <?php if ( has_post_thumbnail( $charity->ID ) ) : ?>
                                <div class="charity-thumb">
                                    <?php echo get_the_post_thumbnail( $charity->ID, 'thumbnail' ); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="charity-name">
                                <h4><?php echo esc_html( $charity->post_title ); ?></h4>
                            </div>
                        </div>
                        
                        <div class="vote-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo esc_attr( $percentage ); ?>%;"></div>
                            </div>
                            <div class="progress-stats">
                                <span class="vote-count"><?php echo number_format_i18n( $result->vote_count ); ?> <?php esc_html_e( 'votes', 'libookin-monthly-offer' ); ?></span>
                                <span class="vote-percentage"><?php echo number_format_i18n( $percentage, 1 ); ?>%</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="vote-counter-footer">
                <p class="total-votes">
                    <?php 
                    printf( 
                        esc_html__( 'Total votes this month: %s', 'libookin-monthly-offer' ), 
                        '<strong>' . number_format_i18n( $total_votes ) . '</strong>' 
                    ); 
                    ?>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Display current winning charity
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function display_current_charity( $atts ) {
        $atts = shortcode_atts( array(
            'show_votes' => 'yes',
            'show_description' => 'yes',
        ), $atts );

        $current_month = date( 'Y-m' );
        $winner = Libookin_MO_Database::get_winning_charity( $current_month );

        if ( ! $winner ) {
            return '<div class="libookin-current-charity"><p>' . esc_html__( 'No charity selected yet.', 'libookin-monthly-offer' ) . '</p></div>';
        }

        $charity = get_post( $winner->charity_id );
        if ( ! $charity ) {
            return '';
        }

        ob_start();
        ?>
        <div class="libookin-current-charity">
            <h3 class="current-charity-title"><?php esc_html_e( 'This Month\'s Charity', 'libookin-monthly-offer' ); ?></h3>
            
            <div class="current-charity-content">
                <?php if ( has_post_thumbnail( $charity->ID ) ) : ?>
                    <div class="current-charity-image">
                        <?php echo get_the_post_thumbnail( $charity->ID, 'medium' ); ?>
                    </div>
                <?php endif; ?>
                
                <div class="current-charity-info">
                    <h4><?php echo esc_html( $charity->post_title ); ?></h4>
                    
                    <?php if ( $atts['show_description'] === 'yes' ) : ?>
                        <div class="current-charity-description">
                            <?php echo wp_kses_post( wpautop( $charity->post_content ) ); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ( $atts['show_votes'] === 'yes' ) : ?>
                        <p class="current-charity-votes">
                            <?php 
                            printf( 
                                esc_html__( 'Received %s votes', 'libookin-monthly-offer' ), 
                                '<strong>' . number_format_i18n( $winner->vote_count ) . '</strong>' 
                            ); 
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
