<?php
/**
 * Admin Page used to Change display options of a search page.
 *
 * @package Site_Search_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! isset( $_GET['post_id'] ) ) {
	exit;
}
$sp_post_id = sanitize_text_field( wp_unslash( $_GET['post_id'] ) );

/*
API Key Setup Page
*/
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
		<?php
		/* translators: The name of the search page */
		echo esc_html( sprintf( __( 'Options - %s', 'site-search-one' ), get_the_title( $sp_post_id ) ) );
		?>
	</h1>
	<hr class="wp-header-end">
	<table class="form-table" role="presentation">
		<tbody>
		<tr>
			<th scope="row">
				<label><?php esc_html_e( 'Results', 'site-search-one' ); ?></label>
			</th>
			<td>
				<fieldset>
					<label>
						<input type="checkbox" id="checkbox_initial_search">
						<span>
							<?php esc_html_e( 'Initial Search', 'site-search-one' ); ?>
						</span>
					</label>
					<br>
					<label>
						<input type="checkbox" name="radio_results" id="checkbox_results_new_window">
						<span id="text_checkbox_results_new_window">
							<?php esc_html_e( 'Open in new Window', 'site-search-one' ); ?>
						</span>
					</label>
					<br>
					<label>
						<input type="radio" name="radio_results" id="radio_results_original">
						<span id="text_radio_results_original">
							<?php esc_html_e( 'Link to original page', 'site-search-one' ); ?>
						</span>
					</label>
					<br>
					<label>
						<input type="radio" name="radio_results" id="radio_results_hitViewer">
						<span id="text_radio_results_hitViewer">
							<?php esc_html_e( 'Link to hit viewer', 'site-search-one' ); ?></span>
					</label>
					<br>
					<label>
						<input type="radio" name="radio_show_fields" value="all" style="margin-left: 18px">
						<span id="text_radio_show_all_fields">
							<?php esc_html_e( 'Show all field data', 'site-search-one' ); ?>
						</span>
					</label>
					<br>
					<label>
						<input type="radio" name="radio_show_fields" value="selected" style="margin-left: 18px">
						<span id="text_radio_show_selected_fields">
							<?php esc_html_e( 'Show selected:', 'site-search-one' ); ?>
						</span>
					</label>
					<div class="postbox" id="postbox_hitviewer_shown_fields" style="margin-left: 20px; max-width: 330px">
						<div id="still-indexing-warning-3" class="hidden"><span id="loading-spinner" class="spinner is-active"></span>
							<?php esc_html_e( 'Sync in progress - Some fields may not be detected yet', 'site-search-one' ); ?>
						</div>
						<div class="inside" style="min-height:20px">
							<span id="loading-spinner" class="spinner is-active"></span>
						</div>
					</div>
					<br>
					<label>
						<?php esc_html_e( 'Link format', 'site-search-one' ); ?>
						<br><textarea id="link_format" style="min-width: 25em; margin-top: 0.5em; min-height: 8em; max-height: 30em">
<h3 class="link_title">%%doc_title%%</h3>
<p class="context">%%context%%</p>
						</textarea>
						<br><?php esc_html_e( 'Document title: %%doc_title%%', 'site-search-one' ); ?>
						<br><?php esc_html_e( 'Document fields: %%field:fieldname%%', 'site-search-one' ); ?>
					</label>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ss1-page-name">
					<?php esc_html_e( 'Filters', 'site-search-one' ); ?><span class="dashicons dashicons-filter"></span>
				</label>
			</th>
			<td>
				<fieldset>
					<label>
						<input name="radio_filters" class="ss1-radio-filters" type="radio" id="radio_filters_all" value="all">
						<span id="text_radio_filters_all">
							<?php esc_html_e( 'Display all fields', 'site-search-one' ); ?></span>
					</label>
					<br>
					<label>
						<input name="radio_filters" class="ss1-radio-filters" type="radio" id="radio_filters_selected" value="include">
						<span id="text_radio_filters_selected">
							<?php esc_html_e( 'Include:', 'site-search-one' ); ?>
						</span>
						<br>
					</label>
					<label>
						<input name="radio_filters" class="ss1-radio-filters" type="radio" id="radio_filters_exclude" value="exclude">
						<span id="text_radio_filters_selected">
							<?php esc_html_e( 'Exclude:', 'site-search-one' ); ?></span>
						<br>
					</label>
					<div class="postbox" id="postbox_filter_checkboxes" style="max-width: 350px">
						<div id="still-indexing-warning-2" class="hidden"><span id="loading-spinner" class="spinner is-active"></span>
							<?php esc_html_e( 'Sync in progress - Some fields may not be detected yet', 'site-search-one' ); ?>
						</div>
						<div class="inside" style="min-height:20px">
							<span id="loading-spinner" class="spinner is-active"></span>
						</div>
					</div>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label><?php esc_html_e( 'Facets', 'site-search-one' ); ?></label>
			</th>
			<td>
				<fieldset>
					<label>
						<input name="radio_facets" type="radio" value="all">
						<span id="text_radio_filters_all">
							<?php esc_html_e( 'Display all facets', 'site-search-one' ); ?>
						</span>
					</label>
					<br>
					<label>
						<input name="radio_facets" type="radio" value="selected">
						<span id="text_radio_filters_selected">
							<?php esc_html_e( 'Include:', 'site-search-one' ); ?>
						</span>
					</label>
					<div class="postbox" id="postbox_facets_checkboxes" style="max-width: 350px">
						<div class="inside"  style="min-height: 20px">
							<span id="loading-spinner" class="spinner is-active"></span>
						</div>
					</div>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label><?php esc_html_e( 'Indexes', 'site-search-one' ); ?></label>
			</th>
			<td>
				<fieldset>
					<div class="postbox" id="postbox_additionally_search" style="max-width: 350px">
						<div class="inside" style="min-height: 20px">
							<input type="checkbox" id="search-all-checkbox">
							<input type="text" id="search-all-text" placeholder="Search all" maxlength="64">
							<br>
							<hr>
						<?php
							require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-search-page.php';
							$editing_search_page = Site_Search_One_Search_Page::get_search_page( $sp_post_id );
							$search_pages        = Site_Search_One_Search_Page::get_all_search_pages();
						if ( is_wp_error( $search_pages ) ) {
							$search_pages = array();
						}
						foreach ( $search_pages as $search_page ) {
							if ( $search_page->get_post_id() !== $editing_search_page->get_post_id() ) {
								$checked = '';
								if ( $editing_search_page->is_also_shown_searchpage( $search_page->get_post_id() ) ) {
									$checked = 'checked';
								}
								?>
									<label>
										<input type="checkbox" data-pid="<?php echo esc_attr( $search_page->get_post_id() ); ?>" class="search-checkbox" <?php echo esc_attr( $checked ); ?>>
										<span><?php echo esc_html( get_the_title( $search_page->get_post_id() ) ); ?></span>
									</label>
									<br>
									<?php
							}
						}
						?>

						</div>
					</div>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ss1-index-name"><?php esc_html_e( 'Index name', 'site-search-one' ); ?></label>
			</th>
			<td>
				<?php
					$ix_name = $editing_search_page->get_ix_name();
				?>
				<input type="text" class="regular-text" id="ss1-index-name" maxlength="60" value="<?php echo esc_attr( $ix_name ); ?>">
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ss1-search-bar"><?php esc_html_e( 'Search bar', 'site-search-one' ); ?></label>
			</th>
			<td>
				<fieldset>
					<label>
						<input type="checkbox" name="ss1-search-bar-hidden" id="checkbox_hide_searchbar">
						<span>
							<?php esc_html_e( 'Hide search bar', 'site-search-one' ); ?>
						</span>
					</label>
					<br>
					<label>
						<input type="checkbox" name="ss1-hideSynonymsAndStemming" id="checkbox_hide_synonyms_stemming">
						<span>
								<?php esc_html_e( 'Hide Stemming and Synonyms Checkboxes', 'site-search-one' ); ?>
							</span>
					</label>
					<br>
					<table class="wp-list-table widefat striped pages" style="max-width: 350px">
						<thead>
						<th class="manage-column column-name headfix">
							<?php esc_html_e( 'Option', 'site-search-one' ); ?>
						</th>
						<th class="mange-column column-name headfix" style="width: 3em">
							<?php esc_html_e( 'Default', 'site-search-one' ); ?>
						</th>
						<th class="manage-column column-name headfix" style="width: 6em">
							User Choice
						</th>
						</thead>
						<tbody>
						<tr>
							<td><span><?php esc_html_e( 'Stemming', 'site-search-one' ); ?></span></td>
							<td>
								<input type="checkbox" value="stemming-default">
							</td>
							<td>
								<input type="checkbox" value="stemming-choice">
							</td>
						</tr>
						<tr>
							<td><span><?php esc_html_e( 'Synonyms', 'site-search-one' ); ?></span></td>
							<td>
								<input type="checkbox" value="synonyms-default">
							</td>
							<td>
								<input type="checkbox" value="synonyms-choice">
							</td>
						</tr>
						</tbody>
					</table>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="thesaurus_file">
					<?php esc_html_e( 'Synonyms', 'site-search-one' ); ?>
				</label>
			</th>
			<td>
				<fieldset>
					<label>
						<input type="checkbox" id="checkbox-user-thesaurus" name="checkbox-use-user-thesaurus">
						<span><?php esc_html_e( 'User Thesaurus Plus:', 'site-search-one' ); ?></span>
					</label>
					<div class="postbox" style="max-width: 350px">
						<div class="inside">
							<button id="btnBrowseThesaurus" class="button button-secondary"><?php esc_html_e( 'Browse...', 'site-search-one' ); ?></button>
							<label  id="labelInputThesaurus" for="input_thesaurus_file"><?php esc_html_e( 'No file selected', 'site-search-one' ); ?></label>
							<input type="file" id="input_thesaurus_file" name="thesaurus_file" accept="application/xml" style="display:none">
						</div>
						<div class="inside">
							<label id="label-wordnet">
								<input type="checkbox" id="checkbox-wordnet" name="checkbox-wordnet">
								<span><?php esc_html_e( 'WordNet', 'site-search-one' ); ?></span>
							</label>
						</div>
					</div>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<!-- title goes here-->
				<label>
					<?php esc_html_e( 'Search type', 'site-search-one' ); ?>
				</label>
			</th>
			<td>
				<table class="wp-list-table widefat striped pages" style="max-width: 350px">
					<thead>
						<tr>
							<th class="manage-column column-name headfix">
								<?php esc_html_e( 'Type', 'site-search-one' ); ?>
							</th>
							<th class="manage-column column-name headfix" style="width: 3em">
								<?php esc_html_e( 'Default', 'site-search-one' ); ?>
							</th>
							<th class="manage-column column-name headfix" style="width: 6em">
								<?php esc_html_e( 'User Choice', 'site-search-one' ); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<span><?php esc_html_e( 'Any words', 'site-search-one' ); ?></span>
							</td>
							<td>
								<input type="radio" value="any" name="def_search_type">
							</td>
							<td>
								<input type="checkbox" value="any" name="search_type">
							</td>
						</tr>
						<tr>
							<td>
								<span><?php esc_html_e( 'All words', 'site-search-one' ); ?></span>
							</td>
							<td>
								<input type="radio" value="all" name="def_search_type">
							</td>
							<td>
								<input type="checkbox" value="all" name="search_type">
							</td>
						</tr>
						<tr>
							<td>
								<span><?php esc_html_e( 'Boolean (and, or, not, ...)', 'site-search-one' ); ?></span>
							</td>
							<td>
								<input type="radio" value="bool" name="def_search_type">
							</td>
							<td>
								<input type="checkbox" value="bool" name="search_type">
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
		</tbody>
	</table>
	<h2 class="title"><?php esc_html_e( 'Custom CSS', 'site-search-one' ); ?></h2>
	<table class="form-table" role="presentation">
		<tbody>
		<tr>
			<th scope="row">
				<label>
					<?php esc_html_e( 'Search Page CSS', 'site-search-one' ); ?>
				</label>
			</th>
			<td>
				<textarea spellcheck="false" id="custom_css" cols="50" rows="10" maxlength="16000"></textarea>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label>
					<?php esc_html_e( 'Document CSS', 'site-search-one' ); ?>
				</label>
			</th>
			<td>
				<textarea spellcheck="false" id="document_css" cols="50" rows="10" maxlength="16000"></textarea>
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

		<?php
			require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-search-page.php';
			$search_page  = Site_Search_One_Search_Page::get_search_page( $sp_post_id );
			$display_opts = $search_page->get_page_display_options();
		?>


		$('#btnBrowseThesaurus').click(function() {
			$('#input_thesaurus_file').click();
		});


		let spinnerSaving = document.getElementById('saving-spinner');
		let displayedInit = false;
		let loadedFields  = false;
		let loadedFacets  = false;
		let init_display_options = <?php echo wp_json_encode( $display_opts, JSON_UNESCAPED_UNICODE ); ?>;
		let synonyms_path = '';
		if ('synonyms_path' in init_display_options) {
			synonyms_path = init_display_options.synonyms_path;
		}
		if ('custom_css' in init_display_options) {
			$('textarea#custom_css').val(init_display_options.custom_css);
		}
		if('document_css' in init_display_options) {
			$('textarea#document_css').val(init_display_options.document_css);
		}

		console.info('init_display_options:', init_display_options);

		switch (init_display_options.fields[0]) {
			case "all":
				$('#radio_filters_all').prop('checked', true);
				break;
			case 'include':
				$('#radio_filters_selected').prop('checked', true);
				break;
			case 'exclude':
				$('#radio_filters_exclude').prop('checked', true);
				break;
		}

		if (init_display_options.hasOwnProperty('facets')) {
			$('input[type="radio"][name="radio_facets"]').val([init_display_options.facets[0]]);
		} else {
			$('input[type="radio"][name="radio_facets"]').val(['all']);
		}

		switch(init_display_options.search_all_option)
		{
			case "optional":
				//$('#select_search_all').val('optional').change();
				// We're no longer having this feature apparently
				break;
			case "hidden":
				$('#search-all-checkbox').prop('checked', false);
				break;
			default:
				$('#search-all-checkbox').prop('checked', true);
				break;
		}

		if (init_display_options.search_all_text !== '')
		{
			$('#search-all-text').val(init_display_options.search_all_text);
		}

		if (init_display_options.link_behaviour === 'new_window') $('#checkbox_results_new_window').prop('checked', true);
		switch (init_display_options.link_opens) {
			case "original_page":
				$('#text_checkbox_show_fields').addClass('ss1-disabled');
				$('input[name="show_fields_at_bottom"]').prop('disabled', true);
				$('#radio_results_original').prop('checked', true);
				break;
			default:
				$('#radio_results_hitViewer').prop('checked', true);

				$('#checkbox_results_new_window').prop('checked', false);
				$('#checkbox_results_new_window').prop('disabled', true);
				$('#text_checkbox_results_new_window').addClass('ss1-disabled');
				break;
		}

		$('input[name="radio_results"]').change(function() {
			console.info('Change');
			if ($('#radio_results_hitViewer').prop('checked')) {
				$('input[name="show_fields_at_bottom"]').prop('disabled', false);
				$('#text_checkbox_show_fields').removeClass('ss1-disabled');

				$('#checkbox_results_new_window').prop('checked', false);
				$('#checkbox_results_new_window').prop('disabled', true);
				$('#text_checkbox_results_new_window').addClass('ss1-disabled');

				$('input[name="radio_show_fields"]').prop('disabled', false);
				$('#text_radio_show_all_fields').removeClass('ss1-disabled');
				$('#text_radio_show_selected_fields').removeClass('ss1-disabled');

			} else {
				$('#text_checkbox_show_fields').addClass('ss1-disabled');

				$('#checkbox_results_new_window').prop('disabled', false);
				$('#text_checkbox_results_new_window').removeClass('ss1-disabled');

				$('input[name="radio_show_fields"]').prop('disabled', true);
				$('#text_radio_show_all_fields').addClass('ss1-disabled');
				$('#text_radio_show_selected_fields').addClass('ss1-disabled');
			}
			evalHVCheckboxesEnabled();
		});

		$('input[name="radio_show_fields"]').change(function() {
			if ($('input[name="radio_show_fields"]:checked').val() === 'all')
			{
				$('input.hv-field-checkbox').prop('checked', true);
			}
			evalHVCheckboxesEnabled();
		});

		$('input[name="radio_facets"]').change(function() {
			if ($('input[name="radio_facets"]:checked').val() === 'all') {
				$('input.facet-checkbox').prop('checked', true);
			}
			evalFacetsCheckboxesEnabled();

		});

		if ('hide_searchbar' in init_display_options)
		{
			$('#checkbox_hide_searchbar').prop('checked', init_display_options.hide_searchbar);
		}

		//region Stemming/Synonyms Options
		// Defaults:
		let stemmingDefault = true;
		let synonymsDefault = false;
		let stemmingChoice   = true;
		let synonymsChoice   = true;

		if ('hideSynonymsAndStemming' in init_display_options) {
			$('#checkbox_hide_synonyms_stemming').prop('checked', init_display_options.hideSynonymsAndStemming);
		}
		if ('stemmingDefault' in init_display_options) stemmingDefault = init_display_options.stemmingDefault;
		if ('synonymsDefault' in init_display_options) synonymsDefault = init_display_options.synonymsDefault;
		if ('stemmingChoice'  in init_display_options) stemmingChoice  = init_display_options.stemmingChoice;
		if ('synonymsChoice' in init_display_options)  synonymsChoice  = init_display_options.synonymsChoice;
		$('input[value="stemming-default"]').prop('checked', stemmingDefault);
		$('input[value="synonyms-default"]').prop('checked', synonymsDefault);
		$('input[value="synonyms-choice"]').prop('checked', synonymsChoice);
		$('input[value="stemming-choice"]').prop('checked', stemmingChoice);
		//endregion
		if ('initialSearch' in init_display_options)
		{
			$('#checkbox_initial_search').prop('checked',init_display_options.initialSearch);
		} else {
			$('#checkbox_initial_search').prop('checked', true);
		}

		if ('synonyms' in init_display_options)
		{
			// This actually refers to user synonyms
			$('#checkbox-user-thesaurus').prop('checked', init_display_options.synonyms);
			if (init_display_options.synonyms) {
				if ('synonyms_filename' in init_display_options) {
					$('#labelInputThesaurus').text(init_display_options.synonyms_filename);
					$('labelInputThesaurus').removeClass('ss1-disabled');
					$('#btnBrowseThesaurus').removeClass('hidden');
				}
			}
		} else {
			$('#checkbox-user-thesaurus').prop('checked', false);
		}

		if ('show_fields_at_bottom' in init_display_options) {
			console.info('show fields at bottom is set ', init_display_options.show_fields_at_bottom);
			if (typeof init_display_options.show_fields_at_bottom === 'boolean') {
				// Legacy behaviour. True for all fields, false for no fields.
				if (init_display_options.show_fields_at_bottom) {
					$('input[name="radio_show_fields"][value="all"]').prop('checked', true);
				} else {
					$('input[name="radio_show_fields"][value="selected"]').prop('checked', true);
				}
			} else {
				if (init_display_options.show_fields_at_bottom === 'all') {
					$('input[name="radio_show_fields"][value="all"]').prop('checked', true);
				} else {
					// It's an array of field names that should be shown at bottom of document.
					$('input[name="radio_show_fields"][value="selected"]').prop('checked', true);
				}
			}
			console.info($('checkbox[name="show_fields_at_bottom"]'));
			$('input[name="show_fields_at_bottom"]').prop('checked', init_display_options.show_fields_at_bottom);
		} else {
			console.info('show fields at bottom is not set');
			$('input[name="show_fields_at_bottom"]').prop('checked', true);
		}
		evalHVCheckboxesEnabled();

		if ('wordnet_synonyms' in init_display_options)
		{
			$('#checkbox-wordnet').prop('checked', init_display_options.wordnet_synonyms);
		} else {
			$('#checkbox-wordnet').prop('checked', true);
		}

		if ('search_types' in init_display_options) {
			for (let i = 0; i < init_display_options.search_types.length; i++)
			{
				let search_type = init_display_options.search_types[i];
				$('input[name="search_type"][value="' + search_type + '"]').prop('checked', true);
			}
		} else {
			$('input[name="search_type"][value="bool"]').prop('checked', true);
		}

		if ('def_search_type' in init_display_options) {
			$('input[name="def_search_type"][value="' + init_display_options.def_search_type + '"]').prop('checked',true);
		} else {
			$('input[name="def_search_type"][value="bool"]').prop('checked',true);
		}

		$('input[name="def_search_type"]').change(function() {
			let value = $(this).val();
			$('input[name="search_type"][value="' + value + '"]').prop('checked',true);
		});

		$('input[name="radio_filters"]').change(function() {
			evalFiltersCheckboxesEnabled();
		});

		if ('search_for' in init_display_options) {
			$('input[name=radio_sf]').val([init_display_options.search_for]);
		} else {
			$('input[name=radio_sf]').val(['bool']); // default to boolean search
		}

		if ('link_format' in init_display_options) {
			$('#link_format').val(init_display_options.link_format);
		}

		$('#checkbox-wordnet').prop('disabled', ! $('#checkbox-user-thesaurus').prop('checked'));
		$('#input_thesaurus_file').prop('disabled', ! $('#checkbox-user-thesaurus').prop('checked'));

		if ($('#checkbox-wordnet').prop('disabled')) {
			$('#label-wordnet').addClass('ss1-disabled');
			$('#labelInputThesaurus').addClass('ss1-disabled');
			$('#btnBrowseThesaurus').addClass('disabled');
		} else {
			$('#label-wordnet').removeClass('ss1-disabled');
			$('#labelInputThesaurus').removeClass('ss1-disabled');
			$('#btnBrowseThesaurus').removeClass('disabled');
		}

		$('#checkbox-user-thesaurus').change(function() {
			let checked = this.checked;

			$('#input_thesaurus_file').prop('disabled', !checked);
			$('#checkbox-wordnet').prop('disabled', !checked);
			if (checked)
			{
				$('#btnBrowseThesaurus').removeClass('disabled');
				$('#labelInputThesaurus').removeClass('ss1-disabled');
			} else {
				$('#btnBrowseThesaurus').addClass('disabled');
				$('#labelInputThesaurus').addClass('ss1-disabled');
			}

			if ($('#checkbox-wordnet').prop('disabled')) {
				$('#label-wordnet').addClass('ss1-disabled');
			} else {
				$('#label-wordnet').removeClass('ss1-disabled');
			}
		});

		function get_checked_also_show_searchpages() {
			let checked = [];
			$('.search-checkbox:checked').each(function() {
				let post_id = $(this).data('pid');
				checked.push(parseInt(post_id));
			});
			return checked;
		}

		function get_checked_filter_fields() {
			let checked_fields = [];
			$('.filter-checkbox:checked').each(function() {
				let fieldName   = $(this).data('fn');
				checked_fields.push(fieldName);
			});
			return checked_fields;
		}

		function get_checked_facet_fields() {
			let checked_facets = [];
			$('.facet-checkbox:checked').each(function() {
				let facetName = $(this).data('fn');
				checked_facets.push(facetName);
			});
			return checked_facets;
		}

		function get_checked_hv_fields() {
			let checked_fields = [];
			$('.hv-field-checkbox:checked').each(function() {
				let fieldName = $(this).data('fn');
				checked_fields.push(fieldName);
			});
			return checked_fields;
		}

		function escape_html(str) {

			if ((str===null) || (str===''))
				return false;
			else
				str = str.toString();

			var map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};

			return str.replace(/[&<>"']/g, function(m) { return map[m]; });
		}

		function update_fields() {
			let req_data = { page_id : <?php echo esc_js( $sp_post_id ); ?> };
			$.ajax({
				type: 'POST',
				url: '<?php echo( esc_url( rest_url( 'ss1_client/v1/fields' ) ) ); ?>',
				dataType: 'json',
				data: JSON.stringify(req_data),
				contentType: "application/json",
				success: function (response, textStatus, xhr) {
					if (response.hasOwnProperty('success') && !response.success) {
						// Failed.
						console.error('Error fetching fields..');
						update_fields();
						return;
					}
					console.info('Fetched fields successfully', response);
					let detected_fields         = [];
					let detected_facets         = [];
					for(let i = 0; i < response.data.Fields.length; i++)
					{
						let fieldSpec = response.data.Fields[i];
						let dspName = fieldSpec.DisplayName;
						if (dspName === 'ss1-noindex') continue;
						if (!detected_fields.includes(dspName)) { // Prevent duplicates
							detected_fields.push(dspName);
						}

						let dataType = fieldSpec.MetaType;
						if (dataType === 'String' && fieldSpec.hasOwnProperty('ExtraParams') && fieldSpec.ExtraParams != null) {
							try {
								let extraParams = JSON.parse(fieldSpec.ExtraParams);
								if (
									extraParams.hasOwnProperty('SS1-Display')
									&& extraParams['SS1-Display'] === 'Taxonomy'
								) {
									detected_facets.push(dspName);
								}
							} catch (e) {
								console.error('Error parsing ExtraParams', e);
							}
						}
					}

					let is_syncing              = response.data.Syncing;
					let checkedFilterFields     = get_checked_filter_fields();
					let checkedHVFields         = get_checked_hv_fields();
					let checkedFacetFields      = get_checked_facet_fields();
					detected_fields.sort();
					detected_facets.push("Categories");
					detected_facets.push("Tags");
					detected_facets.sort();
					let len = detected_fields.length;
					let filterCheckboxArea = $('#postbox_filter_checkboxes>.inside');
					let hvCheckboxArea     = $('#postbox_hitviewer_shown_fields>.inside');
					let facetCheckboxArea  = $('#postbox_facets_checkboxes>.inside');
					filterCheckboxArea.html('');
					hvCheckboxArea.html('');
					facetCheckboxArea.html('');

					for (let i = 0; i < len; i++) {
						let field_name = detected_fields[i];
						if (field_name.includes("_")) continue;
						if (field_name === "Categories" || field_name === "Tags") continue;
						let isCheckedFilterField = false;
						let isCheckedHVField = false;
						if (!loadedFields) {
							//region Filter fields
							let filter_field_options = init_display_options.fields;
							let filter_field_mode = filter_field_options[0];
							switch (filter_field_mode) {
								case "all":
									isCheckedFilterField = true;
									break;
								case "include":
									for (let f = 0; f < filter_field_options.length; f++)
									{

										if (field_name === filter_field_options[f]) {

											isCheckedFilterField = true;
											break;
										}
									}
									break;
								case "exclude":
									isCheckedFilterField = true;
									for (let i = 0; i < filter_field_options.length; i++)
									{
										if (field_name === filter_field_options[i]) {
											isCheckedFilterField = false;
											break;
										}
									}
									break;
							}
							//endregion
							//region Hit viewer fields
							let hv_fields_mode = $('input[name="radio_show_fields"]:checked').val();
							console.info('hv_fields_mode', hv_fields_mode);
							switch (hv_fields_mode) {
								case 'selected':
									let shown_fields;
									if ('show_fields_at_bottom' in init_display_options) {
										if (typeof init_display_options.show_fields_at_bottom === 'boolean') {
											shown_fields = [];
										} else {
											if (init_display_options.show_fields_at_bottom === 'all') {
												isCheckedHVField = true;
											} else {
												shown_fields = init_display_options.show_fields_at_bottom;
											}
										}
									}
									//console.info('shown_fields', shown_fields);
									isCheckedHVField = shown_fields.some(element => element === field_name);
									break;
								default:
									isCheckedHVField = true;
									break;
							}
							//endregion
						} else {
							isCheckedFilterField = checkedFilterFields.some(element => element === field_name);
							isCheckedHVField = checkedHVFields.some(element => element === field_name);

						}

						if ($('input[name="radio_show_fields"][value="all"]').prop('checked'))
						{
							isCheckedHVField = true;
						}




						if (!isCheckedFilterField) {
							filterCheckboxArea.append('<label><input type="checkbox" class="filter-checkbox"><span>' + field_name +'</span></label><br>');
						} else {
							filterCheckboxArea.append('<label><input type="checkbox" class="filter-checkbox" checked><span>' + field_name + '</span></label><br>');
						}
						if (!isCheckedHVField) {
							hvCheckboxArea.append('<label><input type="checkbox" class="hv-field-checkbox"><span>' + field_name +'</span></label><br>');
						} else {
							hvCheckboxArea.append('<label><input type="checkbox" class="hv-field-checkbox" checked><span>' + field_name +'</span></label><br>');
						}
						$('.filter-checkbox').last().data('fn',field_name);
						$('.hv-field-checkbox').last().data('fn', field_name);
						$('.filter-checkbox').last().children('span').first().text(escape_html(field_name));
						$('.hv-field-checkbox').last().children('span').first().text(escape_html(field_name));
					}

					len = detected_facets.length;
					for (let i = 0; i < len; i++) {
						let facet_name = detected_facets[i];
						let isCheckedFacetField = false;
						if (!loadedFacets)
						{
							if (init_display_options.hasOwnProperty('facets')) {
								let facet_field_options = init_display_options.facets;
								let facet_field_mode = facet_field_options[0];
								switch(facet_field_mode) {
									case "all":
										isCheckedFacetField = true;
										break;
									case "selected":
										for (let f = 0; f < facet_field_options.length; f++)
										{
											if (facet_name === facet_field_options[f]) {
												isCheckedFacetField = true;
												break;
											}
										}
										break;
								}
							}

						} else {
							isCheckedFacetField = checkedFacetFields.some(element => element === facet_name);
						}
						if (!isCheckedFacetField) {
							facetCheckboxArea.append('<label><input type="checkbox" class="facet-checkbox"><span>' + facet_name +'</span></label><br>');
						} else {
							facetCheckboxArea.append('<label><input type="checkbox" class="facet-checkbox" checked><span>' + facet_name + '</span></label><br>');
						}
						$('.facet-checkbox')
							.last()
							.data('fn',facet_name);
						if (isCheckedFacetField) {
							$('.facet-checkbox').last().attr('checked', 'checked');
						}
					}

					loadedFacets = true;

					evalHVCheckboxesEnabled();
					evalFiltersCheckboxesEnabled();
					evalFacetsCheckboxesEnabled();


					loadedFields = true;

					if (detected_fields.length === 0) {
						filterCheckboxArea.html('<i>No fields detected</i>');
						hvCheckboxArea.html('<i>No fields detected</i>');
					}
					if (detected_facets.length === 0) {
						facetCheckboxArea.html('<i>No facets detected</i>');
					}
					if (is_syncing) {
						$('#still-indexing-warning').removeClass('hidden');
						$('#still-indexing-warning-2').removeClass('hidden');
						$('#still-indexing-warning-3').removeClass('hidden');
					} else {
						$('#still-indexing-warning').addClass('hidden');
						$('#still-indexing-warning-2').addClass('hidden');
						$('#still-indexing-warning-3').addClass('hidden');
					}
					setTimeout(update_fields, 1000 * 3); // Refresh the list in 3s
				},
				error: function (data, textStatus, xhr) {
					console.error('Failed to fetch fields', data);
					setTimeout(update_fields, 1000 * 6); // Refresh the list in 6s
				}
			});
		}

		let synonyms_base64 = null;




		update_fields();

		let buttonSave              = document.getElementById('ss1-btn-save');
		buttonSave.addEventListener('click', onClickSave);
		let radioFiltersAll         = document.getElementById('radio_filters_all');
		let radioFiltersInclude     = document.getElementById('radio_filters_selected');
		let radioFiltersExclude     = document.getElementById('radio_filters_exclude');
		let buttonSaveValidation    = document.getElementById('save-error');
		let checkboxOpenNewWindow   = document.getElementById('checkbox_results_new_window');
		let radioResultsOriginal    = document.getElementById('radio_results_original');
		let radioResultsHitViewer   = document.getElementById('radio_results_hitViewer');

		$('#input_thesaurus_file').change(function(e) {
			$('#labelInputThesaurus').text(e.target.files[0].name);
			let reader = new FileReader();
			let selectedFile = e.target.files[0];

			reader.onload = function () {
				let comma = this.result.indexOf(',');
				synonyms_base64 = this.result.substr(comma + 1);
				console.log(synonyms_base64);
			}
			reader.readAsDataURL(selectedFile);
		});

		function evalFiltersCheckboxesEnabled() {
			let enable_filter_checkboxes = $('input[name="radio_filters"]:checked').val() !== 'all';
			$('.filter-checkbox').prop('disabled', !enable_filter_checkboxes);
			$('.filter-checkbox').each(function() {
				let txt = $(this).parent().find('span');
				if (!txt.length ) console.error('Txt element not found!');
				if (enable_filter_checkboxes) txt.removeClass('ss1-disabled');
				else txt.addClass('ss1-disabled');
			});
		}

		function evalHVCheckboxesEnabled() {
			let enable_hv_checkboxes = (
				$('#radio_results_hitViewer').prop('checked')
				&& $('input[name="radio_show_fields"]:checked').val() === 'selected'
			);
			$('input.hv-field-checkbox').prop('disabled', !enable_hv_checkboxes);
			$('input.hv-field-checkbox').each(function() {
				let txt = $(this).parent().find('span');
				if (enable_hv_checkboxes) txt.removeClass('ss1-disabled');
				else txt.addClass('ss1-disabled');
			});
			$('input[name="radio_show_fields"]').each(function() {
				let enabled = $('#radio_results_hitViewer').prop('checked');
				$(this).prop('disabled', !enabled);
				if (enabled) {
					$(this).closest('span').removeClass('ss1-disabled');
				} else {
					$(this).closest('span').addClass('ss1-disabled');
				}
			});
		}

		function evalFacetsCheckboxesEnabled() {
			let enable_checkboxes = $('input[name="radio_facets"]:checked').val() === 'selected';
			$('input.facet-checkbox').prop('disabled', !enable_checkboxes);
			$('input.facet-checkbox').each(function() {
				let txt = $(this).parent().find('span');
				if (enable_checkboxes) txt.removeClass('ss1-disabled');
				else txt.addClass('ss1-disabled');
			})
		}

		function onClickSave() {

			buttonSaveValidation.innerText = '';
			buttonSave.innerText = "<?php echo esc_js( __( 'Saving...', 'site-search-one' ) ); ?>";
			spinnerSaving.classList.add('is-active');
			buttonSave.disabled = true;


			if ($('#checkbox-user-thesaurus').prop('checked'))
			{
				if (synonyms_base64 !== null)
				{
					console.info('Uploading Synonyms', synonyms_base64);
					// Setting a new synonyms file on SC1
					upload_synonyms_local(
						function(filename) { // Success
						synonyms_path = filename;
						console.info('Uploaded Synonyms. Saving options.')
						save_display_opts(synonyms_path);
					},
						function() { // Failure
							spinnerSaving.classList.remove('is-active');
							buttonSaveValidation.innerText = '<?php echo esc_js( __( 'Something went wrong. Check your connection and try again.', 'site-search-one' ) ); ?>';
							buttonSave.disabled = false;
							buttonSave.innerText = "<?php echo esc_js( __( 'Save', 'site-search-one' ) ); ?>";
					});
				} else {
					if (!'synonyms' in init_display_options ||! init_display_options.synonyms)
					{
						buttonSaveValidation.innerText = '<?php echo esc_js( __( 'Select Synonyms file', 'site-search-one' ) ); ?>';
						buttonSave.innerText = "<?php echo esc_js( __( 'Save', 'site-search-one' ) ); ?>";
						spinnerSaving.classList.remove('is-active');
						buttonSave.disabled = false;
						return;
					} else {
						// Not changing the synonyms file
						save_display_opts(synonyms_path);
					}
				}
			} else {
				if ('synonyms' in init_display_options && init_display_options.synonyms)
				{
					// Synonyms unchecked but previously uploaded a synonyms file - remove it
					console.info('Removing existing Synonyms');
					synonyms_path = '';
					remove_synonyms(function() {
						console.info('Removed Synonyms. Saving options.')
						save_display_opts(synonyms_path);
					}, function() {
						buttonSaveValidation.innerText = '<?php echo esc_js( __( 'Something went wrong. Check your connection and try again.', 'site-search-one' ) ); ?>';
						buttonSave.innerText = "<?php echo esc_js( __( 'Save', 'site-search-one' ) ); ?>";
						spinnerSaving.classList.remove('is-active');
						buttonSave.disabled = false;
					})
				} else {
					save_display_opts(synonyms_path);
				}
			}

		}

		/**
		 * Save the options
		 * @param synonyms_path
		 * Path to the saved Synonyms file. In case of index recreate, the file at this
		 * path will be used to set the synonyms file on the new index.
		 */
		function save_display_opts(synonyms_path)
		{
			buttonSaveValidation.innerText = '';
			//region Filters Validation/Options retrieval from UI
			//Validate that at least one radio-box is checked
			let allFields = radioFiltersAll.checked;
			let selectedFields = radioFiltersInclude.checked;
			let excludeFields = radioFiltersExclude.checked;
			if (!allFields && !selectedFields && !excludeFields) {
				buttonSave.innerText = "<?php esc_html_e( 'Save', 'site-search-one' ); ?>";
				spinnerSaving.classList.remove('is-active');
				buttonSave.disabled = false;
				buttonSaveValidation.innerText = '<?php esc_html_e( 'Must select a filters drop-down option', 'site-search-one' ); ?>';
				return;
			}
			// Set options based on UI
			let filtersMode = "all";
			if (selectedFields) filtersMode = "include";
			if (excludeFields) filtersMode = "exclude";
			let filtersOptions = [];
			filtersOptions.push(filtersMode);
			let facetOptions = [];
			let facetsMode = $('input[type="radio"][name="radio_facets"]:checked').val();

			facetOptions.push(facetsMode);
			let checked_fields = get_checked_filter_fields();
			for (let i = 0; i < checked_fields.length; i++)
			{
				filtersOptions.push(checked_fields[i]);
			}
			let checked_facet_fields = get_checked_facet_fields();
			for (let i = 0; i < checked_facet_fields.length; i++)
			{
				facetOptions.push(checked_facet_fields[i]);
			}
			//alert('facets mode ' + facetsMode);
			//endregion
			//region Handle page link options
			// Validate that one of the result link behaviour radio buttons is checked
			let resultsOpenHitViewer = radioResultsHitViewer.checked;
			let resultsOpenOriginal = radioResultsOriginal.checked;
			if (!resultsOpenHitViewer && !resultsOpenOriginal)
			{
				buttonSave.innerText = "<?php echo esc_js( __( 'Save', 'site-search-one' ) ); ?>";
				spinnerSaving.classList.remove('is-active');
				buttonSave.disabled = false;
				buttonSaveValidation.innerText = '<?php echo esc_js( __( 'Must choose how results open', 'site-search-one' ) ); ?>';
				return;
			}
			let linkOpens = "original_page";
			if (resultsOpenHitViewer) linkOpens = "hit_viewer";
			let opensInNewWindow = checkboxOpenNewWindow.checked;
			let linkBehaviour = "current_window";
			if (opensInNewWindow) linkBehaviour = "new_window";
			//endregion
			//region Validate search types
			let search_types = [];
			$('input[name="search_type"]:checked').each(function() {
				search_types.push($(this).val());
			});
			if (search_types.length === 0)
			{
				buttonSave.innerText = "<?php echo esc_js( __( 'Save', 'site-search-one' ) ); ?>";
				spinnerSaving.classList.remove('is-active');
				buttonSave.disabled = false;
				buttonSaveValidation.innerText = '<?php echo esc_js( __( 'Must choose at least one search type', 'site-search-one' ) ); ?>';
				return;
			}
			let def_search_type = $('input[name="def_search_type"]:checked').val();
			//endregion
			//region All validated OK. Save the settings
			buttonSave.innerText = "<?php echo esc_js( __( 'Saving...', 'site-search-one' ) ); ?>";
			spinnerSaving.classList.add('is-active');
			buttonSave.disabled = true;

			let ix_name = $('#ss1-index-name').val();

			let search_all_text = 'Search all';

			if ($('#search-all-text').val() !== '')
			{
				search_all_text = $('#search-all-text').val();
			}

			let search_all_option = 'hidden';
			if ($('#search-all-checkbox').prop('checked'))
			{
				search_all_option = 'default';
			}

			let hide_searchbar = $('#checkbox_hide_searchbar').prop('checked');

			let wordnet_synonyms = true;
			if ($('#checkbox-user-thesaurus').prop('checked')) {
				wordnet_synonyms = $('#checkbox-wordnet').prop('checked');
			}

			let synonyms_filename = '';
			if ($('#checkbox-user-thesaurus').prop('checked'))
			{
				synonyms_filename = $('#labelInputThesaurus').text();
			}

			console.info('show fields at bottom', $('input[name="show_fields_at_bottom"]'));
			console.info('checked ', $('input[name="show_fields_at_bottom"]').prop('checked'));

			let show_fields_at_bottom = 'all';
			if ($('input[name="radio_show_fields"][value="selected"]').prop('checked')) {
				show_fields_at_bottom = [];
				$('input.hv-field-checkbox:checked').each(function() {
					let field_name = $(this).parent().find('span').text();
					show_fields_at_bottom.push(field_name);
				});
			}

			let options = {
				fields: filtersOptions,
				facets: facetOptions,
				link_behaviour: linkBehaviour,
				link_opens: linkOpens,
				also_shown: get_checked_also_show_searchpages(),
				ix_name: ix_name,
				search_all_option: search_all_option,
				search_all_text: search_all_text,
				hide_searchbar: hide_searchbar,
				stemmingDefault: $('input[value="stemming-default"]').prop('checked'),
				synonymsDefault: $('input[value="synonyms-default"]').prop('checked'),
				stemmingChoice:  $('input[value="synonyms-choice"]').prop('checked'),
				synonymsChoice:  $('input[value="stemming-choice"]').prop('checked'),
				hideSynonymsAndStemming: $('#checkbox_hide_synonyms_stemming').prop('checked'),
				synonyms: $('#checkbox-user-thesaurus').prop('checked'),
				wordnet_synonyms: wordnet_synonyms,
				initialSearch: $('#checkbox_initial_search').prop('checked'),
				search_types: search_types,
				def_search_type: def_search_type,
				synonyms_filename: synonyms_filename,
				show_fields_at_bottom: show_fields_at_bottom,
				synonyms_path: synonyms_path,
				custom_css: $('textarea#custom_css').val(),
				document_css: $('textarea#document_css').val(),
				link_format: $('#link_format').val()
			};

			let data = {
				set_display_opts : {
					post_id: <?php echo esc_js( $sp_post_id ); ?>,
					opts: options
				}
			};

			console.info('data', data);
			$.ajax({
				type: 'POST',
				url: '<?php echo( esc_url( rest_url( 'ss1_client/v1/options' ) ) ); ?>',
				dataType: 'json',
				data: JSON.stringify(data),
				contentType: "application/json",
				success: function (data, textStatus, xhr) {
					buttonSave.innerText = "<?php echo esc_js( __( 'Saved', 'site-search-one' ) ); ?>";
					window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=' . trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) ) ) . 'view-search-pages.php'; ?>'
					return;
				},
				error: function (data, textStatus, xhr) {
					spinnerSaving.classList.remove('is-active');
					buttonSaveValidation.innerText = '<?php echo esc_js( __( 'Something went wrong. Check your connection and try again.', 'site-search-one' ) ); ?>';
					buttonSave.disabled = false;
					buttonSave.innerText = "<?php echo esc_js( __( 'Save', 'site-search-one' ) ); ?>";
					return;
				}
			});
			//endregion
		}

		function upload_synonyms_local(success, error) {

			//The synonyms file needs to be saved locally, as when rebuilding the index it is lost on SC1
			let data = {
				save_user_defined_synonyms : {
					synonyms_base64: synonyms_base64
				}
			}
			$.ajax({
				type: 'POST',
				url: '<?php echo( esc_url( rest_url( 'ss1_client/v1/options' ) ) ); ?>',
				dataType: 'json',
				data: JSON.stringify(data),
				contentType: "application/json",
				success: function(data, textStatus, xhr) {
					// Successfully uploaded a local copy of the Synonyms file. Now need to
					// Upload it to SC1.
					console.info('Response', data);
					if (data.success === true) {
						let filename = data.filename;
						upload_synonyms_sc1(function() {
							success(filename);
						}, function() {
							error();
						})
					} else {
						error();
					}
				}, error : function() {
					error();
				}
			});

		}

		function upload_synonyms_sc1(success, error) {
			<?php
			require_once plugin_dir_path( __FILE__ ) . 'class-sc1-index-manager.php';
			$api_key = SC1_Index_Manager::get_sc1_api_key();
			?>
			let api_key = '<?php echo esc_js( $api_key ); ?>';
			let ix_uuid = '<?php echo esc_js( $editing_search_page->get_sc1_ix_uuid() ); ?>';
			let request_parameters = {
				APIKey: api_key,
				Action: 'SetUserDefinedSynonyms',
				IndexUUID: ix_uuid,
				UserDefinedSynonyms: synonyms_base64
			};
			let endpoint = '<?php echo esc_url( get_transient( 'ss1-endpoint-url' ) ); ?>/Indexes';
			$.ajax({
				type: 'POST',
				url: endpoint,
				dataType: 'json',
				data: JSON.stringify(request_parameters),
				contentType: 'application/json',
				success: function (data, textStatus, xhr) {
					success();
				}, error: function () {
					error();
				}
			});
		}

		function remove_synonyms(success, error) {
			synonyms_base64 = null;
			synonyms_path = null;
			upload_synonyms_sc1(success, error);
		}

	})(jQuery);
</script>
