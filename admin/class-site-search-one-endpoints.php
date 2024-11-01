<?php
/**
 * Class for getting SC1 Endpoints.
 *
 * @package Site_Search_One
 */

/**
 * Class for getting SC1 Endpoints.
 */
class Site_Search_One_Endpoints {

	/**
	 * Determine if the plugin is using the test server.
	 *
	 * @return bool
	 */
	public static function is_using_test_server() {
		if ( defined( 'SITE_SEARCH_ONE_USE_TEST_SERVER' ) ) {
			return SITE_SEARCH_ONE_USE_TEST_SERVER;
		}
		return false;
	}

}
