<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://syllogic.in
 * @since      1.0.0
 *
 * @package    Wpcrm_Customer_Area
 * @subpackage Wpcrm_Customer_Area/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wpcrm_Customer_Area
 * @subpackage Wpcrm_Customer_Area/includes
 * @author     Aurovrata V. <vrata@syllogic.in>
 */
class Wpcrm_Customer_Area {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wpcrm_Customer_Area_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'wpcrm-customer-area';
		$this->version = '1.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wpcrm_Customer_Area_Loader. Orchestrates the hooks of the plugin.
	 * - Wpcrm_Customer_Area_i18n. Defines internationalization functionality.
	 * - Wpcrm_Customer_Area_Admin. Defines all hooks for the admin area.
	 * - Wpcrm_Customer_Area_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpcrm-customer-area-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wpcrm-customer-area-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wpcrm-customer-area-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wpcrm-customer-area-public.php';

		$this->loader = new Wpcrm_Customer_Area_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wpcrm_Customer_Area_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wpcrm_Customer_Area_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wpcrm_Customer_Area_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
    //TODO check if this option is enabled in the settings
    //assign a user account to all contacts
    $this->loader->add_filter( 'wp_insert_post_data', $plugin_admin,'remove_user_from_private_page', 30, 2 );
    $this->loader->add_action( 'save_post_wpcrm-contact', $plugin_admin,'create_user_for_contact', 30, 2 );
    //check wpcrm project creation
    $this->loader->add_action( 'save_post_wpcrm-project', $plugin_admin,'validate_project', 30, 2 );

    //add the user to the contact table
    $this->loader->add_filter('manage_wpcrm-contact_posts_columns', $plugin_admin,'wpcrm_contact_column' );
    $this->loader->add_action('manage_wpcrm-contact_posts_custom_column', $plugin_admin,'wpcrm_contact_column_value',10, 2);
    //add custom validation scripts to the contact edit page
    //$this->loader->add_action('admin_footer', $plugin_admin,'contact_cpt_scripts');
    //DEBUG
    //$this->loader->add_action('shutdown', $plugin_admin, 'debug_hooks');
    $this->loader->add_filter('admin_notices',  $plugin_admin,'contact_email_validation');
    $this->loader->add_filter('admin_notices',  $plugin_admin,'wpcrm_contact_no_company');
    $this->loader->add_filter('admin_notices',  $plugin_admin,'wpcrm_project_no_company');
    //link projects to private files
    $this->loader->add_action( 'add_meta_boxes', $plugin_admin,'register_private_file_meta_boxes');
    $this->loader->add_action( 'save_post_cuar_private_file', $plugin_admin, 'save_private_file_project',20,1 );
    $this->loader->add_filter('default_hidden_meta_boxes', $plugin_admin,'hide_private_file_user_meta_box',20,2);
    $this->loader->add_action('admin_init', $plugin_admin,'remove_private_file_editor');
    //customer navigation menu metabox
    $this->loader->add_action('admin_init', $plugin_admin, 'add_menu_meta_box');
    $this->loader->add_filter('wp_setup_nav_menu_item',  $plugin_admin, 'setup_item', 10, 1);
    $this->loader->add_action('wp_ajax_ajax_cuar_nav', $plugin_admin, 'ajax_cuar_nav');

    //modify WP CRM Project post type
    $this->loader->add_action('init', $plugin_admin, 'modify_wpcrm_post_type' ,30);


	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wpcrm_Customer_Area_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

    //loading private pages in frontend menus
    $this->loader->add_filter('walker_nav_menu_start_el', $plugin_public, 'start_el', 10, 2);
    $this->loader->add_filter('after_setup_theme', $plugin_public, 'remove_cuar_styling');
    //enable plugin folder template for customer area plugin
    $this->loader->add_filter('cuar/ui/template-directories', $plugin_public, 'customer_area_template_dir');
    $this->loader->add_action( 'init', $plugin_public, 'remove_page_comment' ,100);

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wpcrm_Customer_Area_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
