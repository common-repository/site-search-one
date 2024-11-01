<?php
/**
 * Just a class to conditionally log if WP_DEBUG is enabled
 *
 * @package    Site_Search_One
 */

/**
 * Just a class to conditionally log if WP_DEBUG is enabled
 *
 * @Package Site_Search_One
 */
class Site_Search_One_Debugging {
	/**
	 * Log the given string.
	 *
	 * @param mixed $log_item The item to log. May be a string or other.
	 * @param false $always Log regardless of WP_DEBUG.
	 */
	public static function log( $log_item, $always = false ) {
		if ( null === $log_item ) {
			return;
		}
		if ( ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) || $always ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions
			if ( 'string' === gettype( $log_item ) ) {
				error_log( $log_item );
			} else {
				error_log( print_r( $log_item, true ) );
			}
			// phpcs:enable
		}
	}
}
