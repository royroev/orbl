<?php
/**
 * Main Custom Icons class.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Fusion-Library
 * @since      2.2
 */

/**
 * Adds Custom Icons feature.
 */
class Fusion_Custom_Icon_Set {

	/**
	 * The one, true instance of this object.
	 *
	 * @static
	 * @access private
	 * @since 6.2
	 * @var object
	 */
	private static $instance;

	/**
	 * WP Filesystem object.
	 *
	 * @access private
	 * @since 6.2
	 * @var object
	 */
	private $wp_filesystem;

	/**
	 * Icons post type handle.
	 *
	 * @access private
	 * @since 6.2
	 * @var string
	 */
	private $post_type = 'fusion_icons';

	/**
	 * Used to cache configs.
	 *
	 * @access private
	 * @since 6.2
	 * @var array
	 */
	private $package_config = [];

	/**
	 * Default post meta values.
	 *
	 * @access private
	 * @since 6.2
	 * @var array
	 */
	private $post_meta_defaults = [
		'attachment_id'     => '',
		'icon_set_dir_name' => '',
		'service'           => 'icomoon',
		'css_prefix'        => '',
		'icons'             => [],
	];

	/**
	 * The class constructor.
	 *
	 * @access private
	 * @since 6.2
	 * @return void
	 */
	private function __construct() {

		$this->wp_filesystem = Fusion_Helper::init_filesystem();

		// Register custom post type.
		add_action( 'init', [ $this, 'register_post_type' ] );

		// Front end scripts.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

		// Live Builders scripts.
		add_action( 'fusion_enqueue_live_scripts', [ $this, 'enqueue_scripts' ] );

		// Dashboard scripts.
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );

		if ( is_admin() ) {

			// Add menu page.
			add_action( 'admin_menu', [ $this, 'register_admin_menu' ], 100 );

			// Add meta box.
			add_action( 'add_meta_boxes_' . $this->post_type, [ $this, 'add_meta_box' ] );

			// Save post meta.
			add_action( 'save_post_' . $this->post_type, [ $this, 'save_post_meta' ], 10, 3 );

			// Cleanup when post is deleted (trash emptied).
			add_action( 'before_delete_post', [ $this, 'delete_icon_set' ], 10, 1 );

			// Update post columns.
			add_action( 'manage_' . $this->post_type . '_posts_custom_column', [ $this, 'render_columns' ], 10, 2 );
			add_filter( 'manage_' . $this->post_type . '_posts_columns', [ $this, 'manage_columns' ], 100 );

			// WIP.
			add_action( 'fusion_custom_icon_set_saved', [ $this, 'process_upload' ], 10, 1 );
		}
	}

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @static
	 * @access public
	 * @since 6.2
	 */
	public static function get_instance() {

		// If an instance hasn't been created and set to $instance create an instance and set it to $instance.
		if ( null === self::$instance ) {
			self::$instance = new Fusion_Custom_Icon_Set();
		}
		return self::$instance;
	}

	/**
	 * Register custom post type.
	 *
	 * @since 6.2
	 * @return void
	 */
	public function register_post_type() {

		$labels = [
			'name'               => _x( 'Custom Icons', 'Avada Icon', 'fusion-builder' ),
			'singular_name'      => _x( 'Icon Set', 'Avada Icon', 'fusion-builder' ),
			'add_new'            => _x( 'Add New', 'Avada Icon', 'fusion-builder' ),
			'add_new_item'       => _x( 'Add New Icon Set', 'Avada Icon', 'fusion-builder' ),
			'edit_item'          => _x( 'Edit Icon Set', 'Avada Icon', 'fusion-builder' ),
			'new_item'           => _x( 'New Icon Set', 'Avada Icon', 'fusion-builder' ),
			'all_items'          => _x( 'All Icon Sets', 'Avada Icon', 'fusion-builder' ),
			'view_item'          => _x( 'View Icon Set', 'Avada Icon', 'fusion-builder' ),
			'search_items'       => _x( 'Search Icon Sets', 'Avada Icon', 'fusion-builder' ),
			'not_found'          => _x( 'No Icon Sets found', 'Avada Icon', 'fusion-builder' ),
			'not_found_in_trash' => _x( 'No Icon Sets found in Trash', 'Avada Icon', 'fusion-builder' ),
			'parent_item_colon'  => '',
			'menu_name'          => _x( 'Custom Icons', 'Avada Icon', 'fusion-builder' ),
		];

		$args = [
			'labels'              => $labels,
			'public'              => false,
			'rewrite'             => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'supports'            => [ 'title' ],
		];

		register_post_type( $this->post_type, $args ); // phpcs:ignore WPThemeReview.PluginTerritory.ForbiddenFunctions.plugin_territory_register_post_type
	}

	/**
	 * Register admin menu.
	 *
	 * @since 6.2
	 * @return void
	 */
	public function register_admin_menu() {
		$menu_title = _x( 'Custom Icons', 'Avada Icons', 'fusion-builder' );

		add_submenu_page( // phpcs:ignore WPThemeReview.PluginTerritory.NoAddAdminPages.add_menu_pages_add_submenu_page
			'fusion-builder-options',
			$menu_title,
			$menu_title,
			'manage_options',
			'edit.php?post_type=' . $this->post_type
		);
	}

	/**
	 * Enqueue front end scripts. WIP.
	 *
	 * @since 6.2
	 * @return void
	 */
	public function enqueue_scripts() {
		global $fusion_library_latest_version;

		$icon_sets = fusion_get_custom_icons_array();

		foreach ( $icon_sets as $key => $icon_set ) {
			if ( isset( $icon_set['css_url'] ) && '' !== $icon_set['css_url'] ) {
				wp_enqueue_style( 'fusion-custom-icons-' . $key, $icon_set['css_url'], [], $fusion_library_latest_version, 'all' );
			}
		}
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @since 6.2
	 * @param string $hook_suffix The current admin page.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {
		global $fusion_library_latest_version, $typenow, $post;

		if ( 'post-new.php' !== $hook_suffix && 'post.php' !== $hook_suffix ) {
			return;
		}

		if ( get_post_type() === $this->post_type ) {

			// Enqueue WP media.
			wp_enqueue_media();

			// Scripts.
			wp_enqueue_script( 'fusion-custom-icons', trailingslashit( FUSION_LIBRARY_URL ) . 'assets/js/general/fusion-custom-icons.js', [ 'jquery' ], $fusion_library_latest_version, false );

			// Styles.
			wp_enqueue_style( 'fusion-custom-icons', trailingslashit( FUSION_LIBRARY_URL ) . 'assets/css/fusion-custom-icons.css', [], $fusion_library_latest_version, 'all' );

			// Icon set is already saved.
			if ( 'post.php' === $hook_suffix ) {
				$css_url = fusion_get_custom_icons_css_url();

				if ( $css_url ) {
					wp_enqueue_style( 'fusion-custom-icons-style', $css_url, [], get_the_ID(), 'all' );
				}
			}
		}

		// Enqueue custom icon's styles.
		if ( isset( $typenow ) && class_exists( 'FusionBuilder' ) && in_array( $typenow, FusionBuilder::allowed_post_types(), true ) ) {

			$icon_sets = fusion_get_custom_icons_array();

			foreach ( $icon_sets as $key => $icon_set ) {
				if ( isset( $icon_set['css_url'] ) && '' !== $icon_set['css_url'] ) {
					wp_enqueue_style( 'fusion-custom-icons-' . $key, $icon_set['css_url'], [], $fusion_library_latest_version, 'all' );
				}
			}
		}
	}

	/**
	 * Add metaboxes.
	 *
	 * @since 6.2
	 * @return void
	 */
	public function add_meta_box() {
		add_meta_box(
			'fusion-custom-icons-metabox',
			__( 'Icon Set', 'fusion-builder' ),
			[ $this, 'render_metabox' ],
			$this->post_type,
			'normal',
			'default'
		);
	}

	/**
	 * Meta box callback, outputs metabox content.
	 *
	 * @since 6.2
	 * @return void
	 */
	public function render_metabox() {
		global $post;

		$icon_set = get_post_meta( $post->ID, FUSION_ICONS_META_KEY, true );

		$icon_set = wp_parse_args( $icon_set, $this->post_meta_defaults );

		$is_new_icon_set = empty( $icon_set['icon_set_dir_name'] ) ? true : false;
		$buton_label     = $is_new_icon_set ? __( 'Upload Custom Icon Set', 'fusion-builder' ) : __( 'Update Custom Icon Set', 'fusion-builder' );
		?>
		<div>
			<input type="hidden" id="fusion-custom-icons-attachment-id" name="fusion-custom-icons[attachment_id]" value="<?php echo esc_attr( $icon_set['attachment_id'] ); ?>">
			<input type="hidden" name="fusion-custom-icons-nonce" value="<?php echo esc_attr( wp_create_nonce( 'fusion-custom-icon-set' ) ); ?>">
		</div>
		<a href="#" id="fusion-custom-icons-upload" data-title="<?php echo esc_attr( $buton_label ); ?>">
			<?php echo esc_html( $buton_label ); ?>
		</a>
		<?php if ( ! $is_new_icon_set ) : ?>
			<input type="hidden" id="fusion-custom-icons-update" name="fusion-custom-icons[icon_set_update]" value="">
			<div class="fusion-custom-icon-preview-wrapper">
			<?php

			// Print icons' markup.
			echo $this->get_icons_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
			</div>
			<?php
		endif;
	}

	/**
	 * Save post meta.
	 *
	 * @since 6.2
	 * @param int    $post_id Post ID.
	 * @param object $post    Post Object.
	 * @param bool   $update  Whether this is an existing post being updated or not.
	 * @return void|int
	 */
	public function save_post_meta( $post_id, $post, $update ) {

		// Early exit if it is autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}

		// Check user's permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Check nonce.
		if ( ! isset( $_POST['fusion-custom-icons-nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['fusion-custom-icons-nonce'] ), 'fusion-custom-icon-set' ) ) { // phpcs:ignore WordPress.Security
			return $post_id;
		}

		// WIP.
		do_action( 'fusion_custom_icon_set_saved', $post_id );
	}

	/**
	 * WIP.
	 *
	 * @since 6.2
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function process_upload( $post_id ) {

		// Early exit if post ID is not valid.
		if ( ! $post_id ) {
			return;
		}

		// Remove icon set files if we're updating.
		if ( isset( $_POST['fusion-custom-icons']['icon_set_update'] ) && 'true' === $_POST['fusion-custom-icons']['icon_set_update'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$this->delete_icon_set( $post_id );
		}

		$icon_set = [];

		// Get $_POST values and set defaults.
		foreach ( $this->post_meta_defaults as $key => $value ) {
			$icon_set[ $key ] = isset( $_POST['fusion-custom-icons'][ $key ] ) ? sanitize_text_field( wp_unslash( $_POST['fusion-custom-icons'][ $key ] ) ) : $this->post_meta_defaults[ $key ]; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		// Return if attachment ID is not set.
		if ( empty( $icon_set['attachment_id'] ) ) {
			return;
		}

		// Create base directory if it's not there.
		if ( ! file_exists( FUSION_ICONS_BASE_DIR ) ) {
			wp_mkdir_p( FUSION_ICONS_BASE_DIR );
		}

		// Get package path.
		$package_path = get_attached_file( $icon_set['attachment_id'] );

		// Create icon set path.
		$icon_set_dir_name = $this->get_unique_dir_name( pathinfo( $package_path, PATHINFO_FILENAME ), FUSION_ICONS_BASE_DIR );
		$icon_set_path     = FUSION_ICONS_BASE_DIR . $icon_set_dir_name;

		// Create icon set directory.
		wp_mkdir_p( $icon_set_path );

		// Attempt to manually extract the zip file first. Required for fptext method.
		$status = false;
		if ( class_exists( 'ZipArchive' ) ) {
			$zip = new ZipArchive();
			if ( true === $zip->open( $package_path ) ) {
				$zip->extractTo( $icon_set_path );
				$zip->close();
				$status = true;
			}
		} else {
			$status = unzip_file( $package_path, $icon_set_path );
		}

		// Update post meta if extract didn't fail.
		if ( true === $status ) {

			$icon_set['icon_set_dir_name'] = $icon_set_dir_name;

			// Parse package.
			$parsed_package = $this->parse_icons_package( $icon_set_dir_name );

			// Update post meta with package data.
			foreach ( $parsed_package as $key => $value ) {
				$icon_set[ $key ] = $parsed_package[ $key ];
			}

			// Finally save post meta.
			update_post_meta( $post_id, FUSION_ICONS_META_KEY, $icon_set );
		}

	}

	/**
	 * Delete icon set directory and do general cleanup.
	 *
	 * @since 6.2
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function delete_icon_set( $post_id ) {

		if ( get_post_type( $post_id ) !== $this->post_type ) {
			return;
		}

		$icon_set      = get_post_meta( $post_id, FUSION_ICONS_META_KEY, true );
		$icon_set_path = FUSION_ICONS_BASE_DIR . $icon_set['icon_set_dir_name'];

		// Delete directory.
		$this->wp_filesystem->rmdir( $icon_set_path, true );
	}

	/**
	 * Render preview column in font manager admin listing
	 *
	 * @since 6.2
	 * @param string $column Columna handle.
	 * @param int    $post_id Post ID.
	 */
	public function render_columns( $column, $post_id ) {

		if ( 'icons_prefix' === $column ) {
			$icon_set = get_post_meta( $post_id, FUSION_ICONS_META_KEY, true );
			if ( ! empty( $icon_set['css_prefix'] ) ) {
				echo '<pre>' . esc_html( '.' . $icon_set['css_prefix'] ) . '</pre>';
			}
		}
	}

	/**
	 * Define which columns to display in font manager admin listing
	 *
	 * @since 6.2
	 * @param array $columns Columns array.
	 * @return array
	 */
	public function manage_columns( $columns ) {
		return [
			'cb'           => '<input type="checkbox" />',
			'title'        => __( 'Icon Set', 'fusion-builder' ),
			'icons_prefix' => __( 'CSS Prefix', 'fusion-builder' ),
		];
	}

	/**
	 * Get unique directory name for passed parent directory.
	 *
	 * @since 6.2
	 * @param string $dir_name Name of the directory.
	 * @param string $parent_dir_path Path of the parent directory.
	 * @return string Unique directory name.
	 */
	protected function get_unique_dir_name( $dir_name, $parent_dir_path ) {

		$parent_dir_path = trailingslashit( $parent_dir_path );
		$dir_path        = $parent_dir_path . $dir_name;

		$counter  = 0;
		$tmp_name = $dir_name;
		while ( file_exists( $dir_path ) ) {
			$counter++;
			$dir_name = $tmp_name . '-' . $counter;
			$dir_path = $parent_dir_path . $dir_name;
		}

		return $dir_name;
	}

	/**
	 * Get package config.
	 *
	 * @since 6.2
	 * @param string $icon_set_dir_name Icon set dir name.
	 * @return array Config array.
	 */
	protected function get_package_config( $icon_set_dir_name ) {

		if ( ! isset( $this->package_config[ $icon_set_dir_name ] ) ) {
			$json_file                                  = $this->wp_filesystem->get_contents( FUSION_ICONS_BASE_DIR . '/' . $icon_set_dir_name . '/selection.json' );
			$this->package_config[ $icon_set_dir_name ] = json_decode( $json_file, true );
		}

		return $this->package_config[ $icon_set_dir_name ];
	}

	/**
	 * Parse package.
	 *
	 * @since 6.2
	 * @param string $icon_set_dir_name Icon set dir name.
	 * @return array Post meta array.
	 */
	protected function parse_icons_package( $icon_set_dir_name ) {

		// Get icons config file.
		$icons_config = $this->get_package_config( $icon_set_dir_name );

		$parsed_package          = [];
		$parsed_package['icons'] = [];

		// Add icons.
		foreach ( $icons_config['icons'] as $icon ) {
			$parsed_package['icons'][] = $icon['properties']['name'];
		}

		// Set icon prefix.
		$parsed_package['css_prefix'] = $icons_config['preferences']['fontPref']['prefix'];

		// Set icon count.
		$parsed_package['icon_count'] = count( $parsed_package['icons'] );

		return $parsed_package;
	}

	/**
	 * Get icon HTML code, for example to be used in a meta box.
	 *
	 * @since 6.2
	 * @param int $post_id Post ID.
	 * @return string HTML code.
	 */
	public function get_icons_html( $post_id = 0 ) {

		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$icon_set = get_post_meta( $post_id, FUSION_ICONS_META_KEY, true );

		$html = '';
		foreach ( $icon_set['icons'] as $icon ) {
			$html .= '<span class="fusion-custom-icon-preview"><i class="' . esc_attr( $icon_set['css_prefix'] . $icon ) . '"></i><span class="fusion-custom-icon-preview-name">' . esc_html( $icon ) . '</span></span>';
		}

		return $html;
	}

}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
