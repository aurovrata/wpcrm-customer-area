<?php
/** Template version: 3.0.0
 *
 * -= 3.0.0 =-
 * - Improve UI for new master-skin
 *
 * -= 2.0.0 =-
 * - Add cuar- prefix to bootstrap classes
 *
 * -= 1.1.0 =-
 * - Updated to new responsive markup
 *
 * -= 1.0.0 =-
 * - Initial version
 *
 */ ?>

<?php
global $post;
$private_page_id = get_the_ID();
$date = sprintf("<em>%s</em>", get_the_date());
$author = sprintf("<em>%s</em>", get_the_author_meta('display_name'));
$recipients = sprintf("<em>%s</em>", cuar_get_the_owner());
$org_id = get_post_meta($post->ID,'wpcrm_organisation_id',true);
$current_user_id = get_current_user_id();
$type_id = '';  //the project-type ID requested for display on the page
$page_term_name=''; //the name of the project-type term
/**
* Filter to change/add an extra tab and its label at the end of the of scrollable tabs for user to add more tabs for example.  Default is empty which will print to extra tab.  supplying an extra tab title requires also to filter for the extra panel 'wpcrm_cuar_add_one_more_tab_content'
* @param $tab_label  tab label, default empty.
* @param $current_user_id  logged in user id.
* @param $type_id  the wpcrm project type being displayed.
* @since 1.0
*/
$extra_tab = apply_filters('wpcrm_cuar_add_one_more_tab_title','',$current_user_id, $type_id);

$args = array(
  'post_type'      => 'wpcrm-project',
  'posts_per_page' => -1,
  'orderby'        => 'date',
  'order'          => 'DESC',

);
/*
 If the menu option to show project types as sub-menu was skipped we display all project post
 Else, if it was not skipped, we need to first find if a project-type was passed in the url slug
 oe else display the first projects of type of the first menu item
*/
if( !apply_filters('wpcrm_cuar_skip_project_type_sub_menus', false,  $current_user_id)){
  //do we have a type in the url ?
  $type_id = '';
  if(isset($_GET['type'])) {
    $type_id = $_GET['type'];
  }
  if(empty($type_id)){
    $term_args = array(
      'taxonomy' => 'project-type',
      'orderby'  => 'term_id',
      'order'    => 'ASC',
      'hide_empty' => false);
    $term_args = apply_filters('wpcrm_cuar_query_project_type_in_menu', $term_args, $current_user_id);
    $terms = get_terms($term_args);
    if(empty($terms)){
      debug_msg("No Project Type found, displaying all projects.");
    }else{
      $type_id = $terms[0]->term_id;
    }
  }
  if(!empty($type_id)){
    //add taxonomy constraint to the project query
    $args['tax_query'] = array(
      array(
        'taxonomy' => 'project-type',
  			'field'    => 'term_id',
  			'terms'    => $type_id
      )
    );
    $term = get_term_by('id', $type_id, 'project-type');
    $page_term_name = $term->name;
  }
}
//let's make sure we have the right organisation
/**
* filter out complete projects
* @since 1.0rc2
*/
$args['meta_query'] = array(
  'relation' => 'AND',
		array(
			'key'     => '_wpcrm_project-attach-to-organization',
			'value'   => $org_id,
		),
    array(
      'key' => '_wpcrm_project-status',
      'value' => 'complete',
      'compare' => 'NOT lIKE'
    )
	);
//$project_posts = new WP_Query( $args );
$project_posts = get_posts( $args );
$project_titles = array();
$project_include = array();
$project_class  = array();
//global $wp_filter;
//debug_msg($wp_filter['parse_query'], 'parse query ...');
//debug_msg($wp_filter['pre_get_posts'], 'pre_get_posts ...');

//debug_msg($project_posts->re
if(!empty($project_posts)){
  foreach ( $project_posts as $post ) {
    $post_id = $post->ID;

  	$project_titles[$post_id] = $post->post_title;

    $project_type = wp_get_post_terms( $post_id, 'project-type' );
    if(is_wp_error($project_type)){
      debug_msg($project_type, "Error while loading Project type terms for project ID ".$post_id.", ");
      continue;
    }
    /*
    *  FILTER: allows array of project type slug to be used a teamplate parts.
    * Array can be modfified, slug removed or re-ordered to as to get desired template
    * structure. If emptied, this project content will be filled with its content.
    */
    $project_type = apply_filters('wpcrm_cuar_project_templates',$project_type, $post_id);
    $found_template = false;
    foreach($project_type as $type){
      if(apply_filters('wpcrm_cuar_skip_project_type_in_menu', false, $type, $current_user_id)){
        continue;
      }
      //let's buffer any template parts for this project in the theme folder ./wpcrm-cuar/
      ob_start();
      if( file_exists( get_stylesheet_directory().'/wpcrm-cuar/project-'.$type->slug.'.php' ) ) {
        include(get_stylesheet_directory().'/wpcrm-cuar/project-'.$type->slug.'.php');
      }else{
        include(plugin_dir_path(__DIR__).'wpcrm-project-display.php');
      }
      $page = ob_get_contents();
      ob_end_clean();
      $project_include[$post_id][] = $page;
      //keep the slug for css class
      $project_class[$post_id][]='project_type_'.$type->slug;
    }
  }
  wp_reset_postdata();
}
$page_title ='';
if(!empty($type_id)){
  $page_title = "All ".$page_term_name;
}else{
  $page_title = "All Projects";
}
$page_title = apply_filters('wpcrm_cuar_page_title',$page_title, $type_id, $current_user_id);
?>
<h2 id="page-title"><?php echo $page_title?></h2>
<div id="tabs" class="wpcrm-project-tabs<?= empty($extra_tab)?'':' extra-project'?>">
<?php if(!empty($project_titles)){ ?>
  <ul id="ul-tabs" class="scroll_tabs_theme_light">
<?php
  $idx=1;
  foreach($project_titles as $proj_id=>$title) {
    $class = '';
    if(!empty($project_class[$proj_id])) $class = implode(' ',$project_class[$proj_id]);
    if(1==$idx) $class .=' tab_selected';
    // debug_msg($class);
    ?>
      <li class="<?= $class;?>" data-panel="#tabs-<?= ($idx++);?>">
          <?= $title;?>
      </li>
    <?php
  }

?>
  </ul> <!--  end tabs-->
<?php
  if(!empty($extra_tab)){
    ?>
    <div class="extra-project-tab <?= empty($type_id) ? '' : $class;?>" data-panel="#tabs-<?= ($idx++);?>">
      <a title="Request extra <?=$page_term_name?>" href="javascript:void();"><?= $extra_tab;?></a>
    </div>
    <?php
  }
  ?>
<div id="panels">
  <?php
  $idx=1;
  foreach($project_titles as $proj_id=>$title) {
    echo '  <div id="tabs-'. ($idx++) .'" class="display-none">';
    foreach($project_include[$proj_id] as $content){
      echo $content;
    }
    //
    //PROJECT PRIVATE FILES load project private files if any
    //
    if(function_exists('cuar_addon') ){
      $cpf_addon = cuar_addon('customer-private-files'); //this will instantiate the required class
      //check if the logged in user has access to this
      if ( $cpf_addon->is_accessible_to_current_user()){
        //to check what this user owns, we need to call the post-owner class
        $po_addon = cuar_addon('post-owner');
        $current_user_id = get_current_user_id();
        //now we can build the query
        $meta_query = $po_addon->get_meta_query_post_owned_by($current_user_id);

        if(!empty($meta_query) && isset($meta_query['relation'])){
          $meta_query['relation'] = 'and';
        }else{
          $meta_query = array();
          $meta_query['relation'] = 'or';
        }
        //search the private files with meta key corresponding to this project
        $meta_query[] = array('key'=>'wpcrm-project-id',
                              'value'=>$proj_id
                        );
        $args = array(
          'post_type'      => $cpf_addon->get_friendly_post_type(),
          'posts_per_page' => -1,
          'orderby'        => 'date',
          'order'          => 'DESC',
          'meta_query'     => $meta_query,
          'post_status'    => 'publish'
        );
        $content_posts = get_posts($args);
        $item_output ='';
        if (!empty($content_posts) ){
          if( file_exists( get_stylesheet_directory().'/wpcrm-cuar/private-file-content.php' ) ) {
            include(get_stylesheet_directory().'/wpcrm-cuar/private-file-content.php');
          }else{
            include(plugin_dir_path(__DIR__).'cuar-file-display.php');
          }
          //reset the post data
          wp_reset_postdata();
        }

      }else{
        echo '<p class="project-file-access-error">' . apply_filters('wpcrm_cuar_no_access_private_files_msg', __('Insufficient access to view project files','wpcrm-cusutomer-area')) .'</p>';
      }
    }else{
      echo '<p class="project-file-access-error">' . apply_filters('wpcrm_cuar_error_loading', __('Error loading project files', 'wpcrm-cusutomer-area')) .'</p>';
    }

    $args = array(
      'post_type'      => 'wpcrm-task',
      'posts_per_page' => -1,
      'orderby'        => 'date',
      'order'          => 'DESC',
      'meta_key'       => '_wpcrm_task-attach-to-project',
      'meta_value'     => $proj_id
    );
    // debug_msg($args, "task search query, ");
    $content_posts = get_posts($args);
    $item_output ='';
    if (!empty($content_posts) ){
      if( file_exists( get_stylesheet_directory().'/wpcrm-cuar/project-tasks-content.php' ) ) {
        include(get_stylesheet_directory().'/wpcrm-cuar/project-tasks-content.php');
      }else{
        include(plugin_dir_path(__DIR__).'project-tasks-display.php');
      }
      //reset the query
      wp_reset_postdata();
    }

    echo '</div>';  //close the tab
  }
  if(!empty($extra_tab)){
    ?>
    <div id="tabs-<?php echo ($idx++)?>" class="display-none">
      <?php echo apply_filters('wpcrm_cuar_add_one_more_tab_content','',$current_user_id, $type_id);?>
    </div>
    <?php
  }
   ?>
 </div> <!-- end #panels -->
   <?php
}else{ // no projects found
    if( file_exists( get_stylesheet_directory().'/wpcrm-cuar/project-no-content.php' ) ) {
      include(get_stylesheet_directory().'/wpcrm-cuar/project-no-content.php');
    }else{
      include(plugin_dir_path(__DIR__).'no-project-display.php');
    }
} ?>
</div> <!-- tabs -->
