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
    //wp_enqueue_style( string $handle, string $src, array $deps, string|bool|null $ver, string $media )
    wp_enqueue_style('jquery-ui-smoothness', $url, array() ,$ui->ver , 'all');

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpcrm-customer-area-public.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'scrolltabs-css', plugin_dir_url( __DIR__ ) . 'assets/scrolltabs/css/scrolltabs.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpcrm-customer-area-public.js', array( 'jquery','jquery-ui-tabs','jquery-ui-accordion' ), $this->version, false );
		wp_enqueue_script( 'mousewheel-js', plugin_dir_url( __DIR__ ) . 'assets/scrolltabs/js/jquery.mousewheel.js', array( 'jquery'), $this->version, true );
		wp_enqueue_script( 'scrolltabs-js', plugin_dir_url( __DIR__ ) . 'assets/scrolltabs/js/jquery.scrolltabs.js', array( 'jquery', 'mousewheel-js'), $this->version, true );

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
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'meta_query'     => $po_addon->get_meta_query_post_owned_by($current_user_id)
              );

              $content_query = new WP_Query($args);
              $item_output ='';
              if ($content_query->have_posts()){
                while ($content_query->have_posts()) {
                  $content_query->the_post();
                  $cuar_menu_item = '<a href="'.get_the_permalink().'">'.get_the_title().'</a>';
                  $cuar_menu_item = apply_filters('wpcrm_cuar_menu_item', $cuar_menu_item, $current_user_id);
                  $item_output .= $cuar_menu_item;
                  //get the project types
                  if(!apply_filters('wpcrm_cuar_skip_project_type_sub_menus', false,  $current_user_id)){
                    $args = array(
                      'taxonomy' => 'project-type',
                      'orderby'  => 'term_id',
                      'order'    => 'ASC',
                      'hide_empty' => false);
                    $args = apply_filters('wpcrm_cuar_query_project_type_in_menu', $args, $current_user_id);
                    $terms = get_terms($args);
                    $class = esc_attr( implode( ' ', apply_filters( 'wpcrm_cuar_project_type_nav_menu_css', array_filter( $item->classes ), $item, $current_user_id) ) );
                    $sub_class = esc_attr( implode( ' ', apply_filters( 'wpcrm_cuar_project_type_sub_menu_css', array('sub-menu'), $current_user_id) ) );
                    if(!empty($terms)){
                      $item_output .='<ul class="'.$sub_class.'">';
                      foreach($terms as $term){
                        if(!apply_filters('wpcrm_cuar_skip_project_type_in_menu', false, $term, $current_user_id)){
                          $item_output .='<li class="'.$class.' project-type '.$term->slug.'">';
                          $item_output .='<a href="'.get_the_permalink().'?type='.$term->term_id.'">'.$term->name.'</a>';
                          $item_output .='<div class="menu-item-description">'.$term->description.'</div>';
                          $item_output .='</li>';
                        }
                      }
                      $item_output .='</ul>';
                    }
                  }
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
   * Funciton to disable the Customer Area css styling
   * This functionality can be switched off by return false on the following filter 'wpcrm_cuar_disable_stylesheet'
   * @since 1.0.0
  **/
  public function remove_cuar_styling(){
    $disable = apply_filters('wpcrm_cuar_disable_stylesheet',true);
    if($disable) add_theme_support( 'customer-area.stylesheet' );
  }
  /**
   * Function to add Cusomter Area template path
   * this function hooks the 'cuar/ui/template-directories'
   * @since 1.0.0
   * @param      Array    $possible_paths    an array of template paths .
   * @return     Array    an array with the additional path to look for template     .
  **/
  public function customer_area_template_dir($possible_paths){
    //insert the plugin folder in the 2nd but last position, this way themese can still override
    array_splice($possible_paths, -1, 0, untrailingslashit(WP_CONTENT_DIR) . '/plugins/' . $this->plugin_name . '/public/partials/customer-area');

    return $possible_paths;
  }


  public function remove_page_comment() {
      //remove_post_type_support( 'page', 'comments' );
      remove_post_type_support( 'cuar_private_page', 'comments' );
      remove_post_type_support( 'cuar_private_file', 'comments' );

  }
}
