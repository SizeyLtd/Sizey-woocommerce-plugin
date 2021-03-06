<?php
/**
 * Plugin Name: Sizey 
 * Plugin URI: https://www.sizey.ai/
 * Description: Sizey Vroom woocommerce plugin
 * Version: 1.2.5
 * Author: Sizey Ltd.
 * Author URI: https://www.sizey.ai/
 */
if (!defined('ABSPATH')) :
	exit; // Exit if accessed directly
endif;
define('VROOM_PLUGIN_SLUG', 'sizey-vroom');
if ( !defined( 'VROOM_PREFIX' ) ) {
	define( 'VROOM_PREFIX', 'vroom' ); // Plugin prefix
}
if ( !defined( 'VROOM_PLUGIN_URL' ) ) {
	define( 'VROOM_PLUGIN_URL', plugin_dir_url( __FILE__ ) ); // Plugin url
}
if ( !defined( 'VROOM_VERSION' ) ) {
	define( 'VROOM_VERSION', '1.2.5' ); // Version of plugin
}

if ( !defined( 'VROOM_PLUGIN_PATH' ) ) {
	define( 'VROOM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) ); // Plugin Path
}
register_activation_hook(__FILE__, 'setup_vroom_sizey');
add_action( 'plugins_loaded', 'wc_sizey_vroom_init', 1 );
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'WC_vroom_plugin_action_links');
add_action('admin_menu', 'vroom_configuration_page_registration', 99);
add_action(get_option('vroom-sizey-button-position'), 'add_sizey_recommendation_button');

if (file_exists(plugin_dir_path(__FILE__) . 'inc/include-vroom-backend-file.php')) {
	require_once plugin_dir_path(__FILE__) . 'inc/include-vroom-backend-file.php';
}
if (file_exists(VROOM_PLUGIN_PATH . '/inc/front/' . VROOM_PREFIX . '.front.php')) {
	require_once( VROOM_PLUGIN_PATH . '/inc/front/' . VROOM_PREFIX . '.front.php' );
}

/**
 * Check and validate that WooCommerce plugin is active
 */
function wc_sizey_vroom_init() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wc_sizey_vroom_woocommerce_deactivated' );
		return;
	}

}
/**
 * WooCommerce Deactivated Notice.
 */
function wc_sizey_vroom_woocommerce_deactivated() {

	echo '<div class="error"><p>' . esc_html( 'Sizey vroom requires WooCommerce to be installed and active.' ) . '</p></div>';
}

function optionExists($option_name) {
    global $wpdb;
    $row = $wpdb->get_row($wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option_name));
    if (is_object($row)) {
        return true;
    }
    return false;
}

/**
 * Add some default options required for sizey.
 *
 * @return  boolean
 * @since    1.0.0
 */
function setup_vroom_sizey() {
	if ( ! optionExists( 'vroom-sizey-button-type' ) ) {
		add_option('vroom-sizey-button-type', 'button');
	}
	if ( ! optionExists( 'vroom-sizey-button' ) ) {
		add_option('vroom-sizey-button', 'Find my size now!');
	}
	if ( ! optionExists( 'vroom-sizey-unavailable-message' ) ) {
		add_option('vroom-sizey-unavailable-message', 'Your perfect fit size of this product is not available. Try another size or product.');
	}
	if ( ! optionExists( 'vroom-sizey-button-position' ) ) {
		add_option('vroom-sizey-button-position', 'woocommerce_after_add_to_cart_button');
	}
	return true;
}


function WC_vroom_plugin_action_links( $links) {
	$links[] = '<a href="' . menu_page_url(VROOM_PLUGIN_SLUG, false) . 'admin.php?page=vroom-config">Sizey</a>';
	return $links;
}


function vroom_configuration_page_registration() {
	add_submenu_page(
		'woocommerce',
		'Sizey Vroom Configuration',
		'Sizey',
		'manage_options',
		'vroom-config',
		'vroom_config_callback'
	);
}

function vroom_config_callback() {
	update_vroom_config();
	generate_vroom_config_form();
}

/**
 * Update Vroom configuration data.
 *
 * @return null
 */
function update_vroom_config() {

	//Second Tab content Global Size Attribute updation
	if ( isset( $_POST['vroom-sizey-button-sync'] ) ) {
		$products_IDs = new WP_Query( array(
			'post_type' => 'product',
			'posts_per_page' => -1,
		));

		$sizey_api_key = get_option('vroom-sizey-api-key');
		$versions = array("WordPress" => WC_VERSION, "WooSizey" => 1 );
	
		while ($products_IDs->have_posts() ) : $products_IDs->the_post();
	
			$ch = curl_init();
			// curl_setopt($ch, CURLOPT_HTTPGET, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'x-sizey-key: ' . $sizey_api_key,
				'Content-Type:application/json'
			));

			$product_s = wc_get_product( $products_IDs->post->ID ); 
			if($product_s->is_type( 'simple' )) {
				continue;
			}
			$attributes = $product_s->get_variation_attributes();

			$url = "https://vroom-api.sizey.ai/integration/woocommerce/product/" . $products_IDs->post->ID; //. "?" . $params;


			$payload = json_encode( array( "meta" => $versions, "post" => $products_IDs->post, "attributes" => array_map("array_values",$attributes) ) );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );

			curl_setopt($ch, CURLOPT_URL, $url);
			$result = curl_exec($ch);
		endwhile;		
		curl_close($ch);
	}

	//First Tab Content VRoom configuration
	if ( isset($_POST['vroom-sizey-button-configuration'])) {
		if (
			! isset( $_POST['vroom-config-nonce-field'] )
			|| ! wp_verify_nonce( sanitize_text_field($_POST['vroom-config-nonce-field']), 'sizey-vroom-config-action' )
		) {

			return false;
		}

		if (trim(filter_input(INPUT_POST, 'vroom-sizey-api-key', FILTER_SANITIZE_STRING))!=='') {
			update_option(
				'vroom-sizey-api-key',
				filter_input(INPUT_POST, 'vroom-sizey-api-key', FILTER_SANITIZE_STRING)
			);
			echo '<div class="notice notice-success is-dismissible"> <p>Success! API have been saved.</p> </div>';
		} else {
			echo '<div class="notice notice-warning is-dismissible"> <p>Error! API update error.</p> </div>';
		}

		update_option(
			'vroom-sizey-button-position',
			filter_input(INPUT_POST, 'vroom-sizey-button-position', FILTER_SANITIZE_STRING)
		);

		if (trim(filter_input(INPUT_POST, 'vroom-sizey-button', FILTER_SANITIZE_STRING))!='') {
			update_option(
				'vroom-sizey-button',
				filter_input(INPUT_POST, 'vroom-sizey-button', FILTER_SANITIZE_STRING)
			);
			echo '<div class="notice notice-success is-dismissible"> <p>Success! Button data has updated</p> </div>';
		} else {
			echo '<div class="notice notice-warning is-dismissible"> <p>Error! Button name error.</p> </div>';
		}

		update_option(
			'vroom-sizey-recommendation-button-add-to-cart',
			filter_input(INPUT_POST, 'vroom-sizey-recommendation-button-add-to-cart', FILTER_SANITIZE_STRING)
		);

		update_option(
			'vroom-sizey-button-type',
			filter_input(INPUT_POST, 'vroom-sizey-button-type', FILTER_SANITIZE_STRING)
		);

		update_option(
			'vroom-sizey-unavailable-message',
			filter_input(INPUT_POST, 'vroom-sizey-unavailable-message', FILTER_SANITIZE_STRING)
		);

		update_option(
			'vroom-sizey-css',
			filter_input(INPUT_POST, 'vroom-sizey-css', FILTER_SANITIZE_STRING)
		);


	}
	return null;
}



function generate_vroom_config_form() {
	echo '<div class="data-table">
<ul id="vroom-sizey-tabs">
    <li><a href="#" name="vroomsizeytab1" >Sizey setting</a></li>
	<li><a href="#" name="tab2" >Sync to Portal</a></li>
</ul>
<div id="vroom-sizey-content">
	<div id="tab2">';

	if (file_exists(VROOM_PLUGIN_PATH . 'inc/admin/sizey-vroom-configuration.php')) {
		require_once VROOM_PLUGIN_PATH . 'inc/admin/sizey-vroom-sync.php';
	}
	echo ' </div>';

    echo '<div id="vroomsizeytab1">';

	if (file_exists(VROOM_PLUGIN_PATH . 'inc/admin/sizey-vroom-configuration.php')) {
		require_once VROOM_PLUGIN_PATH . 'inc/admin/sizey-vroom-configuration.php';
	}
	echo ' </div>';
	echo '</div></div>';
	if (file_exists(VROOM_PLUGIN_PATH . 'inc/sizey-vroom-instruction.php')) {
		require_once VROOM_PLUGIN_PATH . 'inc/sizey-vroom-instruction.php';
	}

	return null;
}


function add_sizey_recommendation_button() {
require_once(VROOM_PLUGIN_PATH . '/inc/front/' . VROOM_PREFIX . '.front.button.product.page.php');
}


function generate_vroom_recommendation_add_to_cart_button() {
	check_ajax_referer( 'recommendation_add_to_cart_button', 'nonce_data' );
	$jsontoreturn = array();
	if (isset($_POST['post_id']) && isset($_POST['unique_id'])) {
		$post_id = sanitize_text_field($_POST['post_id']);
		if ( isset($_POST['unique_id'])) {
			$unique_id = sanitize_text_field($_POST['unique_id']);
		}
		$sizey_size_unavailable_message = get_option('sizey-unavailable-message');
		if (isset($_POST['sizey_recommendation'])) {
			$sizey_recommendation = $_POST['sizey_recommendation'];//json_decode(stripslashes(sanitize_text_field($_POST['sizey_recommendation'])), true);
		}

	$product = wc_get_product($post_id);
	$product_variations = $product->get_available_variations();
	$recommendedSizes =  array_map(function($size) { return strtolower($size["size"]); }, $sizey_recommendation['sizes']);
	$attr = $_POST['attributes'];

	$matching_variations = array_filter($product_variations, function($variation) use ($attr, $recommendedSizes) {
		if(!is_null($attr)) {
			$not_matching_attribute = array_filter($attr, function($v, $k) use ($variation) {
				return strtolower($variation['attributes'][$k]) != strtolower($v);		
			}, 1);
			if(count($not_matching_attribute) > 0) {
				return false;
			}
		}

		$matching_recommended_size = array_filter($recommendedSizes, function($v) use ($variation) {
			return strtolower($variation['attributes']['attribute_pa_size']) == $v;		
		}, 0);
		return count($matching_recommended_size) > 0;
	});	

	if(count($matching_variations) == 0) {
		wp_send_json_success(array());
	}		

	$selected_variation = reset($matching_variations);


	$built_query = array();
	$built_query['add-to-cart'] = $post_id;
	$built_query['variation_id'] = $selected_variation['variation_id'];
	$built_query['attribute_pa_size'] = strtolower($selected_variation['attributes']['attribute_pa_size']);
	$addToCartUrl = $product->get_permalink() . '?' . http_build_query($built_query);
	$jsontoreturn['status'] = 'success';
	$jsontoreturn['url'] = $addToCartUrl;
	$jsontoreturn['class'] = 'button';
	$jsontoreturn['id'] = 'recommendation-url';
	$jsontoreturn['content'] = get_option('vroom-sizey-recommendation-button-add-to-cart');

	wp_send_json_success( $jsontoreturn );	
	}
}

function ajax_get_variation_id() {
	$recommendedSizes = $_POST['recommendedSizes'];
	$attr = $_POST['attributes'];
	$product_id = sanitize_text_field($_POST['product_id']);

	$product = wc_get_product($product_id);
	$product_variations = $product->get_available_variations();
	$matching_variations = array_filter($product_variations, function($variation) use ($attr, $recommendedSizes) {
		$not_matching_attribute = array_filter($attr, function($v, $k) use ($variation) {
			return strtolower($variation['attributes'][$k]) != strtolower($v);		
		}, 1);

		$matching_recommended_size = array_filter($recommendedSizes, function($v) use ($variation) {
			return strtolower($variation['attributes']['attribute_pa_size']) == strtolower($v);		
		}, 0);
		return count($not_matching_attribute) == 0 && count($matching_recommended_size) > 0;
	});	

	if(count($matching_variations) == 0) {
		wp_send_json_success(array());
	}

	$selected_variation = reset($matching_variations);
	wp_send_json_success( array(
		"variation_id" => $selected_variation["variation_id"],
		"size" => $selected_variation["attributes"]["attribute_pa_size"],
	) );
}

add_action( 'wp_ajax_nopriv_generate_vroom_recommendation_add_to_cart_button', 'generate_vroom_recommendation_add_to_cart_button' );
add_action( 'wp_ajax_generate_vroom_recommendation_add_to_cart_button', 'generate_vroom_recommendation_add_to_cart_button' );
add_action( 'wp_ajax_get_variation_id', 'ajax_get_variation_id' );
add_action( 'wp_ajax_nopriv_get_variation_id', 'ajax_get_variation_id' );
add_action("woocommerce_thankyou", "after_confirmation_hook", 111, 1);
  
    
function after_confirmation_hook($order_id)
{
	$order = wc_get_order($order_id);
	$items = $order->get_items();
	$qty=0;
	foreach ($items as $item_id => $item_data)
	{
		$qty +=  $item_data->get_quantity();
	}
	$unique_id = $_COOKIE["unique-id"];
	$sizey_api_key = get_option('vroom-sizey-api-key');
	$endpointURL = "https://analytics-api-dot-sizey-ai.appspot.com/checkout";
	$postdata =array("sessionId"=>$unique_id, "numOfProducts"=>$qty);
	$postdata = json_encode($postdata);
	$headers = array(
		'Content-Type: application/json',
		'x-sizey-key: '.$sizey_api_key
	);

	$ch = curl_init($endpointURL);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$result = curl_exec($ch);
	curl_close($ch);

}