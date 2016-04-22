<?php defined('ABSPATH') || die(-1);

/**
 * @package Slideshow Manager
 * @copyright BC Libraries Coop 2013
 *
 **/
/**
 * Plugin Name: Slideshow Manager
 * Description: Slideshow collection manager. User interaction interface.  NETWORK ACTIVATE.
 * Author: Erik Stainsby, Roaring Sky Software
 * Version: 0.3.4
 **/
 
//require_once( 'inc/slide-custom-post-type.php' );
 
if ( ! class_exists( 'SlideshowManager' )) :

class SlideshowManager {

	var $slug = 'slideshow';
	var $sprite = '';

	public function __construct() {
		add_action( 'init', array( &$this, '_init' ));
	//	add_action( 'init', array( &$this, 'create_slide_post_type'));
	}

	public function _init() {
	
		if( is_admin() ) 
		{
			$this->sprite = plugins_url('/imgs/signal-sprite.png',dirname(__FILE__));
		
			add_action( 'wp_ajax_slideshow_add_text_slide',array(&$this,'slideshow_add_text_slide'));
			add_action( 'wp_ajax_precheck_slideshow_collection_name',array(&$this,'slideshow_precheck_collection_name'));
			add_action( 'wp_ajax_slideshow-fetch-img-meta',array(&$this,'slideshow_fetch_img_meta_callback'));
			add_action( 'wp_ajax_slideshow-fetch-collection',array(&$this,'slideshow_fetch_collection'));
			add_action( 'wp_ajax_slideshow-save-slide-collection',array(&$this,'slideshow_save_collection_handler'));
			add_action( 'wp_ajax_slideshow-delete-slide-collection',array(&$this,'slideshow_delete_collection_handler'));
		}
	}

	public function slideshow_manager_page() {
	
		global $wpdb;
		
		$thumb_w = get_option('thumbnail_size_w',true);
		$thumb_h = get_option('thumbnail_size_h',true);

		$med_w = get_option('medium_size_w',true);
		$med_h = get_option('medium_size_h',true);

		$large_w = get_option('large_size_w',true);
		$large_h = get_option('large_size_h',true);
		
		$out = array();
		$out[] = '<div class="wrap">';
		
		$out[] = '<div id="icon-options-general" class="icon32">';
		$out[] = '<br>';
		$out[] = '</div>';
		
		$out[] = '<h2>Slideshow Collection Manager</h2>';
		
		$out[] = '<p>&nbsp;</p>';
		
		$out[] = '<p>This page supports the creation of Slideshows: a series of images / text slides which rotate automatically from one to the next. A slideshow can comprise up to five slides (for best viewing effect). An image suitable for use in the slideshow is 1000 pixels wide x 300 pixels high. Images should be prepared under the Media menu, and must be given a Media Tag of: <b>slide</b>.</p>';
		
		$out[] = '<table class="slideshow-header-controls">';
		$out[] = '<tr><td class="slideshow-name">';
		
		$out[] = '<a class="button add-new" href="">Add new</a>&nbsp;<input type="text" class="slideshow-collection-name" name="slideshow-collection-name" value="" placeholder="Enter a name for a new slideshow">';
		
		$out[] = '</td><td class="slideshow-gutter">&nbsp;</td><td class="slideshow-controls">';
		
		$out[] = '<a href="" class="button button-primary slideshow-save-collection-btn">Save collection</a>';
		$out[] = '<a href="" class="button slideshow-delete-collection-btn">Delete the loaded slideshow</a>';
		
		$out[] = '</td></tr>';
		
		$out[] = '<tr><td class="slideshow-name">';

		$out[] = self::slideshow_collection_selector();
		
		$out[] = '</td><td class="slideshow-gutter">&nbsp;</td><td class="slideshow-signal-preload">';
		
		$out[] = '<div id="collection-name-signal" class="slideshow-signals"><img class="signals-sprite" src="'.$this->sprite.'"></div>';

		$out[] = '</td></tr>';
		$out[] = '</table>';
				
		$out[] = self::slideshow_droppable_table();
		
		$out[] = '<h3 class="slideshow-runtime-heading">Runtime information:</h3>';
		$out[] = '<div class="slideshow-runtime-information"></div>';
		
		
		$out[] = self::text_slide_create_form();
		$out[] = self::quick_set_layout_controls();
		
				
		$out[] = '</td><!-- .slideshow-dropzone -->';
		
		$out[] = '<td class="slideshow-gutter">&nbsp;</td>';
		$out[] = '<td class="slideshow-dragzone">';
		
		
		$out[] = '<table class="slideshow-drag-table">';
		$out[] = '<tr><th class="alignleft">Your Slide Images</th></tr>';
		$out[] = '<tr><td id="slide-remove-local" class="slideshow-draggable-items returnable local">';
		
		$sql = "SELECT * FROM $wpdb->posts WHERE post_type='attachment' AND ID IN (SELECT object_id FROM $wpdb->term_relationships tr JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id=tt.term_taxonomy_id JOIN $wpdb->terms tm ON tt.term_id =tm.term_id WHERE tt.taxonomy = 'media_tag' AND tm.name='slide') ORDER BY post_title";
		
		$res = $wpdb->get_results($sql);
		
		foreach( $res as $r ) {
		
			$title = $r->post_title;			
			$file = get_post_meta($r->ID,'_wp_attached_file', true);
			
			$d = date_parse($r->post_date);
			$folder = sprintf('/files/%4d/%02d/',$d['year'],$d['month']);
			
			$sql = "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_wp_attachment_metadata' AND post_id=$r->ID";
			$meta = $wpdb->get_var($sql);	
			$meta = maybe_unserialize($meta);
			
			$dragslide = $meta['sizes']['drag-slide'];
			$thumbnail = $meta['sizes']['thumbnail'];
			if( array_key_exists('medium',$meta['sizes'])) {
				$medium = $meta['sizes']['medium'];
			}
			$large = $meta['file'];
			
						
/*			$out[] = sprintf('<div class="draggable" data-img-id="%d" data-img-caption="%s"><img id="thumb%d" src="%s%s" width="%d" height="%d" class="thumb">',$r->ID,$title,$r->ID,$folder,$thumbnail['file'], $thumbnail['width'],$thumbnail['height']);*/
			$out[] = sprintf('<div class="draggable" data-img-id="%d" data-img-caption="%s"><img id="thumb%d" src="%s%s" height="%d" width="%d" class="thumb">',$r->ID,$title,$r->ID,$folder,$medium['file'], $medium['height'], $medium['width']);
			
			$out[] = sprintf('<img id="slotview%d" src="%s%s" width="%d" height="%d" class="slotview"></div>',$r->ID,$folder,$dragslide['file'],$dragslide['width'],$dragslide['height']);
		}
		
		$out[] = '</td></tr>';

		//Network Shared Media (NSM) section
		$out[] = '<th class="alignleft shared-slides">Shared Slide Images</th>';
		$out[] = '<tr><td id="slide-remove-shared" class="slideshow-draggable-items returnable shared">';

		// Return to shared media site context
		switch_to_blog(1);

		$fetched = self::fetch_network_shared_media_slides();
		$out = array_merge($out, $fetched);

		//BC Slides
		$out[] = '<tr><th class="alignleft bc-slides">British Columbia</th>';
		$out[] = '<tr><td id="slide-remove-shared" class="slideshow-draggable-items returnable shared">';

		$fetched = self::fetch_network_shared_media_slides( 'BC' );
		$out = array_merge($out, $fetched);

		$out[] = '</td></tr>';

		//MB Slides
		$out[] = '<tr><th class="alignleft mb-slides">Manitoba</th>';
		$out[] = '<tr><td id="slide-remove-shared" class="slideshow-draggable-items returnable shared">';

		$fetched = self::fetch_network_shared_media_slides( 'MB' );
		$out = array_merge($out, $fetched);

		$out[] = '</td></tr>';
		
		$out[] = '</table>';
		$out[] = '</form><!-- .slideshow-definition-form -->';
		
		
		$out[] = '</td><!-- .slideshow-dragzone -->';
		$out[] = '</tr><!-- .master-row -->';
		$out[] = '</table><!-- .slideshow-drag-drop-layout -->';
		
		
		$out[] = '<div class="slideshow-signals-preload">';
		$out[] = '<img src="'.$this->sprite.'" width="362" height="96">';
		$out[] = '</div>';
		
		echo implode("\n",$out);

		//Restore later
		restore_current_blog();
		
	}

	public function fetch_network_shared_media_slides( $region = "" ) {

		/**	fetch NSM images with Media Tag: 'slide'
		*
		*	THIS IS HARDCODED TO FETCH FROM blog 1, 
		*	which is the designated Network Shared Media instance. 
		*
		*	The other significant difference is that the 
		*	image repos URL is distinct from the networked sites:
		*	/wp-uploads/ yadda yadda is the repository address:  sites use /files/ yadda yadda.
		* 
		* 
		* @param string $region value of slide_region post meta [default empty, BC, MB]
		* @return array Returns array of markup-wrapped slide items to be appended to output
		*
		**/

		global $wpdb;

		$args = array(
			'post_type' => 'attachment',
			'tax_query' => array(
												array(
														'taxonomy' => 'media_tag',
														'field' => 	'term_taxonomy_id',
														'operator' => 'EXISTS',
													)),
			'meta_key' => 'slide_region',
			'meta_value' => $region,
			'orderby' => 'title',
			'posts_per_page' => -1,
		);
		if ( empty($region) ) $args['meta_compare'] = 'NOT EXISTS';

		$get_slides = get_posts( $args );

		wp_reset_postdata(); //just in case
		if (empty($get_slides)) return array('<div class="slide-no-results"><p>No slides</p></div>');   //we got nothing with the post meta
		
		foreach( $get_slides as $r ) {
		
			$title = $r->post_title;
			$id = $r->ID;
		
			$select = "SELECT meta_value FROM wp_postmeta WHERE post_id = $r->ID AND meta_key = '_wp_attached_file'";
			$file = $wpdb->get_var($select);
			
			$site = site_url();
			
			$d = date_parse($r->post_date);
			$folder = sprintf('%s/wp-uploads/%4d/%02d/',$site,$d['year'],$d['month']);
			
			$sql = "SELECT meta_value FROM wp_postmeta WHERE post_id=$r->ID AND meta_key = '_wp_attachment_metadata'";
			
			$meta = $wpdb->get_var($sql);
			$meta = maybe_unserialize($meta);

			$dragslide = $meta['sizes']['drag-slide'];
			$thumbnail = $meta['sizes']['thumbnail'];
			$medium = $meta['sizes']['medium'];
			$full = $meta['file'];

			//In case we need the full size, get only filename (drop $folder in return array as well)
			preg_match("/[0-9]+\/[0-9]+\/([\-a-z0-9\_\.]+)/", $full, $matched);
			$large = $matched[1];
			
			$slides[] = sprintf('<div class="draggable" data-img-id="%d" data-img-caption="%s"><img id="thumb%d" src="%s%s" height="%d" width="%d" class="thumb"><p class="caption">%s</p>',$r->ID,$title,$r->ID,$folder,$medium['file'],$medium['height'],$medium['width'],$title);
			
			$slides[] = sprintf('<img id="slotview%d" src="%s%s" width="%d" height="%d" class="slotview"></div>',$r->ID,$folder,$dragslide['file'],$dragslide['width'],$dragslide['height']);

		}

		return $slides;
	
}
	
	private function slideshow_collection_selector() {
		
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'slideshows';
		$sql = "SELECT * FROM $table_name ORDER BY title";
		$res = $wpdb->get_results($sql);
		
		$out = array();
		$out[] = '<select data-placeholder="... or choose a past slideshow to reload" name="slideshow_select" id="slideshow_select" class="slideshow_select chzn-select">';
		
		$out[] = '<option value=""></option>';

		foreach($res as $r) {	
			if( $r->is_active == "1" ) {
				$out[] = '<option value="'.$r->id .'" selected="selected">'.$r->title.'</option>';
			}
			else {
				$out[] = '<option value="'.$r->id .'" >'.$r->title.'</option>';
			}
		}
		
		$out[] = '</select>';
		
		return implode( "\n", $out);
	}
	
	private function slideshow_droppable_table() {
		
		$out = array();
		
		$out[] = '<table class="slideshow-drag-drop-layout">';
		$out[] = '<tr class="master-row">';
		$out[] = '<td class="slideshow-dropzone">';
		
		$out[] = '<table class="slideshow-sortable-rows">';
		
		$out[] = '<tr class="head-row"><th></th><th>';
		
		$out[] = '<div class="slideshow-controls-right"><input type="checkbox" id="slideshow-is-active-collection" class="slideshow-is-active-collection" value="1"> <label for="slideshow-is-active-collection" class="slideshow-activate-collection">This is the active slideshow</label></div>';
		
		$out[] = 'Caption/Title<br/><span class="slideshow-slide-link-header">Slide Link</span>';
					
		$out[] = '</th></tr>';
					
		for( $i=0;$i<5;$i++) {
			$out[] = '<tr id="row'.$i.'" class="slideshow-collection-row draggable droppable" id="dropzone'.$i.'"><td class="thumbbox">&nbsp;</td>';
			$out[] = '<td class="slideshow-slide-title">';
			$out[] = '<div class="slide-title"><span class="placeholder">Caption/Title</span></div>';
			$out[] = '<div class="slide-link"><span class="placeholder">Link URL</span></div></td></tr>';
		}
		
		$out[] = '</table><!-- .slideshow-droppable-rows -->';
		
		$out[] = '<div id="runtime-signal" class="slideshow-signals"><img src="'.$this->sprite.'" class="signals-sprite"></div>';
		
		return implode("\n",$out);
	}
		
	
	public function target_pages_selector() {
	
		global $wpdb;
		
		$sql = "SELECT ID, post_title, post_type, guid FROM $wpdb->posts WHERE post_status = 'publish' AND (post_type='page' OR post_type='post') ORDER BY post_title";
		$res = $wpdb->get_results( $sql );
		
		$out = array('<select data-placeholder="Link to a post or page..." id="slideshow_page_selector" name="slideshow_page_selector" class="slideshow-page-selector chzn-select">');
		$out[] = '<option value=""></option>';
		foreach( $res as $r ) {
			$out[] = '<option value="'.$r->ID.'" class="'.$r->post_type.'" data-guid="'.$r->guid.'">'.$r->post_title.'</option>';
		}
		$out[] = '</select>';
		
		return implode("\n",$out);
	}
		
	
	private function text_slide_create_form() {
	
		$out = array();
		$out[] = '<table class="slideshow-text-slide-create">';
		$out[] = '<tr>';
		$out[] = '<td class="slideshow-label"><h3>Add text-only slide</h3></td>';
		$out[] = '</tr>';

		$out[] = '<tr>';
		$out[] = '<td><input type="text" id="slideshow-text-slide-heading" class="slideshow-text-slide-heading" name="slideshow-text-slide-heading" value="" placeholder="Headline"></td>';
		$out[] = '</tr>';

		$out[] = '<tr>';
		$out[] = '<td><textarea id="slideshow-text-slide-content" class="slideshow-text-slide-content" name="slideshow-text-slide-content" placeholder="Message text"></textarea></td>';
		$out[] = '</tr>';
		
		$out[] = '<tr>';
		$out[] = '<td class="slideshow-text-slide-link-box">';
		
		$out[] = self::target_pages_selector();
		
		$out[] = '</td>';
		$out[] = '</tr>';
		
		$out[] = '<tr>';
		$out[] = '<td class="slideshow-text-slide-save-box">';
		$out[] = 'Items listed in blue are blog posts. Items in green are pages.';
		$out[] = '<a href="javascript:void(0);" class="button slideshow-text-slide-cancel-btn">Cancel</a>';
		$out[] = '<a href="javascript:void(0);" class="button slideshow-text-slide-save-btn">Add the slide</a>';
		$out[] = '</td>';
		$out[] = '</tr>';

		$out[] = '</table><!-- .slideshow-text-slide-create -->';
		
		return implode("\n",$out);
	}
		
	private function quick_set_layout_controls() {
		
		$out = array();
		
		/**
		*	Quick set modes and effects
		*
		*	this is the matrix at the bottom of the form for setting 
		*	thumbnails style and slide transition direction/fade 
		*
		*	Makes no db calls to reset state; this is handled in 
		*	javascript on collection reloading.
		**/
		
		
		$out[] = '<table class="slideshow-layout-controls">';
		
		$out[] = '<tr>';
		$out[] = '<td colspan="3">';
		$out[] = '<h3>Display Captions</h3>';
		$out[] = '</td>';
		$out[] = '</tr>';
		
		$out[] = '<tr>';
		$out[] = '<td>&nbsp;</td>';
		$out[] = '<td colspan="2" align="left">';
		$out[] = '<input type="checkbox" id="slideshow-show-captions" value="true">&nbsp;<label for="slideshow-show-captions">Enable caption display for slideshow</label>';
		$out[] = '</td>';
		$out[] = '</tr>';
		
		
		
		$out[] = '<tr>';
		$out[] = '<td colspan="3">';
		$out[] = '<h3>Slideshow Layout</h3>';
		$out[] = '</td>';
		$out[] = '</tr>';
		
		$out[] = '<tr>';
		$out[] = '<td>'; 
		
			$out[] = '<table class="slideshow-control">';
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<img src="'.plugins_url('/imgs/NoThumbnails.png',dirname(__FILE__)) .'" data-id="slideshow-control-1" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-layout" id="slideshow-control-1" value="no-thumb">';
			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<label for="slideshow-control-1">No thumbnails</label>';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td class="slideshow-control-annotation">'; 
				$out[] = 'Previous / Next arrows';
			$out[] = '</td>';
			$out[] = '</tr>';
			$out[] = '</table><!-- .slideshow-control -->';
		
		$out[] = '</td>';
		$out[] = '<td>'; 
		
			$out[] = '<table class="slideshow-control">';
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<img src="'.plugins_url('/imgs/VerticalThumbnails.png',dirname(__FILE__)) .'" data-id="slideshow-control-2" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-layout" id="slideshow-control-2" value="vertical">';
			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<label for="slideshow-control-2">Vertical thumbnails</label>';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td class="slideshow-control-annotation">'; 
				$out[] = 'Clickable thumbnails displayed vertically on the left-hand side';
			$out[] = '</td>';
			$out[] = '</tr>';
			$out[] = '</table><!-- .slideshow-control -->';
			
		$out[] = '</td>';
		$out[] = '<td>'; 
		
			$out[] = '<table class="slideshow-control">';
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<img src="'.plugins_url('/imgs/HorizontalThumbnails.png',dirname(__FILE__)) .'" data-id="slideshow-control-3" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-layout" id="slideshow-control-3" value="horizontal">';
			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<label for="slideshow-control-3">Horizontal thumbnails</label>';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td class="slideshow-control-annotation">'; 
				$out[] = 'Clickable thumbnails displayed horizontally below the slideshow';
			$out[] = '</td>';
			$out[] = '</tr>';
			$out[] = '</table><!-- .slideshow-control -->';
		
		$out[] = '</td>';
		$out[] = '</tr>';


		$out[] = '<tr>';
		$out[] = '<td colspan="3">';
		$out[] = '<h3>Transitions</h3>';
		$out[] = '</td>';
		$out[] = '</tr>';

		$out[] = '<tr>';
		$out[] = '<td>'; 
		
			$out[] = '<table class="slideshow-control">';
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<img src="'.plugins_url('/imgs/HorizontalSlide.png',dirname(__FILE__)) .'" data-id="slideshow-control-4" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-transition" id="slideshow-control-4" value="horizontal">';
			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<label for="slideshow-control-4">Slide Horizontal</label>';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td class="slideshow-control-annotation">'; 
				$out[] = 'Slides enter from the right and exit to the left';
			$out[] = '</td>';
			$out[] = '</tr>';
			$out[] = '</table><!-- .slideshow-control -->';
		
		$out[] = '</td>';
		$out[] = '<td>'; 
		
			$out[] = '<table class="slideshow-control">';
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<img src="'.plugins_url('/imgs/VerticalSlide.png',dirname(__FILE__)) .'" data-id="slideshow-control-5" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-transition" id="slideshow-control-5" value="vertical">';
			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<label for="slideshow-control-5">Slide Vertical</label>';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td class="slideshow-control-annotation">'; 
				$out[] = 'Slides enter below and exit above';
			$out[] = '</td>';
			$out[] = '</tr>';
			$out[] = '</table><!-- .slideshow-control -->';
			
		$out[] = '</td>';
		$out[] = '<td>'; 
		
			$out[] = '<table class="slideshow-control">';
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<img src="'.plugins_url('/imgs/Fade.png',dirname(__FILE__)) .'" data-id="slideshow-control-6" class="slideshow-control-img">';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td class="radio-box">';
				$out[] = '<input type="radio" name="slideshow-transition" id="slideshow-control-6" value="fade">';
			$out[] = '</td>';
			$out[] = '<td>'; 
				$out[] = '<label for="slideshow-control-6">Cross-fade</label>';
			$out[] = '</td>';
			$out[] = '</tr>';
			
			$out[] = '<tr>';
			$out[] = '<td>';

			$out[] = '</td>';
			$out[] = '<td class="slideshow-control-annotation">'; 
				$out[] = 'One slide dissolves into the next';
			$out[] = '</td>';
			$out[] = '</tr>';
			$out[] = '</table><!-- .slideshow-control -->';
		
		$out[] = '</td>';
		$out[] = '</tr>';


		$out[] = '</table><!-- .slideshow-layout-controls -->';
		
		return implode("\n",$out);
	}
	
	public function slideshow_footer() {
		$out = array();
		$out[] = '<div class="alt-hover">&nbsp;</div>';
		
		echo implode( "\n", $out );
	}
	
	
	public function slideshow_precheck_collection_name() {
		
		global $wpdb;
		
		$slideshow_name = sanitize_text_field($_POST['slideshow_name']);
		$table_name = $wpdb->prefix . 'slideshows';
		
		$sql = "SELECT id FROM $table_name WHERE title = '".$slideshow_name."'";
		
		$id = $wpdb->get_var($sql, FALSE);
		
		if( $id ) {		
			/* found - not okay to use */
			echo '{"result":"found", "slideshow_id":"'.$id.'"}';
		}
		else {	
			/* failed - is okay to use */
			echo '{"result":"not found"}';
		}
		die();
	}
	
	public function slideshow_create_collection( $slideshow_name = '' ) {
		
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'slideshows';
		$sql = "INSERT INTO $table_name (title) VALUES ( '".addslashes($slideshow_name)."' )";
		$wpdb->query($sql);
		
		return $wpdb->insert_id;
	}
	
		
	public function slideshow_save_collection_handler() {
		
		global $wpdb;
				
		$slideshow_title = $_POST['title'];
		
		if( array_key_exists('slideshow_id',$_POST) ) {
			$slideshow_id = $_POST['slideshow_id'];
		}
		
		$captions = '0';
		if( array_key_exists('captions',$_POST) ) {
			$captions = $_POST['captions'];
		}
		
		$is_active = $_POST['is_active'];
		
		if( empty($is_active) || $is_active == 'false' || $is_active == '0' ) {
		//	error_log( 'is_active setting to zero' );
			$is_active = 0;
		}
		else {
			$is_active = 1;
		}
		
		$layout = $_POST['layout'];
		$transition = $_POST['transition'];
		
		// error_log( 'layout: '.$layout .', transition: '.$transition);
		
		$slides = array();
		if( array_key_exists('slides',$_POST) ) {
			$slides = $_POST['slides'];
		}
			
		if( empty($slideshow_id) ) {
			$slideshow_id = self::slideshow_create_collection( $title );
		}
		
		$table_name = $wpdb->prefix . 'slideshows';
		
		if( $is_active == 1 ) {
			/* before we are set to the active record */
			/* unmark any currently marked as active */
			$sql = "UPDATE $table_name SET is_active=0 WHERE is_active=1";
			$wpdb->query($sql);
		}
		
		$wpdb->update( $table_name,
					array( 'title' => $slideshow_title,
							'layout' => $layout,
							'transition' => $transition,
							'date' => 'now()',
							'is_active' => $is_active,
							'captions' => $captions),
					array( 'id' => $slideshow_id )					
				);
		
		
		/**
		*	Release all slides currently associated with this slideshow_id
		*
		*	We do this to accommodate deletions from the set.
		**/
		$table_name = $wpdb->prefix . 'slideshow_slides';
		$ret = $wpdb->update($table_name, array('slideshow_id'=>0),array('slideshow_id' => $slideshow_id));
	//	error_log( 'Releasing slides: updated '.$ret .' where slideshow_id = '.$slideshow_id);
		
		/**
		*	Build the update/insert statement foreach 
		*
		*	Iterates the slides collection, builds appropraite query
		*	Some slides already exist: update; others are new, insert.
		**/
		foreach( $slides as $s ) {
		
			$FIELDS = array('slideshow_id');
			$VALUES = array($slideshow_id);
			
			$type = $s['type'];
			$slide_id = '';
			
			if( 'image' === $type ) {
				
				$FIELDS[] = 'post_id';
				$VALUES[] = $s['post_id'];
				
				$FIELDS[] = 'text_title';
				$VALUES[] = "'".addslashes($s['text_title'])."'";
	
			}
			else {	// 'text' === $type
				
				$FIELDS[] = 'text_title';
				$VALUES[] = "'".addslashes($s['text_title'])."'";
						
				$FIELDS[] = 'text_content';
				$VALUES[] = "'".addslashes($s['text_content'])."'";
			}
						
			if( array_key_exists('slide_id',$s ) ) {
				// don't change the slide's id 
				$slide_id = $s['slide_id'];
			}
			
			if( array_key_exists('ordering',$s) && is_numeric($s['ordering'])) {
				$FIELDS[] = 'ordering';
				$VALUES[] = $s['ordering'];
			}
			
			if( array_key_exists('slide_link',$s ) && !empty($s['slide_link'])) {
				$FIELDS[] = 'slide_link';
				$VALUES[] = "'".addslashes($s['slide_link'])."'";
			}
			else {
				// slide_link may have been deleted - always set to empty if not present
				$FIELDS[] = 'slide_link';
				$VALUES[] = "''";
			}
			
			$table_name = $wpdb->prefix . 'slideshow_slides';
			$sql = '';
			
			if( ! empty($slide_id) ) {
			
				// pre-existing slide - update, do not create
				$sql = "UPDATE $table_name SET ";
				for( $i=0;$i<count($FIELDS);$i++) {
					$sql .= $FIELDS[$i] .'='.$VALUES[$i];
					if( $i < count($FIELDS)-1) {
						$sql .= ',';
					}
				}
				$sql .= " WHERE id = $slide_id";
			}
			else {
			
				$sql = "INSERT INTO $table_name (";
				$sql .= implode(',',$FIELDS);
				$sql .=") VALUES (" . implode(',',$VALUES) . ")";
			}
		
		//	error_log( "\n\n".$sql."\n\n" );
		
			$wpdb->query($sql);
		}
		
		// clean up any orphaned slides 
		$table_name = $wpdb->prefix . 'slideshow_slides';
		$ret = $wpdb->delete($table_name, array('slideshow_id'=>0));
		// $ret == num rows removed | false on error //
		
		
		echo '{"result":"success","slideshow_id":"'.$slideshow_id.'", "feedback":"Collection saved"}';
		die();
		
	}
	
	public function slideshow_fetch_collection() {
		
		global $wpdb;
		
		$slideshow_id = $_POST['slideshow_id'];
		
		if( empty($slideshow_id )) {
			echo '{"result":"none"}';
			die();
		}
		
		$table_name = $wpdb->prefix.'slideshows';
		$show = $wpdb->get_row("SELECT * FROM $table_name WHERE id=$slideshow_id");
		
		$table_name = $wpdb->prefix . 'slideshow_slides';
		$sql = "SELECT * FROM $table_name WHERE slideshow_id=$slideshow_id ORDER BY ordering";
		$slides = $wpdb->get_results($sql);
		$out = array();
		foreach( $slides as $s ) {
			if( $s->post_id ) {
				$out[] = '{"id":"'.$s->id.'","post_id":"'.$s->post_id.'","text_title":'.json_encode(stripslashes($s->text_title)).',"slide_link":"'.$s->slide_link.'","ordering":"'.$s->ordering.'"}';
			}
			else {
				$out[] = '{"id":"'.$s->id.'","slide_link":"'.$s->slide_link.'","text_title":'.json_encode(stripslashes($s->text_title)).',"text_content":'.json_encode(stripslashes($s->text_content)).',"ordering":"'.$s->ordering.'"}'; 
			}
		}
		//error_log( implode( "\n", $out ));
			
		echo '{"slides":['. implode(',',$out).'], "is_active":"'.$show->is_active.'", "captions":"'.$show->captions.'","layout":"'.$show->layout.'", "transition":"'.$show->transition.'"}';
		die();
		
	}
	
	
	
	public function slideshow_delete_collection_handler() {
		
		global $wpdb;
		
		$slideshow_id = $_POST['slideshow_id'];
		
		$table_name = $wpdb->prefix . 'slideshow_slides';
		
		$ret = $wpdb->delete( $table_name, array('slideshow_id'=>$slideshow_id));
	//	error_log( 'remove from '.$table_name .': '. $ret .' for slideshow_id: '.  $slideshow_id );
		
		$table_name = $wpdb->prefix . 'slideshows';
		$ret = $wpdb->delete( $table_name, array('id'=>$slideshow_id));
		error_log( 'remove from '.$table_name .': '. $ret .' for slideshow_id: '.  $slideshow_id );
		
		echo '{"result":"success", "feedback":"Slideshow deleted."}';
		die();
	}


	
	/**
	*	Build a simpler data structure for metadata
	*
	*	return this as a nested array
	**/
	
	public function slideshow_fetch_img_meta( $alt_post_id = null ) {
		
		global $wpdb;

		// try to get the post from the local media cache first,
		// 	fallback to looking in the network shared media collection

		if( $alt_post_id != null ) {
			$post_id = $alt_post_id;
		}
		else {
			$post_id = $_POST['post_id'];
		}
		
		$sql = "SELECT post_title FROM $wpdb->posts WHERE ID = $post_id AND post_type='attachment'";
	
	//	error_log('sql: ' . $sql );
	
		$post_title = $wpdb->get_var($sql);
		
		$source = 'local';	// originates in the blog owner's media dirs vs. network shared media
		
		$meta;
		if( $post_title == NULL ) {
			// or use switch_to_blog() ?
			$sql = "SELECT post_title FROM wp_posts WHERE ID=$post_id AND post_type='attachment'";
			$post_title = $wpdb->get_var($sql);
			if( $post_title == NULL ) {
				error_log( 'select post_title failed where ID = '.$post_id );
				$post_id = 1790; // Set as placeholder slide from Shared Media if the original media was deleted.
			}
			$source = 'network';
			$sql = "SELECT meta_value FROM wp_postmeta WHERE meta_key='_wp_attachment_metadata' AND post_id=$post_id";
			$meta = $wpdb->get_var($sql);
		//	error_log( 'network meta: ' .$meta );
		}
		else {
			// 'local' again/still
			$sql = "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_wp_attachment_metadata' AND post_id=$post_id";
			$meta = $wpdb->get_var($sql);
		//	error_log( 'local meta: ' .$meta );
		}
		$meta = maybe_unserialize($meta);
		
	//	error_log( 'meta: ' .$meta );
		
		$postmeta = array();
		$postmeta['title'] = $post_title;
		
		list( $year, $month, $file ) = explode( '/',$meta['file']);
		
		$rootdir = 'files';		// default for 'local' source
		if( $source === 'network' ) {
			
			switch_to_blog(1);
			$site = site_url();
			restore_current_blog();
			
			$rootdir = $site .'/wp-uploads';
		}
		else {
			// get the subdomain's url
			$site = site_url();
			$rootdir = $site .'/'. $rootdir;
		}
		
	//	error_log( 'source : ' .$source . ', ' . $site .', '. $rootdir );
		
		$postmeta['source'] = $source;
		$postmeta['folder'] = sprintf( "%s/%4d/%02d/",$rootdir,$year,$month);
		$postmeta['file'] = $file;
		$postmeta['width'] = $meta['width'];
		$postmeta['height'] = $meta['height'];
		
		$postmeta['thumb'] = array( 
			'file' => $meta['sizes']['thumbnail']['file'],
			'width'=> $meta['sizes']['thumbnail']['width'],
			'height'=> $meta['sizes']['thumbnail']['height'] 
		);
		$postmeta['medium'] = array( 
			'file' => $meta['sizes']['medium']['file'],
			'width'=> $meta['sizes']['medium']['width'],
			'height'=> $meta['sizes']['medium']['height'] 
		);
	
		$postmeta['large'] = array( 
			'file' => $file,
			'width'=> $meta['width'],
			'height'=> $meta['height'] 
		);

		$postmeta['drag-slide'] = array( 
			'file' => $meta['sizes']['drag-slide']['file'],
			'width'=> $meta['sizes']['drag-slide']['width'],
			'height'=> $meta['sizes']['drag-slide']['height'] 
		);

		/*
			
		foreach( $meta as $k => $v ) {
			if( is_array($v) ) {
				foreach( $v as $j => $l ) {
					if( is_array($l)) {
						foreach( $l as $a => $b ) {
							if( is_array($b)) {
								foreach( $b as $c => $d ) {
									error_log( $k .': '.$j.': ['. $a .'] '. $c .' => ' . $d );
								}
							}
							else {
								error_log( $k.': ['.$j.'] '.$a .' <=> '.$b );		
							}
						}
					}
					else {
						error_log( $k.': '.$j .' => '. $l );
					}
				}
			}
			else {
				error_log( $k .' = > '. $v );
			}
		}	

		*/


		return $postmeta;
		
	}
	

	/**
	*	Fetch image meta callback	
	*		wraps the call to get img meta data
	*	returns it as JSON
	**/

	public function slideshow_fetch_img_meta_callback() {
	
		$post_id = $_POST['post_id'];
		
		$meta = self::slideshow_fetch_img_meta($post_id);

		$out = array();
		
		$out[] = '{"result":"success"';
		$out[] = '"meta": {"title": "'.$meta['title'].'"';
		$out[] = '"file":"'.$meta['file'].'"';
		$out[] = '"folder":"'.$meta['folder'].'"';
		$out[] = '"height":"'.$meta['height'].'"';
		$out[] = '"width":"'.$meta['width'].'"';
		
		$out[] = '"thumb": {"file":"'.$meta['thumb']['file'].'"';
		$out[] = '"width":"'.$meta['thumb']['width'].'"';
		$out[] = '"height":"'.$meta['thumb']['height'].'"}';
		
		$out[] = '"medium": {"file":"'.$meta['medium']['file'].'"';
		$out[] = '"width":"'.$meta['medium']['width'].'"';
		$out[] = '"height":"'.$meta['medium']['height'].'"}';

		$out[] = '"large": {"file":"'.$meta['large']['file'].'"';
		$out[] = '"width":"'.$meta['large']['width'].'"';
		$out[] = '"height":"'.$meta['large']['height'].'"}';
		
		$out[] = '"drag-slide": {"file":"'.$meta['drag-slide']['file'].'"';
		$out[] = '"width":"'.$meta['drag-slide']['width'].'"';
		$out[] = '"height":"'.$meta['drag-slide']['height'].'"}}}';  // meta // result
	
		echo implode(',',$out);
		die();
		
	}
	
		
	
	/**
	*	Store the content of the Add Text-only slide subform
	*
	**/
	public function slideshow_add_text_slide() {
		
	//	error_log(__FUNCTION__ );
		
		global $wpdb;
		
		$slideshow_id = $_POST['slideshow_id'];
		$slideshow_name = sanitize_text_field($_POST['slideshow_name']);
		$title = sanitize_text_field($_POST['title']);
		$content = sanitize_text_field($_POST['content']);
		$link = '';
		if( array_key_exists('slide_link',$_POST)) {
			$link = sanitize_text_field($_POST['slide_link']);	
		}
				
		if( empty($slideshow_id) || $slideshow_id == 'null' ) {
			if( ! empty($slideshow_name) ) {
				$slideshow_id = self::slideshow_create_collection($slideshow_name);
			}
			else {
				echo '{"result":"failed"}';
			}
		}
		
		$table_name = $wpdb->prefix . 'slideshow_slides';
		$sql = "INSERT INTO $table_name (slideshow_id,text_title,text_content, slide_link) values ( $slideshow_id, '".addslashes($title)."','". addslashes($content)."', '".$link."')";
		
		$wpdb->query($sql);
		
		$slide_id = $wpdb->insert_id;
		if( $slide_id ) {
			echo '{"result":"success", "slide_id":"'.$slide_id.'"}';
		}
		else {
			echo '{"result":"failed"}';
		}
		die();
	}
}
	
if ( ! isset( $slideshow_manager ) ) {
	global $slideshow_manager; 
	$slideshow_manager = new SlideshowManager();
}
	
endif; /* ! class_exists */
