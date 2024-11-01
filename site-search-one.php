<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              www.electronart.co.uk
 * @since             0.1.0
 * @package           Site_Search_One
 *
 * @wordpress-plugin
 * Plugin Name:       Site Search ONE
 * Plugin URI:        https://sitesearchone.com
 * Description:       WordPress Site Search Powered by dtSearch
 * Version:           2.0.0.3538
 * Author:            ElectronArt Design Ltd
 * Author URI:        https://profiles.wordpress.org/electronart/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       site-search-one
 * Domain Path:       /languages
 */

/* @noinspection PhpIncludeInspection */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SITE_SEARCH_ONE_VERSION', '2.0.0' );

/**
 * Plugin Build Number
 */
define( 'SITE_SEARCH_ONE_BUILD', '3538' );

/**
 * Whether or not the plugin uses the Test Server.
 */
define( 'SITE_SEARCH_ONE_USE_TEST_SERVER', false );

if ( SITE_SEARCH_ONE_USE_TEST_SERVER ) {
	set_transient( 'ss1-endpoint-url', 'https://testapi.searchcloudone.com' );
} else {
	set_transient( 'ss1-endpoint-url', 'https://api.searchcloudone.com' );
}

$globals_installed = get_transient( 'ss1-globals-installed' );
if ( false === $globals_installed ) {
	ss1_db_install_global_settings();
}
$indexes_installed = get_transient( 'ss1-indexes-installed' );
if ( 'v5' !== $indexes_installed ) {
	ss1_db_install_indexes();
}

$site_vars_installed = get_transient( 'ss1-site-vars-installed' );
if ( 'v1' !== $site_vars_installed ) {
	ss1_db_install_site_vars();
}
$tokens_has_sites = get_transient( 'ss1-tokens-has-sites' ); // Does the tokens table have the wp_site_url column?
if ( ! empty( $tokens_has_sites ) ) {
	ss1_db_install_tokens();
}
$sp_table_vers = get_transient( 'ss1-search-pages-installed' );
if ( 'v3' !== $sp_table_vers ) {
	ss1_db_install_searchpages();
}

require_once plugin_dir_path( __FILE__ ) . 'admin/class-site-search-one-debugging.php';

function enqueue_jquery() {
	wp_enqueue_script( 'jquery' );
}

add_action( 'wp_enqueue_scripts', 'enqueue_jquery' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-site-search-one-activator.php
 */
function activate_site_search_one() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-site-search-one-activator.php';
	register_serp_cpt();
	set_transient( 'ss1-active', 'yes', 0 ); // Using 'yes' to get around WordPress being smart with 'true'
	// region Ensure Databases Installed.
	ss1_db_install_searchpages();
	ss1_db_install_indexed_pages();
	ss1_db_install_sync_queue();
	ss1_db_install_uploaded_posts();
	ss1_db_install_tokens();
	ss1_db_install_global_settings();
	ss1_db_install_indexes();
	ss1_db_install_site_vars();
	// endregion
	// region Upgrade: Ensure all search pages have their menus created.
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-site-search-one-search-page.php';
	$search_pages = Site_Search_One_Search_Page::get_all_search_pages();
	foreach ( $search_pages as $search_page ) {
		$search_page->get_search_page_indexing_menu(); // this function creates it if it doesn't exist.
	}
	// endregion.
	if ( true === WP_DEBUG ) {
		Site_Search_One_Debugging::log( 'SS1-INFO Activating SS1 =======================================================================================' );
	}
	Site_Search_One_Activator::activate();
	$analytics_installed = try_require_premium_analytics();
	if ( ! is_wp_error( $analytics_installed ) ) {
		Site_Search_One_Debugging::log( 'SS1-INFO Site Search ONE Premium detected' );
	} else {
		Site_Search_One_Debugging::log( 'SS1-INFO Site Search ONE Premium not installed' );
		Site_Search_One_Debugging::log( $analytics_installed );
	}
	ss1_dump_table_size();

	// Flush rewrite rules - Custom Post Type might give 404 otherwise...
	// https://wordpress.stackexchange.com/questions/202859/custom-post-type-pages-are-not-found .
	//phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
	flush_rewrite_rules( false );
	//phpcs:enable

}


/**
 * Callback function for the [ss1-search-page] ShortCode
 *
 * @param array $attributes ShortCode Attributes.
 *
 * @return false|string
 */
function render_search_page( $attributes ) {
	ob_start();
	include_once plugin_dir_path( __FILE__ ) . 'public/ss1-searchpage-iframe.php';
	return ob_get_clean();
}

/**
 * Callback function for rendering the contents of a search bar iframe widget
 *
 * @param mixed $attributes widget attributes.
 */
function render_widget_search_bar( $attributes ) {
	header( 'Content-Type: text/html; charset=UTF-8' );
	include plugin_dir_path( __FILE__ ) . 'public/site-search-one-searchbar-bs5.php';
	die();
}

/**
 * Callback function for the [ss1-search-bar] ShortCode
 * NOT Used by the Widget. See render_widget_search_bar
 *
 * @param array $attributes Shortcode Attributes.
 */
function render_search_bar( $attributes ) {

	$search_page = $attributes['page'];
	?>
	<div class="ss1-searchbar-wrapper">
		<input class="ss1-searchbar" type="text" data-page="<?php echo esc_attr( $search_page ); ?>" data-url="<?php echo esc_url( get_post_permalink( $search_page ) ); ?>" placeholder="Search...">
	</div>
	<?php

}

/**
 * Called during init, along with at activation
 *
 * @return void
 */
function register_serp_cpt() {
	// Register SERP Custom Post Type.
	$args = array(
		'label'        => 'Site Search One - SERP',
		'description'  => 'Search Engine Results Page',
		'public'       => true,
		'show_in_menu' => false,
	);
	$res  = register_post_type( 'ss1_serp', $args );
	if ( is_wp_error( $res ) ) {
		Site_Search_One_Debugging::log( 'SS1-ERROR Error registering post type:', true );
		Site_Search_One_Debugging::log( $res, true );
	}
	// Register Widget Custom Post Type.
	$args = array(
		'label'        => 'Site Search One - Widget',
		'description'  => 'Optional Search Widget used in Site Search ONE',
		'public'       => true,
		'show_in_menu' => false,
	);
	$res  = register_post_type( 'ss1_widget', $args );
	if ( is_wp_error( $res ) ) {
		Site_Search_One_Debugging::log( 'SS1-ERROR Error registering post type:' );
		Site_Search_One_Debugging::log( $res, true );
	}
	// Register Hitviewer Custom Post Type.
	$args = array(
		'label'        => 'Site Search ONE - Hitviewer',
		'description'  => 'Viewer for hit highlighting documents',
		'public'       => true,
		'show_in_menu' => false,
	);
	$res  = register_post_type( 'ss1_hitviewer', $args );
	if ( is_wp_error( $res ) ) {
		Site_Search_One_Debugging::log( 'SS1-ERROR Error registering post type:', true );
		Site_Search_One_Debugging::log( $res, true );
	}
	// Register PDF Viewer Custom Post Type.
	$args = array(
		'label'        => 'Site Search ONE - PDF Viewer',
		'description'  => 'Viewer for hit highlighting PDF Documents',
		'public'       => true,
		'show_in_menu' => false,
	);
	$res  = register_post_type( 'ss1_pdfviewer', $args );
	if ( is_wp_error( $res ) ) {
		Site_Search_One_Debugging::log( 'SS1-ERROR Error registering post type:', true );
		Site_Search_One_Debugging::log( $res, true );
	}
}

add_shortcode( 'ss1-search-bar', 'render_search_bar' );
add_shortcode( 'ss1-search-page', 'render_search_page' );

add_filter( 'template_include', 'ss1_custom_search_template', 10, 5 );
add_action(
	'widgets_init',
	function() {
		require_once plugin_dir_path( __FILE__ ) . 'admin/class-site-search-one-searchbar-widget.php';
		register_widget( 'Site_Search_One_Searchbar_Widget' );
	}
);

//add_action( 'save_post', 'ss1_save_postdata' );

add_action(
	'wp_print_scripts',
	function() {
		global $wp_scripts;
		$post = get_post();
		if ( $post ) {
			switch ( $post->post_type ) {
				case 'ss1_serp':
					ss1_strip_enqueues_not_starting_with( $wp_scripts->queue, 'site-search-one-serp' );
					break;
				case 'ss1_widget':
					ss1_strip_enqueues_not_starting_with( $wp_scripts->queue, 'site-search-one-widget' );
					break;
				case 'ss1_hitviewer':
					ss1_strip_enqueues_not_starting_with( $wp_scripts->queue, 'site-search-one-hitviewer' );
					break;
				case 'ss1_pdfviewer':
					ss1_strip_enqueues_not_starting_with( $wp_scripts->queue, 'site-search-one-pdfviewer' );
					break;
				default:
					ss1_strip_enqueues_starting_with( $wp_scripts->queue, 'site-search-one' );
			}
		}
	},
	PHP_INT_MAX
);

add_action(
	'wp_print_footer_scripts',
	function() {
		global $wp_scripts;

		$post = get_post();
		if ( $post ) {
			switch ( $post->post_type ) {
				case 'ss1_serp':
					ss1_strip_enqueues_not_starting_with( $wp_scripts->in_footer, 'site-search-one-serp' );
					break;
				case 'ss1_widget':
					ss1_strip_enqueues_not_starting_with( $wp_scripts->in_footer, 'site-search-one-widget' );
					break;
				case 'ss1_hitviewer':
					ss1_strip_enqueues_not_starting_with( $wp_scripts->in_footer, 'site-search-one-hitviewer' );
					break;
				case 'ss1_pdfviewer':
					ss1_strip_enqueues_not_starting_with( $wp_scripts->in_footer, 'site-search-one-pdfviewer' );
					break;
				default:
					ss1_strip_enqueues_starting_with( $wp_scripts->in_footer, 'site-search-one' );
			}
		}
	},
	PHP_INT_MAX
);

add_action(
	'wp_print_styles',
	function() {
		global $wp_styles;
		$post = get_post();
		if ( $post ) {
			switch ( $post->post_type ) {
				case 'ss1_serp':
					ss1_strip_enqueues_not_starting_with( $wp_styles->queue, 'site-search-one-serp' );
					break;
				case 'ss1_widget':
					ss1_strip_enqueues_not_starting_with( $wp_styles->queue, 'site-search-one-widget' );
					break;
				case 'ss1_hitviewer':
					ss1_strip_enqueues_not_starting_with( $wp_styles->queue, 'site-search-one-hitviewer' );
					break;
				case 'ss1_pdfviewer':
					ss1_strip_enqueues_not_starting_with( $wp_styles->queue, 'site-search-one-pdfviewer' );
					break;
				default:
					ss1_strip_enqueues_starting_with( $wp_styles->queue, 'site-search-one' );
			}
		}
	},
	PHP_INT_MAX
);

/**
 * Removes all enqueued items that do not start with prepended string
 *
 * @param array  $queue The script or style queue.
 * @param string $start Eg. 'site-search-one-serp'.
 * @return void
 */
function ss1_strip_enqueues_not_starting_with( &$queue, $start ) {
	$len = count( $queue );
	for ( $i = $len - 1; $i >= 0; -- $i ) {
		$str = $queue[ $i ];
		if ( strpos( $str, $start ) !== 0 ) {
			array_splice( $queue, $i, 1 );
		}
	}
}

/**
 * Removes all enqueued items that DO start with prepended string
 *
 * @param array  $queue The script or style queue.
 * @param string $start Eg. 'site-search-one-serp'.
 *
 * @return void
 */
function ss1_strip_enqueues_starting_with( &$queue, $start ) {
	$len = count( $queue );
	for ( $i = $len - 1; $i >= 0; --$i ) {
		$str = $queue[ $i ];
		if ( strpos( $str, $start ) === 0 ) {
			// Starts with string, strip it.
			array_splice( $queue, $i, 1 );
		}
	}
}

add_action(
	'init',
	function() {
		register_serp_cpt();
	}
);

add_action(
	'rest_api_init',
	function() {
		register_rest_route(
			'ss1_client/v1',
			'/options',
			array(
				'methods'             => 'POST',
				'callback'            => 'ss1_options_receive',
				'permission_callback' => 'ss1_options_permissions',
			)
		);
		register_rest_route(
			'ss1_client/v1',
			'/debug',
			array(
				'methods'             => 'GET',
				'callback'            => 'ss1_debug_options',
				'args'                => ss1_get_debug_url_arguments(),
				'permission_callback' => 'ss1_options_permissions',
			)
		);
		register_rest_route(
			'ss1_client/v1',
			'/tokens',
			array(
				'methods'             => 'GET',
				'callback'            => 'ss1_tokens',
				'permission_callback' => 'ss1_searchpage_permissions',
			)
		);
		register_rest_route(
			'ss1_client/v1',
			'/fields',
			array(
				'methods'             => 'POST',
				'callback'            => 'ss1_list_fields',
				'permission_callback' => 'ss1_options_permissions',
			)
		);
		register_rest_route(
			'ss1_client/v1',
			'/ongoing',
			array(
				'methods'             => 'GET',
				'callback'            => 'ss1_get_ongoing_tasks',
				'permission_callback' => 'ss1_options_permissions',
			)
		);
		register_rest_route(
			'ss1_client/v1',
			'/cronhack',
			array(
				'methods'             => 'GET',
				'callback'            => 'ss1_cron_hack',
				'permission_callback' => 'ss1_searchpage_permissions'
			)
		);
		register_rest_route(
			'ss1_client/v1',
			'/searchpage',
			array(
				'methods'             => 'GET',
				'callback'            => 'ss1_get_searchpage',
				'permission_callback' => 'ss1_searchpage_permissions',
			)
		);
		register_rest_route(
			'ss1_client/v1',
			'/searchbar',
			array(
				'methods'             => 'GET',
				'callback'            => 'render_widget_search_bar',
				'permission_callback' => 'ss1_searchpage_permissions',
			)
		);
		register_rest_route(
			'ss1_client/v1',
			'/search',
			array(
				'methods'             => 'GET',
				'callback'            => 'ss1_search',
				'permission_callback' => 'ss1_searchpage_permissions',
			)
		);
		register_rest_route(
			'ss1_client/v1',
			'/stored_fields',
			array(
				'methods'             => 'GET',
				'callback'            => 'ss1_stored_fields',
				'permission_callback' => 'ss1_searchpage_permissions',
			)
		);
	}
);



/**
 * Generates search page for the /searchpage endpoint.
 */
function ss1_get_searchpage() {
	header( 'Content-Type: text/html' );
	include plugin_dir_path( __FILE__ ) . 'public/ss1-searchpage-bs3.php';
	exit();
}

/**
 * GET Req callback for REST route ss1_client/v1/debug
 *
 * @param WP_REST_Request $data request data.
 */
function ss1_debug_options( WP_REST_Request $data ) {
	$params = $data->get_query_params();
	if ( array_key_exists( 'disableLongRunningThreads', $params ) ) {
		$val = $params['disableLongRunningThreads'];
		if ( 'yes' === $val || 'no' === $val ) {
			Site_Search_One_Debugging::log( 'Disable Long Running Threads: ' . $val, true );
			ss1_set_disable_long_running_threads( $val );
			echo '{"Success":true}';
		} else {
			echo '{"Success":false}';
		}
		exit();
	}
	if ( array_key_exists( 'maximumBatchSizeBulkUploads', $params ) ) {
		$val = $params['maximumBatchSizeBulkUploads'];
		if ( ss1_set_maximum_upload_batch_size( $val ) !== false ) {
			echo '{"Success":true}';
		} else {
			echo '{"Success":false}';
		}
		exit();
	}
	if ( array_key_exists( 'clearQueue', $params ) ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_sync_queue';
		$query      = 'DELETE FROM ' . $table_name . ' WHERE 1=1';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		// phpcs:disable Generic.Commenting.DocComment.MissingShort
		/** @noinspection SqlConstantCondition */
		$delete = $wpdb->query( $query );
		// phpcs:enable
		if ( false === $delete ) {
			echo '{"Success":false}';
		} else {
			echo '{"Success":true}';
		}
		exit();
	}
}

/**
 * Function that defines the acceptable arguments for the /debug rest route.
 *
 * @return array
 */
function ss1_get_debug_url_arguments() {
	$args = array();
	// Here we are registering the schema for the filter argument.
	$args['disableLongRunningThreads'] = array(
		// description should be a human readable description of the argument.
		'description' => esc_html__( 'disableLongRunningThreads - Debug Option causes only one sync upload task to be carried out per cron', 'site-search-one' ),
		// type specifies the type of data that the argument should be.
		'type'        => 'string',
		// enum specified what values filter can take on.
		'enum'        => array( 'yes', 'no' ),
	);
	return $args;
}
/**
 * Debug function. Dumps out the table size
 *
 * @param bool $always Always dump regardless of WP_DEBUG.
 */
function ss1_dump_table_size( $always = false ) {
	$sql = 'SELECT table_schema AS "Database", SUM(data_length + index_length) / 1024 / 1024 AS "Size (MB)" FROM information_schema.TABLES GROUP BY table_schema';
	global $wpdb;
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
	$results = $wpdb->get_results( $sql );
	// phpcs:enable
	Site_Search_One_Debugging::log( 'SS1-DEBUG Table Size Info:', $always );
	foreach ( $results as $result ) {
		Site_Search_One_Debugging::log( $result, $always );
	}
}

/**
 * Set whether long running threads are disabled.
 *
 * @param string $on string value 'yes' or 'no'.
 *
 * @return bool|int
 */
function ss1_set_disable_long_running_threads( $on ) {
	set_transient( 'ss1-disableLongRunningThreads', $on ); // Avoid hitting DB in future.
	global $wpdb;
	$table_name = $wpdb->prefix . 'ss1_globals';
	// region First check if api key is already set in db.
	// If this function has been called, a transient has not yet been set, so database calls are necessary.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
	$query = $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE `setting` = %s', 'ss1-disableLongRunningThreads' );
	$count = $wpdb->get_var( $query );

	if ( null === $count ) {
		Site_Search_One_Debugging::log( 'SS1-ERROR Failed to check if ss1-apiKey is set in DB - Query returned null' );
	}
	// endregion.
	if ( intval( $count ) === 0 ) {
		return $wpdb->insert(
			$table_name,
			array(
				'setting' => 'ss1-disableLongRunningThreads',
				'value'   => $on,
			)
		);
	} else {
		// Updating existing setting.
		$result = $wpdb->update(
			$table_name,
			array(
				'value' => $on,
			),
			array(
				'setting' => 'ss1-disableLongRunningThreads',
			),
			'%s',
			'%s'
		);
		if ( false !== $result ) {
			return 1;
		}
		return false;
	}
	// phpcs:enable
}

/**
 * Set maximum upload batch size.
 *
 * @param string $max_size Maximum size.
 *
 * @return bool|int
 */
function ss1_set_maximum_upload_batch_size( $max_size ) {
	set_transient( 'ss1-maximumBatchSize', $max_size ); // Avoid hitting DB in future.
	global $wpdb;
	$table_name = $wpdb->prefix . 'ss1_globals';
	// region First check if api key is already set in db.
	// If this function has been called, a transient has not yet been set, so database calls are necessary.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
	$query = $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $table_name . ' WHERE `setting` = %s', 'ss1-maximumBatchSize' );
	$count = $wpdb->get_var( $query );

	if ( null === $count ) {
		Site_Search_One_Debugging::log( 'SS1-ERROR Failed to check if ss1-maximumBatchSize is set in DB - Query returned null' );
	}
	// endregion.
	if ( intval( $count ) === 0 ) {
		return $wpdb->insert(
			$table_name,
			array(
				'setting' => 'ss1-maximumBatchSize',
				'value'   => $max_size,
			)
		);
	} else {
		// Updating existing setting.
		$result = $wpdb->update(
			$table_name,
			array(
				'value' => $max_size,
			),
			array(
				'setting' => 'ss1-maximumBatchSize',
			),
			'%s',
			'%s'
		);
		if ( false !== $result ) {
			return 1;
		}
		return false;
	}
	// phpcs:enable
}




/**
 * Workaround for dysfunctional cron on some installs...
 * Calling this function causes the thread to be dedicated to cron task, user abort ignored,
 * execution time limit removed. Ongoing request immediately ended, unless long running threads
 * is disabled.
 */
function ss1_cron_hack( $data ) {
	$params = $data->get_query_params();
	if ( array_key_exists( 'cronNumber', $params ) ) {
		$cron_number = intval( $params['cronNumber'] );
	} else {
		$cron_number = 0;
	}
	// StackOverflow 15273570.
	ignore_user_abort( true );
	set_time_limit( 0 );
	require_once 'admin/class-sc1-index-manager.php';
	if ( SC1_Index_Manager::ss1_is_long_running_threads_disabled() !== 'yes' ) {
		end_request( false, 'Long running threads are not disabled.' );
	}
	ss1_cron_exec(0, false, $cron_number);
	exit;
}

/**
 * Send response to client to complete the request. Keeps the thread.
 *
 * @param bool $want_another_thread When true, indicates to client that it should send another http request. Not guaranteed.
 */
function end_request( $want_another_thread = false , $details = 'No details given') {
	error_reporting( 0 ); // Suppress header warning
	// region Send response so client doesn't keep req open
	// Buffer all upcoming output...
	ob_start();
	$response = array(
		'want_another_thread' => $want_another_thread,
		'details'             => $details,
	);
	echo wp_json_encode( $response );
	// Get the size of the output.
	$size = ob_get_length();
	// Disable compression (in case content length is compressed).
	header( 'Content-Encoding: none' );
	// Set the content length of the response.
	header( "Content-Length: {$size}" );
	// Close the connection.
	header( 'Connection: close' );
	// Flush all output.
	@ob_end_flush();
	@ob_flush();
	flush();
	if ( function_exists( 'fastcgi_finish_request' ) ) {
		fastcgi_finish_request();// required for PHP-FPM (PHP > 5.3.3).
	}
	// endregion.
	error_reporting( E_ERROR | E_WARNING | E_PARSE ); // Restore warnings.
}

/**
 * Determine if the plugin is currently active.
 *
 * @param string $plugin the plugin.
 *
 * @return bool
 */
function ss1_plugin_active( $plugin ) {
	return in_array( $plugin, (array) get_option( 'active_plugins', array() ), true );
}


function ss1_percent_load_avg(){
	try {
		$cpu_count = 1;
		if ( is_file( '/proc/cpuinfo' ) ) {
			$cpuinfo = file_get_contents( '/proc/cpuinfo' );
			preg_match_all( '/^processor/m', $cpuinfo, $matches );
			$cpu_count = count( $matches[0] );
		}

		if ($cpu_count < 1) {
			usleep( 50 );
			return false;
		}

		if ( function_exists( 'sys_getloadavg' ) ) {
			$sys_getloadavg    = sys_getloadavg();
			$sys_getloadavg[0] = $sys_getloadavg[0] / $cpu_count;
			$sys_getloadavg[1] = $sys_getloadavg[1] / $cpu_count;
			$sys_getloadavg[2] = $sys_getloadavg[2] / $cpu_count;

			return $sys_getloadavg;
		} else {
			usleep( 50 ); // Fall back to just a very small sleep regardless of processor usage on unsupported systems.

			return false;
		}
	} catch (Exception $e) {
		usleep(50);
		return false;
	}

}

/**
 * Execute cron task. This is the background task that carries out sync etc.
 *
 * @param int   $retries the number of times this cron has been retried if any.
 * @param false $cron_id a unique id identifying this task, if one has been created already.
 */
function ss1_cron_exec( $retries = 0, $cron_id = false, $cron_number = 0 ) {
	if ( $retries > 3 ) {
		Site_Search_One_Debugging::log( 'SS1-ERROR Retry limit exceeded. Slaying this thread' );
		return;
	}

	try {
		require_once 'admin/class-sc1-index-manager.php';
		$disable_long_running_threads = ( SC1_Index_Manager::ss1_is_long_running_threads_disabled() === 'yes' );
		if ( 0 === intval( $cron_number ) || 0 === intval( $cron_number ) % 5 ) { // Every 5 requests.
			require_once 'admin/class-site-search-one-tokens.php';
			$tokens_left = Site_Search_One_Tokens::get_num_tokens_left();
			if ( $tokens_left < 20 ) {
				set_time_limit( 0 );
				$result = Site_Search_One_Tokens::request_more_tokens();
				if ( true !== $result ) {

					Site_Search_One_Debugging::log( 'SS1-ERROR Failed to retrieve more tokens:', $retries > 2 );
					Site_Search_One_Debugging::log( $result, $retries > 2 );
				}
				if ( $disable_long_running_threads ) {
					end_request( true, 'Used thread to fetch more tokens this time' );
					return;
				}
			}
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data    = get_plugin_data( __FILE__ );
		$plugin_version = $plugin_data['Version'];

		require_once 'admin/class-site-search-one-queue-manager.php';



		$maximum_batch_size           = SC1_Index_Manager::ss1_get_maximum_batch_size_for_bulk_uploads();
		$queue_manager                = new Site_Search_One_Queue_Manager();
		if ( ! $queue_manager->is_cron_wanted( $disable_long_running_threads ) ) {
			// Avoid executing cron when cron wasn't wanted.
			if ( $disable_long_running_threads ) {
				end_request( false, 'Because is_cron_wanted returned false' );
			}
			return;
		}
		require_once 'admin/class-site-search-one-post.php';
		require_once 'admin/class-site-search-one-search-page.php';
		require_once 'admin/class-site-search-one-bulk-upload.php';
		// region 1. Use the Queue Manager to get the next one or more tasks, run them.
		$index_manager = new SC1_Index_Manager();
		$queue_manager->mark_cron_run();
		$loop_exit_reason = '(1)';
		if ( $queue_manager->get_is_already_running() !== true ) {
			$loop_exit_reason = '(2)';
			if ( $queue_manager->is_queue_paused() === false ) {
				$task = $queue_manager->get_next_queued_task();
				$loop_exit_reason = '(3)';
				if ( false !== $task ) {

					// region 2. While there are more tasks, carry them out.
					if ( false === $cron_id ) {
						$cron_id = '{' . random_int( 0, 100 ) . '}';
					}

					do {
						// region Slow down if the server gets busy.
						$load = ss1_percent_load_avg();
						if ( $load ) {
							if ( $load[0] > 0.6 ) {
								usleep( 5000 ); // yield 0.005 seconds
							}
							if ( $load[0] > 1.0 ) {
								usleep( 5000 ); // yield a further 0.005 seconds
							}
						}
						// endregion.

						$path = basename( dirname( __FILE__ ) ) . '/site-search-one.php';
						if ( ! ss1_plugin_active( $path ) ) {
							$loop_exit_reason = '(4)';
							Site_Search_One_Debugging::log( $cron_id . 'SS1-INFO Detected that the plugin is no longer active, slaying this thread' );
							return;
						}
						// region Check if the plugin version has since changed. If it has, stop the loop.
						$vers = $plugin_data['Version'];
						if ( $vers !== $plugin_version ) {
							$loop_exit_reason = '(5)';
							Site_Search_One_Debugging::log( $cron_id . 'SS1-INFO Plugin version change detected. Stopping sync loop.' );
							return;
						}
						// endregion.
						set_time_limit( 0 );
						// Condition is not actually always true. We're inside a do while loop. $task changes.
						if ( false !== $task ) {
							$loop_exit_reason = '(6)';
							Site_Search_One_Debugging::log( $cron_id . 'SS1-INFO Next task:' );
							Site_Search_One_Debugging::log( $task );
							$task_id = $task['task_id'];
							$action  = $task['action'];
							$post_id = $task['post_id'];
							get_index_from_post_id( $post_id ); // Ignore result - Just ensures that IndexID is cached.
							$index_uuid = $task['index_uuid'];
							$checkout   = $queue_manager->checkout_task( $task_id );

							if ( is_wp_error( $checkout ) ) {
								$loop_exit_reason = '(7)';
								ss1_cron_exec( $retries, $cron_id );
								return;
							}
							if ( false === $checkout ) {
								$loop_exit_reason = '(8)';
								sleep( 3 );
								ss1_cron_exec( $retries, $cron_id );

								return;
							}
							if ( intval( $task_id ) % 1024 === 0 ) {
								$loop_exit_reason = '(9)';
								Site_Search_One_Debugging::log( $cron_id . 'SS1-INFO Halting briefly to allow indexer to catch up...' );
								sleep( 10 );
								Site_Search_One_Debugging::log( $cron_id . 'SS1-INFO Resuming...' );
							}
							set_time_limit( 0 );
							$ss1_post = new Site_Search_One_Post( $post_id );
							//phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
							switch ( intval( $action ) ) {
								case Site_Search_One_Queue_Manager::ACTION_UPLOAD_POST:
								case Site_Search_One_Queue_Manager::ACTION_UPLOAD_ATTACHMENT:
									$loop_exit_reason = '(10)';
									Site_Search_One_Debugging::log( $cron_id . 'SS1-INFO Next task will be a bulk upload...' );
									$bulk_upload = new Site_Search_One_Bulk_Upload( $index_manager );
									$tasks       = array();
									array_push( $tasks, $task );
									$f                  = 1;
									$bulk_prepare_start = time();
									do {

										set_time_limit( 0 );
										// region Workaround in case bug causes post_id to be null or empty..
										if ( null === $post_id || '' === $post_id ) {
											Site_Search_One_Debugging::log( $cron_id . 'SS1-ERROR Sanity Check failed - Post id was null or empty taskid ' . $task_id );
											$queue_manager->mark_task_complete( $task_id );
											$loop_exit_reason = '(11)';
											break 2;
										}
										// endregion
										// region If the post is already uploaded to this index, first delete the uploaded version.
										$indexes_already_uploaded_to = $ss1_post->get_indexes_uploaded_to();
										foreach ( $indexes_already_uploaded_to as $uploaded_index_uuid ) {
											if ( $index_uuid === $uploaded_index_uuid ) {
												// Already uploaded to this index. First delete from this index.
												$deleted = $index_manager->delete_post_from_index( $post_id, $index_uuid );
												if ( false === $deleted ) {
													Site_Search_One_Debugging::log( $cron_id . 'SS1-ERROR Failed to delete post ' . $post_id . ' from index..' );
													$queue_manager->mark_task_failed( $task_id );
													sleep( 3 * $retries );
													ss1_cron_exec( $retries + 1, $cron_id );
													$loop_exit_reason = '(12)';
													return;
												}
												$ss1_post->mark_as_removed_from_index( $index_uuid );
											}
										}
										// endregion
										// region Check if the post with id still exists... if it has since been deleted, remove it from the queue.
										if ( get_post_status( $post_id ) !== 'publish' ) {
											Site_Search_One_Debugging::log( 'SS1-INFO Post ID ' . $post_id . ' was queued for upload but is no longer published or has since been deleted.' );
											$queue_manager->mark_task_complete( $task_id );
											$loop_exit_reason = '(13)';
											break 2;
										}
										// endregion
										// region Check that the index_uuid hasn't since been deleted..
										$search_page = Site_Search_One_Search_Page::search_page_with_index( $index_uuid );
										if ( false === $search_page ) {
											Site_Search_One_Debugging::log( 'SS1-INFO Search page with Index ' . $index_uuid . ' not found. Skipping upload' );
											$queue_manager->mark_task_complete( $task_id );
											$loop_exit_reason = '(14)';
											break 2;
										}
										// endregion
										// All Sanity Checks have passed. Now this can be added to the bulk uploader.
										$bulk_upload->add_post( $post_id, $index_uuid, $task_id );
										// region Check if the next task is an upload, if it is, add that next to the bulk uploader.
										$next_upload         = false;
										$elapsed_seconds     = time() - $bulk_prepare_start;
										$max_time_processing = $disable_long_running_threads ? 5 : 30;
										if ( $f < $maximum_batch_size && $elapsed_seconds < $max_time_processing ) {
											$next_upload = $queue_manager->is_next_task_id_an_upload_and_checks_out( $task_id );
											if ( false !== $next_upload ) {
												$task_id = $next_upload['task_id'];
												$post_id = $next_upload['post_id'];
												get_index_from_post_id( $post_id ); // Ignore result - Just ensures that IndexID is cached.
												$index_uuid = $next_upload['index_uuid'];
												array_push( $tasks, $next_upload );
											}
										}
										// endregion.
										++ $f;
									} while ( false !== $next_upload );
									// Run out of files to bulk upload...
									set_time_limit( 0 );
									Site_Search_One_Debugging::log( $cron_id . 'SS1-INFO Found ' . count( $tasks ) . ' to upload...' );
									$uploaded = $bulk_upload->perform_upload( $cron_id );
									if ( false !== $uploaded ) {
										$loop_exit_reason = '(15)';
										// Uploaded successfully.
										Site_Search_One_Debugging::log( $uploaded );
										$file_uuids = $uploaded->FileUUIDs;
										if ( count( $file_uuids ) !== count( $tasks ) ) {
											Site_Search_One_Debugging::log(
												$cron_id . 'SS1-ERROR Sanity Check Fail!'
													. 'Number of FileUUIDs(' . count( $file_uuids ) . ') returned does not match number of uploaded posts! (' . count( $tasks ) . ')'
											);
										}
										Site_Search_One_Debugging::log( $cron_id . 'SS1-DEBUG Bulk Upload Performed Successfully.' );
										$f        = 0;
										$task_ids = array();
										$count_f  = count( $file_uuids );
										while ( $f < $count_f ) {
											$file_uuid = $file_uuids[ $f ];

											$task       = $tasks[ $f ];
											$task_id    = $task['task_id'];
											$post_id    = $task['post_id'];
											$index_uuid = $task['index_uuid'];
											array_push( $task_ids, intval( $task_id ) );
											$sc1_post = new Site_Search_One_Post( $post_id );
											$sc1_post->mark_as_uploaded_to_index( $index_uuid, $file_uuid, 1 );
											++ $f;
										}
										$queue_manager->mark_tasks_complete( $task_ids );
										Site_Search_One_Debugging::log( $cron_id . 'SS1-DEBUG Bulk uploaded tasks marked complete' );
										$loop_exit_reason = '(16)';
									} else {
										// Failed to upload.
										Site_Search_One_Debugging::log( $cron_id . 'SS1-ERROR Bulk Upload Failed. Server Response:-' );
										Site_Search_One_Debugging::log( $uploaded );
										$count_tasks = count( $tasks );
										while ( $f < $count_tasks ) {
											$task    = $tasks[ $f ];
											$task_id = $task['task_id'];
											$queue_manager->mark_task_failed( $task_id );
											++$f;
										}
										$loop_exit_reason = '(17)';
									}
									break;
								case Site_Search_One_Queue_Manager::ACTION_DELETE_POST:
									if ( null === $post_id || '' === $post_id ) {
										// Workaround in case bug causes post_id to be null or empty..
										Site_Search_One_Debugging::log( $cron_id . 'SS1-ERROR Sanity Check failed - Post id was null or empty taskid ' . $task_id );
										$queue_manager->mark_task_complete( $task_id );
										$loop_exit_reason = '(18)';
										break;
									}
									$search_page = Site_Search_One_Search_Page::search_page_with_index( $index_uuid );
									if ( false === $search_page ) {
										$queue_manager->mark_task_complete( $task_id );
										$loop_exit_reason = '(19)';
										break;
									}
									$deleted = $index_manager->delete_post_from_index( $post_id, $index_uuid );
									if ( false === $deleted ) {
										$queue_manager->mark_task_failed( $task_id );
										sleep( 3 * $retries );
										ss1_cron_exec( $retries + 1, $cron_id );
										$loop_exit_reason = '(20)';
										return;
									} else {
										$ss1_post->mark_as_removed_from_index( $index_uuid );
										$queue_manager->mark_task_complete( $task_id );
										$retries = 0;
										$loop_exit_reason = '(21)';
									}
									break;
								case Site_Search_One_Queue_Manager::ACTION_SYNC_PAGE:
									if ( null === $post_id || '' === $post_id ) {
										// Workaround in case bug causes post_id to be null or empty..
										Site_Search_One_Debugging::log( $cron_id . 'SS1-ERROR Sanity Check failed - Post id was null or empty taskid ' . $task_id );
										$queue_manager->mark_task_complete( $task_id );
										$loop_exit_reason = '(22)';
										break;
									}
									// post_id is actually the page_id value in the table in this case.
									$page_id = $post_id;
									$res     = $queue_manager->perform_page_sync( $page_id, $task_id, intval( $index_uuid ), $disable_long_running_threads );
									if ( ! is_wp_error( $res ) ) {
										$queue_manager->mark_task_complete( $task_id );
										Site_Search_One_Debugging::log( 'SS1-INFO - Scan Complete' );
										$loop_exit_reason = '(23)';
									} else {
										$queue_manager->mark_task_failed( $task_id );
										$loop_exit_reason = '(24)';
									}
									$retries = 0;
									break;
								case Site_Search_One_Queue_Manager::ACTION_CACHE_SPEC:
									// We want to ensure that the field metadata specification is cached locally so the search page
									// has this data ready and does not need to load it.
									$result = $index_manager->ensure_spec_cached_to_db();
									if ( true === $result ) {
										// Success.
										Site_Search_One_Debugging::log( 'SS1-INFO Cache Spec Task Completed Successfully' );
										$queue_manager->mark_task_complete( $task_id );
										$loop_exit_reason = '(25)';
									}
									if ( is_wp_error( $result ) ) {
										Site_Search_One_Debugging::log( 'SS1-ERROR Failed Cache Spec Task:' );
										Site_Search_One_Debugging::log( $result );
										// In the case of failure, rather than immediately retry, schedule this again at the
										// back of the queue as we do not depend on order of execution and do not want this
										// to hold up the queue.
										$queue_manager->mark_task_complete( $task_id );
										$queue_manager->enqueue_cache_spec(); // Try again later.
										$loop_exit_reason = '(26)';
									}
									if ( false === $result ) {
										$queue_manager->mark_task_complete( $task_id );
										$queue_manager->enqueue_cache_spec(); // Try again later.
										$loop_exit_reason = '(27)';
									}
									break;
								default:
									Site_Search_One_Debugging::log( 'SS1-ERROR Unrecognised Task Action!' );
									$loop_exit_reason = '(28)';
									break;
							}
							//phpcs:enable
						} else {
							$loop_exit_reason = '(29)';
							Site_Search_One_Debugging::log( $cron_id . 'SS1-INFO No more tasks' );
						}
						$task = false;
						if ( true === $disable_long_running_threads ) {
							// When disable long running threads is true, we send a response AFTER a task
							// has been completed.
							end_request( true, 'Task completed but there are more tasks ' . $loop_exit_reason );

							return;
						}



						if ( $queue_manager->is_queue_paused() === false
							&& SC1_Index_Manager::ss1_is_long_running_threads_disabled() !== 'yes'
							&& $queue_manager->get_is_already_running() !== true
						) {
							$task = $queue_manager->get_next_queued_task( true );
						}
					} while ( false !== $task );
					Site_Search_One_Debugging::log( $cron_id . ' SS1-DEBUG Cron Task Loop Finished ' . $loop_exit_reason );
					// endregion.
				}
			}
		}
		// endregion.
		$index_manager->ensure_select_values_cached();
		$index_manager->ensure_xfirstword_cached();

		// region If Premium Analytics installed, call it now.
		if ( ! is_wp_error( try_require_premium_analytics() ) ) {
			$res = Site_Search_One_Search_Analytics::ensure_logs_up_to_date();
			if ( true !== $res ) {
				if ( is_wp_error( $res ) ) {
					Site_Search_One_Debugging::log( 'SS1-ERROR An error occurred whilst ensuring logs up to date:' );
					Site_Search_One_Debugging::log( true );
				} else {
					Site_Search_One_Debugging::log( 'SS1-ERROR Unexpected return value from ensure_logs_up_to_date' );
					Site_Search_One_Debugging::log( $res );
				}
			}
		}
		// endregion.
		if ( true === $disable_long_running_threads ) {
			// If we got this far, there were no more tasks. Or the loop ended early due to an error.
			$num_queued = intval( $queue_manager->get_num_queued_tasks() );
			if ( $num_queued > 0 ) {
				sleep( 5 ); // Prevent a rapid request loop, this is to throttle it.
				end_request( true, 'End of loop, but more tasks are queued ' . $loop_exit_reason );
			} else {
				end_request( false, 'End of loop, and no more tasks are queued' );
			}
			return;
		}
	} catch ( Exception $exception ) {
		Site_Search_One_Debugging::log( 'SS1-ERROR Fatal error handling queue:', true );
		Site_Search_One_Debugging::log( $exception, true );
		sleep( $retries * 3 );
		ss1_cron_exec( $retries + 1, $cron_id );
	}
}

/**
 * Try require premium analytics class from Premium plugin, if plugin installed.
 *
 * @return bool|WP_Error
 */
function try_require_premium_analytics() {
	if ( class_exists( 'Site_Search_One_Premium_Functions' ) ) {
		return true;
	} else {
		$plugins = get_plugins();
		foreach ( $plugins as $plugin_dir => $plugin ) {
			if ( 'Site Search ONE Premium' === $plugin['Name'] ) {
				$install_loc = get_option( 'site-search-one-premium-install-location' );
				if ( false !== $install_loc ) {
					$path = $install_loc . '/admin/class-site-search-one-analytics.php';
					if ( file_exists( $path ) && is_plugin_active( $plugin_dir ) ) {
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
 * Attempt to get the path to premium plugin directory, if installed.
 *
 * @return false|string
 */
function try_get_premium_plugin_dir() {
	$plugins = get_plugins();
	foreach ( $plugins as $plugin_dir => $plugin ) {
		if ( 'Site Search ONE Premium' === $plugin['Name'] ) {
			return get_option( 'site-search-one-premium-install-location' );
		}
	}
	return false;
}


add_action( 'add_meta_boxes', 'ss1_register_metabox' );

/**
 * Register the meta box used by SS1.
 */
function ss1_register_metabox() {
	add_meta_box(
		'ss1-meta-box',
		__( 'Site Search ONE', 'textdomain' ),
		'ss1_metabox_display_callback',
		array( 'post', 'page' ),
		'side'
	);
}

/**
 * Callback for SS1 Metabox.
 *
 * @param WP_Post $post the post this metabox is for.
 */
function ss1_metabox_display_callback( $post ) {
	$post_id = $post->ID;
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-site-search-one-post.php';
	$ss1_post = new Site_Search_One_Post( $post_id );
	$no_index = $ss1_post->get_noindex();
	if ( $no_index ) {
		$no_index = 'checked';
	} else {
		$no_index = '';
	}

	?>
	<label for="ss1-noindex-checkbox">
		<input type="checkbox" id="ss1-noindex-checkbox" name="ss1-noindex" value="ss1-noindex" <?php echo esc_attr( $no_index ); ?>>
		Do not index
	</label>
	<?php
}

/**
 * Hooking post/page saved - Save noindex status
 *
 * @param int $post_id the post id.
 */
function ss1_save_postdata( $post_id ) {
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-site-search-one-post.php';
	$ss1_post = new Site_Search_One_Post( $post_id );
	// Nonce verification removed here due to conflict with SiteOrigin PageBuilder.
	//phpcs:disable WordPress.Security.NonceVerification
	if ( ! isset( $_POST[ 'ss1-noindex-metabox-' . $post_id ] ) ) {
		return;
	}
	if ( array_key_exists( 'ss1-noindex', $_POST ) ) {
		$ss1_post->set_noindex( true );
	} else {
		$ss1_post->set_noindex( false );
	}
	//phpcs:enable
}



/**
 * Callback for options endpoint permissions check.
 *
 * @return bool|WP_Error
 */
function ss1_options_permissions() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return true;
	} else {
		return new WP_Error( 'rest_forbidden', 'You are not authorized to perform this action', array( 'status' => 401 ) );
	}
}

/**
 * Callback for searchpage permissions check. Currently just returns true.
 *
 * @return bool
 */
function ss1_searchpage_permissions() {
	return true;
}

/**
 * REST API Callback for getting more tokens
 */
function ss1_tokens() {
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-site-search-one-tokens.php';
	$token = Site_Search_One_Tokens::issue_token();
	return $token;
}

/**
 * REST Callback for getting ongoing tasks.
 *
 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
 */
function ss1_get_ongoing_tasks() {
	// region 1. Query database for any ongoing upload tasks.
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-site-search-one-queue-manager.php';
	$queue_manager      = new Site_Search_One_Queue_Manager();
	$num_queued_uploads = $queue_manager->get_num_queued_tasks();
	$paused             = $queue_manager->is_queue_paused();
	// endregion
	// region 2. Return results as json.
	$load     = array( -1, -1, -1 );
	$php_load = ss1_percent_load_avg();
	if ( $php_load ) {
		$load = $php_load;
	}
	$response_data = array(
		'num_queued_uploads' => $num_queued_uploads,
		'paused'             => $paused,
		'load'               => $load,
	);
	return rest_ensure_response( $response_data );
	// endregion.
}

/**
 * Callback for receiving options via json api.
 *
 * @param WP_REST_Request $data received data.
 *
 * @return int|void|WP_Error
 * @throws Exception Invalid api key.
 */
function ss1_options_receive( WP_REST_Request $data ) {
	global $wpdb;
	$params = $data->get_json_params();
	if ( array_key_exists( 'new_search_page', $params ) ) {
		// Request is to create a new Search Page.

		// region 1. Get page parameters.
		$new_search_page     = $params['new_search_page'];
		$page_name           = $new_search_page['name']; // name of search page.
		$index_pages         = $new_search_page['index_pages']; // either true or false.
		$index_posts         = $new_search_page['index_posts']; // either true or false.
		$categories          = $new_search_page['categories']; // array of category ids.
		$taxonomies          = $new_search_page['taxonomies']; // array of taxonomy term ids.
		$pages               = $new_search_page['pages'];
		$post_types          = $new_search_page['post_types'];
		$page_categories     = $new_search_page['page_categories']; // array of category ids or null.
		$media_term_ids      = $new_search_page['media_term_ids'];
		$attached_media_only = $new_search_page['attached_media_only'];
		$media_mime_types    = $new_search_page['media_mime_types'];
		// endregion
		// region  2. Create a new WordPress page.
		$new_wp_page = array(
			'post_title'   => $page_name,
			'post_content' => '[ss1-search-page]', // ShortCode handler.
			'post_type'    => 'page',
			'post_status'  => 'draft',
			'post_author'  => 1,
			'meta_input'   => array( '_ss1_noindex' => true ),
		);
		$wp_post_id  = wp_insert_post( $new_wp_page, true );
		if ( is_wp_error( $wp_post_id ) ) {
			// Failed to create post for some reason...
			Site_Search_One_Debugging::log( 'Site Search One: Failed to create new WordPress Post' );
			return $wp_post_id; // WP_Error object.
		}
		// endregion
		// region 3. We used to create the index on SC1 at this point. Not any more, this is done later.
		$site_url = get_site_url();
		// endregion
		// region 4. Attempt to save search page into database.
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( 'START TRANSACTION' );
		$page_categories_csv = null;
		if ( null !== $page_categories ) {
			$page_categories_csv = implode( ',', $page_categories );
		}
		$categories_csv        = implode( ',', $categories );
		$taxonomy_term_ids_csv = implode( ',', $taxonomies );
		$post_types_csv        = null;
		if ( null !== $post_types ) {
			$post_types_csv = implode( ',', $post_types );
		}
		$media_term_ids_csv = null;
		if ( null !== $media_term_ids ) {
			$media_term_ids_csv = implode( ',', $media_term_ids );
		}

		$media_mime_types_csv = null;
		if ( null !== $media_mime_types ) {
			$media_mime_types_csv = implode( ',', $media_mime_types );
		}

		$page_saves =
			$wpdb->insert(
				$wpdb->prefix . 'ss1_search_pages',
				array(
					'ix_pages'            => $index_pages,
					'ix_posts'            => $index_posts,
					'cat_ids'             => $categories_csv,
					'post_id'             => $wp_post_id,
					'sc1_ix_uuid'         => 'disused',
					'tax_term_ids'        => $taxonomy_term_ids_csv,
					'page_cat_ids'        => $page_categories_csv,
					'post_types'          => $post_types_csv,
					'media_term_ids'      => $media_term_ids_csv,
					'attached_media_only' => $attached_media_only,
					'media_mime_types'    => $media_mime_types_csv,
				)
			);
		if ( ! $page_saves ) {
			$errormsg = 'Last Error Msg:' . $wpdb->last_error;
			$errormsg = $errormsg . '\nLast Error Statement:' . $wpdb->last_query;
			$wpdb->query( 'ROLLBACK' );

			return new WP_Error( 'save_page_fail', "Error saving search page data\n" . $errormsg );
		}
		$wpdb->query( 'COMMIT' );
		//phpcs:enable
		require_once 'admin/class-site-search-one-search-page.php';
		$search_page = Site_Search_One_Search_Page::get_search_page( $wp_post_id ); // This will cause the index for this site to be created on SC1.
		$search_page->set_special_pages( $pages );
		$search_page->get_search_page_indexing_menu(); // Ensure the search page has an indexing menu
		// endregion
		// We no longer enqueue page sync here, as the page sync will automatically get enqueued when the
		// Index is created on SC1. The index will automatically be created when the Plugin's cron job attempts
		// To retrieve the index uuid for the search page and realises the index does not index for this site
		// / search page combo.
		// endregion
		// Set post status to published.
		wp_update_post(
			array(
				'ID'          => $wp_post_id,
				'post_status' => 'publish',
			)
		);

		// Successfully updated database, respond with 200 OK.
		wp_send_json_success(); // Respond with 200 OK.
		exit;
		// endregion .
	}
	if ( array_key_exists( 'edit_search_page', $params ) ) {
		$edit_search_page    = $params['edit_search_page'];
		$post_id             = $edit_search_page['post_id'];
		$page_name           = $edit_search_page['name']; // name of search page.
		$index_pages         = $edit_search_page['index_pages']; // either true or false.
		$index_posts         = $edit_search_page['index_posts']; // either true or false.
		$categories          = $edit_search_page['categories']; // array of category ids.
		$taxonomies          = $edit_search_page['taxonomies']; // array of taxonomy term ids.
		$pages               = $edit_search_page['pages'];
		$post_types          = $edit_search_page['post_types'];
		$page_categories     = $edit_search_page['page_categories']; // array of category ids or null.
		$ix_media            = $edit_search_page['ix_media'];
		$media_term_ids      = $edit_search_page['media_term_ids'];
		$attached_media_only = $edit_search_page['attached_media_only'];
		$media_mime_types    = $edit_search_page['media_mime_types'];
		require_once 'admin/class-site-search-one-search-page.php';
		$search_page = Site_Search_One_Search_Page::get_search_page( $post_id );
		$result      = $search_page->edit_indexing_options(
			$index_pages,
			$index_posts,
			$categories,
			array(),
			$taxonomies,
			$post_types,
			$page_categories,
			$media_term_ids,
			$attached_media_only,
			$media_mime_types
		);
		if ( false === $result ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Unexpected rows affected editing search page indexing options ' . $result );
			wp_send_json_error( 'Internal Error', 500 );
			exit;
		}
		if ( 0 === $result ) {
			Site_Search_One_Debugging::log( 'SS1-WARNING 0 Rows affected editing search page indexing options for search page ' . $post_id );
		}
		// region Set special case pages.
		$cleared = $search_page->clear_special_pages();
		if ( false === $cleared ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Unexpected error clearing special case pages' );
			wp_send_json_error( 'Internal Error', 500 );
			exit;
		}
		$set = $search_page->set_special_pages( $pages );
		if ( false === $set ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Unexpected error setting special case pages' );
			wp_send_json_error( 'Internal Error', 500 );
			exit;
		}
		// endregion
		// Update post name.
		wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => $page_name,
			)
		);
		wp_send_json_success(); // Respond with 200 OK.
		exit;
	}
	if ( array_key_exists( 'apiKey', $params ) ) {
		// Request is to set the API Key this plugin should be using.
		$api_key = $params['apiKey'];
		if ( sc1_is_api_key_valid( $api_key ) ) {
			// API Key is valid, so we save it and send a success response to let the UI proceed to the user's configuration page.
			require_once 'admin/class-sc1-index-manager.php';
			if ( SC1_Index_Manager::set_sc1_api_key( $api_key ) ) {
				wp_send_json_success();
				return;
			} else {
				header( 'HTTP/1.1 500 Internal Error' );
				$response          = new stdClass();
				$response->success = false;
				$response->message = 'Failed to set API Key';
				echo wp_json_encode( $response );
				exit;
			}
		} else {
			// apiKey is not valid for one reason or another.
			header( 'HTTP/1.1 500 Internal Error' );
			$response          = new stdClass();
			$response->success = false;
			$response->message = 'API Key Invalid';
			echo wp_json_encode( $response );
			exit;
		}
	}
	if ( array_key_exists( 'resetPlugin', $params ) ) {
		require_once 'admin/class-site-search-one-search-page.php';
		$search_pages = Site_Search_One_Search_Page::get_all_search_pages();
		$index_uuids  = array();
		foreach ( $search_pages as $search_page ) {
			$index_uuid = $search_page->get_sc1_ix_uuid();
			if ( ! is_wp_error( $index_uuid ) ) {
				array_push( $index_uuids, $index_uuid );
			}
		}
		require_once 'admin/class-sc1-index-manager.php';
		$ix_mgr = new SC1_Index_Manager();
		$ix_mgr->delete_sc1_indexes( $index_uuids );

		$tables = array(
			$wpdb->prefix . 'ss1_search_pages',
			$wpdb->prefix . 'ss1_tokens',
			$wpdb->prefix . 'ss1_ix_pages',
			$wpdb->prefix . 'ss1_sync_queue',
			$wpdb->prefix . 'ss1_uploaded_posts',
			$wpdb->prefix . 'ss1_globals',
			$wpdb->prefix . 'ss1_sc1_indexes',
		);
		// Request is to reset the plugin.

		foreach ( $tables as $table ) {
			//phpcs:disable WordPress.DB.DirectDatabaseQuery
			//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "DELETE FROM $table WHERE 1=1" );
			//phpcs:enable
		}

		delete_transient( 'ss1-apiKey' );
		wp_send_json_success();
		return;
	}
	if ( array_key_exists( 'delete_ext_ix', $params ) ) {
		// Request is to delete an index from another site...
		require_once 'admin/class-sc1-index-manager.php';
		$ix_mgr        = new SC1_Index_Manager();
		$delete_ext_ix = $params['delete_ext_ix'];
		$ix_uuid       = $delete_ext_ix['ix_uuid'];
		$res           = $ix_mgr->delete_sc1_index( $ix_uuid );
		if ( ! is_wp_error( $res ) ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( $res );
		}
		return;
	}
	if ( array_key_exists( 'delete_search_page', $params ) ) {
		// Request is to delete a search page...
		// region 1. Validation - Check that the search page passed exists.
		require_once plugin_dir_path( __FILE__ ) . 'admin/class-site-search-one-search-page.php';
		$delete_search_page = $params['delete_search_page'];
		$post_id            = $delete_search_page['post_id'];
		$search_page        = Site_Search_One_Search_Page::get_search_page( $post_id );
		if ( false === $search_page ) {
			// No such search page.
			return new WP_Error( 'sp_delete_fail_no_such_page', 'Could not find page to delete' );
		}
		// endregion
		// region 2. Attempt to delete the Search Page.
		$deleted = $search_page->delete();
		if ( true === $deleted ) {
			wp_send_json_success();
		} else {
			// $deleted is a WP_ERROR object
			wp_send_json_error( $deleted );
		}
		// endregion.

	}
	if ( array_key_exists( 'set_override_default_search', $params ) ) {
		$override = $params['set_override_default_search'];
		$post_id  = intval( $override['post_id'] );
		if ( -1 !== $post_id ) {
			set_transient( 'ss1-searchform-override', $post_id, 0 );
		} else {
			delete_transient( 'ss1-searchform-override' );
		}
		wp_send_json_success();
		return;
	}
	if ( array_key_exists( 'scan_search_page', $params ) ) {
		$scan_search_page = $params['scan_search_page'];
		$page_id          = $scan_search_page['post_id'];
		require_once plugin_dir_path( __FILE__ ) . 'admin/class-site-search-one-search-page.php';
		require_once plugin_dir_path( __FILE__ ) . 'admin/class-site-search-one-queue-manager.php';
		$queue_manager = new Site_Search_One_Queue_Manager();
		$queue_manager->enqueue_page_sync( $page_id );
		Site_Search_One_Debugging::log( 'SS1-INFO Page Sync Enqueued for Search page ' . get_the_title( $page_id ) );
		require_once plugin_dir_path( __FILE__ ) . 'admin/class-site-search-one-search-page.php';
		$search_page = Site_Search_One_Search_Page::get_search_page( $page_id );
		$search_page->invalidate_cached_results(); // Also, since user clicked update, may as well invalidate the results cache.
		return wp_send_json_success();
	}
	if ( array_key_exists( 'recreate_search_page', $params ) ) {
		$recreate_search_page = $params['recreate_search_page'];
		$page_id              = $recreate_search_page['post_id'];
		require_once plugin_dir_path( __FILE__ ) . 'admin/class-site-search-one-search-page.php';
		$search_page = Site_Search_One_Search_Page::get_search_page( $page_id );
		if ( false === $search_page ) {
			wp_send_json_error( 'No such search page' );
			return;
		}
		$recreate_started = $search_page->start_index_recreate();
		if ( is_wp_error( $recreate_started ) ) {
			wp_send_json_error( $recreate_started );
			return;
		}
		Site_Search_One_Debugging::log( 'SS1-INFO Index recreate enqueued for search page ' . get_the_title( $page_id ) );
		return wp_send_json_success();
	}
	if ( array_key_exists( 'set_display_opts', $params ) ) {
		$set_page_options = $params['set_display_opts'];
		$page_id          = $set_page_options['post_id'];
		require_once plugin_dir_path( __FILE__ ) . 'admin/class-site-search-one-search-page.php';
		$search_page = Site_Search_One_Search_Page::get_search_page( $page_id );
		if ( false !== $search_page ) {
			$search_page->set_page_display_options( $set_page_options['opts'] );
			Site_Search_One_Debugging::log( 'SS1-INFO Options updated for page ' . $page_id . ':' );
			Site_Search_One_Debugging::log( $set_page_options['opts'] );
			wp_send_json_success();
		} else {
			$error = new WP_Error( 'no_such_search_page', "Couldn't find search page with id " . $page_id );
			Site_Search_One_Debugging::log( 'Options updated for page ' . $page_id );
			wp_send_json_error( $error );
		}
	}
	if ( array_key_exists( 'save_user_defined_synonyms', $params ) ) {
		try {
			$save_user_defined_synonyms = $params['save_user_defined_synonyms'];
			$synonyms_base64            = $save_user_defined_synonyms['synonyms_base64'];
			$filename                   = uniqid( 'synonyms_' );
			$filepath                   = WP_CONTENT_DIR . '/uploads/site-search-one/' . $filename;
			$dirname                    = dirname( $filepath );
			if ( ! is_dir( $dirname ) ) {
				if ( ! mkdir( $dirname, 0755, true ) ) {
					Site_Search_One_Debugging::log( 'SS1-ERROR Failed to create site-search-one directory ' . $dirname );
					$err = new WP_Error( 'ss1_upload_failure', 'Could not create directory' );
					wp_send_json_error( $err );
					exit;
				}
			}
			$opened_file = fopen( $filepath, 'w' );
			$wrote       = false;
			if ( false !== $opened_file ) {
				$wrote = fwrite( $opened_file, $synonyms_base64 );
				fclose( $opened_file );
			}
			if ( false === $wrote ) {
				// Something went wrong.
				Site_Search_One_Debugging::log( 'SS1-ERROR Error writing file, fwrite returned false.' );
				$err = new WP_Error( 'ss1_upload_failure', 'Failed to write uploaded file to disk' );
				wp_send_json_error( $err );
				exit;
			}
			$response = array(
				'success'  => true,
				'filename' => $filename,
			);
			Site_Search_One_Debugging::log( 'SS1-INFO Wrote Synonyms file successfully' . $filepath );
			echo wp_json_encode( $response );
			exit;
		} catch ( Exception $ex ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Failed to save upload - An exception occurred:' );
			Site_Search_One_Debugging::log( $ex );
			wp_send_json_error( $ex );
		}
	}
	if ( array_key_exists( 'set_queue_paused', $params ) ) {
		$paused = $params['set_queue_paused'];
		// Sanity check - Check a boolean was passed.
		$type = gettype( $paused );
		if ( 'boolean' !== $type ) {
			$error = new WP_Error( 'incorrect_type', 'set_queue_paused must be boolean' );
			wp_send_json_error( $error, 400 );
			exit;
		}
		require_once 'admin/class-site-search-one-queue-manager.php';
		$queue_manager = new Site_Search_One_Queue_Manager();
		$success       = $queue_manager->set_queue_paused( $paused );
		if ( false !== $success ) {
			wp_send_json_success();
		} else {
			$error = new WP_Error( 'db_error', 'Something went wrong' );
			wp_send_json_error( $error, 500 );
		}
		exit;
	}
	if ( array_key_exists( 'set_globals', $params ) ) {
		$globals    = $params['set_globals'];
		$table_name = $wpdb->prefix . 'ss1_globals';
		$query      = "REPLACE INTO $table_name (setting, value) VALUES ";
		$first      = true;
		foreach ( $globals as $key => $value ) {
			if ( ! $first ) {
				$query .= ', ';
			}
			$first  = false;
			$query .= $wpdb->prepare( '(%s,%s)', $key, $value );
		}
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$rows_updated = $wpdb->query( $query );
		if ( false === $rows_updated ) {
			$error = new WP_Error( 'db_error', 'Something went wrong' );
			wp_send_json_error( $error, 500 );
		} else {
			wp_send_json_success();
		}
		exit;
		//phpcs:enable
	}
}





/**
 * Callback for when user wants their search results overriden by SiteSearchOne.
 * Only used when user nominates a SS1 Search Page as the default search results page
 *
 * @param mixed $template the template.
 *
 * @return mixed
 */
function ss1_custom_search_template( $template ) {
	global $wp_query;
	Site_Search_One_Debugging::log( 'SS1-INFO Search Template Running... Post ID ' . get_the_ID() );
	Site_Search_One_Debugging::log( 'SS1-INFO post type ' . get_post_type() );
	$override_search_form = get_transient( 'ss1-searchform-override' ) !== false;
	if ( ! isset( $_GET['s'] ) ) {
		switch ( get_post_type() ) {
			case 'ss1_serp':
				return plugin_dir_path( __FILE__ ) . '/templates/site-search-one-searchpage-bs5.php';
			case 'ss1_widget':
				return plugin_dir_path( __FILE__ ) . '/templates/site-search-one-searchbar-bs5.php';
			case 'ss1_hitviewer':
				return plugin_dir_path( __FILE__ ) . '/templates/site-search-one-hitviewer.php';
			case 'ss1_pdfviewer':
				$plugin_dir_path = try_get_premium_plugin_dir();
				if ( $plugin_dir_path ) {
					return $plugin_dir_path . '/templates/pdf-viewer/web/viewer.php';
				}
				return $template; // This should not happen under typical scenarios.
			default:
				return $template;
		}
	} elseif ( $override_search_form ) {
		// Normally you would return the template html but actually we want it to go to the search page.
		// Instead of returning the template to be shown, redirect the user to the correct page..
		$post_id   = intval( get_transient( 'ss1-searchform-override' ) );
		$permalink = get_permalink( $post_id );
		$search    = get_search_query();
		$permalink = add_query_arg( 'query', wp_unslash( $search ), $permalink );
		header( 'Location: ' . $permalink );
		die();
	}
	return $template;
}

/**
 * Callback for list fields REST request
 *
 * @param WP_REST_Request $request the request.
 * @return string|true|void|WP_Error|null
 */
function ss1_list_fields( WP_REST_Request $request ) {
	try {
		// region 1. Get/Validate Params.
		$params = $request->get_json_params();
		if ( ! array_key_exists( 'page_id', $params ) ) {
			return new WP_Error( 'missing_parameter', 'page_id required' );
		}
		$page_id = $params['page_id'];

		require_once plugin_dir_path( __FILE__ ) . 'admin/class-site-search-one-search-page.php';
		$search_page = Site_Search_One_Search_Page::get_search_page( $page_id );
		if ( false === $search_page ) {
			// No such search page.
			$error = new WP_Error( 'no_such_search_page', 'Could not find Search Page: ' . $page_id );
			return $error;
		}
		require_once plugin_dir_path( __FILE__ ) . 'admin/class-sc1-index-manager.php';
		$api_key    = SC1_Index_Manager::get_sc1_api_key();
		$index_uuid = $search_page->get_sc1_ix_uuid();
		if ( is_wp_error( $index_uuid ) ) {
			return $index_uuid;
		}
		// endregion
		// region 2. Perform req to SC1.
		$body     = array(
			'APIKey'          => $api_key,
			'Action'          => 'ListIndexes',
			'FilterToIndexes' => array( $index_uuid ),
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
		//phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( ! is_wp_error( $request ) ) {
			$response_code = wp_remote_retrieve_response_code( $request );

			if ( $response_code >= 200 && $response_code < 300 ) {
				$retrieve_body = wp_remote_retrieve_body( $request );
				$data          = json_decode( $retrieve_body );
				$indexes       = $data->Indexes;
				// Find the index that matches..
				foreach ( $indexes as $index ) {
					if ( $index->IndexUUID === $index_uuid ) {
						// Found our index.
						$fields_spec = array();
						require_once 'admin/class-site-search-one-queue-manager.php';
						$queue_manager = new Site_Search_One_Queue_Manager();
						if ( $index->HasMetaSpec ) {

							$fields_spec = $index->MetaSpecFields;
						}
						$syncing       = ( $queue_manager->get_num_queued_tasks() > 0 );
						$paused        = $queue_manager->is_queue_paused();
						$response_data = array(
							'Fields'  => $fields_spec,
							'Syncing' => $syncing,
						);
						wp_send_json_success( $response_data );
						return;
					}
				}
				$error = new WP_Error( 'no_such_index', "Couldn't find index corresponding to search page" );
			} else {
				// non 2xx response.
				Site_Search_One_Debugging::log( 'SS1-ERROR Non 2xx Response (' . $response_code . ') listing fields:' );
				$retrieve_body = wp_remote_retrieve_body( $request );
				Site_Search_One_Debugging::log( $retrieve_body );
				$error = new WP_Error( 'non_2xx_response', 'Error listing fields, non 2xx response ' . $response_code );
			}
			wp_send_json_error( $error );
		} else {
			wp_send_json_error( $request );
		}
		// endregion.
		//phpcs:enable
	} catch ( Exception $ex ) {
		Site_Search_One_Debugging::log( 'SS1-ERROR Error serving list fields req:' );
		Site_Search_One_Debugging::log( $ex );
		$error = new WP_Error( 'unhandled_error', 'Something went wrong. Check logs for details' );
		wp_send_json_error( $error );
	}
}

/**
 * REST Route callback for /search
 * Performs Search Request to SearchCloudOne, returns results as requested.
 *
 * @param WP_REST_Request $request the request.
 *
 * @return array|WP_Error
 */
function ss1_search( $request ) {
	try {
		require_once plugin_dir_path( __FILE__ ) . 'admin/class-sc1-index-manager.php';
		$api_key = SC1_Index_Manager::get_sc1_api_key();

		$hit_viewer = false;
		$params     = json_decode( urldecode( urldecode( (string) $request['query'] ) ), true );
		// The request has come from the frontend - we're performing a search.
		$query   = $params['performSearch']['query'];
		$page    = $params['performSearch']['page'];
		$context = $params['performSearch']['context'];
		$sort_by = $params['performSearch']['sortBy'];

		// $indexes = array($index);
		$req_facets                               = $params['performSearch']['reqFacets'];
		$facet_filters                            = $params['performSearch']['filters'];
		$data                                     = new StdClass();
		$data->{'APIKey'}                         = $api_key;
		$data->{'Indexes'}                        = $params['performSearch']['indexes'];
		$data->{'Parameters'}                     = new StdClass();
		$data->{'Parameters'}->{'Query'}          = $query;
		$data->{'Parameters'}->{'Page'}           = $page;
		$data->{'Parameters'}->{'IncludeContext'} = $context;
		$data->{'Parameters'}->{'Stemming'}       = $params['performSearch']['stemming'];
		$data->{'Parameters'}->{'Synonyms'}       = $params['performSearch']['synonyms'];
		$data->{'Parameters'}->{'IncludeFields'}  = array( '_link', 'Link' );
		if ( $facet_filters ) {
			$data->{'Parameters'}->{'Filters'} = $facet_filters;
		}
		if ( array_key_exists( 'flags', $params['performSearch'] ) ) {
			$flags = $params['performSearch']['flags'];
			if ( $flags ) {
				$data->{'Parameters'}->{'Flags'} = $params['performSearch']['flags'];
			}
		}
		if ( 'none' !== $sort_by ) {
			// Requested the results to be sorted.
			$data->{'Parameters'}->{'Sort'} = new StdClass();
			switch ( $sort_by ) {
				case 'a-z':
					$data->{'Parameters'}->{'Sort'}->{'SortBy'}    = 'Title';
					$data->{'Parameters'}->{'Sort'}->{'Ascending'} = true;
					break;
				case 'z-a':
					$data->{'Parameters'}->{'Sort'}->{'SortBy'}    = 'Title';
					$data->{'Parameters'}->{'Sort'}->{'Ascending'} = false;
					break;
			}
		}
		$data->{'Parameters'}->{'GetTopFieldValues'}                    = new StdClass(); // Always retrieve all enumerable fields in SS1.
		$data->{'Parameters'}->{'GetTopFieldValues'}->{'MaxResults'}    = 64;
		$data->{'Parameters'}->{'GetTopFieldValues'}->{'CaseSensitive'} = false;

		if ( isset( $params['performSearch']['hitViewer'] ) ) {

			$hit_viewer                                        = true;
			$data->{'Parameters'}->{'HitViewer'}               = new StdClass();
			$data->{'Parameters'}->{'Version'}                 = 3;
			$data->{'Parameters'}->{'HitViewer'}->{'DocIndex'} = $params['performSearch']['hitViewer'];
			$data->{'Parameters'}->{'HitViewer'}->{'MultiColorHits'} = true;
		}

		if ( isset( $request['field'] ) ) {
			// This request is not actually a search request to get results, but rather just to search field values.
			$field_name       = $request['field'];
			$match_expression = '';
			if ( isset( $request['match'] ) ) {
				$match_expression = $request['match'];
			}
			$data->{'Parameters'}->{'GetTopFieldValues'}->{'Fields'}          = array( $field_name );
			$data->{'Parameters'}->{'GetTopFieldValues'}->{'MatchExpression'} = $match_expression;
			$data->{'Parameters'}->{'GetTopFieldValues'}->{'MaxResults'}      = 256;
			// Avoid getting too much irrelevant data back:.
			$data->{'Parameters'}->{'ResultsPerPage'} = 1;
			$data->{'Parameters'}->{'IncludeContext'} = false;
		}

		// New HTTP Handler using WordPress HTTP API
		// Switching over to using this to make http request because better supported more concise, easier to debug
		// https://developer.wordpress.org/plugins/http-api/ .
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
		if ( ! is_wp_error( $response_data ) ) {
			// Success.
			$response_body = wp_remote_retrieve_body( $response_data );
			if ( false !== $hit_viewer ) {
				// Request was for hit viewer, response should be the html for hit viewer.
				header( 'Content-Type: text/html' );
				$obj = json_decode( $response_body );
				//phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$html = $obj->HitViewer->Html;
				//phpcs:enable
				//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $html;
				//phpcs:enable
				exit();
			} else {
				// Request was for search results, simply pass it along.
				echo( esc_html( $response_body ) );
				exit();
			}
		} else {
			// Returning the WP_ERROR object as the request failed.
			return $response_data;
		}
	} catch ( Exception $e ) {
		Site_Search_One_Debugging::log( 'Error Serving Search Request: ' . $e->getMessage() );
		header( 'HTTP/1.1 500 Internal Error' );
		header( 'Content-Type: text/plain' );
		echo( 'Something went wrong (Code 10)' );
		exit();
	}
}

/**
 * Generates hitviewers for requests on the /hitviewer endpoint
 *
 * @param WP_REST_Request $request request.
 */
function ss1_hitviewer( $request ) {
	header( 'Content-Type: text/html' );
	include plugin_dir_path( __FILE__ ) . 'public/ss1-hitviewer.php';
	exit();
}

/**
 * Callback for pdfviewer endpoint.
 *
 * @param WP_REST_Request $request request.
 */
function ss1_pdfviewer( $request ) {
	header( 'Content-Type: text/html' );
	include plugin_dir_path( __FILE__ ) . 'public/pdf-viewer/web/viewer.php';
	exit();
}


/**
 * Retrieve the Index UUID associated with a Page that is a search page.
 *
 * @param int $post_id Page that is a Search Page.
 * @return array|WP_Error
 * The index UUID, or null if page is not a search page.
 */
function get_index_from_post_id( $post_id ) {
	//phpcs:disable WordPress.DB.DirectDatabaseQuery
	//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
	//phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	//phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	// Search wpdb for a Search Page.
	global $wpdb;
	$table_name = $wpdb->prefix . 'ss1_sc1_indexes';
	$index_data = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT `sc1_ix_uuid`, `sc1_ix_id` FROM $table_name WHERE `post_id` = %d AND `wp_site_url` = %s",
			$post_id,
			base64_encode( get_site_url() )
		)
	);
	if ( null === $index_data ) {
		return new WP_Error(
			'failed_retrieve_associated_index',
			'An error occurred retrieving the index associated with post id ' . $post_id
		);
	}
	if ( count( $index_data ) === 0 ) {
		return new WP_Error(
			'no_associated_index',
			'The page is not registered as a search page'
		);
	}
	$index = $index_data[0];
	if ( null === $index->sc1_ix_id ) {
		set_time_limit( 0 );
		$sc1_ix_id = retrieve_index_id_from_sc1( $index->sc1_ix_uuid );
		if ( is_wp_error( $sc1_ix_id ) ) {
			return $sc1_ix_id; // return the error.
		}
		if ( false === $sc1_ix_id ) {
			return new WP_Error(
				'retrieve_id_fail',
				'Failed to retrieve Index ID from SC1 - Service down?'
			);
		}
		$index->sc1_ix_id = $sc1_ix_id;
	}
	return array(
		'IndexID'   => $index->sc1_ix_id,
		'IndexUUID' => $index->sc1_ix_uuid,
	);
	//phpcs:enable
}

/**
 * Attempt to retrieve the IndexID for IndexUUID from SC1
 *
 * @param string $index_uuid SC1 Index UUID.
 *
 * @return int|false|WP_Error
 */
function retrieve_index_id_from_sc1( $index_uuid ) {
	Site_Search_One_Debugging::log( 'SS1-DEBUG Retrieving the ID of IndexUUID ' . $index_uuid . ' ...' );
	$endpoint = get_transient( 'ss1-endpoint-url' ) . '/IndexManager';
	require_once plugin_dir_path( __FILE__ ) . 'admin/class-sc1-index-manager.php';
	$api_key = SC1_Index_Manager::get_sc1_api_key();
	$data    = array(
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
	$options = array(
		'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
		'body'        => wp_json_encode( $data ),
		'method'      => 'POST',
		'data_format' => 'body',
		'timeout'     => 30,
	);
	//phpcs:enable
	$request = wp_remote_post( $endpoint, $options );
	if ( is_wp_error( $request ) ) {
		Site_Search_One_Debugging::log( 'SS1-DEBUG A WP_Error was incurred attempting to retrieve the ID of the Index:' );
		Site_Search_One_Debugging::log( $request );
		return $request;
	}
	$response_body = wp_remote_retrieve_body( $request );
	$response_data = json_decode( $response_body, true );
	Site_Search_One_Debugging::log( 'SS1-DEBUG Received response from server:' );
	Site_Search_One_Debugging::log( $response_data );
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
	return false;
}

/**
 * Not currently used. Debug logs the menu items in a given menu.
 *
 * @param int $menu_id the menu id.
 */
function check_for_uploads_in_menu( $menu_id ) {
	$nav_menu_items = wp_get_nav_menu_items( $menu_id );
	Site_Search_One_Debugging::log( $nav_menu_items );
}

/**
 * Check against SearchCloudOne server to ensure the API Key passed is valid for this server to use.
 *
 * @param string $api_key SC1 api key.
 * @throws Exception Unrecognised status code whilst checking api key.
 */
function sc1_is_api_key_valid( $api_key ) {
	// basic validation - is the string 36 characters in length?
	if ( strlen( $api_key ) !== 36 ) {
		return false;
	}
	// advanced validation - does the server respond with a 200 for a list indexes request with this key?
	$data      = array(
		'APIKey'          => $api_key,
		'Action'          => 'ListIndexes',
		'Activation'      => true,
		'Version'         => 'SS1',
		'FilterToIndexes' => array(),
	);
	$options   = array(
		'http' => array(
			'header'  => "Content-type: application/json\r\n",
			'method'  => 'POST',
			'content' => wp_json_encode( $data ),
		),
	);
	$context   = stream_context_create( $options );
	$result    = @file_get_contents( get_transient( 'ss1-endpoint-url' ) . '/IndexManager', false, $context );
	$http_code = ss1_get_http_code( $http_response_header );
	if ( $http_code >= 200 && $http_code < 300 ) {
		return true;
	}
	if ( $http_code >= 400 && $http_code < 500 ) {
		return false;
	}
	// Following normally shouldn't happen - indicates a problem on SearchCloudOne side.
	throw new Exception( 'Unrecognized status code whilst checking api key validity - Possible internal server error? ' . $http_code );
}

/**
 * Installs the searchpages table into db.
 */
function ss1_db_install_searchpages() {
	 global $wpdb;
	$table_name      = $wpdb->prefix . 'ss1_search_pages';
	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE $table_name (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    ix_pages tinyint(1) NOT NULL,
    ix_posts tinyint(1) NOT NULL,
    cat_ids varchar(256) NOT NULL,
    post_id bigint(20) NOT NULL,
    sc1_ix_uuid char(36) NOT NULL,
    sc1_ix_id bigint(20),
    filter_fields varchar(1024),
    display_opts varchar(32000),
    menu_id bigint(20),
    menu_ids varchar(128),
    tax_term_ids varchar(256),
    also_show varchar(256),
    post_types varchar(2048),
    page_cat_ids varchar(256),
    attached_media_only tinyint(1) NOT NULL DEFAULT 1,
    media_term_ids   varchar(256),
    media_mime_types text,
    PRIMARY KEY  (id)
   ) $charset_collate;";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
	set_transient( 'ss1-search-pages-installed', 'v3' );
}

/**
 * Installs the tokens table into db.
 */
function ss1_db_install_tokens() {
	global $wpdb;
	$table_name      = $wpdb->prefix . 'ss1_tokens';
	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE $table_name (
        token char(36) NOT NULL,
        wp_site_url varchar(512) NOT NULL,
        PRIMARY KEY (token)
    ) $charset_collate;";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
	set_transient( 'ss1-tokens-has-sites', 'yes' );
}

/**
 * Installs the global settings table into db.
 */
function ss1_db_install_global_settings() {
	 global $wpdb;
	$table_name      = $wpdb->prefix . 'ss1_globals';
	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE $table_name (
    setting varchar(128) NOT NULL,
    value   varchar(32000),
    PRIMARY KEY (setting)
    ) $charset_collate;";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
	set_transient( 'ss1-globals-installed', 'v1' );

	require_once plugin_dir_path( __FILE__ ) . 'admin/class-sc1-index-manager.php';
	$api_key = SC1_Index_Manager::get_sc1_api_key();
	if ( $api_key ) {
		SC1_Index_Manager::set_sc1_api_key( $api_key ); // Ensures it's written to db, might just be as transient.
	}
}

/**
 * Installs the indexed pages table into db.
 */
function ss1_db_install_indexed_pages() {
	global $wpdb;
	$table_name      = $wpdb->prefix . 'ss1_ix_pages';
	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE $table_name (
    search_page_id mediumint(9) NOT NULL,
    post_id bigint(20) NOT NULL,
    PRIMARY KEY (search_page_id, post_id)
    ) $charset_collate;";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Installs the syncQueue table into db.
 */
function ss1_db_install_sync_queue() {
	global $wpdb;

	$table_name      = $wpdb->prefix . 'ss1_sync_queue';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    action tinyint,
    post_id bigint(20),
    index_uuid char(36),
    started datetime,
    completed datetime,
    problematic tinyint default 0,
    problem varchar(256),
    PRIMARY KEY  (id)
    ) $charset_collate;";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Installs the uploaded posts table into db.
 */
function ss1_db_install_uploaded_posts() {
	global $wpdb;

	$table_name      = $wpdb->prefix . 'ss1_uploaded_posts';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
    post_id bigint(20),
    index_uuid char(36),
    sc1_file_uuid char(36),
    revision bigint(20),
    PRIMARY KEY (post_id, index_uuid)
    ) $charset_collate;";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Installs the indexes table into db.
 */
function ss1_db_install_indexes() {
	global $wpdb;
	$table_name      = $wpdb->prefix . 'ss1_sc1_indexes';
	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE $table_name (
    post_id bigint(20) NOT NULL,
    wp_site_url varchar(512) NOT NULL,
    sc1_ix_uuid char(36) NOT NULL,
    sc1_ix_id bigint(20),
    cached_meta_spec varchar(32768),
    cached_meta_spec_sc1_modified bigint default 0,
    cached_meta_spec_local_time datetime,
    cached_top_select_field_values text,
    cached_top_select_field_values_sc1_modified bigint default 0,
    cached_xfirstword_response text,
    cached_xfirstword_time datetime,
    PRIMARY KEY (post_id, wp_site_url)
    ) DEFAULT CHARACTER SET = latin1;";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
	set_transient( 'ss1-indexes-installed', 'v5' );
	require_once 'admin/class-site-search-one-queue-manager.php';
	$queue_manager = new Site_Search_One_Queue_Manager();
	$queue_manager->enqueue_cache_spec(); // On upgrade, the metadata spec should be cached.
}

/**
 * Installs the site vars table into db.
 */
function ss1_db_install_site_vars() {
	global $wpdb;
	$table_name      = $wpdb->prefix . 'ss1_site_vars';
	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE $table_name (
    wp_site_url varchar(512) NOT NULL,
    setting     varchar(128) NOT NULL,
    value       varchar(1024),
    PRIMARY KEY (wp_site_url, setting)
    ) DEFAULT CHARACTER SET = latin1;";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
	set_transient( 'ss1-site-vars-installed', 'v1' );
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-site-search-one-deactivator.php
 */
function deactivate_site_search_one() {
	set_transient( 'ss1-active', 'no', 0 );
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-site-search-one-deactivator.php';
	Site_Search_One_Deactivator::deactivate();
}

/**
 * TODO Phase this out in favor of WordPress HTTP API
 *
 * @param array $http_response_header response header.
 *
 * @return int
 */
function ss1_get_http_code( $http_response_header ) {
	if ( is_array( $http_response_header ) ) {
		$parts = explode( ' ', $http_response_header[0] );
		if ( count( $parts ) > 1 ) { // HTTP/1.0 <code> <text>.
			return intval( $parts[1] ); // Get code.
		}
	}
	return 0;
}

register_activation_hook( __FILE__, 'activate_site_search_one' );
register_deactivation_hook( __FILE__, 'deactivate_site_search_one' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-site-search-one.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_site_search_one() {

	$plugin = new Site_Search_One();
	$plugin->run();

}
run_site_search_one();
