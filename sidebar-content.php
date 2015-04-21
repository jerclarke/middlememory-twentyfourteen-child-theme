<?php
/**
 * The Content Sidebar
 *
 * @package WordPress
 * @subpackage Twenty_Fourteen
 * @since Twenty Fourteen 1.0
 */

/**
 * On attachment pages add attachment tags and categories in the sidebar
 */
if (is_attachment()) :
//	echo 'is_attachment';
	$content_sidebar_output = '';
	/**
	 * SARAH: Show attachment categories and tags if they exist
	 */
	if (get_the_term_list( $post->ID, 'attachment_tag')) :
		$content_sidebar_output .= 'Attachment Tags: <ul class="attachment-tags">';
		$content_sidebar_output .= get_the_term_list( $post->ID, 'attachment_tag', '<li>', '</li><li>', '</li>' );
		$content_sidebar_output .= '</ul>';
	endif;
	if (get_the_term_list( $post->ID, 'attachment_category')) :
		$content_sidebar_output .= 'Attachment Categories: <ul class="attachment-categories">';
		$content_sidebar_output .= get_the_term_list( $post->ID, 'attachment_category', '<li>', '</li><li>', '</li>' );
		$content_sidebar_output .= '</ul>';
	endif;
endif;
						

if (!$content_sidebar_output AND ! is_active_sidebar( 'sidebar-2' ) ) {
	return;
}
?>
<div id="content-sidebar" class="content-sidebar widget-area" role="complementary">
	<?php 
	echo $content_sidebar_output;
	dynamic_sidebar( 'sidebar-2' ); ?>
</div><!-- #content-sidebar -->
