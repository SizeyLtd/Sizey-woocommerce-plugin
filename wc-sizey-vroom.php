<?php
/**
 * Plugin Name: Sizey 
 * Plugin URI: https://www.sizey.ai/
 * Description: Sizey Vroom woocommerce plugin
 * Version: 0.2.2
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
	define( 'VROOM_VERSION', '0.2.2' ); // Version of plugin
}

if ( !defined( 'VROOM_PLUGIN_PATH' ) ) {
	define( 'VROOM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) ); // Plugin Path
}
register_activation_hook(__FILE__, 'setup_vroom_sizey');
add_action( 'plugins_loaded', 'wc_sizey_vroom_init', 1 );
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'WC_vroom_plugin_action_links');
add_action('admin_menu', 'vroom_configuration_page_registration', 99);
add_action(get_option('vroom-sizey-button-position'), 'add_sizey_recommendation_button');

if (file_exists(plugin_dir_path(__FILE__) . 'inc/sizey-vroom-api.php')) {
	require_once plugin_dir_path(__FILE__) . 'inc/sizey-vroom-api.php';
}
$garment_data_from_api = get_sizey_vroom_garment_data();
if (file_exists(plugin_dir_path(__FILE__) . 'inc/include-vroom-backend-file.php')) {
	require_once plugin_dir_path(__FILE__) . 'inc/include-vroom-backend-file.php';
}
if (file_exists(plugin_dir_path(__FILE__) . 'inc/vroom-admin-product-page-custom-column.php')) {
	require_once plugin_dir_path(__FILE__) . 'inc/vroom-admin-product-page-custom-column.php';
}
if (file_exists(VROOM_PLUGIN_PATH . '/inc/front/' . VROOM_PREFIX . '.front.php')) {
	require_once( VROOM_PLUGIN_PATH . '/inc/front/' . VROOM_PREFIX . '.front.php' );
}

$sizeychdata = get_vroom_sizey_chart_data();
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

/**
 * Create Action link in the sizey plugin page.
 *
 * @return null
 *
*/
function printr( $data) {
	echo '<pre>';
	print_r($data);
	echo '</pre>';
}

/**
 * Add some default options required for sizey.
 *
 * @return  boolean
 * @since    1.0.0
 */
function setup_vroom_sizey() {
	$attributes = wc_get_attribute_taxonomies();
	$allAttr = array();
	foreach ($attributes as $attr) {
		$allAttr[] =$attr->attribute_name;
	}
	update_option('vroom-sizey-button-type', 'button');
	update_option('vroom-sizey-button', 'Find my size now!');
	update_option('vroom-sizey-unavailable-message', 'Your perfect fit size of this product is not available. Try another size or product.');
	update_option('vroom-sizey-button-position', 'woocommerce_after_add_to_cart_button');
	if (count($allAttr)==1) {
		update_option('vroom-global-size-attributes', $allAttr[0]);
		return true;
	}
	if (in_array('size', $allAttr)) {
		update_option('vroom-global-size-attributes', 'size');
		return true;
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
		'Vroom',
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
			// $variations = $product_s->get_available_variations();
			$attributes = $product_s->get_variation_attributes();

			// var_dump($product_s);
			// var_dump($variations);
			// echo '<pre>' . var_export($product_s->get_variation_attributes(), true) . '</pre>';				

			// $garment_id = get_post_meta( $products_IDs->post->ID, 'sizey-garment-id', true );			

			$url = "https://vroom-api.sizey.ai/integration/woocommerce/product/" . $products_IDs->post->ID; //. "?" . $params;


			$payload = json_encode( array( "meta" => $versions, "post" => $products_IDs->post, "attributes" => array_map("array_values",$attributes) ) );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );

			curl_setopt($ch, CURLOPT_URL, $url);
			$result = curl_exec($ch);


			// foreach ( $variations as $variation ) {

			// 	// var_dump($variation);
			// 	echo '<pre>' . var_export($variation, true) . '</pre>';				
			// 	// $params = "";
			// 	// foreach( array_keys($variation['attributes']) as $key) {
			// 	// 	$params .=  preg_replace('/^attribute_pa_/', '', $key) . "=" . $variation['attributes'][$key] . "&";
			// 	// }

			// 	$url = "http://localhost:9001/integration/woocommerce/product/" . $products_IDs->post->ID; //. "?" . $params;


			// 	$payload = json_encode( array( "meta" => $versions, "post" => $products_IDs->post, "variation" => $variation ) );
			// 	curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );

			// 	curl_setopt($ch, CURLOPT_URL, $url);
			// 	$result = curl_exec($ch);
			// 	curl_close($ch);
			
			// 	// var_dump(array_keys($variation['attributes']));
			// 	// var_dump(array_reduce($variation["attributes"], 'variations2params'));
			// }			
			// echo '<pre>'; var_dump($variations); echo '</pre>';
			// Your code
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


function getVroomGlobalSizeyConfiguration() {
	global $sizeychdata;
	$sizesList = array();
	foreach ($sizeychdata as $sizeys) {
		$sizeslisting=array();
		foreach ($sizeys['sizes'][0]['sizes'] as $individual_sizey_size) {
			$sizeslisting[] = htmlspecialchars(sanitize_text_field($individual_sizey_size));
		}
		$sizesList = array_unique(array_merge($sizesList, $sizeslisting));
	}
	$sizes_List = array_filter($sizesList, 'strlen');
	return $sizes_List;
}


/**
 * Deregister WooCommerce Scripts
 */
add_action( 'wp_print_scripts', 'vroom_deregister_javascript', 100 );
function vroom_deregister_javascript() {
	wp_deregister_script( 'prettyPhoto' );
	wp_deregister_script( 'prettyPhoto-init' );
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

	$sizey_recommendation_add_to_cart_button = get_option('vroom-sizey-recommendation-button-add-to-cart');
	$cart_id = WC()->session->get('new_cart');
	$earlier_session_data = WC()->session->get(WC()->session->get('new_cart'));
	$variable_to_set_session = array();
	$variable_to_set_session['unique_id'] = $unique_id;
	$variable_to_set_session['product_' . $post_id]['sizey_recommendation'] = $sizey_recommendation;
	$variable_to_set_session['product_' . $post_id]['available_sizes'] = $sizey_recommendation;
	$recommendedsizes =  strtolower(htmlspecialchars(sanitize_text_field($sizey_recommendation['size'])));
	foreach($sizey_recommendation['sizes'] as $recommendedsize) {

		$product = wc_get_product($post_id);
		$product_variations = $product->get_available_variations();
		foreach ($product_variations as $product_variation) {
			if(strtolower($recommendedsize['size']) == strtolower($product_variation['attributes']['attribute_pa_size'])) {
				$built_query = array();
				$built_query['add-to-cart'] = $post_id;
				$built_query['attribute_pa_size'] = strtolower($recommendedsize['size']);
				$built_query['variation_id'] = $product_variation['variation_id'];
	
				$addToCartUrl = $product->get_permalink() . '?' . http_build_query($built_query);
	
				$data_to_return = '<a href="' . esc_url($addToCartUrl) . '" class="button" id="recommendation-url">' . esc_html($sizey_recommendation_add_to_cart_button) . '</a>';
				$variable_to_set_session['product_' . $post_id]['add_to_cart_url'] = $addToCartUrl;
				$variable_to_set_session['product_' . $post_id]['add_to_cart_url_with_anchor'] = $data_to_return;
				$variable_to_set_session['product_' . $post_id]['sizey_size_unavailable_message'] = $sizey_size_unavailable_message;
				$jsontoreturn['status'] = 'success';
				$jsontoreturn['url'] = $addToCartUrl;
				$jsontoreturn['class'] = 'button';
				$jsontoreturn['id'] = 'recommendation-url';
				$jsontoreturn['content'] = $sizey_recommendation_add_to_cart_button;
				echo esc_html(json_encode($jsontoreturn));
				exit();			
			}
		}
	}
	$earlier_session_data = WC()->session->get($cart_id);
	$new_session_data = array_merge($earlier_session_data, $variable_to_set_session);
	WC()->session->set($cart_id, $new_session_data);
	exit();
	}
}
add_action( 'wp_ajax_nopriv_generate_vroom_recommendation_add_to_cart_button', 'generate_vroom_recommendation_add_to_cart_button' );
add_action( 'wp_ajax_generate_vroom_recommendation_add_to_cart_button', 'generate_vroom_recommendation_add_to_cart_button' );



function get_sizey_specific_data( $sizey_id = null) {
	global $sizeychdata;
	$individual_sizey_data = array();

	foreach ($sizeychdata as $individual_sizey_ch_data) {
		$individual_sizey_data[$individual_sizey_ch_data['id']]['brand'] = $individual_sizey_ch_data['brand'];
		$individual_sizey_data[$individual_sizey_ch_data['id']]['gender'] = $individual_sizey_ch_data['gender'];
		$individual_sizey_data[$individual_sizey_ch_data['id']]['garment'] = $individual_sizey_ch_data['garment'];
		$individual_sizey_data[$individual_sizey_ch_data['id']]['extra'] = $individual_sizey_ch_data['extra']?$individual_sizey_ch_data['extra']:null;
		$individual_sizey_data[$individual_sizey_ch_data['id']]['sizeType'] = null;
	}
	if (is_null($sizey_id)) {
		return json_encode($individual_sizey_data);
	}
	return json_encode($individual_sizey_data[$sizey_id]);

}
