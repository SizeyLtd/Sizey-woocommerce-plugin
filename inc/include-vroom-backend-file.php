<?php
// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

function vroom_registration_scripts() {

	wp_register_script(
		'sizey_vroom_custom',
		plugins_url('../assets/js/sizey-vroom-custom.js', __FILE__),
		array( 'jquery' ),
		time(),
		true
	);
	wp_enqueue_script('sizey_vroom_custom');

}

add_action('admin_enqueue_scripts', 'vroom_registration_scripts');

function vroom_admin_style() {
	wp_enqueue_style('vroom-form-styles',
		plugins_url('../assets/css/vroom-form.css', __FILE__),
		[],
		true
	);

}
add_action('admin_enqueue_scripts', 'vroom_admin_style');

function vroom_front_scripts() {
	wp_register_script(
		'uuid',
		plugins_url('../assets/js/uuid.min.js', __FILE__),
		array( 'jquery' ),
		true,
		true
	);
	wp_register_script('vroom',
		plugins_url('../assets/js/sizey-vroom-product-page.js', __FILE__),
		array( 'jquery' ),
		//true,
		time(),
		true);
	wp_enqueue_script('vroom');
	wp_enqueue_script('uuid');
}
add_action('wp_enqueue_scripts', 'vroom_front_scripts');
