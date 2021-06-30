<?php
// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}
$vroom_sizey_button_position = array(
	'woocommerce_after_add_to_cart_button' => 'After Add To Cart',
	'woocommerce_single_product_summary' => 'Single Product Summary',
	'woocommerce_product_meta_start' => 'Before Product Meta',
	'woocommerce_product_meta_end' => 'After Product Meta',
	'woocommerce_share' => 'Share',
	'woocommerce_before_single_product' => 'Before Single Product',
	'woocommerce_after_single_product' => 'After Single Product',
	'woocommerce_before_single_product_summary' => 'Before Single Product Summary',
	'woocommerce_after_single_product_summary' => 'After Single Product Summary',
	'woocommerce_before_main_content' => 'Before Main Content',
	'woocommerce_after_main_content' => 'After Main Content'
);

$vroom_sizey_key = get_option('vroom-sizey-api-key');
$vroom_recommendation_add_to_cart = get_option('vroom-sizey-recommendation-button-add-to-cart');
$vroom_sizey_button = get_option('vroom-sizey-button');
$vroom_sizey_button_type = get_option('vroom-sizey-button-type');
$vroom_sizey_css = get_option('vroom-sizey-css');
$vroom_sizey_unavailable_message = get_option('vroom-sizey-unavailable-message');
$previous_sizey_button_position = get_option('vroom-sizey-button-position');
?>
<div id="namediv" class="stuffbox">
	<div class="inside">
		<form action="" method="post">
			<?php wp_nonce_field( 'sizey-vroom-config-action', 'vroom-config-nonce-field' ); ?>
			<table class="form-table editcomment">
				<tr>
					<td><label for="name">Sizey API key</label>
						<input required type="text" id="vroom-sizey-api-key" name="vroom-sizey-api-key"
							   value="<?php echo esc_html($vroom_sizey_key); ?>"
							   onblur="validateAPIKey(this)" />
						<br />
						Sign up to Sizey portal and get your own company API-key:
						<a href="https://portal.sizey.ai" target="_blank">
							https://portal.sizey.ai</a>
					</td>
				</tr>

				<tr>
					<td><label for="name">Button position</label>
					<select required name="vroom-sizey-button-position"
								id="vroom-sizey-button-position">';
						<?php

						foreach ($vroom_sizey_button_position as $sizey_buttons => $value) {
							$selected = '';
							if ($previous_sizey_button_position === $sizey_buttons) {
								$selected = ' selected="selected" ';
							}
							?>
								<option value="<?php echo esc_html( $sizey_buttons ); ?>" <?php echo esc_html($selected); ?> ><?php echo esc_html($value); ?></option>
						<?php } ?>
					</select>
					</td>
				</tr>

				<tr>
					<td>
						<label for="name">Measuring button text</label><input required type="text" name="vroom-sizey-button"
						id="vroom-sizey-button" value="<?php echo esc_html($vroom_sizey_button); ?>" />

					</td>
				</tr>

				<tr>
					<td><label for="name">Recommendation button text</label>
						<input required type="text" id="vroom-sizey-recommendation-button-add-to-cart"
							   name="vroom-sizey-recommendation-button-add-to-cart"
							   value="<?php echo esc_html($vroom_recommendation_add_to_cart); ?>" />
					</td>
				</tr>

				<tr>
					<td>
						<label for="name">Button type</label>
						<select required name="vroom-sizey-button-type" id="vroom-sizey-button-type">
							<option value="button"
							<?php
							if ( 'button' === $vroom_sizey_button_type ) {
								echo esc_html(' selected="selected" ');
							}
							?>
							>Button</option>
							<option value="link"
							<?php
							if ( 'link' === $vroom_sizey_button_type ) {
								echo esc_html(' selected = "selected" ');
							}
							?>
							>Link </option>
					</select>
					</td>
				</tr>

				<tr>
					<td>
						<label for="name">Best fit unavailable</label><input required type="text" name="vroom-sizey-unavailable-message"
																			  id="vroom-sizey-unavailable-message" value="<?php echo esc_html($vroom_sizey_unavailable_message); ?>" />

					</td>
				</tr>
				<tr>
					<td>
						<label for="name">Button css-style</label>
						<textarea name="vroom-sizey-css" id="vroom-sizey-css"><?php echo esc_html($vroom_sizey_css); ?></textarea>
</td>
</tr>
<tr>
	<td>
		<input type="submit" id="vroom-sizey-button-configuration"
			   name="vroom-sizey-button-configuration"
			   value="Save" class="button button-primary button-large" />
	</td>
</tr>
</table>
</form>
</div>
</div>
