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
add_action( 'wp_head', 'wcevzw_woo_scripts_styles' );
function wcevzw_woo_scripts_styles() {
	$enable_lightbox = get_option( 'woocommerce_enable_lightbox' );
}

/**
 * Remove Gallery Thumbnail Images
 */
add_action( 'template_redirect', 'wcevzw_remove_gallery_thumbnail_images' );
function wcevzw_remove_gallery_thumbnail_images() {
	if ( is_product() ) {
		remove_action( 'woocommerce_product_thumbnails', 'woocommerce_show_product_thumbnails', 20 );
	}
}

/**
 * Add new html layout of single product thumbnails
 */
add_action( 'woocommerce_product_thumbnails', 'wcevzw_woo_display_embed_video', 20 );
function wcevzw_woo_display_embed_video( $html ) {
	?>
<script type="text/javascript">
		jQuery(window).load(function(){
			jQuery( '.woocommerce-product-gallery .flex-viewport' ).prepend( '<div class="emoji-search-icon"></div>' );
			jQuery( 'a.woocommerce-product-gallery__trigger' ).hide();
		});
</script>
	<?php
	global $woocommerce;
	global $product;
	$product_id = get_the_ID();
	$product_thum_id = get_post_meta( $product_id, '_thumbnail_id', true );
	$sizey_garment_id =  get_post_meta( $product_id, 'sizey-garment-id', true );
	$sizey_garment_id = 'FFf423cKeuDETHcaXSWE';

	$attachment_ids = $product->get_gallery_image_ids();

			if ( !empty($sizey_garment_id)) {
				$attachment_ids[] = get_option('vroom-gallery-image'); 
				?>
<script>

const changeAvatarAction = (id, pose) => ({
			action: "CHANGE_AVATAR",
			payload: { id, pose },
		});
		const changeGarmentAction = (id, avatar) => ({
		action: "CHANGE_GARMENT",
		payload: {id, avatar}
	  });
jQuery(window).on('load', function () {
	document.getElementById("vroom_iframe").contentWindow.postMessage(
		changeAvatarAction(
			sessionStorage.getItem('model-id'),
			"Adam_m_Arms Down 2"
		),
		"*"
	);
	document.getElementById("vroom_iframe").contentWindow.postMessage(
		changeGarmentAction(
			"<?php echo esc_html($sizey_garment_id); ?>",
			sessionStorage.getItem('model-id')
		),
		"*"
	);
});


jQuery(".woocommerce-product-gallery__image").click(()=>{
	setTimeout(()=> {
	document.getElementById("vroom_iframe1").contentWindow.postMessage(
   changeAvatarAction(
		sessionStorage.getItem('model-id'),
		"Adam_m_Arms Down 2"
	),
	"*"
);
document.getElementById("vroom_iframe1").contentWindow.postMessage(
	changeGarmentAction(
		"<?php echo esc_html($sizey_garment_id); ?>",
		sessionStorage.getItem('model-id')
	),
	"*"
);
	},3000);
});

</script>
			<?php 
			}

	$enable_lightbox = get_option( 'woocommerce_enable_lightbox' );
			if ( $attachment_ids ) {
				$newhtml = '';
				$loop       = 0;
				$total_attachments = count($attachment_ids);
				$columns    = apply_filters( 'woocommerce_product_thumbnails_columns', 3 );
				foreach ( $attachment_ids as $attachment_id ) {
					$newhtml .= '<div data-thumb="' . wp_get_attachment_url( $attachment_id ) . '" class="woocommerce-product-gallery__image" >';
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
						$newhtml .= '<a href="#" data-type="video" data-video="<div class=&quot;wrapper&quot;><div  class=&quot;video-wrapper&quot;><iframe width=&quot;1000&quot; height=&quot;640&quot; src=&quot;' . esc_url ( $video_link ) . '&quot; frameborder=&quot;0&quot; allowfullscreen=&quot;true&quot; webkitallowfullscreen=&quot;true&quot; mozallowfullscreen=&quot;true&quot; id=&quot;vroom_iframe1&quot; ></iframe></div></div>" ><iframe id="vroom_iframe"   class="woo-iframelist" width="" height="" src="' . esc_url( $video_link ) . '" frameborder="0" allowfullscreen="true" webkitallowfullscreen="true" mozallowfullscreen="true" scrolling="no" ></iframe></a>';
					} else {
						$link = ( empty($video_link) ) ? $image_link : $video_link;

						$newhtml .= '<a href="' . esc_url( $link) . '" class="' . $image_class . '" title="' . sanitize_title( $image_title ) . '" rel="prettyPhoto[product-gallery]" data-type="image"  >' . $image . ' </a>';
					}

					$loop++;
					$newhtml .= '</div>';
				}
				$allowed_html = array(
					'div'      => array(
						'data-thumb'  => array(),
						'class' => array(),
						'data-thumb-alt' => array(),
						'style' =>array(),
						'data-size' => array(),

					),
					'a'     => array(
						'href' => array(),
						'data-type' => array(),
						'data-video' => array(),
						'class' =>array(),
						'title' => array(),
                        'test' => array()
					),
					'iframe'     => array(
						'id' => array(),
						'width' => array(),
						'height' => array(),
						'class' =>array(),
						'src' => array(),
						'frameborder' => array(),
						'allowfullscreen' => array(),
						'webkitallowfullscreen' => array(),
						'mozallowfullscreen' => array(),
						'scrolling' => array(),
                        '&quot;' =>array()
					)
				);
				echo $newhtml;
				//echo wp_kses(  $newhtml,  $allowed_html );
			}
}
