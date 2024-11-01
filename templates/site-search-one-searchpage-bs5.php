<?php
/**
 * Template Name: Site Search ONE - Search Page
 * Template Post Type: ss1_serp
 *
 * @package Site_Search_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $_GET['post_id'] ) ) {
	$sp_post_id = intval( sanitize_text_field( wp_unslash( $_GET['post_id'] ) ) );
} else {
	die( esc_html( __( 'Parameter Missing', 'site-search-one' ) ) );
}

if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'view-search-page-' . $sp_post_id ) ) {
	// Validate that a valid nonce for viewing this page was passed to prevent the php file being accessed directly.
	$current_user_id = get_current_user_id();
} else {
	if ( get_post_status($sp_post_id) !== 'publish' )  // The publish status means visible to everyone (public)
	{
		die( esc_html( __( 'Invalid Nonce', 'site-search-one' ) ) );
	}
}

global $wpdb;

$log_to = false;

/**
 * Attempt to require premium analytics, if premium plugin is installed.
 *
 * @return true|WP_Error
 */
function try_require_premium_analytics_2() {
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

$premium = try_require_premium_analytics_2();
if ( ! is_wp_error( $premium ) ) {
	$premium        = true;
	$site_log_group = Site_Search_One_Search_Analytics::get_site_sc1_log_group_id( false ); // Don't try to create it at this point, would slow page load.
	if ( ! is_wp_error( $site_log_group ) ) {
		$log_to   = array();
		$log_to[] = $site_log_group;
	}
} else {
	$premium = false;
}

require_once plugin_dir_path( __FILE__ ) . '../admin/class-site-search-one-tokens.php';
$token = Site_Search_One_Tokens::issue_token( 1, false );
if ($token === null) {
	$token = 'invalid'; // At least allow search page to load. Fetch a valid token after initial load.
}
if ( $sp_post_id ) {
	// Page is set correctly. We now proceed to fetch what Categories should be shown on this page.
	require_once plugin_dir_path( __FILE__ ) . '../admin/class-site-search-one-search-page.php';
	$current_search_page = Site_Search_One_Search_Page::get_search_page( $sp_post_id );
	$display_opts        = $current_search_page->get_display_opts();
	$also_search_pages   = $current_search_page->get_also_shown_searchpages();
	$all_search_pages    = array();
	$all_search_pages[]  = $current_search_page;

	$all_search_pages = array_merge( $all_search_pages, $also_search_pages );
	/**
	 * Sort search pages on display name.
	 *
	 * @param Site_Search_One_Search_Page $search_page_a Search page A.
	 * @param Site_Search_One_Search_Page $search_page_b Search page B.
	 */
	function ss1_display_name_sort( $search_page_a, $search_page_b ) {
		$name_a = strtolower( $search_page_a->get_ix_name() );
		$name_b = strtolower( $search_page_b->get_ix_name() );
		if ( $name_a === $name_b ) {
			return 0;
		}
		return ( $name_a > $name_b ) ? +1 : -1;
	}
	usort( $all_search_pages, 'ss1_display_name_sort' );
} else {

	// Page not set.
	?>
	<!DOCTYPE html>
	<html lang="en">
	<head>
		<title> Misconfiguration </title>
	</head>
	<body><h1>This page is not configured correctly. Contact your site administrator</h1></body>
	</html>
	<?php
	exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Search Page</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- Print Scripts -->
	<?php
	$plugin_ver = SITE_SEARCH_ONE_VERSION;
	wp_enqueue_script( 'site-search-one-serp_jquery', includes_url( 'js/jquery/jquery.min.js' ), array(), $plugin_ver, false );
	wp_enqueue_script( 'site-search-one-serp_bootstrap-datepicker', plugins_url( 'js/bootstrap-datepicker.min.js', __FILE__ ), array(), $plugin_ver, false );
	wp_enqueue_style( 'site-search-one-serp_bootstrap-datepicker', plugins_url( '/css/bootstrap-datepicker.min.css', __FILE__ ), array(), $plugin_ver );
	wp_enqueue_style( 'site-search-one-serp_search', plugins_url( 'css/search-bs5.css', __FILE__ ), array(), $plugin_ver );
	wp_enqueue_script( 'site-search-one-serp_moment', includes_url( 'js/dist/vendor/moment.js' ), array(), $plugin_ver, false );
	wp_enqueue_script( 'site-search-one-serp_popper', plugins_url( 'js/popper.min.js', __FILE__ ), array(), $plugin_ver, false );
	wp_enqueue_script( 'site-search-one-serp_bootstrap', plugins_url( 'js/bootstrap/5.2.3/bootstrap.min.js', __FILE__ ), array(), $plugin_ver, false );
	wp_enqueue_style( 'site-search-one-serp_bootstrap', plugins_url( 'css/bootstrap/5.2.3/bootstrap.min.css', __FILE__ ), array(), $plugin_ver );
	wp_enqueue_style( 'site-search-one-serp_bootstrap-icons', plugins_url( 'css/bootstrap-icons-1.10.2/bootstrap-icons.css', __FILE__ ), array(), $plugin_ver );
	wp_print_head_scripts();
	if ( property_exists( $display_opts, 'custom_css' ) && gettype( $display_opts->custom_css ) === 'string' ) {
		wp_add_inline_style( 'site-search-one-serp_search', $display_opts->custom_css );
	}
	$table_name = $wpdb->prefix . 'ss1_globals';
	$query      = "SELECT value FROM $table_name WHERE setting = 'global_css_search_pages'";
	// phpcs:disable WordPress.DB.DirectDatabaseQuery
	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
	$global_css = $wpdb->get_var( $query );
	// phpcs:enable
	wp_enqueue_style( 'site-search-one-serp_global-css', false, array(), $plugin_ver );
	if ( $global_css ) {
		wp_add_inline_style( 'site-search-one-serp_search', $global_css );
	}
	?>
	<!-- Print Styles -->
	<?php
	wp_print_styles();
	?>
</head>
<body>
	<!-- Templates -->
	<template id="template-datepicker">
		<span class="input-daterange" id="datepicker" data-date-end-date="0d" data-date-format="d M yyyy" data-date-today-highlight="true" data-date-today-btn="linked" data-date-immediate-updates="true" data-date-assume-nearby-year="true">
			<span class="input-group" style="border-radius: 0; width: 100%">
				<input type="text" class="form-control sc1-date-control sc1-date-from" name="from" placeholder="<?php esc_attr_e( 'From', 'site-search-one' ); ?>"
					style="border-radius: 0" data-date-end-date="0d" data-date-format="dd/mm/yyyy" data-date-today-highlight="true" data-date-today-btn="true" data-date-immediate-updates="true" data-date-assume-nearby-year="true"/>
				<i class="bi bi-calendar-fill form-control-feedback"></i>
			</span>
			<span class="input-group" style="border-radius: 0; width: 100%">
				<input type="text" class="form-control sc1-date-control sc1-date-to" name="to" placeholder="<?php esc_attr_e( 'To', 'site-search-one' ); ?>"
					style="border-radius: 0" data-date-end-date="0d" data-date-format="dd/mm/yyyy" data-date-today-highlight="true" data-date-today-btn="true" data-date-immediate-updates="true" data-date-assume-nearby-year="true"/>
				<i class="bi bi-calendar-fill form-control-feedback"></i>
			</span>
		</span>
	</template>
	<template id="template-facet-checkbox">
		<!-- TODO Checkboxes for Facets-->
		<div class="form-check">
			<input class="form-check-input" type="checkbox" value="">
			<label class="form-check-label"><span class="field-value"></span></label>
		</div>
	</template>
	<?php
	$searchbar_hidden = '';
	if ( property_exists( $display_opts, 'hide_searchbar' ) && true === $display_opts->hide_searchbar ) {
		$searchbar_hidden = ' hidden';
	}
	$stemming_syonyms_hidden = '';
	if ( property_exists( $display_opts, 'hideSynonymsAndStemming' ) ) {
		// Old options format.
		//phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( true === $display_opts->hideSynonymsAndStemming ) {
			$stemming_syonyms_hidden = ' hidden';
		}
		//phpcs:enable
	}

	$all_hidden = '';
	if ( $searchbar_hidden && $stemming_syonyms_hidden ) {
		$all_hidden = ' hidden';
	}
	?>
	<div id="container-search-options" class="mx-2 mb-4<?php echo esc_attr( $all_hidden ); ?>">
		<div id="search-bar" class="input-group<?php echo esc_attr( $searchbar_hidden ); ?>">
			<?php
			if ( count( $all_search_pages ) > 1 ) {
				?>
			<select
					class="form-select index-selector"
					aria-label="<?php esc_attr_e( 'Select Index', 'site-search-one' ); ?>"
					id="select-index"
			>
				<?php
				$search_all_opt  = $display_opts->search_all_option;
				$search_all_text = $display_opts->search_all_text;
				if ( null === $search_all_text || '' === $search_all_text ) {
					$search_all_text = 'Search all';
				}
				if ( null === $search_all_opt ) {
					$search_all_opt = 'default';
				}
				if ( 'hidden' !== $search_all_opt ) {
					// Only show the Search all option if not hidden.
					$selected = '';
					if ( 'default' === $search_all_opt ) {
						$selected = ' selected=selected';
					}
					?>
					<option value="all" data-ix-uuid="all" data-ix-id="all" selected="selected">
						<?php echo esc_html( $search_all_text ); ?>
					</option>
					<?php
				}
				foreach ( $all_search_pages as $search_page ) {
					// region Facets - If filtering by category and only one category selected, only show the category children.
					$facets_ignored_parent_category = '';
					$single_cat                     = $search_page->indexes_one_category();
					if ( false !== $single_cat ) {
						$facets_ignored_parent_category = get_cat_name( $single_cat );
					}
					$selected = '';
					if ( 'default' !== $search_all_opt
						&& intval( $search_page->get_post_id() ) === intval( $sp_post_id ) ) {
						// If search all is not default, and this search page matches the post id we're viewing
						// Select this option by default.
						$selected = ' selected="selected"';
					}
					// endregion.
					?>
					<option value="<?php echo esc_attr( $search_page->get_post_id() ); ?>" data-ix-uuid="<?php echo esc_attr( $search_page->get_sc1_ix_uuid() ); ?>" data-ix-id="<?php echo esc_attr( $search_page->get_sc1_ix_id() ); ?>" data-parent-cat="<?php echo esc_attr( $facets_ignored_parent_category ); ?>" <?php echo esc_attr( $selected ); ?>>
						<?php echo esc_html( $search_page->get_ix_name() ); ?>
					</option>
					<?php
				}
				?>
			</select>
				<?php
			}
			?>
			<input
					type="text"
					title="Powered by SiteSearchONE"
					class="form-control"
					aria-label="<?php esc_attr_e( 'Search Box', 'site-search-one' ); ?>"
					placeholder="<?php esc_attr_e( 'Search', 'site-search-one' ); ?>"
					id="textbox-query"
			>
			<button
				id="btn-filter-panel-toggle"
				type="button"
				class="btn"
				data-bs-toggle="collapse"
				data-bs-target="#filter-panel"
				>
				<span class="badge bg-secondary" id="filterCountBadge" style="display: none">0</span>
				<i class="bi bi-funnel"></i>
			</button>
			<button
					id="btn-search"
					type="button"
					class="btn"
					title="Powered by SiteSearchONE"
					aria-label="<?php esc_attr_e( 'Search', 'site-search-one' ); ?>"
			>
				<i class="bi bi-search"></i>
			</button>
		</div>
		<div id="search-bar-mobile" class="input-group">

		</div>
		<div id="query-options-container" class="m-2">
			<div id="stemming-synonyms-container" class="d-flex justify-content-center <?php echo esc_attr( $stemming_syonyms_hidden ); ?>">
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="checkbox" id="check-box-stemming">
					<label class="form-check-label" for="check-box-stemming">
						<?php esc_html_e( 'Stemming', 'site-search-one' ); ?>
					</label>
				</div>
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="checkbox" id="check-box-synonyms">
					<label class="form-check-label" for="check-box-synonyms">
						<?php esc_html_e( 'Synonyms', 'site-search-one' ); ?>
					</label>
				</div>
			</div>
			<div id="search-type-container" class="d-flex justify-content-around">
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" name="search-type" id="search-type-any" value="any">
					<label class="form-check-label" for="search-type-any">
						<?php esc_html_e( 'Any words', 'site-search-one' ); ?>
					</label>
				</div>
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" name="search-type" id="search-type-all" value="all">
					<label class="form-check-label" for="search-type-all">
						<?php esc_html_e( 'All words', 'site-search-one' ); ?>
					</label>
				</div>
				<div class="form-check form-check-inline">
					<input class="form-check-input" type="radio" name="search-type" id="search-type-bool" value="bool">
					<label class="form-check-label" for="search-type-bool">
						<?php esc_html_e( 'Boolean', 'site-search-one' ); ?>
					</label>
				</div>
			</div>
		</div>
	</div>
	<div id="filter-panel" class="collapse card mx-2 mb-4">
		<div class="card-body" id="filter-panel-controls">

		</div>
		<div class="card-footer">
			<button class="btn btn-primary"
					data-bs-toggle="collapse"
					data-bs-target="#filter-panel"
					id="btn-filter-apply">
				<?php esc_html_e( 'Apply', 'site-search-one' ); ?>
			</button>
			<button class="btn btn-default disabled"
					id="btn-filter-clear">
				<?php esc_html_e( 'Clear Filter(s)', 'site-search-one' ); ?>
			</button>
		</div>
	</div>
	<div id="container-search-outcome" class="d-flex flex-column flex-md-row">
		<div class="m-2" id="container-facet-panel">
			<!-- Facets Populate this area via js-->
		</div>
		<div class="m-2 flex-grow-1" id="container-results-panel">
			<div class="d-flex flex-row justify-content-between m-2 mb-4" id="container-info-and-sort">
				<div id="info-display"></div>
				<div class="dropdown">
					<button class="btn dropdown-toggle" type="button"
							id="dropdown-sort-order" data-bs-toggle="dropdown" aria-expanded="false">
						<i class="bi bi-arrow-down-up"></i>
						<?php esc_html_e( 'Sort', 'site-search-one' ); ?>
					</button>
					<ul class="dropdown-menu" aria-labelledby="dropdown-sort-order">
						<li>
							<a class="dropdown-item sort-item" href="javascript:void(0)" data-sort="none">
								<i class="bi bi-arrow-up"></i>
								<?php esc_html_e( 'By Relevance', 'site-search-one' ); ?>
							</a>
						</li>
						<li><a class="dropdown-item sort-item" href="javascript:void(0)" data-sort="a-z">
								<i class="bi bi-sort-alpha-down"></i>
								<?php esc_html_e( 'Alphabetical (A-Z)', 'site-search-one' ); ?>
							</a>
						</li>
						<li><a class="dropdown-item sort-item" href="javascript:void(0)" data-sort="z-a">
								<i class="bi bi-sort-alpha-down-alt"></i>
								<?php esc_html_e( 'Alphabetical (Z-A)', 'site-search-one' ); ?>
							</a>
						</li>
						<li><a class="dropdown-item sort-item" href="javascript:void(0)" data-sort="modified-desc">
								<i class="bi bi-calendar-fill"></i>
								<?php esc_html_e( 'Published (Newest first)', 'site-search-one' ); ?>
							</a>
						</li>
						<li><a class="dropdown-item sort-item" href="javascript:void(0)" data-sort="modified-asc">
								<i class="bi bi-calendar-fill"></i>
								<?php esc_html_e( 'Published (Oldest first)', 'site-search-one' ); ?>
							</a>
						</li>
					</ul>
				</div>
			</div>
			<div id="search-results-container">
				<!-- Search Results Populate this area -->

			</div>
			<nav
					id="nav-result-pagination" class="hidden"
					aria-label="<?php esc_attr_e( 'Results Navigation', 'site-search-one' ); ?>"
			>
				<ul class="pagination">
					<li class="page-item" id="page-first">
						<a class="page-link" href="javascript:void(0)"
						aria-label="<?php esc_attr_e( 'First', 'site-search-one' ); ?>"
						>
							<i class="bi bi-chevron-bar-left"></i>
						</a>
					</li>
					<li class="page-item" id="page-previous">
						<a class="page-link" href="javascript:void(0)"
						aria-label="<?php esc_attr_e( 'Previous', 'site-search-one' ); ?>"
						>
							<i class="bi bi-chevron-left"></i>
						</a>
					</li>
					<li class="page-item" id="page-next">
						<a class="page-link" href="javascript:void(0)"
						aria-label="<?php esc_attr_e( 'Next', 'site-search-one' ); ?>"
						>
							<i class="bi bi-chevron-right"></i>
						</a>
					</li>
					<li class="page-item" id="page-last">
						<a class="page-link" href="javascript:void(0)"
						aria-label="<?php esc_attr_e( 'Next', 'site-search-one' ); ?>"
						>
							<i class="bi bi-chevron-bar-right"></i>
						</a>
					</li>
				</ul>
			</nav>
		</div>
	</div>
	<div class="modal" id="modal-facets-lv" tabindex="-1" role="dialog" data-backdrop="false">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Modal title</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<ul id="ul-facets-modal" class="list-unstyled" style="column-count: 3;">
						<!-- Facets go here -->
					</ul>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-primary" data-bs-dismiss="modal">
						<?php esc_html_e( 'Close', 'site-search-one' ); ?>
					</button>
				</div>
			</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
</body>
<script type="application/javascript">

	jQuery(document).ready(function( $ )
	{
		// On page load, hide next/prev buttons as no results yet.
		$('#page-first, #page-previous, #page-next, #page-last').hide();

		let current_page = 1;
		let last_page = 1;
		const resultsLoaded = new Event('ResultsLoaded');
		let sortBy = "none";

		// Maximum values per field on the sidebar displayed when results has enumerable fields
		// If there are more than this number, the additional ones will be available from the modal dialog
		// This number should be one less than CSS Rule marked in search.css as /* MAX_VALUES_PER_FACET_DISPLAYED */
		const MAX_VALUES_PER_FACET_DISPLAYED = 5;
		// This number should be one less the CSS Rule marked in search.css as /* MAX_VALUES_PER_FACET_DISPLAYED_620 */
		const MAX_VALUES_PER_FACET_DISPLAYED_620 = 3;

		function get_filter_count() {
			return $('.filter-checkbox:checked').length;
		}

		$('#btn-filter-apply').click(function() {
			apply_filters(true);
		});

		$('#check-box-stemming').change(function() {
			display_results(true);
		});

		$('#check-box-synonyms').change(function() {
			display_results(true);
		});

		function apply_filters(displayResults) {
			let filterCount = get_filter_count();
			let badge = $('#filterCountBadge');

			if (filterCount > 0)    $(badge).css('display','inline-block').html(filterCount);
			else                    $(badge).css('display','none').html('');
			current_page = 1;
			if (displayResults) display_results(true);
			update_clear_filters_btn_vis();
		}

		$('#btn-filter-clear').click(function() {
			clear_filters();
		});


		/**
		 * Null when not yet loaded. Must call load_filters
		 */
		let all_fields = null;

		/**
		 * List of field names that are multiple-choice drop down select fields.
		 * These fields should only be displayed in the filters panel
		 * and not on the facets panel, despite being enumerable.
		 */
		let multi_choice_fields = [];


		function getMinutesBetweenDates(startDate, endDate) {
			let diff = endDate.getTime() - startDate.getTime();
			return (diff / 60000);
		}


		function get_cached_xfirstword_responses(ix_uuid)
		{
			console.info('finding cached xfirstword responses', ix_uuid);
			<?php
			$responses = array();
			foreach ( $all_search_pages as $search_page ) {
				$responses[] = array(
					'index_uuid' => $search_page->get_sc1_ix_uuid(),
					'responses'  => json_decode( $search_page->get_cached_xfirstword_responses(), true ),
				);
			}
			?>
			let cached_xfirstword_responses = <?php echo wp_json_encode( $responses, JSON_UNESCAPED_UNICODE ); ?>;
			//console.info('cached_xfirstword_responses', cached_xfirstword_responses);
			for (let i = 0; i < cached_xfirstword_responses.length; ++i) {
				//console.info('cached_xfirstword_responses[i].index_uuid', cached_xfirstword_responses[i].index_uuid, ' ix_uuid' , ix_uuid);
				if (cached_xfirstword_responses[i].index_uuid === ix_uuid) {
					//console.info('found responses', cached_xfirstword_responses[i].responses);
					return cached_xfirstword_responses[i].responses;
				}
			}
			//console.info('did not find responses');
			return false;
		}


		function load_fields(callback = false) {
			try {
				multi_choice_fields = [];
				all_fields = [];
				let index_uuids = get_all_search_page_ix_uuids();
				if (index_uuids.length === 0) {
					populate_filter_items_html(callback);
					return;
				}
				//region Rather than ask SC1, we now use cached values...
				<?php
				$all_cached_meta_spec = array();
				foreach ( $all_search_pages as $search_page ) {
					$spec                   = $search_page->get_cached_meta_spec();
					$index_uuid             = $search_page->get_sc1_ix_uuid();
					$all_cached_meta_spec[] = array(
						'spec'       => json_decode( $spec ),
						'index_uuid' => $index_uuid,
					);
				}
				?>
				let cached_spec = <?php echo wp_json_encode( $all_cached_meta_spec ); ?>;
				for (let i = 0; i < cached_spec.length; i++) {
					let ix_uuid = cached_spec[i].index_uuid;
					let ix_fields_spec = cached_spec[i].spec;
					if (ix_fields_spec === null) continue;
					for (let s = 0 ; s < ix_fields_spec.length; s++)
					{
						let fieldSpec = ix_fields_spec[s];
						let field = {
							dspName: fieldSpec.DisplayName,
							name: fieldSpec.DisplayName,
							datatype: fieldSpec.MetaType,
							ix_uuid: ix_uuid
						};
						//region For enumerable fields, must handle SS1 Display Name property
						if (fieldSpec.hasOwnProperty('ExtraParams') && fieldSpec.ExtraParams != null) {
							let extraParams = JSON.parse(fieldSpec.ExtraParams);
							if (extraParams.hasOwnProperty('SS1-DisplayName')) {

								field.dspName = extraParams["SS1-DisplayName"];
							}
						}
						//endregion
						if (
							fieldSpec.DisplayName !== 'Categories'
							&& fieldSpec.DisplayName !== 'Tags'
							&& !fieldSpec.DisplayName.startsWith('_')
						) {
							let dataType = fieldSpec.MetaType;
							if (dataType === 'String' && fieldSpec.hasOwnProperty('ExtraParams') && fieldSpec.ExtraParams != null) {
								// We may infer a special SS1 dataType from ExtraParams
								// Currently there are two of these, "Taxonomy", and "Multi-Choice"
								// "Taxonomy" fields show up in the facets panel and are enumerable
								// "Multi-Choice" fields show up in the filters area as drop downs.
								try {
									let extraParams = JSON.parse(fieldSpec.ExtraParams);
									let dsp = 'Unknown';
									if (extraParams.hasOwnProperty('SS1-Display')) {
										dsp = extraParams["SS1-Display"];
									} else {
										//console.info(fieldSpec.DisplayName, ' does not have display property: ', dsp);
									}

									switch (dsp) {
										case 'Multi-Choice':
											multi_choice_fields.push(field.name);
											field.datatype = dsp;
											dataType = dsp;
											break;
										case 'Taxonomy':
											field.datatype = dsp;
											dataType = dsp;
											break;
										default:
											break;
									}
								} catch (e) {
									console.error('Error parsing ExtraParams', e);
								}
							}
							//console.info('Field: ', field);
							//console.info('Field should be displayed in filters drop down')
							all_fields.push(field);
						}
					}
				}
				populate_filter_items_html(callback);
				//endregion
			} catch (ex) {
				console.error('Something went wrong loading fields', ex);
			}
		}

		function get_all_search_page_ix_uuids()
		{
			if ($('#select-index').length) {
				// There is more than one search page
				let ix_uuids = [];
				$('#select-index').find('option').each(function() {

					ix_uuids.push($(this).data('ix-uuid'));
				});
				return ix_uuids;
			} else {
				return ['<?php echo esc_html( $current_search_page->get_sc1_ix_uuid() ); ?>'];
			}
		}


		function should_display_in_filters_dropdown(ix_uuid, fieldName)
		{
			//region Check if field is a known facet.
			//console.debug('Checking if field ', fieldName, ' is a facet...');
			if (all_fields != null) {
				for (let i = 0; i < all_fields.length; i++) {
					let field = all_fields[i];
					if (field.name === fieldName) {
						//console.info('Found matching field:', field);
						if (field.datatype === 'Taxonomy') return false; // Taxonomies are facets.
					}
				}
			}
			//endregion
			//console.info('Checking if field ' + fieldDisplayName + " should be displayed in index "  +ix_uuid +" 's dropdown");
			if (fieldName.includes("_")) return false;
			<?php
			$sp_dsp_opts = array();
			foreach ( $all_search_pages as $search_page ) {
				$opts    = $search_page->get_display_opts();
				$ix_uuid = $search_page->get_sc1_ix_uuid();
				array_push(
					$sp_dsp_opts,
					array(
						'ix_uuid' => $ix_uuid,
						'opts'    => $opts,
					)
				);
			}
			?>
			let sp_dsp_opts = <?php echo wp_json_encode( $sp_dsp_opts ); ?>;
			//sp_dsp_opts = JSON.parse(sp_dsp_opts); // Parse in PHP Var
			//console.info('sp_dsp_opts_searchpage', sp_dsp_opts, "retreiving", ix_uuid , ' field ', fieldName);
			for (let x = 0; x < sp_dsp_opts.length; x++)
			{
				let page_opts = sp_dsp_opts[x];
				if (page_opts.ix_uuid === ix_uuid)
				{
					let filter_options = page_opts.opts.fields;
					let filter_mode = filter_options[0];
					switch (filter_mode) {
						case 'all':
							return true;
						case 'include':
							let i = 1;
							while (i < filter_options.length) {
								if (fieldName === filter_options[i]) return true;
								++i;
							}
							return false;
						case 'exclude':
							let ii = 1;
							while (ii < filter_options.length) {
								if (fieldName === filter_options[ii]) return false;
								++ii;
							}
							return true;
					}
					return true;
				}
			}
			console.error('Search page does not have display opts', ix_uuid);
			return false;
		}

		// Used by both clear filters button and when user changes category
		function clear_filters()
		{
			$('.filter-checkbox').prop('checked',false).trigger('change');
		}

		/*
		Retrieve all filter items that should be displayed in filters drop down from all_fields. Excludes items that should
		not be displayed per users settings.
		 */
		function get_all_displayed_filter_items(ix_uuid) {
			let items = [];
			let uniqueNames = [];
			for (let i = 0; i < all_fields.length ; i++)
			{
				if (uniqueNames.indexOf(all_fields[i].name) === -1) {
					if (
						(all_fields[i].ix_uuid === ix_uuid  && ix_uuid !== "all")
						|| (all_fields[i].ix_uuid !== "all" && ix_uuid === 'all' )
					) {
						let tmp_ix_uuid = '' + ix_uuid;
						if (tmp_ix_uuid === 'all') tmp_ix_uuid = all_fields[i].ix_uuid; // Use the index of the field's display options in the case of 'search all'
						if (should_display_in_filters_dropdown(tmp_ix_uuid, all_fields[i].name)) {
							items.push(all_fields[i]);
							uniqueNames.push(all_fields[i].name);
						}
					}
				}
			}
			items.sort((a, b) => {
				let fa = a.name.toLowerCase(),
					fb = b.name.toLowerCase();

				if (fa < fb) {
					return -1;
				}
				if (fa > fb) {
					return 1;
				}
				return 0;
			});
			return items;
		}

		if ($('#select-index').length) {
			// This search page has additional pages the user can search
			$('#select-index').change(function() {
				if (restoring) return;
				// User chose a different search page...
				clear_filters();
				apply_filters(false);
				clear_filter_items_html();
				populate_filter_items_html();
				update_synonyms_stemming_checkbox_dsp();
				facets = [];
				current_page = 1;
				display_results(true);

			});
		}

		function get_current_search_page_post_id() {
			if ($('#select-index').length) {
				// There is more than one search page
				let post_id = $('#select-index').val();
				return parseInt(post_id);
			} else {
				return parseInt('<?php echo esc_html( $current_search_page->get_post_id() ); ?>');
			}
		}

		function get_current_search_page_ix_uuid() {
			if ($('#select-index').length) {
				// There is more than one search page
				let uuid = $('#select-index').find(':selected').data('ix-uuid');
				return uuid;
			} else {
				return '<?php echo esc_html( $current_search_page->get_sc1_ix_uuid() ); ?>';
			}
		}

		function get_current_search_page_ix_id() {
			if ($('#select-index').length) {
				// There is more than one search page
				let id = parseInt($('#select-index').find(':selected').data('ix-id'));
				return id;
			} else {
				return parseInt('<?php echo esc_html( $current_search_page->get_sc1_ix_id() ); ?>');
			}
		}

		function populate_filter_items_html(callback = false) {
			if (all_fields == null)
			{
				// Filters not yet loaded
				//console.info('Loading filter items...');
				load_fields(callback);
				return;
			}
			//console.info('Populating filter items...');
			clear_filter_items_html();
			let filter_items = get_all_displayed_filter_items(get_current_search_page_ix_uuid());
			//console.info('SS1-DEBUG New filter items:', filter_items);
			let i = 0;
			while (i < filter_items.length) {
				let filter_item = filter_items[i];
				append_filter_item_html(filter_item);
				++i;
			}
			if (filter_items.length > 0) {
				$('#btn-filter-panel-toggle').show();
			} else {
				$('#btn-filter-panel-toggle').hide();
			}
			hook_filter_item_events();

			// Check for any selects that need their options loading...
			let selectOptionsToLoad = [];
			let index_uuids = get_index_uuids_to_search();
			$('select.loading-multi-choice-filter').each(function() {
				let cached_val = sessionStorage.getItem("ss1-select-opts-" + index_uuids.join('-') + '-' + name);
				let cache_valid = false;
				if (cached_val) {
					cached_val = JSON.parse(cached_val);
					let now = new Date();
					let time = new Date(Date.parse(cached_val.time)); // js is weird S.Oflow 242627
					let age = getMinutesBetweenDates(time, now);
					if (age < 60) cache_valid = true;
				}
				if (cache_valid) {
					//  Select option already valid in cache
					let options = cached_val.options;
					$(this).empty();
					for (const option of options) {
						$(this).append('<option value="' + option +'">' + option + '</option>');
					}
				} else {
					selectOptionsToLoad.push($(this).data('filter-name'));
				}
			});

			if (selectOptionsToLoad.length > 0) {
				//console.info('There are ' + selectOptionsToLoad.length + ' select options to load');
				loadFilterSelectOptions(selectOptionsToLoad, callback);
			} else {
				//console.info('All select options were already cached');
				if (callback !== false && typeof callback === 'function')
				{
					callback();
				}
			}
		}

		function loadFilterSelectOptions(selectOptions = [], callback = false)
		{
			//region New - We no longer fetch select options from SC1. They are cached in SS1 Plugin
			<?php
			// We want to output an array of indexes and select values that js can call from here.
			// For each search page, get the cached select values.
			$output = array();
			foreach ( $all_search_pages as $search_page ) {
				$ix_uuid          = $search_page->get_sc1_ix_uuid();
				$sp_select_values = $search_page->get_cached_select_values();
				array_push(
					$output,
					array(
						'ix_uuid'          => $ix_uuid,
						'sp_select_values' => json_decode( $sp_select_values ),
					)
				);
			}
			?>
			let cached_sp_select_values = <?php echo wp_json_encode( $output ); ?>;
			let index_uuids = get_index_uuids_to_search();
			//console.info('cached_sp_select_values', cached_sp_select_values, 'index_uuids',index_uuids);
			// Must de-dupe fieldnames, and their values.
			let fieldNames  = [];
			let fieldValues = [];
			for (let i = 0; i  < cached_sp_select_values.length; i++)
			{
				if (index_uuids.indexOf(cached_sp_select_values[i].ix_uuid) === -1) continue; // We're not searching this index
				let topFieldValues = cached_sp_select_values[i].sp_select_values;
				if (topFieldValues === null) continue;
				//console.info('topFieldValues' , topFieldValues);
				for (const field of topFieldValues) {
					//console.info('field', field);
					let name = field.Field;
					if (fieldNames.indexOf(name) === -1) {
						fieldNames.push(name);
						fieldValues.push([]);
					}
					let fvi = fieldNames.indexOf(name);
					for (const value of field.Values) {
						if (fieldValues[fvi].indexOf(value.Value) === -1) {
							fieldValues[fvi].push(value.Value);
						}
					}
				}
			}
			//console.info('fieldNames', fieldNames, 'fieldValues',fieldValues);
			for (let i =0; i < fieldNames.length; i++)
			{
				let fieldName = fieldNames[i];
				let select = $('select[data-filter-name="' + fieldName + '"]');
				if (select.length) {
					select.empty();
					select.append('<option disabled selected value="__choose"><?php esc_html_e( 'Choose...', 'site-search-one' ); ?></option>');
					let values = fieldValues[i];
					values.sort();
					for (const value of values) {
						if (value === '-') continue;
						select.append('<option value="' + value +'">' + value + '</option>');
					}
				}
			}

			if (callback !== false && typeof callback === 'function')
			{
				callback();
			}
			//endregion
		}

		function hook_filter_item_events() {
			$('.sc1-date-control').on('changeDate propertychange input change', function() {
				console.debug('Filter date changed');
				let bothFilled = true;
				$(this).closest('.input-daterange').find('.sc1-date-control').each( function() {
					let str = $(this).val();
					bothFilled  = !!str.trim();
					if (!bothFilled) return false;
				});
				$(this).closest('.filter-item').find('.filter-checkbox').prop('checked',bothFilled);
				update_clear_filters_btn_vis();
			});

			$('.filter-textbox').on('propertychange input change', function() {
				console.debug('Filter text changed');
				let str = $(this).val();
				$(this).closest('.filter-item').find('.filter-checkbox').prop('checked', !!str.trim()); // If the field is not empty, check the checkbox for the title, else uncheck it
				update_clear_filters_btn_vis();
			});

			$('.input-daterange').datepicker({
				autoclose: true
			});

			$('.filter-multi-choice').on('change', function() {
				console.debug('Multi-choice changed..');
				if($(this).val()){
					// Something is selected..
					$(this).closest('.filter-item').find('.filter-checkbox').prop('checked', true);
				} else {
					// Nothing is selected..
					$(this).closest('.filter-item').find('.filter-checkbox').prop('checked', false);
				}
				update_clear_filters_btn_vis();
			});

			$('.filter-checkbox').change(function() {
				let dataType = $(this).closest('.filter-item').attr('data-filter-datatype');
				if ($(this).prop('checked')) {

					switch(dataType.toLowerCase()) {
						case 'date':
						case 'datetime':
						{
							$(this).closest('.filter-item').find('.sc1-date-control')[0].focus();
							break;
						}
						case 'multi-choice':
						{
							break;
						}
						default:
						{
							$(this).closest('.filter-item').find('.filter-textbox')[0].focus();
							break;
						}
					}
				} else {
					// Unchecked
					switch(dataType.toLowerCase()) {
						case 'date':
						case 'datetime':
						{
							console.info('Clearing date');
							$(this).closest('.filter-item').find('.datepicker').datepicker('clearDates');
							$(this).closest('.filter-item').find('.sc1-date-control').val('');
							break;
						}
						case 'multi-choice':
						{
							console.info('Clearing multi-choice');
							console.info($(this).closest('.filter-item').find('select'));
							$(this).closest('.filter-item').find('select').val('__choose').change();
							break;
						}
						default:
						{
							console.info('Clearing text');
							$(this).closest('.filter-item').find('.filter-textbox').val('');
							break;
						}
					}
				}
				update_clear_filters_btn_vis();
			});

			//console.debug('Hooked filter item change events');
		}

		function clear_filter_items_html() {
			$('#filter-panel-controls').html('');
		}

		function update_synonyms_stemming_checkbox_dsp() {
			let all_index_uuids = get_index_uuids_to_search();
			let all_dsp_opts    = get_display_opts(all_index_uuids);

			let stemmingDefault  = false;
			let synonymsDefault  = false;
			let stemmingChoice   = false;
			let synonymsChoice   = false;

			for (let i = 0; i < all_dsp_opts.length; i++)
			{
				let dspOpts = all_dsp_opts[i];
				let pgStemmingDefault = true;
				let pgSynonymsDefault = false;
				let pgStemmingChoice = true;
				let pgSynonymsChoice = true;
				if (dspOpts.hasOwnProperty('stemmingDefault')) {
					pgStemmingDefault = dspOpts.stemmingDefault;
					pgSynonymsDefault = dspOpts.synonymsDefault;
					pgStemmingChoice = dspOpts.stemmingChoice;
					pgSynonymsChoice = dspOpts.synonymsChoice;
				}
				stemmingDefault = pgStemmingDefault ? true : stemmingDefault;
				synonymsDefault = pgSynonymsDefault ? true : synonymsDefault;
				stemmingChoice = pgStemmingChoice ? true : stemmingChoice;
				synonymsChoice = pgSynonymsChoice ? true : synonymsChoice;
			}
			let wasRestoring = restoring;
			restoring = true; // Prevent this causing a search
			$('#check-box-stemming')
				.prop('checked', stemmingDefault)
				.parent().toggleClass('hidden', !stemmingChoice);
			$('#check-box-synonyms')
				.prop('checked', synonymsDefault)
				.parent().toggleClass('hidden', !synonymsChoice);
			restoring = wasRestoring;
		}

		function append_filter_item_html(filter_item) {
			//console.info('Appending Filter Item', filter_item);
			//console.trace();
			let name = filter_item.name;
			let dspName = name;
			if (filter_item.hasOwnProperty('dspName')) {
				dspName = filter_item.dspName;
			}
			let dataType = filter_item.datatype;
			let verb = '';
			switch(dataType) {
				case "DateTime":
				case "Date":
					verb = "<?php esc_html_e( 'Between', 'site-search-one' ); ?>";
					break;
				case "Multi-Choice":
					verb = '';
					break;
				default:
					verb = "<?php esc_html_e( 'Contains', 'site-search-one' ); ?>";
					break;
			}
			let filterPanelFilters = $('#filter-panel-controls');
			filterPanelFilters.append(
				'<span class="filter-item" data-filter-name="'
				+ name
				+ '" data-filter-datatype="' + dataType+ '">'
			);
			let filterSpan = $('.filter-item').last();
			//console.info('Filter span', filterSpan);
			filterSpan.append('<h4>' + dspName + ' ' + verb);
			let inputGroup      = $('<div class="input-group mb-3">').appendTo(filterSpan);
			let inputGroupAddon = $('<div class="input-group-text">').appendTo(inputGroup);
			inputGroupAddon.append('<input type="checkbox" class="filter-checkbox" aria-label="Check Box ' + name +'">');
			switch (dataType) {
				case "DateTime":
				case "Date":
					let temp    = document.getElementById('template-datepicker');
					let clone   = temp.content.cloneNode(true);
					inputGroup.append(clone);
					break;
				case "Multi-Choice":
					let select = $('<select class="form-control form-select filter-multi-choice" data-filter-name="'
						+ name
						+ '" aria-label="'
						+ name
						+'"><option disabled selected value><?php esc_html_e( 'Loading...', 'site-search-one' ); ?> </option></select>')
						.appendTo(inputGroup);
					if (filter_item.hasOwnProperty('options')) {
						select.empty();
						select.append('<option disabled selected value="__choose"><?php esc_html_e( 'Choose...', 'site-search-one' ); ?></option>');
						for (let i = 0; i < filter_item.options.length; i++)
						{
							select.append('<option value="' + filter_item.options[i] +'">' + filter_item.options[i] + '</option>');
						}
					} else {
						// Load in filter items after initial results loaded
						select.addClass('loading-multi-choice-filter');
					}
					break;
				default:
					inputGroup.append('<input type="text" class="form-control filter-textbox" aria-label="' + name + '">');
					break;
			}
		}


		// Search Box Submit - Enter Key
		$("#textbox-query").keypress(function( event ) {
			if (event.keyCode === 13) {
				current_page = 1;
				clear_facets();
				// display_results(true);
			}
		});
		// Search Box Submit - Search Button Click
		$("#btn-search").click(function() {
			current_page = 1;
			facets = [];
			clear_facets();
			// display_results(true);
		});



		$('.sort-item').click(function() {
			sortBy = $(this).attr('data-sort');
			$('#dropdown-sort-order').html($(this).html());
			current_page = 1;
			display_results(false);
		});


		// If a filter is active, the button becomes enabled.
		function update_clear_filters_btn_vis()
		{
			let filters_count = get_filter_count();
			if (filters_count > 0) $('#btn-filter-clear').removeClass('disabled');
			else                   $('#btn-filter-clear').addClass('disabled');
		}
		let lastQuery = {};

		// returns true if facet is selected
		function is_selected_facet(field, value) {
			// console.info('Checking if is selected facet...', field, value);
			let numFacets = facets.length;
			for (let i = 0; i < numFacets; ++i) {
				if (facets[i].field === field && facets[i].value === value) {
					return true;
				}
			}
			return false;
		}

		function is_field_multiselect(fieldName)
		{
			return (multi_choice_fields.indexOf(fieldName) !== -1);
		}

		/**
		 * For facet fields, the display name may contain spaces, but due to limitations of dtSearch Engine,
		 * the field must be stored without spaces. This function will attempt to load the display name
		 * based on a given field name.
		 */
		function get_facet_field_display_name(fieldName)
		{
			if (all_fields == null) return fieldName;
			for (let i = 0; i < all_fields.length; i++)
			{
				let filter = all_fields[i];
				if (filter.name === fieldName)
				{
					return filter.dspName;
				}
			}
			return fieldName;
		}

		$('#container-facet-panel').hide(); // start hidden always.

		function display_top_field_values(fields_to_display) {
			//region Find out if the index indexes a single parent category
			let single_parent_cat = false;
			if ($('#select-index').length) {
				single_parent_cat = $('#select-index').find(':selected').data('parent-cat');
			} else {
				<?php
				$facets_ignored_parent_category = '';
				$single_cat                     = $current_search_page->indexes_one_category();
				if ( false !== $single_cat ) {
					$facets_ignored_parent_category = get_cat_name( $single_cat );
				}
				?>
				single_parent_cat = '<?php echo esc_html( $facets_ignored_parent_category ); ?>';
			}
			//endregion
			console.info('Displaying Top Field Values:', fields_to_display);
			// http://docs.searchcloudone.com/facet-searching/
			let facetRefinementContainer = $('#container-facet-panel');
			facetRefinementContainer.html('');
			let catFacetsDiv = $('<div>', {
				'class' : 'category-facets'
			});
			facetRefinementContainer.append(catFacetsDiv);
			let numFieldsShown = 0;
			for (const field of fields_to_display) {
				if (is_field_multiselect(field.Field))  continue;
				if (!is_shown_facet(field.Field))       continue;
				if (field.Field === "-") continue; // HACK fields named "-" won't search correctly
				let fieldDiv = $('<div>', {
					'class'     : 'facet-field hidden',
					'data-fn'   : field.Field
				});
				catFacetsDiv.append(fieldDiv);
				let fieldDspName = get_facet_field_display_name(field.Field);
				let h4 = $('<h4>', {
					'class' : 'field-name hidden'
				}).text(fieldDspName);
				fieldDiv.append(h4);
				let fieldValuesUL = $('<ul>', {
					'class' : 'field-values list-unstyled',
					'data-facet-ul-fn' : field.Field
				});
				fieldDiv.append(fieldValuesUL);
				let numValuesSelected = 0;
				let numValues         = 0;
				let numValuesShown    = 0;
				let numValuesHidden   = 0;
				for(const value of field.Values) {
					let fieldValue = value.Value;
					++numValues;
					let count = value.Count;
					let checkbox_html = "<li>" + $('#template-facet-checkbox').html() + "</li>";
					fieldValuesUL.append(checkbox_html);
					let cb = fieldValuesUL.find('.form-check').last();
					cb.attr('data-fn', field.Field);
					cb.attr('data-fv', fieldValue);
					// Assign an ID to the checkbox/label for accessibility purposes.
					let randomId = (Math.random() + 1).toString(36).substring(7);
					cb.find('.form-check-input')[0].id = randomId;
					cb.find('.form-check-label').attr('for',randomId);
					if (is_selected_facet(field.Field, fieldValue)) {
						++numValuesSelected;
						++numValuesShown;
						cb.find('input[type=checkbox]').prop('checked', true);

					} else {
						if (numValuesShown >= MAX_VALUES_PER_FACET_DISPLAYED) {
							cb.addClass('hidden');
							++numValuesHidden;
						} else {
							++numValuesShown;
						}
					}
					cb.find('.field-value').html('<div style="display:flex"><div class="facet-value-dsp">' + fieldValue + '</div> <div class="facet-count-dsp ms-2"> (' + count + ')</div></div>');
					cb.find('input').off().change(checkbox_change_evt);
				}
				let overClass = '';
				if (numValues > 2) overClass = ' over-2';
				if (numValues > 3) overClass = ' over-3';
				if (numValues > 4) overClass = ' over-4';
				if (numValues > 6) overClass = ' over-6';

				let moreLink = $('<small></small>', {
					'class' : 'btn-link clickable link-more-fv' + overClass
				}).text('<?php esc_html_e( 'More…', 'site-search-one' ); ?>');
				fieldDiv.append(moreLink);
				//"<div id='all-fields-btn' class='btn-link clickable hidden'>All Fields »</div>"
				let facetField = $('.facet-field[data-fn="' + field.Field + '"]');
				if (field.Values.length > 0 || numValuesSelected > 0) {
					h4.removeClass('hidden');
					facetField
						.removeClass('hidden')
						.addClass('displayed');
					++numFieldsShown;
					facetField.find('.link-more-fv')
						.off()
						.click(function () {
							let fieldName = $(this).closest('.facet-field').attr('data-fn');
							let dspName = get_facet_field_display_name(fieldName);
							show_facet_modal(fieldName, dspName);
						});
				}
				else {
					facetField
						.addClass('hidden')
						.removeClass('displayed');
					facetField.find('.link-more-fv').addClass('hidden');
				}

			}
			let allFieldsBt = $('<div></div>', {
				'class' : 'btn-link clickable hidden',
				'id' : 'all-fields-btn'
			}).text('<?php esc_html_e( 'All Fields »', 'site-search-one' ); ?>');
			facetRefinementContainer.append(allFieldsBt);
			hookAllFieldsBtn();
			if (numFieldsShown === 0) facetRefinementContainer.hide();
			else facetRefinementContainer.show();
			//"<div id='all-fields-btn' class='btn-link clickable hidden'>All Fields »</div>"
		}

		// Filter a TopFieldValues to just those that have values, remove values that
		// shouldn't be shown.
		function filter_displayed_top_field_values(topFieldValues) {
			//region Sort the facets by name
			topFieldValues.sort((a, b) => {
				let fa = a.Field.toLowerCase();
				let fb = b.Field.toLowerCase();
				if (fa < fb) {
					return -1;
				}
				if (fa > fb) {
					return 1;
				}
				return 0;
			});
			//endregion
			//region Find out if the index indexes a single parent category
			let single_parent_cat = false;
			if ($('#select-index').length) {
				single_parent_cat = $('#select-index').find(':selected').data('parent-cat');
			} else {
				<?php
				$facets_ignored_parent_category = '';
				$single_cat                     = $current_search_page->indexes_one_category();
				if ( false !== $single_cat ) {
					$facets_ignored_parent_category = get_cat_name( $single_cat );
				}
				?>
				single_parent_cat = '<?php echo esc_html( $facets_ignored_parent_category ); ?>';
			}
			//endregion
			//region Loop through and remove facet fields that shouldn't be displayed
			for (let i = topFieldValues.length - 1; i >= 0; i--) {
				let field = topFieldValues[i];
				if (
					is_field_multiselect(field.Field)
					|| !is_shown_facet(field.Field)
					|| field.Field === "-") {
					topFieldValues.splice(i, 1);
					continue;
				}
				let numValidValues = 0;
				for (let ii = field.Values.length -1; ii >= 0; ii--) {
					let fieldValue = field.Values[ii].Value;
					if (fieldValue === "-") continue; // HACK values named "-" won't search correctly
					if (field.Field === "Categories" && single_parent_cat === fieldValue) continue;
					++numValidValues;
				}
				if (numValidValues === 0) {
					// This facet field has no valid values, don't display it
					topFieldValues.splice(i, 1);
				}
			}
			//endregion
			return topFieldValues;
		}

		function is_shown_facet(field) {
			let all_index_uuids = get_index_uuids_to_search();
			let all_dsp_opts    = get_display_opts(all_index_uuids);
			for (let i = 0; i < all_dsp_opts.length; ++i)
			{
				let dsp_opts = all_dsp_opts[i];
				// Limitation: In the scenario where we are searching multiple indexes at once, we can't know which index
				// A facet field has come from, therefore if even one index is set to 'all', all facets will always
				// be shown from all indexes.
				if (!dsp_opts.hasOwnProperty('facets')) return true; // Assume 'all'
				if (dsp_opts.facets[0] === 'all') return true;
				for (let f = 1; f < dsp_opts.facets.length; ++f) {
					//console.info('dsp_opts.facets[f]', dsp_opts.facets[f],'field',field);
					if (dsp_opts.facets[f] === field) return true;
				}
			}
			return false;
		}

		function get_display_opts(index_uuids) {
			<?php
			$all_display_opts       = array();
			$count_all_search_pages = count( $all_search_pages );
			for ( $i = 0; $i < $count_all_search_pages; ++$i ) {
				$search_page    = $all_search_pages[ $i ];
				$i_display_opts = $search_page->get_display_opts();
				$index_uuid     = $search_page->get_sc1_ix_uuid();
				array_push( $all_display_opts, $index_uuid, $i_display_opts );
			}
			?>
			let all_dsp_opts = <?php echo wp_json_encode( $all_display_opts, JSON_UNESCAPED_UNICODE ); ?>;
			let return_opts = [];
			for (let i = 0; i < index_uuids.length; i++) {
				let ix_uuid = index_uuids[i];
				// Check all display opts to see if one matches our index uuid..
				let found = false;
				for (let ii = 0; ii < all_dsp_opts.length; ii += 2) {
					if (ix_uuid === all_dsp_opts[ii]) {
						found = true;
						return_opts.push(all_dsp_opts[ii + 1]);
						break;
					}
				}
				if (!found) return_opts.push(null);
			}
			return return_opts;
		}

		function show_facet_modal(fieldName, fieldDspName = '')
		{
			let modalFacets = $('#modal-facets-lv');

			modalFacets.modal('show');
			modalFacets.find('.modal-title').html(fieldDspName);
			modalFacets.find('.modal-title').attr('data-field-name', fieldName);

			let numFieldValues = $('#container-facet-panel')
				.find('ul[data-facet-ul-fn="' + fieldName+'"]').find('checkbox').length;

			if ((numFieldValues * 2) < MAX_VALUES_PER_FACET_DISPLAYED) {
				modalFacets.find('#ul-facets-modal').css("column-count","2")
			} else {
				modalFacets.find('#ul-facets-modal').css("column-count","3");
			}
			modalFacets.find('#ul-facets-modal').html($('[data-facet-ul-fn="' + fieldName + '"]').html());


			//modalFacets.find('.btn-primary').off().click(save_modal_changes);
			sync_modal_checkboxes();

			modalFacets.find('.form-check').each(function(index) {
				$(this).removeClass('hidden');
				$(this).find('input').change(function() {
					save_modal_changes(false);
					sync_modal_checkboxes();
				});
			});
		}

		function compile_query_url(skipField = '') {
			let searchbox = $('#textbox-query');
			let query = $(searchbox).val();
			// Sanity Check 1/2 - Ensure a query was entered
			if (query.trim().length === 0) {
				query = "xfirstword";
			}

			let filters = compile_filter_argument(skipField)

			console.info('Compiled Query: ', query);

			let json = {};
			json.performSearch = {};
			json.performSearch.query = query;
			json.performSearch.page = current_page;
			json.performSearch.context = true;
			if (filters.FieldValuesMatchAny.length > 0) {
				json.performSearch.filters = filters;
			}
			json.performSearch.sortBy = sortBy;
			//json.performSearch.post_id = get_current_search_page_post_id();
			json.performSearch.indexes = get_indexes_to_search();
			json.performSearch.reqFacets = [];
			json.performSearch.stemming = $('#check-box-stemming').prop('checked');


			let synonms = false;
			if ($('#check-box-synonyms').prop('checked'))
			{
				<?php
				$wordnet = true;
				if ( property_exists( $display_opts, 'wordnet_synonyms' ) ) {
					$wordnet = $display_opts->wordnet_synonyms;
					$user    = true;
					?>
				synonms = {
					WordNet : <?php echo $wordnet ? 'true' : 'false'; ?>,
					User: true
				};
					<?php
				} else {
					?>
				synonms = true;
					<?php
				}
				?>
			}
			json.performSearch.synonyms = synonms;

			<?php
			if ( property_exists( $display_opts, 'search_for' ) ) {
				switch ( $display_opts->search_for ) {
					case 'natural':
						$flags = 0x0008; // SearchFlags.dtsSearchNatural Field .
						break;
					case 'any':
						$flags = 0x40000; // SearchFlags.dtsSearchTypeAnyWords .
						break;
					case 'all':
						$flags = 0x20000; // SearchFlags.dtsSearchTypeAllWords .
						break;
					default:
						$flags = 0;

				}
				if ( 0 !== $flags ) {
					?>
			json.performSearch.flags = <?php echo esc_js( $flags ); ?> ;
					<?php
				}
			}
			?>

			lastQuery = json;

			return '<?php echo esc_html( ( rest_url( 'ss1_client/v1/search' ) ) ); ?>?query=' + encodeURIComponent(JSON.stringify(json));
		}

		function get_indexes_to_search() {
			if ($('#select-index').length) {
				// There is more than one search page
				let uuid = $('#select-index').find(':selected').data('ix-uuid');
				if (uuid === 'all') {
					let indexes = [];
					let primary_ix_uuid = '<?php echo esc_html( $current_search_page->get_sc1_ix_uuid() ); ?>';
					$('#select-index').find('option').each(function() {
						if ($(this).data('ix-uuid') !== 'all') {
							if ($(this).data('ix-uuid') === primary_ix_uuid)
							{
								// The primary index uuid should be first to ensure the correct
								// Synonyms/Stemming is used
								indexes.unshift({
									IndexUUID: $(this).data('ix-uuid'),
									IndexID: parseInt($(this).data('ix-id'))
								})
							} else {
								indexes.push({
									IndexUUID: $(this).data('ix-uuid'),
									IndexID: parseInt($(this).data('ix-id'))
								});
							}
						}
					});


					return indexes;
				} else {
					let id = parseInt($('#select-index').find(':selected').data('ix-id'));
					return [{
						IndexUUID : uuid,
						IndexID : id
					}];
				}
			} else {
				let uuid = '<?php echo esc_html( $current_search_page->get_sc1_ix_uuid() ); ?>';
				let id = parseInt('<?php echo esc_html( $current_search_page->get_sc1_ix_id() ); ?>');
				return [{
					IndexUUID : uuid,
					IndexID : id
				}];
			}
		}

		function get_index_uuids_to_search() {
			let uuids = [];
			let indexes = get_indexes_to_search();
			for (const index of indexes) {
				uuids.push(index.IndexUUID);
			}
			return uuids;
		}

		let session_token = '<?php echo esc_html( $token ); ?>';

		let search_for = 'bool';
		<?php
		if ( property_exists( $display_opts, 'search_for' ) ) {
			?>
		search_for = '<?php echo esc_html( $display_opts->search_for ); ?>';
			<?php
		}
		?>

		let document_css = '';
		<?php
		if ( property_exists( $display_opts, 'document_css' ) ) {
			?>
		document_css = <?php echo wp_json_encode( $display_opts->document_css ); ?>;
			<?php
		}
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$table_name           = $wpdb->prefix . 'ss1_globals';
		$query                = "SELECT value FROM $table_name WHERE setting = 'global_css_documents'";
		$global_css_documents = $wpdb->get_var( $query );
		// phpcs:enable
		if ( $global_css_documents && '' !== $global_css_documents ) {
			?>
		document_css += " " + <?php echo wp_json_encode( $global_css_documents ); ?>;
			<?php
		}
		?>


		console.info('Hit viewer document CSS', document_css);

		let logTo = false;
		<?php
		if ( false !== $log_to ) {
			?>
		logTo = <?php echo wp_json_encode( $log_to ); ?>;
			<?php
		}
		?>
		console.info('Log To:', logTo);
		<?php
		$session_info = array(
			'wpuid'  => $current_user_id,
			'postid' => $sp_post_id,
		);
		?>
		let sessionInfo = <?php echo wp_json_encode( $session_info ); ?>;

		function compile_query_json(skipField = '')
		{
			compile_query_url(skipField); // Backwards compatibility with areas of UI not using token method.. Causes lastQuery to be set
			let searchbox = $('#textbox-query');
			let query = $(searchbox).val();
			// Sanity Check 1/2 - Ensure a query was entered
			if (query.trim().length === 0) {
				query = "xfirstword";
			}

			let synonyms = false;
			if ($('#check-box-synonyms').prop('checked'))
			{
				<?php
				$wordnet = true;
				if ( property_exists( $display_opts, 'wordnet_synonyms' ) ) {
					$wordnet = $display_opts->wordnet_synonyms;
					$user    = true;
					?>
				synonyms = {
					WordNet : <?php echo $wordnet ? 'true' : 'false'; ?>,
					User: true
				};
					<?php
				} else {
					?>
				synonyms = true;
					<?php
				}
				?>
			}
			let numWordsOfContext = get_num_words_of_context();
			let reqData = {
				Token   : session_token,
				Indexes : get_indexes_to_search(),
				Parameters : {
					Query: query,
					Page: current_page,
					IncludeContext:  (numWordsOfContext > 0),
					NumWordsOfContext: numWordsOfContext,
					Stemming: $('#check-box-stemming').prop('checked'),
					Synonyms: synonyms,
					IncludeFields: get_include_fields(),
					UseFieldsAsDocDisplayName : [
						'_SS1DisplayName'
					],
					GetTopFieldValues : {
						'MaxResults' : 64,
						'CaseSensitive' : false
					}
				}
			};

			let filters = compile_filter_argument(skipField);
			if (filters.FieldValuesMatchAny.length > 0) {
				reqData.Parameters.Filters = filters;
			}


			if (logTo !== false && !referred) { // Avoid logging if we're restoring a query as WP will have already logged it.
				reqData.Parameters.LogTo = logTo;
				reqData.Parameters.SessionInfo = JSON.stringify(sessionInfo);
			}

			switch (sortBy) {
				case 'a-z': {
					reqData.Parameters.Sort = {
						SortBy : 'Title',
						Ascending : true
					};
					break;
				}
				case 'z-a': {
					reqData.Parameters.Sort = {
						SortBy: 'Title',
						Ascending: false
					};
					break;
				}
				case 'modified-asc': {
					reqData.Parameters.Sort = {
						SortBy: 'SS1_Published',
						Ascending: true
					};
					break;
				}
				case 'modified-desc': {
					reqData.Parameters.Sort = {
						SortBy: 'SS1_Published',
						Ascending: false
					};
					break;
				}
				default: {
					if (query === 'xfirstword') {
						// No sort set and no query set - Bring back newest results first.
						reqData.Parameters.Sort = {
							SortBy: 'SS1_Published',
							Ascending: false
						};
					} else {
						// reqData.Parameters.Sort = {
						//     SortBy: 'Hits',
						//     Ascending: false
						// }
					}
					break;
				}
			}
			let search_for = $('input[name="search-type"]:checked').val();
			let flags = 0;
			console.info('Using Search type', search_for);
			switch (search_for) {
				case 'any':
					flags = 0x40000;
					break;
				case 'all':
					flags = 0x20000
					break;
			}
			if (flags !== 0)
			{
				reqData.Parameters.Flags = flags;
			}
			return reqData;
		}

		function get_link_format() {
			let link_format  = '<h3 class="link_title">%%doc_title%%</h3><p class="context">%%context%%</p>';
			<?php
			// phpcs:disable WordPress.DB.DirectDatabaseQuery
			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
			$table_name         = $wpdb->prefix . 'ss1_globals';
			$query              = "SELECT value FROM $table_name WHERE setting = 'global_link_format'";
			$global_link_format = $wpdb->get_var( $query );
			// phpcs:enable
			if ( $global_link_format && '' !== $global_link_format ) {
				?>
			link_format = <?php echo wp_json_encode( $global_link_format ); ?>;
				<?php
			}
			?>
			<?php
			if (
			property_exists( $display_opts, 'link_format' )
			&& null !== $display_opts->link_format
			&& '<h3 class="link_title">%%doc_title%%</h3><p class="context">%%context%%</p>' !== $display_opts->link_format // The default.
			&& '' !== $display_opts->link_format
			) {
				?>
			link_format = <?php echo wp_json_encode( $display_opts->link_format ); ?>;
				<?php
			}
			?>
			return link_format;
		}

		/**
		 * Get an array of field names the user wants in their result title.
		 * Also results Link and _link in the array as SS1 always needs these.
		 */
		function get_include_fields()
		{
			let link_format  = get_link_format();
			let parts = link_format.split("%%");
			let i = 1;
			let len = parts.length;
			let fields = ['_link','Link','mimetype','_post_id'];
			while (i < len) {
				let s = parts[i];
				if (s.startsWith('field:')) {
					let args = s.split(':');
					let fieldName = args[1].split('(')[0];
					fields.push(fieldName);
				}
				<?php
				if ( $premium ) {
					?>
				if ( s.startsWith( 'img:' ) ) {
					let args = s . split( ':' );
					let fieldName = args[1];
					fields . push( fieldName );
				}
					<?php
				}
				?>
				i +=2;
			}
			console.info('Found fields', fields, 'in', link_format);
			return fields;
		}

		function compile_filter_argument(skipField = '') {
			let filter = {
				FieldValuesMatchAny : []
			};
			for (let i = 0; i < facets.length; ++i) {
				let fieldName = facets[i].field;
				if (fieldName === skipField) continue;
				let value = facets[i].value;
				filter.FieldValuesMatchAny.push({
					Field: fieldName,
					Values: [value]
				});
			}
			if (get_filter_count() === 0) return filter;
			$('#filter-panel').find('.filter-item').each(function () {
				let checked = $(this).find('.filter-checkbox').prop('checked');
				if (checked) {
					let dataType = $(this).attr('data-filter-datatype');
					let fieldName =  $(this).attr('data-filter-name');
					switch (dataType) {
						case 'DateTime':
						case 'Date': {
							let dateFrom = $(this).find('.sc1-date-from').val();
							let dateTo = $(this).find('.sc1-date-to').val();
							filter.FieldValuesMatchAny.push({
								Field: fieldName,
								Values: ["date(" + dateFrom + " to " + dateTo + ")"]
							});
							break;
						}
						case 'Multi-Choice':
							let value = $(this).find('.filter-multi-choice').val();
							filter.FieldValuesMatchAny.push({
								Field: fieldName.replaceAll(' ', '_'),
								Values: [value]
							});
							break;
						default: {
							let text = $(this).find('.filter-textbox').val();
							filter.FieldValuesMatchAny.push({
								Field: fieldName,
								Values: [text]
							});
							break;
						}
					}
				}
			});
			return filter;
		}

		function set_select_value(select, value) {
			let found = false;
			select.find('option').each(function() {
				if (this.value === value) {
					console.info('Found select value', select, value);
					select.val(value).change();
					found = true;
					return false;
				}
			});
			if (found === false) {
				console.error('Could not find select value', select, value);
			}
		}

		/**
		 * Returns true if the search request is the default one when search page is opened.
		 */
		function isDefaultSearch()
		{
			// Conditions for default search:-
			// 1. No facets
			if (facets.length > 0) return false;
			// 2. No filters
			if (get_filter_count() > 0) return false;
			// 3. Search textbox empty
			let searchbox = $('#textbox-query');
			let query = $(searchbox).val();
			if (query !== "") return false;
			// 4. Page is 1
			if (current_page !== 1) return false;
			// 5. Sort order is default.
			if (sortBy !== 'none' && sortBy !== 'modified-desc') return false;
			return true;
		}

		function isEmptySearch()
		{
			let searchbox = $('#textbox-query');
			let query = $(searchbox).val();
			if (query !== "") return false;
			return true;
		}

		function update_facets_dsp()
		{
			get_current_search_page_ix_uuid()
		}

		/**
		 * Display a result set for a query
		 * query:  What was searched
		 * index: Names of which index to be searched
		 * page: The page number to be displayed in the result set
		 */
		function display_results(refresh_facets, loading_msg = '<?php esc_html_e( 'Loading...', 'site-search-one' ); ?>')
		{
			// Fix the search outcome container height to the current height whilst loading the results to avoid 'jump'
			// In appearance
			let searchOutcomeContainer = $('#container-search-outcome');
			let minHeight = searchOutcomeContainer.height();
			//alert(minHeight + 'px');
			searchOutcomeContainer[0].style.minHeight = minHeight + 'px';

			// let facetRefinementContainer = $('#container-facet-panel');
			// facetRefinementContainer.hide();
			let searchbox = $('#textbox-query');
			if (modalHaltedRefreshing || restoring) return; // Prevent refresh from checkbox change events as modal as clicking around.
			$('#nav-result-pagination').addClass('hidden');
			// Sanity Check 2/2 - Ensure the requested page is greater than 0
			if (current_page < 1) {
				current_page = 1;
			}

			$('#info-display').html('<i>' + loading_msg + '</i>');
			$('#search-results-container').html('');
			$('#page-nav-container').css('display', 'none');

			if (refresh_facets) {
				$('.category-facets').hide();
			}
			//return;
			// Perform our query and await a response from the server, or in the case of default search, use the cache
			// if possible.
			let query_json = compile_query_json();
			let useCache = isDefaultSearch();
			if (isEmptySearch()) {
				$('body').addClass('empty-query');
			} else {
				$('body').removeClass('empty-query');
			}
			get_search_response(
				useCache,
				function(data) {
					restoring   = false;
					referred    = false;
					//region displaying results
					window.scrollTo(0, 0);
					$('#page-nav-container').css('display', 'none');
					// query success without error
					// This will respond results xml which needs to be parsed
					let json = data;
					$('#search-results-container').html('');
					// let results_container = $('#search-results-container');
					if (json.Hits === 0) {
						// No results..
						$('#dropdown-sort-order').addClass('collapse');
						$('#info-display').html('<?php esc_html_e( 'No Results.', 'site-search-one' ); ?>');
						let facetRefinementContainer = $('#container-facet-panel');
						facetRefinementContainer.find('input[type=checkbox]').each(function() {
							$(this).attr("disabled", false);
						});
						return;
					}
					$('#dropdown-sort-order').removeClass('collapse');
					// Write out the page navigator
					let start = ((current_page - 1) * 10) + 1;
					let end = ((current_page - 1) * 10) + 10;
					if (end > json.DocCount) end = json.DocCount;
					let infoText = '';
					if (json.TotalFileCount > json.DocCount) {
						infoText = '<?php esc_html_e( 'Showing Results %1 - %2 of %3 (%4)', 'site-search-one' ); ?>';
						infoText = infoText.replace('%4', json.TotalFileCount.toString());
					} else {
						infoText = '<?php esc_html_e( 'Showing Results %1 - %2 of %3', 'site-search-one' ); ?>';
					}
					infoText = infoText.replace('%1', start.toString());
					infoText = infoText.replace('%2', end.toString());
					infoText = infoText.replace('%3', json.DocCount.toString());

					$('#info-display').html('<i>' + infoText + '</i>');


					let num_pages = Math.ceil(json.DocCount / 10);
					last_page = num_pages ;
					let page_result_count = json.Results.length;
					for (let i = 0; i < page_result_count; i++) {
						let resultDiv_element = document.createElement('div');
						resultDiv_element.className = "search-result-container";
						resultDiv_element.setAttribute("style", "padding-top: 5px");
						resultDiv_element.classList.add('card','border-light','mb-3',"p-3");

						let link_element = document.createElement('a');

						// link generation
						let searchBox = $('#textbox-query');
						let pageLinkJson = lastQuery;
						//console.info('SS1-DEBUG pageLinkJson', pageLinkJson);
						let docTypeId = json.Results[i].TypeID;
						let docIndex = json.Results[i].ResultIndex;
						let hitViewer_url;
						console.info('Result',i,json.Results[i].FieldValues);
						<?php
						if ( 'original_page' === $display_opts->link_opens ) {
							?>
						try {

							let url = json.Results[i].FieldValues._link;
							if (url == null || url === "") {
								url = json.Results[i].FieldValues.Link;
							}
							if (url != null && url !== "") {
								if (url.split("|").length > 0) {
									url = url.split("|")[0];
								}

								let query = $(searchbox).val();
								// Sanity Check 1/2 - Ensure a query was entered
								let words = [];
								if (query.trim().length === 0) {
									query = "xfirstword";
								} else {
									words = get_hilite_words(query);
								}
								let numWords = words.length;
								let i = 0;
								while (i < numWords) {
									if (i === 0) {
										url += '?hilite=';
									} else {
										url += '+';
									}
									url += encodeURIComponent(words[i]);
									++i;
								}
								link_element.setAttribute("href", url);
							}
						} catch (ex) {
							console.error(ex);
						}
							<?php
						} else {
							?>
						let hitViewerUrl;
							<?php
							require_once plugin_dir_path( __FILE__ ) . '../admin/class-site-search-one-admin.php';
							$hitviewer_page      = Site_Search_One_Admin::get_hitviewer_page();
							$hitviewer_page_link = get_post_permalink( $hitviewer_page );
							$pdfviewer_page      = Site_Search_One_Admin::get_pdfviewer_page();
							$pdfviewer_page_link = get_post_permalink( $pdfviewer_page );
							?>
						let premium = false;
							<?php
							if ( $premium ) {
								?>
						premium = true;
								<?php
							}
							?>
						if (docTypeId === 227 && premium) { // PDF Document
							hitViewerUrl =  '<?php echo esc_html( $pdfviewer_page_link ); ?>';
						} else {
							// Any other kind of document. Gets rendered as HTML.
							hitViewerUrl = '<?php echo esc_html( $hitviewer_page_link ); ?>';
						}
						query_json.Parameters.Version = 3;
						if (docTypeId !== 227) {
							query_json.Parameters.HitViewer = {
								'DocIndex': docIndex,
								'MultiColorHits': true
							};
						} else {
							query_json.Parameters.GetPdfHitData2 = {};
							query_json.Parameters.GetPdfHitData2.DocIndex = docIndex;
							delete query_json.Parameters.LogTo;
							delete query_json.Parameters.HitViewer;
						}
						hitViewerUrl = hitViewerUrl + '?SearchReq='  + encodeURIComponent(JSON.stringify(query_json));
						if (docTypeId === 227) {
							//hitViewerUrl += "?DocIndex=" + docIndex;
							hitViewerUrl += "&PostID=" + json.Results[i].FieldValues._post_id;
						}
						link_element.setAttribute("href", hitViewerUrl);
						link_element.setAttribute("data-query-json", JSON.stringify(query_json));
							<?php
						}
						?>
						<?php
						if ( 'new_window' === $display_opts->link_behaviour && 'original_page' === $display_opts->link_opens ) {
							?>
						link_element.setAttribute("target","_blank");

							<?php
						} else {
							if ( 'original_page' === $display_opts->link_opens ) {
								?>
						// Cause navigation
						$(link_element).click(function(e) {
							window.parent.postMessage({
								'action' : 'SS1-Navigate-To',
								'url'    : $(this).attr('href')
							}, "*");
							e.preventDefault();
						});
								<?php
							} else {
								?>
						let shown_fields = 'all';
								<?php
								$hv_show_fields_at_bottom = true;
								if ( property_exists( $display_opts, 'show_fields_at_bottom' ) ) {
									$hv_show_fields_at_bottom = $display_opts->show_fields_at_bottom;
								}
								if ( false === $hv_show_fields_at_bottom ) {
									?>
						shown_fields = [];
									<?php
								}

								if ( is_array( $hv_show_fields_at_bottom ) ) {
									// User has selected that only certain fields should be shown at bottom.
									?>
						shown_fields = <?php echo wp_json_encode( $hv_show_fields_at_bottom ); ?>;
									<?php
								}
								?>
						//console.info('shown_fields', shown_fields);
						// Display as Overlay
						if (docTypeId !== 227) {
							// Not a PDF, show regular hitviewer
							$(link_element).click(function (e) {
								window.parent.postMessage({
									'action': 'SS1-HV-Overlay-Show',
									'query': JSON.parse($(this).attr('data-query-json')),
									'shown_fields': shown_fields,
									'document_css': document_css
								}, "*");
								e.preventDefault();
							});
						} else {
							// It's a PDF
							$(link_element).click(function(e) {
								window.parent.postMessage({
									'action' : 'SS1-PDF-HV-Overlay-Show',
									'src' : $(this).attr('href')
								}, '*');
								e.preventDefault();
							});
						}
								<?php
							}
						}
						?>
						let link_format  = get_link_format();
						link_element.innerHTML = get_formatted_link_text(json.Results[i], link_format);
						resultDiv_element.appendChild(link_element);
						document.getElementById("search-results-container").appendChild(resultDiv_element);
					}
					$('#nav-result-pagination').removeClass('hidden');
					let printed_results = 0;
					let paginationElement = $('.pagination');
					let paginationElements = $(paginationElement.find('li'));
					let firstPageElem = paginationElements[0]; // First page Button
					let prevPageElem  = paginationElements[1]; // Previous page button
					let nextPageElem = paginationElements[paginationElements.length - 2]; // Next Button
					let lastPageElem = paginationElements[paginationElements.length - 1]; // Last button

					firstPageElem.remove();
					nextPageElem.remove();
					paginationElement.html('');
					for (let i = 1; i <= num_pages && printed_results < 10; i++) {
						printed_results++;
						if (i < (current_page - 5)) i = current_page - 5;
						let linkLi = document.createElement('li');
						linkLi.classList.add('page-item');
						if (i === current_page) {
							linkLi.setAttribute('class', 'active');
						}
						let pageLink = document.createElement('a');
						pageLink.setAttribute('href', '#');
						pageLink.setAttribute('class', 'page-link');
						pageLink.setAttribute('data-page', i + '');
						pageLink.innerHTML = '' + i;
						linkLi.appendChild(pageLink);
						paginationElement.append(linkLi);
					}

					$('.page-link').click(function() {
						current_page = parseInt($(this).attr('data-page'));
						display_results(false);
						request_scroll_to_first_results();
						return false;
					});

					paginationElement.prepend(prevPageElem);
					paginationElement.prepend(firstPageElem);
					paginationElement.append(nextPageElem);
					paginationElement.append(lastPageElem);

					if (current_page > 1) {
						$('#page-previous,#page-first').show();
					}
					if (current_page === 1) {
						$('#page-previous,#page-first').hide();
					}
					if (current_page === num_pages) {
						$('#page-next,#page-last').hide();
					}
					else {
						$('#page-next').show();
						if (last_page > 1) {
							$('#page-last').show();
						}
					}
					//region Hook page nav events
					$('#page-next').unbind('click').click(function() {
						current_page++;
						display_results(false);
						request_scroll_to_first_results();
					}).attr('href','javascript:void(0);');

					$('#page-last').unbind('click').click(function() {
						console.info('clicked last');
						current_page = last_page;
						display_results(false);
						request_scroll_to_first_results();
					}).attr('href','javascript:void(0);');

					$('#page-previous').unbind('click').click(function() {
						current_page--;
						display_results(false);
						request_scroll_to_first_results();
					}).attr('href','javascript:void(0);');

					$('#page-first').unbind('click').click(function() {
						current_page = 1;
						display_results(false);
						request_scroll_to_first_results();
					}).attr('href','javascript:void(0);');
					//endregion
					let to_display = filter_displayed_top_field_values(json.TopFieldValues);
					display_top_field_values(to_display);
					update_expand_buttons();

					searchOutcomeContainer[0].removeAttribute("style");
					window.dispatchEvent(resultsLoaded);

					sync_modal_checkboxes();

					//endregion
				},
				function(xhr) {

					searchOutcomeContainer[0].removeAttribute("style");
					let statusCode = parseInt(xhr.status);
					if (statusCode === 440 || statusCode === 401) {
						// The token has expired.
						console.info('The session has expired. A new token is being fetched...');
						$('#info-display').html('<i><?php esc_html_e( 'Session Expired. Please wait...', 'site-search-one' ); ?></i>');
						refresh_session(refresh_facets);
						refreshing = false;
						update_expand_buttons();
					} else {
						restoring = false;
						console.error('Error performing search', xhr);
						$('#info-display').html('<i><?php esc_html_e( 'Something went wrong. Check connection and try again.', 'site-search-one' ); ?></i>');
						refreshing = false;
						update_expand_buttons();
					}
				}
			);
		}

		// Called during page changes.
		function request_scroll_to_first_results() {
			let topOffset = $('#info-display').offset().top;
			window.parent.postMessage({
				'action': 'SS1-Scroll-To-Top-Of-SearchPage',
				'resultsOffset': topOffset
			}, "*");
		}

		function get_search_response(useCache = false, onSuccess, onFailure)
		{
			if (useCache) {
				// We'll attempt to use cache...
				let uuid = $('#select-index').find(':selected').data('ix-uuid');
				let searchAll = (uuid === 'all');
				let primary_ix_uuid = '<?php echo esc_html( $current_search_page->get_sc1_ix_uuid() ); ?>';
				if (searchAll) {
					let res = get_cached_xfirstword_responses(primary_ix_uuid);
					if (res !== null && res[1] !== null) {
						//console.info('Using cached multi index response', res[1]);
						try {
							let data = JSON.parse(res[1]);
							if (data.Hits !== 0) {
								onSuccess(data);
								return;
							}
						} catch (e) {
							console.error('Error parsing cached response. Will try server.', e)
						}
					}
				} else {
					if (uuid === undefined) uuid = primary_ix_uuid;
					let res = get_cached_xfirstword_responses(uuid);
					if (res !== null && res[0] !== null) {
						//console.info('Using cached single index response', res[0]);
						try {
							let data = JSON.parse(res[0]);
							if (data.Hits !== 0)
							{
								onSuccess(data);
								return;
							}
						} catch (e) {
							console.error('Error parsing cached response. Will try server.', e)
						}
					}
				}
			}
			console.info('Using response from SC1');
			let query_json = compile_query_json();
			$.ajax({
				type: "POST",
				url: "<?php echo esc_js( get_transient( 'ss1-endpoint-url' ) ); ?>/Search",
				data: JSON.stringify(query_json),
				contentType: "application/json",
				dataType: 'json',
				timeout: 20000,
				success: function (data, textStatus, xhr) {
					onSuccess(data);
				},
				error: function (xhr, textStatus, err) {
					onFailure(xhr);
				}
			});
		}


		/**
		 * Split String on Multiple Delimeters
		 * SO 32754177
		 */
		function multi_split (str, delimeters) {
			let result = [str];
			if (typeof (delimeters) == 'string')
				delimeters = [delimeters];
			while (delimeters.length > 0) {
				for (let i = 0; i < result.length; i++) {
					let tempSplit = result[i].split(delimeters[0]);
					result = result.slice(0, i).concat(tempSplit).concat(result.slice(i + 1));
				}
				delimeters.shift();
			}
			return result;
		}

		/**
		 * External Highlight plugins need to know which words to highlight.
		 * Function strips out special dtSearch characters in query and just leaves words
		 */
		function get_hilite_words(query) {
			let delimeters = ['(',')',':',',','.','-','*','%','?',' '];
			let split_str  = multi_split(query, delimeters);
			let out_words  = [];
			for (let i  = 0 ; i < split_str.length; i++) {
				let str = split_str[i];
				str = str.trim();
				if (str.length > 0) {
					if (str.toLowerCase() === 'and') continue;
					if (str.toLowerCase() === 'or')  continue;
					out_words.push(str);
				}
			}
			return out_words;
		}

		function get_formatted_link_text(result, link_format) {
			if (!link_format) link_format = get_link_format();
			let parts = link_format.split("%%");
			let len = parts.length;
			let i   = 0;
			let str = "";
			while (i < len) {
				let s = parts[i];
				let even = i % 2 === 0;
				if (even) {
					str += s;
				} else
				{
					if (s.startsWith('field:')) {
						let args = s.split(':');
						let fieldArgs = multi_split(args[1],['(',')']);
						let fieldName = fieldArgs[0];
						if (result.FieldValues.hasOwnProperty(fieldName)
							&& result.FieldValues[fieldName] !== ''
						) {
							let beforeStr = '';
							let afterStr  = '';
							if (args.length > 2) beforeStr = args[2];
							if (args.length > 3) afterStr  = args[3];
							let middleStr = '';
							if (   fieldArgs.length === 1
								&& fieldArgs[0] !== 'Published') middleStr = result.FieldValues[fieldName];
							else {
								// A field arg is present. Only supported for 'Published' at the moment.
								if (fieldArgs[0] === 'Published') {
									//console.info('Parsing date ',result.FieldValues[fieldName]);
									// 3 Nov 2021 at 05:42PM UTC
									let inFormat = 'DD MMM YYYY [at] HH:mm:A zz'
									let mom = moment(result.FieldValues[fieldName],inFormat);
									//console.info('date moment', mom);
									if (fieldArgs.length > 1)
									{
										let format = fieldArgs[1];
										middleStr = mom.format(format);
									} else {
										middleStr = mom.format('D MMMM YYYY');
									}
								} else {
									middleStr = result.FieldValues[fieldName];
								}
							}
							str += beforeStr + middleStr + afterStr;
						}
					}
					<?php
					if ( $premium ) {
						?>
					if (s.startsWith('img:')) {
						let args = s.split(':');
						if (args.length >= 2)
						{
							let fieldName  = args[1];
							if (result.FieldValues.hasOwnProperty(fieldName))
							{
								let src = result.FieldValues[fieldName];
								if (src.length > 0) {
									str += "<img src='" + src + "'";
									if (args.length > 2) {
										let mClass = args[2];
										if (mClass.length > 0 && mClass.length < 64) {
											str += " class='" + mClass + "'"
										}
									}
									str += ">";
								}
							}
						}
					}
						<?php
					}
					?>
					if (s === "doc_title") {
						str += result.DocDisplayName;
					}
					if (s === "context" || s.startsWith("context:")) {
						str += result.Context
					}
				}
				++i;
			}
			return str;
		}

		function get_num_words_of_context() {
			let link_format  = get_link_format();
			console.info('Getting num words of context', link_format);
			if (!link_format) {
				//console.info('scenario a');
				return 25;
			}
			let parts = link_format.split("%%");
			let len = parts.length;
			let i = 0;
			while (i < len) {
				let s = parts[i];
				if (s.startsWith("context")) {
					let args = s.split(':');
					if (args.length === 1) {
						//console.info('scenario b');
						return 25;
					}
					else {
						let x = parseInt(args[1]);
						//console.info('num words of context', x);
						if (x > 200) return 200;
						if (x < 0) return 0;
						return x;
					}
				}
				++i;
			}
			return 0; // Context not in link format, context not wanted.
		}

		function refresh_session(refresh_facets)
		{
			let tokens_url = '<?php echo esc_js( rest_url( 'ss1_client/v1/tokens' ) ); ?>';
			$.ajax({
				type: 'GET',
				url: tokens_url,
				timeout: 30000,
				success: function(data, textStatus, xhr) {
					console.info('Got token', data);
					session_token = data;
					display_results(refresh_facets, '<?php esc_html_e( 'Session Expired. Please wait...', 'site-search-one' ); ?>');
				},
				error: function(xhr, textSTatus, error) {
					console.info('Failed to obtain new token', xhr);
					$('#info-display').html('<i><?php esc_html_e( 'Something went wrong. Check connection and try again.', 'site-search-one' ); ?></i>');
				}
			})
		}

		function save_modal_changes(closeModal = true) {
			modalHaltedRefreshing = true;
			let modalFacets = $('#modal-facets-lv');
			let fieldName = modalFacets.find('.modal-title').attr('data-field-name');
			let catFacetsDsp = $('.category-facets');
			let ul = catFacetsDsp.find('.facet-field[data-fn="' + fieldName + '"]').find('ul');
			modalFacets.find('input').each(function() {
				let li = $(this).closest('li');
				let fieldValue = li.find('.form-check').attr('data-fv');
				let checked = $(this).is(':checked');
				if (checked) {
					add_facet(fieldName, fieldValue, false);
				} else {
					remove_facet(fieldName, fieldValue, false);
				}
			});
			if (closeModal) {
				modalFacets.modal('hide');
			} else {
				modalFacets.find('input').each(function() {
					$(this).prop('disabled', true);
				});
				window.addEventListener('ResultsLoaded', facets_modal_handle_results_change);
			}
			modalHaltedRefreshing = false;
			display_results(false);
		}

		function facets_modal_handle_results_change() {
			window.removeEventListener('ResultsLoaded', facets_modal_handle_results_change);
			let modalFacets = $('#modal-facets-lv');
			let fieldName = modalFacets.find('.modal-title').attr('data-field-name');
			let dspName = get_facet_field_display_name(fieldName);
			show_facet_modal(fieldName, dspName);
			sync_modal_checkboxes();
		}

		let modalHaltedRefreshing = false;

		function checkbox_change_evt() {
			// 1. Disable all checkboxes whilst loading the next set of results
			let facetRefinementContainer = $('#container-facet-panel');
			facetRefinementContainer.find('input[type=checkbox]').each(function() {
				$(this).attr("disabled", true);
			});
			// 2. Load the next set of results..
			current_page = 1;
			let checked = $(this).is(":checked");
			let field   = $(this).closest('.form-check').attr('data-fn');
			let value   = $(this).closest('.form-check').attr('data-fv');
			if (checked) {
				add_facet(field, value);
				$(this).closest('label').css({'font-weight':'600'});
			} else {
				remove_facet(field, value);
				$(this).closest('label').css({'font-weight':'400'});
			}
		}

		/**
		 * For each Modal Checkbox, if the facet sidebar checkbox is checked, check on the modal.
		 */
		function sync_modal_checkboxes() {
			let modal = $('#modal-facets-lv');
			let fieldName = modal.find('.modal-title').attr('data-field-name');
			// let facetView = $('.category-facets').find('.facet-field[data-fn="' + fieldName + '"]');
			for (let i = 0; i < facets.length; i++)
			{
				if (facets[i].field === fieldName) {
					modal.find('.form-check[data-fv="' + facets[i].value +'"]').find('input').prop('checked', true);
				}
			}
		}

		let facets = [];

		function add_facet(field, value, reload = true) {
			for (let i = 0; i < facets.length; i++)
			{
				if (facets[i].field === field && facets[i].value === value) {
					return;
				}
			}
			console.info('Adding Facet', field, value);
			let facet = {};
			facet.field = field;
			facet.value = value;

			facets.push(facet);
			if (reload) {
				display_results(false);
			}
		}

		function remove_facet(field, value, reload = true) {
			console.info('Removing facet', field, value);
			for (let i = 0; i < facets.length; i++) {
				let facet = facets[i];
				if (facet.field === field && facet.value === value) {
					facets.splice(i,1);
					if (reload) {
						display_results(false);
					}
					return;
				}
			}
			if (reload) {
				display_results(false);
			}
		}

		function clear_facets() {
			facets = [];
			$('#container-facet-panel').find('input').prop('checked', false);
			display_results(false);
		}

		function get_facet_query(skipField = '') {
			let query = [];
			if (facets.length > 0) {
				for (let i = 0; i < facets.length; ++i) {
					let field = facets[i].field;
					if (field === skipField) continue;
					let value = facets[i].value;
					// let foundField = false;
					// for (let ii = 0; ii < query.length; ii++) {
					//     if (query[ii].Field === field) {
					//         foundField = true;
					//         query[ii].Values.push(value);
					//         break;
					//     }
					// }
					// if (!foundField) {
					query.push({
						Field: field,
						Values: [value]
					});
					// }
				}
			}
			return query;
		}

		let refreshing = false;

		$(window).on('resize',function() {
			// Only update on resize if we're not currently fetching search results.
			if (!refreshing) {
				update_expand_buttons();
			}
		});

		function hookAllFieldsBtn()
		{
			let allFieldsBtn = $('#all-fields-btn');
			allFieldsBtn
				.off()
				.click(function () {
					let open = ($(this).attr('data-open') === 'true');
					open = !open;
					if (open === true) {
						allFieldsBtn.html('<?php esc_html_e( 'Close', 'site-search-one' ); ?>');
						$('#container-search-outcome')
							.removeClass('frc-limited')
							.addClass('frc-maximised');
						$(this).attr('data-open', 'true');


					} else {
						allFieldsBtn.html('All Fields »');
						$('#container-search-outcome')
							.addClass('frc-limited')
							.removeClass('frc-maximised');
						$(this).attr('data-open', 'false');

						$('.field-values').each(function () {
							let ul = $(this);
							ul.find(':checked').each(function () {
								let li = $(this).closest('li');
								let fieldValue = li.find('.form-check').attr('data-fv');
								// Move the selection to the top of the list so it's visible.
								ul.find('.form-check[data-fv="' + fieldValue + '"]').prependTo(ul);
								ul.find('.form-check').click();
							});
						});
					}
				});
		}

		/*
		Hides/Shows 'Show More' buttons in Facet Search depending on window size.
		 */
		function update_expand_buttons() {
			let width = $(window).width();
			let max_values = MAX_VALUES_PER_FACET_DISPLAYED;
			if (width <= 620) {
				if ($('#all-fields-btn').attr('data-open') !== 'true') {
					// Only when the facets are not maximised is this reduced
					max_values = MAX_VALUES_PER_FACET_DISPLAYED_620;
				}
			}
			let max_fields = 5;
			if (width <= 620 && width > 450) {
				max_fields = 3;
			}
			if (width <= 450 && width > 300) {
				max_fields = 2;
			}
			if (width <= 300) {
				max_fields = 1;
			}

			let catFacets = $('#container-facet-panel .category-facets');
			// 1. Hiding/Showing the 'More' button of Facet Values
			// depending on how many values there are.
			// For every Category Facets, check every field to see
			// if there are enough values to require the 'More...'
			// button to be shown.
			let facetFields = $(catFacets).find('.facet-field.displayed');
			facetFields.each(function() {
				let fn = $(this).data('fn');
				let valueCount = $(this).find('li').length;

				if (valueCount > max_values) {
					//console.debug(fn + ' has ' + valueCount + ' items. Showing link-more-fv');
					$(this).find('.link-more-fv').removeClass('hidden');
				} else {
					///console.debug(fn + ' has ' + valueCount + ' items. Hiding link-more-fv');
					$(this).find('.link-more-fv').addClass('hidden');
				}
			});
			// 2. Hiding/Showing the 'More' button of Facet Fields
			// depending on how many fields there are.
			let displayed_results = $('.search-result-container').length;
			let fieldsCount = facetFields.length;
			if (fieldsCount > max_fields && displayed_results > 0) {
				$('#all-fields-btn').removeClass('hidden')

			} else {
				$('#all-fields-btn').addClass('hidden');
			}
		}

		/**
		 * True on first load if url contains ?restore query param
		 * Causes filters to be restored from sessionStorage
		 */
		let restoring = false;

		/**
		 * True on first load if url contains ?query param
		 * User would have been sent here from a different page
		 *
		 */
		let referred = false;

		window.addEventListener("message", (event) => {
			let data = event.data;
			if (data
				&& typeof data === 'object'
				&& 'type' in data
				&& data.type)
			{
				switch(data.type)
				{
					case 'searchbar_update':
						if (restoring) return;
						restore_query(data);
						break;
					case 'set_dark_mode':
						let enabled = data.enabled;
						set_dark_mode_enabled(enabled);
						break;
					default:
						break;
				}

			}
		});

		function set_dark_mode_enabled(enabled = true)
		{
			if (enabled) {
				$('html').addClass('dark-mode');
			} else {
				$('html').removeClass('dark-mode');
			}
		}

		function restore_query(query, from_navigation = false)
		{
			$('#info-display').html('<i><?php esc_html_e( 'Loading...', 'site-search-one' ); ?></i>');
			if (query == null) {
				// No query to restore...
				display_results(true);
				return;
			}
			let msg = (from_navigation) ? 'As a result of navigation from Widget' : 'As a result of update from Widget';
			console.info('Restoring query', query, msg);
			restoring = true;

			let searchType = query.values.searchType;
			if (searchType !== null) $('input[name="search-type"][value="' + searchType + '"]').prop('checked', true);
			$('#textbox-query').val(query.values.query);
			if ($('#select-index').length) {
				// Multiple Categories. Restore the index.
				let option_to_select = $('#select-index').find('option[data-ix-uuid="' + query.values.ix_uuid + '"]');
				$('#select-index').val(option_to_select.attr('value')).change();
			}
			// These checkboxes must be changed AFTER select-index changes, due to select-index change event
			// Setting the checkboxes check state to their default state
			if (query.values.stemming !== null) $('#check-box-stemming').prop('checked', query.values.stemming);
			if (query.values.synonyms !== null) $('#check-box-synonyms').prop('checked', query.values.synonyms);
			populate_filter_items_html(function() { // Async Operation if Filter fields not cached
				query.values.filters.forEach((filter) => {
					let fieldName    = filter[0];
					let dataType = $('.filter-item[data-filter-name="' + fieldName + '"]').attr('data-filter-datatype');
					console.info('Data type for field ' + fieldName + ' ' + dataType);
					switch (dataType)
					{
						case 'DateTime':
							$('.filter-item[data-filter-name="' + fieldName + '"]').find('.filter-checkbox').prop('checked', true);
							let dateValStart = filter[1];
							let dateValEnd = filter[2];
							$('.filter-item[data-filter-name="' + fieldName + '"]').find('.sc1-date-from').val(dateValStart);
							$('.filter-item[data-filter-name="' + fieldName + '"]').find('.sc1-date-to').val(dateValEnd);
							break;
						case 'Multi-Choice':

							let fieldVal = filter[1];
							console.info('Multi-Choice field' + fieldName + ' fieldValue ' + fieldVal);
							let select = $('.filter-item[data-filter-name="' + fieldName + '"]').find('select');
							if (select.length)
							{
								set_select_value(select, fieldVal);
							} else {
								console.error('Could not find select for fieldName', fieldName);
							}
							break;
						default:
							let fieldValue   = filter[1];
							$('.filter-item[data-filter-name="' + fieldName + '"]').find('.filter-checkbox').prop('checked', true);
							$('.filter-item[data-filter-name="' + fieldName + '"]').find('.filter-textbox').val(fieldValue);
							break;

					}
				});
				restoring = false;
				apply_filters(false);
				display_results(true);
			});

		}

		/* Search bar elements are moved to a new row when the layout is small enough */
		let isLayoutMobile = false;
		function setLayoutMobile(mobile) {
			if ($('#select-index').length) {
				if (mobile && !isLayoutMobile) {
					// Switching to mobile layout
					$('#btn-filter-panel-toggle').appendTo('#search-bar-mobile');
					$('#select-index').prependTo('#search-bar-mobile');

					//$('#search-bar').removeClass('input-group');
					$('#search-bar > :first-child').css({
						'border-bottom-left-radius': "0"
					});
					$('#search-bar > :last-child').css({
						'border-bottom-right-radius': '0'
					});
					$('#search-bar-mobile > :last-child').css({
						'border-top-right-radius': '0'
					})
					$('#search-bar-mobile > :first-child').css({
						'border-top-left-radius': '0'
					});
					// border-top-right-radius: 0;
					//border-bottom-right-radius: 0;

				}
				if (!mobile && isLayoutMobile) {
					// Exiting mobile layout
					// Move elements
					$('#btn-filter-panel-toggle').insertBefore('#btn-search');
					$('#select-index').prependTo('#search-bar');
					// Remove Element styling
					$('#search-bar').css('width', '');
					$('#select-index').css('width', '');
					$('#select-index .index-selector').css('width', '');
					$('#search-bar').addClass('input-group');
					$('#search-bar > :first-child').css({
						'border-bottom-left-radius': ''
					});
					$('#search-bar > :last-child').css({
						"border-bottom-right-radius": ''
					});
					$('#search-bar-mobile > :last-child').css({
						'border-top-right-radius': ''
					})
					$('#search-bar-mobile > :first-child').css({
						'border-top-left-radius': ''
					});

				}
			}
			isLayoutMobile = mobile;
		}
		/*
		On page load, get some initial results
		 */
		$(document).ready(function() {

			//region Set available search types...
			$('input[name="search-type"]').each(function() {
				$(this).parent().hide();
			});
			<?php
			$search_types = array();
			if ( property_exists( $display_opts, 'search_types' ) ) {
				$search_types = $display_opts->search_types;
			} else {
				array_push( $search_types, 'bool' );
			}
			$def_search_type = $search_types[0];
			if ( property_exists( $display_opts, 'def_search_type' ) ) {
				$def_search_type = $display_opts->def_search_type;
			}
			if ( count( $search_types ) > 1 ) {
				// Display/hide appropriate radios.
				foreach ( $search_types as $search_type ) {
					?>
			$('input[name="search-type"][value="<?php echo esc_attr( $search_type ); ?>"]').parent().show();
					<?php
				}
			} // Else all radios should be hidden
			?>
			$('input[name="search-type"][value="<?php echo esc_attr( $def_search_type ); ?>"]').prop('checked', true);

			//endregion
			// Doing the filters first ensures the filter spec is cached,
			// Which is used to determine the placement of Taxonomies/Select drop downs on the UI.
			populate_filter_items_html(function(){
				update_synonyms_stemming_checkbox_dsp();
				<?php
				if ( isset( $_GET['query'] ) ) {
					$query = sanitize_text_field( wp_unslash( $_GET['query'] ) );
					?>

				let query = decodeURIComponent("<?php echo esc_js( $query ); ?>");
				query = unescapeQuotes(query);
				console.info('Detected URL Query: ', query);
				referred = true;
				$("#textbox-query").val(query);
					<?php
				}
				if ( isset( $_GET['restore'] ) ) {
					?>
				let query_to_restore = JSON.parse(sessionStorage.getItem('ss1-search-restore'));
				restore_query(query_to_restore, true);
					<?php
				//phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				} elseif ( ! property_exists( $display_opts, 'initialSearch' ) || true === $display_opts->initialSearch ) {
				// phpcs:enable
					?>
				$('#info-display').html('<i><?php esc_html_e( 'Loading...', 'site-search-one' ); ?></i>');
				display_results(true);
					<?php
				}
				?>
			});

			$('input[name="search-type"]').change(function() {
				display_results(true);
			});
			//region Execute code each time window size changes
			let windowWidth = $(window).width();
			setLayoutMobile(windowWidth < 512);
			$(window).on('resize', function() {
				if ($(this).width() !== windowWidth) {
					windowWidth = $(this).width();
					setLayoutMobile(windowWidth < 512);
					console.info('Resize event. Refreshing:', refreshing);
					if (!refreshing) {
						update_expand_buttons();
					}
				}
			});
			//endregion
		});

		function unescapeQuotes(string) {
			return string.replace(/\\"/g, '"');
		}
	});

</script>
<script>
	/*! iFrame Resizer (iframeSizer.contentWindow.min.js) - v3.6.1 - 2018-04-29
	*  Desc: Include this file in any page being loaded into an iframe
	*        to force the iframe to resize to the content size.
	*  Requires: iframeResizer.min.js on host page.
	*  Copyright: (c) 2018 David J. Bradshaw - dave@bradshaw.net
	*  License: MIT
	*/
	!function(a){"use strict";function b(a,b,c){"addEventListener"in window?a.addEventListener(b,c,!1):"attachEvent"in window&&a.attachEvent("on"+b,c)}function c(a,b,c){"removeEventListener"in window?a.removeEventListener(b,c,!1):"detachEvent"in window&&a.detachEvent("on"+b,c)}function d(a){return a.charAt(0).toUpperCase()+a.slice(1)}function e(a){var b,c,d,e=null,f=0,g=function(){f=Ha(),e=null,d=a.apply(b,c),e||(b=c=null)};return function(){var h=Ha();f||(f=h);var i=xa-(h-f);return b=this,c=arguments,i<=0||i>xa?(e&&(clearTimeout(e),e=null),f=h,d=a.apply(b,c),e||(b=c=null)):e||(e=setTimeout(g,i)),d}}function f(a){return ma+"["+oa+"] "+a}function g(a){la&&"object"==typeof window.console&&console.log(f(a))}function h(a){"object"==typeof window.console&&console.warn(f(a))}function i(){j(),g("Initialising iFrame ("+location.href+")"),k(),n(),m("background",W),m("padding",$),A(),s(),t(),o(),C(),u(),ia=B(),N("init","Init message from host page"),Da()}function j(){function b(a){return"true"===a}var c=ha.substr(na).split(":");oa=c[0],X=a!==c[1]?Number(c[1]):X,_=a!==c[2]?b(c[2]):_,la=a!==c[3]?b(c[3]):la,ja=a!==c[4]?Number(c[4]):ja,U=a!==c[6]?b(c[6]):U,Y=c[7],fa=a!==c[8]?c[8]:fa,W=c[9],$=c[10],ua=a!==c[11]?Number(c[11]):ua,ia.enable=a!==c[12]&&b(c[12]),qa=a!==c[13]?c[13]:qa,Aa=a!==c[14]?c[14]:Aa}function k(){function a(){var a=window.iFrameResizer;g("Reading data from page: "+JSON.stringify(a)),Ca="messageCallback"in a?a.messageCallback:Ca,Da="readyCallback"in a?a.readyCallback:Da,ta="targetOrigin"in a?a.targetOrigin:ta,fa="heightCalculationMethod"in a?a.heightCalculationMethod:fa,Aa="widthCalculationMethod"in a?a.widthCalculationMethod:Aa}function b(a,b){return"function"==typeof a&&(g("Setup custom "+b+"CalcMethod"),Fa[b]=a,a="custom"),a}"iFrameResizer"in window&&Object===window.iFrameResizer.constructor&&(a(),fa=b(fa,"height"),Aa=b(Aa,"width")),g("TargetOrigin for parent set to: "+ta)}function l(a,b){return-1!==b.indexOf("-")&&(h("Negative CSS value ignored for "+a),b=""),b}function m(b,c){a!==c&&""!==c&&"null"!==c&&(document.body.style[b]=c,g("Body "+b+' set to "'+c+'"'))}function n(){a===Y&&(Y=X+"px"),m("margin",l("margin",Y))}function o(){document.documentElement.style.height="",document.body.style.height="",g('HTML & body height set to "auto"')}function p(a){var e={add:function(c){function d(){N(a.eventName,a.eventType)}Ga[c]=d,b(window,c,d)},remove:function(a){var b=Ga[a];delete Ga[a],c(window,a,b)}};a.eventNames&&Array.prototype.map?(a.eventName=a.eventNames[0],a.eventNames.map(e[a.method])):e[a.method](a.eventName),g(d(a.method)+" event listener: "+a.eventType)}function q(a){p({method:a,eventType:"Animation Start",eventNames:["animationstart","webkitAnimationStart"]}),p({method:a,eventType:"Animation Iteration",eventNames:["animationiteration","webkitAnimationIteration"]}),p({method:a,eventType:"Animation End",eventNames:["animationend","webkitAnimationEnd"]}),p({method:a,eventType:"Input",eventName:"input"}),p({method:a,eventType:"Mouse Up",eventName:"mouseup"}),p({method:a,eventType:"Mouse Down",eventName:"mousedown"}),p({method:a,eventType:"Orientation Change",eventName:"orientationchange"}),p({method:a,eventType:"Print",eventName:["afterprint","beforeprint"]}),p({method:a,eventType:"Ready State Change",eventName:"readystatechange"}),p({method:a,eventType:"Touch Start",eventName:"touchstart"}),p({method:a,eventType:"Touch End",eventName:"touchend"}),p({method:a,eventType:"Touch Cancel",eventName:"touchcancel"}),p({method:a,eventType:"Transition Start",eventNames:["transitionstart","webkitTransitionStart","MSTransitionStart","oTransitionStart","otransitionstart"]}),p({method:a,eventType:"Transition Iteration",eventNames:["transitioniteration","webkitTransitionIteration","MSTransitionIteration","oTransitionIteration","otransitioniteration"]}),p({method:a,eventType:"Transition End",eventNames:["transitionend","webkitTransitionEnd","MSTransitionEnd","oTransitionEnd","otransitionend"]}),"child"===qa&&p({method:a,eventType:"IFrame Resized",eventName:"resize"})}function r(a,b,c,d){return b!==a&&(a in c||(h(a+" is not a valid option for "+d+"CalculationMethod."),a=b),g(d+' calculation method set to "'+a+'"')),a}function s(){fa=r(fa,ea,Ia,"height")}function t(){Aa=r(Aa,za,Ja,"width")}function u(){!0===U?(q("add"),F()):g("Auto Resize disabled")}function v(){g("Disable outgoing messages"),ra=!1}function w(){g("Remove event listener: Message"),c(window,"message",S)}function x(){null!==Z&&Z.disconnect()}function y(){q("remove"),x(),clearInterval(ka)}function z(){v(),w(),!0===U&&y()}function A(){var a=document.createElement("div");a.style.clear="both",a.style.display="block",document.body.appendChild(a)}function B(){function c(){return{x:window.pageXOffset!==a?window.pageXOffset:document.documentElement.scrollLeft,y:window.pageYOffset!==a?window.pageYOffset:document.documentElement.scrollTop}}function d(a){var b=a.getBoundingClientRect(),d=c();return{x:parseInt(b.left,10)+parseInt(d.x,10),y:parseInt(b.top,10)+parseInt(d.y,10)}}function e(b){function c(a){var b=d(a);g("Moving to in page link (#"+e+") at x: "+b.x+" y: "+b.y),R(b.y,b.x,"scrollToOffset")}var e=b.split("#")[1]||b,f=decodeURIComponent(e),h=document.getElementById(f)||document.getElementsByName(f)[0];a!==h?c(h):(g("In page link (#"+e+") not found in iFrame, so sending to parent"),R(0,0,"inPageLink","#"+e))}function f(){""!==location.hash&&"#"!==location.hash&&e(location.href)}function i(){function a(a){function c(a){a.preventDefault(),e(this.getAttribute("href"))}"#"!==a.getAttribute("href")&&b(a,"click",c)}Array.prototype.forEach.call(document.querySelectorAll('a[href^="#"]'),a)}function j(){b(window,"hashchange",f)}function k(){setTimeout(f,ba)}function l(){Array.prototype.forEach&&document.querySelectorAll?(g("Setting up location.hash handlers"),i(),j(),k()):h("In page linking not fully supported in this browser! (See README.md for IE8 workaround)")}return ia.enable?l():g("In page linking not enabled"),{findTarget:e}}function C(){g("Enable public methods"),Ba.parentIFrame={autoResize:function(a){return!0===a&&!1===U?(U=!0,u()):!1===a&&!0===U&&(U=!1,y()),U},close:function(){R(0,0,"close"),z()},getId:function(){return oa},getPageInfo:function(a){"function"==typeof a?(Ea=a,R(0,0,"pageInfo")):(Ea=function(){},R(0,0,"pageInfoStop"))},moveToAnchor:function(a){ia.findTarget(a)},reset:function(){Q("parentIFrame.reset")},scrollTo:function(a,b){R(b,a,"scrollTo")},scrollToOffset:function(a,b){R(b,a,"scrollToOffset")},sendMessage:function(a,b){R(0,0,"message",JSON.stringify(a),b)},setHeightCalculationMethod:function(a){fa=a,s()},setWidthCalculationMethod:function(a){Aa=a,t()},setTargetOrigin:function(a){g("Set targetOrigin: "+a),ta=a},size:function(a,b){N("size","parentIFrame.size("+(a||"")+(b?","+b:"")+")",a,b)}}}function D(){0!==ja&&(g("setInterval: "+ja+"ms"),ka=setInterval(function(){N("interval","setInterval: "+ja)},Math.abs(ja)))}function E(){function b(a){function b(a){!1===a.complete&&(g("Attach listeners to "+a.src),a.addEventListener("load",f,!1),a.addEventListener("error",h,!1),k.push(a))}"attributes"===a.type&&"src"===a.attributeName?b(a.target):"childList"===a.type&&Array.prototype.forEach.call(a.target.querySelectorAll("img"),b)}function c(a){k.splice(k.indexOf(a),1)}function d(a){g("Remove listeners from "+a.src),a.removeEventListener("load",f,!1),a.removeEventListener("error",h,!1),c(a)}function e(b,c,e){d(b.target),N(c,e+": "+b.target.src,a,a)}function f(a){e(a,"imageLoad","Image loaded")}function h(a){e(a,"imageLoadFailed","Image load failed")}function i(a){N("mutationObserver","mutationObserver: "+a[0].target+" "+a[0].type),a.forEach(b)}function j(){var a=document.querySelector("body"),b={attributes:!0,attributeOldValue:!1,characterData:!0,characterDataOldValue:!1,childList:!0,subtree:!0};return m=new l(i),g("Create body MutationObserver"),m.observe(a,b),m}var k=[],l=window.MutationObserver||window.WebKitMutationObserver,m=j();return{disconnect:function(){"disconnect"in m&&(g("Disconnect body MutationObserver"),m.disconnect(),k.forEach(d))}}}function F(){var a=0>ja;window.MutationObserver||window.WebKitMutationObserver?a?D():Z=E():(g("MutationObserver not supported in this browser!"),D())}function G(a,b){function c(a){if(/^\d+(px)?$/i.test(a))return parseInt(a,V);var c=b.style.left,d=b.runtimeStyle.left;return b.runtimeStyle.left=b.currentStyle.left,b.style.left=a||0,a=b.style.pixelLeft,b.style.left=c,b.runtimeStyle.left=d,a}var d=0;return b=b||document.body,"defaultView"in document&&"getComputedStyle"in document.defaultView?(d=document.defaultView.getComputedStyle(b,null),d=null!==d?d[a]:0):d=c(b.currentStyle[a]),parseInt(d,V)}function H(a){a>xa/2&&(xa=2*a,g("Event throttle increased to "+xa+"ms"))}function I(a,b){for(var c=b.length,e=0,f=0,h=d(a),i=Ha(),j=0;j<c;j++)(e=b[j].getBoundingClientRect()[a]+G("margin"+h,b[j]))>f&&(f=e);return i=Ha()-i,g("Parsed "+c+" HTML elements"),g("Element position calculated in "+i+"ms"),H(i),f}function J(a){return[a.bodyOffset(),a.bodyScroll(),a.documentElementOffset(),a.documentElementScroll()]}function K(a,b){function c(){return h("No tagged elements ("+b+") found on page"),document.querySelectorAll("body *")}var d=document.querySelectorAll("["+b+"]");return 0===d.length&&c(),I(a,d)}function L(){return document.querySelectorAll("body *")}function M(b,c,d,e){function f(){da=m,ya=n,R(da,ya,b)}function h(){function b(a,b){return!(Math.abs(a-b)<=ua)}return m=a!==d?d:Ia[fa](),n=a!==e?e:Ja[Aa](),b(da,m)||_&&b(ya,n)}function i(){return!(b in{init:1,interval:1,size:1})}function j(){return fa in pa||_&&Aa in pa}function k(){g("No change in size detected")}function l(){i()&&j()?Q(c):b in{interval:1}||k()}var m,n;h()||"init"===b?(O(),f()):l()}function N(a,b,c,d){function e(){a in{reset:1,resetPage:1,init:1}||g("Trigger event: "+b)}function f(){return va&&a in aa}f()?g("Trigger event cancelled: "+a):(e(),"init"===a?M(a,b,c,d):Ka(a,b,c,d))}function O(){va||(va=!0,g("Trigger event lock on")),clearTimeout(wa),wa=setTimeout(function(){va=!1,g("Trigger event lock off"),g("--")},ba)}function P(a){da=Ia[fa](),ya=Ja[Aa](),R(da,ya,a)}function Q(a){var b=fa;fa=ea,g("Reset trigger event: "+a),O(),P("reset"),fa=b}function R(b,c,d,e,f){function h(){a===f?f=ta:g("Message targetOrigin: "+f)}function i(){var h=b+":"+c,i=oa+":"+h+":"+d+(a!==e?":"+e:"");g("Sending message to host page ("+i+")"),sa.postMessage(ma+i,f)}!0===ra&&(h(),i())}function S(a){function c(){return ma===(""+a.data).substr(0,na)}function d(){return a.data.split("]")[1].split(":")[0]}function e(){return a.data.substr(a.data.indexOf(":")+1)}function f(){return!("undefined"!=typeof module&&module.exports)&&"iFrameResize"in window}function j(){return a.data.split(":")[2]in{true:1,false:1}}function k(){var b=d();b in m?m[b]():f()||j()||h("Unexpected message ("+a.data+")")}function l(){!1===ca?k():j()?m.init():g('Ignored message of type "'+d()+'". Received before initialization.')}var m={init:function(){function c(){ha=a.data,sa=a.source,i(),ca=!1,setTimeout(function(){ga=!1},ba)}"interactive"===document.readyState||"complete"===document.readyState?c():(g("Waiting for page ready"),b(window,"readystatechange",m.initFromParent))},reset:function(){ga?g("Page reset ignored by init"):(g("Page size reset by host page"),P("resetPage"))},resize:function(){N("resizeParent","Parent window requested size check")},moveToAnchor:function(){ia.findTarget(e())},inPageLink:function(){this.moveToAnchor()},pageInfo:function(){var a=e();g("PageInfoFromParent called from parent: "+a),Ea(JSON.parse(a)),g(" --")},message:function(){var a=e();g("MessageCallback called from parent: "+a),Ca(JSON.parse(a)),g(" --")}};c()&&l()}function T(){"loading"!==document.readyState&&window.parent.postMessage("[iFrameResizerChild]Ready","*")}if("undefined"!=typeof window){var U=!0,V=10,W="",X=0,Y="",Z=null,$="",_=!1,aa={resize:1,click:1},ba=128,ca=!0,da=1,ea="bodyOffset",fa=ea,ga=!0,ha="",ia={},ja=32,ka=null,la=!1,ma="[iFrameSizer]",na=ma.length,oa="",pa={max:1,min:1,bodyScroll:1,documentElementScroll:1},qa="child",ra=!0,sa=window.parent,ta="*",ua=0,va=!1,wa=null,xa=16,ya=1,za="scroll",Aa=za,Ba=window,Ca=function(){h("MessageCallback function not defined")},Da=function(){},Ea=function(){},Fa={height:function(){return h("Custom height calculation function not defined"),document.documentElement.offsetHeight},width:function(){return h("Custom width calculation function not defined"),document.body.scrollWidth}},Ga={},Ha=Date.now||function(){return(new Date).getTime()},Ia={bodyOffset:function(){return document.body.offsetHeight+G("marginTop")+G("marginBottom")},offset:function(){return Ia.bodyOffset()},bodyScroll:function(){return document.body.scrollHeight},custom:function(){return Fa.height()},documentElementOffset:function(){return document.documentElement.offsetHeight},documentElementScroll:function(){return document.documentElement.scrollHeight},max:function(){return Math.max.apply(null,J(Ia))},min:function(){return Math.min.apply(null,J(Ia))},grow:function(){return Ia.max()},lowestElement:function(){return Math.max(Ia.bodyOffset()||Ia.documentElementOffset(),I("bottom",L()))},taggedElement:function(){return K("bottom","data-iframe-height")}},Ja={bodyScroll:function(){return document.body.scrollWidth},bodyOffset:function(){return document.body.offsetWidth},custom:function(){return Fa.width()},documentElementScroll:function(){return document.documentElement.scrollWidth},documentElementOffset:function(){return document.documentElement.offsetWidth},scroll:function(){return Math.max(Ja.bodyScroll(),Ja.documentElementScroll())},max:function(){return Math.max.apply(null,J(Ja))},min:function(){return Math.min.apply(null,J(Ja))},rightMostElement:function(){return I("right",L())},taggedElement:function(){return K("right","data-iframe-width")}},Ka=e(M);b(window,"message",S),T()}}();
	//# sourceMappingURL=iframeResizer.contentWindow.map
</script>
</html>
