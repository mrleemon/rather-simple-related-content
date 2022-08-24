<?php
/**
 * The admin class for the plugin.
 *
 * @package rather_simple_related_content
 */

/**
 * Core class used to implement the admin.
 */
final class Rather_Simple_Related_Content_Admin {

	/**
	 * Holds the instance of this class.
	 *
	 * @var object $instance
	 */
	private static $instance;

	/**
	 * Returns the instance.
	 *
	 * @return object
	 */
	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Plugin setup.
	 */
	public function __construct() {

		add_filter( 'plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_footer-post.php', array( $this, 'print_find_posts_modal' ) );
		add_action( 'admin_footer-post-new.php', array( $this, 'print_find_posts_modal' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'wp_ajax_rsrc_find_posts', array( $this, 'rsrc_find_posts' ) );

	}

	/**
	 * Adds action links.
	 *
	 * @param array  $links  An array of plugin action links.
	 * @param string $file   Path to the plugin file relative to the plugins directory.
	 */
	public function add_action_links( $links, $file ) {
		if ( preg_match( '/rather-simple-related-content\.php/i', $file ) ) {
			$links[] = '<a href="' . admin_url( 'options-general.php?page=rather-simple-related-content' ) . '">' . __( 'Settings' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Enqueues scripts in the backend.
	 *
	 * @param string $hook  The current admin page.
	 */
	public function admin_enqueue_scripts( $hook ) {
		global $post;
		wp_enqueue_style(
			'rather-simple-related-content-css',
			plugins_url( '/assets/css/admin.css', dirname( __FILE__ ) ),
			array(),
			filemtime( plugin_dir_path( dirname( __FILE__ ) ) . '/assets/css/admin.css' )
		);
		if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
			wp_enqueue_script(
				'rather-simple-related-content',
				plugins_url( '/assets/js/backend.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				filemtime( plugin_dir_path( dirname( __FILE__ ) ) . '/assets/js/backend.js' ),
				true
			);
			wp_localize_script( 'rather-simple-related-content', 'rsrc_js', array( 'ID' => $post->ID ) );
		}
	}

	/**
	 * Admin menu
	 */
	public function admin_menu() {
		add_options_page( __( 'Rather Simple Related Content', 'rather-simple-related-content' ), __( 'Rather Simple Related Content', 'rather-simple-related-content' ), 'manage_options', 'rather-simple-related-content', array( $this, 'options_page' ) );
	}

	/**
	 * Admin init
	 */
	public function admin_init() {
		register_setting( 'rather-simple-related-content-settings', 'rsrc_settings', array( $this, 'sanitize_settings' ) );
		add_settings_section( 'layout-section', __( 'General', 'rather-simple-related-content' ), null, 'rather-simple-related-content' );
		add_settings_field( 'header', __( 'Header', 'rather-simple-related-content' ), array( $this, 'header_callback' ), 'rather-simple-related-content', 'layout-section', array( 'class' => 'header' ) );
		add_settings_field( 'layout', __( 'Layout', 'rather-simple-related-content' ), array( $this, 'layout_callback' ), 'rather-simple-related-content', 'layout-section', array( 'class' => 'layout' ) );
		add_settings_field( 'thumbnail_size', __( 'Thumbnail Size', 'rather-simple-related-content' ), array( $this, 'thumbnail_size_callback' ), 'rather-simple-related-content', 'layout-section', array( 'class' => 'thumbnail_size' ) );
		add_settings_field( 'post_types', __( 'Post Types', 'rather-simple-related-content' ), array( $this, 'post_types_callback' ), 'rather-simple-related-content', 'layout-section', array( 'class' => 'post_types' ) );
	}

	/**
	 * Options page
	 */
	public function options_page() {
		?>
	<div class="wrap">
		<h2><?php _e( 'Rather Simple Related Content', 'rather-simple-related-content' ); ?></h2>
		<form action="options.php" method="post">
			<?php settings_fields( 'rather-simple-related-content-settings' ); ?>
			<?php do_settings_sections( 'rather-simple-related-content' ); ?>
			<?php submit_button(); ?>
		</form>
	</div>
		<?php
	}

	/**
	 * Layout callback
	 */
	public function layout_callback() {
		$settings       = (array) get_option( 'rsrc_settings' );
		$layout         = isset( $settings['layout'] ) ? $settings['layout'] : 'thumbnails';
		$layout_options = array(
			'list'       => __( 'List', 'rather-simple-related-content' ),
			'thumbnails' => __( 'Thumbnails', 'rather-simple-related-content' ),
		);
		?>
		<ul>
		<?php foreach ( $layout_options as $option => $label ) { ?>
			<li>
			<label for="layout-<?php echo esc_attr( $option ); ?>">
			<input type="radio" name="rsrc_settings[layout]" id="layout-<?php echo esc_attr( $option ); ?>" value="<?php echo esc_attr( $option ); ?>" <?php checked( $layout, $option ); ?> />
			<?php echo $label; ?>
			</label>
			</li>
		<?php } ?>
		</ul>
		<?php
	}

	/**
	 * Thumbnail size callback
	 */
	public function thumbnail_size_callback() {
		$settings               = (array) get_option( 'rsrc_settings' );
		$thumbnail_size         = isset( $settings['thumbnail_size'] ) ? $settings['thumbnail_size'] : 'thumbnail';
		$thumbnail_size_options = get_intermediate_image_sizes();
		?>
		<select name="rsrc_settings[thumbnail_size]">
		<?php foreach ( $thumbnail_size_options as $option ) { ?>
			<option id="thumbnail-size-<?php echo esc_attr( $option ); ?>" value="<?php echo esc_attr( $option ); ?>" <?php selected( $thumbnail_size, $option ); ?> />
			<?php echo $option; ?>
			</option>
		<?php } ?>
		</select>
		<?php
	}

	/**
	 * Post types callback
	 */
	public function post_types_callback() {
		$settings           = (array) get_option( 'rsrc_settings' );
		$post_types         = ! empty( $settings['post_types'] ) ? $settings['post_types'] : array();
		$post_types_options = get_post_types(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'objects'
		);
		?>
		<ul>
		<?php
		foreach ( $post_types_options as $option ) {
			if ( 'attachment' === $option->name ) {
				continue;
			}
			?>
			<li>
			<label for="post-type-<?php echo esc_attr( $option->name ); ?>">
			<input type="checkbox" name="rsrc_settings[post_types][]" id="post-type-<?php echo esc_attr( $option->name ); ?>" value="<?php echo esc_attr( $option->name ); ?>" <?php checked( in_array( $option->name, $post_types, true ) ? 'on' : '', 'on' ); ?> />
			<?php echo $option->label; ?>
			</label>
			</li>
		<?php } ?>
		</ul>
		<?php
	}

	/**
	 * Header callback
	 */
	public function header_callback() {
		$settings = (array) get_option( 'rsrc_settings' );
		$header   = isset( $settings['header'] ) ? $settings['header'] : __( 'Related Content', 'rather-simple-related-content' );
		?>
		<input name="rsrc_settings[header]" type="text" id="rsrc_settings[header]" value="<?php echo esc_attr( $header ); ?>" class="regular-text" /> 
		<?php
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $input  The form input.
	 */
	public function sanitize_settings( $input ) {
		$settings = (array) get_option( 'rsrc_settings' );
		return $input;
	}

	/**
	 * Print find posts modal
	 */
	public function print_find_posts_modal() {
		global $typenow;

		$settings = (array) get_option( 'rsrc_settings' );

		if ( ! empty( $settings['post_types'] ) && in_array( $typenow, $settings['post_types'], true ) ) {

			?>
			<div id="find-posts" class="find-box" style="display:none;">
				<div id="find-posts-head" class="find-box-head">
					<?php _e( 'Find related content', 'rather-simple-related-content' ); ?>
					<button type="button" id="find-posts-close"><span class="screen-reader-text"><?php _e( 'Close' ); ?></span></button>
				</div>
				<div class="find-box-inside">
					<div class="find-box-search">
						<input type="hidden" name="affected" id="affected" value="" />
						<?php wp_nonce_field( 'find-posts', '_ajax_nonce', false ); ?>
						<label class="screen-reader-text" for="find-posts-input"><?php _e( 'Search' ); ?></label>
						<input type="text" id="find-posts-input" name="ps" value="" />
						<span class="spinner"></span>
						<input type="button" id="find-posts-search" value="<?php esc_attr_e( 'Search' ); ?>" class="button" />
						<div class="clear"></div>
						<div class="find-box-options">
						<?php
						$args       = array(
							'show_ui'             => true,
							'public'              => true,
							'exclude_from_search' => false,
						);
						$post_types = get_post_types( $args, 'objects' );
						foreach ( $post_types as $post_type ) {
							if ( 'attachment' === $post_type->name ) {
								continue;
							}
							?>
							<label for="find-posts-<?php echo esc_attr( $post_type->name ); ?>">
								<input type="checkbox" name="find-posts-what[]" id="find-posts-<?php echo esc_attr( $post_type->name ); ?>" value="<?php echo esc_attr( $post_type->name ); ?>" checked="checked" />
								<?php echo $post_type->label; ?>
							</label>
							<?php
						}
						?>
						</div>
					</div>
					<div id="find-posts-response"></div>
				</div>
				<div class="find-box-buttons">
					<?php submit_button( __( 'Select' ), 'primary alignright', 'find-posts-submit', false ); ?>
					<div class="clear"></div>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Add meta boxes
	 */
	public function add_meta_boxes() {
		$settings   = (array) get_option( 'rsrc_settings' );
		$post_types = $settings['post_types'];
		if ( ! empty( $post_types ) ) {
			add_meta_box( 'related-content', __( 'Related Content', 'rather-simple-related-content' ), array( $this, 'related_content_meta_box' ), $post_types, 'normal', 'low' );
		}
	}

	/**
	 * Related content meta box
	 */
	public function related_content_meta_box() {
		global $post;
		$ids = Rather_Simple_Related_Content::get_instance()->get_related_posts_ids( $post->ID );
		$ids = is_array( $ids ) ? '' : $ids;
		?>
	<div>
		<input class="hide-if-js" type="text" name="rsrc_post_ids" id="rsrc_post_ids" value="<?php echo esc_attr( $ids ); ?>" />
		<?php wp_nonce_field( basename( __FILE__ ), 'metabox_nonce' ); ?>
		<ul class="related-posts">
			<?php
			if ( ! empty( $ids ) ) {
				$ids = wp_parse_id_list( $ids );
				foreach ( $ids as $id ) {
					if ( get_post_status( $id ) ) {
						$title = get_the_title( (int) $id );
					} else {
						$title = __( '(Deleted)', 'rather-simple-related-content' );
					}
					?>
					<li data-id="<?php echo (int) $id; ?>"><span><a class="hide-if-no-js delete_related_post"><span class="dashicons dashicons-dismiss"></span></a>&nbsp;&nbsp;<?php echo $title; ?></span></li>
					<?php
				}
			}
			?>
		</ul>
		<div>
			<a href="javascript:void(0);" id="rsrc_open_find_posts_button" class="button hide-if-no-js"><?php _e( 'Add related content', 'rather-simple-related-content' ); ?></a>
			<span class="hide-if-js"><?php _e( 'Add posts IDs from posts you want to relate, comma separated.', 'rather-simple-related-content' ); ?></span>
			<span class="clear-link"><a href="javascript:void(0);" id="rsrc_delete_related_posts" class="delete hide-if-no-js"><?php _e( 'Clear List' ); ?></a></span>
		</div>
	</div>
		<?php
	}

	/**
	 * Save post
	 *
	 * @param integer $post_id  The post ID.
	 */
	public function save_post( $post_id ) {
		// Verify nonce.
		if ( isset( $_POST['metabox_nonce'] ) && ! wp_verify_nonce( $_POST['metabox_nonce'], basename( __FILE__ ) ) ) {
			return $post_id;
		}

		// is autosave?
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check permissions.
		if ( isset( $_POST['post_type'] ) ) {
			if ( 'page' === $_POST['post_type'] ) {
				if ( ! current_user_can( 'edit_page', $post_id ) ) {
					return $post_id;
				}
			} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
				return $post_id;
			}
		}

		if ( isset( $_POST['rsrc_post_ids'] ) ) {
			$ids = implode( ',', array_filter( wp_parse_id_list( $_POST['rsrc_post_ids'] ) ) );
			update_post_meta( $post_id, '_rsrc_posts_ids', $ids );
		}

	}

	/**
	 * Find posts
	 */
	public function rsrc_find_posts() {
		global $wpdb;

		check_ajax_referer( 'find-posts' );

		$pt = explode( ',', trim( $_POST['post_type'], ',' ) );
		if ( empty( $_POST['ps'] ) ) {
			$posttype = get_post_type_object( $pt[0] );
			wp_die( $posttype->labels->not_found );
		}
		$post_types = get_post_types(
			array(
				'public'  => true,
				'show_ui' => true,
			)
		);
		$in_array   = array_intersect( $pt, $post_types );
		if ( ! empty( $_POST['post_type'] ) && ! empty( $in_array ) ) {
			$what = "'" . implode( "','", $in_array ) . "'";
		} else {
			$what = 'post';
		}
		$s = stripslashes( $_POST['ps'] );
		preg_match_all( '/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $s, $matches );
		$search_terms = array_map( 'trim', $matches[0] );

		$searchand = '';
		$search    = '';
		foreach ( (array) $search_terms as $term ) {
			$term      = esc_sql( $wpdb->esc_like( $term ) );
			$search   .= "{$searchand}(($wpdb->posts.post_title LIKE '%{$term}%') OR ($wpdb->posts.post_content LIKE '%{$term}%'))";
			$searchand = ' AND ';
		}
		$term = esc_sql( $wpdb->esc_like( $s ) );
		if ( count( $search_terms ) > 1 && $search_terms[0] != $s ) {
			$search .= " OR ($wpdb->posts.post_title LIKE '%{$term}%') OR ($wpdb->posts.post_content LIKE '%{$term}%')";
		}

		$posts = $wpdb->get_results( "SELECT ID, post_title, post_status, post_date, post_type FROM $wpdb->posts WHERE post_type IN ($what) AND post_status NOT IN ( 'revision', 'trash' ) AND ($search) ORDER BY post_date_gmt DESC LIMIT 50" );

		if ( ! $posts ) {
			$posttype = get_post_type_object( $pt[0] );
			wp_die( $posttype->labels->not_found );
		}

		$html = '<table class="widefat" cellspacing="0"><thead><tr><th class="found-radio"><br /></th><th>' . __( 'Title' ) . '</th><th>' . __( 'Type' ) . '</th><th>' . __( 'Date' ) . '</th><th>' . __( 'Status' ) . '</th></tr></thead><tbody>';
		foreach ( $posts as $post ) {

			switch ( $post->post_status ) {
				case 'publish':
				case 'private':
					$stat = __( 'Published' );
					break;
				case 'future':
					$stat = __( 'Scheduled' );
					break;
				case 'pending':
					$stat = __( 'Pending Review' );
					break;
				case 'draft':
					$stat = __( 'Draft' );
					break;
			}

			if ( '0000-00-00 00:00:00' === $post->post_date ) {
				$time = '';
			} else {
				$time = mysql2date( __( 'Y/m/d' ), $post->post_date );
			}
			$posttype = get_post_type_object( $post->post_type );
			$posttype = $posttype->labels->singular_name;
			$html    .= '<tr class="found-posts"><td class="found-radio"><input type="checkbox" id="found-' . $post->ID . '" name="found_post_id[]" value="' . esc_attr( $post->ID ) . '"></td>';
			$html    .= '<td><label for="found-' . $post->ID . '">' . esc_html( $post->post_title ) . '</label></td><td>' . esc_html( $posttype ) . '</td><td>' . esc_html( $time ) . '</td><td>' . esc_html( $stat ) . '</td></tr>';
		}
		$html .= '</tbody></table>';
		wp_send_json_success( $html );
		$x = new WP_Ajax_Response();
		$x->add(
			array(
				'what' => 'post',
				'data' => $html,
			)
		);
		$x->send();
	}

}

Rather_Simple_Related_Content_Admin::get_instance();
