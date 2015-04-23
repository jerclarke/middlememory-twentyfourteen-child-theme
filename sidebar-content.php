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
$content_sidebar_output = '';
if (is_attachment()) :
//	echo 'is_attachment';
	$metadata = wp_get_attachment_metadata();

	/**
	 * SARAH: Show attachment categories and tags if they exist
	 */
	if (get_the_term_list( $post->ID, 'attachment_tag')) :
		$content_sidebar_output .= 'Attachment Tags (people): <ul class="attachment-tags">';
		$content_sidebar_output .= get_the_term_list( $post->ID, 'attachment_tag', '<li>', '</li><li>', '</li>' );
		$content_sidebar_output .= '</ul>';
	endif;
	if (get_the_term_list( $post->ID, 'attachment_category')) :
		$content_sidebar_output .= 'Attachment Categories: <ul class="attachment-categories">';
		$content_sidebar_output .= get_the_term_list( $post->ID, 'attachment_category', '<li>', '</li><li>', '</li>' );
		$content_sidebar_output .= '</ul>';
	endif;
	
	$content_sidebar_output .= "<div class='entry-meta'>";
	$content_sidebar_output .= "<span class='full-size-link'><a href='" . esc_url( wp_get_attachment_url() ) . "'> {$metadata['width']} &times; {$metadata['height']}</a></span>";
	if (get_edit_post_link())
		$content_sidebar_output .= "<span class='edit-link'><a href='" . get_edit_post_link() . "'>Edit</a></span>";
//	$content_sidebar_output .= "<br><span class='entry-date'>Uploaded <time class='entry-date' datetime='" . esc_attr( get_the_date( 'c' ) ) . "'>" . esc_html( get_the_date() ) . "</time></span>";

	$content_sidebar_output .= "</div>";
	
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
