<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       www.electronart.co.uk
 * @since      1.0.0
 *
 * @package    Site_Search_One
 * @subpackage Site_Search_One/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Site_Search_One
 * @subpackage Site_Search_One/admin
 * @author     Thomas Harris <thomas.harris@electronart.co.uk>
 */
class Site_Search_One_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Hook Handler for Admin Menu
	 */
	public function admin_menu() {
		//phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$svg_data = file_get_contents( plugin_dir_path( __FILE__ ) . 'menu-icon.svg' );
		$icon     = base64_encode( $svg_data );
		//phpcs:enable
		// https://developer.wordpress.org/reference/functions/add_menu_page/.
		require_once plugin_dir_path( __FILE__ ) . 'class-sc1-index-manager.php';
		$api_key = SC1_Index_Manager::get_sc1_api_key();
		if ( ! $api_key ) {
			// The user has not yet configured the API Key that this plugin will use.
			// As such, the only option on the menu should be to setup.
			add_menu_page(
				'Setup',
				'Site Search ONE',
				'manage_options',
				plugin_dir_path( __FILE__ ) . 'view-set-api-key.php',
				'',
				'data:image/svg+xml;base64,' . $icon
			);
			return;
		}
		add_menu_page(
			'Site Search ONE', // page title.
			'Site Search ONE', // menu title.
			'manage_options', // required capabilities.
			plugin_dir_path( __FILE__ ) . 'view-search-pages.php',
			'', // function, not used.
			'data:image/svg+xml;base64,' . $icon
		);
		add_submenu_page(
			plugin_dir_path( __FILE__ ) . 'view-search-pages.php',
			'Site Search ONE',
			__( 'All Search Pages', 'site-search-one' ),
			'manage_options',
			plugin_dir_path( __FILE__ ) . 'view-search-pages.php'
		);
		$slug = plugin_dir_path( __FILE__ ) . 'view-new-search-page.php';
		add_submenu_page(
			plugin_dir_path( __FILE__ ) . 'view-search-pages.php',
			'Site Search ONE', // page title.
			__( 'New Search Page', 'site-search-one' ), // menu title.
			'manage_options', // required capabilities.
			$slug,
			'' // function, not used.
		);
		add_submenu_page(
			plugin_dir_path( __FILE__ ) . 'view-search-pages.php',
			'Site Search ONE',
			__( 'Global Settings', 'site-search-one' ),
			'manage_options',
			plugin_dir_path( __FILE__ ) . 'view-global-settings.php',
			'' // function, not used.
		);
		add_submenu_page(
			null,
			'Site Search ONE',
			'Customise Search Page',
			'manage_options',
			plugin_dir_path( __FILE__ ) . 'view-customise-search-page.php'
		);
	}

	/**
	 * Echo out the area where the Admin bar/info will shown..
	 */
	public function admin_notices() {       ?>
		<div class="notice notice-info hidden" id="ss1-ongoing-dsp"></div>
		<?php
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-endpoints.php';
		if ( Site_Search_One_Endpoints::is_using_test_server() ) {
			?>
			<div class="notice notice-warning">
				<p><strong>Site Search ONE</strong></p>
				<p>Warning - Plugin is using the test server</p>
			</div>
			<?php
		}
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-queue-manager.php';
		$queue_manager = new Site_Search_One_Queue_Manager();
		$num_in_queue  = $queue_manager->get_num_queued_tasks();
		if ( $num_in_queue > 0 ) {
			wp_schedule_single_event( time(), 'ss1_cron_hook' );
		}
		// region Display get-started notice if no api-key set.
		global $wp;
		$request_uri = filter_input( INPUT_SERVER, 'REQUEST_URI' );
		require_once plugin_dir_path( __FILE__ ) . 'class-sc1-index-manager.php';
		if ( SC1_Index_Manager::ss1_is_long_running_threads_disabled() === 'yes' ) {
			?>
			<div class="notice notice-warning">
				<p><strong>Site Search ONE</strong></p>
				<p>Long running threads disabled - Admin page must be kept open when Sync is running.</p>
			</div>
			<?php
		}
		$api_key = SC1_Index_Manager::get_sc1_api_key();
		if (
				$request_uri &&
				false === $api_key && // API Key is not set.
				strpos( $request_uri, 'view-set-api-key.php' ) === false // And we're not on the set API Key page.
		) {
			?>
			<div class="notice notice-info">
				<p><strong>Site Search ONE</strong></p>
				<p>Ready to create your first search page?</p>

				<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) ) . 'view-set-api-key.php' ); ?>" class="button-secondary">Get Started</a></p>
			</div>
			<?php
		}
		// endregion.
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Site_Search_One_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Site_Search_One_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/site-search-one-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Site_Search_One_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Site_Search_One_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'js/site-search-one-admin.js',
			array( 'jquery' ),
			$this->version,
			false
		);

		require_once plugin_dir_path( __FILE__ ) . 'class-sc1-index-manager.php';
		$disable_long_threads = SC1_Index_Manager::ss1_is_long_running_threads_disabled();
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-queue-manager.php';
		$queue_manager = new Site_Search_One_Queue_Manager();
		$paused        = $queue_manager->is_queue_paused();
		$remaining     = $queue_manager->get_num_queued_tasks();

		$script_arguments = array(
			'ss1_rest_ongoing_url'          => rest_url( 'ss1_client/v1/ongoing' ),
			'ss1_rest_cron_hack'            => rest_url( 'ss1_client/v1/cronhack' ),
			'ss1_rest_options'              => rest_url( 'ss1_client/v1/options' ),
			// translators: The number of items in sync queue remaining.
			'ss1_syncing_msg'               => __( 'Syncing - %s items remaining', 'site-search-one' ),
			'ss1_sync_complete_msg'         => __( 'Sync Complete!', 'site-search-one' ),
			'ss1_paused_msg'                => __( 'Syncing Paused', 'site-search-one' ),
			'ss1_btn_pause_txt'             => __( 'Pause', 'site-search-one' ),
			'ss1_btn_resume_txt'            => __( 'Resume', 'site-search-one' ),
			'ss1_pause_msg'                 => __( 'Pause Syncing', 'site-search-one' ),
			'ss1_disableLongRunningThreads' => $disable_long_threads,
			'ss1_paused'                    => $paused,
			'ss1_remaining'                 => $remaining,
		);
		wp_localize_script(
			$this->plugin_name,
			'php_vars',
			$script_arguments
		);
	}

	/**
	 * The Site Search Plugin hooks post status transitions to detect when posts are published/unpublished, deleted etc
	 *
	 * @link https://codex.wordpress.org/Post_Status_Transitions
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post The WordPress post.
	 * @param boolean $menu_update When true, the post itself was not updated, but rather it was assigned/removed from a menu.
	 */
	public function on_all_post_status_transitions( $new_status, $old_status, $post, $menu_update = false ) {
		try {
			$post_id   = $post->ID;
			$post_type = get_post_type( $post_id );

			if ( 'nav_menu_item' === $post_type ) {
				$this->handle_nav_menu_item_publish_status_change( $post_id, $new_status, $old_status );
				return;
			}
			// region Check that SearchCloudOne credentials are already configured and if not return early.
			require_once plugin_dir_path( __FILE__ ) . 'class-sc1-index-manager.php';
			$sc1_api_key = SC1_Index_Manager::get_sc1_api_key();
			if ( ! $sc1_api_key ) {
				return;
			}
			// endregion
			// region Update the queue as appropriate.
			require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-queue-manager.php';
			require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-post.php';
			$queue_manager = new Site_Search_One_Queue_Manager();
			if ( 'attachment' !== $post_type ) {
				$queue_manager->evaluate_attachments( $post_id, $new_status );
			}
			$ss1_post                    = new Site_Search_One_Post( $post_id );
			$indexes_already_uploaded_to = $ss1_post->get_indexes_uploaded_to();
			if ( 'publish' === $new_status ) {
				// Post was either newly created or updated.
				$indexes_should_upload_to = $this->determine_indexes_to_upload_to( $post_id );
				foreach ( $indexes_already_uploaded_to as $index_already_uploaded_to ) {
					$delete_from_this_index = true;
					foreach ( $indexes_should_upload_to as $index_should_upload_to ) {
						if ( $index_should_upload_to === $index_already_uploaded_to ) {
							$delete_from_this_index = false;
							break;
						}
					}
					if ( true === $delete_from_this_index ) {
						// The post has been updated in such a way it should no longer belong to this index.
						$queue_manager->add_post_to_removal_queue( $post_id, $index_already_uploaded_to );
					}
				}
				foreach ( $indexes_should_upload_to as $index_uuid ) {
					if ( false === $menu_update ) {
						$queue_manager->add_post_to_upload_queue( $post_id, $index_uuid );
					} else {
						// The post wasnt actually uploaded, just its menu assignments were
						// The post meets criteria after menu update, but might already be uploaded, if so, don't upload as the post didnt change.
						$already_uploaded = false;
						foreach ( $indexes_already_uploaded_to as $index_already_uploaded_to ) {
							if ( $index_already_uploaded_to === $index_uuid ) {
								$already_uploaded = true;
							}
						}
						if ( false === $already_uploaded ) {
							$queue_manager->add_post_to_upload_queue( $post_id, $index_uuid );
						}
					}
				}
				Site_Search_One_Debugging::log(
					'SS1-INFO Post ' . $post->ID . ' published, found '
						. count( $indexes_should_upload_to ) . ' indexes to upload to'
				);
			} elseif ( 'publish' === $old_status ) {
				Site_Search_One_Debugging::log( 'SS1-INFO Post ' . $post->ID . 'unpublished' );
				// A published post was unpublished/deleted
				// Remove it from all indexes it has been marked as uploaded to.

				$ss1_post                    = new Site_Search_One_Post( $post_id );
				$indexes_already_uploaded_to = $ss1_post->get_indexes_uploaded_to();
				foreach ( $indexes_already_uploaded_to as $index_uuid ) {
					$queue_manager->add_post_to_removal_queue( $post_id, $index_uuid );
				}
			}
			// endregion
			// region Schedule Cron event to handle newly queued actions
			// Check if a Cron event is already scheduled...
			$cron_next_event = wp_next_scheduled( 'ss1_cron_hook' );
			if ( false === $cron_next_event ) {
				wp_schedule_single_event( time(), 'ss1_cron_hook' );
			}
			// endregion.
		} catch ( Exception $exception ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Exception handling Post Status Change:' );
			Site_Search_One_Debugging::log( $exception );
		}
	}

	/**
	 * Callback for when a post in the WordPress installation is deleted.
	 *
	 * @param int          $post_id The post id that was deleted.
	 * @param null|WP_Post $post The WordPress post.
	 */
	public function on_delete_post( $post_id = -1, $post = null ) {
		if ( null === $post ) {
			return;
		}
		if ( 'nav_menu_item' === $post->post_type ) {
			$meta = get_post_meta( $post_id );
			if ( 'post_type' === $meta['_menu_item_type'][0] && 'page' === $meta['_menu_item_object'][0] ) {
				$page_id = $meta['_menu_item_object_id'][0];
				$status  = get_post_status( $page_id );
				$this->on_all_post_status_transitions( $status, $status, get_post( $page_id ), true ); // re-evaluates which indexes this post should belongs to and schedules uploads/deletions as such.
			}
		}
	}

	/**
	 * Callback for when an attachment is added to a post.
	 *
	 * @param int $post_id WordPress post id that the attachment was added to.
	 */
	public function on_add_attachment( $post_id ) {
		$post_status = get_post_status( $post_id );
		if ( 'publish' === $post_status ) {
			$this->on_all_post_status_transitions( 'publish', 'draft', get_post( $post_id ) );
		}
	}

	/**
	 * Callback for when an attachment was edited.
	 *
	 * @param int $post_id The Post ID the attachment is attached to.
	 */
	public function on_edit_attachment( $post_id ) {
		$post_status = get_post_status( $post_id );
		$this->on_all_post_status_transitions( $post_status, $post_status, get_post( $post_id ) );
	}

	/**
	 * Callback for when an attachment was deleted from a post.
	 *
	 * @param int $post_id The Post id that the attachment was removed from.
	 */
	public function on_delete_attachment( $post_id ) {
		$post_status = get_post_status( $post_id );
		$this->on_all_post_status_transitions( 'delete_attachment', 'publish', get_post( $post_id ) );
	}

	/**
	 * Called when a Menu Navigation Item is added or removed from a menu.
	 * WordPress internally stores menu items as 'posts'.. an item being added to menu is 'published'
	 *
	 * @param int    $nav_menu_item_id Navigation Menu Item.
	 * @param string $new_status New nav publish status.
	 * @param string $old_status Old nav publish status.
	 */
	private function handle_nav_menu_item_publish_status_change( $nav_menu_item_id, $new_status, $old_status ) {
		$meta = get_post_meta( $nav_menu_item_id );
		if ( 'draft' === $new_status && 'new' === $old_status ) {
			// The menu item was newly added to the menu, but the user hasnt saved yet, so we dont do anything here.
			return;
		}
		Site_Search_One_Debugging::log( 'Nav Menu Meta:' );
		Site_Search_One_Debugging::log( $meta );

		if ( 'post_type' === $meta['_menu_item_type'][0] && 'page' === $meta['_menu_item_object'][0] ) {
			$page_id = $meta['_menu_item_object_id'][0];
			$status  = get_post_status( $page_id );
			$this->on_all_post_status_transitions( $status, $status, get_post( $page_id ), true ); // re-evaluates which indexes this post should belongs to and schedules uploads/deletions as such.
		}

	}

	/**
	 * Determine which IndexUUID's a post needs to be uploaded into
	 *
	 * @param int $post_id ID of Post.
	 * @return array Array of index_uuid's upon which the post should be uploaded into if any
	 */
	private function determine_indexes_to_upload_to( $post_id ) {
		// region 1. Retrieve list of Search Pages.
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-search-page.php';
		$search_pages = Site_Search_One_Search_Page::get_all_search_pages();
		// endregion.
		// region 2. For each Search Page, check if Post matches criteria to Index and has not already been indexed.
		$index_uuids = array();
		foreach ( $search_pages as $search_page ) {
			if ( $search_page->does_post_meet_upload_criteria( $post_id ) ) {
				$index_uuid = $search_page->get_sc1_ix_uuid();
				if ( ! is_wp_error( $index_uuid ) ) {
					array_push( $index_uuids, $index_uuid );
				}
			}
		}
		// endregion.
		return $index_uuids;
	}

	/**
	 * Get the Search Engine Results Page ID (Creates it if it does not exist).
	 *
	 * @return int|WP_Error
	 */
	public static function get_serp_page() {
		return self::get_or_generate_page( 'ss1_serp', 'Site Search ONE - SERP' );
	}

	/**
	 * Gets the ID of the Widget Page (Creates it if it does not exist).
	 *
	 * @return int|WP_Error
	 */
	public static function get_widget_page() {
		return self::get_or_generate_page( 'ss1_widget', 'Site Search ONE - Widget' );
	}

	/**
	 * Gets the ID of the Hitviewer Page (Creates it if it does not exist).
	 *
	 * @return int|WP_Error
	 */
	public static function get_hitviewer_page() {
		return self::get_or_generate_page( 'ss1_hitviewer', 'Site Search ONE - HitViewer' );
	}

	/**
	 * Gets the ID of the PDF Viewer Page (Creates it if it does not exist).
	 *
	 * @return int|WP_Error
	 */
	public static function get_pdfviewer_page() {
		return self::get_or_generate_page( 'ss1_pdfviewer', 'Site Search ONE - PDFViewer' );
	}

	/**
	 * Returns ID of first post of type, or creates if does not exist.
	 *
	 * @param string $post_type The post type to search for.
	 * @param string $post_title The post title of the post to create, if post of type does not exist.
	 * @return int|WP_Error
	 */
	private static function get_or_generate_page( $post_type, $post_title ) {
		$posts = get_posts(
			array(
				'post_type' => $post_type,
			)
		);
		if ( count( $posts ) === 0 ) {
			Site_Search_One_Debugging::log( 'SS1-INFO Creating ' . $post_title );
			$inserted = wp_insert_post(
				array(
					'post_type'    => $post_type,
					'post_title'   => $post_title,
					'post_content' => 'ignored',
					'post_status'  => 'publish',
				)
			);
			if ( is_wp_error( $inserted ) ) {
				return $inserted;
			}
			if ( 0 === $inserted ) {
				return new WP_Error( 'failed_create_' . $post_type, 'Failed to Create ' . $post_title );
			}
			// flush_rewrite_rules is fix for 404 on immediately opening this page..
			// https://wordpress.stackexchange.com/questions/202859/custom-post-type-pages-are-not-found.
			// Acknowledge this is expensive operation, but required after creating post type. This is only done once.
			//phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
			flush_rewrite_rules( false );
			return $inserted;
			//phpcs:enable
		} else {
			return $posts[0]->ID;
		}
	}



}
