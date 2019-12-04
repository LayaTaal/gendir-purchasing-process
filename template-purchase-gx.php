<?php

/**
 * Template Name: Gx Purchase Page
 * This is the template for our custom page to select and bundle Gx
 * products and collect relevant meta data.
*/

get_header( 'shop' );

global $wp;
$current_url = get_site_url() . '/purchase-gx-programs/';
$action_type = filter_input( INPUT_POST, 'action_type' );               // Are we adding an item or updating an item?
$item_count = WC()->cart->get_cart_contents_count();                    // Are there any items in the cart?

// Delete item if button clicked.
if ( $_GET['action'] == 'delete') {
    WC()->cart->remove_cart_item( $_GET['key'] );
}

$cart_items = WC()->cart->get_cart();

/**
 * Check which page we are coming from so we can route the user 
 * to the right sequence.
 * 
 * Options: regular, 23andMe, lyfecode
 */
$product_type = (isset($_GET["product_type"])) ? $_GET["product_type"] : 'regular';

/**
 * Define Product class
 */
class GxProduct {
    var $name;
    var $id;

    function __construct($product_name) {
        $this->name = $product_name;
    }
    function set_name($new_name) { 
        $this->name = $new_name;  
    }
    function get_name() {
        return $this->name;
    }
}

/**
 * Create product and setup properties
 */
$gx_product = new GxProduct($product_type);

if ($gx_product->get_name() == 'regular') { 
    $gx_product->id = 5121;
    $gx_product->title = 'Gx Programs';
    $gx_product->prices = array(299,449,549,649);
} else if ($gx_product->get_name() == '23andMe') {
    $gx_product->id = 5159;
    $gx_product->title = '23andMe Gx Programs';
    $gx_product->prices = array(159,239,279,319);
} else if ($gx_product->get_name() == 'lyfecode') {
    // Set product id according to environment
    // dev and prod envs have the same product ids
    $gx_product->id = 7633;
    $gx_product->title = 'Lyfecode Gx Programs';
    $gx_product->prices = array(250,350,425,500);
};

$gx_product_codes = array();
$gx_product_codes['regular'] = array(5121);
$gx_product_codes['23andMe'] = array(5159);
// Set product id according to environment
// dev and prod envs have the same product ids
$gx_product_codes['lyfecode'] = array(7633,7635);

$gx_price_table = array(
    5121 => array(299,449,549,649),
    5159 => array(159,239,279,319),
    7633 => array(250,350,425,500), //production & dev
    7635 => array(150,207,237,262),    
);

/**
 * Retreive page information
 */
$the_title = $post->post_title;
$the_content = apply_filters('the_content', $post->post_content);

?>

<div class="container-wrap">
    <div class="main-content container">
        <!-- Page Header -->
        <div class="row">
            <header class="gx-purchase-header">
                <?php echo $the_content; ?>
            </header>
        </div>

        <?php
        // Display error message if no programs were selected
        if ($action_type === 'add' && empty( $_REQUEST['gx_programs'] ) ) {

            ?>

            <div class="woocommerce-notices-wrapper" style="background-color: #fff !important ">
                <ul class="woocommerce-error" role="alert" style="background-color: #fff !important">
                    <li style="background-color: #fff !important">Please select at least 1 Gx program.</li>
                </ul>
            </div>

            <?php
        }

        // Display  message if item deleted successfully
        if ( $_GET['action'] == 'delete') {

            ?>

            <div class="woocommerce-notices-wrapper" style="background-color: #fff !important ">
                <ul class="woocommerce-error" role="alert" style="background-color: #fff !important">
                    <li style="background-color: #fff !important">Order removed successfully.</li>
                </ul>
            </div>

        <?php } ?>

        <header class="gx-purchase-header">
            <h1 class="gx-step__title">Purchase <?php echo $gx_product->title; ?></h1>
            <?php if ($gx_product->get_name() == 'lyfecode') { ?>
                <a href="http://www.lyfecodegc.com/" class="btn-lyfecode">Return to Lyfecode</a>
            <?php } ?>
        </header>

        <div id="gx-bundles" class="row gx-step" data-product-id="<?php echo $gx_product->id ?>" data-product-name="<?php echo $gx_product->get_name() ?>">

            <div class="gx-step__container">
                <?php

                // Check if we have any cart contents, if not build an empty form, otherwise populate Gx bundles
                if ( $item_count == 0 ) {

                    //Setup array to pass to function
                    $bundle_options = array(
                        'pricing'       => $gx_product->prices,
                    );
                    
                    // Add html for our initial bundle
                    echo add_bundle_html($gx_product, 1, $bundle_options);

                } else { // if we have cart items

                    // loop through our cart items
                    $count = 1;
                    $has_gx_bundle = false;

                    foreach ($cart_items as $cart_item) { 

                        // First we want to apply any item updates before repopulating the item
                        
                        if ($action_type === 'update' && $cart_item['key'] == filter_input( INPUT_POST, 'unique_key' )) {
                            $cart_item['first_name'] = filter_input( INPUT_POST, 'first_name' );
                            $cart_item['last_name'] = filter_input( INPUT_POST, 'last_name' );
                            $cart_item['email_address'] = filter_input( INPUT_POST, 'email_address' );
                            $cart_item['gx_programs'] = implode(",", filter_input(INPUT_POST, 'gx_programs', FILTER_DEFAULT , FILTER_REQUIRE_ARRAY));
                            WC()->cart->cart_contents[$cart_item['key']] = $cart_item;
                        }
                        
                        // Check if the cart item is a Gx bundle
                        // Note: Need to improve this when going to production for GD
                        foreach ( $gx_product_codes[$gx_product->get_name()] as $gx_product_code ) {

                            if ( $cart_item['product_id'] === $gx_product_code ) {

                                // True if we already have a bundle in cart
                                $has_gx_bundle = true;

                                // Put selected programs into array
                                $gx_programs = explode(",", $cart_item['gx_programs']);
                                $gx_programs = array_filter(array_map('trim', $gx_programs));

                                // Setup our action for deleting this bundle
                                $delete_path = '?product_type=' . $gx_product->name . '&action=delete&key=' . $cart_item['key'];
                                $full_path = $current_url . $delete_path;

                                //Setup array to pass to function
                                $bundle_options = array(
                                    'action'        => 'update',
                                    'first_name'    => $cart_item['first_name'],
                                    'last_name'     => $cart_item['last_name'],
                                    'email_address' => $cart_item['email_address'],
                                    'gx_programs'   => $gx_programs,
                                    'pricing'       => $gx_price_table[$cart_item['product_id']],
                                    'product_id'    => $cart_item['product_id'],
                                    'unique_key'    => $cart_item['key'],
                                    'delete_url'    => $full_path,                          
                                );

                                echo add_bundle_html($gx_product, $count, $bundle_options);

                                $count++; // only increment count for gx bundles

                            }

                        }

                    } //end foreach

                    if ( ! $has_gx_bundle ) {
                        // if we have products in cart but no bundles, add a fresh one 

                        //Setup array to pass to function
                        $bundle_options = array(
                            'pricing'       => $gx_product->prices,
                        );

                        echo add_bundle_html($gx_product, 1, $bundle_options);
                    }

                    WC()->cart->set_session();

                } ?>

            </div><!-- .gx-step__container -->

            <div class="gx-add-additional">
                <p>Buying programs for more than one person? <a class="add-bundle-btn">Add Another Order</a></p>
            </div>

            <div class="gx-cart-btn-container">
                <a class="button gx-add-to-cart-btn" href="<?php echo get_site_url() ?>/cart/">Go to Cart</a>
            </div>

            </div>

    </div><!-- .main-content -->
</div>

<?php get_footer(); ?>

<?php
function add_bundle_html($gxProduct, $count, $options = array()) {
    $defaults = array(
        'action'        => 'add',
        'first_name'    => '',
        'last_name'     => '',
        'email_address' => '',
        'gx_programs'   => array(),
        'pricing'       => array(),
        'product_id'    => '',
        'unique_key'    => '',
    );
    $config = array_merge($defaults, $options);
    global $gx_product_codes;
?>
    <!-- Gx Bundle -->
    <div id="gxbundle-<?php echo $count ?>" class="gx-bundle <?php echo ($config['action'] == 'update') ? 'gx-bundle--filled' : ''; ?>">
        <header class="gx-bundle__header">
            <?php if ( $config['first_name'] == '') { ?>
                <h2><?php echo 'Individual ' . $count . ' Details'; ?></h2>
            <?php } else { ?>
                <h2><?php echo $config['first_name'] . ' ' . $config['last_name'] ?></h2>
                <div class="gx-bundle__header__delete">
                    <a class="modify-bundle">Modify</a> 
                    |
                    <a href="<?php echo $config['delete_url'] ?>">Delete</a>
                </div>
                <div class="gx-bundle__header__programs">
                    <span>Selected Programs: <?php echo implode(', ', $config['gx_programs']) ?></span>
                </div>
            <?php } ?>
        </header>
        <form id="js-gx-bundle-<?php echo $count ?>__form" class="gx-bundle__form cart" method="post" enctype="multipart/form-data">
            <?php
            /**
             * Form Step 1: Determine if they are already a 23andMe customer
             * 
             * Note: at the moment this is active ONLY for LyfeCode
             */
            if ($gxProduct->get_name() == 'lyfecode') { 
            ?>
            <fieldset id="gx-customer-type" class="gx-bundle__form__fieldset">
                <legend>Are you already a 23andMe customer (Health + Ancestry Service ONLY)? <abbr class="required" title="required">*</abbr></legend>
                <label for="yes"><input type="radio" id="yes" name="existing-customer" class="customer-type-radio" value="yes" <?php echo ($config['product_id'] === $gx_product_codes['lyfecode'][1]) ? 'checked': ''; ?>> Yes</label>
                <label for="no"><input type="radio" id="no" name="existing-customer" class="customer-type-radio" value="no" <?php echo ($config['product_id'] === $gx_product_codes['lyfecode'][0]) ? 'checked': ''; ?>> No</label>
            </fieldset>
            <?php } ?>
            <fieldset id="gx-customer-details" class="gx-bundle__form__fieldset">
                <legend>Please provide the name and email address for this order.</legend>
                <div class="fieldset-flex">
                    <label for="first_name" autocomplete="off">First Name <abbr class="required" title="required">*</abbr>
                        <input type="text" name="first_name" id="first_name_<?php echo $count ?>" value="<?php echo $config['first_name'] ?>" required>
                    </label>
                    <label for="last_name" autocomplete="off">Last Name <abbr class="required" title="required">*</abbr>
                        <input type="text" name="last_name" id="last_name_<?php echo $count ?>" value="<?php echo $config['last_name'] ?>" required>
                    </label>
                    <label for="email_address"  autocomplete="off">Email Address <abbr class="required" title="required">*</abbr>
                        <input type="email" name="email_address" id="email_address_<?php echo $count ?>" value="<?php echo $config['email_address'] ?>" class="bundle-email" required>
                    </label>
                </div>
            </fieldset>
            <fieldset id="gx-program-options" class="gx-bundle__form__fieldset">
                <legend>Please select the programs you wish to purchase.</legend>
                <div class="fieldset-flex">
                    <div class="gx-programs">
                        <div>
                            <label for="gx-slim-<?php echo $count ?>"><input type="checkbox" class="gx-program-checkbox" id="gx-slim-<?php echo $count ?>" name="gx_programs[]" value="GxSlim" <?php if (in_array( 'GxSlim', $config['gx_programs'] )) { echo 'checked'; } ?> autocomplete="off"> GxSlim</label>
                        </div>
                        <div>
                            <label for="gx-renew-<?php echo $count ?>"><input type="checkbox" class="gx-program-checkbox" id="gx-renew-<?php echo $count ?>" name="gx_programs[]" value="GxRenew" <?php if (in_array( 'GxRenew', $config['gx_programs'] )) { echo 'checked'; } ?> autocomplete="off"> GxRenew</label>
                        </div>
                        <div>
                            <label for="gx-nutrient-<?php echo $count ?>"><input type="checkbox" class="gx-program-checkbox" id="gx-nutrient-<?php echo $count ?>" name="gx_programs[]" value="GxNutrient" <?php if (in_array( 'GxNutrient', $config['gx_programs'] )) { echo 'checked'; } ?> autocomplete="off"> GxNutrient</label>
                        </div>
                        <div>
                            <label for="gx-perform-<?php echo $count ?>"><input type="checkbox" class="gx-program-checkbox" id="gx-perform-<?php echo $count ?>" name="gx_programs[]" value="GxPerform" <?php if (in_array( 'GxPerform', $config['gx_programs'] )) { echo 'checked'; } ?> autocomplete="off"> GxPerform</label>
                        </div>
                    </div>
                    <div class="gx-price-table">
                        <ul>
                            <li>Program Pricing <span class="li-right">Total</span></li>
                            <li class="gx-price gx-1">1 program <span class="li-right">$<?php echo $config['pricing'][0] ?></span></li>
                            <li class="gx-price gx-2">2 programs <span class="li-right">$<?php echo $config['pricing'][1] ?></span></li>
                            <li class="gx-price gx-3">3 programs <span class="li-right">$<?php echo $config['pricing'][2] ?></span></li>
                            <li class="gx-price gx-4">4 programs <span class="li-right">$<?php echo $config['pricing'][3] ?></span></li>
                        </ul>
                    </div>
                </div>
            </fieldset>
            <div class="gx-buttons">
                <input type="hidden" name="action_type" id="action_type" value="<?php echo $config['action'] ?>">
                <input type="hidden" name="unique_key" id="unique_key" value="<?php echo $config['unique_key'] ?>">
                <button type="submit" name="<?php echo ($config['action'] == 'add') ? 'add-to-cart' : 'update-bundle'; ?>" data-product-id="<?php echo $gxProduct->id ?>" value="<?php echo $gxProduct->id ?>" class="single_add_to_cart_button button alt <?php echo ($config['action'] == 'add') ? 'btn--disabled' : ''; ?>"><?php echo ($config['action'] == 'update') ? 'Update' : 'Add to Cart'; ?></button>
            </div>
        </form>
    </div>
<?php
}
?>