<?php
/*
Plugin Name: Rather Simple Related Content
Plugin URI: http://wordpress.org/plugins/rather-simple-related-content/
Version: v1.0
Author: Oscar Ciutat
Author URI: http://oscarciutat.com/code/
Description: A really simple manual related posts plugin
*/

class Rather_Simple_Related_Content {

    /**
     * Plugin instance.
     *
     * @since 1.0
     *
     */
    protected static $instance = null;

    /**
     * Access this plugin’s working instance
     *
     * @since 1.0
     *
     */
    public static function get_instance() {
        
        if ( !self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;

    }
    
    /**
     * Used for regular plugin work.
     *
     * @since 1.0
     *
     */
    public function plugin_setup() {

        $this->includes();

        add_action( 'init', array( $this, 'load_language' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'get_related_content', array( $this, 'get_related_content' ) );
    }
    
    /**
     * Constructor. Intentionally left empty and public.
     *
     * @since 1.0
     *
     */
    public function __construct() {}
    
    /**
     * Includes required core files used in admin and on the frontend.
     *
     * @since 1.0
     *
     */
    protected function includes() {
        include_once 'includes/class-admin.php';
    }

    /**
     * Loads language
     *
     * @since 1.0
     *
     */
    function load_language() {
        load_plugin_textdomain( 'rather-simple-related-content', '', dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }
    
    /**
     * enqueue_scripts
     */
    function enqueue_scripts() {
        wp_enqueue_style( 'rather-simple-related-content-css', plugins_url( '/style.css', __FILE__) );
    }

    /**
     * get_related_posts_ids
     */
    function get_related_posts_ids( $post_id ) {
        $settings = (array) get_option( 'rsrc_settings' );
        $ids = get_post_meta( $post_id, '_rsrc_posts_ids', true );
        $ids = ! empty( $ids ) ? implode( ',', wp_parse_id_list( $ids ) ) : array();
        return $ids;
    }
    
    /**
     * get_related_content
     */
    function get_related_content( $post_id ) {
        $settings = (array) get_option( 'rsrc_settings' );
        $header = isset( $settings['header'] ) ? $settings['header'] : __( 'Related Content', 'rather-simple-related-content' );
        $layout = isset( $settings['layout'] ) ? $settings['layout'] : 'thumbnails';
        $thumbnail_size = isset( $settings['thumbnail_size'] ) ? $settings['thumbnail_size'] : 'thumbnail';
        $ids = wp_parse_id_list( $this->get_related_posts_ids( $post_id ) );
        if ( ! empty( $ids ) ) {
            $html = '<div class="related-content">
                     <h2>' . $header . '</h2>
                     <ul class="layout-' . $layout . '">';
            foreach( $ids as $id ) {
                if ( get_post_status( $id ) == 'publish' ) {
                    if ( $layout == 'list' ) {
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
     * get_post_thumbnail
     */
    function get_post_thumbnail( $id = null, $size = 'thumbnail' ) {
        $post = get_post( $id );
        
        $html = '<a href="' . get_permalink( $id ) . '"><div class="thumb">';
        if ( has_post_thumbnail( $id ) ) {
            $html .= wp_get_attachment_image( get_post_thumbnail_id( $id ), $size );
        } else {
            if ( isset( $_wp_additional_image_sizes ) && count( $_wp_additional_image_sizes ) && in_array( $size, array_keys( $_wp_additional_image_sizes ) ) ) {
                $width = $_wp_additional_image_sizes[$size]['width'];
                $height = $_wp_additional_image_sizes[$size]['height'];
            } else {
                $width = get_option( $size. '_size_w' );
                $height = get_option( $size. '_size_h' );
            } 
            $placeholder = apply_filters( 'rather_simple_related_content_placeholder_url', plugins_url( '/assets/images/placeholder.png', __FILE__ ) );
            $html .= '<img src="' . $placeholder . '" width="' . $width . '" height="' . $height . '" alt="' . esc_attr( get_the_title( $id ) ) . '" />';
        }
        
        $html .= '<div class="overlay">
                <div class="entry-caption">
                <div class="entry-title">' . get_the_title( $id ) . '</div>';
                
        $html .= '</div></div></div></a>';

        return $html;
    }

}

add_action( 'plugins_loaded', array ( Rather_Simple_Related_Content::get_instance(), 'plugin_setup' ) );