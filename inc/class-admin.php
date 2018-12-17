<?php
/**
 * Our Admin class.
 *
 * This handles our main admin page
 *
 * @package stylepress
 */

namespace StylePress;

defined( 'STYLEPRESS_VERSION' ) || exit;

/**
 * All the magic happens here.
 *
 * Class Admin
 */
class Admin extends Base {


	/**
	 * Initializes the plugin and sets all required filters.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_action_stylepress_new_style', array( $this, 'stylepress_new_style' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ) );
		add_action( 'admin_action_stylepress_save', array( $this, 'stylepress_save' ) );
	}

	/**
	 * This is our custom "Full Site Builder" menu item that appears under the appearance tab.
	 *
	 * @since 2.0.0
	 */
	public function admin_menu() {


		add_menu_page( __( 'StylePress', 'stylepress' ), __( 'StylePress', 'stylepress' ), 'manage_options', 'stylepress', array(
			$this,
			'sections_page_callback',
		), STYLEPRESS_URI . 'assets/images/icon.png' );
		// hack to rmeove default submenu
		$page = add_submenu_page( 'stylepress', __( 'Sections', 'stylepress' ), __( 'Sections', 'stylepress' ), 'manage_options', 'stylepress', array(
			$this,
			'sections_page_callback'
		) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'admin_page_assets' ) );

		$page = add_submenu_page( 'stylepress', __( 'Styles', 'stylepress' ), __( 'Styles', 'stylepress' ), 'manage_options', 'stylepress-styles', array(
			$this,
			'default_styles_page_callback'
		) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'admin_page_assets' ) );

		$page = add_submenu_page( 'stylepress', __( 'Settings', 'stylepress' ), __( 'Settings', 'stylepress' ), 'manage_options', 'stylepress-settings', array(
			$this,
			'settings_page_callback'
		) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'admin_page_assets' ) );

	}

	/**
	 * Font Awesome and other assets for admin pages.
	 *
	 * @since 2.0.0
	 */
	public function admin_page_assets() {

		wp_enqueue_script( 'stylepress-admin', STYLEPRESS_URI . 'assets/js/admin.min.js', array( 'jquery' ), STYLEPRESS_VERSION, true );

		require_once STYLEPRESS_PATH . 'views/_help_text.php';

	}

	/**
	 * This is our callback for rendering our custom menu page.
	 * This page shows all our site styles and currently selected defaults.
	 *
	 * @since 2.0.0
	 */
	public function sections_page_callback() {
		$this->content = $this->render_template(
			'admin/sections.php', [
			]
		);
		$this->header  = $this->render_template( 'admin/header.php' );
		echo $this->render_template( 'wrapper.php' );
	}
	/**
	 * This is our callback for rendering our custom menu page.
	 * This page shows all our site styles and currently selected defaults.
	 *
	 * @since 2.0.0
	 */
	public function default_styles_page_callback() {
		$this->content = $this->render_template(
			'admin/styles.php', [
			]
		);
		$this->header  = $this->render_template( 'admin/header.php' );
		echo $this->render_template( 'wrapper.php' );
	}

	/**
	 * This is our callback for rendering our custom menu page.
	 * This page shows all our site styles and currently selected defaults.
	 *
	 * @since 2.0.0
	 */
	public function settings_page_callback() {
		$this->content = $this->render_template(
			'admin/settings.php', [
			]
		);
		$this->header  = $this->render_template( 'admin/header.php' );
		echo $this->render_template( 'wrapper.php' );
	}


	public function stylepress_new_style() {
		// Check if our nonce is set.
		if ( ! isset( $_POST['stylepress_new_style'] ) ) { // WPCS: input var okay.
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['stylepress_new_style'], 'stylepress_new_style' ) ) { // WPCS: sanitization ok. input var okay.
			return;
		}

		$new_style_name = stripslashes( sanitize_text_field( trim( $_POST['new_style_name'] ) ) );
		$new_category   = sanitize_text_field( trim( $_POST['new_style_category'] ) );

		if ( ! $new_style_name ) {
			wp_die( 'Please go back and enter a new style name' );
		}

		if ( ! $new_category ) {
			wp_die( 'No category found' );
		}

		$post_id = wp_insert_post( [
			'post_type'   => Styles::CPT,
			'post_status' => 'publish',
			'post_title'  => $new_style_name,
		], true );
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			die( 'Failed to create new style' );
		}

		wp_set_object_terms( $post_id, $new_category, STYLEPRESS_SLUG . '-cat', false );


		wp_redirect( admin_url( 'admin.php?page=stylepress&saved#cat-' . $new_category ) );
		exit;

	}

	public function stylepress_save() {

		// Check if our nonce is set.
		if ( ! isset( $_POST['stylepress_save_options'] ) ) { // WPCS: input var okay.
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['stylepress_save_options'], 'stylepress_save_options' ) ) { // WPCS: sanitization ok. input var okay.
			return;
		}

		$page_types       = Settings::get_instance()->get_all_page_types();
		$categories       = Styles::get_instance()->get_categories();
		$defaults_to_save = [];

		$user_provided_defaults = [];
		$is_advanced_settings   = ! empty( $_POST['stylepress_advanced'] );
		Settings::get_instance()->set( 'stylepress_advanced', $is_advanced_settings );
		if ( $is_advanced_settings ) {
			if ( isset( $_POST['default_style'] ) && is_array( $_POST['default_style'] ) ) {
				$user_provided_defaults = $_POST['default_style'];
			}
		} else {
			// simple styles.
			if ( isset( $_POST['default_style_simple'] ) && is_array( $_POST['default_style_simple'] ) ) {
				$user_provided_defaults = $_POST['default_style_simple'];
			}
		}
		foreach ( $page_types as $page_type => $page_type_name ) {
			$defaults_to_save[ $page_type ] = [];
			if ( isset( $user_provided_defaults[ $page_type ] ) && is_array( $user_provided_defaults[ $page_type ] ) ) {
				// store defaults for each page type here.
				foreach ( $categories as $category ) {
					if ( isset( $user_provided_defaults[ $page_type ][ $category['slug'] ] ) ) {
						$chosen_default = $user_provided_defaults[ $page_type ][ $category['slug'] ];
						$valid_answers  = Styles::get_instance()->get_all_styles( $category['slug'], true );
						if ( isset( $valid_answers[ $chosen_default ] ) ) {
							$defaults_to_save[ $page_type ][ $category['slug'] ] = $chosen_default;
						}
					}
				}
			}
		}

		foreach ( $defaults_to_save as $default_page_type => $default_styles ) {
			if ( $default_page_type !== '_global' ) {
				$defaults_to_save[ $default_page_type ] = array_merge( $defaults_to_save['_global'], $default_styles );
			}
		}

		Settings::get_instance()->set( 'stylepress_styles', $defaults_to_save );

		wp_redirect( admin_url( 'admin.php?page=stylepress-settings&saved' ) );
		exit;


	}


	/**
	 * Adds a meta box to every post type.
	 *
	 * @since 2.0.0
	 */
	public function add_meta_box() {

		if ( Plugin::get_instance()->has_permission() ) {
			$post_types = get_post_types();
			foreach ( $post_types as $post_type ) {
				if ( ! in_array( $post_type, array( Styles::CPT, 'elementor_library' ), true ) ) {
					add_meta_box(
						'stylepress_style_metabox',
						__( 'StylePress', 'stylepress' ),
						array( $this, 'meta_box_display' ),
						$post_type,
						'side',
						'high'
					);
				}
			}

		}

	}

	/**
	 * This renders our metabox on most page/post types.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Post $post Current post object.
	 */
	public function meta_box_display( $post ) {

		if ( Plugin::get_instance()->has_permission( $post ) ) {

			include_once STYLEPRESS_PATH . 'metaboxes/post-meta-box.php';

		}
	}

	/**
	 * Saves our metabox details, which is the style for a particular page.
	 *
	 * @since 2.0.0
	 *
	 * @param int $post_id The post we're current saving.
	 */
	public function save_meta_box( $post_id ) {
		// Check if our nonce is set.
		if ( ! isset( $_POST['stylepress_style_nonce'] ) ) { // WPCS: input var okay.
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['stylepress_style_nonce'], 'stylepress_style_nonce' ) ) { // WPCS: sanitization ok. input var okay.
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['stylepress_style'] ) && is_array( $_POST['stylepress_style'] ) ) { // WPCS: sanitization ok. input var okay.
			$default_styles = [];
			foreach ( $_POST['stylepress_style'] as $page_type ) {
				// sanitise each one.
			}
			update_post_meta( $post_id, 'stylepress_style', $_POST['stylepress_style'] ); // WPCS: sanitization ok. input var okay.
		}

	}


}

