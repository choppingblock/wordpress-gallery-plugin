<?php
/*
Plugin Name: Choppingblocks Wordpress Gallery Plugin
Plugin URI: https://github.com/choppingblock/wordpress-gallery-plugin
Description: a sample plugin to show how it is done
Version: 0.2
Author: Matthew Richmond
Author URI: http://choppingblock.com
License: GPL2
*/

/**
 * Enqueue plugin javascript-file
 */
function my_scripts_method() {
	wp_enqueue_script('gallery-scripts', plugins_url( '/wordpress-gallery-plugin.js' , __FILE__ ), array( 'jquery' ) );
}

add_action( 'wp_enqueue_scripts', 'my_scripts_method' );


/**
 * Enqueue plugin style-file
 */
function prefix_add_my_stylesheet() {
    // Respects SSL, Style.css is relative to the current file
    wp_register_style( 'prefix-style', plugins_url('wordpress-gallery-plugin.css', __FILE__) );
    wp_enqueue_style( 'prefix-style' );
}

add_action( 'wp_enqueue_scripts', 'prefix_add_my_stylesheet' );


/**
 * Replace the gallery shortcode with your own
 */

remove_shortcode('gallery');
add_shortcode('gallery', 'parse_gallery_shortcode');


function parse_gallery_shortcode($attr) {
 
	$post = get_post();

	static $instance = 0;
	$instance++;

	if ( ! empty( $attr['ids'] ) ) {
		// 'ids' is explicitly ordered, unless you specify otherwise.
		if ( empty( $attr['orderby'] ) )
			$attr['orderby'] = 'post__in';
		$attr['include'] = $attr['ids'];
	}

	// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
	if ( isset( $attr['orderby'] ) ) {
		$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
		if ( !$attr['orderby'] )
			unset( $attr['orderby'] );
	}

	extract(shortcode_atts(array(
		'order'      => 'ASC',
		'orderby'    => 'menu_order ID',
		'id'         => $post->ID,
		'itemtag'    => 'dl',
		'icontag'    => 'dt',
		'captiontag' => 'dd',
		'columns'    => 3,
		'size'       => 'large',
		'include'    => '',
		'exclude'    => ''
	), $attr));

	$id = intval($id);
	if ( 'RAND' == $order )
		$orderby = 'none';

	if ( !empty($include) ) {
		$_attachments = get_posts( array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );

		$attachments = array();
		foreach ( $_attachments as $key => $val ) {
			$attachments[$val->ID] = $_attachments[$key];
		}
	} elseif ( !empty($exclude) ) {
		$attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
	} else {
		$attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
	}

	if ( empty($attachments) )
		return '';

	if ( is_feed() ) {
		$output = "\n";
		foreach ( $attachments as $att_id => $attachment )
			$output .= wp_get_attachment_link($att_id, $size, true) . "\n";
		return $output;
	}

	$selector = "gallery-{$instance}";

	$gallery_div = "<div id='$selector' class='gallery galleryid-{$id} '>";
	$output = $gallery_div;

	$i = 0;
	foreach ( $attachments as $id => $attachment ) {
		$link = isset($attr['link']) && 'file' == $attr['link'] ? wp_get_attachment_link($id, $size, false, false) : wp_get_attachment_link($id, $size, true, false);

		$output .= "<div class='custom-gallery-item'>";
		$output .= "
			<div class='custom-gallery-icon'>
				$link
			</div>";
		if ( $captiontag && trim($attachment->post_excerpt) ) {
			$output .= "
				<div class='wp-caption-text custom-gallery-caption'>
				" . wptexturize($attachment->post_excerpt) . "
				</div>";
		}
		$output .= "</div>";
	}

	$output .= "
			<br style='clear: both;' />
		</div>\n";

	return $output;
}

?>
