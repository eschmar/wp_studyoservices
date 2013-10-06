<?php
/*
Plugin Name: Studyo Services
Plugin URI: https://github.com/eschmar/wp_studyoservices
Description: Custom post type for Services presentation
Version: 1.0
Author: Marcel Eschmann
Author URI: https://github.com/eschmar
License: MIT
*/


/***************************************************************************
 * ACTIVATE THUMBNAIL SUPPORT
 ***************************************************************************/
add_theme_support( 'post-thumbnails' );


/***************************************************************************
 * CUSTOM SLIDER THUMBNAIL SIZE
 ***************************************************************************/
add_image_size( 'service-post-list-image', 50, 50, true );

/***************************************************************************
 * I18N / L10N
 ***************************************************************************/
function studyo_services_i18n() {
	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain( 'studyoservices', false, $plugin_dir );
}
add_action('plugins_loaded', 'studyo_services_i18n');


/***************************************************************************
 * REGISTER CUSTOM POST TYPE
 ***************************************************************************/
function studyo_services_register() {
	$args = array(
		'labels' => array(
			'name' => '',
			'singular_name'      => __( 'Service', 'studyoservices' ),
			'add_new'            => __( 'Add New', 'studyoservices' ),
			'add_new_item'       => __( 'Add New Service', 'studyoservices' ),
			'edit_item'          => __( 'Edit Service', 'studyoservices' ),
			'new_item'           => __( 'New Service', 'studyoservices' ),
			'all_items'          => __( 'All Services', 'studyoservices' ),
			'view_item'          => __( 'View Service', 'studyoservices' ),
			'search_items'       => __( 'Search Services', 'studyoservices' ),
			'not_found'          => __( 'No services found', 'studyoservices' ),
			'not_found_in_trash' => __( 'No services found in the Trash', 'studyoservices' ), 
			'parent_item_colon'  => '',
			'menu_name'          => 'Services'
		),
		'description'   => 'Holds all services',
		'public'        => true,
		'menu_position' => 4,
		'supports'      => array( 'title', 'editor', 'thumbnail' ),
		'has_archive'   => false
	);
	register_post_type( 'service', $args );	
}
add_action( 'init', 'studyo_services_register' );


/***************************************************************************
 * REGISTER TAXONOMY
 ***************************************************************************/
function studyo_services_taxonomies() {
	$args = array(
		'labels' => array(
			'name'              => __( 'Service Categories', 'studyoservices' ),
			'singular_name'     => __( 'Service Category', 'studyoservices' ),
			'search_items'      => __( 'Search Service Categories', 'studyoservices' ),
			'all_items'         => __( 'All Service Categories', 'studyoservices' ),
			'parent_item'       => __( 'Parent Service Category', 'studyoservices' ),
			'parent_item_colon' => __( 'Parent Service Category:', 'studyoservices' ),
			'edit_item'         => __( 'Edit Service Category', 'studyoservices' ), 
			'update_item'       => __( 'Update Service Category', 'studyoservices' ),
			'add_new_item'      => __( 'Add New Service Category', 'studyoservices' ),
			'new_item_name'     => __( 'New Service Category', 'studyoservices' ),
			'menu_name'         => __( 'Service Categories', 'studyoservices' ),
		),
		'hierarchical' => true
	);
	register_taxonomy( 'service_category', 'service', $args );
}
add_action('init', 'studyo_services_taxonomies', 0);


/***************************************************************************
 * FILTERABLE BY TAXONOMY
 ***************************************************************************/
function studyo_services_restrict_manage_posts() {
	global $typenow;
	$taxonomy = $typenow.'_category';
	if( $typenow == "service" ){
		$filters = array($taxonomy);
		foreach ($filters as $tax_slug) {
			$tax_obj = get_taxonomy($tax_slug);
			$tax_name = $tax_obj->labels->name;
			$terms = get_terms($tax_slug);
			echo "<select name='$tax_slug' id='$tax_slug' class='postform'>";
			echo "<option value=''>".__( 'All Categories' )."</option>";
			foreach ($terms as $term) { echo '<option value='. $term->slug, $_GET[$tax_slug] == $term->slug ? ' selected="selected"' : '','>' . $term->name .' (' . $term->count .')</option>'; }
			echo "</select>";
		}
	}
}
add_action( 'restrict_manage_posts', 'studyo_services_restrict_manage_posts' );


/***************************************************************************
 * ADD CUSTOM COLUMNS
 ***************************************************************************/
function studyo_services_custom_columns( $columns ) {
	// Add new columns
	$columns['service_order'] = __( 'Order' );
	$columns['service-category'] = __( 'Category' );
	$columns['featured_image'] = __( 'Featured Image' );

	// Move date column to the end
	$temp = $columns['date'];
	unset($columns['date']);
	$columns['date'] = $temp;

	return $columns;
}
add_filter('manage_edit-service_columns', 'studyo_services_custom_columns');


/***************************************************************************
 * ISNERT CUSTOM COLUMNS CONTENT
 ***************************************************************************/
function studyo_services_custom_columns_content( $column, $post_id ) {
	global $post;
	if ($column == 'featured_image') {
		the_post_thumbnail('service-post-list-image');
	}else if ($column == 'service-category') {
		$terms = get_the_terms($post_id, 'service_category');
		if (empty($terms)) {
			echo '-';
		}else {
			$output = array();
			foreach ($terms as $t) {
				array_push($output, $t->slug);
			}
			echo join(', ', $output);
		}
	}else if ($column == 'service_order') {
		echo get_post_meta($post_id, 'service_order', true);
	}
}
add_action( 'manage_service_posts_custom_column', 'studyo_services_custom_columns_content', 10, 2 );


/***************************************************************************
 * SORTING META BOX
 ***************************************************************************/
// Add meta box
add_action( 'add_meta_boxes', 'studyo_services_meta_box_order' );
function studyo_services_meta_box_order() {
    add_meta_box( 
        'service_order_box',
        __( 'Attributes' ),
        'service_attributes_box_content',
        'service',
        'side'
    );
}

// Content of the meta box
function service_attributes_box_content( $post ) {
	wp_nonce_field( plugin_basename( __FILE__ ), 'service_attributes_box_content_nonce' );

	$str_order = __('Order');
	$str_order_description = __('Services are displayed in ascending order.', 'studyoservices');
	$str_css = __('CSS Classes', 'studyoservices');
	$str_css_description = __('CSS classes added to this service.', 'studyoservices');

	$value = get_post_meta($post->ID, 'service_order', true);
	$caption = get_post_meta($post->ID, 'service_classes', true);

	$form =
<<<EOT
	$str_order
	<input type="text" name="service_order" id="slider_order" value="$value"/><br/>
	<i>$str_order_description</i><br/><br/>
	$str_css:
	<input type="text" name="service_classes" id="slider_caption_classes" value="$caption"/><br/>
	<i>$str_css_description</i><br/><br/>
EOT;

	echo $form;
}

// Save data from metabox
function service_attributes_box_save( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
	return;

	if ( !wp_verify_nonce( $_POST['service_attributes_box_content_nonce'], plugin_basename( __FILE__ ) ) )
	return;

	if ( 'page' == $_POST['post_type'] ) {
		if ( !current_user_can( 'edit_page', $post_id ) )
		return;
	} else {
		if ( !current_user_can( 'edit_post', $post_id ) )
		return;
	}

	$service_order = $_POST['service_order'];
	$service_classes = $_POST['service_classes'];

	update_post_meta( $post_id, 'service_order', $service_order );
	update_post_meta( $post_id, 'service_classes', $service_classes );
}
add_action( 'save_post', 'service_attributes_box_save' );


/***************************************************************************
 * OUTPUT SLIDER HTML
 ***************************************************************************/
function studyo_services_output($slug, $wrap_class = '', $ul_class = '', $img_class = '' ) {

	$args = array(
		'post_type' => 'service',
		'service_category' => $slug,
		'order' => 'ASC',
		'meta_key' => 'service_order',
		'orderby' => 'meta_value_num'
	);

	$services = new WP_Query($args);

	$output = '<div class="studyo_services '.$wrap_class.'"><ul class="'.$ul_class.'">';

	if ($services->have_posts()) {
		while ($services->have_posts()) {
			$services->the_post();
			$service_classes = get_post_meta( get_the_ID(), 'service_classes', true );
			$output .= '<li class="'.$service_classes.'">';
			$attachement = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full');
			$output .= '<div class="'.$img_class.'"><img src="'.$attachement[0].'" alt="'.get_the_title().'"></div>';
			$content = get_the_content();
			if (!empty($content)) {
				$output .= '<p>'.$content.'</p>';
			}
			$output .= '</li>';
		}
	}

	$output .= '</ul></div>';
	echo $output;
}

/***************************************************************************
 * CONTEXTUAL HELP (UPPER RIGHT CORNER "HELP")
 ***************************************************************************/
function studyo_service_contextual_help( $contextual_help, $screen_id, $screen ) { 
	if ( 'edit-service' == $screen->id ) {

		$contextual_help = '<h2>Output</h2>
		<p>Use this function to output a slider in your template:</p>
		<i>studyo_services_output($slug, $wrap_class = "", $ul_class = "", $img_class = "" );</i><br/><br/>
		<a href="https://github.com/eschmar/wp_studyoservices" target="_blank">More information here</a>';

	} elseif ( 'service' == $screen->id ) {

		$contextual_help = '<h2>Editing Services</h2>
		<p><strong>Title: </strong>Heading, Image alt attribute</p>
		<p><strong>Content: </strong>Text</p>
		<p><strong>Featured Image: </strong>Service Image</p>
		<a href="https://github.com/eschmar/wp_studyoservices" target="_blank">More information here</a>';

	}
	return $contextual_help;
}
add_action( 'contextual_help', 'studyo_service_contextual_help', 10, 3 );