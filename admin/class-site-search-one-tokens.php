<?php
/**
 * SearchCloudOne Token management.
 *
 * @package Site_Search_One
 */

/**
 * SearchCloudOne Token management.
 */
class Site_Search_One_Tokens {
	/**
	 * Get the number of tokens left in this WordPress installs cache.
	 *
	 * @return int|null
	 */
	public static function get_num_tokens_left() {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_tokens';
		$query      = $wpdb->prepare(
			'SELECT COUNT(*) FROM ' . $table_name . ' WHERE wp_site_url = %s',
			base64_encode( get_site_url() )
		);
		$result     = $wpdb->get_var( $query );
		if ( null === $result ) {
			return null;
		}
		return intval( $result );
		//phpcs:enable
	}

	/**
	 * Request more tokens from SC1.
	 *
	 * @return true|WP_Error
	 */
	public static function request_more_tokens() {
		require_once plugin_dir_path( __FILE__ ) . 'class-sc1-index-manager.php';
		$api_key  = SC1_Index_Manager::get_sc1_api_key();
		$req_data = array(
			'APIKey'    => $api_key,
			'Action'    => 'CreateTokens',
			'NumTokens' => 10,
			'Scope'     => 'READ',
			'MaxAge'    => 120,
		);
		//phpcs:disable WordPressVIPMinimum.Performance.RemoteRequestTimeout
		$args     = array(
			'body'        => wp_json_encode( $req_data ),
			'timeout'     => '20',
			'blocking'    => true,
			'data_format' => 'body',
			'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
			'httpversion' => '1.1',
		);
		//phpcs:enable
		$endpoint = get_transient( 'ss1-endpoint-url' ) . '/Tokens';
		$request  = wp_remote_post( $endpoint, $args );
		if ( is_wp_error( $request ) ) {
			return $request; // Failed to obtain tokens.
		}
		$response_body = wp_remote_retrieve_body( $request );
		$response_code = wp_remote_retrieve_response_code( $request );
		$data          = json_decode( $response_body );
		//phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$tokens = $data->Tokens;
		//phpcs:enable
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_tokens';
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		foreach ( $tokens as $token ) {
			$data = array(
				'token'       => $token,
				'wp_site_url' => base64_encode( get_site_url() ),
			);
			$wpdb->insert( $table_name, $data );
		}
		return true;
		//phpcs:enable
	}

	/**
	 * Issue a token from the token cache.
	 *
	 * @param int $attempt The attempt number. Method will retry on 1st attempt if fails.
	 * @return string|null
	 */
	public static function issue_token( $attempt = 1, $allow_retry = true ) {
		//phpcs:disable WordPress.DB.DirectDatabaseQuery
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		//phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		global $wpdb;
		$table_name = $wpdb->prefix . 'ss1_tokens';
		$query      = $wpdb->prepare(
			'SELECT token FROM ' . $table_name . ' WHERE wp_site_url = %s LIMIT 1',
			base64_encode( get_site_url() )
		);
		$result     = $wpdb->get_var( $query );
		if ( null !== $result ) {
			// Remove the token from the db so it can't be issued again.
			$where = array(
				'token' => $result,
			);
			$wpdb->delete( $table_name, $where );
		} else {
			// Ran out of tokens. Attempt to get more if this is 1st attempt.
			if ( 1 === $attempt  && $allow_retry === true ) {
				Site_Search_One_Debugging::log( 'SS1-WARNING Ran out of tokens - Cron not running?' );
				self::request_more_tokens();
				return self::issue_token( 2 );
			}
		}
		return $result;
		//phpcs:enable
	}

}
