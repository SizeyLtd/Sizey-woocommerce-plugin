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


$individualSizeycharts = getVroomGlobalSizeyConfiguration(); //Get All Sizey Sizes
$garment_specific_sizes = get_all_garment_specific_sizes();

?>
<div id="namediv" class="stuffbox">
	<div class="inside">
		<div class="content">
			<label>Garment Mapping With sizey Sizes:</label>

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
		<form action="" method="post">
		<?php wp_nonce_field( 'vroom-sizey-mapping-garment-action', 'vroom-sizey-mapping-garment-nonce-field' ); ?>
	<table>
		<colgroup>
			<col style="width:30%">
			<col style="width:70%">
		</colgroup>
		<thead>
		<tr>
			<th>Garment size</th>
			<th>Sizey sizes</th>
		</tr>
		</thead>
		<tbody>


<?php foreach ($garment_specific_sizes as $value) { ?>
<tr>
								<td><input type="hidden" name="garment_attribute[]" value="<?php echo esc_html($value); ?>"/>
								<?php echo esc_html($value); ?>
	</td><td>
<?php vroom_generate_global_sizey_mapping_for_garments($value); ?>
	</td></tr>
<?php } ?>
					<tr><td colspan="2">
							<input type="submit" class="global-button text-right button button-primary button-large"
								   name="vroom-sizey-mapping-garment" value="Save"/>
						</td>
					</tr>

				</tbody>
			</table>
			</form>
		</div>
</div>
</div>

