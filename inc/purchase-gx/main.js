/**
 * Attach event listeners to DOM
 */
jQuery(document).ready(function($) {
  /**
   * The environment we are on is set and passed to this script in functions.php. This switch is used
   * to determine the product ids we want to use.
   * Options are: "production", "staging", "development"
   */
  const gd_env = php_vars.env;
  const gx_product_ids = php_vars.product_ids;

  // Determine if we are a regular product or 23andMe product
  const product_id = document.getElementById('gx-bundles').dataset.productId;
  const product_name = document.getElementById('gx-bundles').dataset.productName;
  let pricing = [];
  if (product_id == 5121) {
    pricing = [299, 449, 549, 649];
  } else if (product_id == 5159) {
    pricing = [159, 239, 279, 319];
  } else if (product_name == 'lyfecode') {
    pricing = [250, 350, 425, 500];
  }

  // Retrieve all of our current bundles.
  const bundles = document.getElementsByClassName('gx-bundle');

  // Attach event listeners to buttons in each bundle
  for (let i = 0; i < bundles.length; i++) {
    // Modify button
    if (document.getElementsByClassName('modify-bundle').length > 0) {
      bundles[i]
        .querySelector('.modify-bundle')
        .addEventListener('click', function() {
          jQuery(bundles[i]).toggleClass('expanded');
        });
    }

    attachCheckboxes(bundles[i], i + 1);
    updateProducts(bundles[i], i + 1, gd_env);

    // Highlight pricing for current programs selected
    // Refactor this into function
    const current_checkboxes = bundles[i].getElementsByClassName(
      'gx-program-checkbox'
    );
    let programs_selected = 0;

    for (let j = 0; j < current_checkboxes.length; j++) {
      if (current_checkboxes[j].checked) {
        programs_selected++;
      }
    }

    const pricing_table = bundles[i].querySelector('.gx-price-table');
    const program_prices = pricing_table
      .querySelector('ul')
      .getElementsByClassName('gx-price');
    programs_selected -= 1;

    for (let k = 0; k < program_prices.length; k++) {
      if (programs_selected == k) {
        addClass(program_prices[k], 'active');
      }
    }
  }

  jQuery('.add-bundle-btn').on('click', function() {
    // Retrieve all of our current bundles.
    const bundles = document.getElementsByClassName('gx-bundle');
    const bundle_count = bundles.length + 1;

    const html_gxBundle = jQuery('<div />', {
      id: `gxbundle-${bundle_count}`,
      class: 'gx-bundle',
    });
    const html_gxBundle_header = jQuery('<header />', {
      class: 'gx-bundle__header',
    });
    const html_gxBundle_form = jQuery('<form />', {
      id: `js-gx-bundle-${bundle_count}__form`,
      class: 'gx-bundle__form cart',
      method: 'post',
      action: document.location,
      enctype: 'multipart/form-data',
    });

    html_gxBundle.appendTo(jQuery('#gx-bundles .gx-step__container'));

    const new_bundle = jQuery(`#gxbundle-${bundle_count}`);
    new_bundle.append(html_gxBundle_header, html_gxBundle_form);

    new_bundle
      .children('.gx-bundle__header')
      .html(`<h2>Individual ${bundle_count} Details</h2>`);

    /* Add customer type - regular or 23andMe */

    /* Note: ONLY for LyfeCode at the moment! */

    const html_customer_description =
      '<legend>Are you already a 23andMe customer (Health + Ancestry Service ONLY)?</legend>';
    const html_radio_yes =
      '<label for="yes"><input type="radio" id="yes" name="existing-customer" class="customer-type-radio" value="yes"> Yes</label>';
    const html_radio_no =
      '<label for="no"><input type="radio" id="no" name="existing-customer" class="customer-type-radio" value="no"> No</label>';

    if (product_name === 'lyfecode') {
      new_bundle
        .children('.gx-bundle__form')
        .append(
          '<fieldset id="gx-customer-type" class="gx-bundle__form__fieldset" />'
        );
      new_bundle
        .children('.gx-bundle__form')
        .children('#gx-customer-type')
        .append(html_customer_description, html_radio_yes, html_radio_no);
    }

    /* Add customer details - name, email */

    const html_firstName = `<label for="first_name" autocomplete="off">First Name <abbr class="required" title="required">*</abbr><input type="text" name="first_name" id="first_name_${bundle_count}" required></label>`;
    const html_lastName = `<label for="last_name" autocomplete="off">Last Name <abbr class="required" title="required">*</abbr><input type="text" name="last_name" id="last_name_${bundle_count}" required></label>`;
    const html_email = `<label for="email_address"  autocomplete="off">Email Address <abbr class="required" title="required">*</abbr><input type="email" name="email_address" id="email_address_${bundle_count}" class="bundle-email" required></label>`;

    new_bundle
      .children('.gx-bundle__form')
      .append(
        '<fieldset id="gx-customer-details" class="gx-bundle__form__fieldset"><legend>Please provide the name and email address for this order.</legend></fieldset>'
      );
    new_bundle
      .children('.gx-bundle__form')
      .children('#gx-customer-details')
      .append('<div class="fieldset-flex" />');
    new_bundle
      .children('.gx-bundle__form')
      .children('#gx-customer-details')
      .children('.fieldset-flex')
      .append(html_firstName, html_lastName, html_email);

    /* Add program selections */

    const html_gxSlim = `<div><label for="gx-slim-${bundle_count}"><input type="checkbox" class="gx-program-checkbox" id="gx-slim-${bundle_count}" name="gx_programs[]" value="GxSlim" autocomplete="off"> GxSlim</label></div>`;
    const html_gxRenew = `<div><label for="gx-renew-${bundle_count}"><input type="checkbox" class="gx-program-checkbox" id="gx-renew-${bundle_count}" name="gx_programs[]" value="GxRenew" autocomplete="off"> GxRenew</label></div>`;
    const html_gxNutrient = `<div><label for="gx-nutrient-${bundle_count}"><input type="checkbox" class="gx-program-checkbox" id="gx-nutrient-${bundle_count}" name="gx_programs[]" value="GxNutrient" autocomplete="off"> GxNutrient</label></div>`;
    const html_gxPerform = `<div><label for="gx-perform-${bundle_count}"><input type="checkbox" class="gx-program-checkbox" id="gx-perform-${bundle_count}" name="gx_programs[]" value="GxPerform" autocomplete="off"> GxPerform</label></div>`;

    new_bundle
      .children('.gx-bundle__form')
      .append(
        '<fieldset id="gx-program-options" class="gx-bundle__form__fieldset"><legend>Please select the programs you wish to purchase.</legend></fieldset>'
      );
    new_bundle
      .children('.gx-bundle__form')
      .children('#gx-program-options')
      .append('<div class="fieldset-flex" />');
    new_bundle
      .children('.gx-bundle__form')
      .children('#gx-program-options')
      .children('.fieldset-flex')
      .append('<div class="gx-programs" />');
    new_bundle
      .children('.gx-bundle__form')
      .children('#gx-program-options')
      .children('.fieldset-flex')
      .children('.gx-programs')
      .append(html_gxSlim, html_gxRenew, html_gxNutrient, html_gxPerform);

    /* Add price table */

    const html_priceTable = `<div class="gx-price-table"><ul><li>Program Pricing <span class="li-right">Total</span></li><li class="gx-price gx-1">1 program <span class="li-right">$${
      pricing[0]
    }</span></li><li class="gx-price gx-2">2 programs <span class="li-right">$${
      pricing[1]
    }</span></li><li class="gx-price gx-3">3 programs <span class="li-right">$${
      pricing[2]
    }</span></li><li class="gx-price gx-4">4 programs <span class="li-right">$${
      pricing[3]
    }</span></li></ul></div>`;

    new_bundle
      .children('.gx-bundle__form')
      .children('#gx-program-options')
      .children('.fieldset-flex')
      .append(html_priceTable);

    const html_buttonContainer = '<div class="gx-buttons"></div>';

    new_bundle.children('.gx-bundle__form').append(html_buttonContainer);

    const html_hiddenAction =
      '<input type="hidden" name="action_type" id="action_type" value="add">';
    const html_addToCartBtn = `<button type="submit" name="add-to-cart" value="${product_id}" data-product-id="${product_id}" class="single_add_to_cart_button button alt btn--disabled">Add to Cart</button>`;

    new_bundle
      .children('.gx-bundle__form')
      .children('.gx-buttons')
      .append(html_hiddenAction, html_addToCartBtn);

    const bundle = document.getElementById(`gxbundle-${bundle_count}`);
    attachCheckboxes(bundle, bundle_count);
    updateProducts(bundle, bundle_count, gd_env);
  });
});

/**
 * LyfeCode specific process to handle user input
 * when they choose customer type (i.e. 23andMe or regular)
 * @param {int} bundle_id
 */
function updateProducts(bundle, bundle_id, gd_env) {
  const customer_type = bundle.getElementsByClassName('customer-type-radio');
  let existing_customer = 'no';
  const current_bundle = document.getElementById(`gxbundle-${bundle_id}`);
  const gxProduct = {};

  for (let i = 0; i < customer_type.length; i++) {
    customer_type[i].addEventListener('click', function() {
      existing_customer = this.value;

      if (existing_customer === 'yes') {
        gxProduct.id = 7635;
        gxProduct.pricing = [150, 207, 237, 262];
      } else {
        gxProduct.id = 7633;
        gxProduct.pricing = [250, 350, 425, 500];
      }

      // Update product id once we know our user type
      current_bundle.querySelector('.single_add_to_cart_button').value =
        gxProduct.id;
      current_bundle.querySelector(
        '.single_add_to_cart_button'
      ).dataset.productId = gxProduct.id;

      // Update our pricing accordingly
      const price_table = current_bundle.querySelector('.gx-price-table');
      const price_rows = price_table.getElementsByClassName('gx-price');

      for (let i = 0; i < price_rows.length; i++) {
        price_rows[i].querySelector(
          '.li-right'
        ).innerHTML = `$${gxProduct.pricing[i]}`;
      }
    });
  }

  /*

    */
}

/**
 * Callback function for Gx program selection click functions
 * @param {object} bundle
 * @param {int} bundle_id
 */
function attachCheckboxes(bundle, bundle_id) {
  // Check number of selected programs and highlight corresponding price
  const program_checkboxes = bundle.getElementsByClassName(
    'gx-program-checkbox'
  );

  for (let j = 0; j < program_checkboxes.length; j++) {
    program_checkboxes[j].addEventListener('click', function() {
      const current_checkboxes = this.closest(
        '.gx-programs'
      ).getElementsByClassName('gx-program-checkbox');
      let programs_selected = 0;

      for (let k = 0; k < current_checkboxes.length; k++) {
        if (current_checkboxes[k].checked) {
          programs_selected++;
        }
      }

      const pricing_table = this.closest('form').querySelector(
        '.gx-price-table'
      );
      const program_prices = pricing_table
        .querySelector('ul')
        .getElementsByClassName('gx-price');

      programs_selected -= 1;

      for (let l = 0; l < program_prices.length; l++) {
        if (programs_selected == l) {
          addClass(program_prices[l], 'active');
        } else if (hasClass(program_prices[l], 'active')) {
          removeClass(program_prices[l], 'active');
        }
      }
    });
  }

  // Check that all fields are filled out
  const form_fields = bundle.querySelectorAll('input');
  form_fields.forEach(function(form_field) {
    form_field.addEventListener('change', function() {
      const form_complete = validateFields(bundle_id);
      addEmailErrorMessage(bundle_id, form_complete);
    });
  });

  // Attach validation to form submit buttons
  bundle
    .querySelector('.single_add_to_cart_button')
    .addEventListener('click', function() {
      error_exists = this.closest('.gx-bundle').querySelector('.form-error');

      // Remove disabled class from submit button
      if (hasClass(this, 'btn--disabled')) {
        if (!error_exists) {
          const error_html = document.createElement('div');
          error_html.innerHTML =
            '<p>* Please complete all form fields before submitting.</p>';
          error_html.classList.add('form-error');

          this.closest('.gx-buttons').prepend(error_html);
        }

        event.preventDefault();
      }
    });
}

function emailValidator(bundle_id) {
  let email_addresses = document.querySelectorAll('.bundle-email');
    let email_values = [];
    let clean_email;

  email_addresses.forEach(function(email) {
    clean_email = email.value.trim();
    email_values.push(clean_email);
  });

  const duplicates = email_values.reduce(function(acc, el, i, arr) {
    if (arr.indexOf(el) !== i && acc.indexOf(el) < 0) acc.push(el);
    return acc;
  }, []);

  const bundle_el = document.getElementById(`js-gx-bundle-${bundle_id}__form`);

  if (duplicates.length != 0) {
    return false;
  }
  return true;
}

function validateFields(bundle_id) {
  const bundle_el = document.getElementById(`js-gx-bundle-${bundle_id}__form`);
  const product_name = document.getElementById('gx-bundles').dataset
    .productName;
  let selected_products = 0;
  let radio_complete = false;
  let form_complete = true;

  // Check if this is a LyfeCode product
  const lyfecode_product = product_name === 'lyfecode';

  // Check that all fields are filled out and at least one product is selected.
  [...bundle_el.elements].forEach(element => {
    if (
      (element.getAttribute('type') == 'text' ||
        element.getAttribute('type') == 'email') &&
      element.value == ''
    ) {
      form_complete = false;
    }
    if (element.getAttribute('type') == 'radio' && element.checked == true) {
      radio_complete = true;
    }
    if (element.getAttribute('type') == 'checkbox' && element.checked == true) {
      selected_products++;
    }
  });

  if (selected_products == 0) {
    form_complete = false;
  }
  if (lyfecode_product && !radio_complete) {
    form_complete = false;
  }

  return form_complete;
}

function addEmailErrorMessage(bundle_id, form_complete) {
  const bundle_el = document.getElementById(`js-gx-bundle-${bundle_id}__form`);
  const error_email = document.createElement('div');
  error_email.innerHTML =
    '<p>* A unique email address is required for each order. Please choose a different email address.</p>';
  error_email.classList.add('email-error');
  const error_html = document.createElement('div');
  error_html.innerHTML =
    '<p>* Please complete all form fields before submitting.</p>';
  error_html.classList.add('form-error');

  // Call email validation to verify unique email addresss for more than one order
  const unique_email = bundle_id > 1 ? emailValidator(bundle_id) : true;

  if (form_complete == true && unique_email == true) {
    // Remove email error messages if present
    if (bundle_el.querySelector('.email-error')) {
      bundle_el
        .querySelector('#gx-customer-details')
        .removeChild(bundle_el.querySelector('.email-error'));
    }

    // Remove form error messages if present
    if (bundle_el.querySelector('.form-error')) {
      bundle_el
        .querySelector('.gx-buttons')
        .removeChild(bundle_el.querySelector('.form-error'));
    }

    // Remove disabled class from submit button
    if (
      hasClass(
        bundle_el.querySelector('.single_add_to_cart_button'),
        'btn--disabled'
      )
    ) {
      removeClass(
        bundle_el.querySelector('.single_add_to_cart_button'),
        'btn--disabled'
      );
    }
  } else if (form_complete == true && unique_email == false) {
    // Add email error message if not already displayed
    if (!bundle_el.querySelector('.email-error')) {
      bundle_el.querySelector('#gx-customer-details').appendChild(error_email);
    }

    // Add disabled class to submit button if it isn't already
    if (
      !hasClass(
        bundle_el.querySelector('.single_add_to_cart_button'),
        'btn--disabled'
      )
    ) {
      addClass(
        bundle_el.querySelector('.single_add_to_cart_button'),
        'btn--disabled'
      );
    }
  } else if (form_complete == false && unique_email == true) {
    // Remove email error message if present
    if (bundle_el.querySelector('.email-error')) {
      bundle_el
        .querySelector('#gx-customer-details')
        .removeChild(bundle_el.querySelector('.email-error'));
    }

    // Add disabled class to submit button if it isn't already
    if (
      !hasClass(
        bundle_el.querySelector('.single_add_to_cart_button'),
        'btn--disabled'
      )
    ) {
      addClass(
        bundle_el.querySelector('.single_add_to_cart_button'),
        'btn--disabled'
      );
    }
  } else {
    /* All false */

    // Add disabled class to submit button if it isn't already
    if (
      !hasClass(
        bundle_el.querySelector('.single_add_to_cart_button'),
        'btn--disabled'
      )
    ) {
      addClass(
        bundle_el.querySelector('.single_add_to_cart_button'),
        'btn--disabled'
      );
    }

    // Add email error message if not already displayed
    if (!bundle_el.querySelector('.email-error')) {
      bundle_el.querySelector('#gx-customer-details').appendChild(error_email);
    }
  }
}

/**
 * Helper functions for manipulating classes without jQuery
 * @param {string} el - element to modify
 * @param {string} className - class to be modified
 */
function hasClass(el, className) {
  return el.classList
    ? el.classList.contains(className)
    : new RegExp(`\\b${className}\\b`).test(el.className);
}

function addClass(el, className) {
  if (el.classList) el.classList.add(className);
  else if (!hasClass(el, className)) el.className += ` ${className}`;
}

function removeClass(el, className) {
  if (el.classList) el.classList.remove(className);
  else
    el.className = el.className.replace(
      new RegExp(`\\b${className}\\b`, 'g'),
      ''
    );
}
