<?php
// prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//add charity data tab
add_filter( 'woocommerce_product_data_tabs', 'libookin_charity_product_data_tabs' );

function libookin_charity_product_data_tabs( $tabs ) {
    $tabs['charity'] = array(
        'label'    => __( 'Charity', 'libookin-monthly-offer' ),
        'target'   => 'libookin_charity_data',
        'class'    => array( 'show_if_woosb', ),
    );
    return $tabs;
}

//render charity data panel
add_filter( 'woocommerce_product_data_panels', 'libookin_charity_product_data_panels' );
function libookin_charity_product_data_panels() {
    ?>
    <div id="libookin_charity_data" class="panel woocommerce_options_panel">
        <?php 
        $charities = get_posts( array(
            'post_type' => 'libookin_charity',
            'posts_per_page' => -1,
        ) );
        ?>
        <div class="options_group">
            <?php
            woocommerce_wp_select(
                array(
                    'id' => '_libookin_charity',
                    'label' => __( 'Charity', 'libookin-monthly-offer' ),
                    'placeholder' => __( 'Enter charity name', 'libookin-monthly-offer' ),
                    'desc_tip' => 'true',
                    'description' => __( 'Enter the charity name', 'libookin-monthly-offer' ),
                    'options' => wp_list_pluck( $charities, 'post_title', 'ID' ),
                )
            );
            ?>
        </div>
    </div>
    <?php
}

//save the charity data
add_action( 'woocommerce_process_product_meta', 'libookin_save_charity_data' );
function libookin_save_charity_data( $post_id ) {
    if ( isset( $_POST['_libookin_charity'] ) ) {
        update_post_meta( $post_id, '_libookin_charity', sanitize_text_field( $_POST['_libookin_charity'] ) );
    }
}