<?php
/*
 *  Plugin Name: WooCommerce Hepsipay Gateway
 *  Plugin URI: https://www.hepsipay.com
 *  Description: Integrate Hepsipay payment service with WooCommerce checkout
 *  Text Domain: hepsipay
 *  Domain Path: /i18n/languages/
 *  Version: 1.0.0
 *  Author: Hepsipay
 *  Author URI: https://www.hepsipay.com
 * */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


function hp_load_textdomain() {
    load_plugin_textdomain( 'hepsipay', false, dirname( plugin_basename(__FILE__) ) . '/i18n/languages/' );
}

function woo_gateway_hepsipay_init() {
    if(!defined('WOOCOMMERCE_VERSION')) {
        throw new \Exception('The WooCommerce is not activated.');
    }

    require_once dirname(__FILE__).'/src/WC_Gateway_Hepsipay.php';
    $instance = new WC_Gateway_Hepsipay(true);
    $instance->initApiService();
}

function woo_gateway_hepsipay_add_class( $methods ) {
	$methods[] = 'WC_Gateway_Hepsipay';
    if (!class_exists('WC_Gateway_Hepsipay')) {
		die("not class_exists('WC_Gateway_Hepsipay')");
        return [];
    }
	return $methods;
}

function woo_gateway_hepsipay_activate() {
	global $user_ID;
    $new_post = array(
		'post_title' => 'Hepsipay Payment Result',
		'post_content' => '[hepsipay_payment_result]',
		'post_status' => 'publish',
		'post_date' => date('Y-m-d H:i:s'),
		'post_author' => $user_ID,
		'post_type' => 'page',
		'post_category' => array(0)
	);
	$post_id = wp_insert_post($new_post);
	update_option('woo_hepsipay_payment_result_page_id', $post_id);
}

function woo_gateway_hepsipay_deactivate() {
	$pid = get_option('woo_hepsipay_payment_result_page_id', null);
	if($pid) {
		wp_trash_post( $pid );
	}
}

function woo_gateway_hepsipay_payment_result_shortcode( $atts ) {
	$html[] =  "woo_gateway_hepsipay_payment_result_shortcode";
    $html[] = "<pre>";
    $html[] = print_r($_GET, 1);
    $html[] = "</pre>";
    return implode('', $html);
}


add_action('init', 'woo_gateway_hepsipay_init', 0);
add_filter( 'woocommerce_payment_gateways', 'woo_gateway_hepsipay_add_class' );
register_activation_hook( __FILE__, 'woo_gateway_hepsipay_activate' );
register_deactivation_hook( __FILE__, 'woo_gateway_hepsipay_deactivate' );
add_shortcode( 'hepsipay_payment_result', 'woo_gateway_hepsipay_payment_result_shortcode' );
add_action('plugins_loaded', 'hp_load_textdomain');
