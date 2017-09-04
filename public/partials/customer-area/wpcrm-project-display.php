<!-- This is the plugin template -->
<?php
$post_id = $post->ID;
?>
<div id="wpcrm-project-<? echo $post_id?>" class="wpcrm-project-content">
  <?php
  $content = apply_filters( 'the_content', $post->post_content );
  echo apply_filters('wpcrm_cuar_filter_project_content', $content, $post_id);
  ?>
</div>
