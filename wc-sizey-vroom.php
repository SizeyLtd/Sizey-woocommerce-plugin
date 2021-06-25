<?php
/**
 * Plugin Name: Sizey vroom integration
 * Plugin URI: https://www.sizey.ai/
 * Description: Sizey Vroom woocommerce plugin
 * Version: 0.0.3
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
	define( 'VROOM_VERSION', '0.0.1' ); // Version of plugin
}

if ( !defined( 'VROOM_PLUGIN_PATH' ) ) {
	define( 'VROOM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) ); // Plugin Path
}
register_activation_hook(__FILE__, 'setup_vroom_sizey');
add_action( 'plugins_loaded', 'wc_sizey_vroom_init', 1 );
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'WC_vroom_plugin_action_links');
add_action('add_meta_boxes', 'sizey_vroom_add_section_for_select_garment');
add_action('save_post', 'sizey_vroom_save_product_specific_garment', 10, 2);
add_action('admin_menu', 'vroom_configuration_page_registration', 99);
add_action(get_option('sizey-button-position'), 'add_sizey_recommendation_button');

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
	return true;
}


/**
 * Handles saving the meta box.
 *
 * @param int     $post_id Post ID.
 * @return null
*/
function sizey_vroom_save_product_specific_garment( $post_id ) {
	// If this is an autosave, our form has not been submitted,
	// so we don't want to do anything.
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return $post_id;
	}
	// Sanitize user input.
	$post_type_name = filter_input(INPUT_POST, 'post_type', FILTER_SANITIZE_STRING);
	// Check the user's permissions.

	if ('product' === $post_type_name) {
		if (!current_user_can('edit_page', $post_id)) {
			return $post_id;
		}
	} else {
		if (!current_user_can('edit_post', $post_id)) {
			return $post_id;
		}
	}

	// Save the meta when the chart post is saved.
	// Sanitize the user input. sizey_chart_name_nonce
	$size_chart_select_nonce = filter_input(INPUT_POST, 'sizey-garment-id', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	$size_chart_select_name_nonce = filter_input(INPUT_POST, 'sizey-garment-name', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
	// Verify that the nonce is valid.

	if (isset($size_chart_select_nonce) && isset($size_chart_select_name_nonce)) {
		// Save the size chart in product page.
		update_post_meta($post_id, 'sizey-garment-id', $size_chart_select_nonce);
		update_post_meta($post_id, 'sizey-garment-name', $size_chart_select_name_nonce);
		$sizey_model_data  = get_photourl_by_garment_id($size_chart_select_nonce);
		if (0 < count($sizey_model_data)) {
			update_post_meta($post_id, 'sizey-model-data', json_encode($sizey_model_data));
			$counter =0;
			foreach ($sizey_model_data as $model_id => $value) {
				if (0 === $counter) {
					update_post_meta($post_id, 'post_avatar', json_encode($value));

					if (isset($value['avatars'])) {
						update_post_meta($post_id, 'avatars', $value['avatars']);
					}
					if (isset($value['poses'])) {
						update_post_meta($post_id, 'avatars_poses', $value['poses']);
					}
					$modelurl = $value['photouri'];
					$older_attachment_id = get_post_meta($post_id, 'sizey_gallery_image', true);

					$attachment_id =  manage_media_gallery($modelurl, $post_id);
					if ($attachment_id) {
						wp_delete_attachment( $older_attachment_id );
						update_post_meta($post_id, 'sizey_gallery_image', $attachment_id);
					}
				}
				$counter ++;
			}
		}

		return true;
	}
	return true;
}
/*
 *
 * */
function save_image( $inPath, $product_id) {
 //Download images from remote server
	$outpath = VROOM_PLUGIN_PATH . 'assets/images/' . $product_id . '.png';
	$in=    fopen($inPath, 'rb');
	$out=   fopen($outpath, 'wb');
	while ($chunk = fread($in, 8192)) {
		fwrite($out, $chunk, 8192);

	}

	fclose($in);
	fclose($out);

	return $outpath;
}

/**
 * Delete file using file path
 */

function delete_image($image_path) {
	if (is_file ($image_path)) {
		unlink($image_path);
	}
	return true;
}

/*
 * upload image into gallery
 *
 * */
function manage_media_gallery( $file_url, $post_id) {
	$file = save_image($file_url, $post_id);
	if (!$file) {
		return false;
	}

	//$file = VROOM_PLUGIN_PATH . 'assets/images/'.$post_id.'.png';
	$filename = basename($file);
	$upload_file = wp_upload_bits($filename, null, file_get_contents($file));
	if (!$upload_file['error']) {
		$wp_filetype = wp_check_filetype($filename, null );
		$attachment = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_parent' => $post_id,
			'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
			'post_content' => '',
			'post_status' => 'inherit'
		);
		$attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], $post_id );

		if (!is_wp_error($attachment_id)) {
			require_once(ABSPATH . 'wp-admin/includes/image.php');
			$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
			wp_update_attachment_metadata( $attachment_id, $attachment_data );
			delete_image($file);
			return $attachment_id;

		}
	}

	return false;
}

/**
 * Adds the meta box container.
 *
 * @since    1.0.0
 */
function sizey_vroom_add_section_for_select_garment() {
	// Meta box to select chart in product page.
	add_meta_box(
		'vroom-garment',
		__('Select Sizey Vroom Garment', 'create_sizey_vroom_search_dropdown'),
		'sizey_vroom_add_meta_box_callback',
		'product',
		'side',
		'default'
	);
}



/**
 * Randering of the Meta Box
 *
 * @since    1.0.0
 */


function sizey_vroom_add_meta_box_callback() {
	if (file_exists(VROOM_PLUGIN_PATH . 'inc/admin/sizey-vroom-select-garment-form.php')) {
		require_once VROOM_PLUGIN_PATH . 'inc/admin/sizey-vroom-select-garment-form.php';
	}
}

/**
 * Create Action link in the sizey plugin page.
 *
 * @return null
 *
 */

function WC_vroom_plugin_action_links( $links) {
	$links[] = '<a href="' . menu_page_url(VROOM_PLUGIN_SLUG, false) . 'admin.php?page=vroom-config">Vroom Settings</a>';
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

function update_vroom_config() {

}

function generate_vroom_config_form() {
	echo '<div class="data-table">
<ul id="sizey-tabs">
    <li><a href="#" name="sizeytab1" >Sizey setting</a></li>

</ul>

<div id="sizey-content">
    <div id="sizeytab1">';

	if (file_exists(VROOM_PLUGIN_PATH . 'inc/admin/sizey-vroom-configuration.php')) {
		require_once VROOM_PLUGIN_PATH . 'inc/admin/sizey-vroom-configuration.php';
	}
	echo ' </div>';

	echo '</div></div>';


	return null;
}

/**
 * Deregister WooCommerce Scripts
 */
add_action( 'wp_print_scripts', 'vroom_deregister_javascript', 100 );
function vroom_deregister_javascript() {
	wp_deregister_script( 'prettyPhoto' );
	wp_deregister_script( 'prettyPhoto-init' );
}

/**
 * Enqueue script and style for plugin
 */
add_action( 'wp_enqueue_scripts', 'vroom_embed_iframe_scripts', 999 );
function vroom_embed_iframe_scripts() {
	wp_enqueue_script( VROOM_PREFIX . '-custom-photoswipe', VROOM_PLUGIN_URL . 'assets/js/photoswipe.js', array('jquery'), VROOM_VERSION, true );
	//wp_enqueue_style( VROOM_PREFIX . '-style-prefetch', VROOM_PLUGIN_URL . 'assets/css/photoswipe.css', array(), VROOM_VERSION, false );
	//wp_enqueue_style( VROOM_PREFIX . '-style-product-page', VROOM_PLUGIN_URL . 'assets/css/vroom-front.css', array() , VROOM_VERSION, false);
}

function add_sizey_recommendation_button() {
require_once(VROOM_PLUGIN_PATH . '/inc/front/' . VROOM_PREFIX . '.front.button.product.page.php');
}


function generate_vroom_recommendation_add_to_cart_button() {
	check_ajax_referer( 'recommendation_add_to_cart_button', 'nonce_data' );
	$jsontoreturn = array();
	if (isset($_POST['post_id']) && isset($_POST['unique_id'])) {
		global $sizeychdata;
		$post_id = sanitize_text_field($_POST['post_id']);
		if ( isset($_POST['unique_id'])) {
			$unique_id = sanitize_text_field($_POST['unique_id']);
		}
		$sizey_size_unavailable_message = get_option('sizey-unavailable-message');
		if (isset($_POST['sizey_recommendation'])) {
			$sizey_recommendation = json_decode(stripslashes(sanitize_text_field($_POST['sizey_recommendation'])), true);
		}
		$sizey_recommendation_add_to_cart_button = get_option('sizey-recommendation-button-add-to-cart');
		$cart_id = WC()->session->get('new_cart');
		$earlier_session_data = WC()->session->get(WC()->session->get('new_cart'));
		$variable_to_set_session = array();
		$variable_to_set_session['unique_id'] = $unique_id;
		$variable_to_set_session['product_' . $post_id]['sizey_recommendation'] = $sizey_recommendation;
		$variable_to_set_session['product_' . $post_id]['available_sizes'] = $sizey_recommendation;
		$individual_size_chart_id = get_post_meta($post_id, 'sizey-chart-id', true); // Validate if any sizechart is mapped
		$global_size_attribute = 'pa_' . get_option('global-size-attributes'); //pa_size
		//Validate size chart id from main list
		foreach ($sizeychdata as $individual_chart_data) {
			$size_charts[] = $individual_chart_data['id'];
		}

		//Get Sizey Mapping data with boutique
		if (!isset($individual_size_chart_id)) {
			$jsontoreturn['status'] = 'success';
			$jsontoreturn['content'] = '';
			echo esc_html(json_encode($jsontoreturn));
			exit();
		}
		if (!in_array($individual_size_chart_id, $size_charts)) {
			$jsontoreturn['status'] = 'success';
			$jsontoreturn['content'] = '';
			echo esc_html(json_encode($jsontoreturn));
			exit();
		}
		$size_mapping_data = get_option($individual_size_chart_id); // Get individual sizey id mapped with boutique sizes
		if (!$size_mapping_data) {
			$size_mapping_data = get_option('global-sizey-mapping');
			if (!isset($size_mapping_data)) {
				$jsontoreturn['status'] = 'success';
				$jsontoreturn['content'] = '';
				echo esc_html(json_encode($jsontoreturn));
				exit();
			}
		}
		$size_mapping_data = json_decode($size_mapping_data, true);

		$escaped_size_mapping_data = array();
		foreach ($size_mapping_data as $key=>$value) {
			foreach ($value as $sizekey=>$size_value) {
				$escaped_size_mapping_data[strtolower(htmlspecialchars(sanitize_text_field( $size_value)))] = $key;
			}
		}

		global $product;
		$product_specific_size_attributes = wc_get_product_terms( $post_id, 'pa_' . get_option('global-size-attributes') ) ;
		foreach ($product_specific_size_attributes as $individual_attribute) {
			$size_slug[strtolower($individual_attribute->slug)] = strtolower($individual_attribute->slug);
		}
		$recommendedsize =  strtolower(htmlspecialchars(sanitize_text_field($sizey_recommendation['size'])));
		//Validate if the suggested size is available for a specific product
		if ( isset($escaped_size_mapping_data[$recommendedsize]) && in_array( strtolower($escaped_size_mapping_data[ $recommendedsize ]), $size_slug ) ) {
			$individual_product_to_generate_url = wc_get_product($post_id);
			$product_specific_variations = $individual_product_to_generate_url->get_available_variations();
			foreach ($product_specific_variations as $product_variations) {
				// if every attribute has separate variation and attributes are equals to suggested attribute

				if (isset( $product_variations['attributes']['attribute_' . $global_size_attribute]) &&
					'' !==  $product_variations['attributes']['attribute_' . $global_size_attribute] &&
					strtolower($escaped_size_mapping_data[$recommendedsize]) === strtolower($product_variations['attributes']['attribute_' . $global_size_attribute])
				) {
					$individual_product_variation_id = $product_variations['variation_id'];
				} else {
					if (isset( $product_variations['attributes']['attribute_' . $global_size_attribute]) &&
						'' ===  $product_variations['attributes']['attribute_' . $global_size_attribute]) {
						$individual_product_variation_id = $product_variations['variation_id'];
					}
				}

			}
			$built_query = array();
			$built_query['add-to-cart'] = $post_id;
			$built_query['attribute_' . $global_size_attribute] = $escaped_size_mapping_data[$recommendedsize];
			if (isset ($individual_product_variation_id)) {
				$built_query['variation_id'] = $individual_product_variation_id;
			}

			$addToCartUrl = $individual_product_to_generate_url->get_permalink() . '?' . http_build_query($built_query);

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
		} else {
			$variable_to_set_session['product_' . $post_id]['add_to_cart_url'] = '';
			$variable_to_set_session['product_' . $post_id]['add_to_cart_url_with_anchor'] = '';
			$variable_to_set_session['product_' . $post_id]['sizey_size_unavailable_message'] = $sizey_size_unavailable_message;
			$jsontoreturn['content'] = esc_html($sizey_size_unavailable_message);
			$jsontoreturn['status'] = 'success';
			echo esc_html(json_encode($jsontoreturn));
			exit();
		}
		$earlier_session_data = WC()->session->get($cart_id);
		$new_session_data = array_merge($earlier_session_data, $variable_to_set_session);
		WC()->session->set($cart_id, $new_session_data);
		exit();
	}

}
add_action( 'wp_ajax_nopriv_generate_vroom_recommendation_add_to_cart_button', 'generate_vroom_recommendation_add_to_cart_button' );
add_action( 'wp_ajax_generate_vroom_recommendation_add_to_cart_button', 'generate_vroom_recommendation_add_to_cart_button' );
