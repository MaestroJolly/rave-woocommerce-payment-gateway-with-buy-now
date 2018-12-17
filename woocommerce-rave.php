<?php

/*
Plugin Name: WooCommerce Rave Payment Gateway With Buy Now Option
Plugin URI: https://rave.flutterwave.com/
Description: WooCommerce payment gateway for Rave with buy now option for instant purchase of digital products.
Version: 1.0.1
Author: Flutterwave Developers, Bosun Olanrewaju & Jolaoso Yusuf
Author URI: http://twitter.com/theflutterwave
  License: MIT License
*/


if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

define( 'FLW_WC_PLUGIN_FILE', __FILE__ );
define( 'FLW_WC_DIR_PATH', plugin_dir_path( FLW_WC_PLUGIN_FILE ) );

add_action('plugins_loaded', 'flw_woocommerce_rave_init', 0);

function flw_woocommerce_rave_init() {

  if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

  require_once( FLW_WC_DIR_PATH . 'includes/class.flw_wc_payment_gateway.php' );
  require_once( FLW_WC_DIR_PATH . 'includes/pay_now.php' );

  add_filter('woocommerce_payment_gateways', 'flw_woocommerce_add_rave_gateway' );

  wp_register_style('fancybox_css', 'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.2/jquery.fancybox.min.css');
  wp_enqueue_style('fancybox_css');

  wp_register_style('flw_pay_now_css', plugins_url( 'assets/css/flw_pay_now.css',__FILE__ ));
  wp_enqueue_style('flw_pay_now_css');

  $load = new Flw_Pay();

  
  /**
   * Add the Settings link to the plugin
   *
   * @param  Array $links Existing links on the plugin page
   *
   * @return Array          Existing links with our settings link added
   */
  function flw_plugin_action_links( $links ) {

    $rave_settings_url = esc_url( get_admin_url( null, 'admin.php?page=wc-settings&tab=checkout&section=rave' ) );
    array_unshift( $links, "<a title='Rave Settings Page' href='$rave_settings_url'>Settings</a>" );

    return $links;

  }

  add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'flw_plugin_action_links' );

  /**
   * Add the Gateway to WooCommerce
   *
   * @param  Array $methods Existing gateways in WooCommerce
   *
   * @return Array          Gateway list with our gateway added
   */
  function flw_woocommerce_add_rave_gateway($methods) {

    $methods[] = 'FLW_WC_Payment_Gateway';
    return $methods;

  }
}

?>