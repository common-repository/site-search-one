<?php
/**
 * Class for parsing Pods fields.
 *
 * @package Site_Search_One
 */

/**
 * Parses Pods Custom Fields
 * https://wordpress.org/plugins/pods/
 * Class Pods_Parser
 */
class Site_Search_One_Pods_Parser {

	/**
	 * Determine if field is a Pods date.
	 *
	 * @param string $field_label field label.
	 *
	 * @return false|string false if not a date, otherwise returns "date" or "datetime"
	 */
	public static function is_field_pods_date( $field_label ) {
		$pods_field = self::is_pods_field( $field_label );
		if ( false !== $pods_field ) {
			$pods_field_type = get_post_meta( $pods_field, 'type', true );
			if ( 'datetime' === $pods_field_type || 'date' === $pods_field_type ) {
				return $pods_field_type;
			}
		}
		return false;
	}

	/**
	 * Check if field is a pods field.
	 *
	 * @param string $field_label the field label.
	 * @return int|false The post id of the pods field, or false if not a pods field.
	 */
	public static function is_pods_field( $field_label ) {
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		// Fields in pods register themselves as 'posts' internally in WordPress.
		global $wpdb;
		$table_name = $wpdb->posts;
		$query      = $wpdb->prepare(
			'SELECT ID FROM ' . $table_name . ' WHERE post_type=%s AND post_name=%s',
			'_pods_field',
			$field_label
		);
		$result     = $wpdb->get_var( $query );
		if ( null === $result ) {
			return false;
		} else {
			return intval( $result );
		}
		//phpcs:enable
	}

	/**
	 * Convert date to SC1 compatible date format.
	 *
	 * @param string $meta_value date value.
	 * @param string $date_or_datetime Whether this is a 'date' or a 'datetime'.
	 * @return string
	 */
	public static function to_sc1_date( $meta_value, $date_or_datetime ) {
		$output_format = 'Y-m-d\TH:i:s.v\Z'; // ISO 8601.
		$input_format  = '';
		switch ( $date_or_datetime ) {
			case 'datetime':
				$input_format = 'Y-m-d H:i:s';
				break;
			default:
				$input_format = 'Y-m-d';
		}
		$datetime = DateTime::createFromFormat( $input_format, $meta_value );
		return $datetime->format( $output_format );
	}
}
