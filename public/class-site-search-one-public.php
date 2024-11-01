<?php
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Site_Search_One
 * @subpackage Site_Search_One/public
 * @author     Thomas Harris <thomas.harris@electronart.co.uk>
 */

/**
 * The public-facing functionality of the plugin.
 */
class Site_Search_One_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Site_Search_One_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Site_Search_One_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/site-search-one-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Site_Search_One_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Site_Search_One_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		require_once plugin_dir_path( __FILE__ ) . '../admin/class-site-search-one-queue-manager.php';
		$queue_manager = new Site_Search_One_Queue_Manager();
		$cron_wanted   = $queue_manager->is_cron_wanted();

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/site-search-one-public.js', array( 'jquery' ), $this->version, false );
		$script_arguments = array(
			'ss1_rest_cron_hack' => rest_url( 'ss1_client/v1/cronhack' ),
			'ss1_is_cron_wanted' => $cron_wanted,
		);
		wp_localize_script( $this->plugin_name, 'php_vars', $script_arguments );

	}

}
