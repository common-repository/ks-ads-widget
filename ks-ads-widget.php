<?php
/*
Plugin Name: KS Ads Widget
Plugin URI: http://aioresources.net
Description: A simple ads widget that uses the native WordPress media manager to add ads widgets to your site.
Author: King Soft
Version: 1.2
Author URI: http://aioresources.net
License: GNU General Public License v2.0 (or later)
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

// Block direct requests
if ( !defined('ABSPATH') ) {
	die('-1');
}

// Load the widget on widgets_init
function ks_load_ads_widget() {
	register_widget('KS_Ads_Widget');
}
add_action('widgets_init', 'ks_load_ads_widget');

/**
 * KS_Ads_Widget class
 **/
class KS_Ads_Widget extends WP_Widget {

	const VERSION = '1.2';

	const CUSTOM_IMAGE_SIZE_SLUG = 'ks_ads_widget_custom';

	/**
	 * KS Ads Widget constructor
	 *
	 * @author King Soft
	 */
	public function __construct() {
		load_plugin_textdomain( 'ads_widget', false, trailingslashit(basename(dirname(__FILE__))) . 'lang/');
		$widget_ops = array( 'classname' => 'widget_sp_image', 'description' => __( 'Showcase a single image with a Title, URL, and a Description', 'ads_widget' ) );
		$control_ops = array( 'id_base' => 'widget_sp_image' );
		parent::__construct('widget_sp_image', __('KS Ads Widget', 'ads_widget'), $widget_ops, $control_ops);

		if ( $this->use_old_uploader() ) {
			require_once( 'lib/AdsWidgetDeprecated.php' );
			new AdsWidgetDeprecated( $this );
		} else {
			add_action( 'sidebar_admin_setup', array( $this, 'admin_setup' ) );
		}
		add_action( 'admin_head-widgets.php', array( $this, 'admin_head' ) );

		add_action( 'plugin_row_meta', array( $this, 'plugin_row_meta' ),10 ,2 );

		if ( !defined('I_HAVE_SUPPORTED_THE_ADS_WIDGET') )
			add_action( 'admin_notices', array( $this, 'post_upgrade_nag') );

		add_action( 'network_admin_notices', array( $this, 'post_upgrade_nag') );

		/**
		 * Proper way to enqueue scripts and styles
		 */
		add_action( 'wp_enqueue_scripts', array( $this, 'ks_ads_scripts' ) );
	}

	/**
	 * Test to see if this version of WordPress supports the new image manager.
	 * @return bool true if the current version of WordPress does NOT support the current image management tech.
	 */
	private function use_old_uploader() {
		if ( defined( 'ADS_WIDGET_COMPATIBILITY_TEST' ) ) return true;
		return !function_exists('wp_enqueue_media');
	}

	/**
	 * Enqueue all the javascript.
	 */
	public function admin_setup() {
		wp_enqueue_media();
		wp_enqueue_script( 'ks-ads-widget', plugins_url('resources/js/ads-widget.js', __FILE__), array( 'jquery', 'media-upload', 'media-views' ), self::VERSION );

		wp_localize_script( 'ks-ads-widget', 'KsAdsWidget', array(
			'frame_title' => __( 'Select an Image', 'ads_widget' ),
			'button_title' => __( 'Insert Into Widget', 'ads_widget' ),
		) );
	}

	public function ks_ads_scripts() {
		wp_enqueue_style( 'ks-ads-css', plugins_url('resources/css/css.css', __FILE__) );
	}

	/**
	 * Widget frontend output
	 *
	 * @param array $args
	 * @param array $instance
	 * @author King Soft
	 */
	public function widget( $args, $instance ) {
		extract( $args );
		$instance = wp_parse_args( (array) $instance, self::get_defaults() );
		if ( !empty( $instance['imageurl'] ) || !empty( $instance['attachment_id'] ) ) {

			$instance['title'] = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'] );
			$instance['description'] = apply_filters( 'widget_text', $instance['description'], $args, $instance );
			$instance['color'] = apply_filters( 'widget_color', empty( $instance['color'] ) ? '' : $instance['color'] );
			$instance['link'] = apply_filters( 'ads_widget_image_link', esc_url( $instance['link'] ), $args, $instance );
			$instance['linkid'] = apply_filters( 'ads_widget_image_link_id', esc_attr( $instance['linkid'] ), $args, $instance );
			$instance['linktarget'] = apply_filters( 'ads_widget_image_link_target', esc_attr( $instance['linktarget'] ), $args, $instance );
			$instance['width'] = apply_filters( 'ads_widget_image_width', abs( $instance['width'] ), $args, $instance );
			$instance['height'] = apply_filters( 'ads_widget_image_height', abs( $instance['height'] ), $args, $instance );
			$instance['maxwidth'] = apply_filters( 'ads_widget_image_maxwidth', esc_attr( $instance['maxwidth'] ), $args, $instance );
			$instance['maxheight'] = apply_filters( 'ads_widget_image_maxheight', esc_attr( $instance['maxheight'] ), $args, $instance );
			$instance['align'] = apply_filters( 'ads_widget_image_align', esc_attr( $instance['align'] ), $args, $instance );
			$instance['hoverstyle'] = apply_filters( 'ads_widget_image_hoverstyle', esc_attr( $instance['hoverstyle'] ), $args, $instance );
			$instance['alt'] = apply_filters( 'ads_widget_image_alt', esc_attr( $instance['alt'] ), $args, $instance );
			$instance['rel'] = apply_filters( 'ads_widget_image_rel', esc_attr( $instance['rel'] ), $args, $instance );

			if ( !defined( 'ADS_WIDGET_COMPATIBILITY_TEST' ) ) {
				$instance['attachment_id'] = ( $instance['attachment_id'] > 0 ) ? $instance['attachment_id'] : $instance['image'];
				$instance['attachment_id'] = apply_filters( 'ads_widget_image_attachment_id', abs( $instance['attachment_id'] ), $args, $instance );
				$instance['size'] = apply_filters( 'ads_widget_image_size', esc_attr( $instance['size'] ), $args, $instance );
			}
			$instance['imageurl'] = apply_filters( 'ads_widget_image_url', esc_url( $instance['imageurl'] ), $args, $instance );

			// No longer using extracted vars. This is here for backwards compatibility.
			extract( $instance );

			include( $this->getTemplateHierarchy( 'widget' ) );
		}
	}

	/**
	 * Update widget options
	 *
	 * @param object $new_instance Widget Instance
	 * @param object $old_instance Widget Instance
	 * @return object
	 * @author King Soft
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$new_instance = wp_parse_args( (array) $new_instance, self::get_defaults() );
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['color'] = strip_tags($new_instance['color']);
		if ( current_user_can('unfiltered_html') ) {
			$instance['description'] = $new_instance['description'];
		} else {
			$instance['description'] = wp_filter_post_kses($new_instance['description']);
		}
		$instance['link'] = $new_instance['link'];
		$instance['linkid'] = $new_instance['linkid'];
		$instance['linktarget'] = $new_instance['linktarget'];
		$instance['width'] = abs( $new_instance['width'] );
		$instance['height'] =abs( $new_instance['height'] );
		if ( !defined( 'ADS_WIDGET_COMPATIBILITY_TEST' ) ) {
			$instance['size'] = $new_instance['size'];
		}
		$instance['align'] = $new_instance['align'];
		$instance['hoverstyle'] = $new_instance['hoverstyle'];
		$instance['alt'] = $new_instance['alt'];
		$instance['rel'] = $new_instance['rel'];

		// Reverse compatibility with $image, now called $attachement_id
		if ( !defined( 'ADS_WIDGET_COMPATIBILITY_TEST' ) && $new_instance['attachment_id'] > 0 ) {
			$instance['attachment_id'] = abs( $new_instance['attachment_id'] );
		} elseif ( $new_instance['image'] > 0 ) {
			$instance['attachment_id'] = $instance['image'] = abs( $new_instance['image'] );
			if ( class_exists('AdsWidgetDeprecated') ) {
				$instance['imageurl'] = AdsWidgetDeprecated::get_image_url( $instance['image'], $instance['width'], $instance['height'] );  // image resizing not working right now
			}
		}
		$instance['imageurl'] = $new_instance['imageurl']; // deprecated

		$instance['aspect_ratio'] = $this->get_image_aspect_ratio( $instance );

		return $instance;
	}

	/**
	 * Form UI
	 *
	 * @param object $instance Widget Instance
	 * @author King Soft
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, self::get_defaults() );
		if ( $this->use_old_uploader() ) {
			include( $this->getTemplateHierarchy( 'widget-admin.deprecated' ) );
		} else {
			include( $this->getTemplateHierarchy( 'widget-admin' ) );
		}
	}

	/**
	 * Admin header css
	 *
	 * @author King Soft
	 */
	public function admin_head() {
		?>
	<style type="text/css">
		.uploader input.button {
			width: 100%;
			height: 34px;
			line-height: 33px;
			margin-top: 15px;
		}
		.ks_preview .aligncenter {
			display: block;
			margin-left: auto !important;
			margin-right: auto !important;
		}
		.ks_preview {
			overflow: hidden;
			max-height: 300px;
		}
		.ks_preview img {
			margin: 10px 0;
			height: auto;
		}
	</style>
	<?php
	}

	/**
	 * Render an array of default values.
	 *
	 * @return array default values
	 */
	private static function get_defaults() {

		$defaults = array(
			'title' => '',
			'color' => '',
			'description' => '',
			'link' => '',
			'linkid' => '',
			'linktarget' => '',
			'width' => 0,
			'height' => 0,
			'maxwidth' => '100%',
			'maxheight' => '',
			'image' => 0, // reverse compatible - now attachement_id
			'imageurl' => '', // reverse compatible.
			'align' => 'none',
			'hoverstyle' => '',
			'alt' => '',
			'rel' => '',
		);

		if ( !defined( 'ADS_WIDGET_COMPATIBILITY_TEST' ) ) {
			$defaults['size'] = self::CUSTOM_IMAGE_SIZE_SLUG;
			$defaults['attachment_id'] = 0;
		}

		return $defaults;
	}

	/**
	 * Render the image html output.
	 *
	 * @param array $instance
	 * @param bool $include_link will only render the link if this is set to true. Otherwise link is ignored.
	 * @return string image html
	 */
	private function get_image_html( $instance, $include_link = true ) {

		// Backwards compatible image display.
		if ( $instance['attachment_id'] == 0 && $instance['image'] > 0 ) {
			$instance['attachment_id'] = $instance['image'];
		}

		$output = '';

		if ( $include_link && !empty( $instance['link'] ) ) {
			$attr = array(
				'href' => $instance['link'],
				'id' => $instance['linkid'],
				'target' => $instance['linktarget'],
				'class' => 	$this->widget_options['classname'].'-image-link',
				'title' => ( !empty( $instance['alt'] ) ) ? $instance['alt'] : $instance['title'],
				'rel' => $instance['rel'],
			);
			$attr = apply_filters('ads_widget_link_attributes', $attr, $instance );
			$attr = array_map( 'esc_attr', $attr );
			$output = '<a';
			foreach ( $attr as $name => $value ) {
				$output .= sprintf( ' %s="%s"', $name, $value );
			}
			$output .= '>';
		}

		$size = $this->get_image_size( $instance );
		if ( is_array( $size ) ) {
			$instance['width'] = $size[0];
			$instance['height'] = $size[1];
		} elseif ( !empty( $instance['attachment_id'] ) ) {
			//$instance['width'] = $instance['height'] = 0;
			$image_details = wp_get_attachment_image_src( $instance['attachment_id'], $size );
			if ($image_details) {
				$instance['imageurl'] = $image_details[0];
				$instance['width'] = $image_details[1];
				$instance['height'] = $image_details[2];
			}
		}
		$instance['width'] = abs( $instance['width'] );
		$instance['height'] = abs( $instance['height'] );

		$attr = array();
		$attr['alt'] = ( !empty( $instance['alt'] ) ) ? $instance['alt'] : $instance['title'];
		if (is_array($size)) {
			$attr['class'] = 'attachment-'.join('x',$size);
		} else {
			$attr['class'] = 'attachment-'.$size;
		}
		$attr['style'] = '';
		if (!empty($instance['maxwidth'])) {
			$attr['style'] .= "max-width: {$instance['maxwidth']};";
		}
		if (!empty($instance['maxheight'])) {
			$attr['style'] .= "max-height: {$instance['maxheight']};";
		}
		if (!empty($instance['align']) && $instance['align'] != 'none') {
			$attr['class'] .= " align{$instance['align']}";
		}
		$attr = apply_filters( 'ads_widget_image_attributes', $attr, $instance );

		// If there is an imageurl, use it to render the image. Eventually we should kill this and simply rely on attachment_ids.
		if ( !empty( $instance['imageurl'] ) ) {
			// If all we have is an image src url we can still render an image.
			$attr['src'] = $instance['imageurl'];
			$attr = array_map( 'esc_attr', $attr );
			$hwstring = image_hwstring( $instance['width'], $instance['height'] );
			$output .= rtrim("<img $hwstring");
			foreach ( $attr as $name => $value ) {
				$output .= sprintf( ' %s="%s"', $name, $value );
			}
			$output .= ' />';
		} elseif( abs( $instance['attachment_id'] ) > 0 ) {
			$output .= wp_get_attachment_image($instance['attachment_id'], $size, false, $attr);
		}

		if ( $include_link && !empty( $instance['link'] ) ) {
			$output .= '</a>';
		}

		return $output;
	}

	/**
	 * Get all possible image sizes to choose from
	 *
	 * @return array
	 */
	private function possible_image_sizes() {
		$registered = get_intermediate_image_sizes();
		// label other sizes with their image size "ID"
		$registered = array_combine( $registered, $registered );

		$possible_sizes = array_merge( $registered, array(
			'full'                       => __('Full Size', 'ads_widget'),
			'thumbnail'                  => __('Thumbnail', 'ads_widget'),
			'medium'                     => __('Medium', 'ads_widget'),
			'large'                      => __('Large', 'ads_widget'),
			self::CUSTOM_IMAGE_SIZE_SLUG => __('Custom', 'ads_widget'),
		) );

		return (array) apply_filters( 'image_size_names_choose', $possible_sizes );
	}

	/**
	 * Assesses the image size in case it has not been set or in case there is a mismatch.
	 *
	 * @param $instance
	 * @return array|string
	 */
	private function get_image_size( $instance ) {
		if ( !empty( $instance['size'] ) && $instance['size'] != self::CUSTOM_IMAGE_SIZE_SLUG ) {
			$size = $instance['size'];
		} elseif ( isset( $instance['width'] ) && is_numeric($instance['width']) && isset( $instance['height'] ) && is_numeric($instance['height']) ) {
			//$size = array(abs($instance['width']),abs($instance['height']));
			$size = array($instance['width'],$instance['height']);
		} else {
			$size = 'full';
		}
		return $size;
	}

	/**
	 * Establish the aspect ratio of the image.
	 *
	 * @param $instance
	 * @return float|number
	 */
	private function get_image_aspect_ratio( $instance ) {
		if ( !empty( $instance['aspect_ratio'] ) ) {
			return abs( $instance['aspect_ratio'] );
		} else {
			$attachment_id = ( !empty($instance['attachment_id']) ) ? $instance['attachment_id'] : $instance['image'];
			if ( !empty($attachment_id) ) {
				$image_details = wp_get_attachment_image_src( $attachment_id, 'full' );
				if ($image_details) {
					return ( $image_details[1]/$image_details[2] );
				}
			}
		}
	}

	/**
	 * Loads theme files in appropriate hierarchy: 1) child theme,
	 * 2) parent template, 3) plugin resources. will look in the ads-widget/
	 * directory in a theme and the views/ directory in the plugin
	 *
	 * @param string $template template file to search for
	 * @return template path
	 * @author King Soft (Matt Wiebe)
	 **/

	public function getTemplateHierarchy($template) {
		// whether or not .php was added
		$template_slug = rtrim($template, '.php');
		$template = $template_slug . '.php';

		if ( $theme_file = locate_template(array('ads-widget/'.$template)) ) {
			$file = $theme_file;
		} else {
			$file = 'views/' . $template;
		}
		return apply_filters( 'sp_template_ads-widget_'.$template, $file);
	}


	/**
	 * Display a thank you nag when the plugin has been upgraded.
	 */
	public function post_upgrade_nag() {
		if ( !current_user_can('install_plugins') ) return;

		$version_key = '_ads_widget_version';
		if ( get_site_option( $version_key ) == self::VERSION ) return;

		$msg = sprintf(__('Thanks for upgrading the KS Ads Widget! Please go to <a href="%s" target="_blank">here</a> and maybe maybe download more scripts or themes or plugins premium!', 'ads-widget'),'http://aioresources.net');
		echo "<div class='update-nag'>$msg</div>";

		update_site_option( $version_key, self::VERSION );
	}

	/**
	 * Display an informational section in the plugin admin ui.
	 * @param $meta
	 * @param $file
	 *
	 * @return array
	 */
	public function plugin_row_meta( $meta, $file ) {
		if ( $file == plugin_basename( dirname(__FILE__).'/ads-widget.php' ) ) {
			$meta[] = '<span class="ks-test">'.sprintf(__('Check out our other <a href="%s" target="_blank">plugins</a> including our <a href="%s" target="_blank">Events Calendar Pro</a>!', 'ads-widget'),'http://tri.be/products/?source=ads-widget&pos=pluginlist','http://tri.be/wordpress-events-calendar-pro/?source=ads-widget&pos=pluginlist').'</span>';
		}
		return $meta;
	}
}
