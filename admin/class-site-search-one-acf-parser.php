<?php
/**
 * Parser for ACF Fields (Advanced Custom Fields)
 *
 * @package Site_Search_One
 */

/* @noinspection PhpMissingReturnTypeInspection */

/**
 * Parser for ACF Fields (Advanced Custom Fields)
 */
class Site_Search_One_ACF_Parser {

	/**
	 * Check if an ACF Field is not published.
	 * Only call this method if is_acf_field returns true on field name. Does not check that the field is an acf-field
	 *
	 * @param string $meta_key The field name.
	 * @return bool true if field is published, else false
	 */
	public static function is_published_field( $meta_key ) {
		global $wpdb;
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		$table_name = $wpdb->posts;
		$query      = $wpdb->prepare(
			'SELECT post_status FROM ' . $table_name . ' WHERE post_type=%s AND post_excerpt=%s',
			'acf-field',
			$meta_key
		);
		$result     = $wpdb->get_var( $query );
		return ( 'publish' === $result );
		//phpcs:enable
	}

	/**
	 * Determine if field is an ACF field.
	 *
	 * @param string $meta_key field key.
	 * @return string|false Display name of ACF field, or false if not an ACF field
	 */
	public static function is_acf_field( $meta_key ) {
		global $wpdb;
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		$table_name = $wpdb->posts;
		$query      = $wpdb->prepare(
			'SELECT post_title FROM ' . $table_name . ' WHERE post_type=%s AND post_excerpt=%s',
			'acf-field',
			$meta_key
		);
		$result     = $wpdb->get_var( $query );
		if ( null === $result ) {
			return false;
		} else {
			return $result;
		}
		//phpcs:enable
	}

	/**
	 * Get the value of an ACF Yes/No field. Returned value depends on if the value is True or False and also the user
	 * specified text for true/false.
	 *
	 * @param string $meta_key The field name.
	 * @param string $meta_value The value.
	 *
	 * @return string
	 */
	public static function get_yes_no( $meta_key, $meta_value ) {
		$field_data = self::get_acf_field_data( $meta_key );
		if ( null === $meta_value ) {
			$meta_value = intval( $field_data['default_value'] );
		}

		$meta_value = intval( $meta_value );
		$text_yes   = 'True';
		$text_no    = 'False';

		if ( intval( $field_data['ui'] ) === 1 ) {
			if ( null !== $field_data['ui_on_text'] ) {
				$text_yes = $field_data['ui_on_text'];
			}
			if ( null !== $field_data['ui_off_text'] ) {
				$text_no = $field_data['ui_off_text'];
			}
		}
		if ( 0 === $meta_value ) {
			return $text_no;
		} else {
			return $text_yes;
		}
	}

	/**
	 * Get data associated with ACF field.
	 *
	 * @param string $meta_key field name.
	 *
	 * @return false|mixed
	 */
	private static function get_acf_field_data( $meta_key ) {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name   = $wpdb->posts;
		$query        = $wpdb->prepare(
			'SELECT post_content FROM ' . $table_name . ' WHERE post_type=%s AND post_excerpt=%s',
			'acf-field',
			$meta_key
		);
		$post_content = $wpdb->get_var( $query );
		if ( null === $post_content ) {
			return false;
		}
		// endregion
		// Unserialize is unavoidable here since ACF plugin is what is storing the data serialized in this fashion.
		return unserialize( $post_content );
		//phpcs:enable
	}

	/**
	 * Retrieve the type of a given ACF field.
	 *
	 * @param string $meta_key ACF field name.
	 *
	 * @return false|mixed
	 */
	public static function get_acf_field_type( $meta_key ) {
		$field_data = self::get_acf_field_data( $meta_key );
		if ( $field_data ) {
			try {
				return $field_data['type'];
			} catch ( Exception $exception ) {
				Site_Search_One_Debugging::log( 'SS1-ERROR Exception checking if field is date:' );
				Site_Search_One_Debugging::log( $exception );
			}
		}
		return false;
	}

	/**
	 * Use when ACF field type is 'image'. Returns a URL to the image attachment.
	 *
	 * @param string $meta_value field name.
	 *
	 * @return false|string
	 */
	public static function get_acf_img_url( $meta_value ) {
		return wp_get_attachment_image_url( intval( $meta_value ) );
	}

	/**
	 * Check if acf field is a date, if it is, returns the return_format (PHP Date format of meta_value)
	 *
	 * @param string $meta_key field name.
	 * @return string|false
	 */
	public static function is_acf_field_a_date( $meta_key ) {
		// region Retrieve acf field data.
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		global $wpdb;
		$table_name   = $wpdb->posts;
		$query        = $wpdb->prepare(
			'SELECT post_content FROM ' . $table_name . ' WHERE post_type=%s AND post_excerpt=%s',
			'acf-field',
			$meta_key
		);
		$post_content = $wpdb->get_var( $query );
		if ( null === $post_content ) {
			return false;
		}
		// endregion
		// Unavoidable unserialize call - ACF Plugin stores data this way, not ours.
		$field_data = unserialize( $post_content );
		try {
			$type = $field_data['type'];
			if ( 'date_picker' === $type ) {
				return 'Ymd';
			}
		} catch ( Exception $exception ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Exception checking if field is date:' );
			Site_Search_One_Debugging::log( $exception );
		}
		return false;
		//phpcs:enable
	}

	/**
	 * Convert ACF Date to format suitable for SC1 Indexer (To allow sort on date field in SERP).
	 *
	 * @param string $meta_value Field value (the date).
	 * @param string $input_format the Input date format.
	 *
	 * @return false|string
	 */
	public static function to_sc1_date( $meta_value, $input_format ) {
		try {
			$output_format = 'Y-m-d\TH:i:s.v\Z'; // ISO 8601.
			$datetime      = DateTime::createFromFormat( $input_format, $meta_value );
			if ( $datetime ) {
				return $datetime->format( $output_format );
			}
		} catch ( Exception $exception ) {
			return false;
		}
		return false;
	}

}
