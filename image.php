<?php
/**
 * The template for displaying image attachments
 *
 * @package WordPress
 * @subpackage Twenty_Fourteen
 * @since Twenty Fourteen 1.0
 */

// Retrieve attachment metadata.
$metadata = wp_get_attachment_metadata();

get_header();
?>


	<section id="primary" class="content-area image-attachment">
		<div id="content" class="site-content" role="main">
			<?php

			?>
	<?php
		// Start the Loop.
		while ( have_posts() ) : the_post();
	?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<header class="entry-header">
					<?php 
					/**
					 * SARAH: Hide title and header entry-meta
					 * NOTE: Cloned to post footer
					 */
					?>
					<!--
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
					<div class="entry-meta">

						<span class="entry-date">Uploaded <time class="entry-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></span>

						<span class="full-size-link"><a href="<?php echo esc_url( wp_get_attachment_url() ); ?>"><?php echo $metadata['width']; ?> &times; <?php echo $metadata['height']; ?></a></span>

						<span class="parent-post-link"><a href="<?php echo esc_url( get_permalink( $post->post_parent ) ); ?>" rel="gallery"><?php echo get_the_title( $post->post_parent ); ?></a></span>
						<?php edit_post_link( __( 'Edit', 'twentyfourteen' ), '<span class="edit-link">', '</span>' ); ?>
					</div>	
					-->

						<div class="entry-meta">

						<?php

						/**
						 * SARAH: Show attachment categories and tags if they exist
						 */
//						if (get_the_term_list( $post->ID, 'attachment_tag')) :
//							echo 'Attachment Tags: <ul class="attachment-tags">';
//							echo get_the_term_list( $post->ID, 'attachment_tag', '<li>', '</li><li>', '</li>' );
//							echo '</ul>';
//						endif;
//						if (get_the_term_list( $post->ID, 'attachment_category')) :
//							echo 'Attachment Categories: <ul class="attachment-categories">';
//							echo get_the_term_list( $post->ID, 'attachment_category', '<li>', '</li><li>', '</li>' );
//							echo '</ul>';
//						endif;
?>
					</div><!-- .entry-meta -->
				</header><!-- .entry-header -->

				<div class="entry-content">
					<div class="entry-attachment">
						<!--<div class="attachment">-->
							<?php twentyfourteen_the_attached_image(); ?>
						<!--</div>--><!-- .attachment -->

						<?php if ( has_excerpt() ) : ?>
						<div class="entry-caption">
							<?php the_excerpt(); ?>
						</div><!-- .entry-caption -->
						<?php endif; ?>
					</div><!-- .entry-attachment -->

					<?php
						the_content();
						wp_link_pages( array(
							'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'twentyfourteen' ) . '</span>',
							'after'       => '</div>',
							'link_before' => '<span>',
							'link_after'  => '</span>',
						) );
					?>
					<?php	
					/**
					 * Sarah: Show geo-mashup map
					 */
					if (class_exists('GeoMashup')) :
						echo GeoMashup::map('single'); 
					endif;
					?>
				</div><!-- .entry-content -->

					<?php 
					/**
					 * SARAH: Clone entry-meta section from post header
					 */
					?>
					<div class="entry-meta">
					<!--
						<span class="entry-date">Uploaded <time class="entry-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></span>
					-->
					<!--
						<span class="full-size-link"><a href="<?php echo esc_url( wp_get_attachment_url() ); ?>"><?php echo $metadata['width']; ?> &times; <?php echo $metadata['height']; ?></a></span>
					-->
						<?php 
						/**
						 * SARAH: Fix bug that caused orphan attachments to link to themselves as parent. 
						 * If post->post_parent was empty/zero get_permalink() returned global $post_id i.e. self
						 * Making sure post_parent is true ensures we only link to parent post if there is one
						 */
						if ($post->post_parent):?>
							<span class="parent-post-link">Part of <a href="<?php echo esc_url( get_permalink( $post->post_parent ) ); ?>" rel="gallery"><?php echo get_the_title( $post->post_parent ); ?></a></span>
						<?php endif;?>	
					</div>				
			</article><!-- #post-## -->

			<nav id="image-navigation" class="navigation image-navigation">
				<div class="nav-links">
				<?php previous_image_link( false, '<div class="previous-image">' . __( 'Previous Image', 'twentyfourteen' ) . '</div>' ); ?>
				<?php next_image_link( false, '<div class="next-image">' . __( 'Next Image', 'twentyfourteen' ) . '</div>' ); ?>
				</div><!-- .nav-links -->
			</nav><!-- #image-navigation -->

			<?php comments_template(); ?>

		<?php endwhile; // end of the loop. ?>

		</div><!-- #content -->
	</section><!-- #primary -->

<?php
/**
 * Sarah: Show content sidebar (not included in twenty-fourteen)
 */
get_sidebar( 'content' );
get_sidebar();
get_footer();
