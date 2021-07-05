<?php
// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}
/*
 * Sizey Admin Configuration tab 2 content
 * */
$attributes = wc_get_attribute_taxonomies();
$allAttributes = array();
$allAttr = array();
$attribute_taxonomies =array();
$global_size_att = get_option('global-size-attributes');
foreach ($attributes as $attr) {
	$allAttributes[$attr->attribute_name] = $attr->attribute_label;
	$allAttr[] =$attr->attribute_name;
}
global $sizeychdata;
$size_chart_data = $sizeychdata;
$size_chart_data_with_id =array();
foreach ($size_chart_data as $individual_chart_data) {
	$size_chart_data_with_id[$individual_chart_data['id']]= $individual_chart_data['sizes'][0]['sizes'];
}

?>
<div id="namediv" class="stuffbox">
	<div class="inside">
		<div class="content">
			<label>Global Configuration:</label>
			<form action="" method="post" id="vroom-boutique-attribute">
				<?php wp_nonce_field( 'vroom-boutique-attribute-define-action', 'vroom-boutique-attribute-define-nonce-field' ); ?>
				<select name="vroom-global-size-attributes"
						id="vroom-global-size-attributes"
						class="global-dropdown" onchange="return validatevroomsizesumbit()">
<?php
foreach ($allAttributes as $attrb_name => $attrb_value) {
	$selected='';
	if (trim($global_size_att) == trim($attrb_name)) {
		$selected = ' selected ="selected" ';
	}
	?>

	<option <?php echo esc_html( $selected ); ?> value="<?php echo esc_html($attrb_name); ?>" ><?php echo esc_html($attrb_value); ?></option>

<?php } ?>
				</select>
				<input type="hidden" name="vroom-global-size-attribute-submit" value="Save" />
			</form>
		</div>
<?php
		$attribute_taxonomies[] = get_option('vroom-global-size-attributes');
		$attributes_sizes = array_filter(array_map('wc_attribute_taxonomy_name', $attribute_taxonomies));
		$boutiqueattr=array();
		$terms = get_terms($attributes_sizes[0]);
foreach ($terms as $boutique_size) {
	$boutiqueattr[$boutique_size->slug] = $boutique_size->name;
}

?>

		<div class="content">

	<table>
		<colgroup>
			<col style="width:30%">
			<col style="width:70%">
		</colgroup>
		<thead>
		<tr>
			<th>Store size</th>
			<th>Sizey sizes</th>
		</tr>
		</thead>
		<tbody>
		<form action="" method="post">
			<?php wp_nonce_field( 'vroom-boutique-attribute-sizey-global-mapping-action', 'vroom-boutique-attribute-sizey-global-mapping-nonce-field' ); ?>
<?php foreach ($boutiqueattr as $key => $value) { ?>
<tr>
								<td><input type="hidden" name="boutique_attribute[]" value="<?php echo esc_html($key); ?>"/>
								<input type="hidden" name="global-sizey-mapping"
											value="global-sizey-mapping"/><?php echo esc_html($value); ?>
	</td><td>
<?php vroom_generate_global_sizey_mapping($key); ?>
	</td></tr>
<?php } ?>
					<tr><td colspan="2">
							<input type="submit" class="global-button text-right button button-primary button-large"
								   name="vroom-global-attribute-mapping" value="Save"/>
						</td>
					</tr>
				</form>
				</tbody>
			</table>
		</div>
<?php

		$attribute_taxonomies[] = get_option('global-size-attributes');
		$attributes_sizes = array_filter(array_map('wc_attribute_taxonomy_name', $attribute_taxonomies));
		$boutiqueattr=array();
		$terms = get_terms($attributes_sizes[0]);
foreach ($terms as $boutique_size) {
	$boutiqueattr[$boutique_size->slug] = $boutique_size->name;
}
?>
</div>
</div>
<div id="namediv" class="stuffbox">
	<div class="inside">
		<div class="content">
			<label>Size charts:</label>
			<?php $sizey_details = get_vroom_sizey_ids(); ?>
			<form action="" method="post" id="vroom-sizey-dropdown">
				<?php wp_nonce_field( 'boutique-attribute-redirect-url-action', 'boutique-attribute-redirect-url-nonce-field' ); ?>
				<select name="sizey_id" class="global-dropdown" onchange="submitvroomform()">';
	<?php
		$loaded_sizey_id = '';
	if ( isset( $_GET['sizey_id'] ) ) {
		$loaded_sizey_id = sanitize_text_field($_GET['sizey_id']);
	}

	foreach ($sizey_details as $key => $value) {
		?>
	<option
		<?php if (trim($loaded_sizey_id) !='' && trim($loaded_sizey_id) == $key) { ?>
		 selected = "selected"
	<?php	} ?>
	 value="<?php echo esc_html($key); ?>"><?php echo esc_html($value); ?></option>
	<?php } ?>
				</select>
				<input type="hidden" name="redirect-url" value="Load" />

			</form>
		</div>
		<div class="content">
	<?php
	$sizey_id = '';
	if ( isset( $_GET['sizey_id'] ) ) {
		$sizey_id = sanitize_text_field($_GET['sizey_id']);
	}

	if ( '' != $sizey_id ) {
		$sizey_details = get_vroom_sizey_ids($sizey_id);
		?>
	<form action="" method="post" >
		<?php wp_nonce_field( 'vroom-sizey-size-specific-attribute-mapping-action', 'vroom-sizey-size-specific-attribute-mapping-nonce-field' ); ?>
				<table>
				<thead>
				<tr><th>Store size</th><th>Size chart size</th></tr>
				</thead>
				<tbody>
<?php
	$size_chart_data_with_id[$individual_chart_data['id']] = $individual_chart_data['sizes'][0]['sizes'];
		foreach ($boutiqueattr as $key => $value) {
			?>
		<tr>
						<td>
							<input type="hidden" name="boutique_attribute[]"
								   value="<?php echo esc_html($key); ?>"/>
							<input type="hidden" name="sizey_id"
								   value="<?php echo esc_html($sizey_details['id']); ?>"/>
					 <?php echo esc_html($value); ?></td>
		<td>
			<?php generate_vroom_sizey_specific_boutique_dropdown_mapping($sizey_details, $key); ?>
		</td>
		</tr>
		<?php } ?>
	<tr>
				<td colspan="2">
					<input type="submit" name="vroom-sizey-attribute-reset"
						   class="global-button text-right button button-primary button-large "
						   value="Reset Override" />
					<input type="submit" class="global-button text-right button button-primary button-large mr-10"
						   name="vroom-individual-attribute-mapping" value="Save"/>
				</td>
			</tr>
		</tbody>
	</table>
	</form>
<?php } ?>
</div>

	</div>
</div>
