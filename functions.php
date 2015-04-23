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

/**
 * Display Geo Mashup global map during 'sarah_between_entry_header_and_content' action if we're on the "Global Mashup Page"
 * 
 * Geo Mashup plugin has a setting for "Global Mashup Page" which should contain a big map of all posts. 
 * On this page we want a full screen map so we need to put it above the main post content so we can't use the post editor. 
 * 
 * In our child theme we duplicated the content-page.php file and added an action in the full-width section of the header:
 * do_action('sarah_between_entry_header_and_content');
 * 
 * This function inserts a global map in that action if the current page is the Global Mashup page. 
 * 
 * 
 * @global object $geo_mashup_options
 * @return echo
 */
function sarah_between_entry_header_and_content_geomashup_main_map() {
	global $geo_mashup_options;

	/**
	 * Exit the action if this isn't a page or if Geo Mashup isn't active
	 */
	if (!is_page() OR !class_exists('GeoMashupOptions') OR !isset($geo_mashup_options) OR !is_object($geo_mashup_options))
		return;
	
	/**
	 * Get the Mashup page ID and exit if it isn't set up
	 */
	$mashup_page_id = $geo_mashup_options->get( 'overall', 'mashup_page' );
	if (!$mashup_page_id OR is_wp_error($mashup_page_id))
		return;
	
	/**
	 * Get the current page and exit if it isn't the Global Mashup Page
	 */
	$current_page_id = get_queried_object_id();	
	if ($current_page_id != $mashup_page_id)
		return;

	/**
	 * Show a global map!
	 */
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
 * Filter attributes of [mla_gallery] to enable Fullscreen Galleria mode by default
 * 
 * Fullscreen Galleria plugin hooks into [gallery] and requires [gallery link='file'] to function. 
 * 
 * We use [mla_gallery] instead, which requires mla_alt_shortcode="gallery" to load as [gallery]. 
 * 
 * SO: We filter attributes of [mla_gallery] to add mla_alt_shortcode="gallery" AND link="file" attributes. 
 * BUT: We only do so if Fullscreen Galleria plugin is enabled 
 * AND: We never do it if 'mla_output' attribute is present because it means MLA is outputting something 
 * other than a normal gallery.
 * NOTE: There may be other uses of [mla_gallery] that need to be excluded, mla_output was just the one I found.
 * 
 * @param type $atts
 * @return string
 */
function sarah_filter_mla_gallery_raw_attributes($atts) {
	
	// Only filter atts if Fullscreen Galleria is active
	if (!is_plugin_active('fullscreen-galleria/galleria-fs.php')) 
		return $atts;

	// Don't filter atts if 'mla_output' attribute is set (causes Notices/Warnings)
	if (isset($atts['mla_output']))
		return $atts;
	
	// Insert mla_alt_shortcode=gallery so MLA uses default [gallery] output
	$atts['mla_alt_shortcode'] = 'gallery';
	
	// Insert link="file" to make Fullscreen Galleria work properly
	$atts['link'] = 'file';
	
	return $atts;
}
add_filter('mla_gallery_raw_attributes', 'sarah_filter_mla_gallery_raw_attributes');

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

/**
 * Filter body_class to remove full-width when is_attachment
 * 
 * 'full-width' is added by twentyfourteen_body_classes because it assumes you'll never
 * want to use sidebar-content on attachments. We do use it there so we need to remove the
 * class as well as adding get_sidebar('content') to image.php 
 * 
 * @param type $classes
 * @return type
 */
function sarah_filter_body_class_for_attachments($classes) {
	if (is_attachment()) 	
		$classes = array_diff($classes, array('full-width'));
	
	return $classes;
}
add_filter('body_class', 'sarah_filter_body_class_for_attachments', 1000);

/**
 * Beutified variable/array/object output for debugging
 *
 * Intellegently determines the type of data in $input and
 * prints out a pleaseant rendering of its contents.
 *
 * Distinguishes between arrays, objects, strings, integers and booleans
 * Otherwise states its not sure what $input is.
 *
 * $args is an array of optional parameters:
 *	hide • Default false. If true wrap the output in <!--html comment tags--> instead of pretty divs.
 *	title • Default false. H3 to show at the start of output explaning expectations/source
 *	strip_tags • Default true, false to disable. HTML stripped from strings and print_r output.
 *	truncate • Default 2000. False to disable. Strings will be truncated to this many characters.
 *	excluded_children • Children of the passed-in object/array to NOT show. Can be array or a single key to exclude.
 *   collapse • Whether to collapse array/object content by default or not.
 *	echo • Default true. False will return HTML output instead of printing it to screen.
 *
 * @uses GV_ECHOR_CSS_USED bool Whether echor CSS has been used on this pageload yet to only output echor CSS on the first instance.
 * @uses GV_ECHOR_JQUERY_USED bool Whether echor CSS has been used on this pageload yet to only output echor CSS on the first instance.
 * @global <type> $gv
 * @param <type> $input
 * @param <type> $args
 */
if (!function_exists('echor')):
function echor($input, $args = "") {
	// See function doc for explanation of default arguments.
	$defaults = array (
		'hide' => false,
		'title' => false, 
	     'strip_tags' => true,
	     'truncate' => 2000,
		'excluded_children' => '',
		'collapse' => false, 
		'echo' => true,
	);
	$args = wp_parse_args($args, $defaults);
	extract($args, EXTR_SKIP);

	/**
	 * Echor CSS, only inserted once per pageview, tracked by GV_ECHOR_CSS_USED constant
	 */
	if (!defined('GV_ECHOR_CSS_USED') and (!$hide AND $echo )) : 
		?>
		<style type="text/css">
			.echor-container {
				text-align:left;
				margin:5px 5px 5px 0;
				direction:ltr;
			}
			.echor {
				padding:1em;
				font-family: "Source Code Pro", monaco, courier,  verdana;
				color: #333;
				background-color: #FFF3C2;
				font-size:11px;
				line-height:1.2;
				border: 2px solid #F1E19D;
			}
			.echor h3 {
				margin: 0 0 5px 0;
				padding: 0;
				font-family: "Source Sans Pro", sans-serif;
				font-size: 17px;
				font-weight: normal;
			}
			.echor b {
				font-weight: normal;
				font-family: "Source Sans Pro", sans-serif;
				font-size: 14px;
			}
			.echor pre {
				margin: 5px 0;
				white-space: pre-wrap;
			}
			.echor .calling-function {
				font-size: 9px;
				text-align:right;
				padding: 5px 0 0 0 ;
				color: #666;
			}
		</style>
		<?php
		// Set the constant to avoid showing CSS again
		define('GV_ECHOR_CSS_USED', true);
		
	endif;

	// Label shows above any content. Mandatory
	$label = '';
	// Echor content shows below label, optional if label covers it.
	$echor_content = '';
	
	/**
	 * Array or Object (enhanced print_r output)
	 */
	if (is_array($input) OR is_object($input)) :

		$label = "Object";
		if (is_array($input))
			$label = "Array";

		$input_count = count($input);

		/**
		 * Remove excluded children if requested
		 */
		if ($excluded_children) :
			// Reformat as array with one element if it's just a string
			if (!is_array($excluded_children))
				$excluded_children = array($excluded_children);

			/**
			 * Remove excluded children by recreating the array/object with unwanted children replaced.
			 */
			// Array Style
			if (is_array($input)) :
				$input_copy = array();
				foreach ($input as $key => $value) :
					// If this element is not excluded add it to the copy
					if (!in_array($key, $excluded_children))
						$input_copy[$key] = $value;
					 // Otherwise add a note that it was excluded
					else
						$input_copy[$key] = '•• VALUE EXCLUDED ••';
				endforeach;
			// Object style (has to be one or the other)
			else :
				$input_copy = new stdClass();
				foreach ($input as $key =>$value) :
					// If it is not in the excluded array then add it to the input_copy
					 if (!in_array($key, $excluded_children))
						$input_copy->$key = $value;
					 // Otherwise add a note that it was excluded
					 else
						$input_copy->$key = '•• VALUE EXCLUDED ••';
				endforeach;
			endif;
		// If we aren't excluding children just copy $input into $input_copy
		else :
			$input_copy = $input;
		endif;

		// Fetch the <pre>-formatted print_r output
		$print_r_output = print_r($input_copy, 1);
		// Convert it into an array of each line of the output
		$print_r_lines = explode("\n", $print_r_output);
		// Clear 3 spaces from the front of the print_r <pre> output, its unnecessary space
		foreach ($print_r_lines as $key => $line)
			$print_r_lines[$key] = substr($line, 3);

		// Remove the first two and last two lines, they are unnecessary brackets and labels from print_r
		array_shift($print_r_lines);
		array_shift($print_r_lines);
		array_pop($print_r_lines);
		array_pop($print_r_lines);
		// Turn the array of lines back into a string
		$reformatted_print_r = implode("\n",$print_r_lines);

		/**
		 * Remove any HTML to avoid breaking the page
		 */
		if ($strip_tags)
			$reformatted_print_r = strip_tags($reformatted_print_r);

		/**
		 * Add item count and show/hide button to label
		 */
		$label .= " ($input_count items) ";
		// Add show/hide button to label 
		if (!$hide)
			$label .= " &middot; <small><a class=\"echor-toggle\">[show/hide]</a></small>";
		
		/**
		 * Prepare content with print_r
		 */
		$echor_content = "<pre>$reformatted_print_r</pre> \n";

	/**
	 * String - Truncated and shown in <pre>
	 */
	elseif ( is_string($input) ) :

		/**
		 * If it's empty then say so in words
		 */
		if (empty($input)) :
			$label = ' <i>is_string() but empty</i>';
		else :
			$label = '<b>String: </b>';
		endif;

		/**
		 * Remove any HTML to avoid breaking the page with strange string
		 * Pass 'strip_tags=0' into echor to disable this and output whatever html was in $input
		 */
		if ($strip_tags)
			$input = strip_tags($input);

		/**
		 * If it's long and $truncate is active condense the string to it's start and end.
		 * Pass 'truncate=0' into echor to turn off truncation for that instance,
		 * or pass in a larger number that suits your specific task.
		 */
		if ($truncate) :
			// Default truncation length is 2000
			$text_truncation_length = $truncate;
			$text_truncation_half = (int) $truncate / 2;

			if (strlen($input) > $text_truncation_length) :

				// Copy the last $text_truncation_half characters (start -$text_truncation_half from the end, copy $text_truncation_half chars) of the string
				$end_of_string = substr($input, -$text_truncation_half, $text_truncation_half);
				// Cut the $input to only it's first 500 characters
				$start_of_string = substr($input, 0, 1000);
				// Tack the ending onto the starting
				$input = $start_of_string . "\n\n • TRUNCATED • \n\n" . $end_of_string;

			endif;
		endif; // $truncate


		$echor_content = "<pre>$input</pre>";

	/**
	 * Integers
	 */
	elseif ( is_int($input) AND $input) :
		$label = "<b>Integer • $input</b> ";

	/**
	 * Float number
	 */
	elseif (is_float($input)) :
		$label = "<b>Float • $input</b>";

	/**
	 * Numeric Zero
	 */
	elseif ($input === 0) :
		$label = '<b>0 === $input</b>';

	/**
	 * Boolean False
	 */
	elseif ($input === FALSE) :
		$label = '<b>false === $input</b>';

	/**
	 * Boolean True
	 */
	elseif ($input === TRUE) :
		$label = '<b>true === $input</b>';

	/**
	 * Undefined variable
	 */
	elseif (!$input) :
		$label = '<i>Undefined or empty value</i>';

	/**
	 * Other defined value
	 */
	elseif ($input) :
		$label = "Not sure what it is • $input";
	endif;
	
	/**
	 * Determine the calling function 
	 */
	$debug_backtrace = debug_backtrace();
	$calling_function = $debug_backtrace[1]['function'];

	$final_output = '';
	$extra_css_classes = '';
	
	/**
	 * Hide mode with $output wrapped in HTML comments
	 */
	if ($hide) :

		$final_output .= "<!-- ECHOR (SET TO SILENT) \n";
		if ($title)
			$final_output .= " $title \n";
		$final_output .= "\n $label \n \n";
		$final_output .= "\n $echor_content \n \n";
		$final_output .= " -->\n";
	
	/**
	 * Non-Hidden printout using divs
	 */
	else :
		/**
		 * Add the echor collapsed class so that this will start as closed
		 */
		if ($collapse) 
			$extra_css_classes .= "echor-collapsed ";
		
		$final_output .= "<div class='echor-container'>\n";
		$final_output .= "<div class='echor'> \n";
		if ($title)
			$final_output .= "<h3>{$args['title']}</h3> \n";
		if ($label)
			$final_output .= "<span class='echor-label'>$label</span>";
		if ($echor_content)
			$final_output .= "<div class='echor-content $extra_css_classes'>$echor_content</div>";
//		$final_output .= $output;
		$final_output .= "<p class='calling-function'> $calling_function()</p>";
		$final_output .= "</div><!--.echor-->\n";
		$final_output .= "</div><!--#echor-container-->\n";
	endif;
	
	/**
	 * Output Echor jQuery code only once per pageload, tracked by GV_ECHOR_JQUERY_USED
	 */
	if (!defined('GV_ECHOR_JQUERY_USED') and $collapse ) : 
			$final_output .= "
			<script type='text/javascript'>{
				jQuery(document).ready(function($) {
					// Hide any initially-collapsed echors
					$('.echor-collapsed').hide();
					// Make the toggle button toggle the content of the current echor
					$('.echor-toggle').click(function() {
					   // Get the parent echor, then find it's child .echor-content and toggle it
					   $(this).parents('.echor').children('.echor-content').toggle();
					});
					
				}); // end document.ready				
			}</script>";
		// Set the constant to avoid showing this again
		define('GV_ECHOR_JQUERY_USED', true);
	endif;
	
	/**
	 * Echo or return output
	 */
	if ($echo)
		echo $final_output;
	else
		return $final_output;
}
endif; // function_exists(echor)