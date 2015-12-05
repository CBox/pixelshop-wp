<?php 

/*
*  pixelshop_export
*
*  @description: controller for export sub menu page
*  @since: 1.0.0
*  @created: 03/12/2015
*/

class pixelshop_export
{
	
	var $action;	
	
	/*
	*  __construct
	*
	*  @description: 
	*  @since 1.0.0
	*  @created: 03/12/2015
	*/
	
	function __construct()
	{
		// actions
		add_action('admin_menu', array($this,'admin_menu'), 11, 0);

		add_action('init', array($this, 'export_products'));
	}
	
	
	/*
	*  admin_menu
	*
	*  @description: 
	*  @since 1.0.0
	*  @created: 03/12/2015
	*/
	
	function admin_menu()
	{
		// add page
		$page = add_submenu_page('pixelshop', __('Export','pixelshop'), __('Export','pixelshop'), 'manage_options', 'pixelshop-export', array($this, 'html'));
	}

	/**
	 * api key invalid
	 * @return string
	 */
	function api_key_invalid()
	{
		?>
		<div class="error notice">
			<p><?php _e( 'API Key is invalid or not exists.', 'pixelshop' ); ?></p>
		</div>
		<?php
	}

	/*
	*  export_producs
	*
	*  @description: start export products
	*  @since 1.0.0
	*  @created: 03/12/2015
	*/

	function export_products()
	{
		$api_key = get_option('pixelshop_key');

		$path = apply_filters('pixelshop/get_info', 'path');
		
		include_once($path . 'core/api.php');

		if( isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'export_products') && $api_key !== false )
		{
			$api = new API($api_key);

			$args = array(
				'post_type' 		=> 'product',
				'orderby' 			=> $orderby,
				'post_status'		=> 'publish',
				'posts_per_page'	=> -1,
			);

			$the_query = new WP_Query($args);
			$products = [];

			$i = 0;
			while ( $the_query->have_posts() )
			{
				$i++;

				$the_query->the_post();

				$product = new WC_Product( $the_query->post->ID );

				$thumb = wp_get_attachment_image_src( get_post_thumbnail_id($the_query->post->ID), array(250, 250) );

				$products[$i] = [
					"sync_id"		=> $the_query->post->ID,
					"link"			=> get_permalink($the_query->post->ID),
					"title"			=> get_the_title(),
					"description"	=> get_the_excerpt(),
					"price"			=> $product->get_price(),
					"sku"			=> $product->get_sku(),
					"thumb"			=> $thumb[0],
					"tags"			=> '',
				];

				if ( ! isset($product->get_tags()->errors) )
					$products[$i]["tags"] = $product->get_tags();
			}

			$export = $api->export->products($products);

			if( isset($export['error']) )
			{
				add_action( 'admin_notices', array($this, 'api_key_invalid') );
			}			
			else
			{
				add_option( 'pixelshop_message', $export, '', 'yes' );

				$time = 60*60*24;

				if( get_option('pxs_last_export') === false )
					add_option( 'pxs_last_export', time() + $time, '', 'no' );
				else
					update_option( 'pxs_last_export', time() + $time );
			}
		}
	}
	
	/*
	*  html
	*
	*  @description: 
	*  @since 1.0.0
	*  @created: 03/12/2015
	*/
	
	function html()
	{
		$dir = apply_filters('pixelshop/get_info', 'dir');
		$api_key = get_option('pixelshop_key');
		$message = get_option('pixelshop_message');
		$last_export = get_option('pxs_last_export');
		?>
<div class="wrap">
	<h2 style="margin: 4px 0 25px;"><?php _e("Pixelshop Export Products",'pixelshop'); ?></h2>
	<form method="post">
		<div class="wp-box">
			<div class="pixelshop-export">
				<img src="<?php echo $dir; ?>images/export-products.png" width="220" height="auto" alt="">
				<p>
					<?php _e("We will export all your products to <b>Pixelshop.io</b> so you will be able to connect products to social posts, let's start it's a going to be a click of a button.", 'pixelshop'); ?>
				</p>
				<?php if( $api_key !== false && is_plugin_active('woocommerce/woocommerce.php') && $message === false && time() > $last_export ) { ?> 
					<form method="post" id="export-products">
						<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'export_products' ); ?>" />
						<input type="hidden" name="api_key" value="<?php echo $api_key; ?>" />
						<input type="submit" name="export_products" class="submit" value="<?php _e('Click To Export Products', 'pixelshop'); ?>">
					</form>
				<?php } else if(! is_plugin_active('woocommerce/woocommerce.php') ) { ?>
					<div class="pixelshop-error"><?php _e("WooCommerce not installed or not active, Please retry again later.", 'pixelshop'); ?></div>
				<?php } else if( $api_key === false )  { ?>
					<div class="pixelshop-error"><?php _e('You have got the add api key before export', 'pixelshop'); ?></div>
				<?php } else if( $message !== false ) { $count = intval($message['success']); ?>
					<div class="pixelshop-success"><?php printf( esc_html__( "It's Done! We have imported %d products successfully.", 'pixelshop' ), $count ); ?></div>
					<?php if( $message['exists'] > 0 ) { $count = intval($message['exists']); ?>
						<div class="pixelshop-error"><?php printf( esc_html__( "There is %d products exists.", 'pixelshop' ), $count ); ?></div>
					<?php } ?>
				<?php } else if( time() < $last_export ) { ?>
					<div class="pixelshop-error"><?php _("You can export all your products each 24 hours<br /> Upgrade for full sync between WooCommerce to Pixelshop", 'pixelshop'); ?></div>
				<?php } ?>
			</div>
		</div>
	</form>
</div>
<script type="text/javascript">
	jQuery(document).ready(function( $ ) {
		$('form').submit(function(){
			$(this).find(':submit').attr('disabled','disabled').val('<?php _e("Process...", "pixelshop"); ?>');
		});
	});
</script>
		<?php
		delete_option('pixelshop_message');
		return;
	}
}

new pixelshop_export();
?>