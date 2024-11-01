<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       www.electronart.co.uk
 * @since      1.0.0
 *
 * @package    Site_Search_One
 * @subpackage Site_Search_One/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Site_Search_One
 * @subpackage Site_Search_One/includes
 * @author     Thomas Harris <thomas.harris@electronart.co.uk>
 */
class Site_Search_One_I18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'site-search-one',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
