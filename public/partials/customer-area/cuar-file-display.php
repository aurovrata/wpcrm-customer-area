<?php
/**
* This template can access the following variables
* @param  Array  $content_posts  an array post type cuar_private_files associated with the current user
*/
?>
<h3 class="private-files-title">Documents</h3>
<ul class="project-private-files">
  <?php
foreach ($content_posts as $private_file_post) {
  $pfile_id = $private_file_post->ID;
  $files = cuar_get_the_attached_files( $pfile_id );
  foreach($files as $fid => $file){
    ?>
    <li class="private-file-item">
      <span class="private-file-title"><?php echo $private_file_post->post_title?>:</span>&nbsp;<a href="<?php cuar_the_attached_file_link($pfile_id, $file); ?>">
        <?php cuar_the_attached_file_caption($pfile_id, $file); ?>
      </a> (<?php cuar_the_attached_file_size($pfile_id, $file); ?>)
    </li>
    <?php
  }
}
?>
</ul>
<!-- end of documents -->
