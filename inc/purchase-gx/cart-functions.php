<?php
/**
 * Functions for dynamic pricing in cart and product bundling.
 * 
 * Included here
 * ---
 * gd_dynamic_pricing - Bundles products in cart according to product type and adjusts prices.
 * add_more_to_cart   - Modified functionality for the add to cart button allowing more than one item at a time.
 * bbloomer_split_product_individual_cart_items - Split cart items into individual items (not lumped by quantity).
 */

/* Add meta data to cart items.
*
* @param array $cart_item_data
* @param int   $product_id
* @param int   $variation_id
*
* @return array
*/

global $gd_env;	// Grab environment type to determine if we are in development, staging or production
global $gx_product_ids; // Get product ids for Gx programs

/* Gx Product IDs */
//5121 - Genetic Direction Gx
//5159 - Genetic Direction 23andMe
//7633 - LyfeCode Gx
//7635 - LyfeCode 23andMe

add_filter( 'woocommerce_add_cart_item_data', 'add_gx_metadata_to_cart_item', 10, 3 );
function add_gx_metadata_to_cart_item( $cart_item_data, $product_id, $variation_id ) {
    
    global $gd_env;
    global $gx_product_ids;

    $metadata = array(
        "first_name"        => filter_input( INPUT_POST, 'first_name' ),
        "last_name"         => filter_input( INPUT_POST, 'last_name' ),
        "email_address"     => filter_input( INPUT_POST, 'email_address' ),
        "gx_programs"       => implode( ",", filter_input(INPUT_POST, 'gx_programs', FILTER_DEFAULT , FILTER_REQUIRE_ARRAY) ),
    );

    // Loop through gx product ids to check if cart item is a valid gx product
    foreach ( $gx_product_ids as $gx_product_id ) {
        if ( $product_id === $gx_product_id ) {

            //Update cart item meta data
            foreach ( $metadata as $meta_key => $meta_field ) {
                $cart_item_data[$meta_key] = $meta_field;  
            }
        }
    }

    return $cart_item_data;
    }

/* Display metadata text in the cart.
*
* @param array $item_data
* @param array $cart_item
*
* @return array
*/
add_filter( 'woocommerce_get_item_data', 'display_gx_metadata_text_cart', 10, 2 );
function display_gx_metadata_text_cart( $item_data, $cart_item ) {

    global $gd_env;
    global $gx_product_ids;

    // Loop through gx product ids to check if cart item is a valid gx product
    foreach ( $gx_product_ids as $gx_product_id ) {
        if ( $cart_item['product_id'] == $gx_product_id ) {

            // Update our program list to output with a comma and space after each program
            $gx_programs = str_replace( ',', ', ', $cart_item['gx_programs']);

            $item_data[] = array(
                'key'     => __( 'First Name', 'salient' ),
                'value'   => wc_clean( $cart_item['first_name'] ),
                'display' => '',
            );
            $item_data[] = array(
                'key'     => __( 'Last Name', 'salient' ),
                'value'   => wc_clean( $cart_item['last_name'] ),
                'display' => '',
            );
            $item_data[] = array(
                'key'     => __( 'Email Address', 'salient' ),
                'value'   => wc_clean( $cart_item['email_address'] ),
                'display' => '',
            );
            $item_data[] = array(
                'key'     => __( 'Programs', 'salient' ),
                'value'   => wc_clean( $gx_programs ),
                'display' => '',
            );
        }
    }

    return $item_data;
}

/**
 * Add metadata text to order.
 *
 * @param WC_Order_Item_Product $item
 * @param string                $cart_item_key
 * @param array                 $values
 * @param WC_Order              $order
 */
add_action( 'woocommerce_checkout_create_order_line_item', 'gx_add_metadata_to_order_items', 10, 4 );
function gx_add_metadata_to_order_items( $item, $cart_item_key, $values, $order ) {

    global $gd_env;
    global $gx_product_ids;

    // Loop through gx product ids to check if cart item is a valid gx product
    foreach ( $gx_product_ids as $gx_product_id ) {
        if ( $item['product_id'] == $gx_product_id ) {
            $item->add_meta_data( __( 'First Name', 'salient' ), $values['first_name'] );
            $item->add_meta_data( __( 'Last Name', 'salient' ), $values['last_name'] );
            $item->add_meta_data( __( 'Email Address', 'salient' ), $values['email_address'] );
            $item->add_meta_data( __( 'Programs', 'salient' ), $values['gx_programs'] );
        }
    }
}

add_action( 'woocommerce_before_calculate_totals', 'add_custom_price', 20, 1);
function add_custom_price( $cart_obj ) {

    // This is necessary for WC 3.0+
    if ( is_admin() && ! defined( 'DOING_AJAX' ) )
        return;

    // Avoiding hook repetition (when using price calculations for example)
    if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 )
        return;

    global $gd_env;
    global $gx_product_ids;

    $gx_product_prices = array(
        5121 => array(299,449,549,649),
        5159 => array(159,239,279,319),
        7633 => array(250,350,425,500),
        7635 => array(150,207,237,262),
    );

    // Loop through cart items
    foreach ( $cart_obj->get_cart() as $cart_item ) {

        // Loop through gx product ids to check if cart item is a valid gx product
        foreach ( $gx_product_ids as $gx_product_id ) {

            // Adjust pricing for Gx products
            if ( $cart_item['product_id'] == $gx_product_id ) {

                //Count how many programs have been selected
                $program_count = count(explode( ',', $cart_item['gx_programs'] ) );

                // Update pricing based on program count
                $cart_item['data']->set_price( $gx_product_prices[$gx_product_id][$program_count - 1] );
            }
        }
    }
}

/**
 * Validate that the user has selected at least one Gx program
 */
add_filter( 'woocommerce_add_to_cart_validation', 'add_gx_programs_validation', 10, 5 );
function add_gx_programs_validation( $passed ) { 

    global $gd_env;
    global $gx_product_ids;

    // Loop through gx product ids to check if cart item is a valid gx product
    foreach ( $gx_product_ids as $gx_product_id ) {
        if ( $_REQUEST['product_id'] == $gx_product_id ) {
            if ( empty( $_REQUEST['gx_programs'] )) {
                wc_add_notice( __( 'Please select at least 1 Gx program', 'woocommerce' ), 'error' );
                $passed = false;
            }
        }
    }
    
    return $passed;
}

/**
 * Exclude products from a particular category on the shop page
 */
add_action( 'woocommerce_product_query', 'custom_pre_get_posts_query' );  
function custom_pre_get_posts_query( $q ) {

    $tax_query = (array) $q->get( 'tax_query' );

    $tax_query[] = array(
        'taxonomy' => 'product_cat',
        'field' => 'slug',
        'terms' => array( 'no-display', 'genetic-wellness' ), // Don't display products with category "no-display" on the shop.
        'operator' => 'NOT IN'
    );

    $q->set( 'tax_query', $tax_query );

}