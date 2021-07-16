<?php
// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}
global $product;
$productID = get_the_ID();
$sizey_garment_id =  get_post_meta( $productID, 'sizey-garment-id', true );

$vroom_sizey_api_key = get_option('vroom-sizey-api-key');
$size_chart_data_with_id = array();
$size_slug = array();
$recommendedSizey = array();
$recommendedsize = '';
$product_specific_size_attributes = wc_get_product_terms( $productID, 'pa_' . get_option('vroom-global-size-attributes') ) ;
if (!isset($product_specific_size_attributes) || count($product_specific_size_attributes)===0) {
	return;
}
//Get Sizey Mapping data with boutique


$new_cart = WC()->session->get('new_cart');
if ( is_null($new_cart) ) {
	WC()->session->set('new_cart', time());
}
$session_data = WC()->session->get($new_cart);

if (get_option('vroom-sizey-button-type') === 'button') {
	$sizeypopupclass = ' class=button ';
} else {
	$sizeypopupclass =' ';
}

echo '<span id="sizeyRecommendedButton">';
?>
	<a href="#" id="SizeyMe"
	   style="<?php echo esc_html(get_option('vroom-sizey-css')); ?>"
	   target="popup"  <?php echo esc_html($sizeypopupclass); ?>
	   onclick="openSizeyPopupViaVroom('<?php echo esc_html($vroom_sizey_api_key); ?>',
			   '<?php echo esc_html($sizey_garment_id); ?>',
			   '<?php echo esc_html($productID); ?>');
			   return false;">
		<?php echo esc_html(get_option('vroom-sizey-button')); ?>
	</a>
<?php
 echo '</span>';

 echo '<span id="sizeyVroomRecommendedButton"></span>';
/* Create Nonce */
$nonce = wp_create_nonce( 'recommendation_add_to_cart_button' );
?>
	<div id="recommendation-button-nonce" data-nonce="<?php echo esc_attr( $nonce ); ?>"></div>
	<script type="text/javascript">
		function call_realtime_vroom_button(unique_id, sizey_recommendation, product_id=<?php echo esc_html($productID); ?>) {
			let nonce_data = jQuery( '#recommendation-button-nonce' ).data( 'nonce' );
			console.log(1, sizey_recommendation)

			fetch('https://vroom-api.sizey.ai/garments/<?php echo esc_html($sizey_garment_id); ?>').then(o => o.json()).then(garment => {
            if(!garment.sizeChartId) {
                // show some error?

            } else {
				jQuery.ajax({
				url: "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
				type: 'POST',
				data: {
					action: "generate_vroom_recommendation_add_to_cart_button","post_id":product_id, "unique_id": unique_id, "chart_id": garment.sizeChartId, "sizey_recommendation":sizey_recommendation, "nonce_data": nonce_data
				},
				success: function (data, textStatus, jqXHR) {

					let data_to_show = '';
					data = jQuery('<textarea />').html(data).text();
					console.log("KK", data)
					data =JSON.parse(data);
					console.log("KK", data);
					if(undefined !== data.url) {
						data_to_show = '<a href="'+data.url+'" id="'+data.id+'" class="'+data.class+'" >'+data.content+'</a>';
					} else {
						data_to_show = data.content;
					}
					jQuery("#sizeyVroomRecommendedButton").html(data_to_show);
                    jQuery("#sizeyVroomRecommendationResult").html(data_to_show);
				}
			});				
            }
        })


		}
		jQuery(document).ready(function () {
			if (sessionStorage.getItem('sizey-recommendation_'+<?php echo esc_html($productID); ?>) && sessionStorage.getItem('unique-id')) {
				call_realtime_vroom_button(sessionStorage.getItem('unique-id'), JSON.parse(sessionStorage.getItem('sizey-recommendation_<?php echo esc_html($productID); ?>')), <?php echo esc_html($productID); ?>);
			}
		});
	</script>
<?php

