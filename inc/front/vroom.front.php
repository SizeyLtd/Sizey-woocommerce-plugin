<?php
/**
 *
 * Handles the admin functionality.
 *
 * @package WordPress
 * @subpackage Embed Videos For Product Image Gallery Using WooCommerce
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) {
exit;
}

/**
 * Embed Videos To Product Image Gallery WooCommerce styles and scripts.
 */
add_action( 'wp_head', 'vroom_woo_scripts_styles' );
function vroom_woo_scripts_styles() {
	$enable_lightbox = get_option( 'woocommerce_enable_lightbox' );
}

/**
 * Remove Gallery Thumbnail Images
 */
add_action( 'template_redirect', 'vroom_remove_gallery_thumbnail_images' );
function vroom_remove_gallery_thumbnail_images() {
	if ( is_product() ) {
		remove_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );
	}
}

/**
 * Add new html layout of single product thumbnails
 */
add_action( 'woocommerce_product_thumbnails', 'vroom_woo_display_embed_avatar', 20 );
function vroom_woo_display_embed_avatar( $html ) {
	global $woocommerce;
	global $product;
	$product_id = get_the_ID();
	$sizey_id = get_post_meta($product_id, 'sizey-chart-id', true);
	$product_thum_id = get_post_meta( $product_id, '_thumbnail_id', true );
	$sizey_garment_id =  get_post_meta( $product_id, 'sizey-garment-id', true );
	$attachment_ids = array();
	$attachment_ids = $product->get_gallery_image_ids();
	if ( !empty($sizey_garment_id)) {
		get_post_meta($product_id, 'sizey_gallery_image', true);
		$attachment_ids[] = get_post_meta($product_id, 'sizey_gallery_image', true);
		$avatars = ( get_post_meta($product_id, 'avatars', true) ) ? get_post_meta($product_id, 'avatars', true) : 'sizey_male_mid_normal_46';
		$avatar_poses = ( get_post_meta($product_id, 'avatars_poses', true) ) ? get_post_meta($product_id, 'avatars_poses', true) : 'Adam_m_Arms Down 2';
		?>
<script>
	jQuery(window).load(function(){
			jQuery( 'a.woocommerce-product-gallery__trigger' ).hide();
		});
	var loaded = false;
	const changeAvatarAction = (id) => ({
			action: "CHANGE_AVATAR",
			payload: { id },
		});
		const changeGarmentAction = (id, avatar) => ({
		action: "CHANGE_GARMENT",
		payload: {id, avatar}
	  });
jQuery(window).on('load', function () {

	let modelid = sessionStorage.getItem('model-id');

	 jQuery(".woocommerce-product-gallery__image").click(()=>{

		setTimeout(()=> {
			if(modelid) {
				document.getElementById("vroom_iframe1").contentWindow.postMessage(
					changeAvatarAction(
						modelid
					),
					"*"
				);
			}
			document.getElementById("vroom_iframe1").contentWindow.postMessage(
				changeGarmentAction(
					"<?php echo esc_html($sizey_garment_id); ?>",
					modelid
				),
				"*"
			);
		},3000);
		jQuery('button.pswp__button--share').hide();
		jQuery('button.pswp__button--fs').show();
	});

jQuery("ol li img").last().attr('id', "iframeliid");

function loadModelinIframe() {
	if(!loaded) {
		let model_id = sessionStorage.getItem('model-id');
		if(!model_id) {
			model_id = 'f_sz_mid_n38';
		}
			document.getElementById("vroom_iframe").contentWindow.postMessage(
				changeAvatarAction(
					model_id,

				),
				"*"
			);
			document.getElementById("vroom_iframe").contentWindow.postMessage(
				changeGarmentAction(
					"<?php echo esc_html($sizey_garment_id); ?>",
					model_id
				),
				"*"
			);


			loaded = true;
		}

}

document.getElementById('iframeliid').onclick = loadModelinIframe;
document.getElementById('iframeliid').ontouchstart= loadModelinIframe;
//document.getElementById('iframeliid').addEventListener('click', loadModelinIframe)
//document.getElementById('iframeliid').addEventListener('touchstart', loadModelinIframe);
});

</script>
		<style>
			.woocommerce-product-gallery__image {
				position:relative !important;
			}
			button.pswp__button--share {
				display:none !important;
			}
		</style>
			<?php
	}

	$enable_lightbox = get_option( 'woocommerce_enable_lightbox' );
	if ( $attachment_ids ) {
		$newhtml = '';
		$loop       = 0;
		$total_attachments = count($attachment_ids);
		$columns    = apply_filters( 'woocommerce_product_thumbnails_columns', 3 );
		foreach ( $attachment_ids as $attachment_id ) {
			$newhtml .= '<div data-thumb="' . wp_get_attachment_image_src( $attachment_id, 'thumbnail' )[0] . '" class="woocommerce-product-gallery__image" >';
			$classes = array( 'zoom' );
			if ( 0 == $loop  || 0 == $loop % $columns ) {
				$classes[] = 'first';
			}
			if ( 0 == ( ( $loop + 1 ) % $columns ) ) {
				$classes[] = 'last';
			}
			$image_link = wp_get_attachment_url( $attachment_id );
			if ( ! $image_link ) {
				continue;
			}
			$video_link = '';
			$full_size_image = wp_get_attachment_image_src( $attachment_id, 'full' );


			$attributes      = array(
				'title'                   => get_post_field( 'post_title', $attachment_id ),
				'data-caption'            => get_post_field( 'post_excerpt', $attachment_id ),
				'data-src'                => $full_size_image[0],
				'data-large_image'        => $full_size_image[0],
				'data-large_image_width'  => $full_size_image[1],
				'data-large_image_height' => $full_size_image[2],
			);


			$image = wp_get_attachment_image( $attachment_id, 'woocommerce_single', false, $attributes );
			$image_class = esc_attr( implode( ' ', $classes ) );
			$image_title = esc_attr( get_the_title( $attachment_id ) );

			if ( !empty( $sizey_garment_id ) && ( $loop+1 === $total_attachments )) {
				$video_link = 'https://vroom.sizey.ai';
			}
			$video = '';
			if ( !empty( $video_link ) ) {

				$new_cart = WC()->session->get('new_cart');
				if ( is_null($new_cart) ) {
					WC()->session->set('new_cart', time());
				}
				$session_data = WC()->session->get($new_cart);

				if (get_option('sizey-button-type') === 'button') {
					$sizeypopupclass = ' class=button ';
				} else {
					$sizeypopupclass =' ';
				}

					$sizey_chart_specific_json = urlencode(get_sizey_specific_data($sizey_id));

					$sizey_css = esc_html(get_option('sizey-css'));
					$sizeypopupclass = esc_html($sizeypopupclass);
					$vroom_sizey_api_key = esc_html( get_option('vroom-sizey-api-key'));
					$product_id= esc_html($product_id);
					$button_name = esc_html(get_option('sizey-button'));
					$anchorData = "<button id=&quot;SizeyVroomButton&quot;
	                style=&quot;$sizey_css position:absolute; right: 30px; top: 50px;&quot;
	                target=&quot;popup&quot; $sizeypopupclass
                    onclick=&quot;openSizeyVroomPopup(&apos;$vroom_sizey_api_key&apos;, &apos;$product_id&apos;,&apos;$sizey_garment_id&apos;,&apos;$sizey_chart_specific_json&apos;)&quot;>$button_name</button>";

					$newhtml .= '<a href="#" data-type="video" rel="prettyPhoto[product-gallery]" data-type="video" data-video="<div class=&quot;wrapper&quot;><div style=&quot;position:relative;&quot;  class=&quot;video-wrapper&quot;>' . $anchorData . '<iframe width=&quot;1000&quot; height=&quot;640&quot; src=&quot;' . esc_url ( $video_link ) . '&quot; frameborder=&quot;0&quot; allowfullscreen=&quot;true&quot; webkitallowfullscreen=&quot;true&quot; mozallowfullscreen=&quot;true&quot; id=&quot;vroom_iframe1&quot; ></iframe></div></div>" ><i class="fas fa-expand-arrows-alt fa-2x"  aria-hidden="true" style="top:10px; position:absolute; right:10px;"></i><iframe id="vroom_iframe"   class="woo-iframelist" width="" height="" src="' . esc_url( $video_link ) . '" frameborder="0" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true" scrolling="no" ></iframe></a>';
			} else {
				$link = ( empty($video_link) ) ? $image_link : $video_link;

				$newhtml .= '<a href="' . esc_url( $link) . '" class="' . $image_class . '" title="' . sanitize_title( $image_title ) . '" rel="prettyPhoto[product-gallery]" data-type="image"  ><i class="fas fa-expand-arrows-alt fa-2x" aria-hidden="true" style="top:10px; position:absolute; right:10px;"></i>' . $image . ' </a>';
			}

			$loop++;
			$newhtml .= '</div>';
		}

		echo $newhtml;
		//echo wp_kses(  $newhtml,  $allowed_html );
	}
}
