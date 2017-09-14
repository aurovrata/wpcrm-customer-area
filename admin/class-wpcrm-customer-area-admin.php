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
	public function enqueue_styles($hook) {

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
	public function enqueue_scripts($hook) {

    switch ($hook){
      case 'nav-menus.php':
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpcrm-cuar-nav.js', array( 'jquery' ), $this->version, true );
        break;
      case 'post.php':
      case 'post-new.php':
        $screen = get_current_screen();
        //check if js script exists in the theme folder
        if(file_exists(get_stylesheet_directory() . '/wpcrm-cuar/js/' . $screen->id . '.js')){
          wp_enqueue_script( $screen->id, get_stylesheet_directory_uri() . '/wpcrm-cuar/js/' . $screen->id . '.js', array( 'jquery' ), $this->version, true );
        } else {//use plugin version
          wp_enqueue_script( $screen->id, plugin_dir_url( __FILE__ ) . 'js/' . $screen->id . '.js', array( 'jquery' ), $this->version, true );
        }
        break;
      default:
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpcrm-customer-area-admin.js', array( 'jquery' ), $this->version, true );
      break;
    }

	}
  /**
   * Function to check if a user is de-mapped from a company
   * Hooked to 'wp_isert_post_data' before post is saved to db
   * @since 1.0.0
   * @param      string    $post_data     array of posted data being filtered.
   * @return     string    $raw_data     array of raw post data.
  **/
  public function remove_user_from_private_page($post_data, $raw_data){
    if( !isset($post_data['post_type']) || 'wpcrm-contact' != $post_data['post_type'] ){
      return $post_data;
    }
    $post_id = $raw_data['ID'];
    if('publish' == $post_data['post_status']){
      $org_id = $_POST['_wpcrm_contact-attach-to-organization'];
      $saved_org_id = get_post_meta($post_id, '_wpcrm_contact-attach-to-organization', true);
      if(!empty($org_id) && $org_id != $saved_org_id){
        //the copmany has been re-mapped, we need to remove user rights from old company page
        //get the user first
        $user_id = get_post_meta($post_id, '_wpcrm_contact-user_id', true);
				if(!empty($user_id)){
	        $args = array(
	            'meta_key' => 'wpcrm_organisation_id',
	            'meta_value' => $saved_org_id,
	            'post_type' => 'cuar_private_page',
	            'post_status' => 'any',
	            'posts_per_page' => -1
	        );
	        $posts = get_posts($args);
	        if ($posts){
	          $cuar_page = $posts[0];
	          if( function_exists('cuar_addon') ){
	            debug_msg($cuar_page->ID, "Found private page ");

	            $po_addon = cuar_addon('post-owner'); //this will instantiate the required class
	            $owners = $po_addon->get_post_owners($cuar_page->ID);
	            debug_msg($owners, "Removing ".$user_id." from Current owners ");
	            $owners['usr'] = array_diff($owners['usr'], array($user_id) );
	            $po_addon->save_post_owners($cuar_page->ID, $owners);
	          }
						wp_reset_postdata();
	        }
				}
      }
    }
    return $post_data;
  }
  /**
   * Assigns a user to WP-CRM cpt contact
   * This function hooks the 'save_post_wpcrm-contact' action which is fired after the post of saved.
   * @since 1.0.0
   * @param      int    $ID     the id of the post being saved.
   * @return     WP_Object    $post     the post being saved.
   */
  public function create_user_for_contact($ID, $post){

    if('publish' == $post->post_status){
      //check if we have an email
      $user='';
      if(isset($_POST['_wpcrm_contact-email']) && !empty($_POST['_wpcrm_contact-email']) ){
        $email = $_POST['_wpcrm_contact-email'];
        $first_name = $last_name = '';
        if( isset($_POST['_wpcrm_contact-first-name']) ){
          $first_name = $_POST['_wpcrm_contact-first-name'];
        }
        if( isset($_POST['_wpcrm_contact-first-name']) ){
          $last_name = $_POST['_wpcrm_contact-last-name'];
        }
        //check if the user exists
        $user = get_user_by( 'email', $email );
        //debug_msg($user, " for ".$email);
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
          $user = get_user_by( 'id', $user_id );
          update_user_meta( $user->ID, 'first_name', $first_name);
          update_user_meta( $user->ID, 'last_name', $last_name);
          update_user_meta( $user->ID, 'display_name', $first_name.' '.$last_name);
          //notify user
          wp_new_user_notification($user_id, null, 'both');
        }else{
          update_post_meta($ID, '_wpcrm_contact-user_id', $user->ID);
        }
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
        return; //no need to go any further
      }
      //
      // Check if the contact is attached to an oganisation
      //
      if(isset($_POST['_wpcrm_contact-attach-to-organization']) && !empty($_POST['_wpcrm_contact-attach-to-organization']) ){
        //let's make sure we have a customer area page created for this organisation
        $org_id= $_POST['_wpcrm_contact-attach-to-organization'];
        $args = array(
            'meta_key' => 'wpcrm_organisation_id',
            'meta_value' => $org_id,
            'post_type' => 'cuar_private_page',
            'post_status' => 'any',
            'posts_per_page' => -1
        );
        $posts = get_posts($args);
        if ($posts){
          $cuar_page = $posts[0];
          if( function_exists('cuar_addon') ){
            debug_msg($cuar_page->ID, "Found private page ");

            $po_addon = cuar_addon('post-owner'); //this will instantiate the required class
            $owners = $po_addon->get_post_owners($cuar_page->ID);

            if(!in_array($user->ID, $owners['usr'])){
              $owners['usr'][] = $user->ID;
              $po_addon->save_post_owners($cuar_page->ID, $owners);
							debug_msg($owners, "Added ".$user->ID." to Current owners ");
            }

          }
					wp_reset_postdata(); //organisation search.
					//search for private files
					$args = array(
						'post_type' => 'wpcrm-project',
						'post_status' => 'publish',
						'meta_key' => '_wpcrm_project-attach-to-organization',
						'meta_value' => $org_id
					);
					$projects = get_posts($args);
					if(!empty($projects)){
						$args = array();
						foreach($projects as $project){
							$args[] = $project->ID;
						}
						wp_reset_postdata(); //projects
						$args = array(
							'post_type' => 'cuar_private_file',
							'post_status' => 'any',
							'meta_key' => 'wpcrm-project-id',
							'meta_value' => $args,
							'meta_compare' => 'IN'
						);
						$files = get_posts($args);
						if(!empty($files)){
							if( function_exists('cuar_addon') ){
								$po_addon = cuar_addon('post-owner'); //this will instantiate the required class
								foreach($files as $file){
			            debug_msg($file->ID, "Found private file ");
			            $owners = $po_addon->get_post_owners($file->ID);

			            if(!in_array($user->ID, $owners['usr'])){
			              $owners['usr'][] = $user->ID;
			              $po_addon->save_post_owners($file->ID, $owners);
										debug_msg($owners, "Added ".$user->ID." to Current owners ");
			            }

			          }
							}
							wp_reset_postdata();
						}
					}
        }else{
          $org_post = get_post($org_id);
          //create a new page
          debug_msg("creating private page ".$org_post->post_title);
          if( function_exists('cuar_addon') ){
            $page_data = array(
              'post_title' => $org_post->post_title,
              'post_type' => 'cuar_private_page',
              'post_name' => 'private-page-'.$org_id,
              'post_status' => 'publish',
            );

            $page_id = wp_insert_post( $page_data, true );

            if( is_wp_error($page_id) ){
              debug_msg($page_id, "Error creating new private page: ");
            }else{
              //update the title to ofset the wp-crm hook
              /*global $wpdb;
      				$where = array( 'ID' => $page_id );
      				$wpdb->update( $wpdb->posts, array( 'post_title' => $org_post->post_title ), $where );
*/
              $owner = array( 'usr' => array( $user->ID ) );
              $po_addon = cuar_addon('post-owner'); //this will instantiate the required class
              //$dummyp = get_post($page_id);
              //debug_msg($dummyp, "before saving owners ");
              $po_addon->save_post_owners($page_id, $owner);


              add_post_meta($page_id, 'wpcrm_organisation_id', $org_id);
              debug_msg('Created new private page '.$page_id.' for company '.$org_post->post_title);
              $dummyp = get_post($page_id);
              debug_msg($dummyp, "after saving owners ");
            }
          }
        }
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
   * Checks the validity of a new project
   * This function hooks the 'save_post_wpcrm-project' and checks that an organisation is linked to the project.  It also links the project to the Customer Area plugin private page of the organisation with a meta field 'cuar_private_page' inserted in the wpcrm-project post
   * @since 1.0.0
   * @param      int    $ID     the id of the post being saved.
   * @return     WP_Object    $post     the post being saved.
   */
  public function validate_project($ID, $post, $update){

    if('publish' == $post->post_status){
      // Check if the project is attached to an oganisation
      if(isset($_POST['_wpcrm_project-attach-to-organization']) && !empty($_POST['_wpcrm_project-attach-to-organization']) ){
        //let's make sure we have a customer area page created for this organisation
        $org_id = $_POST['_wpcrm_project-attach-to-organization'];
        $args = array(
            'meta_key' => 'wpcrm_organisation_id',
            'meta_value' => $org_id,
            'post_type' => 'cuar_private_page',
            'post_status' => 'any',
            'posts_per_page' => -1
        );
        $posts = get_posts($args);
        if ($posts){
          $cuar_page = $posts[0];
          //add the private page to the project
          update_post_meta($ID, 'cuar_private_page', $cuar_page->ID);
        }else{
          debug_msg('Project creation: No private page linked to organisation '.$org_id);
        }
      }else{
        // unhook this function so it doesn't loop infinitely
        remove_action( 'save_post_wpcrm-project', array($this,'check_org_for_project'),10,2 );
        //save error message in a transient
        add_settings_error(
          'missing-company',
          'missing-company',
          __( 'Please select an Organisation for this project', $this->plugin_name ),
          'error'
        );

        set_transient( 'wpcrm_project_no_company', get_settings_errors(), 30 );
        // update the post, which calls save_post again
        wp_update_post( array( 'ID' => $ID, 'post_status' => 'draft' ) );
        // re-hook this function
        add_action( 'save_post_wpcrm-project', array($this, 'check_org_for_project'),10,2 );

        //notify all contacts.
        if(!$update){
          //find which organisation id it belongs to (Org_id)
          //search all wpcrm-contacts which have have meta-field organisation id = to this org_id.
          //with contacts, get emails, send notification.
        }
      }
      //check if project type is set, wp_get_post_terms( $post_id, $taxonomy, $args );
      $terms = wp_get_post_terms( $ID, 'project-type' );
      if(!is_wp_error($terms)){
        $existing_tasks_slugs = array();
        if($update){ //this is an update of this project
          $args = array(
            'meta_key' => '_wpcrm_task-attach-to-project',
            'meta_value' => $ID,
            'post_type' => 'wpcrm-task',
            'post_status' => 'any',
            'posts_per_page' => -1
          );
          $posts = get_posts($args);

      	  foreach($posts as $task){
      	    $existing_tasks_slugs[$task->post_name] = $task->ID;
      	  }
        }

        foreach($terms as $term){
          $task_slug = $term->slug.'_'.$ID.'_'.'task';
          if(!isset($existing_tasks_slugs[$task_slug])){
	          $args = array(
	            'post_type'   => 'wpcrm-task',
	            'post_author' => $post->post_author,
	            'post_title'  => $term->name.' | '.$post->post_title,
	            'post_name'   => $task_slug,
	            'post_status' => 'publish'
	          );
	          $task_id = wp_insert_post($args);
	          update_post_meta($task_id, '_wpcrm_task-attach-to-project', $ID);
      	  }else{
      	    $task_id = $existing_tasks_slugs[$task_slug];
      	  }
          if( isset($_POST['_wpcrm_project-assigned']) ){
            $assigned = $_POST['_wpcrm_project-assigned'];
            update_post_meta($task_id, '_wpcrm_task-assignment',$assigned);
          }

          if( isset($_POST['_wpcrm_project-closedate']) ){
            $due_date = $_POST['_wpcrm_project-closedate'];
            update_post_meta($task_id, '_wpcrm_task-due-date', strtotime($due_date));
          }
          update_post_meta($task_id, '_wpcrm_task-start-date', time());
        }
      }
    }
  }

  /**
   * Sends a new project creation notification email to all contacts attached to an Organization
   * This function hooks the 'added_post_meta' and sends an email to all contacts for a particular organization
   * @since 1.0.1
   * @param      int      $meta_id   the meta_id of the postmeta being saved.
   * @param      int      $post_id   the post_id of the postmeta being saved.
   * @param      array    $meta_key  the meta_key of the postmeta being saved.
   * @param      array    $meta_value the meta_value of the postmeta being saved.
   */
  public function project_creation_notification($meta_id, $post_id, $meta_key, $meta_value){
            $post = get_post($post_id);

            if($post->post_type === 'wpcrm-project') {
                $project_organization = get_post_meta($post_id);
                if(isset($project_organization['_wpcrm_project-attach-to-organization'])){
                    $got_posts = get_posts(array(
                        'meta_key' => '_wpcrm_contact-attach-to-organization',
                        'meta_value' => $project_organization['_wpcrm_project-attach-to-organization'],
                        'post_type' => 'wpcrm-contact',
                        'post_status' => 'publish',
                        'posts_per_page' => -1
                    ));
                    foreach($got_posts as $posted){
                        $emails[] = get_post_meta($posted->ID, '_wpcrm_contact-email', true);
                    }
										debug_msg($emails, 'sending emails');
                    wp_mail($emails, "New Project Created", "New Project Created");
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
    switch($col){
      case 'contact-user':
        //get the user id for the post
        $user_id = get_post_meta($post_id,'_wpcrm_contact-user_id',true);
        if(empty($user_id)) break;
        //debug_msg("found user ".$user_id);
        //get the username
        $user = get_user_by( 'id', $user_id );
        echo '<a href="'.admin_url("user-edit.php?user_id=".$user_id).'">'.$user->user_login.'</a>';
        break;
      case 'contact-email':
        $email = get_post_meta($post_id,'_wpcrm_contact-email',true);
        if(empty($email)) break;
        //debug_msg("found email ".$email);
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
    $message = '<div id="wpcrm-error-message" class="notice otice-error is-dismissible"><p>';
    foreach ( $errors as $error ) {
     $message .=  $error['message'] ;
    }
    $message .= '</p></div><!-- #error -->';
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
    $this->wpcrm_no_company('contact');
  }
  /**
   * Display an error notice if no company set for projects
   * This is hooked to the 'admin-Notices' and display a transient error msg set by the 'save_post' hooked function
   * @since 1.0.0
  **/
  public function wpcrm_project_no_company(){
    $this->wpcrm_no_company('project');
  }
  /**
   * Function to display a notice when a post is being published
   * This is a general function used by specific functions hooked by the 'admin_notices' which need to notify specific post that are missing organisation links.
   * @since 1.0.0
   * @param      string    $post_label     label of post to notify.
  **/
  protected function wpcrm_no_company($post_label){
    if ( ! ( $errors = get_transient( 'wpcrm_'.$post_label.'_no_company' ) ) ) {
     return;
    }
    // Otherwise, build the list of errors that exist in the settings errores
    $message = '<div id="wpcrm-'.$post_label.'-error" class="notice notice-error is-dismissible"><p>';
    foreach ( $errors as $error ) {
     $message .=  $error['message'] ;
    }
    $message .= '</p></div><!-- #error -->';
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
    delete_transient( 'wpcrm_'.$post_label.'_no_company' );
    remove_action( 'admin_notices', array($this,'wpcrm_'.$post_label.'_no_company') );
  }
  /**
   * Function to add a meta_box to cuar private file post
   * This function is hooked to 'add_meta_boxes'
   * @since 1.0.0
   * @param      string    $post_type     the post_type fro which to add a box.
  **/

  public function register_private_file_meta_boxes($post_type){
    if ($post_type!='cuar_private_file') return;

    global $post;

    add_meta_box(
        'wpcrm_project_metabox',
        __('Attach this file to a project', 'cuar'),
        array(&$this, 'print_project_meta_box'),
        'cuar_private_file',
        'normal', 'high');

  }

  /**
   * This will add a meta-box to the private file cpt
   * It is called by the callback parameter in the register_private_file_meta_boxes() fn.
   * @since 1.0.0
   * @param      string    $post     Post to which the metabox is being added to.
  **/
  public function print_project_meta_box($post){
    $html = '<label id="private-file-project" for="private_file_project">';

    $html .= 'Projects';

    $html .= '</label>';

    $html .= '<select id="current_projects" name="private_file_project">';
    //previously selected
    $value = get_post_meta( $post->ID, 'wpcrm-project-id', true );

    $args = array(
        'post_type' => 'wpcrm-project',
        'post_status' => 'publish',
        'posts_per_page' => -1
    );
    $posts = get_posts($args);
    if ($posts){
      $html .='<option value="">'.__('Select a project',$this->plugin_name).'</option>';
      $titles = array();
      $projects = array();

      foreach($posts as $project){
        $pid = $project->ID;
        $selected = ($pid==$value) ? ' selected="selected" ':' ';

        $org_id = get_post_meta($pid,'_wpcrm_project-attach-to-organization', true);
        if(empty($org_id)) continue;

        $org = get_post($org_id);
        $projects[$org_id][] ='<option'.$selected.'value="'.$pid.'">'.$project->post_title.'</option>';
        $titles[$org_id] = '<optgroup label="'.$org->post_title.'">';
      }
      foreach($titles as $org_id=>$group){
        $html .= $group;
        $html .= implode('',$projects[$org_id]);
        $html .= '</optgroup>';
      }
    }else{
      $html .='<option value="">'.__('No projects found.',$this->plugin_name).'</option>';
    }

    $html .= '</select>';
    echo $html;
  }
  /**
   *
   *
   * @since 1.0.0
   * @param      string    $post_id     Id of Post being saved.
  **/
  public function save_private_file_project( $post_id ) {
    if ( isset( $_POST['private_file_project'] ) && !empty($_POST['private_file_project'] ) ) {
      //project selected
      $project_id = $_POST['private_file_project'];
      update_post_meta( $post_id, 'wpcrm-project-id', $project_id );
      //need to set up users for this file.
      $org_id = get_post_meta($project_id, '_wpcrm_project-attach-to-organization', true);
      if( !empty($org_id) ){
        $args = array(
            'meta_key' => 'wpcrm_organisation_id',
            'meta_value' => $org_id,
            'post_type' => 'cuar_private_page',
            'post_status' => 'any',
            'posts_per_page' => -1
        );
        $posts = get_posts($args);
        if ($posts){
          $cuar_page = $posts[0];
          if( function_exists('cuar_addon') ){
            // debug_msg($cuar_page->ID, "Found private page ");

            $po_addon = cuar_addon('post-owner'); //this will instantiate the required class
            $owners = $po_addon->get_post_owners($cuar_page->ID);
            //add the owners of this private page to the file
            $po_addon->save_post_owners($post_id, $owners);
          }
        }else{
          debug_msg("No private page found for organisation ".$org_id);
        }// posts
      }else{
        debug_msg("No organisation found for project ".$project_id);
      }//org_id
    }
  }

  /**
   *
   *
   * @since 1.0.0
   * @param      string    $p1     .
   * @return     string    $p2     .
  **/
  public function hide_private_file_user_meta_box($hidden, $screen) {
    if ( ('post' == $screen->base) && ('cuar_private_file' == $screen->id) ){
      $hidden = array(
        'postexcerpt',
        'slugdiv',
        'postcustom',
        'trackbacksdiv',
        'commentstatusdiv',
        'commentsdiv',
        'authordiv',
        'revisionsdiv');
      $hidden[] ='cuar_post_owner';//= array_diff($hidden, array('postexcerpt');
    }
    return $hidden;
  }

  /**
   *Remove editor from priavate files edit page
   *Fundtin is hooked to 'admin_init'
   * @since 1.0.0
  **/
  public function remove_private_file_editor(){
    remove_post_type_support('cuar_private_file', 'editor');
  }
  /**
   * Add meta box for navigational menu
   * Hooked to 'admin_init'
   * @since 1.0.0
  **/
  public function add_menu_meta_box(){
    add_meta_box('add-customer-private-pages', __('Customer Area Private Pages', $this->plugin_name), array($this, 'menu_meta_box'), 'nav-menus', 'side', 'default');
  }
  /**
   * Adds a meta box for navigational menus
   *
   * @since 1.0.0
   * @param      string    $p1     .
   * @return     string    $p2     .
  **/
  public function menu_meta_box(){
    global $_nav_menu_placeholder, $nav_menu_selected_id;

    $_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;

    $last_object_id = get_option('cuar_nav_last_object_id', 0);
    $object_id = $this->new_object_id($last_object_id);
    ?>
    <div class="cuar-nav-div" id="cuar-nav-div">
        <input type="hidden" class="menu-item-db-id" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-db-id]" value="0" />
        <input type="hidden" class="menu-item-object-id" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-object-id]" value="<?php echo $object_id; ?>" />
        <input type="hidden" class="menu-item-object" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-object]" value="cuar_nav" />
        <input type="hidden" class="menu-item-type" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" value="" />
        <input type="hidden" class="menu-item-url" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-url]" value="" />
        <input type="hidden" class="regular-text menu-item-textbox" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-title]" value="User Private Pages" />
        <input type="hidden" id="cuar-private-pages-nonce" value="<?php echo wp_create_nonce('cuar_private_pages_nonce') ?>" />

        <p class="button-controls">
            <span class="add-to-menu">
                <input type="submit"<?php wp_nav_menu_disabled_check($nav_menu_selected_id); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e('Add User Private Pages'); ?>" name="add-cuar-pp-menu-item" id="submit-cuar-pp" />
                <span class="spinner"></span>
            </span>
        </p>

    </div>
    <?php
  }
  /**
   * Gets a new object id,given the current one
   *
   * @param int $last_object_id The current/last object id
   * @return int
   */
  private function new_object_id($last_object_id) {

      // make sure it's an integer
      $object_id = (int) $last_object_id;
      // increment it
      $object_id++;
      // if object_id was 0 to start off with, make it 1
      $object_id = ($object_id < 1) ? 1 : $object_id;
      // save into the options table
      update_option('cuar_nav_last_object_id', $object_id);
      return $object_id;
  }
  /**
   * Modify the menu item before display on Menu editor
   *
   * @param object $item The menu item
   * @return object
   */
  public function setup_item($item) {
      if(empty($item)) return;
      $foundObject=false;
      // only if it is our object
      switch($item->object){
          case ('cuar_nav'):
              $foundObject=true;
              // setup our label
              $item->type_label = __('Customer Area Private Pages', $this->plugin_name);
              break;
      }
      return $item;
  }
  /**
   * An ajax based workaround to save descriptions without using the custom object type
   */
  public function ajax_cuar_nav() {
      // verify the nonce
      $nonce = $_POST['cuar-nav-nonce'];
      if (!wp_verify_nonce($nonce, 'cuar_private_pages_nonce')) {
          die();
      }
      // get the menu item
      $item = $_POST['menu-item'];

      // save the description in a transient. This is what we'll use in setup_item()
      //set_transient('gs_sim_description_hack_' . $item['menu-item-object-id'], $item['menu-item-description']);

      // increment the object id, so it can be used by js
      $object_id = $this->new_object_id($item['menu-item-object-id']);
      echo $object_id;

      die();
  }
  /**
  * Modify the regsitered wpcrm-project post tppe
  * THis function enables public capability for the  post type. Hooked late on `init`
  * @since 1.0.0
  **/
  public function modify_wpcrm_post_type(){
    //wpcrm-project
    global $wp_post_types;
    //projects
    //$wp_post_types['wpcrm-project']->public = false;
    //$wp_post_types['wpcrm-project']->show_ui = true;
    $wp_post_types['wpcrm-project']->exclude_from_search = true;
    $wp_post_types['wpcrm-project']->publicly_queryable = true;
    $wp_post_types['wpcrm-project']->query_var = false;
    //$wp_post_types['wpcrm-project']->show_in_menu = true;
    //$wp_post_types['wpcrm-project']->show_in_admin_bar = false;
    //tasks
    //$wp_post_types['wpcrm-task']->public = false;
    //$wp_post_types['wpcrm-task']->show_ui = true;
    $wp_post_types['wpcrm-task']->exclude_from_search = false;
    $wp_post_types['wpcrm-task']->publicly_queryable = true;
    $wp_post_types['wpcrm-task']->query_var = false;
    //$wp_post_types['wpcrm-task']->show_in_menu = true;
    //$wp_post_types['wpcrm-task']->show_in_admin_bar = false;
  }
  /**
   * This function delete the user account associated with this contact post
   * It is hooked on 'trashed_post'
   * @since 1.0.0
   * @param      int    $post_id     ID of post benig processed.
  **/
  public function trash_wpcrm_contact($post_id){
    $post = get_post($post_id);
    if(!empty($post) && 'wpcrm-contact' != $post->post_type){
      return;
    }
    //delete the user account associated with this contact
    $user_id = get_post_meta($post_id, '_wpcrm_contact-user_id', true);
    wp_delete_user( $user_id);
    //remove the user from the CUAR pages
    $org_id = get_post_meta($post_id, '_wpcrm_contact-attach-to-organization', true);
    if(!empty($org_id)){
      $args = array(
          'meta_key' => 'wpcrm_organisation_id',
          'meta_value' => $org_id,
          'post_type' => 'cuar_private_page',
          'post_status' => 'any',
          'posts_per_page' => -1
      );
      $posts = get_posts($args);
      if ($posts){
        $cuar_page = $posts[0];
        if( function_exists('cuar_addon') ){
          //debug_msg($cuar_page->ID, "Foudn private page ");

          $po_addon = cuar_addon('post-owner'); //this will instantiate the required class
          $owners = $po_addon->get_post_owners($cuar_page->ID);
          if(($key = array_search($user_id, $owners['usr'])) !== false) {
            unset($owners['usr'][$key]);
            $po_addon->save_post_owners($cuar_page->ID, $owners);
          }

        }
      }
    }
  }
  /**
   * This function creates a user account associated with this contact post
   * It is hooked on 'ustrashed_post'
   * @since 1.0.0
   * @param      int    $post_id     ID of post benig processed.
  **/
  public function untrash_wpcrm_contact($post_id){
    $screen = get_current_screen();
    if('wpcrm-contact' != $screen->post_type){
      return;
    }
    $post=get_post($post_id);
    $this->create_user_for_contact($post_id, $post);
  }
  /**
   * This function trashes any tasks associated with the project being trashed
   * It is hooked on 'trashed_post'
   * @since 1.0.0
   * @param      int    $post_id     ID of post benig processed.
  **/
  public function trash_wpcrm_project($post_id){
    $post = get_post($post_id);
    if(!empty($post) && 'wpcrm-project' != $post->post_type){
      return;
    }
    //trash associated tasks
    $tasks = get_posts(array(
      'post_type'=> 'wpcrm-task',
      'meta_key' => '_wpcrm_task-attach-to-project',
      'meta_value' => $post_id,
      'post_status' => 'any'
    ));
    if(!empty($tasks)){
      foreach($tasks as $task){
        wp_trash_post( $task->ID  );
        add_post_meta(
          $post_id,
          '_wp_trash_wpcrm-task', //key
          $task->ID, //value
          false //unique
        );
      }
    }
    //trash any cuar private files
    $files = get_posts(array(
      'post_type'=> 'cuar_private_file',
      'meta_key' => 'wpcrm-project-id',
      'meta_value' => $post_id,
      'post_status' => 'any'
    ));
    if(!empty($files)){
      foreach($files as $file){
        wp_trash_post( $file->ID  );
        add_post_meta(
          $post_id,
          '_wp_trash_cuar-file', //key
          $file->ID, //value
          false //unique
        );
      }
    }
  }
  /**
   * This function untrashed associated project/cuar file
   * It is hooked on 'ustrashed_post'
   * @since 1.0.0
   * @param      int    $post_id     ID of post benig processed.
  **/
  public function untrash_wpcrm_project($post_id){
    $screen = get_current_screen();
    if('wpcrm-project' != $screen->post_type){
      return;
    }
    //untrash tasks
    $task_ids = get_post_meta(
      $post_id,
      '_wp_trash_wpcrm-task',
      false //single value
    );
    if(!empty($task_ids)){
      foreach($task_ids as $task_id){
        wp_untrash_post($task_id);
      }
    }
    //untrash files
    $file_ids = get_post_meta(
      $post_id,
      '_wp_trash_cuar-file',
      false //single value
    );
    if(!empty($file_ids)){
      foreach($file_ids as $file_id){
        wp_untrash_post($file_id);
      }
    }
  }
  /**
   * Add organistation to the table list
   * Hooked on 'manage_edit-{$post_type}_posts_columns'
   * @since 1.0.0
   * @param      Array    $columns     array of columns to display.
   * @return     Array    array of columns to display.
  **/
  public function add_organisation_column($columns){
    $column_title = apply_filters('wpcrm_cuar_organisation_column_title', 'Organisation');
    $start = array_splice($columns, 0, 2);
    $columns = array_merge($start, array('organisation'=>$column_title), $columns);
    return $columns;
  }
  /**
  * Add organistation to the table list
  * Hooked on 'manage_edit-{$post_type}_sortable_columns'
  * @since 1.0.0
  * @param      Array    $columns     array of columns to display.
  * @return     Array    array of columns to display.
  **/
  public function sort_organisation_column($columns){
    //array_splice($columns, 2, 0 , 'organisation');
    $columns['organisation'] = 'organisation';
    return $columns;
  }
  /**
  * Populate custom columns
  * hooked on 'manage_{$post_type}_posts_custom_column'
  * @since 1.0.0
  * @param      String    $column     column key.
  * @param      Int    $post_id     row post id.
  * @return     String    value to display.
  */
  public function populate_organisation_column( $column, $post_id ) {
    if('organisation' == $column){
      $screen = get_current_screen();
      switch($screen->post_type){
        case 'wpcrm-project':
        case 'wpcrm-opportunity':
        case 'wpcrm-contact':
          $meta = '_' . str_replace('-', '_', $screen->post_type) . '-attach-to-organization';
          $org_id = get_post_meta($post_id, $meta, true);
          echo get_the_title($org_id);
          break;
        case 'wpcrm-task':
          $proj_id = get_post_meta($post_id, '_wpcrm_task-attach-to-project', true);
          if(empty($proj_id)){
            echo '<em>No parent project</em>';
          }else{
            $org_id = get_post_meta($proj_id, '_wpcrm_project-attach-to-organization', true);
            if( empty($org_id) ){
              echo '<em>No parent organization</em>';
            }else{
              echo get_the_title($org_id);
            }
          }
          break;
      }
    }
  }
  /**
   * Adds a filter to the WP-CRM post tables
   * Hooked to action 'restrict_manage_posts'
   * @since 1.0.0
   * @param      String    $post_type     post to filter.
   * @return     String    echo html dropdown select to display
  **/
  public function wpcrm_type_filter($post_type){
    if(false === strpos($post_type, 'wpcrm-')){
      return;
    }
    $taxonomy_slug = str_replace('wpcrm-', '', $post_type). '-type';
    $taxonomy = get_taxonomy($taxonomy_slug);
    $selected = '';
    $request_attr = 'wpcrm_type';
    if ( isset($_REQUEST[$request_attr]) ) {
      $selected = $_REQUEST[$request_attr];
    }
    wp_dropdown_categories(array(
      'show_option_all' =>  __("Show All {$taxonomy->label}"),
      'taxonomy'        =>  $taxonomy_slug,
      'name'            =>  $request_attr,
      'orderby'         =>  'name',
      'selected'        =>  $selected,
      'hierarchical'    =>  true,
      'depth'           =>  3,
      'show_count'      =>  true, // Show # listings in parents
      'hide_empty'      =>  false, // Don't show posts w/o terms
    ));
  }
  /**
  * Adds a filter to the WP-CRM post tables for organisations
  * Hooked to action 'restrict_manage_posts'
   * @since 1.0.0
   * @param      String    $post_type     post to filter.
   * @return     String    echo html dropdown select to display
  **/
  public function organisation_filtering($post_type){
    if(false === array_search($post_type, array('wpcrm-project', 'wpcrm-opportunity', 'wpcrm-task') )){
      return;
    }
    $selected = '';
    $request_attr = 'wpcrm_org';
    if ( isset($_REQUEST[$request_attr]) ) {
      $selected = $_REQUEST[$request_attr];
    }
    $args = array(
      'post_type'=>'wpcrm-organization',
      'post_status' => 'publish',
      'orderby' => 'title'
    );
    $org_posts = get_posts($args);

    echo '<select id="wpcrm_organisation" name="wpcrm_org">';
		if(!empty($org_posts)){
	    echo '<option value="0">' . __( 'Show all Organisations', 'secure-wpcrm-frontend' ) . ' </option>';
	    foreach( $org_posts as $org ) {
	      $select = ($org->ID == $selected) ? ' selected="selected"':'';
	      echo '<option value="'.$org->ID.'"'.$select.'>' . $org->post_title . ' </option>';
	    }
			wp_reset_postdata();
		}
    echo '</select>';
  }
  /**
   * Filter the wpcrm table post per ogranisation
   * Hooked on filter 'parse_query'
   * @since 1.0.0
   * @param      WP_Query    $query     query passed by reference.
   * @return     string    $p2     .
  **/
  public function filter_request_query($query){
    if( !(is_admin() AND $query->is_main_query()) ){
      return $query;
    }
    if( false === array_search($query->query['post_type'], array('wpcrm-project', 'wpcrm-opportunity', 'wpcrm-task') ) ){
      return $query;
    }
    $post_type = $query->query['post_type'];
    //organization filter
    if(isset($_REQUEST['wpcrm_org']) && 0 != $_REQUEST['wpcrm_org']){
      $meta='';
      switch($post_type){
        case 'wpcrm-project':
        case 'wpcrm-opportunity':
          $meta = '_' . str_replace('-', '_', $post_type) . '-attach-to-organization';
          break;
        case 'wpcrm-task':
          $meta =  '_wpcrm_task-attach-to-project';
          break;
      }
      $query->query_vars['meta_query'] = array(array(
        'field' => $meta,
        'value' => $_REQUEST['wpcrm_org'],
        'compare' => '=',
        'type' => 'CHAR'
      ));
    }
    //type filter
    if( isset($_REQUEST['wpcrm_type']) &&  0 != $_REQUEST['wpcrm_type']){
      $term =  sanitize_text_field($_REQUEST['wpcrm_type']);
      $taxonomy_slug = str_replace('wpcrm-', '', $post_type). '-type';
      $query->query_vars['tax_query'] = array(
        array(
            'taxonomy'  => $taxonomy_slug,
            'field'     => 'ID',
            'terms'     => array($term)
        )
      );
    }
    return $query;
  }
  /**
   * Add a column to the User Admin Table
   * Hooked on 'manage_users_columns'
   * @since 1.0.0
   * @param      array  $column    Column to be added
   * @return     array  $column
   **/
  function new_modify_user_table($column){
      $column['organisation'] = 'Organization';
      return $column;
  }

  /**
   * Add a column to the User Admin Table
   * Hooked on 'manage_users_custom_column'
   * @since 1.0.0
   * @param      string $val  Custom column output. Default empty
   * @param      string $column_name  Column name
   * @param      int $user_id  ID of the listed user
   * @return     array  $column
   **/
  function add_organisation_to_user_table($val, $column_name, $user_id){
      switch ($column_name) {
          case 'organisation' :
              $got_posts = get_posts(array(
                  'meta_key' => '_wpcrm_contact-user_id',
                  'meta_value' => $user_id,
                  'post_type' => 'wpcrm-contact',
                  'post_status' => 'any',
                  'posts_per_page' => -1
              ));

              $org = 0;

              if(empty($got_posts)){
                  $org = 'NA';
              }else{
                  $details = get_post_custom($got_posts[0]->ID);
                  $org_id = $details['_wpcrm_contact-attach-to-organization'][0];
                  $org_post = get_post($org_id);
                  $org = $org_post->post_title;
              }
              return $org;
              break;
          default:
      }
      return $val;
  }

  function wpcrm_customer_area__user_table_filtering()
    {
        if ( isset( $_GET[ 'organization' ]) ) {
            $organization = $_GET[ 'organization' ];
            $organization = !empty( $organization[ 0 ] ) ? $organization[ 0 ] : $organization[ 1 ];
            $organization = wp_strip_all_tags($organization);
        } else {
            $section = -1;
        }
        echo ' <select name="organization[]" style="float:none;"><option value="">Organization..</option>';

        $got_posts = get_posts(array(
            'meta_key' => '_wpcrm_contact-attach-to-organization',
            'post_type' => 'wpcrm-contact',
            'post_status' => 'any',
            'posts_per_page' => -1
        ));

        foreach($got_posts as $posted){
            $organization = get_post_custom($posted->ID);
            $org_id[] = $organization['_wpcrm_contact-attach-to-organization'][0];
        }

        foreach(array_unique($org_id) as $uniq_org_id){
            $org_post = get_post($uniq_org_id);
            $org = $org_post->post_title;
            echo '<option value="' . $uniq_org_id . '">' . $org . '</option>';
        }

        echo '</select>';

        echo '<input type="submit" class="button" value="Filter">';

    }

    function wpcrm_customer_area__user_by_org($query)
    {
        global $pagenow;

        if ( is_admin() &&
            'users.php' == $pagenow &&
            isset( $_GET[ 'organization' ] ) &&
            is_array( $_GET[ 'organization' ] )
        ) {
            $organization = $_GET[ 'organization' ];
            $organization = !empty( $organization[ 0 ] ) ? $organization[ 0 ] : $organization[ 1 ];

            $user_posts = get_posts(array(
                'meta_key' => '_wpcrm_contact-attach-to-organization',
                'meta_value' => $organization,
                'post_type' => 'wpcrm-contact',
                'post_status' => 'any',
                'posts_per_page' => -1
            ));

            foreach($user_posts as $user_post){
                $user_post_ids = get_post_custom($user_post->ID);
                $user_ids[] = $user_post_ids['_wpcrm_contact-user_id'];
            }

            $query_user_ids = call_user_func_array('array_merge', $user_ids);

            $query->query_vars['include'] = $query_user_ids;
        }
    }
	/**
	* Funciton to modify the dashboard comments link to the taks comment.
	* Hooked on 'comment_row_actions'
	* @since 1.0.0
	**/
	public function dashboard_comment_links($actions, $comment_post){
		if(isset($actions['view'])){
			$link = admin_url('/post.php?post='.$comment_post->comment_post_ID.'&action=edit#commentsdiv');
			$actions['view'] = '<a class="comment-link" href="' . esc_url( $link ) . '" aria-label="' . esc_attr__( 'View comment in task' ) . '">' . __( 'View' ) . '</a>';
		}
		return $actions;
	}
}
