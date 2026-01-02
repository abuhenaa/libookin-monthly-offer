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
        // Bundle shortcodes
        add_shortcode( 'libookin_bundle_offer', array( $this, 'display_bundle_offer' ) );
        add_shortcode( 'libookin_bundled_thumbs', array( $this, 'display_bundled_thumbs' ) );
    }

    /**
     * Enqueue frontend styles for the plugin when needed
     */
    private function enqueue_styles() {
        if ( ! wp_style_is( 'libookin-mo-styles', 'enqueued' ) ) {
            wp_enqueue_style( 'libookin-mo-styles', LIBOOKIN_MO_PLUGIN_URL . 'assets/css/styles.css', array(), LIBOOKIN_MO_VERSION );
        }
    }

    /**
     * Try to extract bundled product IDs for a woosb product.
     * Returns array of product IDs (ints) or empty array.
     * This function is defensive - it attempts several common meta keys and formats.
     */
    private function get_woosb_item_ids( $product_id ) {
        $ids = array();

        // Common meta keys used by some bundle plugins
        $keys = array( '_woosb', 'woosb', '_woosb_data', '_woosb_products', 'woosb_products', 'woosb_items' );

        foreach ( $keys as $key ) {
            $val = get_post_meta( $product_id, $key, true );
            if ( empty( $val ) ) {
                continue;
            }

            // If serialized string, try to unserialize
            if ( is_string( $val ) ) {
                $maybe = @unserialize( $val );
                if ( $maybe !== false || $val === 'b:0;' ) {
                    $val = $maybe;
                }
            }

            // If array, walk it to find integers
            if ( is_array( $val ) ) {
                $stack = $val;
                while ( ! empty( $stack ) ) {
                    $item = array_shift( $stack );
                    if ( is_int( $item ) || ctype_digit( (string) $item ) ) {
                        $ids[] = intval( $item );
                        continue;
                    }
                    if ( is_array( $item ) ) {
                        foreach ( $item as $v ) {
                            $stack[] = $v;
                        }
                        continue;
                    }
                    // If object with product_id
                    if ( is_object( $item ) ) {
                        if ( isset( $item->product_id ) ) {
                            $ids[] = intval( $item->product_id );
                        }
                        foreach ( (array) $item as $v ) {
                            $stack[] = $v;
                        }
                    }
                }
            }
        }

        // Deduplicate and validate existence
        $ids = array_filter( array_unique( $ids ), function( $id ) {
            return get_post_type( $id ) === 'product';
        } );

        return array_values( $ids );
    }

    /**
     * Shortcode: display single bundle product offer box + items using plugin markup
     * Usage: [libookin_bundle_offer id="123" title="Offer of the Month"]
     */
    public function display_bundle_offer( $atts ) {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return '<div class="libookin-bundle-offer">' . esc_html__( 'WooCommerce is required.', 'libookin-monthly-offer' ) . '</div>';
        }

        $atts = shortcode_atts( array(
            'id'    => 0,
            'title' => esc_html__( 'Offer of the Month', 'libookin-monthly-offer' ),
        ), $atts );

        $product_id = intval( $atts['id'] );
        if ( $product_id <= 0 ) {
            return '<div class="libookin-bundle-offer">' . esc_html__( 'No bundle product ID provided.', 'libookin-monthly-offer' ) . '</div>';
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return '<div class="libookin-bundle-offer">' . esc_html__( 'Bundle product not found.', 'libookin-monthly-offer' ) . '</div>';
        }

        // Enqueue styles
        $this->enqueue_styles();

        // Attempt to get bundled items
        $items = $product->get_items();        

        ob_start();
        ?>
        <section class="libookin-section libookin-bundle-offer">
            <div class="libookin-container">
                <header class="libookin-header">
                    <h2 class="libookin-title"><?php echo esc_html( $atts['title'] ); ?></h2>
                    <p class="libookin-subtitle"><?php echo esc_html__( 'libookin', 'libookin-monthly-offer' ); ?></p>
                </header>

                <div class="libookin-content">
                    <div class="libookin-books-area">
                        <div class="libookin-books-grid">
                            <?php if ( ! empty( $items ) ) :
                                foreach ( $items as $item ) :
                                    $item_id = $item['id'];
                                    $child_post = get_post( $item_id );
                                    if ( ! $child_post ) {
                                        continue;
                                    }
                                    ?>
                                    <a href="<?php echo esc_url( get_permalink( $product_id ) ); ?>">
                                    <article class="libookin-book-card">
                                        <div class="libookin-book-cover">
                                            <?php echo get_the_post_thumbnail( $item_id, 'medium' ); ?>
                                        </div>
                                        <div class="libookin-book-info">
                                            <h3 class="libookin-book-title"><?php echo esc_html( get_the_title( $item_id ) ); ?></h3>
                                            <p class="libookin-book-description"><?php echo wp_kses_post( wp_trim_words( $child_post->post_excerpt ? $child_post->post_excerpt : $child_post->post_content, 30, '...' ) ); ?></p>
                                        </div>
                                    </article>
                                    </a>
                                <?php endforeach; else : ?>
                                    <p><?php esc_html_e( 'No bundled items found for this product.', 'libookin-monthly-offer' ); ?></p>
                                <?php endif; ?>
                        </div>

                        <div class="libookin-learn-more">
                            <h4 class="libookin-learn-more-title"><?php esc_html_e( 'Learn more about the offer', 'libookin-monthly-offer' ); ?></h4>
                            <p class="libookin-learn-more-text"><?php printf( esc_html__( 'Get all %s at an incredible price.', 'libookin-monthly-offer' ), '<strong>' . number_format_i18n( count( $items ) ) . ' ' . esc_html__( 'books', 'libookin-monthly-offer' ) . '</strong>' ); ?></p>
                        </div>
                    </div>

                    <aside class="libookin-offer-box">
                        <span class="libookin-badge"><?php esc_html_e( 'Offer of the Month', 'libookin-monthly-offer' ); ?></span>
                        <div class="libookin-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
                        <p class="libookin-price-note"><?php echo esc_html__( 'for the bundle', 'libookin-monthly-offer' ); ?></p>
                        <h3 class="libookin-offer-title"><?php echo esc_html( $product->get_name() ); ?></h3>
                        <p class="libookin-offer-description"><?php echo wp_kses_post( wp_trim_words( $product->get_description(), 25, '...' ) ); ?></p>
                        <p class="libookin-tagline"><?php echo esc_html__( 'Read more, spend less', 'libookin-monthly-offer' ); ?></p>
                        <a class="libookin-cta" href="<?php echo esc_url( get_permalink( $product_id ) ); ?>"><?php esc_html_e( 'View bundle', 'libookin-monthly-offer' ); ?></a>
                    </aside>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: display thumbs for bundled items
     * Usage: [libookin_bundled_thumbs id="123" limit="5"]
     */
    public function display_bundled_thumbs( $atts ) {
        if ( ! function_exists( 'wc_get_product' ) ) {
            return '<div class="libookin-bundled-thumbs">' . esc_html__( 'WooCommerce is required.', 'libookin-monthly-offer' ) . '</div>';
        }

        $atts = shortcode_atts( array(
            'id'    => 0,
            'limit' => 5,
        ), $atts );

        $product_id = intval( $atts['id'] );
        if ( $product_id <= 0 ) {
            return '<div class="libookin-bundled-thumbs">' . esc_html__( 'No bundle product ID provided.', 'libookin-monthly-offer' ) . '</div>';
        }

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            return '<div class="libookin-bundled-thumbs">' . esc_html__( 'Bundle product not found.', 'libookin-monthly-offer' ) . '</div>';
        }

        // Enqueue styles
        $this->enqueue_styles();

        $items = array();
        if ( is_callable( array( $product, 'get_bundled_items' ) ) ) {
            $raw = $product->get_bundled_items();
            if ( is_array( $raw ) ) {
                foreach ( $raw as $r ) {
                    if ( is_object( $r ) && isset( $r->product_id ) ) {
                        $items[] = intval( $r->product_id );
                    }
                }
            }
        }

        if ( empty( $items ) ) {
            $items = $this->get_woosb_item_ids( $product_id );
        }

        if ( ! empty( $atts['limit'] ) ) {
            $items = array_slice( $items, 0, intval( $atts['limit'] ) );
        }

        ob_start();
        ?>
        <div class="libookin-bundled-thumbs">
            <div class="libookin-books-grid">
                <?php if ( ! empty( $items ) ) : foreach ( $items as $child_id ) :
                    $child_post = get_post( $child_id );
                    if ( ! $child_post ) {
                        continue;
                    }
                    // Author detection
                    $author = get_post_meta( $child_id, 'author', true );
                    if ( empty( $author ) ) {
                        $terms = get_the_terms( $child_id, 'pa_author' );
                        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                            $author = esc_html( $terms[0]->name );
                        }
                    }
                    if ( empty( $author ) ) {
                        $post_author_id = get_post_field( 'post_author', $child_id );
                        $author = $post_author_id ? get_the_author_meta( 'display_name', $post_author_id ) : '';
                    }
                    ?>
                    <article class="libookin-book-card">
                        <div class="libookin-book-cover">
                            <?php echo get_the_post_thumbnail( $child_id, 'medium' ); ?>
                        </div>
                        <div class="libookin-book-info">
                            <h3 class="libookin-book-title"><?php echo esc_html( get_the_title( $child_id ) ); ?></h3>
                            <?php if ( $author ) : ?><p class="libookin-book-description"><?php echo esc_html( $author ); ?></p><?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; else : ?>
                    <p><?php esc_html_e( 'No bundled items found.', 'libookin-monthly-offer' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
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
