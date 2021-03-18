<?php
/**
 *
 * @link              https://ducksoupdigital.co.uk/
 * @since             1.0.0
 * @package           hubspot_contact_sync_woocommerce_order_quote
 *
 * Plugin Name:       Hubspot Contact Sync - Woocommerce Order Quote
 * Plugin URI:        https://ducksoupdigital.co.uk/
 * Description:       WooCommerce Sync Contacts and Order or Quote info into Hubspot account using v2 api of hubspot
 * Version:           3.0.0
 * Author:            DuckSoup Digital 
 * Author URI:        https://ducksoupdigital.co.uk/
 * Text Domain:       hubspot_contact_sync_woocommerce_order_quote
 * Domain Path:       /languages
 * Requires at least:   4.3
 * Tested up to:        5.6.1
 *
 * @author          https://ducksoupdigital.co.uk
 * @copyright       All rights reserved Copyright (c) 2021, https://ducksoupdigital.co.uk
 *
 */

if ( ! defined( 'ABSPATH') ) {
	return;
}


add_action( 'ywraq_after_create_order', 'hubspot_sync_order_contacts', 10, 1 );
add_action( 'woocommerce_thankyou', 'hubspot_sync_order_contacts', 10, 1 );
function hubspot_sync_order_contacts( $order_id ) {
    if ( ! $order_id ) return;

    $order = wc_get_order( $order_id );
    $order_status = $order->get_status();

    $page_url = site_url('/');
    $page_name = "Home page";

    $order_data = $order->get_data();
    
    if ( $order->has_status('processing') ) {
	    $page_url = site_url('my-account/view-order/'.$order_id);
	    $page_name = 'WooCommerce Order Detail Page';

	    $order_notes = $order_data['billing']['wcj_checkout_field_1'];
	    $order_vat = $order_data['billing']['vat'];
	}

	if ( $order->has_status('ywraq-new') ) {
	    $page_url = site_url('my-account/view-quote/'.$order_id);
	    $page_name = 'WooCommerce Quote Detail Page';
	    
	    $order_notes = get_post_meta( $order_id, 'message', true );
		$order_vat = get_post_meta( $order_id, 'VAT_Number', true );
	}

	 // The Order data

	$order_billing_first_name = $order_data['billing']['first_name'];
  $order_billing_last_name = $order_data['billing']['last_name'];
  $order_billing_company = $order_data['billing']['company'];
  $order_billing_address_1 = $order_data['billing']['address_1'];
  $order_billing_address_2 = $order_data['billing']['address_2'];
  $order_billing_city = isset($order->get_billing_city())?$order->get_billing_city():$order_data['billing']['city'];
  $order_billing_state = $order_data['billing']['state'];
  $order_billing_postcode = $order_data['billing']['postcode'];
  $order_billing_country = isset($order->get_billing_country())?$order->get_billing_country():$order_data['billing']['country'];
  $order_billing_email = $order_data['billing']['email'];
  $order_billing_phone = $order_data['billing']['phone'];
	
	$order_total = $order_data['total'];
	$order_detail = '';

	$count = 1;
	foreach ($order->get_items() as $item_key => $item ):
		$product      = $item->get_product(); 

	    $product_id   = $item->get_product_id();
	    $variation_id = $item->get_variation_id();

	    $item_type    = $item->get_type();

	    $item_name    = $item->get_name();
	    $quantity     = $item->get_quantity(); 
	    $product_price  = $product->get_price();

	    $order_detail .= $count.') '. $item_name .' (#'.$product_id.')'. PHP_EOL;
	    $count++;
	endforeach;



	/* $hs_context stores analytics data about your visitor, including 
    their HubSpot cookie, their IP address, and the page the form was on.
    If you have a custom registration page, you may want to replace the 
    pageUrl and pageName arguments. */
    $hs_context = array(
      'hutk' => $_COOKIE['hubspotutk'],
      'ipAddress' => $_SERVER['REMOTE_ADDR'],
      'pageUrl' => $page_url,
      'pageName' => $page_name
    );

    /* The key on the left here will contain the HubSpot property name, 
    while the part on the right will pull the meta field from their 
    WordPress profile.
    If you have more fields, this is where you'd add them. */
    $fields = array(
      'firstname' => $order_billing_first_name,
      'lastname' => $order_billing_last_name,
      'email' => $order_billing_email,
      'phone' => $order_billing_phone,
      'company' => $order_billing_company,
      'city' => $order_billing_city,
      'address' => $order_billing_address_1.' '.$order_billing_address_2,
      'state' => $order_billing_state,
      'country' => $order_billing_country,
      'vat' => $order_vat,
      'order_total' => $order_total,
      'order_detail' => $order_detail,
      'operating_company_pel' => 'true',
      'hs_context' => json_encode($hs_context)
    );

    /* This is the URL we'll be submitting to. Replace "YourPortalID" 
    and "YourFormGUID" with the appropriate values from the previous 
    step. */
    $portalId = '7760466';
    $formGuid = '0196b388-43de-4695-9fd8-e4ccfab63d55';
    
    $endpoint = "https://forms.hubspot.com/uploads/form/v2/$portalId/$formGuid";


    $data = wp_remote_post($endpoint, array(
      'headers' => array(
        'Content-Type' => 'application/x-www-form-urlencoded'
      ),
      'body' => $fields
    ));
}
