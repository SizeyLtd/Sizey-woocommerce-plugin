<?php
// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}
?>
<?php
//Add heading for the sizey in product list page
add_filter( 'manage_edit-product_columns', 'sizey_vroom_extra_column', 22 );

function sizey_vroom_extra_column( $columns_array ) {
	$vroom_columns['vroom-garment'] = 'Garment';
	array_splice( $columns_array, count($columns_array), 0, $vroom_columns ) ;

	return $columns_array;

}

// Adds sizey name to our newly created column
add_action( 'manage_posts_custom_column', 'sizey_vroom_populate_columns' );
function sizey_vroom_populate_columns( $column_name ) {
	if ( 1 === $column_name  ) {
		$postmeta =  get_post_meta( get_the_ID(), 'sizey-garment-name', true );
		if (isset($postmeta)) {
			echo esc_html($postmeta);
		} else {
			echo esc_html('-');
		}
	}

}


