<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://syllogic.in
 * @since      1.0.0
 *
 * @package    Wpcrm_Customer_Area
 * @subpackage Wpcrm_Customer_Area/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wpcrm_Customer_Area
 * @subpackage Wpcrm_Customer_Area/admin
 * @author     Aurovrata V. <vrata@syllogic.in>
 */
class Wpcrm_Customer_Area_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wpcrm-customer-area-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpcrm-customer-area-admin.js', array( 'jquery' ), $this->version, false );

	}
  /**
   * Assigns a user to WP-CRM cpt contact
   * This function hooks the 'save_post' action which is fired after the post of saved.
   * @since 1.0.0
   * @param      int    $ID     the id of the post being saved.
   * @return     WP_Object    $post     the post being saved.
   */
  public function create_user_for_contact($ID, $post){
    //if('wpcrm-contact'!= $post->post_type) return;
    //debug_msg($_POST," POST ");
    //debug_msg($post, " post object ");
    if('publish' == $post->post_status){
      //check if we have an email
      if(isset($_POST['_wpcrm_contact-email']) && !empty($_POST['_wpcrm_contact-email']) ){
        $email = $_POST['_wpcrm_contact-email'];
        //check if the user exists
        $user = get_user_by( 'email', $email );
        debug_msg($user, " for ".$email);
        if(empty($user)){
          //create a new user
          $username = 'hex'.$ID;
          if ( username_exists( $username ) ){
            $user = get_user_by( 'login', $username );
            wp_delete_user( $user->ID, 1 );
          }
          $password = wp_generate_password( $length=12, $include_standard_special_chars=false );
          $user_id = wp_create_user( $username, $password, $email );
          update_post_meta($ID, '_wpcrm_contact-user_id', $user_id);
        }else{
          update_post_meta($ID, '_wpcrm_contact-user_id', $user->ID);
        }
        debug_msg('Assigned new user '.$user->ID.' to contact '.$email);
      }else{
        // unhook this function so it doesn't loop infinitely
  		  remove_action( 'save_post_wpcrm-contact', array($this,'create_user_for_contact'),10,2 );
        //save error message in a transient
        add_settings_error(
          'missing-email',
          'missing-email',
          __( 'Please enter a valid email for this contact before publishing', $this->plugin_name ),
          'error'
        );

        set_transient( 'wpcrm_contact_errors', get_settings_errors(), 30 );
    		// update the post, which calls save_post again
    		wp_update_post( array( 'ID' => $ID, 'post_status' => 'draft' ) );
    		// re-hook this function
    		add_action( 'save_post_wpcrm-contact', array($this, 'create_user_for_contact'),10,2 );
      }
      //
      // Check if the contact is attached to an oganisation
      //
      if(isset($_POST['_wpcrm_contact-attach-to-organization']) && !empty($_POST['_wpcrm_contact-attach-to-organization'])){
        //let's make sure we have a customer area page created for this organisation

      }else{
        // unhook this function so it doesn't loop infinitely
  		  remove_action( 'save_post_wpcrm-contact', array($this,'create_user_for_contact'),10,2 );
        //save error message in a transient
        add_settings_error(
          'missing-company',
          'missing-company',
          __( 'Please select an Organization for this contact person', $this->plugin_name ),
          'error'
        );

        set_transient( 'wpcrm_contact_no_company', get_settings_errors(), 30 );
    		// update the post, which calls save_post again
    		wp_update_post( array( 'ID' => $ID, 'post_status' => 'draft' ) );
    		// re-hook this function
    		add_action( 'save_post_wpcrm-contact', array($this, 'create_user_for_contact'),10,2 );
      }
    }
  }

  /**
   * Add a user column to WP-CRM contact table
   * This is called by a filter set in the 'wpcrm_contact_admin_table' in this class.
   * @since 1.0.0
   * @param      array    $cols     array of table columns.
   * @return     array    $cols     modified array of table columns.
  **/
  public function wpcrm_contact_column($cols){
    //remove author
    unset($cols['author']);
    //remove published date
    unset($cols['date']);
    //add user account
    $cols['contact-user'] = __('Login', $this->plugin_name);
    //add phone number
    $cols['contact-phone'] = __('Phone', $this->plugin_name);
    //add email
    $cols['contact-email'] = __('Email', $this->plugin_name);

    return $cols;
  }
  /**
   * Populate the user for WP-CRM contact table rows
   * This function is hooked in the 'wpcrm_contact_admin_table' in this class.
   * @since 1.0.0
   * @param      string    $col     the column for which the value is required.
   * @param      string    $post_id     the post id for the current row.
   * @return     string    $p2     .
  **/
  public function wpcrm_contact_column_value($col, $post_id){
    debug_msg($col,"populating new column...".$post_id);
    switch($col){
      case 'contact-user':
        //get the user id for the post
        $user_id = get_post_meta($post_id,'_wpcrm_contact-user_id',true);
        if(empty($user_id)) break;
        debug_msg("found user ".$user_id);
        //get the username
        $user = get_user_by( 'id', $user_id );
        echo '<a href="'.admin_url("user-edit.php?user_id=".$user_id).'">'.$user->user_login.'</a>';
        break;
      case 'contact-email':
        $email = get_post_meta($post_id,'_wpcrm_contact-email',true);
        if(empty($email)) break;
        debug_msg("found email ".$email);
        echo '<a href="mailto:'.$email.'">'.$email.'</a>';
        break;
      case 'contact-phone':
        $phone = get_post_meta($post_id,'_wpcrm_contact-phone',true);
        if(empty($phone)) break;
        echo '<a href="tel:'.$phone.'">'.$phone.'</a>';
        break;
    }
  }
  /**
   * Display an error notice if no email
   * This is hooked to the 'admin-Notices' and display a transient error msg set by the 'save_post' hooked function
   * @since 1.0.0
  **/
  public function contact_email_validation(){
    if ( ! ( $errors = get_transient( 'wpcrm_contact_errors' ) ) ) {
     return;
    }
    // Otherwise, build the list of errors that exist in the settings errores
    $message = '<div id="wpcrm-error-message" class="error is-dismissible"><p><ul>';
    foreach ( $errors as $error ) {
     $message .= '<li>' . $error['message'] . '</li>';
    }
    $message .= '</ul></p></div><!-- #error -->';
    //let's hide any success message
    $message .='<script type="text/javascript">';
    $message .='(function( $ ) {';
    $message .='	"use strict";';

    $message .='  $(document).ready(function() {';
    $message .='    $("div#message").hide();';
    $message .='});})( jQuery );</script>';
    // Write them out to the screen
    echo $message;
    // Clear and the transient and unhook any other notices so we don't see duplicate messages
    delete_transient( 'wpcrm_contact_errors' );
    remove_action( 'admin_notices', array($this,'contact_email_validation') );
  }
  /**
   * Display an error notice if no company set for contact
   * This is hooked to the 'admin-Notices' and display a transient error msg set by the 'save_post' hooked function
   * @since 1.0.0
  **/
  public function wpcrm_contact_no_company(){
    if ( ! ( $errors = get_transient( 'wpcrm_contact_no_company' ) ) ) {
     return;
    }
    // Otherwise, build the list of errors that exist in the settings errores
    $message = '<div id="wpcrm-company-error" class="error is-dismissible"><p><ul>';
    foreach ( $errors as $error ) {
     $message .= '<li>' . $error['message'] . '</li>';
    }
    $message .= '</ul></p></div><!-- #error -->';
    //let's hide any success message
    $message .='<script type="text/javascript">';
    $message .='(function( $ ) {';
    $message .='	"use strict";';

    $message .='  $(document).ready(function() {';
    $message .='    $("div#message").hide();';
    $message .='});})( jQuery );</script>';
    // Write them out to the screen
    echo $message;
    // Clear and the transient and unhook any other notices so we don't see duplicate messages
    delete_transient( 'wpcrm_contact_no_company' );
    remove_action( 'admin_notices', array($this,'wpcrm_contact_no_company') );
  }

}
