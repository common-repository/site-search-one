<?php
/**
 * Class for managing the SS1 Sync Queue.
 *
 * @package Site_Search_One
 */

/**
 * Class for managing the SS1 Sync Queue.
 */
class Site_Search_One_Queue_Manager {

	const ACTION_UPLOAD_POST       = 0;
	const ACTION_DELETE_POST       = 1;
	const ACTION_SYNC_PAGE         = 2;
	const ACTION_CACHE_SPEC        = 3;
	const ACTION_UPLOAD_ATTACHMENT = 4;

	/**
	 * Empty Constructor.
	 */
	public function __construct() {

	}

	/**
	 * Determine if we want to run a cron task, based on the time since the last cron task ran and the number of tokens remaining.
	 *
	 * @param bool $long_running_threads_disabled Whether or not long running threads are disabled.
	 * When not passed, assumes false.
	 * @return bool
	 */
	public function is_cron_wanted( $long_running_threads_disabled = false ) {
		// It's tempting to check is_queue_paused but remember that cron also covers non queue data updates..
		if ( $this->get_num_queued_tasks() > 0 ) {
			return true;
		}
		$max_wait_time = $long_running_threads_disabled ? 60 : 300;
		if ( $this->get_elapsed_seconds_since_last_ran_cron() > $max_wait_time ) {
			return true; // Been 5 minutes.
		}
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-tokens.php';
		if ( Site_Search_One_Tokens::get_num_tokens_left() < 20 ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the amount of time since last ran cron.
	 *
	 * @return int
	 */
	private function get_elapsed_seconds_since_last_ran_cron() {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_globals';
		$query      = 'SELECT value FROM ' . $table_name . ' WHERE setting="last_ran_cron"';
		$res        = $wpdb->get_var( $query );
		if ( null === $res ) {
			return time();
		} else {
			return ( time() - intval( $res ) );
		}
		//phpcs:enable
	}

	/**
	 * Mark in the database when a cron task was started.
	 * The plugin uses the time the last task was started to determine
	 * if the front-end should send in http requests to run more cron tasks.
	 *
	 * @return bool|WP_Error
	 */
	public function mark_cron_run() {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_globals';
		$replaced   = $wpdb->replace(
			$table_name,
			array(
				'setting' => 'last_ran_cron',
				'value'   => strval( time() ),
			),
			'%s'
		);
		if ( false === $replaced ) {
			return new WP_Error( 'db_error', 'Replace operation failed in mark_cron_run SS1' );
		}
		return true;
		//phpcs:enable
	}

	/**
	 * Enqueue sync of given search page
	 *
	 * @param int $page_id The search page id.
	 */
	public function enqueue_page_sync( $page_id ) {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		Site_Search_One_Debugging::log( 'SS1-INFO Enqueuing initial page sync for page_id ' . $page_id );
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'ss1_sync_queue',
			array(
				'post_id'    => $page_id,
				'index_uuid' => '0',
				'action'     => self::ACTION_SYNC_PAGE,
			)
		);
		//phpcs:enable
	}

	/**
	 * Enqueue cache of indexes field spec.
	 */
	public function enqueue_cache_spec() {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'ss1_sync_queue',
			array(
				'post_id'    => 0,
				'index_uuid' => '0',
				'action'     => self::ACTION_CACHE_SPEC,
			)
		);
		//phpcs:enable
	}

	/**
	 * Perform a sync for search page.
	 *
	 * @param int     $page_id search page id.
	 * @param int     $task_id the task id.
	 * @param int     $resume_from page to resume from.
	 * @param boolean $disable_long_running_threads whether or not long running threads is disabled.
	 * When true, will only scan for a max of 5 seconds before returning with WP_Error code thread_time_exceeded.
	 * @return true|WP_Error
	 */
	public function perform_page_sync( $page_id, $task_id, $resume_from = 1, $disable_long_running_threads = false ) {
		try {
			require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-search-page.php';
			require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-post.php';
			$search_page = Site_Search_One_Search_Page::get_search_page( $page_id );
			// TODO
			// Attachments mean that the previous filtering on post types/category ids when searching for posts
			// to evaluate does not work. Smarter logic for those functions is needed.

			$post_types   = 'any';
			$category_ids = 'any';
			$start_time   = time();
			if ( false !== $search_page ) {
				if ( intval( $resume_from > 0 ) ) {
					Site_Search_One_Debugging::log( 'SS1-INFO Resuming Scan from Post ID ' . $resume_from );
				}
				$index_uuid = $search_page->get_sc1_ix_uuid();
				if ( is_wp_error( $index_uuid ) ) {
					Site_Search_One_Debugging::log( 'SS1-ERROR Error performing page sync. Could not retrieve index uuid for this site' );
					Site_Search_One_Debugging::log( $index_uuid );
					return $index_uuid;
				}
				$page           = $resume_from;
				$posts_per_page = 64;
				$post_ids       = $this->get_posts_paginated( $page, $posts_per_page, $post_types, $category_ids );
				$enqueued       = 1;
				while ( false !== $post_ids ) {
					foreach ( $post_ids as $post_id ) {
						$this->evaluate_post_sp( $post_id, $search_page, $index_uuid, $enqueued );
					}
					++$page;
					$post_ids = $this->get_posts_paginated( $page, $posts_per_page, $post_types, $category_ids );
					if ( false !== $post_ids ) {
						Site_Search_One_Debugging::log( 'SS1-INFO More Pages: There are ' . count( $post_ids ) . ' post ids on page ' . $page );
					}
					$search_page = Site_Search_One_Search_Page::get_search_page( $page_id );
					if ( false === $search_page ) {
						// The search page was since deleted during the scan. Stop the scan.
						return true;
					}
					set_time_limit( 0 );
					if ( ( time() - $start_time ) > 30 ) {
						// At least 30s has elapsed.
						Site_Search_One_Debugging::log( 'SS1-DEBUG At least 30s elapsed whilst performing page sync. Marking task still alive.. ===================' );
						$task_ids   = array();
						$task_ids[] = $task_id;
						$this->keep_tasks_alive( $task_ids );
						$start_time = time();
					}

					$this->set_sync_resume_from_page( $task_id, $page );
					if ( $disable_long_running_threads && ( ( time() - $start_time ) > 5 ) ) {
						// The thread has been running for at least 5 seconds and long running threads disabled.
						return new WP_Error( 'thread_time_exceeded', 'Long running threads disabled' );
					}
					if ( 0 === $enqueued % 256 ) {
						// Every 256 posts upload tasks/removal added to queue, also queue a refresh of the cached spec.
						// Just means the user sees the fields whilst indexing is ongoing. Really the final enqueued
						// cache spec task is the one that matters the most.
						$this->enqueue_cache_spec();
						++$enqueued;
					}
				}
				$this->enqueue_cache_spec();
				return true;
			} else {
				// Search page does not exist. Just consider it complete.
				return true;
			}
		} catch ( Exception $ex ) {

			Site_Search_One_Debugging::log( 'SS1-ERROR - Error performing initial scan:' );
			Site_Search_One_Debugging::log( $ex );
			return new WP_Error( 'unhandled_error', 'An exception occurred', $ex );
		}
	}

	/**
	 * Find all media attached to post id and evaluate if should be uploaded or deleted etc, will enqueue tasks to this
	 * end too.
	 *
	 * @param int    $post_id the post id.
	 * @param string $post_status the post status. Pass the parent post status.
	 * @return void
	 */
	public function evaluate_attachments( $post_id, $post_status ) {
		$attachments = get_attached_media( '', $post_id );
		foreach ( $attachments as $attachment ) {
			$attachment_id = $attachment->ID;
			$res           = $this->evaluate_post( $attachment_id, $post_status );
			if ( is_wp_error( $res ) ) {
				Site_Search_One_Debugging::log( 'SS1-ERROR Error whilst evaluating attachment:' );
				Site_Search_One_Debugging::log( $res );
			}
		}
	}

	/**
	 * Completely re-evaluates a post against all search pages. This function should not be used in bulk, it's intended
	 * for handling attachments when a parent post changes status
	 *
	 * @param int  $post_id the post id.
	 * @param bool $post_status false|string
	 *        Override the post status - Useful for attachments on post status transitions as their status inherits but post
	 *        status functions return old status.
	 * @return true|WP_Error
	 */
	public function evaluate_post( $post_id, $post_status = false ) {
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-search-page.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-post.php';
		$search_pages = Site_Search_One_Search_Page::get_all_search_pages();
		if ( is_wp_error( $search_pages ) ) {
			return $search_pages;
		}
		foreach ( $search_pages as $search_page ) {
			$index_uuid = $search_page->get_sc1_ix_uuid();
			if ( ! is_wp_error( $index_uuid ) ) {
				$ignored = 0;
				$this->evaluate_post_sp( $post_id, $search_page, $index_uuid, $ignored, $post_status );
			}
		}
		return true;
	}

	/**
	 * Evaluates if the given post should be in the given search page.
	 * Will add the post to upload queue if it should be and it isn't, and will add to deletion queue if it is and it
	 * shouldn't be.
	 *
	 * @param int                         $post_id Post to evaluate.
	 * @param Site_Search_One_Search_Page $search_page the search page.
	 * @param string                      $index_uuid SC1 Index UUID. Search Page's Index UUID.
	 * @param int                         $enqueued running count of enqueued posts.
	 * Pass by reference. Incremented if a new task is added to the queue (including deletion task).
	 * @param bool                        $post_status The post publish status.
	 * When passed, overrides detected post status, useful for attachments during parent post status transition.
	 * @return void
	 */
	private function evaluate_post_sp( $post_id, $search_page, $index_uuid, &$enqueued = 0, $post_status = false ) {
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-search-page.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-post.php';
		if ( false === $post_status ) {
			$post_status = get_post_status( $post_id );
		}
		$ss1_post = new Site_Search_One_Post( $post_id );
		if ( 'publish' === $post_status ) {
			// The post is published, check if it meets upload criteria.
			if ( $search_page->does_post_meet_upload_criteria( $post_id ) ) {

				// The post currently meets upload criteria
				// Check if it's already uploaded..
				if ( $ss1_post->is_uploaded_to_search_page( $search_page ) === false ) {
					// Not already uploaded, needs to be uploaded.
					$this->add_post_to_upload_queue( $post_id, $index_uuid );
					++$enqueued;
				}
			} else {
				// The post currently does not meet upload criteria.
				if ( $ss1_post->is_uploaded_to_search_page( $search_page ) ) {
					// The post should be removed.
					$this->add_post_to_removal_queue( $post_id, $index_uuid );
					++$enqueued;
				}
			}
		} else {
			// The post is not published. Make sure it's not uploaded and if it is remove.
			if ( $ss1_post->is_uploaded_to_search_page( $search_page ) ) {
				$this->add_post_to_removal_queue( $post_id, $index_uuid );
				++$enqueued;
			}
		}
	}

	/**
	 * Set the page the sync should resume from, as a backup in case this thread ends.
	 *
	 * @param int $task_id The current task id.
	 * @param int $min_page the page sync resumes from.
	 *
	 * @return bool
	 */
	private function set_sync_resume_from_page( $task_id, $min_page ) {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name    = $wpdb->prefix . 'ss1_sync_queue';
		$query         = $wpdb->prepare(
			'UPDATE ' . $table_name . ' SET index_uuid = %s WHERE id=%d',
			strval( $min_page ),
			intval( $task_id )
		);
		$rows_affected = $wpdb->query( $query );
		if ( false === $rows_affected ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Failed to set resume from post id ' . $min_page . ' for task ' . $task_id );
			return false;
		}
		return true;
		//phpcs:enable
	}

	/**
	 * Retrieve an array of posts of any type/publish status
	 *
	 * @param int          $page The page in paginated result set.
	 * @param int          $posts_per_page The number of posts per page in paginated result set.
	 * @param string|array $post_type The post type(s) to search for, or 'any'.
	 * @param string|array $category_ids The category id's to search for, or 'any'.
	 * @return int[]|false Array of post_id or false on no more posts
	 */
	private function get_posts_paginated( $page, $posts_per_page = 64, $post_type = 'any', $category_ids = 'any' ) {
		$query_args = array(
			'posts_per_page' => $posts_per_page,
			'paged'          => $page,
			'post_type'      => $post_type,
			'fields'         => 'ids',
			'order'          => 'ASC',
			'orderby'        => 'post_date',
			'post_status'    => 'any', // Required to catch attachments as they have post_status inherit.
		);
		if ( 'any' !== $category_ids ) {
			$query_args['category__in'] = $category_ids;
		}
		$query_result = new WP_Query( $query_args );
		$ids          = $query_result->posts;
		if ( count( $ids ) === 0 ) {
			return false;
		}
		return $ids;
	}

	// **
	// * Recursive call. Gets the 'true' post status, handling the case the post_status is inherit in
	// * which case the parent is checked. This will keep repeating until parent is not 'inherit' or 5 levels
	// * reached
	// * @param $post_id
	// * @param $status_str
	// *
	// * @return mixed|string
	// */
	// private function get_calculated_post_status($post_id, $status_str, $level = 0) {
	// ++$level;
	// if ($level >= 5) return '???'; // Avoid infinite loop in case of weird database edit
	// if ($status_str !== 'inherit') return $status_str;
	// else {
	// It inherits from its parent...
	// $parent = get_post_parent($post_id);
	// if ($parent === null) return '???'; // Should not be possible
	// return $this->get_calculated_post_status($parent->ID, $parent->post_status, $level);
	// }
	// }

	/**
	 * Add the Specified Post to the Upload Queue
	 *
	 * @param int    $post_id The post id.
	 * @param string $index_uuid SC1 Index UUID.
	 * @return bool success
	 */
	public function add_post_to_upload_queue( $post_id, $index_uuid ) {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		// region 1. Check if post is already in Queue but upload not started yet
		// If the upload has already started, we treated the queued action as an update to the post, and thus still
		// add it to the queue even though it is already uploading.

		$query   = $wpdb->prepare(
			'SELECT * FROM `'
			. $wpdb->prefix
			. 'ss1_sync_queue` WHERE `post_id`=%d AND `index_uuid`=%s AND `started` IS NULL AND `action`=%d',
			$post_id,
			$index_uuid,
			self::ACTION_UPLOAD_POST
		);
		$results = $wpdb->get_results( $query );
		if ( count( $results ) > 0 ) {
			Site_Search_One_Debugging::log( 'SS1-WARNING Post is already in upload queue but command sent to add it again - Skipped' );
			return true; // This is already a queued upload. No need to add to queue again, simply return true.
		}
		// endregion
		// region 2. Insert into DB.
		$queued =
			$wpdb->insert(
				$wpdb->prefix . 'ss1_sync_queue',
				array(
					'post_id'    => $post_id,
					'index_uuid' => $index_uuid,
					'action'     => self::ACTION_UPLOAD_POST,
				)
			);
		return ( false !== $queued );
		// endregion.
		//phpcs:enable
	}

	/**
	 * Attempt to require premium functionality. May return false if premium plugin not installed.
	 *
	 * @return bool|WP_Error
	 */
	private static function try_require_premium_functions() {
		if ( class_exists( 'Site_Search_One_Premium_Functions' ) ) {
			return true;
		} else {
			$plugins = get_plugins();
			foreach ( $plugins as $plugin_dir => $plugin ) {
				if ( 'Site Search ONE Premium' === $plugin['Name'] ) {
					$install_loc = get_option( 'site-search-one-premium-install-location' );
					if ( false !== $install_loc ) {
						$path = $install_loc . '/admin/class-site-search-one-p-functions.php';
						if ( file_exists( $path ) ) {
							require_once $path;

							return true;
						} else {
							return new WP_Error( 'plugin_file_not_found', 'Site Search ONE Installation is missing a required file', $path );
						}
					}
				}
			}
		}
		return new WP_Error( 'plugin_not_installed', 'Site Search ONE Premium does not appear to be installed' );
	}

	/**
	 * Queue the specified post to be removed from index on SC1
	 *
	 * @param int    $post_id The post id.
	 * @param string $index_uuid SC1 Index UUID.
	 * @return bool success
	 */
	public function add_post_to_removal_queue( $post_id, $index_uuid ) {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$queued = $wpdb->insert(
			$wpdb->prefix . 'ss1_sync_queue',
			array(
				'post_id'    => $post_id,
				'index_uuid' => $index_uuid,
				'action'     => self::ACTION_DELETE_POST,
			)
		);
		return ( false !== $queued );
		//phpcs:enable
	}
	/**
	 * Get number of queued tasks. NOTE includes tasks in progress.
	 *
	 * @return string|null count
	 */
	public function get_num_queued_tasks() {
		// Query database, retrieve number of queued uploads. Includes uploads in progress.
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;

		return $wpdb->get_var(
			'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'ss1_sync_queue WHERE post_id IS NOT NULL AND completed IS NULL'
		);
		//phpcs:enable
	}

	/**
	 * Determine if a sync task is already running.
	 *
	 * @return bool
	 */
	public function get_is_already_running() {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$query   = 'SELECT COUNT(*) FROM '
				. $wpdb->prefix . 'ss1_sync_queue WHERE `started` > (NOW() - INTERVAL 1 MINUTE) AND `completed` IS NULL';
		$results = intval( $wpdb->get_var( $query ) );
		if ( null !== $results ) {
			return $results > 1;
		} else {
			Site_Search_One_Debugging::log( 'SS1-ERROR Failed to check if cron already running. DB Error. Assuming it already is.' );
			return true;
		}
		//phpcs:enable
	}

	/**
	 * Check if running queued tasks is paused.
	 * Will first check transient, and fall back to db if necessary.
	 *
	 * @return bool true for paused, false for not paused.
	 */
	public function is_queue_paused() {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		$paused = false; // Workaround - Transient doesn't seem to update if updated from another thread. Just use db.
		if ( false === $paused ) {
			// transient not yet set.
			// Check if pause set in db.
			global $wpdb;
			$table_name = $wpdb->prefix . 'ss1_globals';
			$query      = $wpdb->prepare(
				'SELECT value FROM ' . $table_name . ' WHERE setting=%s',
				'ss1-queue-paused'
			);
			$result     = $wpdb->get_var( $query );
			if ( null !== $result ) {
				// A value is set in DB, but avoid hitting DB in future.
				set_transient( 'ss1-queue-paused', $result, 0 );
				return ( 'paused' === $result );
			} else {
				// Not set in DB. Set it in the DB now and as transient.
				$this->set_queue_paused( false );
				return false;
			}
		}
		return 'paused' === $paused;
		//phpcs:enable
	}

	/**
	 * Pause or unpause the queue.
	 *
	 * @param bool $paused Pause.
	 *
	 * @return bool|int
	 */
	public function set_queue_paused( $paused ) {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		if ( $paused ) {
			$val = 'paused';
		} else {
			$val = 'not_paused';
		}
		set_transient( 'ss1-queue-paused', $val, 0 );
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_globals';
		$query      = $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . $table_name . ' WHERE `setting` = %s',
			'ss1-queue-paused'
		);
		$count      = $wpdb->get_var( $query );
		if ( null === $count ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Failed to check if ss1-queue-paused set in DB - Query returned null' );
		}
		if ( intval( $count ) === 0 ) {
			// Setting must be inserted.
			return $wpdb->insert(
				$table_name,
				array(
					'setting' => 'ss1-queue-paused',
					'value'   => $val,
				)
			);
		} else {
			// Update existing setting.
			return $wpdb->update(
				$table_name,
				array(
					'value' => $val,
				),
				array(
					'setting' => 'ss1-queue-paused',
				),
				'%s',
				'%s'
			);
		}
		//phpcs:enable
	}

	/**
	 * Get the next queued task
	 * Action might be an upload or deletion.
	 *
	 * @param bool $skip_busy_check Whether or not to skip checking if busy.
	 *
	 * @return array|false false if no more actions
	 */
	public function get_next_queued_task( $skip_busy_check = false ) {
		$this->check_for_timeouts();
		if ( false === $skip_busy_check ) {
			$busy = $this->is_task_saturated();
			if ( false !== $busy ) {
				if ( is_wp_error( $busy ) ) {
					Site_Search_One_Debugging::log( 'SS1-ERROR Failed to check if server is busy:' );
					Site_Search_One_Debugging::log( $busy );
				}
				return false;
			}
		}
		// region 1. Query database for queued upload that hasn't started uploading yet.
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$results = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'ss1_sync_queue WHERE started IS NULL AND completed IS NULL LIMIT 1' );
		// endregion
		// region 2. If found, return the post id and index uuid, else return false.
		if ( count( $results ) > 0 ) {
			$result     = $results[0];
			$task_id    = $result->id;
			$action     = $result->action;
			$post_id    = $result->post_id;
			$index_uuid = $result->index_uuid;
			return array(
				'task_id'    => $task_id,
				'action'     => $action,
				'post_id'    => $post_id,
				'index_uuid' => $index_uuid,
			);
		} else {
			return false;
		}
		// endregion.
		//phpcs:enable
	}

	/**
	 * Check if the task after task_id is an upload task. Checks out the task.
	 * If it is, return an array describing the task. <b>The task will be checked out!!</b>,
	 * else returns false
	 *
	 * @param int $task_id The task id.
	 * @return array|false
	 */
	public function is_next_task_id_an_upload_and_checks_out( $task_id ) {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$query = 'SELECT * FROM ' . $wpdb->prefix . 'ss1_sync_queue WHERE id > %d '
				. 'AND started IS NULL AND completed IS NULL ORDER BY id LIMIT 1';

		$results = $wpdb->get_results(
			$wpdb->prepare( $query, $task_id )
		);
		if ( count( $results ) > 0 ) {
			// There are more tasks in the queue.
			$result  = $results[0];
			$task_id = $result->id;
			$action  = intval( $result->action );
			switch ( $action ) {
				case self::ACTION_UPLOAD_POST:
				case self::ACTION_UPLOAD_ATTACHMENT:
					break;
				default:
					// Not an upload.
					return false;
			}
			$post_id    = $result->post_id;
			$index_uuid = $result->index_uuid;
			if ( $this->checkout_task( $task_id ) ) {
				// The next task is an upload, and successfully checked it out.
				return array(
					'task_id'    => $task_id,
					'action'     => $action,
					'post_id'    => $post_id,
					'index_uuid' => $index_uuid,
				);
			} else {
				// The next task is an upload, but failed to check the task out.
				Site_Search_One_Debugging::log( 'SS1-WARNING The next task is an upload, but failed to check it out' );
				return false;
			}
		} else {
			// end of the queue.
			return false;
		}
		//phpcs:enable
	}

	/**
	 * Check if there are are already too many tasks currently running
	 *
	 * @return bool
	 */
	private function is_task_saturated() {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_sync_queue';
		$query      = 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE started IS NOT NULL AND completed IS NULL';
		$count      = intval( $wpdb->get_var( $query ) );
		return ( $count > 1 );
		//phpcs:enable
	}

	/**
	 * Detects any tasks that have been running for longer than 2 minutes, marks them as failed tasks.
	 */
	private function check_for_timeouts() {
		$sync_timeouts = $this->check_sync_timeouts();
		if ( is_wp_error( $sync_timeouts ) ) {
			return $sync_timeouts;
		}
		$upload_timeouts = $this->check_upload_task_timeouts();
		if ( is_wp_error( $upload_timeouts ) ) {
			return $upload_timeouts;
		}
		$deletion_timeouts = $this->check_delete_timeouts();
		if ( is_wp_error( $deletion_timeouts ) ) {
			return $deletion_timeouts;
		}
		return true;
	}

	/**
	 * Check for any deletion timeouts.
	 *
	 * @return bool|WP_Error
	 */
	private function check_delete_timeouts() {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name    = $wpdb->prefix . 'ss1_sync_queue';
		$query         = 'UPDATE ' . $table_name . ' SET started = NULL WHERE `started` < (NOW() - INTERVAL 2 MINUTE) AND completed IS NULL AND action = 1';
		$rows_affected = $wpdb->query( $query );
		if ( false === $rows_affected ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Failed to retrieve delete timeouts' );
			return new WP_Error( 'failed_check_timeouts', 'Database error checking for delete tasks that have timed out' );
		} else {
			if ( $rows_affected > 0 ) {
				Site_Search_One_Debugging::log( 'SS1-INFO Found ' . $rows_affected . ' deletion timeouts' );
			}
			return true;
		}
		//phpcs:enable
	}

	/**
	 * Check for sync timeouts
	 *
	 * @return bool|WP_Error
	 */
	private function check_sync_timeouts() {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name    = $wpdb->prefix . 'ss1_sync_queue';
		$query         = 'UPDATE ' . $table_name . ' SET started = NULL WHERE `started` < (NOW() - INTERVAL 2 MINUTE) AND completed IS NULL AND action = 2';
		$rows_affected = $wpdb->query( $query );
		if ( false === $rows_affected ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Failed to retrieve timeouts' );
			return new WP_Error( 'failed_check_timeouts', 'Database error checking for tasks that have timed out' );
		} else {
			if ( $rows_affected > 0 ) {
				Site_Search_One_Debugging::log( 'SS1-INFO Found ' . $rows_affected . ' sync timeouts' );
			}
			return true;
		}
		//phpcs:enable
	}

	/**
	 * Check for any upload timeouts.
	 *
	 * @return bool|WP_Error
	 */
	private function check_upload_task_timeouts() {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name    = $wpdb->prefix . 'ss1_sync_queue';
		$query         = 'UPDATE ' . $table_name . ' SET started = NULL WHERE `started` < (NOW() - INTERVAL 4 MINUTE) AND completed IS NULL AND action != 2';
		$rows_affected = $wpdb->query( $query );
		if ( false === $rows_affected ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Failed to retrieve timeouts' );
			return new WP_Error( 'failed_check_timeouts', 'Database error checking for tasks that have timed out' );
		} else {
			if ( $rows_affected > 0 ) {
				Site_Search_One_Debugging::log( 'SS1-INFO Found ' . $rows_affected . ' upload timeouts' );
			}
			return true;
		}
		//phpcs:enable
	}

	/**
	 * Mark a task as having been started
	 *
	 * @param int $task_id The task id.
	 * @return bool|WP_Error
	 */
	public function checkout_task( $task_id ) {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name    = $wpdb->prefix . 'ss1_sync_queue';
		$query         = $wpdb->prepare(
			'UPDATE ' . $table_name . ' SET started=NOW() WHERE id=%d AND started IS NULL',
			$task_id
		);
		$rows_affected = $wpdb->query( $query );
		if ( false === $rows_affected ) {
			Site_Search_One_Debugging::log( "SS1-ERROR Failed to mark $task_id as updating" );
			return new WP_Error( 'db_error', 'Failed to mark ' . $task_id . ' as started - Database error' );
		} else {
			if ( $rows_affected > 0 ) {
				return true;
			} else {
				Site_Search_One_Debugging::log( 'SS1-INFO Did not mark ' . $task_id . ' as started because already started' );
				return false;
			}
		}
		//phpcs:enable
	}

	/**
	 * Keep given task ids alive.
	 *
	 * @param array $task_ids task ids.
	 *
	 * @return bool
	 */
	public function keep_tasks_alive( $task_ids ) {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name     = $wpdb->prefix . 'ss1_sync_queue';
		$sql            = 'UPDATE ' . $table_name . ' SET started = NOW() WHERE id IN(';
		$count_task_ids = count( $task_ids );
		for ( $i = 0; $i < $count_task_ids; $i++ ) {
			if ( $i > 0 ) {
				$sql .= ',';
			}
			$sql .= '%d';
		}
		$sql          .= ')';
		$query         = $wpdb->prepare(
			$sql,
			...$task_ids // Argument Unpacking.
		);
		$rows_affected = $wpdb->query( $query );
		if ( false === $rows_affected ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR DB Error keeping tasks alive' );
			return false;
		}
		if ( count( $task_ids ) === $rows_affected ) {
			return true;
		} else {
			Site_Search_One_Debugging::log( 'SS1-Error Task-keepalive, Rows Affected is ' . $rows_affected . ' but was expecting ' . count( $task_ids ) );
			return false;
		}
		//phpcs:enable
	}

	/**
	 * Mark the given task ids as completed.
	 *
	 * @param array $task_ids The task ids which completed.
	 * @return bool
	 */
	public function mark_tasks_complete( $task_ids ) {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name     = $wpdb->prefix . 'ss1_sync_queue';
		$sql            = 'UPDATE ' . $table_name . ' SET completed = NOW() WHERE id IN(';
		$count_task_ids = count( $task_ids );
		for ( $i = 0; $i < $count_task_ids; $i++ ) {
			if ( $i > 0 ) {
				$sql .= ',';
			}
			$sql .= '%d';
		}
		$sql .= ')';

		$query         = $wpdb->prepare(
			$sql,
			...$task_ids // Argument Unpacking.
		);
		$rows_affected = $wpdb->query( $query );
		if ( false === $rows_affected ) {
			Site_Search_One_Debugging::log( 'SS1-Error DB Error Marking Task Ids complete' );
			return false;
		} else {
			if ( count( $task_ids ) === $rows_affected ) {
				return true;
			} else {
				Site_Search_One_Debugging::log( 'SS1-Error Rows Affected is ' . $rows_affected . ' but was expecting ' . count( $task_ids ) );
				return false;
			}
		}
		//phpcs:enable
	}

	/**
	 * Mark the given task as complete.
	 *
	 * @param int $task_id The task id.
	 */
	public function mark_task_complete( $task_id ) {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name    = $wpdb->prefix . 'ss1_sync_queue';
		$query         = $wpdb->prepare(
			'UPDATE ' . $table_name . ' SET completed = NOW() WHERE id=%d',
			$task_id
		);
		$rows_affected = $wpdb->query( $query );
		if ( false === $rows_affected ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Failed to mark ' . $task_id . ' as complete' );
		}
		//phpcs:enable
	}

	/**
	 * Mark given task as failed.
	 *
	 * @param int $task_id The task id.
	 */
	public function mark_task_failed( $task_id ) {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_sync_queue';
		$data       = array(
			'started'   => null,
			'completed' => null,
		);
		$where      = array(
			'id' => $task_id,
		);
		$updated    = $wpdb->update( $table_name, $data, $where );
		if ( false === $updated ) {
			// Error updating...
			Site_Search_One_Debugging::log( "SS1-ERROR Failed to mark $task_id as failed" );
		} else {
			Site_Search_One_Debugging::log( "SS1-INFO Marked $task_id as failed" );
		}
		//phpcs:enable
	}
}
