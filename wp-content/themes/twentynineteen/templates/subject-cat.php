<?php 

/**
 * Template Name: Subject 
 */
 get_header(); 
$taxonomy = 'category';
$terms = get_terms($taxonomy); // Get all terms of a taxonomy

if ( $terms && !is_wp_error( $terms ) ) :
?>
    <ul>
        <?php foreach ( $terms as $term ) {
            ?>
             <li>
                <a href="<?php echo get_term_link($term->slug, $taxonomy); ?>"><?php echo $term->name; ?></a></li>
            <?php
             $niche_ourteam_args = array(
                'post_type' => 'subject',
                'cat'   => $term->term_id,                 
                'orderby' => 'post_date',               
                'order' => 'DESC',
                // 'meta_query' => array(
                //     array(
                //         'key' => '_thumbnail_id',
                //         'compare' => 'EXISTS'
                //     ),
                // )
            );          

            $niche_ourteam = new WP_Query($niche_ourteam_args);

            while ($niche_ourteam->have_posts()) : $niche_ourteam->the_post();
                $title = get_the_title();
                $link = get_the_permalink();
                echo '<div class="subject_main"><p><a href="'.$link.'">'.$title.'<a><p></div>';

            endwhile;
         ?>
           
        <?php }
         ?>
    </ul>
<?php endif;?>


<?php get_footer(); ?>