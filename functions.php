<?php

function salient_child_enqueue_styles() {

  wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_script( 'update-cart', get_stylesheet_directory_uri() . '/js/update_cart.js' );
	
	if (is_front_page()){
		
		wp_enqueue_script( 'create-js', 'https://code.createjs.com/createjs-2015.11.26.min.js' );
		wp_enqueue_script( 'gd-canvas-js', get_stylesheet_directory_uri() . '/js/gd-canvas-slider.js', '', filemtime( get_stylesheet_directory() . '/js/gd-canvas-slider.js'));
		wp_enqueue_script( 'canvas-js', get_stylesheet_directory_uri() . '/js/canvas.js' );
	}
	
}

add_action( 'wp_enqueue_scripts', 'salient_child_enqueue_styles');

/**
 * The main switch for moving between staging and production environments.
 * 
 * Options: production, staging, or development
 */
if ($_SERVER['SERVER_NAME'] === 'geneticdirection.com') {
	$gd_env = "production";
} else if ( $_SERVER['SERVER_NAME'] === 'gendirstaging.wpengine.com' ) {
	$gd_env = "staging";
} else if ( $_SERVER['SERVER_NAME'] === 'genddev.wpengine.com' ) {
	$gd_env = "development";
}
$gx_product_ids = array(5121,5159,7633,7635);//ztw - add parent theme CSS

/**
 * The Gx program buying process
 * 
 * Required files:
 * template-purchase-gx.php -- The base template for the product page
 * /inc/purchase-gx/cart-functions.php -- Functions to control dynamic pricing for GX Programs
 * /inc/purchase-gx/main.js -- JavaScript functions for the portal page
 * /inc/purchase-gx/main.css -- Stylesheet for the portal page
 */
require_once(get_stylesheet_directory() . '/inc/purchase-gx/cart-functions.php');

function gendir_purchase_gx_styles() {
	global $post;
	global $gd_env;
	global $gx_product_ids;

	$gd_env_vars = array(
		'env' => $gd_env,
		'product_ids' => $gx_product_ids,
	);
	
	wp_enqueue_style( 'purchase-gx-styles', get_stylesheet_directory_uri() . '/inc/purchase-gx/main.css', array(), '2.1.0' );
	wp_register_script( 'purchase-gx-script', get_stylesheet_directory_uri() . '/inc/purchase-gx/main.js', array('jquery'), '2.1.0', TRUE);
	
	if ($post->post_name == 'purchase-gx-programs') {
		wp_enqueue_script( 'purchase-gx-script' );
		wp_localize_script( 'purchase-gx-script', 'php_vars', $gd_env_vars );
	}
}
add_action( 'wp_enqueue_scripts', 'gendir_purchase_gx_styles');

/**
 * We are modifying the WooCommerce checkout process with custom fields
 * that will save as item meta data. These fields collect first name, last name, and email
 * for the person who will use that product, which may be different than the individual purchasing.
 * 
 * Required files for processing product orders.
 * checkout.css - Custom styling for the checkout page.
 * thankyou.css - Custom styling for the thankyou page.
 */

function gendir_checkout_enqueue_styles() {
	wp_enqueue_style( 'checkout-styles', get_stylesheet_directory_uri() . '/inc/product-orders/checkout.css' );
	wp_enqueue_style( 'thankyou-styles', get_stylesheet_directory_uri() . '/inc/product-orders/thankyou.css' );
}
add_action( 'wp_enqueue_scripts', 'gendir_checkout_enqueue_styles');

/**
 * Extending the WooCommerce default product page to
 * match our needs. These functions will remove some actions
 * which we don't want to display on the single product page.
 * 
 * We are also adding a custom meta box to the product admin page
 * which will allow an additional image upload and secondary editor area.
 */
function gendir_remove_product_actions() {
	// Remove actions we don't want from WooCommerce hooks
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10,0);
	remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10,0);
}
add_action( 'init', 'gendir_remove_product_actions');

/**
 * Newsletter Signup Form
 * ---
 * This is a popup modal that asks new users to signup for the GD mailing list
 */
function gendir_newsletter_enqueue_styles() {
	wp_enqueue_style( 'newsletter-modal-styles', get_stylesheet_directory_uri() . '/inc/newsletter/modal.css' );
	wp_register_script( 'newsletter-modal', get_stylesheet_directory_uri() . '/inc/newsletter/modal.js', array('jquery'), '0.0.1', TRUE);
	wp_enqueue_script( 'newsletter-modal' );
}
add_action( 'wp_enqueue_scripts', 'gendir_newsletter_enqueue_styles');