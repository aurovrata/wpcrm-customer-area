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
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpcrm-cuar-nav.js', array( 'jquery' ), $this->version, false );
        break;
      default:
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wpcrm-customer-area-admin.js', array( 'jquery' ), $this->version, false );
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
            //debug_msg($cuar_page->ID, "Foudn private page ");

            $po_addon = cuar_addon('post-owner'); //this will instantiate the required class
            $owners = $po_addon->get_post_owners($cuar_page->ID);

            if(!in_array($user->ID, $owners['usr'])){
              $owners['usr'][] = $user->ID;
              $po_addon->save_post_owners($cuar_page->ID, $owners);
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
  public function validate_project($ID, $post){

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
          add_post_meta($ID, 'cuar_private_page', $cuar_page->ID);
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

}
