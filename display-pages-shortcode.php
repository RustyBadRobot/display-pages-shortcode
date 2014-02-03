<?php

/**

 * Plugin Name: Display Pages Shortcode

 * Plugin URI: http://www.widgetmedia.co/shortcode-to-display-pages/

 * Description: Display a listing of pages using the [display-pages] shortcode

 * Version: 1.0

 * Author: Paul Angell

 * Author URI: http://www.widgetmedia.co

 *

 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU 

 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume 

 * that you can use any other version of the GPL.

 *

 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without 

 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

 *

 * @package Display Pages

 * @version 1.0

 * @author Paul Angell

 * @copyright Copyright (c) 2014, Paul Angell

 * @link http://www.widgetmedia.co/shortcode-to-display-pages/

 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

 */

 

 

/**

 * To Customize, use the following filters:

 *

 * `display_pages_shortcode_args`

 * For customizing the $args passed to WP_Query

 *

 * `display_pages_shortcode_output`

 * For customizing the output of individual posts.

 * Example: https://gist.github.com/1175575#file_display_pages_shortcode_output.php

 *

 * `display_pages_shortcode_wrapper_open` 

 * display_pages_shortcode_wrapper_close`

 * For customizing the outer markup of the whole listing. By default it is a <ul> but

 * can be changed to <ol> or <div> using the 'wrapper' attribute, or by using this filter.

 * Example: https://gist.github.com/1270278

 */ 

 

// Create the shortcode

add_shortcode( 'display-pages', 'be_display_pages_shortcode' );

function be_display_pages_shortcode( $atts ) {



	// Original Attributes, for filters

	$original_atts = $atts;



	// Pull in shortcode attributes and set defaults

	$atts = shortcode_atts( array(

		'author'              => '',

		'date_format'         => 'F j, Y',

		'id'                  => false,

		'image_size'          => false,

		'include_content'     => false,

		'include_date'        => false,

		'include_excerpt'     => false,

		'meta_key'            => '',

		'no_posts_message'    => '',

		'offset'              => 0,

		'order'               => 'DESC',

		'orderby'             => 'date',

		'post_parent'         => false,

		'post_status'         => 'publish',

		'post_type'           => 'page',

		'posts_per_page'      => '10',

		'tag'                 => '',

		'tax_operator'        => 'IN',

		'tax_term'            => false,

		'taxonomy'            => false,

		'wrapper'             => 'ul',

	), $atts );



	$author = sanitize_text_field( $atts['author'] );

	$date_format = sanitize_text_field( $atts['date_format'] );

	$id = $atts['id']; // Sanitized later as an array of integers

	$image_size = sanitize_key( $atts['image_size'] );

	$include_content = (bool)$atts['include_content'];

	$include_date = (bool)$atts['include_date'];

	$include_excerpt = (bool)$atts['include_excerpt'];

	$meta_key = sanitize_text_field( $atts['meta_key'] );

	$no_posts_message = sanitize_text_field( $atts['no_posts_message'] );

	$offset = intval( $atts['offset'] );

	$order = sanitize_key( $atts['order'] );

	$orderby = sanitize_key( $atts['orderby'] );

	$post_parent = $atts['post_parent']; // Validated later, after check for 'current'

	$post_status = $atts['post_status']; // Validated later as one of a few values

	$post_type = sanitize_text_field( $atts['post_type'] );

	$posts_per_page = intval( $atts['posts_per_page'] );

	$tag = sanitize_text_field( $atts['tag'] );

	$tax_operator = $atts['tax_operator']; // Validated later as one of a few values

	$tax_term = sanitize_text_field( $atts['tax_term'] );

	$taxonomy = sanitize_key( $atts['taxonomy'] );

	$wrapper = sanitize_text_field( $atts['wrapper'] );



	

	// Set up initial query for post

	$args = array(

		'order'               => $order,

		'orderby'             => $orderby,

		'post_type'           => explode( ',', $post_type ),

		'posts_per_page'      => $posts_per_page,

		'tag'                 => $tag,

	);

	

	// Meta key (for ordering)

	if( !empty( $meta_key ) )

		$args['meta_key'] = $meta_key;

	

	// If Post IDs

	if( $id ) {

		$posts_in = array_map( 'intval', explode( ',', $id ) );

		$args['post__in'] = $posts_in;

	}

	

	// Post Author

	if( !empty( $author ) )

		$args['author_name'] = $author;

		

	// Offset

	if( !empty( $offset ) )

		$args['offset'] = $offset;

	

	// Post Status	

	$post_status = explode( ', ', $post_status );		

	$validated = array();

	$available = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash', 'any' );

	foreach ( $post_status as $unvalidated )

		if ( in_array( $unvalidated, $available ) )

			$validated[] = $unvalidated;

	if( !empty( $validated ) )		

		$args['post_status'] = $validated;

	

	

	// If taxonomy attributes, create a taxonomy query

	if ( !empty( $taxonomy ) && !empty( $tax_term ) ) {

	

		// Term string to array

		$tax_term = explode( ', ', $tax_term );

		

		// Validate operator

		if( !in_array( $tax_operator, array( 'IN', 'NOT IN', 'AND' ) ) )

			$tax_operator = 'IN';

					

		$tax_args = array(

			'tax_query' => array(

				array(

					'taxonomy' => $taxonomy,

					'field'    => 'slug',

					'terms'    => $tax_term,

					'operator' => $tax_operator

				)

			)

		);

		

		// Check for multiple taxonomy queries

		$count = 2;

		$more_tax_queries = false;

		while( 

			isset( $original_atts['taxonomy_' . $count] ) && !empty( $original_atts['taxonomy_' . $count] ) && 

			isset( $original_atts['tax_' . $count . '_term'] ) && !empty( $original_atts['tax_' . $count . '_term'] ) 

		):

		

			// Sanitize values

			$more_tax_queries = true;

			$taxonomy = sanitize_key( $original_atts['taxonomy_' . $count] );

	 		$terms = explode( ', ', sanitize_text_field( $original_atts['tax_' . $count . '_term'] ) );

	 		$tax_operator = isset( $original_atts['tax_' . $count . '_operator'] ) ? $original_atts['tax_' . $count . '_operator'] : 'IN';

	 		$tax_operator = in_array( $tax_operator, array( 'IN', 'NOT IN', 'AND' ) ) ? $tax_operator : 'IN';

	 		

	 		$tax_args['tax_query'][] = array(

	 			'taxonomy' => $taxonomy,

	 			'field' => 'slug',

	 			'terms' => $terms,

	 			'operator' => $tax_operator

	 		);

	

			$count++;

			

		endwhile;

		

		if( $more_tax_queries ):

			$tax_relation = 'AND';

			if( isset( $original_atts['tax_relation'] ) && in_array( $original_atts['tax_relation'], array( 'AND', 'OR' ) ) )

				$tax_relation = $original_atts['tax_relation'];

			$args['tax_query']['relation'] = $tax_relation;

		endif;

		

		$args = array_merge( $args, $tax_args );

	}

	

	// If post parent attribute, set up parent

	if( $post_parent ) {

		if( 'current' == $post_parent ) {

			global $post;

			$post_parent = $post->ID;

		}

		$args['post_parent'] = intval( $post_parent );

	}

	

	// Set up html elements used to wrap the posts. 

	// Default is ul/li, but can also be ol/li and div/div

	$wrapper_options = array( 'ul', 'ol', 'div' );

	if( ! in_array( $wrapper, $wrapper_options ) )

		$wrapper = 'ul';

	$inner_wrapper = 'div' == $wrapper ? 'div' : 'li';



	

	$listing = new WP_Query( apply_filters( 'display_pages_shortcode_args', $args, $original_atts ) );

	if ( ! $listing->have_posts() )

		return apply_filters( 'display_pages_shortcode_no_results', wpautop( $no_posts_message ) );

		

	$inner = '';

	while ( $listing->have_posts() ): $listing->the_post(); global $post;

		

		$image = $date = $excerpt = $content = '';
		
		$toptitle = '<h2 class="widget_spec"><a href="' . get_category_link($categories) . apply_filters( 'the_title', get_the_title() ) . '</a></h2>';

		

		$title = '<h2><a href="' . apply_filters( 'the_permalink', get_permalink() ) . '">' . apply_filters( 'the_title', get_the_title() ) . '</a></h2>';

		

		if ( $image_size && has_post_thumbnail() )  

			$image = '<div class="imgwrap"><a href="' . get_permalink() . '">' . get_the_post_thumbnail( $post->ID, $image_size ) . '</a></div>';

			

		if ( $include_date ) 

			$date = ' <p class="meta">' . get_the_date( $date_format ) . '</p>';

		

		if ( $include_excerpt ) 

			$excerpt = '<p class="excerpt">' . vergo_excerpt( get_the_excerpt(), '150') . '</p><p class="meta more"><a href="' . get_permalink() . '" class="fr">Read More <i class="icon-circle-arrow-right"></i></a></p>';

			

		if( $include_content )

			$content = '<p class="teaser">' . apply_filters( 'the_content', get_the_content() ) . '</p>'; 

		

		$class = array( 'widgetcol_three' );

		$class = apply_filters( 'display_pages_shortcode_post_class', $class, $post, $listing );

		$output = '<' . $inner_wrapper . ' class="' . implode( ' ', $class ) . '"><div class="widgetcol_big">' . $image . $title . $date . $excerpt . $content . '</div></' . $inner_wrapper . '>';

		

		// If post is set to private, only show to logged in users

		if( 'private' == get_post_status( $post->ID ) && !current_user_can( 'read_private_posts' ) )

			$output = '';

		

		$inner .= apply_filters( 'display_pages_shortcode_output', $output, $original_atts, $image, $title, $date, $excerpt, $inner_wrapper, $content, $class );

		

	endwhile; wp_reset_postdata();

	

	$open = apply_filters( 'display_pages_shortcode_wrapper_open', '<' . $wrapper . '>', $original_atts );

	$close = apply_filters( 'display_pages_shortcode_wrapper_close', '</' . $wrapper . '>', $original_atts );

	$return = $open . $inner . $close;



	return $return;

}

