<?php
/**
 * Class for SS1 functions related to posts.
 *
 * @package Site_Search_One
 */

/**
 * Class for SS1 functions related to posts.
 */
class Site_Search_One_Post {

	/**
	 * The post id.
	 *
	 * @var int
	 */
	private $post_id;

	/**
	 * Constructor.
	 *
	 * @param int $post_id the post id.
	 */
	public function __construct( $post_id ) {
		$this->post_id = $post_id;
	}

	/**
	 * Get noindex status. When true,the post should not be indexed.
	 *
	 * @return bool
	 */
	public function get_noindex() {
		if ( ! get_post_meta( $this->post_id, '_ss1_noindex', true ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Set the posts noindex status.
	 *
	 * @param bool $noindex new noindex status.
	 */
	public function set_noindex( $noindex ) {
		update_post_meta( $this->post_id, '_ss1_noindex', $noindex );
	}

	/**
	 * Retrieve array of indexes the post has been uploaded to
	 *
	 * @return string[] index_uuid's uploaded to
	 */
	public function get_indexes_uploaded_to() {
		//phpcs:disable WordPress.DB.PreparedSQL
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name  = $wpdb->prefix . 'ss1_uploaded_posts';
		$query       = $wpdb->prepare( "SELECT index_uuid FROM $table_name WHERE post_id=%s", $this->post_id );
		$results     = $wpdb->get_results(
			$query
		);
		$index_uuids = array();
		foreach ( $results as $result ) {
			$index_uuid    = $result->index_uuid;
			$index_uuids[] = $index_uuid;
		}
		return $index_uuids;
		//phpcs:enable
	}

	/**
	 * Check if Post has been uploaded to search page's corresponding index uuid
	 *
	 * @param Site_Search_One_Search_Page $search_page The search page.
	 * @return boolean
	 */
	public function is_uploaded_to_search_page( $search_page ) {
		$indexes_uploaded_to    = $this->get_indexes_uploaded_to();
		$search_page_index_uuid = $search_page->get_sc1_ix_uuid();
		foreach ( $indexes_uploaded_to as $index_uploaded_to ) {
			if ( $index_uploaded_to === $search_page_index_uuid ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get all categories this post belongs to, including parent categories
	 *
	 * @return array
	 */
	public function get_all_category_ids_including_parents() {
		$post_categories = wp_get_post_categories(
			$this->post_id,
			array( 'fields' => 'ids' )
		);
		if ( is_wp_error( $post_categories ) ) {
			return array();
		}
		$all_categories = array();
		foreach ( $post_categories as $post_category ) {
			$parent_categories = $this->get_all_parent_category_ids( $post_category );
			$all_categories    = array_merge( $all_categories, $parent_categories );
		}
		$all_categories = array_merge( $all_categories, $post_categories );
		return array_unique( $all_categories );
	}

	/**
	 * Get all parent categories for a given category id.
	 *
	 * @param int $catid The category id to check.
	 *
	 * @return array
	 */
	private function get_all_parent_category_ids( $catid ) {
		$category_ids = array();
		$catid        = $this->category_has_parent( $catid );
		while ( false !== $catid ) {
			array_push( $category_ids, $catid );
			$catid = $this->category_has_parent( $catid );
		}
		return $category_ids;
	}

	/**
	 * Determine if category has a parent.
	 *
	 * @param int $catid the category id.
	 *
	 * @return false|int
	 */
	private function category_has_parent( $catid ) {
		$category = get_category( $catid );
		if ( $category->category_parent > 0 ) {
			return $category->category_parent;
		}
		return false;
	}

	/**
	 * Check if post is uploaded to Index on SC1
	 *
	 * @param string $index_uuid SC1 Index UUID. UID of Index on SC1.
	 * @return string|false
	 * Revision number of post uploaded to index, or false if post hasn't been uploaded to index
	 */
	public function get_is_post_uploaded_to_index( $index_uuid ) {
		//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_uploaded_posts';
		$result     = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT sc1_file_uuid FROM $table_name WHERE post_id=%d AND index_uuid=%s",
				$this->post_id,
				$index_uuid
			)
		);
		if ( null === $result ) {
			return false;
		} else {
			return $result;
		}
		//phpcs:enable
	}

	/**
	 * Mark the post as uploaded to index.
	 *
	 * @param string $index_uuid SC1 Index UUID.
	 * @param string $sc1_file_uuid SC1 File UUID that was generated when uploaded completed successfully.
	 * @param int    $revision Revision of this upload.
	 * @return false|int
	 */
	public function mark_as_uploaded_to_index( $index_uuid, $sc1_file_uuid, $revision ) {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_uploaded_posts';
		// region Check if post is already marked as uploaded to this index...
		$query = $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . $table_name . ' WHERE post_id=%d AND index_uuid=%s',
			$this->post_id,
			$index_uuid
		);
		$count = $wpdb->get_var( $query );
		// endregion
		// region If the post is already uploaded, update the existing entry.
		if ( $count > 0 ) {
			$data         = array(
				'sc1_file_uuid' => $sc1_file_uuid,
			);
			$where        = array(
				'post_id'    => $this->post_id,
				'index_uuid' => $index_uuid,
			);
			$format       = '%s';
			$where_format = array(
				'%d',
				'%s',
			);
			return $wpdb->update( $table_name, $data, $where, $format, $where_format );
		}
		// endregion
		// region Else if the post is not arleady uploaded, insert a new entry.

		return $wpdb->insert(
			$table_name,
			array(
				'post_id'       => $this->post_id,
				'index_uuid'    => $index_uuid,
				'sc1_file_uuid' => $sc1_file_uuid,
				'revision'      => $revision,
			)
		);
		// endregion.
		//phpcs:enable
	}

	/**
	 * Mark post as having been removed from SC1 index.
	 *
	 * @param string $index_uuid SC1 Index UUID.
	 * @return false|int
	 */
	public function mark_as_removed_from_index( $index_uuid ) {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_uploaded_posts';
		return $wpdb->delete(
			$table_name,
			array(
				'post_id'    => $this->post_id,
				'index_uuid' => $index_uuid,
			),
			array(
				'%d',
				'%s',
			)
		);
		//phpcs:enable
	}
}
