<?php
get_header();
?>

    <?php

while ( have_posts() ) :
                the_post();
                $duration =  get_post_meta(get_the_ID(), 'duration', TRUE);
                $title = get_the_title();
                $con = get_the_content();
                $featured_img_url = get_the_post_thumbnail_url(get_the_ID(),'full'); 
                echo '<div class="entry-content">';
                echo '<img src="'.$featured_img_url.'" with="100" height="100">'; 
                echo '<h3>'.$title.'</h3>';
                echo '<h2>'.$duration.'</h2>';
                 echo '<p>'.$con.'</p>';
                 echo "</div>";
            endwhile; // End the loop.
            ?>


<?php
get_footer();
