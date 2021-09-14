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
			let nonce_data = jQuery( '#recommendation-button-nonce' ).data( 'nonce' );
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

		}
		jQuery(document).ready(function () {
			if (sessionStorage.getItem('sizey-recommendation_'+<?php echo esc_html($productID); ?>) && sessionStorage.getItem('unique-id')) {
				call_realtime_vroom_button(sessionStorage.getItem('unique-id'), JSON.parse(sessionStorage.getItem('sizey-recommendation_<?php echo esc_html($productID); ?>')), <?php echo esc_html($productID); ?>);
			}
		});





	jQuery(window).load(function(){
		setTimeout(function() {
			for(var oldli of document.getElementsByClassName('woocommerce-product-gallery')[0].getElementsByTagName('ol')[0].getElementsByTagName('li')) {
    			oldli.addEventListener("click", function() {        
		        var vroom = document.getElementById('sizey-vroom');
				if(vroom) {
					vroom.style.display = 'none';
				}
    
		  	  });
			}

			var li = document.createElement("li");

			var img = document.createElement("img");
			img.src = "https://www.sizey.ai/wp-content/uploads/sizey_logo_mvp-300x75.png";
			li.appendChild(img);

			var photos = document.getElementsByClassName('woocommerce-product-gallery')[0].getElementsByTagName('ol')[0];
			photos.appendChild(li);


			li.addEventListener("click", function() {

				var vroom = document.getElementById('sizey-vroom');
				if(vroom) {
					vroom.style.display = 'table';
					return;
				}

				var iframe = document.createElement('iframe');
				iframe.id = "sizey-vroom";
				iframe.style.position = 'absolute';    
				iframe.style.display = "table";
				iframe.style.height = "100%";
				iframe.style.width = "100%";
				iframe.style.padding = 0;
				iframe.style.margin = 0;
				iframe.style.border = 'none';
				// iframe.src = "https://vroom.sizey.ai/";
				iframe.src = "http://localhost:3000/?apikey=<?php echo esc_html($vroom_sizey_api_key); ?>";

				iframe.addEventListener('load', function() {
					fetch('http://localhost:9000/my-avatar?measurementId=' + sessionStorage.getItem('sizey-measurement-id'), {headers: {'x-sizey-key': '<?php echo esc_html($vroom_sizey_api_key); ?>'}}).then(o => o.json()).then(avatar => {
						console.log("Loading my avatar", avatar)
						iframe.contentWindow.postMessage({action: "CHANGE_AVATAR",payload: { id: avatar.id, scale: 1 }}, "*");
					});
					setTimeout(function() {
						// iframe.contentWindow.postMessage({action: "CHANGE_AVATAR",payload: { id: 'f_sz_mid_n56', scale: 1 }}, "*");
						iframe.contentWindow.postMessage({action: "CHANGE_GARMENT", payload: { id: '<?php echo esc_html($productID); ?>', size: 'xl', colorway: '', scale: 1 }}, "*");
					}, 1000);
				}, true)

				var viewport = document.getElementsByClassName('flex-viewport')[0];
				console.log(viewport.style);
				viewport.appendChild(iframe);

				jQuery( ".variations_form" ).on( "woocommerce_variation_select_change", function () {
						// alert('woocommerce_variation_select_change');
					// Fires whenever variation selects are changed
					// console.log("KK", iframe)
				} );

				jQuery( ".single_variation_wrap" ).on( "show_variation", function ( event, variation ) {
					// Fired when the user selects all the required dropdowns / attributes
					// and a final variation is selected / shown
					console.log("Loading size", variation.attributes.attribute_pa_size)
					iframe.contentWindow.postMessage({action: "CHANGE_GARMENT", payload: { id: '<?php echo esc_html($productID); ?>', size: variation.attributes.attribute_pa_size, colorway: '', scale: 1 }}, "*");
				} );	

				// setTimeout(function() {viewport.appendChild(iframe);}, 1000)
				
			});		

		}, 1000)
	});

	</script>
<?php

