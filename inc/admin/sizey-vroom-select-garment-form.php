<?php
/**
 * Provide a admin area form view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since      1.0.0
 *
 * @package    size-chart-for-woocommerce
 * @subpackage size-chart-for-woocommerce/admin/partials
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

global $post;
global $garment_data_from_api;
$current_post_id = $post->ID;
$selected_garment_id = get_post_meta($current_post_id, 'sizey-garment-id', true);
$selected_garment_name = get_post_meta($current_post_id, 'sizey-garment-name', true);
?>
<div id="size-chart-meta-fields">
	<div id="field">
		<div class="field-item">
			<label for="sizey-garment-id"></label>
			<select class="sizey-garment-id" name="sizey-garment-id" id="sizey-garment-id" data-allow_clear="true"
				data-placeholder="<?php esc_attr_e('Type the garment name', ''); ?>"
				data-minimum_input_length="3"
				data-nonce="<?php echo esc_attr(wp_create_nonce('garment_search_nonce')); ?>">
				<?php
				foreach ($garment_data_from_api as $garment_id=>$garment_name) {
					if (isset($selected_garment_id) && ! empty($selected_garment_id)  && ( $selected_garment_id === $garment_id )) {
						printf(
							"<option value='%s' selected>%s</option>",
							esc_attr__($garment_id),
							esc_html($garment_name)
						);
					} else {
						printf(
							"<option value='%s'>%s</option>",
							esc_attr__($garment_id),
							esc_html($garment_name)
						);
					}
				}
				?>
			</select>
			<input type="hidden" name="sizey-garment-name" class="sizey-garment-name" id="sizey-garment-name"
				data-nonce="<?php echo esc_attr(wp_create_nonce('sizey-garment-name')); ?>"
				value="<?php echo esc_html($selected_garment_name); ?>" />
		</div>


	</div>
</div>
