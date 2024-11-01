<?php
/**
 * Class for representing an SS1 Search Page
 *
 * @package Site_Search_One
 */

/**
 * Class for representing an SS1 Search Page
 */
class Site_Search_One_Search_Page {

	/**
	 * Search Page ID
	 *
	 * @var int
	 */
	private $id;
	/**
	 * Whether or not pages are indexed by default.
	 * When true, all pages EXCEPT those in ss1_ix_pages for given index should be indexed.
	 * When false, ONLY pages in ss1_ix_pages for given index should be indexed.
	 *
	 * @var bool
	 */
	private $ix_pages;
	/**
	 * Whether or not posts are indexed by default.
	 * When true, all pages EXCEPT those in ss1_ix_pages for given index should be indexed.
	 * When false, ONLY posts in ss1_ix_pages for given index should be indexed.
	 *
	 * @var bool
	 */
	private $ix_posts;
	/**
	 * Array of Post Category Id's in which posts should be indexed.
	 *
	 * @var array
	 */
	private $cat_ids;
	/**
	 * The Post ID associated with this search page.
	 *
	 * @var int
	 */
	private $post_id;
	/**
	 * Not used any more - We switched from a one to one relationship between search page and index uuid, to
	 * a one to many, because of staging sites. Must instead call get_sc1_ix_uuid/get_sc1_ix_id methods.
	 *
	 * @var string
	 * @deprecated
	 */
	private $deprecated_sc1_ix_uuid;
	/**
	 * Display options for the search page.
	 *
	 * @var string
	 */
	private $display_opts;
	/**
	 * Taxonomy Term Ids. When greater than zero, indexed posts are filtered on taxonomy.
	 *
	 * @var array
	 */
	private $tax_term_ids;
	/**
	 * Array of post_ids of search pages that should also be shown in index select drop down on this search page.
	 *
	 * @var array
	 */
	private $also_show;
	/**
	 * When non null, search page indexes on specific post types.
	 *
	 * @var null|array
	 */
	private $post_types;
	/**
	 * When non null, array of category ids that pages (not posts) should be filtered on during indexing
	 *
	 * @var null|array
	 */
	private $page_cat_ids;
	/**
	 * When non null, taxonomy term ids that media items should be filtered on during indexing.
	 *
	 * @var null|array
	 */
	private $media_term_ids;
	/**
	 * Whether or not indexer should only index media that is attached to a post for this search page.
	 *
	 * @var bool
	 */
	private $attached_media_only;
	/**
	 * When non null, an array of mimetypes that media must be of be indexed.
	 *
	 * @var null|array
	 */
	private $media_mime_types;

	/**
	 * Cached Index ID for this Search Page/WordPress site. May be null if not yet cached.
	 *
	 * @var null|int
	 */
	private $site_sc1_ix_id = null;
	/**
	 * Cached Index UUID for this Search Page/WordPress site. May be null if not yet cached.
	 *
	 * @var null|string
	 */
	private $site_sc1_ix_uuid = null;

	/**
	 * Constructor for a Search Page.
	 *
	 * @param int        $id Search Page ID.
	 * @param bool       $ix_pages Whether/not to index pages by default.
	 * @param bool       $ix_posts Whether/not to index posts by default.
	 * @param array      $cat_ids Category Ids posts should belong to.
	 * @param int        $post_id Post ID of this search page.
	 * @param string     $sc1_ix_uuid SC1 Index UUID.
	 * @param mixed      $display_opts Display Options for search page.
	 * @param array|null $tax_term_ids Taxonomy Terms posts should belong to.
	 * @param array|null $also_show Search pages also shown in index drop down for this search page.
	 * @param array|null $post_types Post types that are indexed.
	 * @param int        $sc1_ix_id SC1 Index ID.
	 * @param array|null $page_cat_ids Category Id's Pages are filtered on.
	 * @param array|null $media_term_ids Term Id's Media is filtered on.
	 * @param bool       $attached_media_only Whether/not to index attached media.
	 * @param array|null $media_mime_types Mime types to filter media on.
	 */
	private function __construct(
		$id, $ix_pages, $ix_posts, $cat_ids,
		$post_id, $sc1_ix_uuid, $display_opts,
		$tax_term_ids, $also_show, $post_types,
		$sc1_ix_id, $page_cat_ids, $media_term_ids,
		$attached_media_only, $media_mime_types
	) {
		$this->id                     = $id;
		$this->ix_pages               = $ix_pages;
		$this->ix_posts               = $ix_posts;
		$this->cat_ids                = $cat_ids;
		$this->post_id                = $post_id;
		$this->deprecated_sc1_ix_uuid = $sc1_ix_uuid;
		$this->display_opts           = $display_opts;
		$this->tax_term_ids           = $tax_term_ids;
		$this->also_show              = $also_show;
		$this->post_types             = $post_types;
		$this->page_cat_ids           = $page_cat_ids;
		$this->media_term_ids         = $media_term_ids;
		$this->attached_media_only    = $attached_media_only;
		$this->media_mime_types       = $media_mime_types;

		if ( null !== $this->deprecated_sc1_ix_uuid
			&& 'disused' !== $this->deprecated_sc1_ix_uuid ) {
			// This property will only be not null from a site that has not yet
			// Upgraded to the version where we switched to a one to many relationship
			// Between WP Installation and IndexUUID. In this case we now need to
			// Associate the Index UUID with the Site URL, and then null out the property.
			Site_Search_One_Debugging::log( 'SS1-WARN Upgrading a search page to use new index system. ' . $this->deprecated_sc1_ix_uuid );
			if ( $this->associate_self_with_index_uuid( $this->deprecated_sc1_ix_uuid ) === false ) {
				Site_Search_One_Debugging::log( 'SS1-ERROR Failed to associate self with index uuid' );
			}

			// Now null out the values in the table so this won't happen again.
			global $wpdb;
			$table_name   = $wpdb->prefix . 'ss1_search_pages';
			$data         = array(
				'sc1_ix_id'   => null,
				'sc1_ix_uuid' => 'disused',
			);
			$format       = array(
				'%d',
				'%s',
			);
			$where        = array(
				'post_id' => $this->post_id,
			);
			$where_format = array(
				'%d',
			);
			//phpcs:disable WordPress.DB.DirectDatabaseQuery
			$wpdb->update( $table_name, $data, $where, $format, $where_format );
			//phpcs:enable
		}
	}

	/**
	 * Attempt to retrieve the Index ID for this site. Note that this is specific to the current Site URL
	 * If no index exists specific to this Site URL, an attempt will be made to create a new one.
	 *
	 * @return int|WP_Error
	 */
	public function get_sc1_ix_id() {
		if ( null === $this->site_sc1_ix_uuid ) {
			// The index may not have been created yet. Or the values simply are not cached.
			$cached = $this->get_sc1_ix_uuid();
			if ( is_wp_error( $cached ) ) {
				return $cached;
			}
		}
		if ( null === $this->site_sc1_ix_id ) {
			// UUID is cached, but ID is not yet known... try to fetch it.
			require_once plugin_dir_path( __FILE__ ) . 'class-sc1-index-manager.php';
			$ix_mgr = new SC1_Index_Manager();
			$res    = $ix_mgr->retrieve_index_id_from_sc1( $this->site_sc1_ix_uuid );
			if ( is_wp_error( $res ) ) {
				return $res;
			}
			$this->site_sc1_ix_id = $res;
		}

		return $this->site_sc1_ix_id;
	}

	/**
	 * For WP_Query only.
	 * Get indexed post types in this search page. Returns 'any' if any post types to match WP_Query
	 * Also returns 'any' if attached media is not filtered.
	 * Will include the 'page' post type if ix_pages is true
	 *
	 * @return string|array
	 */
	public function get_indexed_post_types() {
		if ( $this->ix_pages && ! $this->ix_posts ) {
			return 'page';
		}
		if ( null === $this->post_types ) {
			return 'any';
		}
		$post_types = $this->post_types;
		if ( $this->ix_pages ) {
			array_push( $post_types, 'page' );
		}
		if ( ! $this->get_index_attached_media_only() ) {
			return 'any';
		}
		return $post_types;
	}

	/**
	 * Get all indexed category ids. For WP_Engine only, includes both page category ids and post category ids.
	 * May return 'any' if one or the other is not filtered.
	 */
	public function get_indexed_category_ids() {
		if ( count( $this->cat_ids ) === 0 ) {
			return 'any';
		}
		if ( null === $this->page_cat_ids || count( $this->page_cat_ids ) === 0 ) {
			return 'any';
		}
		return array_merge( $this->cat_ids, $this->page_cat_ids );
	}

	/**
	 * Attempt to retrieve the Index UUID for this site. Note that this is specific to the current Site URL
	 * If no index exists specific to this Site URL, an attempt will be made to create a new one.
	 *
	 * @return string|WP_Error
	 */
	public function get_sc1_ix_uuid() {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		if ( null !== $this->site_sc1_ix_uuid ) {
			return $this->site_sc1_ix_uuid; // Value is already cached. Return that.
		}
		$wp_site_url = get_site_url();
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_sc1_indexes';
		$query      = $wpdb->prepare(
			'SELECT sc1_ix_uuid, sc1_ix_id FROM ' . $table_name . ' WHERE wp_site_url=%s AND post_id=%d',
			base64_encode( $wp_site_url ),
			$this->post_id
		);
		$results    = $wpdb->get_results( $query );
		if ( count( $results ) > 0 ) {
			// We have an index for this search page/site url.
			$this->site_sc1_ix_uuid = $results[0]->sc1_ix_uuid;
			$this->site_sc1_ix_id   = $results[0]->sc1_ix_id;
		} else {
			$result = $this->start_index_recreate( true );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}
		return $this->site_sc1_ix_uuid;
		//phpcs:enable
	}

	/**
	 * Retrieves the last cached modified date from SC1 where the plugin saved the index meta specifications.
	 * May return false if the site url does not exist.
	 */
	public function get_sc1_ix_modified() {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		global $wpdb;
		$table_name  = $wpdb->prefix . 'ss1_sc1_indexes';
		$wp_site_url = get_site_url();
		$query       = $wpdb->prepare(
			'SELECT cached_meta_spec_sc1_modified FROM ' . $table_name . ' WHERE wp_site_url=%s AND post_id=%d',
			base64_encode( $wp_site_url ),
			$this->post_id
		);
		$results     = $wpdb->get_results( $query );
		if ( count( $results ) > 0 ) {
			return $results[0]->cached_meta_spec_sc1_modified;
		}
		return false;
		//phpcs:enable
	}

	/**
	 * Get the cached meta spec for this search page, if there is one cached.
	 *
	 * @return string|null
	 */
	public function get_cached_meta_spec() {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		global $wpdb;
		$table_name  = $wpdb->prefix . 'ss1_sc1_indexes';
		$wp_site_url = get_site_url();
		$query       = $wpdb->prepare(
			'SELECT cached_meta_spec FROM ' . $table_name . ' WHERE wp_site_url=%s AND post_id=%d AND cached_meta_spec IS NOT NULL',
			base64_encode( $wp_site_url ),
			$this->post_id
		);
		return $wpdb->get_var( $query );
		//phpcs:enable
	}

	/**
	 * Cached TopFieldValues part of response
	 *
	 * @return string|null
	 */
	public function get_cached_select_values() {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		global $wpdb;
		$table_name  = $wpdb->prefix . 'ss1_sc1_indexes';
		$wp_site_url = get_site_url();
		$query       = $wpdb->prepare(
			'SELECT cached_top_select_field_values FROM ' . $table_name . ' WHERE wp_site_url=%s AND post_id=%d AND cached_top_select_field_values IS NOT NULL',
			base64_encode( $wp_site_url ),
			$this->post_id
		);
		return $wpdb->get_var( $query );
		//phpcs:enable
	}

	/**
	 * Returns the category id if the search page indexes precisely one category, else returns false.
	 *
	 * @return false|int
	 */
	public function indexes_one_category() {
		$categories = array();
		if ( true === $this->ix_posts ) {
			if ( count( $this->cat_ids ) > 0 ) {
				foreach ( $this->cat_ids as $cat_id ) {
					array_push( $categories, $cat_id );
				}
			}
		}
		if ( count( $categories ) > 1 ) {
			return false;
		}
		if ( null !== $this->page_cat_ids ) {
			foreach ( $this->page_cat_ids as $page_cat_id ) {
				array_push( $categories, $page_cat_id );
			}
		}
		$categories = array_unique( $categories );
		if ( count( $categories ) === 1 ) {
			return $categories[0];
		} else {
			return false;
		}
	}

	/**
	 * Get search page display options.
	 *
	 * @return mixed|string
	 */
	public function get_display_opts() {
		return $this->display_opts;
	}

	/**
	 * Get the index name of this search page, as should be shown in the index select drop down on SERP.
	 *
	 * @return string
	 */
	public function get_ix_name() {
		if ( null === $this->display_opts ) {
			return get_the_title( $this->post_id );
		}
		if ( ! property_exists( $this->display_opts, 'ix_name' ) ) {
			return get_the_title( $this->post_id );
		}
		if ( null === $this->display_opts->ix_name ) {
			return get_the_title( $this->post_id );
		}
		if ( '' === $this->display_opts->ix_name ) {
			return get_the_title( $this->post_id );
		}
		// Done in a hurry, probably a better way to do the above...
		return $this->display_opts->ix_name;
	}

	/**
	 * Get Search Pages (Essentially, indexes) that should also be searchable from this search page
	 *
	 * @return array
	 */
	public function get_also_shown_searchpages() {
		$pages = array();
		foreach ( $this->also_show as $post_id ) {
			$page = self::get_search_page( $post_id );
			if ( false !== $page ) {
				array_push( $pages, $page );
			}
		}
		return $pages;
	}

	/**
	 * Set Search Pages (indexes) that should also be searchable from within this search page
	 *
	 * @param array $post_ids search page post_ids.
	 * @return false|int Rows affected or false on error
	 */
	public function set_also_shown_searchpages( $post_ids ) {
		$post_ids_csv = implode( ',', $post_ids );
		global $wpdb;
		$table_name   = $wpdb->prefix . 'ss1_search_pages';
		$data         = array(
			'also_show' => $post_ids_csv,
		);
		$where        = array(
			'post_id' => $this->post_id,
		);
		$format       = array(
			'%s',
		);
		$where_format = array(
			'%d',
		);
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->update(
			$table_name,
			$data,
			$where,
			$format,
			$where_format
		);
		//phpcs:enable
	}

	/**
	 * Check if search page with post_id is also shown search page
	 *
	 * @param int $post_id Search page post id.
	 * @return bool
	 */
	public function is_also_shown_searchpage( $post_id ) {
		if ( null !== $this->also_show ) {
			foreach ( $this->also_show as $also_shown_post_id ) {
				if ( intval( $also_shown_post_id ) === intval( $post_id ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Get the array of mime types that are indexed by default.
	 *
	 * @return string[]
	 */
	public static function get_default_allowed_mime_types() {
		return array(
			'text/plain',
			'text/csv',
			'text/tab-separated-values',
			'text/calendar',
			'text/richtext',
			'text/html',
			'application/rtf',
			'application/pdf',
			'application/msword',
			'application/vnd.ms-powerpoint',
			'application/vnd.ms-write',
			'application/vnd.ms-excel',
			'application/vnd.ms-access',
			'application/vnd.ms-project',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'application/vnd.ms-word.document.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
			'application/vnd.ms-word.template.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'application/vnd.ms-excel.sheet.macroEnabled.12',
			'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
			'application/vnd.ms-excel.template.macroEnabled.12',
			'application/vnd.ms-excel.addin.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
			'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.presentationml.template',
			'application/vnd.ms-powerpoint.template.macroEnabled.12',
			'application/vnd.ms-powerpoint.addin.macroEnabled.12',
			'application/vnd.openxmlformats-officedocument.presentationml.slide',
			'application/vnd.ms-powerpoint.slide.macroEnabled.12',
			'application/onenote',
			'application/oxps',
			'application/vnd.ms-xpsdocument',
			'application/vnd.oasis.opendocument.text',
			'application/vnd.oasis.opendocument.presentation',
			'application/vnd.oasis.opendocument.spreadsheet',
			'application/vnd.oasis.opendocument.graphics',
			'application/vnd.oasis.opendocument.chart',
			'application/vnd.oasis.opendocument.database',
			'application/vnd.oasis.opendocument.formula',
			'application/wordperfect',
			'application/vnd.apple.keynote',
			'application/vnd.apple.numbers',
			'application/vnd.apple.pages',
		);
	}

	/**
	 * Check if the given mime type is an indexed media mime type in this search page.
	 *
	 * @param string $mime_type Mime type.
	 * @return bool
	 */
	public function is_indexed_mime_type( $mime_type ) {
		if ( null !== $this->media_mime_types ) {
			return in_array( $mime_type, $this->media_mime_types, true );
		} else {
			$allowed_mimes = self::get_default_allowed_mime_types();
			return in_array( $mime_type, $allowed_mimes, true );
		}
	}

	/**
	 * Get search page's indexed mime types.
	 *
	 * @return string[]
	 */
	public function get_indexed_mime_types() {
		if ( null !== $this->media_mime_types ) {
			return $this->media_mime_types;
		} else {
			return self::get_default_allowed_mime_types();
		}
	}

	/**
	 * Update an existing search page's indexing settings.
	 * Note - Does not trigger recreate or update, must be triggered afterwards
	 *
	 * @param bool       $ix_pages Whether/not to index pages by default.
	 * @param bool       $ix_posts Whether/not to index posts by default.
	 * @param array      $cat_ids Category Ids posts should belong to.
	 * @param array      $menu_ids Not currently used.
	 * @param array|null $tax_term_ids Taxonomy Terms posts should belong to.
	 * @param array|null $post_types Post types that are indexed.
	 * @param array|null $page_cat_ids Category Id's Pages are filtered on.
	 * @param array|null $media_term_ids Term Id's Media is filtered on.
	 * @param bool       $attached_media_only Whether/not to index attached media.
	 * @param array|null $media_mime_types Mime types to filter media on.
	 * @return false|int false on failure, otherwise rows affected
	 */
	public function edit_indexing_options( $ix_pages, $ix_posts, $cat_ids, $menu_ids, $tax_term_ids, $post_types, $page_cat_ids, $media_term_ids, $attached_media_only, $media_mime_types ) {
		$this->ix_pages            = $ix_pages;
		$this->ix_posts            = $ix_posts;
		$this->cat_ids             = $cat_ids;
		$this->tax_term_ids        = $tax_term_ids;
		$this->post_types          = $post_types;
		$this->page_cat_ids        = $page_cat_ids;
		$this->media_term_ids      = $media_term_ids;
		$this->attached_media_only = $attached_media_only;
		$this->media_mime_types    = $media_mime_types;

		$insert_post_types = null;
		if ( null !== $post_types ) {
			$insert_post_types = implode( ',', $post_types );
		}

		if ( null !== $page_cat_ids ) {
			$page_cat_ids = implode( ',', $page_cat_ids );
		}
		if ( null !== $media_term_ids ) {
			$media_term_ids = implode( ',', $media_term_ids );
		}
		if ( null !== $media_mime_types ) {
			$media_mime_types = implode( ',', $media_mime_types );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_search_pages';
		$data       = array(
			'ix_pages'            => $ix_pages,
			'ix_posts'            => $ix_posts,
			'cat_ids'             => implode( ',', $cat_ids ),
			'menu_ids'            => implode( ',', $menu_ids ),
			'tax_term_ids'        => implode( ',', $tax_term_ids ),
			'post_types'          => $insert_post_types,
			'page_cat_ids'        => $page_cat_ids,
			'media_term_ids'      => $media_term_ids,
			'attached_media_only' => $attached_media_only,
			'media_mime_types'    => $media_mime_types,
		);
		Site_Search_One_Debugging::log( 'SS1-INFO Editing Indexing Options:' );
		Site_Search_One_Debugging::log( $data );
		$where        = array(
			'post_id' => $this->post_id,
		);
		$format       = array(
			'%d',
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
			'%s',
		);
		$where_format = array(
			'%d',
		);
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		return $wpdb->update(
			$table_name,
			$data,
			$where,
			$format,
			$where_format
		);
		//phpcs:enable
	}

	/**
	 * Determine if a field should be displayed in the filters drop down.
	 * If the user has indicated to display all fields, always returns true
	 * Otherwise returns true if matches one of the user specified field names
	 *
	 * @param string $field_name the field name.
	 *
	 * @return bool
	 */
	public function should_display_in_filter_dropdown( $field_name ) {
		$filter_options = $this->display_opts->fields;
		$filter_mode    = $filter_options[0];
		switch ( $filter_mode ) {
			case 'all':
				return true;
			case 'include':
				$i                    = 1;
				$count_filter_options = count( $filter_options );
				while ( $i < $count_filter_options ) {
					if ( $field_name === $filter_options[ $i ] ) {
						return true;
					}
					++$i;
				}
				return false;
			case 'exclude':
				$ii                   = 1;
				$count_filter_options = count( $filter_options );
				while ( $ii < $count_filter_options ) {
					if ( $field_name === $filter_options[ $ii ] ) {
						return false;
					}
					++$ii;
				}
				return true;
		}
		return true;
	}

	/**
	 * Not to be called lightly. Triggers the existing SC1 index to be deleted. Enqueues a scan which will enqueue
	 * uploads of all posts meeting criteria to the selected index. Recreation is far from immediate and happens async
	 *
	 * @param bool $skip_delete Skip deleting the existing index on recreate. Used when site url change detected in case it's a staging site that
	 * has been copied from live.
	 *
	 * @return true|WP_Error
	 */
	public function start_index_recreate( $skip_delete = false ) {
		require_once plugin_dir_path( __FILE__ ) . 'class-sc1-index-manager.php';
		$ix_mgr = new SC1_Index_Manager();
		// 1. Delete index from SC1 (Only if skipDelete is false)
		if ( false === $skip_delete ) {

			$old_ix_uuid = $this->get_sc1_ix_uuid();
			if ( is_wp_error( $old_ix_uuid ) ) {
				return $old_ix_uuid;
			}
			$deleted = $ix_mgr->delete_sc1_index( $old_ix_uuid );
			if ( is_wp_error( $deleted ) ) {
				return $deleted;
			}
		}
		// 2. Create new index
		$site_url         = get_site_url();
		$page_name        = get_the_title( $this->post_id );
		$index_name       = 'SS1 Search Page - ' . $site_url . ' - ' . $page_name;
		$current_username = wp_get_current_user()->user_login;
		$current_userid   = get_current_user_id();
		$index_desc       =
			'Created By' . $current_username . ' (' . $current_userid . ')\nPost Id: ' . $this->post_id;
		$created_uuid     = $ix_mgr->create_sc1_index( $index_name, $index_desc );
		if ( false === $created_uuid ) {
			return new WP_Error( 'failed_create_index', 'Failed to create new index' );
		}
		// 3. Associate self with new index
		$associated = $this->associate_self_with_index_uuid( $created_uuid );
		if ( false === $associated ) {
			return new WP_Error( 'db_error', 'Failed to associate search page with index - Database error' );
		}
		// 4. If the index uses Synonyms, upload them now if possible.
		$opts = $this->get_display_opts();
		if ( $opts->synonyms ) {
			if ( array_key_exists( 'synonyms_path', $opts ) ) {
				$filepath = WP_CONTENT_DIR . '/uploads/site-search-one/' . $opts->synonyms_path;
				if ( file_exists( $filepath ) ) {
					// Synonyms file exists.
					//phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
					$synonyms_base64 = file_get_contents( $filepath );
					//phpcs:enable
					if ( false !== $synonyms_base64 ) {
						$uploaded = $this->upload_synonyms_base64_to_current_index( $synonyms_base64 );
						if ( is_wp_error( $uploaded ) ) {
							Site_Search_One_Debugging::log( 'SS1-ERROR Synonyms file failed to upload to SC1!' );
						}
					} else {
						Site_Search_One_Debugging::log( 'SS1-ERROR Synonyms file could not be read!' );
					}
				} else {
					// File does not exist.
					Site_Search_One_Debugging::log( 'SS1-ERROR Synonyms file not found at ' . $filepath );
				}
			}
		}
		// 5. Schedule full scan
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-queue-manager.php';
		$queue_manager = new Site_Search_One_Queue_Manager();
		$queue_manager->enqueue_page_sync( $this->post_id );
		$this->site_sc1_ix_uuid = $created_uuid;
		return $created_uuid;
	}

	/**
	 * Get cached xfirstword response for this search page, if one is cached.
	 *
	 * @return string|null
	 */
	public function get_cached_xfirstword_responses() {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_sc1_indexes';
		$sql        =
			'SELECT cached_xfirstword_response FROM ' . $table_name
			. ' WHERE post_id=%d AND wp_site_url=%s AND cached_xfirstword_time > DATE_SUB(NOW(), INTERVAL 7 DAY) LIMIT 1';
		$query      = $wpdb->prepare( $sql, $this->post_id, base64_encode( get_site_url() ) );
		return $wpdb->get_var( $query );
		//phpcs:enable
	}


	/**
	 * Upload Synonyms to SC1.
	 *
	 * @param string $synonyms_base64 Base64 encoded user defined synonyms.
	 *
	 * @return true|WP_Error
	 */
	private function upload_synonyms_base64_to_current_index( $synonyms_base64 ) {
		set_time_limit( 0 );
		$url     = get_transient( 'ss1-endpoint-url' ) . '/Indexes';
		$api_key = SC1_Index_Manager::get_sc1_api_key();
		$body    = array(
			'APIKey'              => $api_key,
			'Action'              => 'SetUserDefinedSynonyms',
			'IndexUUID'           => $this->get_sc1_ix_uuid(),
			'UserDefinedSynonyms' => $synonyms_base64,
		);
		//phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout
		$args    = array(
			'body'        => wp_json_encode( $body, JSON_UNESCAPED_UNICODE ),
			'timeout'     => '30',
			'blocking'    => true,
			'data_format' => 'body',
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'httpversion' => '1.1',
		);
		//phpcs:enable
		$request = wp_remote_post( $url, $args );
		if ( is_wp_error( $request ) ) {
			return $request;
		}
		return true;
	}

	/**
	 * Assosciate this search page/the current site url with a given index
	 *
	 * @param string $index_uuid SC1 Index UUID.
	 *
	 * @return bool true on success else false.
	 */
	private function associate_self_with_index_uuid( $index_uuid ) {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_sc1_indexes';
		// region 1. Check if we're already associated with an index uuid.
		$query = $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . $table_name . ' WHERE post_id=%d AND wp_site_url=%s',
			$this->post_id,
			base64_encode( get_site_url() )
		);
		$count = intval( $wpdb->get_var( $query ) );
		// endregion
		// If we are, update the record, else, insert a new one.
		if ( $count > 0 ) {
			// An index is already associated with this search page/site url combo.
			// Update existing association.
			$data          = array(
				'sc1_ix_uuid' => $index_uuid,
				'sc1_ix_id'   => null,
			);
			$where         = array(
				'post_id'     => $this->post_id,
				'wp_site_url' => base64_encode( get_site_url() ),
			);
			$data_format   = array( '%s', '%d' );
			$where_format  = array( '%d', '%s' );
			$rows_affected = $wpdb->update( $table_name, $data, $where, $data_format, $where_format );
			return false !== $rows_affected;
		} else {
			// No index associated with this search page/site url combo
			// Create new association.
			$data        = array(
				'sc1_ix_uuid' => $index_uuid,
				'sc1_ix_id'   => null,
				'wp_site_url' => base64_encode( get_site_url() ),
				'post_id'     => $this->post_id,
			);
			$data_format = array(
				'%s',
				'%d',
				'%s',
				'%d',
			);
			$res         = $wpdb->insert( $table_name, $data, $data_format );
			return false !== $res;
		}
		//phpcs:enable

	}

	/**
	 * Get description html as should be rendered in the search page list.
	 *
	 * @return string
	 */
	public function get_description_html() {
		$desc_obj        = new StdClass();
		$post_parameters = __( "Posts - Can't Describe", 'site-search-one' ); // Should not be shown.
		// region 1. Detect indexing Parameters for posts.
		if ( $this->ix_posts ) {
			if ( count( $this->cat_ids ) > 0 || count( $this->tax_term_ids ) > 0 ) {
				if ( count( $this->cat_ids ) > 0 ) {
					$category_names = array();
					foreach ( $this->cat_ids as $cat_id ) {
						$category_names[] = get_cat_name( $cat_id );
					}
					$post_parameters = array(
						__( 'Index posts belonging to categories:', 'site-search-one' ) => $category_names,
					);
				}
				if ( count( $this->tax_term_ids ) > 0 ) {
					$term_names = array();
					foreach ( $this->tax_term_ids as $term_id ) {
						$term         = get_term( $term_id );
						$term_names[] = $term->name;
					}
					$post_parameters = array(
						__( 'Index posts belonging to terms:', 'site-search-one' ) => $term_names,
					);
				}
			} else {
				$post_parameters = __( 'Index all posts', 'site-search-one' );
			}
		} else {
			$post_parameters = __( 'Do not index posts', 'site-search-one' );
		}
		// endregion
		// region 2. Detect indexing parameters for pages.
		$special_case_pages = $this->get_special_pages();
		if ( count( $special_case_pages ) > 0 ) {
			$page_names = array();
			foreach ( $special_case_pages as $page_id ) {
				array_push( $page_names, get_the_title( $page_id ) );
			}
		} else {
			// Pleasing the IDE.
			$page_names = array();
		}
		if ( $this->ix_pages ) {
			if ( count( $special_case_pages ) > 0 ) {
				// Index all pages except special case pages.
				$page_parameters = array(
					__( 'Index pages, excluding:', 'site-search-one' ) => $page_names,
				);
			} else {
				// Could be Index all pages OR Index pages belonging to categories.
				if ( null !== $this->page_cat_ids ) {
					$category_names = array();
					foreach ( $this->page_cat_ids as $cat_id ) {
						array_push( $category_names, get_cat_name( $cat_id ) );
					}
					$page_parameters = array(
						__( 'Index pages belonging to categories:', 'site-search-one' ) => $category_names,
					);
				} else {
					$page_parameters = __( 'Index all pages', 'site-search-one' );
				}
			}
		} else {
			if ( count( $special_case_pages ) > 0 ) {
				// Do not index any pages except special case pages.
				$page_parameters = array(
					__( 'Index selected pages:', 'site-search-one' ) => $page_names,
				);
			} else {
				// Do not index pages.
				$page_parameters = __( 'Do not index pages', 'site-search-one' );
			}
		}
		// endregion.
		$parameters = array( $post_parameters, $page_parameters );
		// region Add media parameters if premium plugin enabled.
		$media_params = __( "Can't Describe Index Configuration", 'site-search-one' );
		if ( self::try_require_premium_functions() ) {
			// Premium is installed. Show Media Parameters.
			if ( $this->is_media_filtered_on_terms() ) {
				// Media may either be filtered on terms or excluded completely.
				if ( count( $this->media_term_ids ) === 0 ) {
					$media_params = __( 'Do not index media', 'site-search-one' );
				} else {
					// Filters on one or more terms.
					if ( $this->get_index_attached_media_only() ) {
						$desc = __( 'Index attached media belonging to', 'site-search-one' );
					} else {
						$desc = __( 'Index all media belonging to', 'site-search-one' );
					}
					$term_str_array = array();
					foreach ( $this->media_term_ids as $media_term_id ) {
						$term = get_term( intval( $media_term_id ) );
						if ( is_wp_error( $term ) || null === $term ) {
							Site_Search_One_Debugging::log( 'SS1-ERROR Error displaying Term ID' . $media_term_id );
							if ( is_wp_error( $term ) ) {
								Site_Search_One_Debugging::log( $term );
							}
						} else {
							$term_name = $term->name;
							$taxonomy  = get_taxonomy( $term->taxonomy );
							if ( false !== $taxonomy ) {
								$taxonomy_name    = $taxonomy->label;
								$term_str_array[] = $taxonomy_name . ': ' . $term_name;
							} else {
								$term_str_array[] = $term->taxonomy . ': ' . $term_name;
							}
						}
					}
					$media_params = array( $desc => $term_str_array );
				}
			} else {
				// Not filtered on terms..
				if ( $this->get_index_attached_media_only() ) {
					$media_params = __( 'Index attached media only', 'site-search-one' );
				} else {
					$media_params = __( 'Index all media', 'site-search-one' );
				}
			}
		}
		$parameters[] = $media_params;
		// endregion.

		return $this->parameters_to_html_list( $parameters );
	}

	/**
	 * Get the post id associated with this search page.
	 *
	 * @return int
	 */
	public function get_post_id() {
		return $this->post_id;
	}

	/**
	 * Returns true for all posts
	 * Returns false for selected posts/no posts
	 *
	 * @return bool
	 */
	public function does_index_all_posts() {
		if ( count( $this->cat_ids ) === 0 && count( $this->tax_term_ids ) === 0 && true === $this->ix_posts ) {
			return true;
		}
		return false;
	}

	/**
	 * Determine if this search page indexes all posts of type page.
	 *
	 * @return bool
	 */
	public function does_index_all_pages() {
		if ( false === $this->ix_pages ) {
			return false;
		} else {
			if ( count( $this->get_special_pages() ) === 0 ) {
				if ( null !== $this->page_cat_ids ) {
					return false;
				} else {
					return true;
				}
			} else {
				return false;
			}
		}
	}

	/**
	 * Determine if this search page filters media on terms
	 *
	 * @return bool
	 */
	public function is_media_filtered_on_terms() {
		return null !== $this->media_term_ids;
	}

	/**
	 * When true, special case pages are to be excluded from index, rather than included.
	 *
	 * @return bool
	 */
	public function are_special_case_pages_exclude() {
		if ( true === $this->ix_pages ) {
			return true;
		}
		return false;
	}


	/**
	 * Determine if the given category is an indexed category.
	 *
	 * @param int $category_id The category id.
	 * @return bool
	 */
	public function is_indexed_category( $category_id ) {
		if ( count( $this->cat_ids ) === 0 && count( $this->tax_term_ids ) === 0 && $this->ix_posts ) {
			return true;
		}
		foreach ( $this->cat_ids as $indexed_cat_id ) {
			if ( intval( $indexed_cat_id ) === intval( $category_id ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Determine if the given category is a page category that is indexed.
	 *
	 * @param int $category_id Category id.
	 * @return bool
	 */
	public function is_indexed_page_category( $category_id ) {
		if ( null === $this->page_cat_ids ) {
			return true;
		}
		foreach ( $this->page_cat_ids as $page_cat_id ) {
			if ( intval( $page_cat_id ) === intval( $category_id ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Determine if the given term is an indexed media term id.
	 *
	 * @param int $category_id the media term id.
	 * @return bool
	 */
	public function is_indexed_media_term_id( $category_id ) {
		if ( null === $this->media_term_ids ) {
			return true; // Indexes all terms.
		}
		foreach ( $this->media_term_ids as $indexed_media_cat_id ) {
			if ( intval( $indexed_media_cat_id ) === intval( $category_id ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Determine if the given term is an indexed taxonomy term.
	 *
	 * @param int $term_id The term id.
	 * @return bool
	 */
	public function is_indexed_tax_term( $term_id ) {
		if ( count( $this->cat_ids ) === 0 && count( $this->tax_term_ids ) === 0 && $this->ix_posts ) {
			return true;
		}
		foreach ( $this->tax_term_ids as $indexed_term_id ) {
			if ( intval( $indexed_term_id ) === intval( $term_id ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Creates a <ul> list of items
	 *
	 * @param array  $params array of strings that form the <ul>.
	 * @param string $append_to string to append to.
	 *
	 * @return string
	 */
	private function parameters_to_html_list( $params, $append_to = '' ) {
		$append_to .= "<ul class='ss1-list'>";
		$type       = $this->check_type( $params );
		switch ( $type ) {
			case 'array_a':
				$append_to .= $this->array_a_to_html_list( $params );
				break;
			case 'array_s':
				$append_to .= $this->array_s_to_html_list( $params );
				break;
			default:
				$append_to .= '<li>';
				$append_to .= $params;
				$append_to .= '</li>';
		}
		$append_to .= '</ul>';
		return $append_to;
	}

	/**
	 * Assoc array to html list
	 *
	 * @param array $assoc Assoc array.
	 *
	 * @return string
	 */
	private function array_a_to_html_list( $assoc ) {
		$li = '';
		foreach ( $assoc as $key => $val ) {
			$li .= '<li>';
			$li .= $key;
			$li .= "<ul class='ss1-list'>";
			switch ( $this->check_type( $val ) ) {
				case 'array_a':
					$li .= $this->array_a_to_html_list( $val );
					break;
				case 'array_s':
					$li .= $this->array_s_to_html_list( $val );
					break;
			}
			$li .= '</ul>';
			$li .= '</li>';
		}
		return $li;
	}

	/**
	 * Array of string to html list.
	 *
	 * @param array $seq the array.
	 *
	 * @return string
	 */
	private function array_s_to_html_list( $seq ) {
		$li = '';
		foreach ( $seq as $item ) {
			switch ( $this->check_type( $item ) ) {
				case 'array_a':
					$li .= $this->array_a_to_html_list( $item );
					break;
				case 'array_s':
					$li .= $this->array_s_to_html_list( $item );
					break;
				default:
					$li .= '<li>';
					$li .= $item;
					$li .= '</li>';
					break;
			}
		}
		return $li;
	}

	/**
	 * Check the type of an item, additionally arrays may return as array_a or array_s depending on if assoc array.
	 *
	 * @param mixed $param item.
	 *
	 * @return string|void
	 */
	private function check_type( $param ) {
		$type = gettype( $param );
		if ( 'array' === $type ) {
			if ( $this->is_array_assoc( $param ) ) {
				return 'array_a';
			} else {
				return 'array_s';
			}
		} else {
			return $type;
		}
	}

	/**
	 * Determine if an array os assoc.
	 *
	 * @param array $arr1 the array.
	 *
	 * @return bool
	 */
	private function is_array_assoc( $arr1 ) {
		if ( array_keys( $arr1 ) !== range( 0, count( $arr1 ) - 1 ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Determine if a facet should be displayed based on the page display options.
	 *
	 * @param string $facet_name the facet name.
	 *
	 * @return bool
	 */
	public function should_display_facet( $facet_name ) {
		$facet_options = $this->display_opts->facets;
		$facet_mode    = $facet_options[0];
		switch ( $facet_mode ) {
			case 'all':
				return true;
			case 'include':
				$i                   = 1;
				$count_facet_options = count( $facet_options );
				while ( $i < $count_facet_options ) {
					if ( $facet_name === $facet_options[ $i ] ) {
						return true;
					}
					++$i;
				}
				return false;
			case 'exclude':
				$ii                  = 1;
				$count_facet_options = count( $facet_options );
				while ( $ii < $count_facet_options ) {
					if ( $facet_name === $facet_options[ $ii ] ) {
						return false;
					}
					++$ii;
				}
				return true;
		}
		return true;
	}

	/**
	 * Retrieve Search Page with ID
	 *
	 * @param int $page_id the page id.
	 * @return false|Site_Search_One_Search_Page
	 */
	public static function get_search_page( $page_id ) {
		$search_pages = self::get_all_search_pages();
		if ( is_wp_error( $search_pages ) ) {
			return false;
		}
		foreach ( $search_pages as $search_page ) {
			if ( strval( $search_page->post_id ) === strval( $page_id ) ) {
				return $search_page;
			}
		}
		Site_Search_One_Debugging::log( 'SS1-ERROR Failed to find Search Page' . $page_id );
		return false;
	}

	/**
	 * Write page display specific options to db
	 *
	 * @param mixed $options display options.
	 * @return true|WP_Error
	 */
	public function set_page_display_options( $options ) {
		$additional_search_pages = $options['also_shown'];
		$this->set_also_shown_searchpages( $additional_search_pages );
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_search_pages';
		$data       = array(
			'display_opts' => wp_json_encode( $options ),
		);
		$where      = array(
			'post_id' => $this->post_id,
		);
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		$updates = $wpdb->update( $table_name, $data, $where );
		if ( false === $updates ) {
			return new WP_Error(
				'fail_set_ss1_display_options',
				'Database Error setting SS1 Page Display Options'
			);
		}
		if ( 0 === $updates ) {
			return new WP_Error(
				'no_such_page',
				'Could not set page display options - No such page'
			);
		}
		// Invalidate cached results.
		$this->invalidate_cached_results();
		return true;
		//phpcs:enable
	}

	/**
	 * Invalidates the xfirstword response cache. Will cause SS1 Plugin to immediately request new response from SC1
	 * upon next cron run.
	 */
	public function invalidate_cached_results() {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_sc1_indexes';
		$sql        = 'UPDATE ' . $table_name . ' SET cached_xfirstword_response = NULL, cached_xfirstword_time = NULL WHERE post_id=%d ';
		$statement  = $wpdb->prepare( $sql, $this->post_id );
		$wpdb->query( $statement );
		//phpcs:enable
	}

	/**
	 * Get page display options. Where options have not been set, returns defaults
	 */
	public function get_page_display_options() {
		return $this->display_opts;
	}

	/**
	 * Determine if indexes attached media only.
	 *
	 * @return bool TRUE if should indexed attached media only
	 */
	public function get_index_attached_media_only() {
		return $this->attached_media_only;
	}

	/**
	 * Retrieve all created SS1 Search Pages
	 *
	 * @return Site_Search_One_Search_Page[]
	 */
	public static function get_all_search_pages() {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		global $wpdb;
		$table_name   = $wpdb->prefix . 'ss1_search_pages';
		$results      = $wpdb->get_results( 'SELECT * FROM ' . $table_name );
		$search_pages = array();
		foreach ( $results as $result ) {
			$id                  = $result->id;
			$ix_pages            = ( '1' === $result->ix_pages );
			$ix_posts            = ( '1' === $result->ix_posts );
			$attached_media_only = ( '1' === $result->attached_media_only );
			$cat_ids             = array_filter(
				explode( ',', $result->cat_ids ),
				'strlen'
			); // In the case of result->cat_ids being an empty string, this returns an empty array.
			$tax_term_ids        = array_filter(
				explode( ',', $result->tax_term_ids ),
				'strlen'
			);
			$also_show           = array_filter(
				explode( ',', $result->also_show ),
				'strlen'
			);
			$post_types          = null;
			if ( null !== $result->post_types ) {
				$post_types = array_filter(
					explode( ',', $result->post_types ),
					'strlen'
				);
			}
			$post_id      = $result->post_id;
			$sc1_ix_uuid  = $result->sc1_ix_uuid;
			$display_opts = $result->display_opts;
			$sc1_ix_id    = $result->sc1_ix_id;

			$page_cat_ids = null;
			if ( null !== $result->page_cat_ids ) {
				$page_cat_ids = array_filter(
					explode( ',', $result->page_cat_ids ),
					'strlen'
				);
			}
			$media_term_ids = null;
			if ( null !== $result->media_term_ids ) {
				$media_term_ids = array_filter(
					explode( ',', $result->media_term_ids ),
					'strlen'
				);
			}
			$media_mime_types = null;
			if ( null !== $result->media_mime_types ) {
				$media_mime_types = array_filter(
					explode( ',', $result->media_mime_types ),
					'strlen'
				);
			}

			if ( null === $display_opts ) {
				$display_opts          = self::get_default_display_opts();
				$display_opts->ix_name = get_the_title( $post_id );
			} else {
				$display_opts = json_decode( $display_opts );
			}

			$search_page = new Site_Search_One_Search_Page(
				$id,
				$ix_pages,
				$ix_posts,
				$cat_ids,
				$post_id,
				$sc1_ix_uuid,
				$display_opts,
				$tax_term_ids,
				$also_show,
				$post_types,
				$sc1_ix_id,
				$page_cat_ids,
				$media_term_ids,
				$attached_media_only,
				$media_mime_types
			);
			array_push( $search_pages, $search_page );
		}
		return $search_pages;
		//phpcs:enable
	}


	/**
	 * Retrieve search page that owns index_uuid
	 *
	 * @param string $index_uuid SC1 Index UUID.
	 *
	 * @return false|Site_Search_One_Search_Page
	 */
	public static function search_page_with_index( $index_uuid ) {
		$search_pages = self::get_all_search_pages();
		if ( is_wp_error( $search_pages ) ) {
			return false;
		}
		foreach ( $search_pages as $search_page ) {
			if ( $search_page->get_sc1_ix_uuid() === $index_uuid ) {
				return $search_page;
			}
		}
		return false;
	}

	/**
	 * Get the default display options when a new search page is created.
	 *
	 * @return StdClass
	 */
	public static function get_default_display_opts() {
		$display_opts = new StdClass();
		// First element any of "all", "include", "exclude"
		// For selected/exclude, further elements are fields to include/exclude.
		$display_opts->facets         = array( 'all' );
		$display_opts->link_opens     = 'original_page'; // "original_page or hit_viewer"
		$display_opts->link_behaviour = 'new_window'; // Either "new_window" or "current_window"
		// First element any of "all", "include", "exclude"
		// For selected/exclude, further elements are fields to include/exclude.
		$display_opts->fields            = array( 'all' );
		$display_opts->search_all_option = 'hidden';

		return $display_opts;
	}

	/**
	 * Deletes the Search Page. Deletes it locally, and also deletes the Index on SearchCloudOne.
	 *
	 * @return true|WP_Error
	 */
	public function delete() {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		set_time_limit( 0 );
		Site_Search_One_Debugging::log(
			'SS1-INFO Search Page '
			. get_the_title( $this->post_id )
			. '[' . $this->post_id . '] is being deleted...'
		);
		$index_uuid = $this->get_sc1_ix_uuid();
		if ( ! is_wp_error( $index_uuid ) ) {
			// region 1. Cancel any queued tasks for this search page.
			$queue_table = $wpdb->prefix . 'ss1_sync_queue';
			$where       = array(
				'index_uuid' => $index_uuid,
			);
			$deleted     = $wpdb->delete( $queue_table, $where );
			if ( false === $deleted ) {
				Site_Search_One_Debugging::log( 'SS1-ERROR Error deleting search page - Could not delete records from db' );

				return new WP_Error( 'failed_remove_from_queue', 'Error deleting search page - Could not delete records from database' );
			}
			// endregion
			// region 2. Delete index from SC1.
			require_once plugin_dir_path( __FILE__ ) . 'class-sc1-index-manager.php';
			$ix_mgr  = new SC1_Index_Manager();
			$deleted = $ix_mgr->delete_sc1_index( $index_uuid );
			if ( is_wp_error( $deleted ) ) {
				Site_Search_One_Debugging::log( 'SS1-ERROR Failed to delete index from SC1:' );
				Site_Search_One_Debugging::log( $deleted );

				return $deleted;
			}
			// endregion.
		}
		// region 3. Remove Searchpage post from WordPress.
		wp_delete_post( $this->post_id, true );
		// endregion.
		try {
			// region 4. Finally remove searchpage from DB and also upload records.
			$sp_table = $wpdb->prefix . 'ss1_search_pages';
			$where    = array(
				'post_id' => $this->post_id,
			);
			$wpdb->delete( $sp_table, $where );
			$uploads_table = $wpdb->prefix . 'ss1_uploaded_posts';
			$where         = array(
				'index_uuid' => $index_uuid,
			);
			$wpdb->delete( $uploads_table, $where );
			// endregion.
		} catch ( Exception $ex ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Error deleting from database during deletion of Search Page:' );
			Site_Search_One_Debugging::log( $ex );
		} finally {
			Site_Search_One_Debugging::log( 'SS1-INFO Deletion success' );
			return true;
		}
		//phpcs:enable
	}

	/**
	 * Retrieve the menu that is associated with this search page. If one doesn't exist, one is created.
	 */
	public function get_search_page_indexing_menu() {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_search_pages';
		$query      = $wpdb->prepare(
			'SELECT menu_id FROM ' . $table_name . ' WHERE post_id=%d',
			$this->post_id
		);
		$menu_id    = $wpdb->get_var( $query );
		if ( null === $menu_id ) {
			$menu_id = $this->create_search_page_indexing_menu();
		}
		return $menu_id;
		//phpcs:enable
	}

	/**
	 * Get name of search page as should be displayed on indexing menu.
	 *
	 * @return string
	 */
	private function generate_search_page_indexing_menu_name() {
		$search_page_title = get_the_title( $this->post_id );
		return 'Search Page: ' . $search_page_title;
	}

	/**
	 * Create menu
	 *
	 * @return int|WP_Error
	 */
	private function create_search_page_indexing_menu() {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$menu_name = $this->generate_search_page_indexing_menu_name();
		$menu_id   = wp_create_nav_menu( $menu_name );
		if ( is_wp_error( $menu_id ) ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Failed to create nav menu:' );
			Site_Search_One_Debugging::log( $menu_id );
			return $menu_id;
		} else {
			Site_Search_One_Debugging::log( 'SS1-INFO Created menu for search page w/ post_id ' . $this->post_id . ', menu id ' . $menu_id );
		}
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_search_pages';
		$result     = $wpdb->update(
			$table_name,
			array(
				'menu_id' => $menu_id,
			),
			array(
				'post_id' => $this->post_id,
			),
			'%d',
			'%d'
		);
		if ( false === $result ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR "Failed to associate menu id with search page"' );
			return new WP_Error( 'db_error', 'Failed to associate menu id with search page' );
		}
		return $menu_id;
		//phpcs:enable
	}

	/**
	 * Determine if the search page filters by post type when indexing.
	 *
	 * @return bool
	 */
	public function does_filter_by_post_types() {
		if ( null === $this->post_types ) {
			return false;
		}
		return true;
	}

	/**
	 * Attempt to require premium plugin functions. May fail if premium plugin not installed.
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
	 * Determine if a given post meets the post type requirements to be uploaded to index associated with search page.
	 *
	 * @param int $post_id the post id.
	 *
	 * @return bool
	 */
	public function does_post_meet_post_type_requirements( $post_id ) {
		$type = get_post_type( $post_id );
		if ( 'attachment' === $type ) {
			/*
			 * Attachments are a special case with a few different possibilities
			 * An attachment can be attached to a post OR uploaded directly to media directory (parent post 0)
			 * Check that Premium plugin is enabled.
			 * Must check get_index_attached_media_only ... If TRUE, check parent id is indexed
			 * Then, check valid attachment taxonomy
			 */
			// region Check premium plugin enabled.
			if ( ! self::try_require_premium_functions() ) {
				return false; // Premium is not installed. We don't support attachments.
			}
			// endregion
			// region Check the file is not too big.
			$size = filesize( get_attached_file( $post_id ) );
			if ( $size > 20000000 ) {
				Site_Search_One_Debugging::log( 'SS1-WARNING ' . $post_id . ' skipped - Too big (>20mb)' );
				return false;
			} // Bigger than 20mb
			// endregion
			// region Check get_index_attached_media_only.
			if ( $this->get_index_attached_media_only() ) {
				// This index will only index media if it is attached to a post.
				$parent = get_post_parent( $post_id );
				if ( null === $parent ) {
					return false;
				} // Unattached media item
				// Attached media item... does the parent meet requirements to be indexed?
				if ( ! $this->does_post_meet_upload_criteria( $parent->ID ) ) {
					return false; // Attached, but parent post not indexed.
				}
			}
			// endregion
			// region Check is indexed mime type.
			$attachment_mime_type = get_post_mime_type( $post_id );
			if ( ! $this->is_indexed_mime_type( $attachment_mime_type ) ) {
				return false; // Not an indexed mime type.
			}
			// endregion
			// region Check valid attachment taxonomy.
			if (
				null === $this->media_term_ids ||
				false === $this->media_term_ids
			) {
				return true; // indexes all media items.
			}
			if ( count( $this->media_term_ids ) === 0 ) {
				return false; // does not index any media items.
			}
			$terms          = $this->get_all_terms( $post_id );
			$has_valid_term = false;
			foreach ( $terms as $term ) {
				if ( $this->is_term_or_parents_in( $term, $this->media_term_ids ) ) {
					$has_valid_term = true;
					break;
				}
			}
			if ( $has_valid_term ) {
				return true;
			}
			return false;
			// endregion.
		}
		if ( null === $this->post_types ) {
			// This search page indexes all post types.
			return true;
		} else {

			if ( $this->is_indexed_post_type( $type ) ) {
				return true;
			}
			return false;
		}
	}

	/**
	 * Get all terms associated with post.
	 *
	 * @param int $post_id the post id.
	 *
	 * @return array
	 */
	private function get_all_terms( $post_id ) {
		$terms      = array();
		$taxonomies = get_post_taxonomies( $post_id );
		foreach ( $taxonomies as $taxonomy_name ) {
			$terms_in_taxonomy = get_the_terms( $post_id, $taxonomy_name );
			if ( $terms_in_taxonomy && ! is_wp_error( $terms_in_taxonomy ) ) {
				foreach ( $terms_in_taxonomy as $term_in_taxonomy ) {
					$terms[] = $term_in_taxonomy;
				}
			}
		}
		return $terms;
	}

	/**
	 * Recursive call. Checks if term or parent terms are in list of term_ids
	 *
	 * @param WP_Term $term the term.
	 * @param int[]   $term_ids the term ids.
	 *
	 * @return bool|WP_Error
	 */
	private function is_term_or_parents_in( $term, $term_ids ) {
		foreach ( $term_ids as $indexed_term_id ) {
			if ( intval( $term->term_id ) === intval( $indexed_term_id ) ) {
				return true;
			}
		}
		// Not directly an indexed term.. Are any of our parents indexed?
		if ( 0 !== $term->parent ) {
			$parent_term = get_term( $term->parent );
			if ( is_wp_error( $parent_term ) ) {
				return $parent_term;
			}
			return $this->is_term_or_parents_in( $parent_term, $term_ids );
		}
		return false;
	}

	/**
	 * Determine if post type is indexed by search page index.
	 *
	 * @param string $type_name the post type.
	 *
	 * @return bool
	 */
	public function is_indexed_post_type( $type_name ) {
		if ( null === $this->post_types ) {
			return true; // This search page indexes all post types.
		}
		foreach ( $this->post_types as $indexed_post_type ) {
			if ( $indexed_post_type === $type_name ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks that post/page meets upload criteria for Search Page
	 * DOES NOT Check that the post is actually published - Check beforehand!!
	 *
	 * @param int $post_id the post id.
	 * @return bool|WP_Error true if meets criteria, false if not, WP_Error if failed to check for any reason
	 */
	public function does_post_meet_upload_criteria( $post_id ) {
		// Check that the post has not been marked as noindex.
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-post.php';
		$ss1_post = new Site_Search_One_Post( $post_id );
		if ( $ss1_post->get_noindex() === true ) {
			return false;
		}
		// region 1. Check if $post_id is page or post, and this search page indexes that type.
		$post_type = get_post_type( $post_id );
		// Filter out nav menu items, ss1 custom post types from indexing.
		switch ( $post_type ) {
			case 'nav_menu_item':
			case 'ss1_serp':
			case 'ss1_widget':
			case 'ss1_hitviewer':
			case 'ss1_pdfviewer':
				return false;
		}

		$is_page = 'page' === $post_type;
		if ( true === $is_page ) {
			return $this->get_is_indexed_page( $post_id );
		} elseif ( false === $this->ix_posts && 'attachment' !== $post_type ) {
			// Treat it as a blog post, and blog posts aren't indexed.
			return false;
		}
		// endregion
		// Check that the type of post is indexed.
		if ( ! $this->does_post_meet_post_type_requirements( $post_id ) ) {
			return false;
		}
		if ( 'attachment' === $post_type ) {
			return true; // No further checks for attachments.
		}
		// region 2. If $post_id is post, check that it belongs to at least one of the categories or taxonomies.
		if ( $this->does_post_meet_category_requirements( $post_id ) ) {
			return true;
		}
		if ( $this->does_post_meet_taxonomy_requirements( $post_id ) ) {
			return true;
		}
		// endregion.
		return false;
	}

	/**
	 * Clear all special-case pages from the ix_pages table for this search page
	 *
	 * @return false|int number of rows returned or false on error
	 */
	public function clear_special_pages() {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_ix_pages';

		return $wpdb->delete(
			$table_name,
			array(
				'search_page_id' => $this->id,
			),
			array(
				'%d', // format of value being targeted for deletion.
			)
		);
		//phpcs:enable
	}

	/**
	 * Set the special-case pages. If the search page is set to index all pages,
	 * the added page will be excluded, if the search page is set to not index pages, this page will be indexed as
	 * an exception to the rule
	 *
	 * @param array $post_ids  post ids.
	 *
	 * @return bool
	 */
	public function set_special_pages( $post_ids ) {
		$cleared = $this->clear_special_pages();
		if ( false === $cleared ) {
			return false;
		}
		foreach ( $post_ids as $post_id ) {
			$added = $this->add_special_page( $post_id );
			if ( false === $added ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Add a special page. Does not check the page has already been added. Internal method, to aid set_special_pages
	 *
	 * @param int $post_id the post id.
	 *
	 * @return bool
	 */
	private function add_special_page( $post_id ) {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_ix_pages';
		$data       = array(
			'search_page_id' => $this->id,
			'post_id'        => $post_id,
		);
		$format     = array(
			'%d',
			'%d',
		);
		$result     = $wpdb->insert( $table_name, $data, $format );
		if ( false === $result ) {
			return false;
		}
		return true;
		//phpcs:enable
	}

	/**
	 * Get an array of all special-case pages
	 *
	 * @return array|bool array of post_id
	 */
	public function get_special_pages() {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_ix_pages';
		$query      = $wpdb->prepare( 'SELECT post_id FROM ' . $table_name . ' WHERE search_page_id=%d', $this->id );
		$results    = $wpdb->get_results( $query, 'ARRAY_N' );
		if ( null === $results ) {
			return false;
		}
		$post_ids = array();
		foreach ( $results as $result ) {
			$post_id = intval( $result[0] );
			array_push( $post_ids, $post_id );
		}
		return $post_ids;
		//phpcs:enable
	}

	/**
	 * Check if the given page is one that should be in this search page's index
	 * NOTE: must be a page, not a post - The method does not check and will provide false positives otherwise
	 *
	 * @param int $post_id the post id.
	 * @return bool|WP_Error true/false, or WP_Error on database error
	 */
	private function get_is_indexed_page( $post_id ) {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		global $wpdb;
		$is_searchpage = $this->is_searchpage( $post_id );
		if ( is_wp_error( $is_searchpage ) ) {
			return $is_searchpage;
		}
		if ( true === $is_searchpage ) {
			return false;
		}
		$table_name = $wpdb->prefix . 'ss1_ix_pages';
		$query      = $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . $table_name . ' WHERE search_page_id=%d AND post_id=%d',
			$this->id,
			$post_id
		);
		$count      = $wpdb->get_var( $query );
		if ( null === $count ) {
			return new WP_Error( 'db_error', 'A database error occurred' );
		} else {
			if ( $this->ix_pages ) {
				if ( null !== $this->page_cat_ids ) {
					// Instead we're using page category...
					return $this->does_page_meet_category_requirements( $post_id );
				}

				// Index all pages except excluded pages. Pages in the ix_pages table are pages to be excluded.
				if ( intval( $count ) === 0 ) {
					// The page is not excluded.
					return true;
				} else {
					return false;
				}
			} else {
				// Index no pages except included pages and pages in selected menus. Pages in the ix_pages table are pages to be included.
				if ( intval( $count ) === 1 ) {
					return true;
				} else {
					// The page is not an included page - Check if it is in an indexed menu.
					$menus = $this->get_menus_page_assigned_to( $post_id );
					foreach ( $menus as $menu ) {

						if ( $this->get_is_indexed_menu( $menu ) ) {
							return true;
						}
					}
					return false;
				}
			}
		}
		//phpcs:enable
	}

	/**
	 * Get page categories that are indexed. Pages having Categories is non-standard in WordPress
	 * However users are able to make it possible to assosciate categories with pages by  assosciating the
	 * Category taxonomy with post type.
	 *
	 * @param int $post_id the post id.
	 *
	 * @return bool
	 */
	private function does_page_meet_category_requirements( $post_id ) {
		if ( null !== $this->page_cat_ids ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-post.php';
			$ss1_post        = new Site_Search_One_Post( $post_id );
			$page_categories = $ss1_post->get_all_category_ids_including_parents();
			foreach ( $page_categories as $page_category ) {
				foreach ( $this->page_cat_ids as $indexed_page_cat ) {
					if ( intval( $page_category ) === intval( $indexed_page_cat ) ) {
						return true;
					}
				}
			}
			return false;
		}
		return true;
	}


	/**
	 * Check if post belongs to an indexed category. Also returns true if search page indexes all categories
	 *
	 * @param int $post_id the post id.
	 * @return bool
	 */
	private function does_post_meet_category_requirements( $post_id ) {
		if ( count( $this->cat_ids ) > 0 ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-post.php';
			$ss1_post                = new Site_Search_One_Post( $post_id );
			$post_categories         = $ss1_post->get_all_category_ids_including_parents();
			$in_atleast_one_category = false;
			foreach ( $post_categories as $post_category ) {
				foreach ( $this->cat_ids as $indexed_category ) {
					if ( intval( $post_category ) === intval( $indexed_category ) ) {
						$in_atleast_one_category = true;
						break;
					}
				}
			}
			return $in_atleast_one_category;
		} else {
			if ( count( $this->tax_term_ids ) === 0 ) {
				// If both the Categories and Taxonomies are empty, treat it as index all posts.
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * Check if post has a taxonomy meeting criteria for indexing
	 *
	 * @param int $post_id the post id.
	 * @return bool
	 */
	private function does_post_meet_taxonomy_requirements( $post_id ) {
		if ( count( $this->tax_term_ids ) > 0 ) {
			$taxonomy_names = get_post_taxonomies( $post_id );
			foreach ( $taxonomy_names as $taxonomy_name ) {
				if ( 'category' === $taxonomy_name ) {
					continue;
				} // Ignore the default ones
				if ( 'post_tag' === $taxonomy_name ) {
					continue;
				}
				if ( 'post_format' === $taxonomy_name ) {
					continue;
				}
				$terms = get_the_terms( $post_id, $taxonomy_name );
				if ( ! empty( $terms ) ) {

					foreach ( $terms as $term ) {
						$term_id = $term->term_id;
						foreach ( $this->tax_term_ids as $indexed_term_id ) {
							if ( intval( $indexed_term_id ) === intval( $term_id ) ) {
								return true;
							}
						}
					}
				}
			}
			return false;
		} else {
			if ( count( $this->cat_ids ) === 0 ) {
				// If both the Categories and Taxonomies are empty, treat it as index all posts.
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * Check if given post_id is a Search Page
	 *
	 * @param int $post_id the post id.
	 * @return bool|WP_Error true/false or WP_Error on db error
	 */
	private function is_searchpage( $post_id ) {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_search_pages';
		$query      = $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . $table_name . ' WHERE post_id=%d',
			intval( $post_id )
		);
		$count      = $wpdb->get_var( $query );
		if ( null === $count ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Database Error checking if post_id is searchpage:' );
			Site_Search_One_Debugging::log( $wpdb->last_error );
			return new WP_Error( 'db_error', 'Database error checking if post_id is search page' );
		}
		$count = intval( $count );
		if ( $count > 0 ) {
			return true;
		}
		return false;
		//phpcs:enable
	}

	/**
	 * Determine if search page filters pages to index by page category.
	 *
	 * @return bool
	 */
	public function are_pages_filtered_by_category() {
		return null !== $this->page_cat_ids;
	}

	/**
	 * Get the menus a given page with post_id is assigned to.
	 *
	 * @param int $post_id the post id of the page.
	 * @return array
	 */
	private function get_menus_page_assigned_to( $post_id ) {
		$assigned_menus = array();
		$nav_menus      = wp_get_nav_menus();
		foreach ( $nav_menus as $nav_menu ) {

			$nav_menu_items = wp_get_nav_menu_items( $nav_menu );
			foreach ( $nav_menu_items as $nav_menu_item ) {
				if ( intval( $nav_menu_item->object_id ) === intval( $post_id ) ) {
					$assigned_menus[] = $nav_menu;
				}
			}
		}
		return $assigned_menus;
	}

	/**
	 * Check if pages assigned to the given menu are indexed by this search page
	 *
	 * @param WP_TERM $menu Menu item.
	 * @return boolean
	 */
	private function get_is_indexed_menu( $menu ) {
		$menu_ids = $this->get_all_indexed_menu_ids();
		foreach ( $menu_ids as $menu_id ) {
			if ( intval( $menu_id ) === intval( $menu->term_id ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get ids of all menu's that this Search Page indexes pages belonging to
	 * First item of array is the search page specific menu, further items are user selected menus
	 *
	 * @return array
	 */
	private function get_all_indexed_menu_ids() {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$menu_ids            = array();
		$search_page_menu_id = $this->get_search_page_indexing_menu();
		array_push( $menu_ids, $search_page_menu_id );
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_search_pages';
		$query      = $wpdb->prepare( "SELECT menu_ids FROM $table_name WHERE id=%d", $this->id );
		$result     = $wpdb->get_var( $query );
		if ( gettype( $result ) === 'string' ) {
			$ids = array_filter(
				explode( ',', $result ),
				'strlen'
			);
			foreach ( $ids as $id ) {
				array_push( $menu_ids, $id );
			}
		}
		return $menu_ids;
		//phpcs:enable
	}

}
