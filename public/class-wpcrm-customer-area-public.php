<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://syllogic.in
 * @since      1.0.0
 *
 * @package    Wpcrm_Customer_Area
 * @subpackage Wpcrm_Customer_Area/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpcrm_Customer_Area
 * @subpackage Wpcrm_Customer_Area/public
 * @author     Aurovrata V. <vrata@syllogic.in>
 */
class Wpcrm_Customer_Area_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		global $wp_scripts;
     // get registered script object for jquery-ui
    $ui = $wp_scripts->query('jquery-ui-core');

    // tell WordPress to load the Smoothness theme from Google CDN
    $protocol = is_ssl() ? 'https' : 'http';
    $url="$protocol://ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.min.css";
    wp_enqueue_style('jquery-ui-smoothness', $url, false, null);

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpcrm-customer-area-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wpcrm_Customer_Area_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wpcrm_Customer_Area_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpcrm-customer-area-public.js', array( 'jquery','jquery-ui-tabs' ), $this->version, false );

	}
  /**
   * Modifies the menu item display on frontend
   *
   * @param string $item_output The original html.
   * @param object $item  The menu item being displayed.
   * @return object
   */
  public function start_el($item_output, $item) {
      // if it isn't our custom object
      //debug_msg($item, "menu object, ");
      switch ($item->object){

          case ($item->object == 'cuar_nav'):
              // just process it
              if(function_exists('cuar_addon') ){
                $cpp_addon = cuar_addon('customer-private-pages'); //this will instantiate the required class
                //check if the logged in user has access to this
                  if ( $cpp_addon->is_accessible_to_current_user()){
                    //to check what this user owns, we need to call the post-owner class
                    $po_addon = cuar_addon('post-owner');
                    $current_user_id = get_current_user_id();
                    //now we can build the query
                    $args = array(
                      'post_type'      => $cpp_addon->get_friendly_post_type(),
                      'posts_per_page' => $cpp_addon->get_max_item_number_on_dashboard(),
                      'orderby'        => 'date',
                      'order'          => 'DESC',
                      'meta_query'     => $po_addon->get_meta_query_post_owned_by($current_user_id)
                    );
                    $content_query = new WP_Query($args);
                    $item_output ='';
                    if ($content_query->have_posts()){
                      while ($content_query->have_posts()) {
                        $content_query->the_post();
                        $item_output .='<a href="'.get_the_permalink().'">'.get_the_title();
                        $item_output .='</a>';
                      }
                    }else{
                      $item_output .='<span>'.apply_filters('wpcrm_cuar_no_page_nav_title', __('No pages found',$this->plugin_name)).'</span>';
                    }
                  }else{
                    $item_output .='<span>'.apply_filters('wpcrm_cuar_no_access_nav_title', __('Insufficient access',$this->plugin_name)).'</span>';
                  }
              }else{
                $item_output .='<span>'.apply_filters('wpcrm_cuar_error_nav_title', __('Error loading page',$this->plugin_name)).'</span>';
              }

              break;
      }

      return $item_output;
  }
  /**
   *
   *
   * @since 1.0.0
   * @param      string    $p1     .
   * @return     string    $p2     .
  **/
  public function remove_cuar_styling(){
    add_theme_support( 'customer-area.stylesheet' );
  }

}
