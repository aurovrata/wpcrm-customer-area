<?php
/**
* This template can access the following variables
* @param  Array  $content_posts  an array post type cuar_private_files associated with the current user
*/
?>
<h2 class="wpcrm-tasks">Tasks</h2>
<p> Please leave any project feedback/comments in the appropriate task below </p>
<div id="tasks-accordion">
<?php
  global $post;
  foreach ($content_posts as $post ){
    setup_postdata( $post );
    $desc = get_post_meta($post->ID, '_wpcrm_task-description',true);
    ?>
    <h3><?php echo $post->post_title?></h3>
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
