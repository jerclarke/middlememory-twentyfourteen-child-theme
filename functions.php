<?php
/**
 * Functions.php for Middle Memory child theme based on Twenty Fourteen
 */


/**
 * SARAH: Set this up as child theme of twentyfourteen
 */
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );
function theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}

/**
 * SARAH: Fix attachment category/term listings to show attachments by fetching status=inherit instead of status=publish
 */
function wpa82573_show_tax_attachments( $query ) {
    if (( $query->is_tax('attachment_category') OR $query->is_tax('attachment_tag') )&& $query->is_main_query() ) {
        $query->set( 'post_status', 'inherit' );
    }
}
add_action( 'pre_get_posts', 'wpa82573_show_tax_attachments' );


function sarah_between_entry_header_and_content_geomashup_main_map() {
	global $geo_mashup_options;
	
	if (!is_page())
		return;

	if (!class_exists('GeoMashupOptions') OR !isset($geo_mashup_options) OR !is_object($geo_mashup_options))
		return;
	
	
	$mashup_page_id = $geo_mashup_options->get( 'overall', 'mashup_page' );
	
	if (!$mashup_page_id OR is_wp_error($mashup_page_id))
		return;
	
	
	$current_page_id = get_queried_object_id();
	
	if ($current_page_id != $mashup_page_id)
		return;

	echo GeoMashup::map(array(
		'map_content' => 'global',
	));
	
	/**
	 * Insert a special style to remove top padding form content-area
	 * so that map is flush with top. 
	 */
	echo "<style type='text/css'>
		.content-area {padding-top:0;}
	</style>
	";
}
add_action('sarah_between_entry_header_and_content', 'sarah_between_entry_header_and_content_geomashup_main_map');

/**
 * SARAH - Filter wp_get_attachment_link to use $permalink=true mode on attachment taxonomy screens
 * 
 * By default attachments listed on archive screens are shown with wp_get_attachment_link() which 
 * links to the file rather than attachment page on the image itself. 
 * There is no direct filter to change the $permalink value to true to link to the attachment page.
 * 
 * Code in this function filters the output to re-run wp_get_attachment_link() with permalink=true 
 * and replaces the output with that. 
 * 
 * It removes then re-adds itself before running to avoid an infinite loop
 * 
 * It only runs on attachment_category and attachment_tag archives to avoid breaking attachment
 * display in other contexts.
 */
function sarah_filter_wp_get_attachment_link($output, $id, $size, $permalink, $icon, $text) {

	// Only run on attachment taxonomy archives
	if ( is_tax('attachment_category') OR is_tax('attachment_tag') ) :

		// Remove this filter to avoid infinite loop
		remove_filter('wp_get_attachment_link', 'sarah_filter_wp_get_attachment_link');
	
		// Re-run wp_get_attachment_link with permalink=true so it links to page instead of file
		$permalink = true;
		$output = wp_get_attachment_link($id, $size, $permalink, $icon, $text);
		
		// Re-add this filter for the next one
		add_filter('wp_get_attachment_link', 'sarah_filter_wp_get_attachment_link', 10, 6);
		
	endif;

	return $output;
}
add_filter('wp_get_attachment_link', 'sarah_filter_wp_get_attachment_link', 10, 6);

/**
 * SARAH: Filter geo mashup 'where' clause to insert 'inherit' as a valid status when attachments are being queried
 *
 * By default geo mashup queries for attachments when post_type=all but only fetches
 * 'publish' so you never get any attachments' geo data
 *
 * NOTE: As of Geo Mashup 1.8.3 this hack isn't enough! We also need to edit GeoMashupQuery::generate_object_html()
 * to insert 'inherit' as a valid status. 
 * Unfortunately the plugin blocks access out of ignorance. It checks if posts are appropriate (i.e. if you asked for 
 * future posts to be included) for the given query in the function that this filters, then on the query side it 
 * just asks for all possible statuses. Unfortunately it forgot that 'inherit' is the only status for attachments
 * and didn't include it even though attachments are a valid post type
 * 
 * @since Twenty Fourteen 1.0
 */
function sarah_filter_geo_mashup_locations_where($where) {

  // Only filter if it's exactly what we expect Fetching attachments that are published (STUPID)
  if (!strpos($where, "post_status = 'publish'") OR !strpos($where, "o.post_type IN ('post', 'page', 'attachment')"))
    return $where;

  // Add inherit
  $where = str_replace("post_status = 'publish'", "post_status in( 'publish', 'inherit')", $where);

  return $where;
}
add_filter( 'geo_mashup_locations_where', 'sarah_filter_geo_mashup_locations_where');

