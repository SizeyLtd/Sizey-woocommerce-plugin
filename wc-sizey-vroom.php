<?php
/**
 * Plugin Name: Sizey vroom integration
 * Plugin URI: https://www.sizey.ai/
 * Description: Sizey Vroom woocommerce plugin
 * Version: 0.0.6
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
	define( 'VROOM_VERSION', '0.0.6' ); // Version of plugin
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
		// var_dump($post_id);
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

function delete_image( $image_path) {
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

/**
 * Update Vroom configuration data.
 *
 * @return null
 */
function update_vroom_config() {
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
	//Second Tab content Global Size Attribute updation
	if ( isset( $_POST['vroom-global-size-attribute-submit'] ) ) {
		if (
			! isset( $_POST['vroom-boutique-attribute-define-nonce-field'] )
			|| ! wp_verify_nonce( sanitize_text_field($_POST['vroom-boutique-attribute-define-nonce-field']), 'vroom-boutique-attribute-define-action' )
		) {
			return false;
		}
		update_option(
			'vroom-global-size-attributes',
			filter_input(INPUT_POST, 'vroom-global-size-attributes', FILTER_SANITIZE_STRING)
		);
	}

	//Second Tab Individual Sizey Attributes mapping
	if (isset($_POST['vroom-individual-attribute-mapping'])) {
		if (
			! isset( $_POST['vroom-sizey-size-specific-attribute-mapping-nonce-field'] )
			|| ! wp_verify_nonce( sanitize_text_field($_POST['vroom-sizey-size-specific-attribute-mapping-nonce-field']), 'vroom-sizey-size-specific-attribute-mapping-action' )
		) {
			return false;
		}
		$sizey_id = filter_input(INPUT_POST, 'sizey_id', FILTER_SANITIZE_STRING);
		//Store the selected data against sizey id
		$count_data = 0;
		if (isset($_POST['boutique_attribute']) && isset($_POST['sizey_selected'])) {
			$boutique_attribute =array();
			$count_data = count($_POST['boutique_attribute']);
			for ($i=0; $i < $count_data; $i++) {
				if (isset($_POST['boutique_attribute'][$i])) {
					$boutique_attribute[$i] = sanitize_text_field($_POST['boutique_attribute'][$i]);
				}
			}

			$sizey_selected =array();
			$count_data = count($_POST['sizey_selected']);
			for ($i=0; $i<$count_data; $i++) {
				if (isset($_POST['sizey_selected'][$i])) {
					$sizey_selected[$i] = sanitize_text_field($_POST['sizey_selected'][$i]);
				}
			}
			$selectedData = array_combine($boutique_attribute, $sizey_selected);
			update_option($sizey_id, $selectedData);
			echo '<div class="notice notice-success is-dismissible">
                    <p>Success! Sizechart mapping have been saved.</p>
                  </div>';
		}
	}
	//Second Tab Global Sizey Attribute mapping
	if (isset($_POST['vroom-global-attribute-mapping'])) {
		if (
			! isset( $_POST['vroom-boutique-attribute-sizey-global-mapping-nonce-field'] )
			|| ! wp_verify_nonce( sanitize_text_field($_POST['vroom-boutique-attribute-sizey-global-mapping-nonce-field']), 'vroom-boutique-attribute-sizey-global-mapping-action' )
		) {

			return false;
		}

		$boutique_attributes =array();
		$count_data = count($_POST['boutique_attribute']);
		for ($i=0; $i<$count_data; $i++) {
			if (isset($_POST['boutique_attribute'][$i])) {
				$boutique_attributes[$i] = sanitize_text_field($_POST['boutique_attribute'][$i]);
			}
		}
		$arrayToStore = array();
		foreach ($boutique_attributes as $boutique_size) {
			$arrayToStore[$boutique_size] = array();
			if (isset($_POST['global-sizey-' . $boutique_size])) {
				$global_sizey_count = count($_POST['global-sizey-' . $boutique_size]);
				for ($i=0; $i < $global_sizey_count; $i++) {
					if (isset($_POST['global-sizey-' . $boutique_size][$i])) {
						$arrayToStore[$boutique_size][$i] = htmlspecialchars(sanitize_text_field($_POST['global-sizey-' . $boutique_size][$i]));
					}
				}
			} else {
				$arrayToStore[$boutique_size] = [];
			}

		}

		update_option('vroom-global-sizey-mapping', json_encode($arrayToStore));
		echo '<div class="notice notice-success is-dismissible">
                <p>Success! Global Sizechart mapping have been saved.</p>
            </div>';
	}

	//Second Tab Load individual sizey content
	if (isset($_POST['redirect-url'])) {
		if (
			! isset( $_POST['boutique-attribute-redirect-url-nonce-field'] )
			|| ! wp_verify_nonce( sanitize_text_field($_POST['boutique-attribute-redirect-url-nonce-field']), 'boutique-attribute-redirect-url-action' )
		) {
			//wp_nonce_ays( '' );
			return false;
		}

		//Load the individual sizey id configuration mapping
		if (isset($_POST['sizey_id'])) {
			$url = 'admin.php?page=vroom-config&sizey_id=' . sanitize_text_field( $_POST['sizey_id'] );
			header( 'location: ' . $url );
			exit();
		}
	}

	//Second Tab content Individual sizey reset
	if (isset($_POST['vroom-sizey-attribute-reset'])) {
		if (
			! isset( $_POST['vroom-sizey-size-specific-attribute-mapping-nonce-field'] )
			|| ! wp_verify_nonce( sanitize_text_field($_POST['vroom-sizey-size-specific-attribute-mapping-nonce-field']), 'vroom-sizey-size-specific-attribute-mapping-action' )
		) {
			return false;
		}
		if (isset($_POST['sizey_id'])) {
			delete_option(sanitize_text_field($_POST['sizey_id']));
			echo '<div class="notice notice-success is-dismissible">
                <p>Success! Individual sizechart has been reset.</p>
            </div>';
		}

	}

	//Third Tab Garment mapping with sizey

	if (isset($_POST['vroom-sizey-mapping-garment'])) {
		if (
			! isset( $_POST['vroom-sizey-mapping-garment-nonce-field'] )
			|| ! wp_verify_nonce( sanitize_text_field($_POST['vroom-sizey-mapping-garment-nonce-field']), 'vroom-sizey-mapping-garment-action' )
		) {

			return false;
		}

		$boutique_attributes =array();
		$count_data = 0;
		if (isset($_POST['garment_attribute'])) {
			$count_data = count($_POST['garment_attribute']);
		}

		for ($i=0; $i<$count_data; $i++) {
			if (isset($_POST['garment_attribute'][$i])) {
				$boutique_attributes[$i] = sanitize_text_field($_POST['garment_attribute'][$i]);
			}
		}
		$arrayToStore = array();
		foreach ($boutique_attributes as $boutique_size) {
			$arrayToStore[$boutique_size] = array();
			if (isset($_POST['garment-sizey-' . $boutique_size])) {
				$global_sizey_count = count($_POST['garment-sizey-' . $boutique_size]);
				for ($i=0; $i < $global_sizey_count; $i++) {
					if (isset($_POST['garment-sizey-' . $boutique_size][$i])) {
						$arrayToStore[$boutique_size][$i] = htmlspecialchars(sanitize_text_field($_POST['garment-sizey-' . $boutique_size][$i]));
					}
				}
			} else {
				$arrayToStore[$boutique_size] = [];
			}

		}

		update_option('vroom-sizey-mapping-with-garment', json_encode($arrayToStore));
		echo '<div class="notice notice-success is-dismissible">
                <p>Success! Garment mapping with Sizey have been saved.</p>
            </div>';
	}

	return null;
}



function generate_vroom_config_form() {
	echo '<div class="data-table">
<ul id="vroom-sizey-tabs">
    <li><a href="#" name="vroomsizeytab1" >Sizey setting</a></li>
	<li><a href="#" name="vroomsizeytab2" >Store wide size mapping</a></li>
	<li><a href="#" name="vroomsizeytab3" >Sizey vs Garment</a></li>

</ul>
<div id="vroom-sizey-content">
    <div id="vroomsizeytab1">';

	if (file_exists(VROOM_PLUGIN_PATH . 'inc/admin/sizey-vroom-configuration.php')) {
		require_once VROOM_PLUGIN_PATH . 'inc/admin/sizey-vroom-configuration.php';
	}
	echo ' </div>';
	echo '<div id="vroomsizeytab2">';
	if (file_exists(VROOM_PLUGIN_PATH . 'inc/admin/sizey-vroom-global-configuration.php')) {
		require_once VROOM_PLUGIN_PATH . 'inc/admin/sizey-vroom-global-configuration.php';
	}
	echo '</div>';
	echo '<div id="vroomsizeytab3">';
	if (file_exists(VROOM_PLUGIN_PATH . 'inc/admin/sizey-size-mapping-with-garment-size.php')) {
		require_once VROOM_PLUGIN_PATH . 'inc/admin/sizey-size-mapping-with-garment-size.php';
	}
	echo '</div>';
	echo '</div></div>';
	if (file_exists(VROOM_PLUGIN_PATH . 'inc/sizey-vroom-instruction.php')) {
		require_once VROOM_PLUGIN_PATH . 'inc/sizey-vroom-instruction.php';
	}

	return null;
}


/**
 * Dropdrown creation of the global sizey mapping
 */

function vroom_generate_global_sizey_mapping( $boutique_size) {
	$mapped_attribute = get_vroom_global_mapping_by_boutique($boutique_size);
	$mapped_attribute_count = count($mapped_attribute);
	for ($counter =0; $counter < $mapped_attribute_count; $counter++) {
		$mapped_attribute[$counter] = $mapped_attribute[$counter];
	}
	$sizeySizesData = getVroomGlobalSizeyConfiguration();

	$selectData = '<select name="global-sizey-' . $boutique_size . '[]" id="global-sizey"
                    class="sizey-global-configuration" multiple="multiple">';
	foreach ($sizeySizesData as $sizes) {
		$selectData .='<option ';
		if (in_array($sizes, $mapped_attribute)) {
			$selectData .= ' selected="selected"';
		}
		$selectData .= ' value="' . $sizes . '">' . $sizes . '</option>';
	}
	$selectData .= '</select>';
	$allowed_html = array(
		'select'      => array(
			'name'  => array(),
			'id' => array(),
			'class' => array(),
			'multiple' => array()
		),
		'option'     => array(
			'value' => array(),
			'selected' => array()
		)
	);
	echo wp_kses( $selectData, $allowed_html );

}

/**
 * Dropdrown creation of the sizey mapping with garments
 */

function vroom_generate_global_sizey_mapping_for_garments( $garment_size) {
	$mapped_attribute = array();
	$global_sizey_mapping = json_decode(get_option('vroom-sizey-mapping-with-garment'), true);
	if (isset($global_sizey_mapping[$garment_size])) {
		$mapped_attribute = $global_sizey_mapping[$garment_size];
	}

	$mapped_attribute_count = count($mapped_attribute);
	for ($counter =0; $counter < $mapped_attribute_count; $counter++) {
		$mapped_attribute[$counter] = $mapped_attribute[$counter];
	}
	$sizeySizesData = getVroomGlobalSizeyConfiguration();

	$selectData = '<select name="garment-sizey-' . $garment_size . '[]" id="garment-sizey"
                    class="sizey-global-configuration" multiple="multiple">';
	foreach ($sizeySizesData as $sizes) {
		$selectData .='<option ';
		if (in_array($sizes, $mapped_attribute)) {
			$selectData .= ' selected="selected"';
		}
		$selectData .= ' value="' . $sizes . '">' . $sizes . '</option>';
	}
	$selectData .= '</select>';
	$allowed_html = array(
		'select'      => array(
			'name'  => array(),
			'id' => array(),
			'class' => array(),
			'multiple' => array()
		),
		'option'     => array(
			'value' => array(),
			'selected' => array()
		)
	);
	echo wp_kses( $selectData, $allowed_html );

}


function get_vroom_global_mapping_by_boutique( $boutique_attribute ) {
	$global_sizey_mapping = json_decode(get_option('vroom-global-sizey-mapping'), true);
	if ($global_sizey_mapping && $global_sizey_mapping[$boutique_attribute]) {
		return $global_sizey_mapping[$boutique_attribute];
	}
	return [];
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
	global $sizeychdata;
	$post_id = sanitize_text_field($_POST['post_id']);
		if ( isset($_POST['unique_id'])) {
			$unique_id = sanitize_text_field($_POST['unique_id']);
		}
	$sizey_size_unavailable_message = get_option('sizey-unavailable-message');
		if (isset($_POST['sizey_recommendation'])) {
			$sizey_recommendation = $_POST['sizey_recommendation'];//json_decode(stripslashes(sanitize_text_field($_POST['sizey_recommendation'])), true);
		}

		// var_dump(($_POST['sizey_recommendation']['size']));
	$sizey_recommendation_add_to_cart_button = get_option('vroom-sizey-recommendation-button-add-to-cart');
	$cart_id = WC()->session->get('new_cart');
	$earlier_session_data = WC()->session->get(WC()->session->get('new_cart'));
	$variable_to_set_session = array();
	$variable_to_set_session['unique_id'] = $unique_id;
	$variable_to_set_session['product_' . $post_id]['sizey_recommendation'] = $sizey_recommendation;
	$variable_to_set_session['product_' . $post_id]['available_sizes'] = $sizey_recommendation;
	$individual_size_chart_id = $_POST['chart_id']; //get_post_meta($post_id, 'sizey-chart-id', true); // Validate if any sizechart is mapped
	$global_size_attribute = 'pa_' . get_option('vroom-global-size-attributes'); //pa_size
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
		// var_dump($size_mapping_data);
		// $size_mapping_data = json_decode($size_mapping_data, true);

		$escaped_size_mapping_data = array();
		foreach ($size_mapping_data as $key=>$value) {
			// foreach ($value as $sizekey=>$size_value) {
			// 	var_dump($size_value);
				$escaped_size_mapping_data[strtolower(htmlspecialchars(sanitize_text_field( $value)))] = $key;
			// }
		}

	global $product;
	$product_specific_size_attributes = wc_get_product_terms( $post_id, 'pa_' . get_option('vroom-global-size-attributes') ) ;
		foreach ($product_specific_size_attributes as $individual_attribute) {
			$size_slug[strtolower($individual_attribute->slug)] = strtolower($individual_attribute->slug);
		}
	$recommendedsize =  strtolower(htmlspecialchars(sanitize_text_field($sizey_recommendation['size'])));
	// var_dump($_POST['sizey_recommendation']);
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


	// check_ajax_referer( 'recommendation_add_to_cart_button', 'nonce_data' );
	// $jsontoreturn = array();

	// if (isset($_POST['post_id']) && isset($_POST['unique_id'])) {
	// 	global $sizeychdata;
	// 	$post_id = sanitize_text_field($_POST['post_id']);
	// 	if ( isset($_POST['unique_id'])) {
	// 		$unique_id = sanitize_text_field($_POST['unique_id']);
	// 	}
	// 	if ( isset($_POST['sizey_recommendation'])) {
	// 		$garment_recommendation_data = sanitize_text_field($_POST['sizey_recommendation']);
	// 	}
	// 	$global_garment_data = json_decode(get_option('vroom-sizey-mapping-with-garment'), true);
	// 	//here the sizeychart data relate to sizey is retrieved
	// 	var_dump($garment_recommendation_data);
	// 	var_dump($global_garment_data);


	// 	$garment_specific_data = $global_garment_data[$garment_recommendation_data];
	// 	// var_dump($garment_recommendation_data);
	// 	for ($i = 0; $i < count($garment_specific_data); $i++) {
	// 		$garment_specific_data[$i] = strtolower($garment_specific_data[$i]);
	// 	}

	// 	$sizey_size_unavailable_message = get_option('vroom-sizey-unavailable-message');
	// 	$sizey_recommendation_add_to_cart_button = get_option('vroom-sizey-recommendation-button-add-to-cart');
	// 	$cart_id = WC()->session->get('new_cart');
	// 	$earlier_session_data = WC()->session->get(WC()->session->get('new_cart'));
	// 	$variable_to_set_session = array();
	// 	$variable_to_set_session['unique_id'] = $unique_id;
	// 	//$variable_to_set_session['product_' . $post_id]['available_sizes'] = $sizey_recommendation;
	// 	$individual_size_chart_id = $_POST['chart_id']; // Validate if any sizechart is mapped
	// 	$global_size_attribute = 'pa_' . get_option('vroom-global-size-attributes'); //pa_size
	// 	//Validate size chart id from main list
	// 	foreach ($sizeychdata as $individual_chart_data) {
	// 		$size_charts[] = $individual_chart_data['id'];
	// 	}

	// 	//Get Sizey Mapping data with boutique
	// 	if (!isset($individual_size_chart_id)) {
	// 		$jsontoreturn['status'] = 'success';
	// 		$jsontoreturn['content'] = '';
	// 		echo esc_html(json_encode($jsontoreturn));
	// 		exit();
	// 	}
	// 	if (!in_array($individual_size_chart_id, $size_charts)) {
	// 		// var_dump($individual_size_chart_id);
	// 		// var_dump($sizke_charts);
	// 		$jsontoreturn['status'] = 'success';
	// 		$jsontoreturn['content'] = '';
	// 		echo esc_html(json_encode($jsontoreturn));
	// 		exit();
	// 	}
	// 	$size_mapping_data = get_option($individual_size_chart_id); // Get individual sizey id mapped with boutique sizes
	// 	if (!$size_mapping_data) {

	// 		$size_mapping_data = get_option('vroom-global-sizey-mapping');
	// 		if (!isset($size_mapping_data)) {
	// 			$jsontoreturn['status'] = 'success';
	// 			$jsontoreturn['content'] = '';
	// 			echo esc_html(json_encode($jsontoreturn));
	// 			exit();
	// 		}
	// 	}
	// 	$size_mapping_data = json_decode($size_mapping_data, true);

	// 	$escaped_size_mapping_data = array();
	// 	foreach ($size_mapping_data as $key=>$value) {
	// 		foreach ($value as $sizekey=>$size_value) {
	// 			$escaped_size_mapping_data[strtolower(htmlspecialchars(sanitize_text_field( $size_value)))] = $key;
	// 		}
	// 	}
	// 	//sizechart mapping data

	// 	global $product;
	// 	$product_specific_size_attributes = wc_get_product_terms( $post_id, 'pa_' . get_option('vroom-global-size-attributes') ) ;
	// 	foreach ($product_specific_size_attributes as $individual_attribute) {
	// 		$size_slug[strtolower($individual_attribute->slug)] = strtolower($individual_attribute->slug);
	// 	}

	// 	// var_dump($garment_specific_data);
	// 	$recommendedsize =  $garment_specific_data[0];
	// 	//Validate if the suggested size is available for a specific product

	// 	// var_dump($escaped_size_mapping_data);
	// 	// var_dump($recommendedsize);
	// 	if ( isset($escaped_size_mapping_data[$recommendedsize]) && in_array( strtolower($escaped_size_mapping_data[ $recommendedsize ]), $size_slug ) ) {
	// 		$individual_product_to_generate_url = wc_get_product($post_id);
	// 		$product_specific_variations = $individual_product_to_generate_url->get_available_variations();
	// 		foreach ($product_specific_variations as $product_variations) {
	// 			// if every attribute has separate variation and attributes are equals to suggested attribute

	// 			if (isset( $product_variations['attributes']['attribute_' . $global_size_attribute]) &&
	// 				'' !==  $product_variations['attributes']['attribute_' . $global_size_attribute] &&
	// 				strtolower($escaped_size_mapping_data[$recommendedsize]) === strtolower($product_variations['attributes']['attribute_' . $global_size_attribute])
	// 			) {
	// 				$individual_product_variation_id = $product_variations['variation_id'];
	// 			} else {
	// 				if (isset( $product_variations['attributes']['attribute_' . $global_size_attribute]) &&
	// 					'' ===  $product_variations['attributes']['attribute_' . $global_size_attribute]) {
	// 					$individual_product_variation_id = $product_variations['variation_id'];
	// 				}
	// 			}

	// 		}
	// 		$built_query = array();
	// 		$built_query['add-to-cart'] = $post_id;
	// 		$built_query['attribute_' . $global_size_attribute] = $escaped_size_mapping_data[$recommendedsize];
	// 		if (isset ($individual_product_variation_id)) {
	// 			$built_query['variation_id'] = $individual_product_variation_id;
	// 		}

	// 		$addToCartUrl = $individual_product_to_generate_url->get_permalink() . '?' . http_build_query($built_query);

	// 		$data_to_return = '<a href="' . esc_url($addToCartUrl) . '" class="button" id="recommendation-url">' . esc_html($sizey_recommendation_add_to_cart_button) . '</a>';
	// 		$variable_to_set_session['product_' . $post_id]['add_to_cart_url'] = $addToCartUrl;
	// 		$variable_to_set_session['product_' . $post_id]['add_to_cart_url_with_anchor'] = $data_to_return;
	// 		$variable_to_set_session['product_' . $post_id]['sizey_size_unavailable_message'] = $sizey_size_unavailable_message;
	// 		$jsontoreturn['status'] = 'success';
	// 		$jsontoreturn['url'] = $addToCartUrl;
	// 		$jsontoreturn['class'] = 'button';
	// 		$jsontoreturn['id'] = 'recommendation-url';
	// 		$jsontoreturn['content'] = $sizey_recommendation_add_to_cart_button;
	// 		echo esc_html(json_encode($jsontoreturn));
	// 		exit();
	// 	} else {
	// 		$variable_to_set_session['product_' . $post_id]['add_to_cart_url'] = '';
	// 		$variable_to_set_session['product_' . $post_id]['add_to_cart_url_with_anchor'] = '';
	// 		$variable_to_set_session['product_' . $post_id]['sizey_size_unavailable_message'] = $sizey_size_unavailable_message;
	// 		$jsontoreturn['content'] = esc_html($sizey_size_unavailable_message);
	// 		$jsontoreturn['status'] = 'success';
	// 		echo esc_html(json_encode($jsontoreturn));
	// 		exit();
	// 	}
	// 	$earlier_session_data = WC()->session->get($cart_id);
	// 	$new_session_data = array_merge($earlier_session_data, $variable_to_set_session);
	// 	WC()->session->set($cart_id, $new_session_data);
	// 	exit();
	// }

}
add_action( 'wp_ajax_nopriv_generate_vroom_recommendation_add_to_cart_button', 'generate_vroom_recommendation_add_to_cart_button' );
add_action( 'wp_ajax_generate_vroom_recommendation_add_to_cart_button', 'generate_vroom_recommendation_add_to_cart_button' );


function get_vroom_sizey_ids ( $sizey_id = null) {
		global $sizeychdata;
		$sizeyData = $sizeychdata;
		$complete_details = array();
	if ( null != $sizey_id ) {
		foreach ($sizeyData as $sizeys) {
			if ($sizeys['id'] == $sizey_id) {
				$complete_details['id'] = $sizey_id;
				$complete_details['name'] = ucwords($sizeys['brand'] . ' - ' . $sizeys['gender'] . ' - '
													. $sizeys['garment']);
				$complete_details['size'] = array_filter(array_unique($sizeys['sizes'][0]['sizes']), 'strlen');
			}
		}
	} else {
		foreach ($sizeyData as $sizeys) {
			$complete_details[$sizeys['id']] = ucwords($sizeys['brand'] . ' - '
													   . $sizeys['gender'] . ' - ' . $sizeys['garment']);
		}
	}
		return $complete_details;


}



function generate_vroom_sizey_specific_boutique_dropdown_mapping( $sizey_details, $boutique_size) {
	$sizes = $sizey_details['size'];
	foreach ($sizes as $key => $value) {
		$sizes[$key] = htmlspecialchars(sanitize_text_field($value));
	}
	$sizes =array_unique($sizes);
	$sizes = array_values($sizes);

	$mapped_sizes = get_option($sizey_details['id'], true);
	$selectbox = '<select name="sizey_selected[]" >';
	foreach ($sizes as $size) {
		$selectbox .= '<option ';
		if ( htmlspecialchars(sanitize_text_field($mapped_sizes[$boutique_size])) ===  $size) {
			$selectbox .= ' selected="selected"';
		}
		$selectbox .= ' value="' . $size . '"';
		$selectbox .= '>' . $size . '</option>';
	}
	$selectbox .= '</select>';
	$allowed_html = array(
		'select'      => array(
			'name'  => array(),
			'id' => array(),
			'class' => array(),
			'multiple' => array()
		),
		'option'     => array(
			'value' => array(),
			'selected' => array()
		)
	);
	echo wp_kses( $selectbox, $allowed_html );
}


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
