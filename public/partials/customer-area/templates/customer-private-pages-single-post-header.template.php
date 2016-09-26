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
$date = sprintf("<em>%s</em>", get_the_date());
$author = sprintf("<em>%s</em>", get_the_author_meta('display_name'));
$recipients = sprintf("<em>%s</em>", cuar_get_the_owner());
$org_id = get_post_meta($post->ID,'wpcrm_organisation_id',true);

$args = array(
  'post_type'      => 'wpcrm-project',
  'posts_per_page' => -1,
  'orderby'        => 'date',
  'order'          => 'DESC',
  'meta_key'       => '_wpcrm_project-attach-to-organization',
  'meta_value'     => $org_id
);
$project_posts = new WP_Query( $args );
$project_titles = array();
$project_include = array();
$project_class  = array();

if($project_posts->have_posts()){

  while ( $project_posts->have_posts() ) {
		$project_posts->the_post();
    $post_id = get_the_ID();

		$project_titles[$post_id] = get_the_title();

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
      //let's buffer any template parts for this project in the theme folder ./wpcrm-cuar/
      ob_start();
      get_template_part('wpcrm-cuar/project',$type->slug);
      $page = ob_get_contents();
      ob_end_clean();
      if(!empty($page)){
        $project_include[$post_id][] = $page;
        $found_template = true;
      }
      //keep the slug for css class
      $project_class[$post_id][]='project_type_'.$type->slug;
    }
    if(!$found_template){ //if no rempaltes exists for this project, let's simply display the content
      $project_include[$post_id][] = get_the_content();
    }
	}
  wp_reset_postdata();
}

?>
<div id="tabs" class="wpcrm-project-tabs">
<?php if(!empty($project_titles)){ ?>
  <ul>
<?php
$idx=1;
foreach($project_titles as $proj_id=>$title) {
  $class = '';
  if(!empty($project_class[$proj_id])) $class = implode(' ',$project_class[$proj_id])
  ?>
    <li class="<?php echo $class;?>">
      <a href="#tabs-<?php echo ($idx++);?>">
        <?php echo $title;?>
      </a>
    </li>
  <?php
}
?>
  </ul>
<?php
$idx=1;
foreach($project_titles as $proj_id=>$title) {
  echo '  <div id="tabs-'. ($idx++) .'">';
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
          'meta_query'     => $meta_query
        );
        //debug_msg($args, "private files search query, ");
        $content_query = new WP_Query($args);
        $item_output ='';
        if ($content_query->have_posts()){
          echo '<h2>Documents</h2>';
          echo '<ul class="project-private-files">';
          while ($content_query->have_posts()) {
            $content_query->the_post();
            $pfile_id = get_the_ID();
            $files = cuar_get_the_attached_files( $pfile_id );
            foreach($files as $fid => $file){
              ?>
              <li>
                <span><?php the_title();?>:</span>&nbsp;<a href="<?php cuar_the_attached_file_link($pfile_id, $file); ?>">
                  <?php cuar_the_attached_file_caption($pfile_id, $file); ?>
                </a> (<?php cuar_the_attached_file_size($pfile_id, $file); ?>)
              </li>
              <?php
            }
          }
          wp_reset_postdata();
          echo '</ul>';
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
    //debug_msg($args, "private files search query, ");
    $content_query = new WP_Query($args);
    $item_output ='';
    if ($content_query->have_posts()){
    ?>
      <h2 class="wpcrm-tasks">Tasks</h2>
      <p> Please leave any project feedback/comments in the appropriate task below </p>
      <div id="tasks-accordion">
    <?php
      while ($content_query->have_posts()) {
        $content_query->the_post();
        $desc = get_post_meta(get_the_ID(), '_wpcrm_task-description',true);
        ?>
        <h3><?php the_title();?></h3>
        <div>
          <?php
          if($desc){
            echo $desc;
          }
          comments_template();
          ?>
        </div>
        <?php
      }
      wp_reset_postdata();
      ?>
      </div> <!-- #tasks-accordion -->
      <script>
        (function( $ ) {
          $( function() {
            $( "#tasks-accordion" ).accordion({
              collapsible: true
            });
          } );
        })( jQuery );
      </script>
      <?php
    }
    echo '  </div>';
  }
}else{ // no projects found ?>
  <p>
<?php _e('There are currently no projects associated with this organisation.','wpcrm-cusutomer-area'); ?>
  </p>
<?php
} ?>



</div> <!-- tabs -->
