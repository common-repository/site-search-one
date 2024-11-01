<?php
/**
 * Admin Page used to Change Global Settings such as CSS etc.
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

	.form-table .widefat .headfix {
		font-weight: 400;
		padding: 8px 10px;
	}
</style>
<div class="wrap ss1-admin">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Global Settings', 'site-search-one' ); ?>
	</h1>
	<hr class="wp-header-end">
	<h2 class="title"><?php esc_html_e( 'Custom CSS', 'site-search-one' ); ?></h2>
	<table class="form-table" role="presentation">
		<tbody>
		<tr>
			<th scope="row">
				<label for="global_css_search_pages">
					<?php esc_html_e( 'Search Page CSS', 'site-search-one' ); ?>
				</label>
			</th>
			<td>
				<textarea spellcheck="false" id="global_css_search_pages" cols="50" rows="10" maxlength="16000"></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="global_css_widgets">
					<?php esc_html_e( 'Widget CSS', 'site-search-one' ); ?>
				</label>
			</th>
			<td>
				<textarea spellcheck="false" id="global_css_widgets" cols="50" rows="10" maxlength="16000"></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="global_css_documents">
					<?php esc_html_e( 'Document CSS', 'site-search-one' ); ?>
				</label>
			</th>
			<td>
				<textarea spellcheck="false" id="global_css_documents" cols="50" rows="10" maxlength="16000"></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="global_link_format">
					<?php esc_html_e( 'Link format', 'site-search-one' ); ?>
				</label>
			</th>
			<td>
				<textarea spellcheck="false" id="global_link_format" cols="50" rows="10" maxlength="16000">
<h3 class="link_title">%%doc_title%%</h3>
<p class="context">%%context%%</p>
				</textarea>
			</td>
		</tr>
		</tbody>
	</table>
	<br>
	<span id="saving-spinner" class="spinner"></span>
	<p id="save-error" class="ss1-validation-error"></p>
	<button class="button button-primary" id="ss1-btn-save"><?php esc_html_e( 'Save', 'site-search-one' ); ?></button>
</div>
<script>
	(function ($) {
		$(document).ready(function() {
			<?php
			$settings = array(
				'global_css_search_pages',
				'global_css_documents',
				'global_css_widgets',
				'global_link_format',
			);
			global $wpdb;
			$table_name = $wpdb->prefix . 'ss1_globals';
			$query      = "SELECT setting, value FROM $table_name WHERE setting IN (";
			$first      = true;
			foreach ( $settings as $setting ) {
				if ( ! $first ) {
					$query .= ',';
				}
				$first  = false;
				$query .= '%s';
			}
			//phpcs:disable WordPress.DB.DirectDatabaseQuery
			//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			$query   .= ')';
			$prepared = $wpdb->prepare( $query, $settings );
			$results  = $wpdb->get_results( $prepared, OBJECT_K );
			//phpcs:enable
			?>
			let settings = <?php echo wp_json_encode( $results ); ?>;
			console.info('settings', settings);
			let global_css_search_pages = settings.global_css_search_pages ? settings.global_css_search_pages.value : '';
			let global_css_documents    = settings.global_css_documents ? settings.global_css_documents.value : '';
			let global_css_widgets      = settings.global_css_widgets ? settings.global_css_widgets.value : '';
			let global_link_format      = settings.global_link_format ? settings.global_link_format.value : '';
			$('#global_css_search_pages').val(global_css_search_pages);
			$('#global_css_documents').val(global_css_documents);
			$('#global_css_widgets').val(global_css_widgets);
			$('#global_link_format').val(global_link_format);

			$('#ss1-btn-save').click(function() {
				let btnSave         = $('#ss1-btn-save');
				let spinner         = $('#saving-spinner');
				let validationTxt   = $('#save-error');
				spinner.addClass('is-active');
				btnSave.text('<?php echo esc_js( __( 'Saving', 'site-search-one' ) ); ?>').prop('disabled', true);
				validationTxt.text('');
				let data = {
					'set_globals' : {
						'global_css_search_pages':  $('#global_css_search_pages').val(),
						'global_css_documents':     $('#global_css_documents').val(),
						'global_css_widgets':       $('#global_css_widgets').val(),
						'global_link_format':       $('#global_link_format').val()
					}
				};
				$.ajax({
					type: 'POST',
					url: '<?php echo( esc_js( rest_url( 'ss1_client/v1/options' ) ) ); ?>',
					dataType: 'json',
					data: JSON.stringify(data),
					contentType: "application/json",
					success: function (data, textStatus, xhr) {
						btnSave.text("<?php echo esc_js( __( 'Saved', 'site-search-one' ) ); ?>");
						window.location.href = '<?php echo esc_js( admin_url( 'admin.php?page=' . trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) ) . 'view-search-pages.php' ); ?>'
						return;
					},
					error: function (data, textStatus, xhr) {
						spinner.removeClass('is-active');
						validationTxt.text('<?php echo esc_js( __( 'Something went wrong. Check your connection and try again.', 'site-search-one' ) ); ?>');
						btnSave.prop('disabled', false).text("<?php echo esc_js( __( 'Save', 'site-search-one' ) ); ?>");
						return;
					}
				});

			});
		});
	})(jQuery);
</script>
