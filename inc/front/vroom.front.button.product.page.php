<?php
// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}
global $product;
$productID = get_the_ID();

$vroom_sizey_api_key = get_option('vroom-sizey-api-key');
$size_chart_data_with_id = array();
$size_slug = array();
$recommendedSizey = array();
$recommendedsize = '';
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
	
	   style="display: none; <?php echo esc_html(get_option('vroom-sizey-css')); ?>"
	   target="popup"  <?php echo esc_html($sizeypopupclass); ?>
	   onclick="openSizeyPopupViaVroom('<?php echo esc_html($vroom_sizey_api_key); ?>',
			   '<?php echo esc_html($productID); ?>',
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

		fetch('https://vroom-api.sizey.ai/products/' + <?php echo esc_html($productID); ?>, {headers: {'x-sizey-key': '<?php echo esc_html($vroom_sizey_api_key); ?>'}}).then(o => o.json()).then(product => {
			if(product.sizeChart?.id) {
				document.getElementById('SizeyMe').style.display = 'inline';
			}
		});

		function call_realtime_vroom_button(unique_id, sizey_recommendation, product_id=<?php echo esc_html($productID); ?>) {
			console.log("CORRECT", sizey_recommendation);
			let nonce_data = jQuery( '#recommendation-button-nonce' ).data( 'nonce' );
			// fetch('https://localhost:9001/products/woo-<?php echo esc_html($productID); ?>').then(o => o.json()).then(product => {
            // if(!product.sizeChartId) {
            //     // show some error?

            // } else {
				jQuery.ajax({
				url: "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
				type: 'POST',
				data: {
					action: "generate_vroom_recommendation_add_to_cart_button","post_id":product_id, "unique_id": unique_id, "sizey_recommendation":sizey_recommendation, "nonce_data": nonce_data
				},
				success: function (data, textStatus, jqXHR) {

					let data_to_show = '';
					data = jQuery('<textarea />').html(data).text();
					data =JSON.parse(data);
					if(undefined !== data.url) {
						data_to_show = '<a href="'+data.url+'" id="'+data.id+'" class="'+data.class+'" >'+data.content+'</a>';
					} else {
						data_to_show = data.content;
					}
					jQuery("#sizeyVroomRecommendedButton").html(data_to_show);
                    jQuery("#sizeyVroomRecommendationResult").html(data_to_show);
				}
			});				
            // }
        // })


		}
		jQuery(document).ready(function () {
			if (sessionStorage.getItem('sizey-recommendation_'+<?php echo esc_html($productID); ?>) && sessionStorage.getItem('unique-id')) {
				call_realtime_vroom_button(sessionStorage.getItem('unique-id'), JSON.parse(sessionStorage.getItem('sizey-recommendation_<?php echo esc_html($productID); ?>')), <?php echo esc_html($productID); ?>);
			}
		});
	</script>
<?php

