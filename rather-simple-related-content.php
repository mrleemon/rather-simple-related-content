<?php
/**
 * Plugin Name: Rather Simple Related Content
 * Plugin URI:
 * Update URI: false
 * Version: v1.0
 * Author: Oscar Ciutat
 * Author URI: http://oscarciutat.com/code/
 * Description: A really simple manual related content plugin
 * Text Domain: rather-simple-related-content
 * License: GPL v2 or later
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package rather_simple_related_content
 */

/**
 * Core class used to implement the plugin.
 */
class Rather_Simple_Related_Content {

	/**
	 * Plugin instance
	 *
	 * @var object $instance
	 */
	protected static $instance = null;

	/**
	 * Access this plugin’s working instance
	 */
	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Used for regular plugin work
	 */
	public function plugin_setup() {

		$this->includes();

		add_action( 'init', array( $this, 'load_language' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'get_related_content', array( $this, 'get_related_content' ) );
	}

	/**
	 * Constructor. Intentionally left empty and public.
	 */
	public function __construct() {}

	/**
	 * Includes required core files used in admin and on the frontend
	 */
	protected function includes() {
		include_once 'includes/class-rather-simple-related-content-admin.php';
	}

	/**
	 * Loads language
	 */
	public function load_language() {
		load_plugin_textdomain( 'rather-simple-related-content', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'rather-simple-related-content-css', plugins_url( '/style.css', __FILE__ ) );
	}

	/**
	 * Get related posts ids
	 *
	 * @param integer $post_id  The post ID.
	 */
	public function get_related_posts_ids( $post_id ) {
		$settings = (array) get_option( 'rsrc_settings' );
		$ids      = get_post_meta( $post_id, '_rsrc_posts_ids', true );
		$ids      = ! empty( $ids ) ? implode( ',', wp_parse_id_list( $ids ) ) : array();
		return $ids;
	}

	/**
	 * Get related content
	 *
	 * @param integer $post_id  The post ID.
	 */
	public function get_related_content( $post_id ) {
		$settings       = (array) get_option( 'rsrc_settings' );
		$header         = isset( $settings['header'] ) ? $settings['header'] : __( 'Related Content', 'rather-simple-related-content' );
		$layout         = isset( $settings['layout'] ) ? $settings['layout'] : 'thumbnails';
		$thumbnail_size = isset( $settings['thumbnail_size'] ) ? $settings['thumbnail_size'] : 'thumbnail';
		$ids            = wp_parse_id_list( $this->get_related_posts_ids( $post_id ) );
		if ( ! empty( $ids ) ) {
			$html = '<div class="related-content">
                     <h2>' . $header . '</h2>
                     <ul class="layout-' . esc_attr( $layout ) . '">';
			foreach ( $ids as $id ) {
				if ( 'publish' === get_post_status( $id ) ) {
					if ( 'list' === $layout ) {
						$html .= '<li><h3 class="entry-title"><a href="' . esc_url( apply_filters( 'the_permalink', get_permalink( $id ) ) ) . '">' . get_the_title( $id ) . '</a></h3></li>';
					} else {
						$html .= '<li>' . $this->get_post_thumbnail( $id, $thumbnail_size ) . '</li>';
					}
				}
			}
			$html .= '</ul></div>';
			echo $html;
		}

	}

	/**
	 * Get post thumbnail
	 *
	 * @param integer $id    The post ID.
	 * @param string  $size  The thumbnail size.
	 */
	public function get_post_thumbnail( $id = null, $size = 'thumbnail' ) {
		$post = get_post( $id );

		$html = '<a href="' . esc_url( get_permalink( $id ) ) . '"><div class="thumb">';
		if ( has_post_thumbnail( $id ) ) {
			$html .= wp_get_attachment_image( get_post_thumbnail_id( $id ), $size );
		} else {
			if ( isset( $_wp_additional_image_sizes ) && count( $_wp_additional_image_sizes ) && in_array( $size, array_keys( $_wp_additional_image_sizes ), true ) ) {
				$width  = $_wp_additional_image_sizes[ $size ]['width'];
				$height = $_wp_additional_image_sizes[ $size ]['height'];
			} else {
				$width  = get_option( $size . '_size_w' );
				$height = get_option( $size . '_size_h' );
			}
			$placeholder = apply_filters( 'rather_simple_related_content_placeholder_url', plugins_url( '/assets/images/placeholder.png', __FILE__ ) );
			$html       .= '<img src="' . esc_url( $placeholder ) . '" width="' . esc_attr( $width ) . '" height="' . esc_attr( $height ) . '" alt="' . esc_attr( get_the_title( $id ) ) . '" />';
		}

		$html .= '<div class="overlay">
                <div class="entry-caption">
                <div class="entry-title">' . get_the_title( $id ) . '</div>';

		$html .= '</div></div></div></a>';

		return $html;
	}

}

add_action( 'plugins_loaded', array( Rather_Simple_Related_Content::get_instance(), 'plugin_setup' ) );
