<?php
/**
 * Creates/Manages Indexes on SearchCloudOne
 *
 * @package Site_Search_One
 */

/**
 * Creates/Manages Indexes on SearchCloudOne
 */
class SC1_Index_Manager {

	/**
	 * SC1_Index_Manager constructor.
	 * Use the Index Manager to upload/remove posts from an Index and other SC1 API Operations
	 */
	public function __construct() {
		require_once plugin_dir_path( __FILE__ ) . 'class-ss1-sc1-index-field-cache.php';
		$this->ss1_field_cache = new SS1_SC1_Index_Field_Cache();
	}

	/**
	 * Fields cached in this Index manager.
	 *
	 * @var SS1_SC1_Index_Field_Cache
	 */
	private $ss1_field_cache;

	/**
	 * Attempt to create an Index on SearchCloudOne
	 *
	 * @param string $index_name Name of index to create.
	 * @param string $index_desc Description of index.
	 * @return string|bool Index UUID on success, false on error.
	 */
	public function create_sc1_index( $index_name, $index_desc ) {
		$api_key  = self::get_sc1_api_key();
		$body     = array(
			'APIKey'      => $api_key,
			'Action'      => 'CreateIndex',
			'Name'        => $index_name,
			'Description' => $index_desc,
			'Tech'        => 1,
		);
		//phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout
		$args     = array(
			'body'        => wp_json_encode( $body, JSON_UNESCAPED_UNICODE ),
			'timeout'     => '20',
			'blocking'    => true,
			'data_format' => 'body',
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'httpversion' => '1.1',
		);
		//phpcs:enable
		$endpoint = get_transient( 'ss1-endpoint-url' ) . '/IndexManager';
		$request  = wp_remote_post( $endpoint, $args );
		if ( is_wp_error( $request ) ) {
			return false; // Failed to create index.
		}
		$response_body = wp_remote_retrieve_body( $request );

		$data = json_decode( $response_body );
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$index_uuid = $data->IndexUUID;
		// phpcs:enable
		// region Now that the index is created, assign metadata fields as appropriate.
		$result = false;
		try {
			$result = $this->set_sc1_index_enumerable_fields( $index_uuid, array( 'Categories', 'Tags', 'Mimetype' ) );
			if ( is_wp_error( $result ) ) {
				Site_Search_One_Debugging::log( 'Failed to set enumerable fields:' );
				Site_Search_One_Debugging::log( $result );

				return false;
			}
		} catch ( Exception $ex ) {
			Site_Search_One_Debugging::log( 'Fatal error configuring enumerable fields:' );
			Site_Search_One_Debugging::log( $result );
			return new WP_Error(
				'fail_create_enumerable_fields',
				'Fatal error configuring enumerable fields'
			);
		}
		// endregion.
		return $index_uuid;
	}

	/**
	 * Delete Index from SearchCloudOne. Removes all files in that index too.
	 *
	 * @param string $index_uuid SC1 Index UUID.
	 * @return true|WP_Error
	 */
	public function delete_sc1_index( $index_uuid ) {
		set_time_limit( 0 );
		$index_exists = $this->does_index_exist( $index_uuid );
		if ( is_wp_error( $index_exists ) ) {
			return $index_exists;
		}
		if ( ! $index_exists ) {
			return true;
		}
		$api_key  = self::get_sc1_api_key();
		$body     = array(
			'APIKey'    => $api_key,
			'Action'    => 'DeleteIndex',
			'IndexUUID' => $index_uuid,
		);
		//phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout
		$args     = array(
			'body'        => wp_json_encode( $body ),
			'timeout'     => '20',
			'blocking'    => true,
			'data_format' => 'body',
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'httpversion' => '1.1',
		);
		//phpcs:enable
		$endpoint = get_transient( 'ss1-endpoint-url' ) . '/IndexManager';
		$request  = wp_remote_post( $endpoint, $args );
		if ( is_wp_error( $request ) ) {
			return $request;
		}
		$response_code = wp_remote_retrieve_response_code( $request );
		if ( $response_code >= 200 && $response_code < 300 ) {
			return true;
		} else {
			return new WP_Error( 'failed_delete_sc1_index', 'Failed to delete Index on SC1' );
		}
	}

	/**
	 * Delete Indexes from SearchCloudOne. Removes all files in those indexes too.
	 *
	 * @param array $index_uuids SC1 Index UUIDs.
	 * @return true|WP_Error
	 */
	public function delete_sc1_indexes( $index_uuids ) {
		set_time_limit( 0 );
		$api_key  = self::get_sc1_api_key();
		$body     = array(
			'APIKey'     => $api_key,
			'Action'     => 'DeleteIndexes',
			'IndexUUIDs' => $index_uuids,
		);
		//phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout
		$args     = array(
			'body'        => wp_json_encode( $body ),
			'timeout'     => '20',
			'blocking'    => true,
			'data_format' => 'body',
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'httpversion' => '1.1',
		);
		//phpcs:enable
		$endpoint = get_transient( 'ss1-endpoint-url' ) . '/IndexManager';
		$request  = wp_remote_post( $endpoint, $args );
		if ( is_wp_error( $request ) ) {
			return $request;
		}
		$response_code = wp_remote_retrieve_response_code( $request );
		if ( $response_code >= 200 && $response_code < 300 ) {
			return true;
		} else {
			return new WP_Error( 'failed_delete_sc1_index', 'Failed to delete indexes from SC1' );
		}
	}

	/**
	 * Check that the given Index exists on SC1 under the currently set API Key
	 *
	 * @param string $index_uuid SC1 Index UUID.
	 * @return true|false|WP_Error
	 */
	private function does_index_exist( $index_uuid ) {
		try {
			set_time_limit( 30 );
			$api_key  = self::get_sc1_api_key();
			$endpoint = get_transient( 'ss1-endpoint-url' ) . '/IndexManager';
			$data     = array(
				'APIKey'              => $api_key,
				'Action'              => 'ListIndexes',
				'FilterToIndexes'     => array( $index_uuid ),
				'IncludeMetaSpec'     => false,
				'IncludePending'      => false,
				'IncludeRecycleCount' => false,
				'IncludeNotices'      => false,
				'IncludeIndexInfo'    => false,
			);
			//phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout
			$options  = array(
				'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'        => wp_json_encode( $data ),
				'method'      => 'POST',
				'data_format' => 'body',
				'timeout'     => '20',
				'blocking'    => true,
				'httpversion' => '1.1',
			);
			//phpcs:enable
			$request  = wp_remote_post( $endpoint, $options );
			if ( is_wp_error( $request ) ) {
				return $request;
			}
			$response_code = wp_remote_retrieve_response_code( $request );
			if ( $response_code >= 200 && $response_code < 300 ) {
				$response_body = wp_remote_retrieve_body( $request );
				$data          = json_decode( $response_body );
				// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$indexes = $data->Indexes;
				foreach ( $indexes as $index ) {
					if ( $index->IndexUUID === $index_uuid ) {
						return true;
					}
				}
				// phpcs:enable
				return false;
			} else {
				return new WP_Error( 'non_2xx_response', 'Error checking index exists - Non 2xx response' );
			}
		} catch ( Exception $ex ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Failed to check if index ' . $index_uuid . ' exists. A fatal error occurred:' );
			Site_Search_One_Debugging::log( $ex );
			return new WP_Error( 'failed_check_index_exists', 'Failed to check if index ' . $index_uuid . ' exists', $ex );
		}
	}

	/**
	 * Check if string startsWith String
	 *
	 * @param string $haystack String to check.
	 * @param string $needle What to check for.
	 *
	 * @return bool
	 */
	private function string_starts_with( $haystack, $needle ) {
		$length = strlen( $needle );
		return substr( $haystack, 0, $length ) === $needle;
	}

	/**
	 * Ensure that fields are stored as necessary in index in SC1. Also handles enumerable fields.
	 *
	 * @param string      $index_uuid SC1 Index UUID.
	 * @param SC1_Field[] $fields List of fields.
	 * @return array|WP_Error fieldCache array on success or WP_ERROR on failure.
	 */
	public function ensure_fields_stored( $index_uuid, $fields ) {

		$field_cache = $this->get_stored_fields( $index_uuid );
		if ( is_wp_error( $field_cache ) ) {
			return $field_cache;
		}
		$enumerable_field_cache = $this->get_sc1_index_enumerable_fields( $index_uuid );
		if ( is_wp_error( $enumerable_field_cache ) ) {
			return $enumerable_field_cache;
		}

		$fields_to_add = array();
		foreach ( $fields as $field ) {
			if ( $this->is_field_stored( $field, $field_cache ) ) {
				// Stored.
				if ( $field->is_field_multi_choice() ) {
					// Check it's also been made enumerable.
					if ( ! $this->is_field_enumerable( $field, $enumerable_field_cache ) ) {
						// Not yet made enumerable...
						$temp = array();
						array_push( $temp, $field );
						$made_enumerable = $this->ensure_fields_enumerable( $index_uuid, $temp );
						if ( is_wp_error( $made_enumerable ) ) {
							return $made_enumerable;
						}
					}
				}
			} else {
				// Not yet stored.
				if ( $field->is_field_visible() ) {
					// Some fields can be 'unpublished' eg. ACF .. this handles that scenario.
					Site_Search_One_Debugging::log( 'SS1-INFO Field ' . $field->get_field_name() . ' is not yet stored' );
					array_push( $fields_to_add, $field );
				}
			}
		}
		if ( count( $fields_to_add ) > 0 ) {
			// There's at least one new field to make into a stored field.
			Site_Search_One_Debugging::log( 'SS1-INFO Detected ' . count( $fields_to_add ) . ' new stored fields to add...' );
			$added = $this->add_stored_fields_to_index( $index_uuid, $fields_to_add );
			if ( is_wp_error( $added ) ) {
				return $added;
			}
		}

		return $field_cache;
	}

	/**
	 * Ensures that all configured search pages have their xfirstword page 1 response cached.
	 */
	public function ensure_xfirstword_cached() {
		// region 1. Identify which indexes have no/out of date xfirstword search cache.
		global $wpdb;
		$results    = wp_cache_get( 'site-search-one-expired-xfirstword-caches', '', false, $found );
		$table_name = $wpdb->prefix . 'ss1_sc1_indexes';
		if ( false === $results || false === $found ) {
			$table_name2 = $wpdb->prefix . 'ss1_search_pages';
			$sql         = 'SELECT sc1_ix_uuid, sc1_ix_id, post_id FROM '
						. $table_name
						. ' WHERE (cached_xfirstword_time IS NULL OR '
						. 'cached_xfirstword_time < DATE_SUB(NOW(), INTERVAL 10 MINUTE)) '
						. 'AND wp_site_url=%s AND sc1_ix_uuid IS NOT NULL AND sc1_ix_id IS NOT NULL '
						. 'AND post_id IN (SELECT post_id FROM ' . $table_name2 . ')';
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$query   = $wpdb->prepare( $sql, base64_encode( get_site_url() ) );
			$results = $wpdb->get_results( $query );
			// phpcs:enable
			wp_cache_set( 'site-search-one-expired-xfirstword-caches', $results, '', 10 );
		}
		// endregion
		// region 2. For each index, go through and fetch xfirstword response/cache it.
		// Note - Some search pages have a 'search all' option, and must also cache the
		// Search all response.
		foreach ( $results as $result ) {
			$responses = array();
			$ix_uuid   = $result->sc1_ix_uuid;
			$post_id   = $result->post_id;
			$ix_id     = $result->sc1_ix_id;

			$search_page            = Site_Search_One_Search_Page::get_search_page( $post_id );
			$display_opts           = $search_page->get_display_opts();
			$num_words_fields_array = $this->get_xfirstword_include_fields_context_arr( $display_opts );
			$num_words_of_context   = $num_words_fields_array[0];
			$fields                 = $num_words_fields_array[1];

			$indexes = array();
			array_push(
				$indexes,
				array(
					'IndexID'   => $ix_id,
					'IndexUUID' => $ix_uuid,
				)
			);
			$single_response = $this->get_xfirstword_response( $indexes, $num_words_of_context, $fields );
			if ( is_wp_error( $single_response ) ) {
				Site_Search_One_Debugging::log( 'SS1-ERROR Failed to fetch xfirstword single response' );
				Site_Search_One_Debugging::log( $single_response );
				continue;
			}

			$search_all_option   = $display_opts->search_all_option;
			$search_all_response = null;
			if ( 'hidden' !== $search_all_option ) {
				$also_shown = $search_page->get_also_shown_searchpages();
				$indexes    = array();
				array_push(
					$indexes,
					array(
						'IndexID'   => $ix_id,
						'IndexUUID' => $ix_uuid,
					)
				);
				foreach ( $also_shown as $also_shown_page ) {
					array_push(
						$indexes,
						array(
							'IndexID'   => $also_shown_page->get_sc1_ix_id(),
							'IndexUUID' => $also_shown_page->get_sc1_ix_uuid(),
						)
					);
				}
				$search_all_response = $this->get_xfirstword_response( $indexes, $num_words_of_context, $fields );
				if ( is_wp_error( $search_all_response ) ) {
					Site_Search_One_Debugging::log( 'SS1-ERROR Failed to fetch xfirstword search all response' );
					Site_Search_One_Debugging::log( $search_all_response );
					continue;
				}
			}
			$responses = array();
			array_push( $responses, $single_response );
			array_push( $responses, $search_all_response );
			$sql =
				'UPDATE '
				. $table_name
				. ' SET cached_xfirstword_response=%s, cached_xfirstword_time=NOW() '
				. ' WHERE post_id=%d AND wp_site_url=%s';
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			$query = $wpdb->prepare(
				$sql,
				wp_json_encode( $responses ),
				$post_id,
				base64_encode( get_site_url() )
			);
			$wpdb->query( $query );
			// phpcs:enable
		}
		// endregion.
	}

	/**
	 * Performs a HTTP Request to SC1 to get back xfirstword request response from SC1 for cacheing.
	 *
	 * @param array $indexes Indexes to get xfirstword response for.
	 * @param int   $num_words_of_context Number of words of context to include per result.
	 * @param array $fields Fields to request in search req.
	 *
	 * @return string|WP_Error
	 */
	private function get_xfirstword_response( $indexes, $num_words_of_context = 25, $fields = array() ) {
		$api_key                                     = $this->get_sc1_api_key();
		$data                                        = new StdClass();
		$data->{'APIKey'}                            = $api_key;
		$data->{'Indexes'}                           = $indexes;
		$data->{'Parameters'}                        = new StdClass();
		$data->{'Parameters'}->{'Query'}             = 'xfirstword';
		$data->{'Parameters'}->{'IncludeContext'}    = $num_words_of_context > 0;
		$data->{'Parameters'}->{'NumWordsOfContext'} = $num_words_of_context;
		$data->{'Parameters'}->{'GetTopFieldValues'} = new StdClass(); // Always retrieve all enumerable fields in SS1.
		$data->{'Parameters'}->{'GetTopFieldValues'}->{'MaxResults'}    = 64;
		$data->{'Parameters'}->{'GetTopFieldValues'}->{'CaseSensitive'} = false;
		$data->{'Parameters'}->{'IncludeFields'}                        = $fields;
		$data->{'Parameters'}->{'UseFieldsAsDocDisplayName'}            = array( '_SS1DisplayName' );
		$data->{'Parameters'}->{'Sort'}                                 = new StdClass();
		$data->{'Parameters'}->{'Sort'}->{'SortBy'}                     = 'SS1_Published';
		$data->{'Parameters'}->{'Sort'}->{'Ascending'}                  = false;

		$url           = get_transient( 'ss1-endpoint-url' ) . '/SearchMgr';
		$response_data = wp_remote_post(
			$url,
			array(
				'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'        => wp_json_encode( $data, JSON_UNESCAPED_UNICODE ),
				'method'      => 'POST',
				'data_format' => 'body',
			)
		);

		if ( is_wp_error( $response_data ) ) {
			return $response_data; // Failed with WP_Error object.
		}
		$response_code = wp_remote_retrieve_response_code( $response_data );
		if ( $response_code < 200 || $response_code > 299 ) {
			// Error code.
			Site_Search_One_Debugging::log( wp_remote_retrieve_body( $response_data ) );
			return new WP_Error( 'non_2xx_response', 'Error fetching xfirstword response. Non 2xx Status Code: ' . $response_code );
		}
		// Success.
		return wp_remote_retrieve_body( $response_data ); // May also return a WP_Error object.
	}

	/**
	 * Returns an array containing the number of words of context as 1st element, and array of field names as 2nd element.
	 *
	 * @param object $dsp_opts Search page Display opts.
	 * @return array first element integer num words of context, second array of fields to be retrieved.
	 */
	private function get_xfirstword_include_fields_context_arr( $dsp_opts ) {
		// region Get fields.
		$link_format = '<h3 class="link_title">%%doc_title%%</h3><p class="context">%%context%%</p>';
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_globals';
		$query      = "SELECT value FROM $table_name WHERE setting = 'global_link_format'";
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$global_link_format = $wpdb->get_var( $query );
		// phpcs:enable
		if ( $global_link_format && '' !== $global_link_format ) {
			$link_format = $global_link_format;
		}
		if (
			property_exists( $dsp_opts, 'link_format' )
			&& null !== $dsp_opts->link_format
			&& '<h3 class="link_title">%%doc_title%%</h3><p class="context">%%context%%</p>' !== $dsp_opts->link_format // The default.
			&& '' !== $dsp_opts->link_format

		) {
			// Override Global/Default.
			$link_format = $dsp_opts->link_format;
		}
		// Basically doing the equivalent of get_include_fields in ss1-searchpage-bs3 except in PHP Backend.
		$parts  = explode( '%%', $link_format );
		$i      = 1;
		$len    = count( $parts );
		$fields = array( '_link', 'Link', '_post_id' );
		while ( $i < $len ) {
			$s = $parts[ $i ];
			if ( strpos( $s, 'field:' ) === 0 ) {
				// s starts with field:     .
				$args       = explode( ':', $s );
				$field_name = explode( '(', $args[1] )[0];
				array_push( $fields, $field_name );
			}
			if ( strpos( $s, 'img:' ) === 0 ) {
				// s starts with img:   .
				$args       = explode( ':', $s );
				$field_name = $args[1];
				array_push( $fields, $field_name );
			}
			$i += 2;
		}
		// endregion
		// region Get num words of context
		// Same as get_num_words_of_context in ss1-searchbpage-bs3.php except in PHP Backend.
		$num_words_of_context = 25; // default.
		$i                    = 0;
		while ( $i < $len ) {
			$s = $parts[ $i ];
			if ( strpos( $s, 'context' ) === 0 ) {
				$args = explode( ':', $s );
				if ( count( $args ) !== 1 ) {
					$num_words_of_context = intval( $args[1] );
					if ( $num_words_of_context > 200 ) {
						$num_words_of_context = 200;
					}
					if ( $num_words_of_context < 0 ) {
						$num_words_of_context = 0;
					}
				}
			}
			++$i;
		}
		// endregion.
		return array( $num_words_of_context, $fields );
	}

	/**
	 * Check if the given field should be stored.
	 *
	 * @param SC1_Field $field The field.
	 * @param string[]  $field_cache Cache of stored fields.
	 *
	 * @return bool
	 */
	private function is_field_stored( $field, $field_cache ) {
		foreach ( $field_cache as $stored_field ) {
			if ( $stored_field === $field->get_field_name() ) {
				return true;
			}
			if ( $field->is_field_taxonomy() || $field->is_field_multi_choice() ) {
				// Would be an enumerable field with a slightly different name..
				if ( $stored_field === $field->get_dts_enum_safe_field_name() ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Performs a request to SearchCloudOne to find out which fields are already stored in this index.
	 *
	 * @param string $index_uuid SC1 Index UUID.
	 * @return string[]|WP_Error
	 */
	private function get_stored_fields( $index_uuid ) {
		set_time_limit( 0 );
		$cached_stored_fields = $this->ss1_field_cache->get_cached_stored_fields( $index_uuid );
		if ( $cached_stored_fields ) {
			return $cached_stored_fields;
		}
		$response_body = $this->get_stored_fields_response_body( $index_uuid );
		if ( is_wp_error( $response_body ) ) {
			return $response_body;
		}
		$data = json_decode( $response_body );
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( count( $data->Fields ) > 0 ) {
			$fields      = $data->Fields;
			$field_names = array();
			foreach ( $fields as $field ) {
				array_push( $field_names, $field->DisplayName );
			}
			$this->ss1_field_cache->add_stored_fields( $index_uuid, $field_names );
			return $field_names;
		} else {
			$this->ss1_field_cache->add_stored_fields( $index_uuid, array() );
			return array();
		}
		//phpcs:enable
	}

	/**
	 * Note, will attempt to fetch from cache first.
	 * Get the stored fields that SC1 knows about.
	 *
	 * @param string $index_uuid SC1 Index UUID.
	 *
	 * @return string|WP_Error
	 */
	private function get_stored_fields_response_body( $index_uuid ) {
		$cached_response = $this->get_cached_get_stored_fields_response( $index_uuid );
		if ( null !== $cached_response ) {
			return $cached_response;
		}

		$api_key  = self::get_sc1_api_key();
		$endpoint = get_transient( 'ss1-endpoint-url' ) . '/IndexManager';
		$data     = array(
			'APIKey'    => $api_key,
			'Action'    => 'GetIndexMetaSpec',
			'IndexUUID' => $index_uuid,
		);
		//phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout
		$options  = array(
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'        => wp_json_encode( $data ),
			'method'      => 'POST',
			'data_format' => 'body',
			'timeout'     => '30',
			'blocking'    => true,
			'httpversion' => '1.1',
		);
		//phpcs:enable
		Site_Search_One_Debugging::log( 'SS1-INFO Retrieving Index ' . $index_uuid . ' Stored fields...' );
		$request = wp_remote_post( $endpoint, $options );
		if ( is_wp_error( $request ) ) {
			return $request;
		}
		$response_code = wp_remote_retrieve_response_code( $request );
		if ( $response_code >= 200 && $response_code < 300 ) {
			Site_Search_One_Debugging::log( 'SS1-INFO Fields retrieved successfully' );
			$response_body = wp_remote_retrieve_body( $request );
			$cached        = $this->cache_get_stored_fields_response( $response_body, $index_uuid );
			if ( is_wp_error( $cached ) ) {
				Site_Search_One_Debugging::log( 'SS1-WARNING Failed to cache response body' );
				Site_Search_One_Debugging::log( $cached );
			}
			return $response_body;
		} else {
			Site_Search_One_Debugging::log( 'SS1-ERROR Non 2xx response retrieving stored fields for index_uuid ' . $index_uuid );
			return new WP_Error( 'failed_retrieve_stored_fields', 'Server sent non 2xx response code ' . $response_code );
		}
	}

	/**
	 * Cache get stored fields response so we don't have to request to SC1 next time
	 *
	 * @param string $response_body stored fields response body.
	 * @param string $index_uuid SC1 Index UUID.
	 * @return bool|WP_Error
	 */
	private function cache_get_stored_fields_response( $response_body, $index_uuid ) {
		$cache_key = 'site_search_one_cached_stored_fields_response_' . $index_uuid;
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_globals';
		// CSF Standing for CachedStoredFields.
		$key = 'CSF_' . $index_uuid;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$replaced = $wpdb->replace(
			$table_name,
			array(
				'setting' => $key,
				'value'   => $response_body,
			),
			'%s'
		);
		// phpcs:enable
		wp_cache_set( $cache_key, $response_body, '', 30 );
		if ( false === $replaced ) {
			return new WP_Error( 'db_error', 'Failed Cache Get Stored Fields Response', $wpdb->last_error );
		}
		return true;
	}

	/**
	 * Cache get enumerable fields response we don't have to request from SC1 next time
	 *
	 * @param string $response_body enumerable fields response body.
	 * @param string $index_uuid SC1 index uuid.
	 * @return bool|WP_Error
	 */
	private function cache_get_enumerable_fields_response( $response_body, $index_uuid ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_globals';
		// CEF Standing for CachedEnumerableFields.
		$key = 'CEF_' . $index_uuid;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$replaced = $wpdb->replace(
			$table_name,
			array(
				'setting' => $key,
				'value'   => $response_body,
			),
			'%s'
		);
		// phpcs:enable
		if ( false === $replaced ) {
			return new WP_Error( 'db_error', 'Failed Cache Get Enumerable Fields Response', $wpdb->last_error );
		}
		return true;
	}

	/**
	 * Get cached stored fields response for the given index uuid. Null if no response cached.
	 *
	 * @param string $index_uuid SC1 Index UUID.
	 * @return string|null
	 */
	private function get_cached_get_stored_fields_response( $index_uuid ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'ss1_globals';
		$key        = 'CSF_' . $index_uuid;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		$query  = $wpdb->prepare( 'SELECT value FROM ' . $table_name . ' WHERE setting=%s', $key );
		$result = $wpdb->get_var( $query );
		// phpcs:enable
		return $result;
	}

	/**
	 * Get cached enumerable fields response for given index uuid. Null if no response cached.
	 *
	 * @param string $index_uuid SC1 Index UUID.
	 * @return string|null
	 */
	private function get_cached_get_enumerable_fields_response( $index_uuid ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'ss1_globals';
		$key        = 'CEF_' . $index_uuid;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$query  = $wpdb->prepare( 'SELECT value FROM ' . $table_name . ' WHERE setting=%s', $key );
		$result = $wpdb->get_var( $query );

		return $result;
	}

	/**
	 * Invalidate any cached stored field responses from SC1.
	 *
	 * @param string $index_uuid SC1 Index UUID.
	 * @return bool|WP_Error
	 */
	private function invalidate_cached_get_stored_fields_response( $index_uuid ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_globals';
		$key        = 'CSF_' . $index_uuid;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare( 'DELETE FROM ' . $table_name . ' WHERE setting=%s', $key );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$deleted = $wpdb->query( $query );
		// phpcs:enable
		if ( false === $deleted ) {
			return new WP_Error( 'db_error', 'Failed to invalidate cached get stored fields response', $wpdb->last_error );
		}
		return true;
	}

	/**
	 * Invalidate any cached enumerable field responses from SC1.
	 *
	 * @param string $index_uuid SC1 Index UUID.
	 * @return bool|WP_Error
	 */
	private function invalidate_cached_get_enumerable_fields_response( $index_uuid ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_globals';
		$key        = 'CEF_' . $index_uuid;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare( 'DELETE FROM ' . $table_name . ' WHERE setting=%s', $key );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		$deleted = $wpdb->query( $query );
		// phpcs:enable
		if ( false === $deleted ) {
			return new WP_Error( 'db_error', 'Failed to invalidate cached get enumerable fields response', $wpdb->last_error );
		}
		return true;
	}


	/**
	 * Adds stored fields to index. All marked as 'required' false, and data type String except 'Published' which is DateTime
	 * Used to make string fields enumerable, no longer does
	 *
	 * @param string      $index_uuid SC1 Index UUID.
	 * @param SC1_Field[] $fields SC1 Fields.
	 * @return WP_Error|true
	 */
	private function add_stored_fields_to_index( $index_uuid, $fields ) {
		$sc1_ix_fields               = array();
		$fields_should_be_enumerable = array();
		foreach ( $fields as $field ) {
			// enum Searchability
			// FullySearchable(0).
			// ValueOnly(1).
			// Hidden(2).

			$field_name = $field->get_field_name();

			if ( substr( $field_name, 0, 1 ) === '_' ) {
				// Hidden stored field.
				$searchability = 2;
			} else {
				$searchability = 0;
			}

			$extra_params = array(
				'Searchability' => $searchability,
			);

			if ( $field->is_field_taxonomy() ) {
				// Will be enumerable and show up in facets panel.
				$extra_params['SS1-Display']     = 'Taxonomy';
				$extra_params['SS1-DisplayName'] = $field_name;
				$field_name                      = $field->get_dts_enum_safe_field_name();
				array_push( $fields_should_be_enumerable, $field );
			}
			$multi_choice     = $field->is_field_multi_choice();
			$multi_choice_str = $multi_choice ? 'true' : 'false';
			if ( $multi_choice ) {
				// Will be enumerable but shows up in filters as drop down select.
				$extra_params['SS1-Display']     = 'Multi-Choice';
				$extra_params['SS1-DisplayName'] = $field_name;
				array_push( $fields_should_be_enumerable, $field );
				$field_name = $field->get_dts_enum_safe_field_name();
			}
			$meta_type    = $this->get_field_meta_type( $field );
			$sc1_ix_field = new SC1_Index_Field( $field_name, $meta_type, '', $extra_params, false );
			array_push( $sc1_ix_fields, $sc1_ix_field );
		}
		if ( count( $fields_should_be_enumerable ) > 0 ) {
			// One or more fields need to be made enumerable because they are multi-choice.
			$made_enumerable = $this->ensure_fields_enumerable( $index_uuid, $fields_should_be_enumerable );
			if ( is_wp_error( $made_enumerable ) ) {
				return $made_enumerable;
			}
		}
		$spec_updates = $this->update_index_meta_specification( $index_uuid, $sc1_ix_fields, array() );
		if ( is_wp_error( $spec_updates ) ) {
			return $spec_updates;
		}

		return true;
	}

	/**
	 * Get the type of metadata the field should be on SC1. Might return 'DateTime' or 'String'
	 *
	 * @param SC1_Field $field SC1 Field.
	 *
	 * @return string
	 */
	private function get_field_meta_type( $field ) {
		if ( $field->is_date() ) {
			return 'DateTime';
		} else {
			return 'String';
		}
	}

	/**
	 * Update the given indexes metadata specification on SC1.
	 *
	 * @param string            $index_uuid SC1 Index UUID.
	 * @param SC1_Index_Field[] $new_fields new fields to create.
	 * @param int[]             $remove_field_ids field id's to remove.
	 * @param boolean           $reindex Whether or not SC1 should reindex all documents after this operation.
	 * @return WP_Error|true
	 */
	public function update_index_meta_specification( $index_uuid, $new_fields, $remove_field_ids, $reindex = false ) {
		set_time_limit( 0 );
		$this->ss1_field_cache->invalidate(); // Invalidate the cache.
		$invalidated = $this->invalidate_cached_get_stored_fields_response( $index_uuid );
		if ( is_wp_error( $invalidated ) ) {
			return $invalidated;
		}
		$api_key  = self::get_sc1_api_key();
		$endpoint = get_transient( 'ss1-endpoint-url' ) . '/IndexManager';
		$data     = array(
			'APIKey'    => $api_key,
			'Action'    => 'UpdateIndexMetaSpec',
			'IndexUUID' => $index_uuid,
			'Updates'   => array(
				'NewFields'    => $new_fields,
				'DeleteFields' => $remove_field_ids,
				'Reindex'      => $reindex,
			),
		);
		//phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout
		$options  = array(
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'        => wp_json_encode( $data, JSON_UNESCAPED_UNICODE ),
			'method'      => 'POST',
			'data_format' => 'body',
			'timeout'     => '30',
		);
		//phpcs:enable
		$request  = wp_remote_post( $endpoint, $options );
		if ( is_wp_error( $request ) ) {
			return $request;
		}
		$response_code = wp_remote_retrieve_response_code( $request );
		if ( $response_code >= 200 && $response_code < 300 ) {
			Site_Search_One_Debugging::log( 'SS1-INFO Successfully updated Index Meta Specification' );
			return true;
		} else {
			$response_body = wp_remote_retrieve_body( $request );
			Site_Search_One_Debugging::log( 'SS1-ERROR Non 2xx Response setting stored fields ' . $response_code );
			Site_Search_One_Debugging::log( $response_body );
			return new WP_Error( 'non_2xx_response', 'Error updating Index Meta Specification - Non 2xx response' );
		}
	}

	/**
	 * Ensure the given fields are marked as enumerable on SC1 for given Index UUID.
	 *
	 * @param string      $index_uuid SC1 Index UUID.
	 * @param SC1_Field[] $fields SC1 Fields.
	 *
	 * @return true|WP_Error
	 */
	public function ensure_fields_enumerable( $index_uuid, $fields ) {
		if ( count( $fields ) === 0 ) {
			return true;
		}
		$enumerable_fields         = $this->get_sc1_index_enumerable_fields( $index_uuid );
		$num_new_enumerable_fields = 0;
		if ( is_wp_error( $enumerable_fields ) ) {
			return $enumerable_fields;
		}
		foreach ( $fields as $field ) {
			$found      = false;
			$field_name = $field->get_dts_enum_safe_field_name();
			foreach ( $enumerable_fields as $enumerable_field ) {
				if ( $enumerable_field === $field_name ) {
					$found = true;
					break;
				}
			}
			if ( false === $found ) {
				array_push( $enumerable_fields, $field_name );
				++ $num_new_enumerable_fields;
			}
		}
		if ( 0 === $num_new_enumerable_fields ) {
			return true;
		}
		Site_Search_One_Debugging::log( 'SS1-INFO Found new Enumerable Fields:' );
		Site_Search_One_Debugging::log( $enumerable_fields );
		return $this->set_sc1_index_enumerable_fields( $index_uuid, $enumerable_fields, false );
	}

	/**
	 * Check if given field is enumerable based on field cache only.
	 *
	 * @param SC1_Field $field the field.
	 * @param object    $enumerable_field_cache the field cache.
	 *
	 * @return bool
	 */
	private function is_field_enumerable( $field, $enumerable_field_cache ) {
		$field_name = $field->get_dts_enum_safe_field_name();
		foreach ( $enumerable_field_cache as $enumerable_field ) {
			if ( $enumerable_field === $field_name ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Ensure that Index Spec is cached to DB for all indexes.
	 *
	 * @return array|bool|WP_Error
	 */
	public function ensure_spec_cached_to_db() {
		// region 1. Get all of the Index UUID's assosciated with this site.
		$index_uuids        = array();
		$index_sc1_modified = array();
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-search-page.php';
		$search_pages = Site_Search_One_Search_Page::get_all_search_pages();
		foreach ( $search_pages as $search_page ) {
			array_push( $index_uuids, $search_page->get_sc1_ix_uuid() );
			array_push( $index_sc1_modified, $search_page->get_sc1_ix_modified() );
		}
		// endregion
		// region 2. Perform a ListIndex Request to find out if any of these indexes have since been updated.
		$index_uuids_require_updates = array();
		set_time_limit( 60 );
		$api_key  = self::get_sc1_api_key();
		$endpoint = get_transient( 'ss1-endpoint-url' ) . '/IndexManager';
		$data     = array(
			'APIKey'              => $api_key,
			'Action'              => 'ListIndexes',
			'FilterToIndexes'     => $index_uuids,
			'IncludeMetaSpec'     => false,
			'IncludePending'      => false,
			'IncludeRecycleCount' => false,
			'IncludeNotices'      => false,
			'IncludeIndexInfo'    => false,
		);
		//phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout
		$options  = array(
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'        => wp_json_encode( $data ),
			'method'      => 'POST',
			'data_format' => 'body',
			'timeout'     => '30',
			'blocking'    => true,
			'httpversion' => '1.1',
		);
		//phpcs:enable
		$request  = wp_remote_post( $endpoint, $options );
		if ( is_wp_error( $request ) ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Error performing ListIndexes Request' );
			Site_Search_One_Debugging::log( $request );
			return $request;
		}
		$response_code = wp_remote_retrieve_response_code( $request );
		if ( $response_code >= 200 && $response_code < 300 ) {
			$response_body = wp_remote_retrieve_body( $request );
			$data          = json_decode( $response_body );
			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$indexes = $data->Indexes;
			foreach ( $indexes as $index ) {
				$count_index_uuids = count( $index_uuids );
				for ( $i = 0; $i < $count_index_uuids; $i++ ) {
					$sp_ix_uuid = $index_uuids[ $i ];
					if ( $sp_ix_uuid === $index->IndexUUID ) {
						$sp_ix_sc1_modified = $index_sc1_modified[ $i ];
						if ( strval( $sp_ix_sc1_modified ) !== strval( $index->Modified ) ) {
							// Cached SC1 Modified date does not match Modified date on SC1.
							array_push( $index_uuids_require_updates, $sp_ix_uuid );
						}
					}
				}
			}
			// phpcs:enable
		} else {
			return new WP_Error( 'failed_listindex', 'Failed to retrieve listing of indexes on SC1.' );
		}
		// endregion
		// region 3. For each index that needs updating, bring back the meta spec, and cache it to disk.
		if ( count( $index_uuids_require_updates ) > 0 ) {
			Site_Search_One_Debugging::log( 'SS1-INFO Found ' . count( $index_uuids_require_updates ) . ' index uuids that require meta spec cache to be updated' );
			foreach ( $index_uuids_require_updates as $index_uuid_to_update ) {
				set_time_limit( 60 );
				$data    = array(
					'APIKey'              => $api_key,
					'Action'              => 'ListIndexes',
					'FilterToIndexes'     => array( $index_uuid_to_update ),
					'IncludeMetaSpec'     => true,
					'IncludePending'      => false,
					'IncludeRecycleCount' => false,
					'IncludeNotices'      => false,
					'IncludeIndexInfo'    => false,
				);
				//phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout
				$options = array(
					'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
					'body'        => wp_json_encode( $data ),
					'method'      => 'POST',
					'data_format' => 'body',
					'timeout'     => '30',
					'blocking'    => true,
					'httpversion' => '1.1',
				);
				//phpcs:enable
				$request = wp_remote_post( $endpoint, $options );
				if ( is_wp_error( $request ) ) {
					return $request;
				}
				$response_code = wp_remote_retrieve_response_code( $request );
				if ( $response_code >= 200 && $response_code < 300 ) {
					$response_body = wp_remote_retrieve_body( $request );
					$data          = json_decode( $response_body );
					// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$index    = $data->Indexes[0];
					$modified = $index->Modified;
					$spec     = $index->MetaSpecFields;
					if ( null === $spec || 'null' === $spec ) {
						$spec = array();
					}
					global $wpdb;
					$table_name = $wpdb->prefix . 'ss1_sc1_indexes';
					// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
					$query = $wpdb->prepare(
						'UPDATE ' . $table_name . ' SET cached_meta_spec = %s, cached_meta_spec_sc1_modified = %s, cached_meta_spec_local_time = NOW() WHERE sc1_ix_uuid=%s',
						wp_json_encode( $spec ),
						$modified,
						$index->IndexUUID
					);
					// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
					// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
					$rows_affected = $wpdb->query( $query );
					// phpcs:enable
					if ( false === $rows_affected ) {
						Site_Search_One_Debugging::log( 'SS1-ERROR Failed to update cached meta spec for ' . $index_uuid_to_update );

						return false;
					}
				} else {
					// Non 2xx response.
					Site_Search_One_Debugging::log( 'SS1-ERROR Non 2xx response getting meta spec for ' . $index_uuid_to_update );
					return false;
				}
			}
			// Done looping through indexes to update cached spec.
			Site_Search_One_Debugging::log( 'SS1-INFO Cached Meta Spec is now up to date!' );
		}

		return true;
		// endregion.
	}

	/**
	 * Cache Select Options for ACF Select fields. On SC1 these are stored as enumerable fields.
	 * Function will check for indexes belonging to this website who haven't had their select fields updated since
	 * the index was last updated.
	 */
	public function ensure_select_values_cached() {
		// region 0. Find all indexes that have not had their select field values updated since the index was last modified.
		global $wpdb;
		$table_indexes     = $wpdb->prefix . 'ss1_sc1_indexes';
		$table_searchpages = $wpdb->prefix . 'ss1_search_pages';
		$wp_site_url       = get_site_url();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$query   = $wpdb->prepare(
			'SELECT sc1_ix_id, sc1_ix_uuid, cached_meta_spec_sc1_modified FROM ' . $table_indexes
			. ' WHERE cached_top_select_field_values_sc1_modified < cached_meta_spec_sc1_modified AND wp_site_url = %s AND post_id IN (SELECT post_id FROM ' . $table_searchpages . ')',
			base64_encode( $wp_site_url )
		);
		$results = $wpdb->get_results( $query );
		// phpcs:enable
		if ( count( $results ) === 0 ) {
			return true; // Nothing to update.
		}
		foreach ( $results as $result ) {
			Site_Search_One_Debugging::log( $result );
			$index_id                      = $result->sc1_ix_id;
			$index_uuid                    = $result->sc1_ix_uuid;
			$cached_meta_spec_sc1_modified = intval( $result->cached_meta_spec_sc1_modified );
			// endregion
			// region 1. Check if the index has known select fields.

			$table_name = $wpdb->prefix . 'ss1_sc1_indexes';
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			$query = $wpdb->prepare(
				'SELECT cached_meta_spec FROM ' . $table_name . ' WHERE sc1_ix_uuid=%s',
				$index_uuid
			);
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery

			$res = $wpdb->get_var( $query );
			// phpcs:enable
			if ( null === $res ) {
				continue;
			} // The index's fields are not yet cached locally
			$spec          = json_decode( $res, true ); // This is the MetaSpecFields part of ListIndex response.
			$select_fields = array();
			$count_spec    = count( $spec );
			for ( $i = 0; $i < $count_spec; ++ $i ) {
				$spec_field   = $spec[ $i ];
				$extra_params = $spec_field['ExtraParams'];
				if ( strpos( $extra_params, '{' ) === 0 ) {
					// String starts with {, likely json string
					// This is json encoded to a string...
					$extra_params = json_decode( $extra_params, true );
					if ( array_key_exists( 'SS1-Display', $extra_params )
						&& 'Multi-Choice' === $extra_params['SS1-Display']
					) {
						array_push( $select_fields, $spec_field['DisplayName'] );
					}
				}
			}
			// region Ensure that the Mimetype field gets cached.
			if ( ! in_array( 'Mimetype', $select_fields, true ) && ! in_array( 'mimetype', $select_fields, true ) ) {
				array_push( $select_fields, 'Mimetype' );
			}
			// endregion.
			// endregion.
			$modified = self::sc1_indexing_check( $index_uuid );
			if ( $modified > $cached_meta_spec_sc1_modified ) {
				// Actually the field spec needs to be updated in this scenario...
				set_time_limit( 90 );
				self::ensure_spec_cached_to_db();
				sleep( 10 );
			}
			if ( false === $modified ) {
				Site_Search_One_Debugging::log( 'SS1-Error Indexing check failed' );
				return false;
			}
			// region 2. If select fields are known, request their values from SC1.
			if ( count( $select_fields ) > 0 ) {
				set_time_limit( 60 );
				$endpoint = get_transient( 'ss1-endpoint-url' ) . '/Search';
				$api_key  = self::get_sc1_api_key();
				$data     = array(
					'APIKey'     => $api_key,
					'Indexes'    => array(
						array(
							'IndexID'   => $index_id,
							'IndexUUID' => $index_uuid,
						),
					),
					'Parameters' => array(
						'GetTopFieldValues' => array(
							'CaseSensitive' => false,
							'Fields'        => $select_fields,
							'MaxResults'    => 64,
						),
						'IncludeContext'    => false,
						'Page'              => 1,
						'Query'             => 'xfirstword',
						'ResultsPerPage'    => 1,
					),
				);
				//phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout
				$options  = array(
					'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
					'body'        => wp_json_encode( $data ),
					'method'      => 'POST',
					'data_format' => 'body',
					'timeout'     => '30',
					'blocking'    => true,
					'httpversion' => '1.1',
				);
				//phpcs:enable
				$request  = wp_remote_post( $endpoint, $options );
				if ( is_wp_error( $request ) ) {
					Site_Search_One_Debugging::log( 'SS1-ERROR Something went wrong checking select field values' );
					Site_Search_One_Debugging::log( $request );

					return false;
				}
				$response_code = wp_remote_retrieve_response_code( $request );
				if ( $response_code >= 200 && $response_code < 300 ) {

					$response_body    = wp_remote_retrieve_body( $request );
					$data             = json_decode( $response_body, true );
					$top_field_values = $data['TopFieldValues'];

					// Now we have our values, put them into the database.
					// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
					// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
					// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
					$query         = $wpdb->prepare(
						'UPDATE ' . $table_name . ' SET cached_top_select_field_values =%s, cached_top_select_field_values_sc1_modified =%d WHERE sc1_ix_uuid=%s',
						wp_json_encode( $top_field_values ),
						$modified,
						$index_uuid
					);
					$rows_affected = $wpdb->query( $query );
					// phpcs:enable

					continue;

				} else {
					Site_Search_One_Debugging::log( 'SS1-ERROR Something went wrong checking select field values' );
					Site_Search_One_Debugging::log( 'Non 2xx response: ' . $response_code );

					return false;
				}
			} else {
				// No select fields, but prevent further queries on this index.
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
				$query         = $wpdb->prepare(
					'UPDATE ' . $table_name . ' SET cached_top_select_field_values =%s, cached_top_select_field_values_sc1_modified =%d WHERE sc1_ix_uuid=%s',
					wp_json_encode( array() ),
					$modified,
					$index_uuid
				);
				$rows_affected = $wpdb->query( $query );
				// phpcs:enable
				// 0 === rows affected is not an error
				continue;
			}
			// endregion.
		}
		return true;
	}

	/**
	 * Performs a ListIndex request to SC1 to determine if the index has finished indexing.
	 * If still indexing, returns 0, else returns index SC1 last modified,
	 * On failure for any reason, returns false.
	 *
	 * @param string $index_uuid SC1 Index UUID.
	 *
	 * @return false|int
	 */
	private function sc1_indexing_check( $index_uuid ) {
		set_time_limit( 30 );
		$api_key  = self::get_sc1_api_key();
		$endpoint = get_transient( 'ss1-endpoint-url' ) . '/IndexManager';
		$data     = array(
			'APIKey'              => $api_key,
			'Action'              => 'ListIndexes',
			'FilterToIndexes'     => array( $index_uuid ),
			'IncludeMetaSpec'     => false,
			'IncludePending'      => true,
			'IncludeRecycleCount' => false,
			'IncludeNotices'      => false,
			'IncludeIndexInfo'    => false,
		);
		//phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout
		$options  = array(
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'        => wp_json_encode( $data ),
			'method'      => 'POST',
			'data_format' => 'body',
			'timeout'     => '20',
			'blocking'    => true,
			'httpversion' => '1.1',
		);
		//phpcs:enable
		$request  = wp_remote_post( $endpoint, $options );
		if ( is_wp_error( $request ) ) {
			return false;
		}
		$response_code = wp_remote_retrieve_response_code( $request );
		if ( $response_code >= 200 && $response_code < 300 ) {
			$response_body = wp_remote_retrieve_body( $request );
			$response_data = json_decode( $response_body, true );
			$index         = $response_data['Indexes'][0];
			if ( intval( $index['Pending'] ) > 0 ) {
				sleep( 3 );
				return 0; // Still indexing.
			} else {
				return $index['Modified']; // All good!
			}
		} else {
			return false;
		}
	}

	/**
	 * Set Enumerable fields for Index on SearchCloudOne. Checks if the same fields are already set on SC1, if so does nada
	 * to avoid triggering unnecessary re-index.
	 * This should not called when creating an index, but instead prior to uploading a post as it's easier to recover a failure
	 * during uploading a post than when creating an index, and allows continuous updating as user adjusts settings..
	 *
	 * @param string $index_uuid SC1 Index UUID.
	 * @param array  $enumerable_fields of string fields to make enumerable.
	 * @param bool   $reindex Whether or not SC1 should reindex.
	 *
	 * @return true|WP_Error true on success/fields already set, WP_Error otherwise
	 */
	private function set_sc1_index_enumerable_fields( $index_uuid, $enumerable_fields, $reindex = false ) {
		$this->ss1_field_cache->invalidate(); // Invalidate the cache.
		$invalidated = $this->invalidate_cached_get_enumerable_fields_response( $index_uuid );
		if ( is_wp_error( $invalidated ) ) {
			return $invalidated;
		}
		$api_key = self::get_sc1_api_key();
		// region 1. Get existing Enumerable fields.
		$existing_enumerable_fields = $this->get_sc1_index_enumerable_fields( $index_uuid );
		if ( is_wp_error( $existing_enumerable_fields ) ) {
			return $existing_enumerable_fields; // return the error.
		}
		// endregion
		// region 2. Compare against the enumerable fields we're setting.
		sort( $enumerable_fields );
		sort( $existing_enumerable_fields );
		if ( $enumerable_fields === $existing_enumerable_fields ) {
			return true; // There is no difference, no need to trigger a re-index.
		}
		// endregion
		// region 3. Only if there are new fields, or fields removed, do we actually set enumerable fields to avoid unnecessarily triggering re-index.
		$body     = array(
			'APIKey'    => $api_key,
			'Action'    => 'AddEnumerableFields',
			'IndexUUID' => $index_uuid,
			'Fields'    => $enumerable_fields,
			'Reindex'   => $reindex,
		);
		//phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout
		$args     = array(
			'body'        => wp_json_encode( $body, JSON_UNESCAPED_UNICODE ),
			'timeout'     => '20',
			'blocking'    => true,
			'data_format' => 'body',
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'httpversion' => '1.1',
		);
		//phpcs:enable
		$endpoint = get_transient( 'ss1-endpoint-url' ) . '/IndexManager';
		$request  = wp_remote_post( $endpoint, $args );
		if ( is_wp_error( $request ) ) {
			return $request;
		}
		return true;
		// endregion.
	}

	/**
	 * Retrieve Enumerable fields configured in SearchCloudOne Index
	 *
	 * @param string $index_uuid SC1 Index UUID.
	 *
	 * @return array|WP_Error
	 * array of enumerable fields, or WP_Error obj on failure.
	 */
	private function get_sc1_index_enumerable_fields( $index_uuid ) {
		set_time_limit( 0 );
		$cached_enumerable_fields = $this->ss1_field_cache->get_cached_enumerable_fields( $index_uuid );
		if ( $cached_enumerable_fields ) {
			return $cached_enumerable_fields;
		}
		$response_body = $this->get_sc1_index_enumerable_fields_response_body( $index_uuid );
		if ( is_wp_error( $response_body ) ) {
			return $response_body;
		}
		$obj = json_decode( $response_body );
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$existing_enumerable_fields = $obj->Fields;
		// phpcs:enable
		$this->ss1_field_cache->add_enumerable_field( $index_uuid, $existing_enumerable_fields );
		return $existing_enumerable_fields;
	}

	/**
	 * Get the enumerable fields response body from SC1. (May return cached version)
	 *
	 * @param string $index_uuid SC1 Index UUID.
	 *
	 * @return array|string|WP_Error
	 */
	private function get_sc1_index_enumerable_fields_response_body( $index_uuid ) {
		$cached_response = $this->get_cached_get_enumerable_fields_response( $index_uuid );
		if ( null !== $cached_response ) {
			return $cached_response;
		}
		$api_key = self::get_sc1_api_key();
		// region 1. Get existing Enumerable fields.
		$body     = array(
			'APIKey'  => $api_key,
			'Action'  => 'GetEnumerableFields',
			'Indexes' => array( $index_uuid ),
		);
		//phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout
		$args     = array(
			'body'        => wp_json_encode( $body ),
			'timeout'     => '30',
			'blocking'    => true,
			'data_format' => 'body',
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'httpversion' => '1.1',
		);
		//phpcs:enable
		$endpoint = get_transient( 'ss1-endpoint-url' ) . '/IndexManager';
		Site_Search_One_Debugging::log( 'SS1-INFO Getting Index [' . $index_uuid . '] Enumerable Fields... ' );
		$request = wp_remote_post( $endpoint, $args );
		if ( is_wp_error( $request ) ) {
			return $request;
		}
		$response_body = wp_remote_retrieve_body( $request );
		$cached        = $this->cache_get_enumerable_fields_response( $response_body, $index_uuid );
		if ( is_wp_error( $cached ) ) {
			Site_Search_One_Debugging::log( 'SS1-WARNING Failed to cache enumerable fields response' );
			Site_Search_One_Debugging::log( $cached );
		}
		return wp_remote_retrieve_body( $request );
	}


	/**
	 * Get the API Key the SS1 Plugin is using
	 *
	 * @return false|string
	 */
	public static function get_sc1_api_key() {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// Try transient first...
		$api_key = get_transient( 'ss1-apiKey' );
		if ( false !== $api_key ) {
			return $api_key;
		}
		// Not set as transient. Try restore from DB.
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_globals';
		$query      = $wpdb->prepare(
			'SELECT value FROM ' . $table_name . ' WHERE setting=%s',
			'ss1-apiKey'
		);
		$result     = $wpdb->get_var( $query );
		// phpcs:enable
		if ( null !== $result ) {
			set_transient( 'ss1-apiKey', $result ); // Avoid hitting DB in future.
			return $result;
		}
		return false;
	}

	/**
	 * Check if long running threads disabled
	 *
	 * @return false|mixed|string
	 */
	public static function ss1_is_long_running_threads_disabled() {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// Try transient first...
		$ss1_disable_long_running_threads = get_transient( 'ss1-disableLongRunningThreads' );
		if ( false !== $ss1_disable_long_running_threads ) {
			return $ss1_disable_long_running_threads;
		}
		// Not set as transient. Try restore from DB.
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_globals';
		$query      = $wpdb->prepare(
			'SELECT value FROM ' . $table_name . ' WHERE setting=%s',
			'ss1-disableLongRunningThreads'
		);
		$result     = $wpdb->get_var( $query );
		// phpcs:enable
		if ( null !== $result ) {
			set_transient( 'ss1-disableLongRunningThreads', $result ); // Avoid hitting DB in future.
			return $result;
		}
		set_transient( 'ss1-disableLongRunningThreads', 'no' );
		return false;
	}

	/**
	 * Get maximum batch size of bulk uploads.
	 * Typically 32.
	 *
	 * @return int Maximum size.
	 */
	public static function ss1_get_maximum_batch_size_for_bulk_uploads() {
		$val = 32;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$ss1_maximum_batch_size = get_transient( 'ss1-maximumBatchSize' );
		if ( false !== $ss1_maximum_batch_size ) {
			$val = intval( $ss1_maximum_batch_size );
			if ( $val < 1 ) {
				$val = 1;
			}
			if ( $val > 32 ) {
				$val = 32;
			}
			return $val;
		}
		// Not set as transient. Try restore from db.
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_globals';
		$query      = $wpdb->prepare(
			'SELECT value FROM ' . $table_name . ' WHERE setting=%s',
			'ss1-maximumBatchSize'
		);
		$result     = $wpdb->get_var( $query );
		// phpcs:enable
		if ( null !== $result ) {
			$val = intval( $result );
		}
		if ( $val < 1 ) {
			$val = 1;
		}
		if ( $val > 32 ) {
			$val = 32;
		}
		set_transient( 'ss1-maximumBatchSize', strval( $result ) ); // Avoid hitting db in future.
		return $val;
	}

	/**
	 * Set the API Key the SS1 Plugin should use
	 *
	 * @param string $api_key SC1 API Key.
	 * @return false|int 1 on success, false on failure
	 */
	public static function set_sc1_api_key( $api_key ) {
		set_transient( 'ss1-apiKey', $api_key ); // Avoid hitting DB in future.
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_globals';
		// region First check if api key is already set in db.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE `setting` = %s', 'ss1-apiKey' );
		$count = $wpdb->get_var( $query );
		if ( null === $count ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Failed to check if ss1-apiKey is set in DB - Query returned null' );
		}
		// endregion.
		if ( intval( $count ) === 0 ) {
			return $wpdb->insert(
				$table_name,
				array(
					'setting' => 'ss1-apiKey',
					'value'   => $api_key,
				)
			);
		} else {
			// Updating existing setting.
			$result = $wpdb->update(
				$table_name,
				array(
					'value' => $api_key,
				),
				array(
					'setting' => 'ss1-apiKey',
				),
				'%s',
				'%s'
			);
			if ( false !== $result ) {
				return 1;
			}
			// phpcs:enable
			return false;
		}
	}

	/**
	 * Delete post from SC1 Index.
	 *
	 * @param int    $post_id WP Post ID.
	 * @param string $index_uuid SC1 Index UUID.
	 *
	 * @return bool
	 */
	public function delete_post_from_index( $post_id, $index_uuid ) {
		// region 1. Retrieve API Key, Uploaded FileUUID.
		$api_key = $this->get_sc1_api_key();
		if ( ! $api_key ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Cant delete post from index - API Key not set' );
			return false;
		}
		$index_exists = $this->does_index_exist( $index_uuid );
		if ( is_wp_error( $index_exists ) ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Cant delete post from index - Error checking index exists:' );
			Site_Search_One_Debugging::log( $index_exists );
			return false;
		}
		if ( false === $index_exists ) {
			return true;
		} // The index itself has been deleted
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-post.php';
		$ss1_post      = new Site_Search_One_Post( $post_id );
		$sc1_file_uuid = $ss1_post->get_is_post_uploaded_to_index( $index_uuid );
		if ( false === $sc1_file_uuid ) {
			// Not in this index, just return true as though deletion successful.
			return true;
		}
		// endregion
		// region 2. Attempt to perform the deletion, return True if succeeds, else false.
		$endpoint = get_transient( 'ss1-endpoint-url' ) . '/Files';
		$body     = array(
			'APIKey'             => $api_key,
			'Action'             => 'RemoveFilesFromIndex',
			'IndexUUID'          => $index_uuid,
			'Files'              => array(
				array(
					'FileUUID' => $sc1_file_uuid,
				),
			),
			'IgnoreMissingFiles' => true,
		);
		//phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout
		$args     = array(
			'body'        => wp_json_encode( $body ),
			'timeout'     => '20',
			'blocking'    => true,
			'data_format' => 'body',
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'httpversion' => '1.1',
		);
		//phpcs:enable
		$request  = wp_remote_post( $endpoint, $args );
		if ( is_wp_error( $request ) ) {
			return false; // Failed to delete file.
		}
		$code = wp_remote_retrieve_response_code( $request );
		if ( $code >= 200 && $code < 300 ) {
			return true;
		} else {
			Site_Search_One_Debugging::log( 'SS1-ERROR non 2xx response code attempting to remove file ' . $sc1_file_uuid . '(' . $post_id . ') from index:' );
			$body = wp_remote_retrieve_body( $request );
			Site_Search_One_Debugging::log( $body );
			return false;
		}
		// endregion.
	}

	/**
	 * Attempt to retrieve the IndexID for IndexUUID from SC1
	 *
	 * @param string $index_uuid SC1 Index UUID.
	 *
	 * @return int|WP_Error
	 */
	public function retrieve_index_id_from_sc1( $index_uuid ) {
		$endpoint = get_transient( 'ss1-endpoint-url' ) . '/IndexManager';
		$api_key  = self::get_sc1_api_key();
		$data     = array(
			'APIKey'              => $api_key,
			'Action'              => 'ListIndexes',
			'FilterToIndexes'     => array( $index_uuid ),
			'IncludeMetaSpec'     => false,
			'IncludePending'      => false,
			'IncludeRecycleCount' => false,
			'IncludeNotices'      => false,
			'IncludeIndexInfo'    => false,
		);
		//phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout
		$options  = array(
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'body'        => wp_json_encode( $data ),
			'method'      => 'POST',
			'data_format' => 'body',
			'timeout'     => 30,
		);
		//phpcs:enable
		$request  = wp_remote_post( $endpoint, $options );
		if ( is_wp_error( $request ) ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR A WP_Error was incurred attempting to retrieve the ID of the Index:' );
			Site_Search_One_Debugging::log( $request );
			return $request;
		}
		$response_body = wp_remote_retrieve_body( $request );
		$response_data = json_decode( $response_body, true );
		foreach ( $response_data['Indexes'] as $index ) {
			$id   = $index['IndexID'];
			$uuid = $index['IndexUUID'];
			if ( $uuid === $index_uuid ) {
				// Bingo.
				try {
					// region 1. Save this to database so we don't need to request it in future.
					global $wpdb;
					$table_name = $wpdb->prefix . 'ss1_sc1_indexes';
					$data       = array(
						'sc1_ix_id' => $id,
					);
					$where      = array(
						'sc1_ix_uuid' => $index_uuid,
					);
					// endregion.
					// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
					// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->update( $table_name, $data, $where );
					// phpcs:enable
				} catch ( Exception $ex ) {
					Site_Search_One_Debugging::log( 'SS1-ERROR Database error associating IndexID to IndexUUID:' );
					Site_Search_One_Debugging::log( $ex );
				} finally {
					return $id;
				}
			}
		}
		return new WP_Error( 'failed_get_id', 'Failed to get ID' );

	}
}


/**
 * Index Field Class as used by SC1, field names here must not be snake cased as SC1 uses PascalCasing and is
 * case sensitive.
 */
class SC1_Index_Field {

	// Not snake case because these are used by SC1 API.
	// phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
	// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	/**
	 * Field display name.
	 *
	 * @var false|string
	 */
	public $DisplayName;
	/**
	 * Field SC1 MetaType.
	 *
	 * @var string
	 */
	public $MetaType;
	/**
	 * Field SC1 default value.
	 *
	 * @var string
	 */
	public $DefaultValue;
	/**
	 * Field SC1 ExtraParams.
	 *
	 * @var object
	 */
	public $ExtraParams;
	/**
	 * Field SC1 Required.
	 *
	 * @var bool
	 */
	public $Required;

	/**
	 * SC1_Index_Field constructor.
	 *
	 * @param string $name Displayname of field.
	 * @param string $meta_type "String"|"Integer"|"Boolean"|"DateTime"|"Date" Meta data type.
	 * @param string $default_value default value.
	 * @param object $extra_params (Just pass an empty stdClass).
	 * @param bool   $required Whether or not field is required.
	 */
	public function __construct( $name, $meta_type, $default_value, $extra_params, $required ) {
		if ( strlen( $name ) > 64 ) {
			$name = substr( $name, 0, 63 ); // Field DisplayNames must not exceed 64 characters on SC1.
		}
		$this->DisplayName  = $name;
		$this->MetaType     = $meta_type;
		$this->DefaultValue = $default_value;
		$this->ExtraParams  = $extra_params;
		$this->Required     = $required;
	}

	// phpcs:enable
}
