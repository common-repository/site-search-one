<?php
/**
 * API Key Setup Page
 *
 * @package Site_Search_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<style>
	<?php
	echo esc_html( file_get_contents( plugin_dir_path( __FILE__ ) . 'css/site-search-one-admin.css' ) );
	?>
</style>
<script>
	function ss1_submit_apiKey(btn) {
		jQuery('#ss1_apiErrors').html(" ");
		let api_key = jQuery('#ss1-input-apiKey')[0].value.trim();
		// very basic validation
		if (api_key.length !== 36) {
			jQuery('#ss1_apiErrors').html("<?php esc_html_e( 'Invalid API Key', 'site-search-one' ); ?>");
			return;
		}
		set_key(api_key)
	}

	function set_key(api_key) {
		let json = {};
		json.apiKey = api_key;
		jQuery.ajax({
			url: '<?php echo esc_js( ( rest_url( 'ss1_client/v1/options' ) ) ); ?>',
			type: "POST",
			data: JSON.stringify(json),
			contentType: "application/json",
			dataType: 'json',
			timeout: 10000, // 10 seconds
			success: function(data, textStatus, xhr) {
				window.location.href = "<?php echo esc_js( admin_url( 'admin.php?page=' . trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) ) . 'view-new-search-page.php' ); ?>";
			}, error: function(data, textStatus, xhr) {
				console.error('Failed to set API Key');
				console.error(data);
				try {
					<?php
					/* translators: %s is replaced with The error description */
					$error_str = __( 'Error: %s <br> Please try again.', 'site-search-one' );
					?>
					// In the event of a server error, responseJSON might cause null ref exception, but normally would send a human readable message.

					jQuery('#ss1_apiErrors').html( '<?php echo esc_html( $error_str ); ?>'.replace('%s',data.responseJSON.message) );
				} catch (e) {
					jQuery('#ss1_apiErrors').html(
						'<?php esc_html_e( 'Something went wrong. Check your connection and try again.', 'site-search-one' ); ?>'
					);
				}
			}
		});
	}
</script>
<div class="wrap ss1-admin">
	<h1><?php esc_html_e( 'Connect to your Site Search ONE account', 'site-search-one' ); ?></h1>
	<p><?php esc_html_e( 'Paste the key below to continue', 'site-search-one' ); ?></p>
	<input type="text" class="regular-text" id="ss1-input-apiKey">
	<button class="button button-primary" onclick="ss1_submit_apiKey(this)"><?php esc_html_e( 'Submit', 'site-search-one' ); ?></button>
	<p id="ss1_apiErrors" style="color:red"><!-- Error Messages go here --></p>
</div>
