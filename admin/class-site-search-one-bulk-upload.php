<?php
/**
 * Class for handling Bulk Uploads to SearchCloudOne.
 *
 * @package    Site_Search_One
 */

/* @noinspection PhpMissingParamTypeInspection */

/**
 * Class for handling Bulk Uploads to SearchCloudOne.
 */
class Site_Search_One_Bulk_Upload {

	/**
	 * Post Ids to be uploaded
	 *
	 * @var array
	 */
	private $post_ids;

	/**
	 * Index UUID each post is being uploaded into.
	 *
	 * @var array
	 */
	private $post_index_uuids;

	/**
	 * Task ID's this upload belongs to.
	 *
	 * @var array
	 */
	private $task_ids;

	/**
	 * Delimiter to use during bulk upload.
	 *
	 * @var string
	 */
	private $delimiter;

	/**
	 * Instance of SC1_Index_Manager.
	 *
	 * @var SC1_Index_Manager
	 */
	private $index_mgr;

	/**
	 * Site_Search_One_Bulk_Upload constructor.
	 *
	 * @param SC1_Index_Manager $index_mgr SC1 Index Manager.
	 */
	public function __construct( $index_mgr ) {
		$this->index_mgr        = $index_mgr;
		$this->post_ids         = array();
		$this->post_index_uuids = array();
		$this->task_ids         = array();
		$boundary               = uniqid();
		$this->delimiter        = '-------------' . $boundary;
	}

	/**
	 * Add a post that will be uploaded as a part of this bulk upload job
	 *
	 * @param int    $post_id ID of POST to be uploaded in this bulk upload.
	 * @param string $index_uuid SC1 Index UUID. UID of the Index that the post will be uploaded to.
	 * @param int    $task_id The task id this post belongs to.
	 */
	public function add_post( $post_id, $index_uuid, $task_id ) {
		array_push( $this->post_ids, $post_id );
		array_push( $this->post_index_uuids, $index_uuid );
		array_push( $this->task_ids, $task_id );
	}

	/**
	 * Synchronous.
	 * Performs the bulk upload of all documents added.
	 *
	 * @param int  $cron_id The cron task id.
	 * @param bool $disable_long_running_threads Whether or not long running threads is disabled.
	 * @return array|false|int
	 * On Success, an array of FileUUIDs
	 * On General Failure, false
	 * On Failure caused by a specific post id, the post id that caused the failure.
	 */
	public function perform_upload( $cron_id, $disable_long_running_threads = false ) {
		$num_files = count( $this->post_ids );
		if ( 0 === $num_files ) {
			return array(); // Nothing was added to the bulk uploader.
		}
		require_once plugin_dir_path( __FILE__ ) . 'class-sc1-index-manager.php';
		$api_key            = SC1_Index_Manager::get_sc1_api_key();
		$multipart_contents = $this->build_multipart_req_body( $cron_id );
		if ( is_wp_error( $multipart_contents ) ) {
			Site_Search_One_Debugging::log( $cron_id . ' SS1-ERROR An error occurred preparing multipart body:' );
			Site_Search_One_Debugging::log( $multipart_contents );
			return false;
		}
		$endpoint = get_transient( 'ss1-endpoint-url' ) . '/Files';
		//phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout
		$options  = array(
			'body'        => $multipart_contents,
			'headers'     => array(
				'Content-Type'       => 'multipart/form-data; boundary=' . $this->delimiter,
				'APIKey'             => $api_key,
				'SC1ProtocolVersion' => '2',
			),
			'timeout'     => 60,
			'redirection' => 5,
			'blocking'    => true,
			'httpversion' => '1.1',
		);
		//phpcs:enable
		set_time_limit( 0 );
		$response = wp_remote_post( $endpoint, $options );
		if ( is_wp_error( $response ) ) {
			Site_Search_One_Debugging::log( $cron_id . 'SS1-ERROR Failed to upload posts to SC1:' );
			Site_Search_One_Debugging::log( $response );
			return false;
		} else {
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );
			if ( $response_code >= 200 && $response_code < 300 ) {
				Site_Search_One_Debugging::log( $cron_id . 'SS1-INFO Uploaded ' . $num_files . ' items successfully' );
				return json_decode( $response_body );
			} else {
				// TODO The new protocol will give the specific post id that caused a problem, need a way to handle
				// that so as to allow a batch to skip a post id ..
				Site_Search_One_Debugging::log( $cron_id . 'SS1-ERROR Failed to Upload, Non 2xx response code: ' . $response_code );
				Site_Search_One_Debugging::log( $response_body );
				return false;
			}
		}
	}

	/**
	 * Logs Memory usage to debug log.
	 */
	private function log_memory_usage() {
		$mem_usage = memory_get_usage( true );

		if ( $mem_usage < 1024 ) {
			Site_Search_One_Debugging::log( $mem_usage . ' bytes used' );
		} elseif ( $mem_usage < 1048576 ) {
			Site_Search_One_Debugging::log( round( $mem_usage / 1024, 2 ) . ' kilobytes used' );
		} else {
			Site_Search_One_Debugging::log( round( $mem_usage / 1048576, 2 ) . ' megabytes used' );
		}
	}

	/**
	 * Build the body of the multipart http request containing the necessary data to complete a
	 * bulk upload.
	 *
	 * This method may make HTTP requests if fields need to be made enumerable
	 *
	 * @param string $cron_id The Cron Task Id.
	 * @param bool   $disable_long_running_threads Whether or not long running threads are disabled.
	 * @return string|WP_Error
	 * Returns the request body necessary to upload all added posts to their indexes or false
	 * on failure.
	 * Failure may be caused by a field failing to be made enumerable or
	 */
	private function build_multipart_req_body( $cron_id = '{Not_Supplied}', $disable_long_running_threads = false ) {
		$max_execution_time = 5; // Only when disable_long_running_threads.
		$execution_start    = time();
		// region multipart/form-data boundary RFC 7578.

		$data = '';
		$eol  = "\r\n";
		// endregion
		// region Create numFiles portion.
		$num_files = count( $this->post_ids );
		$data     .= $this->make_text_part( 'NumFiles', (string) $num_files );
		// endregion
		// region For each of the posts to be uploaded, create the Options part and File part.
		$f          = 0;
		$start_time = time();
		while ( $f < $num_files ) {
			$post_id    = $this->post_ids[ $f ];
			$index_uuid = $this->post_index_uuids[ $f ];
			// region    Create Options Part.
			$json = $this->create_options_json(
				$post_id,
				$index_uuid,
				$disable_long_running_threads
			);
			if ( is_wp_error( $json ) ) {
				return $json;
			}
			$data .= $this->make_text_part( 'Options' . $f, $json );
			// endregion
			// region Create File Part.
			$post = get_post( $post_id );
			if ( 'attachment' !== $post->post_type ) {
				$post_content = apply_filters( 'the_content', $post->post_content );
				$html         = $this->wrap_html( get_the_title( $post_id ), $post_content );
				$data        .= $this->make_file_part( 'File' . $f, $html );
			} else {
				// It's an attachment.
				$attached_file_path = get_attached_file( $post_id );
				if ( is_wp_error( $attached_file_path ) ) {
					return $attached_file_path;
				}
				//phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$file_content = file_get_contents( $attached_file_path );
				//phpcs:enable
				if ( false === $file_content ) {
					return new WP_Error( 'failed_read_file', 'SS1 Could not read file at path ' . $attached_file_path );
				}
				$data .= $this->make_file_part( 'File' . $f, $file_content );
			}
			// endregion .
			if ( $disable_long_running_threads && time() - $execution_start > 30 ) {
				return new WP_Error( 'thread_time_exceeded', 'Long running threads disabled' );
			}
			if ( ( time() - $start_time ) > 30 ) {
				// Spent >30s on fields. Updating DB so that tasks appear alive...
				require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-queue-manager.php';
				$queue_manager = new Site_Search_One_Queue_Manager();
				$marked_alive  = $queue_manager->keep_tasks_alive( $this->task_ids );
				// if false === $marked_alive the error is ignored as non-fatal, no logging here, logged within the method itself.
				$start_time = time();

			}
			++$f;
		}
		// endregion.
		$data .= '--' . $this->delimiter . '--' . $eol;
		return $data;
	}

	/**
	 * Attempt to get the custom attachment title from the Blocks associated with attachment if present.
	 *
	 * @param int $attachment_id The attachment id.
	 *
	 * @return string|false
	 */
	private function try_get_custom_attachment_title_from_blocks( $attachment_id ) {
		if ( has_post_parent( $attachment_id ) ) {
			$parent_post = get_post_parent( $attachment_id );
			$blocks      = parse_blocks( $parent_post->post_content );
			foreach ( $blocks as $block ) {
				if ( 'core/file' === $block['blockName'] && strval( $block['attrs']['id'] ) === strval( $attachment_id ) ) {
					$inner_html = $block['innerHTML'];

					// SO 61018188.
					$doc = new DOMDocument();
					// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
					@$doc->loadHTML( $inner_html );
					// phpcs:enable
					$elems = $doc->getElementsByTagName( 'object' );
					if ( count( $elems ) === 1 ) {
						$elem       = $elems->item( 0 );
						$label_attr = $elem->getAttribute( 'aria-label' );
						if ( gettype( $label_attr ) === 'string' ) {
							return $label_attr;
						}
					}
				}
			}
		}
		return false;
	}

	/**
	 * Wrap HTML Segment with HTML Document tags/Title
	 *
	 * @param string $title The document title.
	 * @param string $contents The document contents html.
	 *
	 * @return string
	 */
	private function wrap_html( $title, $contents ) {
		return "<!DOCTYPE html><html lang='en-US'><head><title>"
			. $title
			. '</title></head><body>'
			. $contents
			. '</body></html>';
	}

	/**
	 * Create Options Json for Post Upload.
	 *
	 * @param int    $post_id ID of post to get Options for.
	 * @param string $index_uuid SC1 Index UUID. IndexUUID post will be uploaded to.
	 * @param bool   $disable_long_running_threads Whether or not long running threads disabled.
	 * @return string|WP_Error
	 * Options JSON or false on failure.
	 * Failure might be caused by failed attempt to make fields stored or enumerable.
	 */
	private function create_options_json( $post_id, $index_uuid, $disable_long_running_threads = false ) {
		$created_d  = get_the_date( 'Y-m-d\TH:i:s.v\Z', $post_id );
		$modified_d = get_the_modified_date( 'Y-m-d\TH:i:s.v\Z', $post_id );
		$metadata   = $this->get_post_metadata( $post_id, $index_uuid, $disable_long_running_threads );
		if ( is_wp_error( $metadata ) ) {
			return $metadata;
		}
		$options = array(
			'IndexUUID'       => $index_uuid,
			'Metadata'        => $metadata,
			'FileName'        => $this->get_file_name( $post_id ),
			'FileDisplayName' => $this->get_display_name( $post_id ),
		);

		if ( false !== $created_d ) {
			$options['FileCreated'] = $created_d;
		}
		if ( false !== $modified_d ) {
			$options['FileModified'] = $modified_d;
		}

		$res = wp_json_encode( $options, JSON_UNESCAPED_UNICODE );

		$i = 0; // Prevent infinite error recursion.
		while ( false === $res && $i < 2 ) {
			$i++;
			$error = json_last_error();
			if ( JSON_ERROR_UTF8 === $error ) {
				Site_Search_One_Debugging::log( 'SS1-WARNING Post ID ' . $post_id . ' (' . get_the_title( $post_id ) . ') has invalid UTF-8 characters in metadata which will be substituted' );
				$res = wp_json_encode( $options, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR );
			} else {
				return new WP_Error( 'wp_json_encode_error', json_last_error_msg() );
			}
		}
		return $res;
	}

	/**
	 * Get the file name that SC1 Should use when the post is uploaded.
	 *
	 * @param int $post_id ID of the post.
	 * @return string
	 */
	private function get_file_name( $post_id ) {
		$post_type = get_post_type( $post_id );
		if ( false === $post_type ) {
			$post_type = 'unknown';
		}
		$extension = '.html';
		if ( 'attachment' === $post_type ) {
			$extension = $this->get_attachment_extension( $post_id );
		}
		return html_entity_decode( $post_type . '-' . $post_id ) . '.' . $extension;
	}

	/**
	 * Get the file extension that SC1 should use for attachment post id.
	 *
	 * @param int $post_id the attachment post id.
	 * @return false|mixed|string
	 */
	private function get_attachment_extension( $post_id ) {
		$metadata = wp_get_attachment_metadata( $post_id );
		$file     = $metadata['file'];
		$arr      = explode( '.', $file );
		$size     = count( $arr );
		if ( $size > 1 ) {
			$extension = $arr[ $size - 1 ];
			if ( ctype_alnum( $extension ) ) {
				if ( strlen( $extension ) <= 25 ) {
					return $extension;
				} else {
					return substr( $extension, 0, 24 );
				}
			} else {
				// Extension is not alphanumeric.
				$extension = 'invalid';
			}
		} else {
			$extension = 'unknown';
		}
		return $extension;
	}

	/**
	 * Get the display name SC1 should use for Post.
	 *
	 * @param int $post_id the post id.
	 *
	 * @return string
	 */
	private function get_display_name( $post_id ) {
		if ( get_post_type( $post_id ) !== 'attachment' ) {
			return get_the_title( $post_id );
		} else {
			// It's an attachment.
			$custom_name = $this->try_get_custom_attachment_title_from_blocks( $post_id );
			if ( false !== $custom_name && null !== $custom_name && trim( $custom_name ) !== '' ) {
				return $custom_name;
			} else {
				return get_the_title( $post_id );
			}
		}
	}

	/**
	 * Get Metadata as SC1 should store about given post.
	 *
	 * @param int    $post_id the post id.
	 * @param string $index_uuid SC1 Index UUID.
	 * @param false  $disable_long_running_threads Whether long running threads is disabled.
	 * @return array|WP_Error
	 * Metadata error or WP_Error on failure. Failure code may be thread_time_exceeded if long running threads disabled.
	 */
	private function get_post_metadata( $post_id, $index_uuid, $disable_long_running_threads = false ) {
		$execution_start = time();
		require_once plugin_dir_path( __FILE__ ) . 'class-sc1-index-manager.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-sc1-field.php';
		$index_mgr = $this->index_mgr;
		// region Ensure all detected fields are stored fields on SC1.
		$fields = SC1_Field::get_post_fields( $post_id, $this->get_display_name( $post_id ) );
		set_time_limit( 0 );
		$stored = $index_mgr->ensure_fields_stored( $index_uuid, $fields );
		if ( is_wp_error( $stored ) ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Failed to Upload - Could not set stored fields' );
			Site_Search_One_Debugging::log( $stored );
			return $stored;
		}
		if ( $disable_long_running_threads && time() - $execution_start > 30 ) {
			return new WP_Error( 'thread_time_exceeded', 'Long running threads disabled' );
		}
		// endregion
		// region Ensure all Taxonomy names are enumerable fields.
		$post_taxonomies = SC1_Field::get_post_taxonomies( $post_id );
		$stored          = $index_mgr->ensure_fields_stored( $index_uuid, $post_taxonomies );
		if ( is_wp_error( $stored ) ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Cannot upload ' . get_the_title( $post_id ) . ' - Could not mark Taxonomy Fields as stored:' );
			Site_Search_One_Debugging::log( $stored );
			return $stored;
		}
		if ( $disable_long_running_threads && time() - $execution_start > 30 ) {
			return new WP_Error( 'thread_time_exceeded', 'Long running threads disabled' );
		}
		$enumerable = $index_mgr->ensure_fields_enumerable( $index_uuid, $post_taxonomies );
		if ( is_wp_error( $enumerable ) ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Cannot upload ' . get_the_title( $post_id ) . ' - Could not set Enumerable fields:' );
			Site_Search_One_Debugging::log( $enumerable );
			return $enumerable;
		}
		if ( $disable_long_running_threads && time() - $execution_start > 30 ) {
			return new WP_Error( 'thread_time_exceeded', 'Long running threads disabled' );
		}
		// endregion
		// region Build the metadata array from fields and taxonomies.
		$metadata = array();
		foreach ( $fields as $field ) {
			if ( $field->get_field_name() !== false && $field->get_field_value() !== false ) {
				$field_name = $field->get_field_name();
				if ( $field->is_field_multi_choice() || $field->is_field_true_false() ) {
					// Enumerable. Must be indexed with safe name.
					$field_name = $field->get_dts_enum_safe_field_name();
				}
				$metadata[] = array(
					$field_name,
					$field->get_field_value(),
				);
			}
		}
		foreach ( $post_taxonomies as $post_taxonomy ) {
			if ( $post_taxonomy->get_field_name() !== false && $post_taxonomy->get_field_value() !== false ) {
				$metadata[] = array(
					$post_taxonomy->get_dts_enum_safe_field_name(), // Taxonomies are enumerable, must use safe name.
					$post_taxonomy->get_field_value(),
				);
			}
		}
		// endregion.
		return $metadata;
	}

	/**
	 * Make a text part for multipart upload.
	 *
	 * @param string $part_name The part name.
	 * @param string $part_contents The part contents.
	 *
	 * @return string
	 */
	private function make_text_part( $part_name, $part_contents ) {
		$eol = "\r\n";

		return '--' . $this->delimiter . $eol
		. 'Content-Disposition: form-data; name="' . $part_name . '"' . $eol . $eol
		. $part_contents . $eol;
	}

	/**
	 * Make a file part for multipart upload.
	 *
	 * @param string $part_name The part name.
	 * @param string $file_data The file data.
	 *
	 * @return string
	 */
	private function make_file_part( $part_name, $file_data ) {
		$eol = "\r\n";

		return '--' . $this->delimiter . $eol
			. 'Content-Disposition: form-data; name="' . $part_name . '"; filename="' . $part_name . '"' . $eol
			. 'Content-Transfer-Encoding: binary' . $eol . $eol
			. $file_data
			. $eol;
	}

}
