<?php
// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}
$sizey_button_position = array(
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

$sizey_key = get_option('sizey-api-key');
$recommendation_add_to_cart = get_option('sizey-recommendation-button-add-to-cart');
$sizey_button = get_option('sizey-button');
$sizey_button_type = get_option('sizey-button-type');
$sizey_css = get_option('sizey-css');
$sizey_unavailable_message = get_option('sizey-unavailable-message');
?>
<div id="namediv" class="stuffbox">
	<div class="inside">
		<form action="" method="post">
			<?php wp_nonce_field( 'sizey-vroom-config-action', 'sizey-config-nonce-field' ); ?>
			<table class="form-table editcomment">
				<tr>
					<td><label for="name">Sizey API key</label>
						<input required type="text" id="sizey-api-key" name="sizey-api-key"
							   value="<?php echo esc_html($sizey_key); ?>"
							   onblur="validateAPIKey(this)" />
						<br />
						Sign up to Sizey portal and get your own company API-key:
						<a href="https://portal.sizey.ai" target="_blank">
							https://portal.sizey.ai</a>
					</td>
				</tr>

				<tr>
					<td><label for="name">Button position</label>
					<select required name="sizey-button-position"
								id="sizey-button-position">';
						<?php
						$previous_sizey_button_position = get_option('sizey-button-position');
						foreach ($sizey_button_position as $sizey_buttons => $value) {
							$selected = '';
							if ($previous_sizey_button_position === $sizey_button) {
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
						<label for="name">Measuring button text</label><input required type="text" name="sizey-button"
						id="sizey-button" value="<?php echo esc_html($sizey_button); ?>" />

					</td>
				</tr>
				<tr>
					<td><label for="name">Recommendation button text</label>
						<input required type="text" id="sizey-recommendation-button-add-to-cart"
							   name="sizey-recommendation-button-add-to-cart"
							   value="<?php echo esc_html($recommendation_add_to_cart); ?>" />
					</td>
				</tr>
				<tr>
					<td>
						<label for="name">Button type</label>
	<select required name="sizey-button-type" id="sizey-button-type">
	<option value="button"
	<?php
	if ( 'button' === $sizey_button_type ) {
		echo esc_html(' selected="selected" ');
	}
	?>
	>Button</option>
	<option value="link"
	<?php
	if ( 'link' === $sizey_button_type ) {
		echo esc_html(' selected = "selected" ');
	}
	?>
	>Link </option></select>
	</td>
				</tr>
				<tr>
					<td>
						<label for="name">Best fit unavailable</label><input required type="text" name="sizey-unavailable-message"
																			  id="sizey-unavailable-message" value="<?php echo esc_html($sizey_unavailable_message); ?>" />

					</td>
				</tr>
				<tr>
					<td>
						<label for="name">Button css-style</label>
						<textarea name="sizey-css" id="sizey-css"><?php echo esc_html($sizey_css); ?></textarea>
</td>
</tr>
<tr>
	<td>
		<input type="submit" id="sizey-button-configuration"
			   name="sizey-button-configuration"
			   value="Save" class="button button-primary button-large" />
	</td>
</tr>
</table>
</form>
</div>
</div>
