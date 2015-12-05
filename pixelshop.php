<?php
/*
Plugin Name: Pixelshop Integration
Plugin URI: http://pixelshop.io
Description: Wordpress plugin for pixelshop.io Integration.
Version: 1.0.0
Author: Pixelshop
Author URI: http://pixelshop.io/
License: GPL
Copyright: Pixelshop.io
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if( !class_exists('pixelshop') ):

class pixelshop
{
	// vars
	var $settings;
		
	
	/*
	*  Constructor
	*
	*  This function will construct all the neccessary actions, filters and functions for the Pixelshop plugin to work
	*
	*  @type	function
	*  @date	03/12/2015
	*  @since	1.0.0
	*
	*  @param	N/A
	*  @return	N/A
	*/
	
	function __construct()
	{
		// helpers
		add_filter('pixelshop/helpers/get_path', array($this, 'helpers_get_path'), 1, 1);
		add_filter('pixelshop/helpers/get_dir', array($this, 'helpers_get_dir'), 1, 1);

		// vars
		$this->settings = array(
			'path'				=> apply_filters('pixelshop/helpers/get_path', __FILE__),
			'dir'				=> apply_filters('pixelshop/helpers/get_dir', __FILE__),
			'hook'				=> basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ),
			'version'			=> '1.0.0',
			// 'upgrade_version'	=> '3.4.1',
		);
		
		
		// set lang local
		load_textdomain('pixelshop', $this->settings['path'] . 'lang/pixelshop-' . get_locale() . '.mo');
		
		// actions
		add_action('init', array($this, 'init'), 1);

		// filters
		add_filter('pixelshop/get_info', array($this, 'get_info'), 1, 1);
		
		// includes
		$this->include_before_theme();
		add_action('after_setup_theme', array($this, 'include_after_theme'), 1);
	}
	

	/*
	*  helpers_get_path
	*
	*  This function will calculate the path to a file
	*
	*  @type	function
	*  @date	03/12/2015
	*  @since	1.0.0
	*
	*  @param	$file (file) a reference to the file
	*  @return	(string)
	*/
    
    function helpers_get_path( $file )
    {
        return trailingslashit(dirname($file));
    }


    /*
	*  helpers_get_dir
	*
	*  This function will calculate the directory (URL) to a file
	*
	*  @type	function
	*  @date	03/12/2015
	*  @since	1.0.0
	*
	*  @param	$file (file) a reference to the file
	*  @return	(string)
	*/
    
    function helpers_get_dir( $file )
    {
        $dir = trailingslashit(dirname($file));
        $count = 0;
        
        
        // sanitize for Win32 installs
        $dir = str_replace('\\' ,'/', $dir); 
        
        
        // if file is in plugins folder
        $wp_plugin_dir = str_replace('\\' ,'/', WP_PLUGIN_DIR); 
        $dir = str_replace($wp_plugin_dir, plugins_url(), $dir, $count);
        
        
        if( $count < 1 )
        {
	        // if file is in wp-content folder
	        $wp_content_dir = str_replace('\\' ,'/', WP_CONTENT_DIR); 
	        $dir = str_replace($wp_content_dir, content_url(), $dir, $count);
        }
        
        
        if( $count < 1 )
        {
	        // if file is in ??? folder
	        $wp_dir = str_replace('\\' ,'/', ABSPATH); 
	        $dir = str_replace($wp_dir, site_url('/'), $dir);
        }
        

        return $dir;
    }


	/*
	*  get_info
	*
	*  This function will return a setting from the settings array
	*
	*  @type	function
	*  @date	03/12/2015
	*  @since	1.0.0
	*
	*  @param	$i (string) the setting to get
	*  @return	(mixed)
	*/
	
	function get_info( $i )
	{
		// vars
		$return = false;
		
		
		// specific
		if( isset($this->settings[ $i ]) )
		{
			$return = $this->settings[ $i ];
		}
		
		
		// all
		if( $i == 'all' )
		{
			$return = $this->settings;
		}
		
		
		// return
		return $return;
	}


   	/*
	*  include_before_theme
	*
	*  This function will include core files before the theme's functions.php file has been excecuted.
	*  
	*  @type	action (plugins_loaded)
	*  @date	03/12/2015
	*  @since	1.0.0
	*
	*  @param	N/A
	*  @return	N/A
	*/
	
	function include_before_theme()
	{
		// admin only includes
		if( is_admin() )
		{
		}
	}
	
	
	/*
	*  include_after_theme
	*
	*  This function will include core files after the theme's functions.php file has been excecuted.
	*  
	*  @type	action (after_setup_theme)
	*  @date	03/12/2015
	*  @since	1.0.0
	*
	*  @param	N/A
	*  @return	N/A
	*/
	
	function include_after_theme() {
		
		// admin only includes
		if( is_admin() )
		{
			include_once('core/controllers/export.php');
		}
		
	}
	
	
	/*
	*  init
	*
	*  This function is called during the 'init' action and will do things such as:
	*  create post_type, register scripts, add actions / filters
	*
	*  @type	action (init)
	*  @date	03/12/2015
	*  @since	1.0.0
	*
	*  @param	N/A
	*  @return	N/A
	*/
	
	function init()
	{
		// register pixelshop styles
		$styles = array(
			'pixelshop'				=> $this->settings['dir'] . 'css/pixelshop.css',
		);
		
		foreach( $styles as $k => $v )
		{
			wp_register_style( $k, $v, false, $this->settings['version'] );
		}

		// admin only
		if( is_admin() )
		{
			$this->post_save_api();
			
			add_action('admin_menu', array($this,'admin_menu'));

			wp_enqueue_style(array('pixelshop'));
		}
	}

	/*
	*  post_save_api
	*
	*  This function is called during the 'init' action checking if api key posted.
	*
	*  @type	action (init)
	*  @date	03/12/2015
	*  @since	1.0.0
	*
	*  @param	N/A
	*  @return	N/A
	*/
	function post_save_api()
	{
		if( isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'api_key') )
		{
			if( $api_key === false )
			{
				add_option( 'pixelshop_key', $_POST['pixelshop_key'], '', 'yes' );
			}
			else
			{
				update_option( 'pixelshop_key', $_POST['pixelshop_key'] );
			}

			add_action( 'admin_notices', array($this, 'key_saved_notice') );
		}
	}

	/**
	 * save api key notice
	 * @return string
	 */
	function key_saved_notice()
	{
		?>
		<div class="updated">
			<p><?php _e( 'The api key has been saved.', 'pixelshop' ); ?></p>
		</div>
		<?php
	}

	/**
	 * save api key notice
	 * @return string
	 */
	function woocommerce_missing_notice()
	{
		?>
		<div class="update-nag notice">
			<p><?php _e( 'WooCommerce plugin is missing!', 'pixelshop' ); ?></p>
		</div>
		<?php
	}


	function check_woocommerce()
	{
		if( is_plugin_active('woocommerce/woocommerce.php') )
		{
			add_action( 'admin_notices', array($this, 'woocommerce_missing_notice') );
		}
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
		add_menu_page(__("Pixelshop",'pixelshop'), __("Pixelshop",'pixelshop'), 'manage_options', 'pixelshop', array($this, 'html'), false, '90');
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
		$api_key = get_option('pixelshop_key');
		?>
<div class="wrap">
	<h2 style="margin: 4px 0 25px;"><?php _e("Pixelshop Integration",'pixelshop'); ?></h2>

	<form method="post" class="pixelshop-box">
		<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'api_key' ); ?>" />
		<div class="title">
			<h3><?php _e("API Key",'pixelshop'); ?></h3>
		</div>
		<div class="pixelshop-content" id="post-body-content">
			<div id="titlediv">
				<div id="titlewrap">
					<?php if( $api_key === false  ) { ?>
						<input type="text" name="pixelshop_key" size="64" value="" id="pixelshop_key" spellcheck="true" placeholder="<?php _e("Enter API Key",'pixelshop'); ?>">
					<?php } else { ?>
						<input type="text" name="pixelshop_key" size="64" value="<?php echo $api_key; ?>" id="title">
					<?php } ?>
				</div>
			</div>
			<div class="submit-post">
				<input type="submit" name="save_key" id="save_key" class="button button-primary button-large" value="<?php _e("Save Key",'pixelshop'); ?>">
			</div>
		</div>

		<div class="clearfix"></div>
	</form>
</div>
		<?php
		return;
	}
}


/*
*  pixelshop
*
*  The main function responsible for returning the one true pixelshop Instance to functions everywhere.
*  Use this function like you would a global variable, except without needing to declare the global.
*
*  Example: <?php $pixelshop = pixelshop(); ?>
*
*  @type	function
*  @date	03/12/2015
*  @since	1.0.0
*
*  @param	N/A
*  @return	(object)
*/

function pixelshop()
{
	global $pixelshop;
	
	if( !isset($pixelshop) )
	{
		$pixelshop = new pixelshop();
	}
	
	return $pixelshop;
}


// initialize
pixelshop();

endif; // class_exists check
?>