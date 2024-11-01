<?php
/**
 * Admin Page used to Create and Edit Search Pages
 *
 * @package Site_Search_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print out category checkboxes
 *
 * @param string                            $class_name The class name.
 * @param bool                              $disabled Whether or not checkboxes are disabled.
 * @param string                            $parent The parent category.
 * @param Site_Search_One_Search_Page|false $edit The search page that is being edited, if any.
 */
function print_category_checkboxes( $class_name, $disabled, $parent, $edit ) {
	$categories = get_categories(
		array(
			'orderby'    => 'name',
			'order'      => 'ASC',
			'hide_empty' => false,
			'parent'     => $parent,
		)
	);
	if ( count( $categories ) === 0 ) {
		return;
	}
	?>
	<ul>
		<?php
		foreach ( $categories as $category ) {
			$cat_id   = $category->term_id;
			$cat_name = $category->name;
			$checked  = ' checked';
			if ( 'page-category-checkbox' === $class_name && false !== $edit ) {
				// Printing for pages.
				if ( ! $edit->is_indexed_page_category( $cat_id ) ) {
					$checked = '';
				}
			}
			if ( 'category-checkbox' === $class_name && false !== $edit ) {
				// Printing for posts.
				if ( ! $edit->is_indexed_category( $cat_id ) ) {
					$checked = '';
				}
			}
			if ( 'media-category-checkbox' === $class_name && false !== $edit ) {
				if ( ! $edit->is_indexed_media_category( $cat_id ) ) {
					$checked = '';
				}
			}
			?>
		<li>
			<label>
				<input type="checkbox" class="<?php echo esc_attr( $class_name ); ?>" data-cat-id="<?php echo esc_attr( $cat_id ); ?>" <?php echo esc_attr( $checked ); ?> <?php echo esc_attr( $disabled ); ?>>
				<span><?php echo esc_html( $cat_name ); ?></span>
			</label>
			<?php
				print_category_checkboxes( $class_name, $disabled, $cat_id, $edit );
			?>
		</li>
			<?php
		}
		?>
	</ul>
	<?php
}

/**
 * Print out taxonomy checkboxes
 *
 * @param string                            $class_name The class name.
 * @param string                            $post_type The post type.
 * @param bool                              $disabled Whether or not tax checkbox is disabled.
 * @param false|Site_Search_One_Search_Page $edit The search page being edited, if any.
 */
function print_taxonomy_checkboxes( $class_name, $post_type, $disabled, $edit = false ) {
	?>
	<?php
	$obj_taxonomies = get_object_taxonomies( $post_type, 'objects' );

	foreach ( $obj_taxonomies as $obj_taxonomy ) {
		$name  = $obj_taxonomy->name;
		$label = $obj_taxonomy->label;
		?>
		<h4><?php echo esc_html( $label ); ?></h4>
		<?php
		$taxonomy_terms = get_terms(
			array(
				'taxonomy'   => $name,
				'hide_empty' => false,
				'fields'     => 'all',
			)
		);
		?>
		<ul>
		<?php
		foreach ( $taxonomy_terms as $taxonomy_term ) {
			$term_id   = $taxonomy_term->term_id;
			$term_name = $taxonomy_term->name;
			// TODO Currently this code assumes all printed tax term checkboxes are media and checks as such..
			$checked = '';
			if (
				false !== $edit
				&& $edit->is_media_filtered_on_terms()
				&& $edit->is_indexed_media_term_id( $term_id ) ) {
				$checked = ' checked';
			}
			?>
		<li>
			<label>
				<input type="checkbox" class="<?php echo esc_attr( $class_name ); ?>" data-tax-name="<?php echo esc_attr( $name ); ?>" data-term-id="<?php echo esc_attr( $term_id ); ?>" <?php echo esc_attr( $checked ); ?> <?php echo esc_attr( $disabled ); ?>>
				<span><?php echo esc_html( $term_name ); ?></span>
			</label>
		</li>
			<?php
		}
		?>
		</ul>
		<?php
	}
}

/**
 * Print out mimetype checkboxes
 *
 * @param string                            $class_name the class name.
 * @param string                            $disabled Whether or not mime type checkbox is disabled.
 * @param false|Site_Search_One_Search_Page $edit the search page being edited, if any.
 */
function print_mimetype_checkboxes( $class_name, $disabled = '', $edit = false ) {
	$allowed_mimes = get_allowed_mime_types();
	if ( null !== $edit && false !== $edit ) {
		$indexed_types = $edit->get_indexed_mime_types();
	} else {
		$indexed_types = Site_Search_One_Search_Page::get_default_allowed_mime_types();
	}
	?>
	<ul>
	<?php
	foreach ( $allowed_mimes as $allowed_mime ) {
		$checked = '';
		if ( in_array( $allowed_mime, $indexed_types, true ) ) {
			$checked = ' checked';
		}
		?>
		<li>
			<label>
				<input type="checkbox" class="<?php echo esc_attr( $class_name ); ?>" data-mime-type="<?php echo esc_attr( $allowed_mime ); ?>" <?php echo esc_attr( $checked ); ?> <?php echo esc_attr( $disabled ); ?>>
				<span><?php echo esc_html( $allowed_mime ); ?></span>
			</label>
		</li>
		<?php
	}
	?>
	</ul>
	<?php
}

/**
 * Try requiring functions from the premium plugin, if it is installed.
 *
 * @return bool|WP_Error
 */
function try_require_premium_functions() {
	if ( class_exists( 'Site_Search_One_Premium_Functions' ) ) {
		return true;
	} else {
		$plugins = get_plugins();
		foreach ( $plugins as $plugin_dir => $plugin ) {
			if ( 'Site Search ONE Premium' === $plugin['Name'] ) {
				$install_loc = get_option( 'site-search-one-premium-install-location' );
				if ( false !== $install_loc ) {
					$path = $install_loc . '/admin/class-site-search-one-p-functions.php';
					if ( file_exists( $path ) ) {
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

?>
<style>
	<?php
	echo esc_html( file_get_contents( plugin_dir_path( __FILE__ ) . 'css/site-search-one-admin.css' ) );
	?>
</style>
<style>
	<?php
	echo esc_html( file_get_contents( plugin_dir_path( __FILE__ ) . 'css/select2.min.css' ) );
	?>
	.select2 {
		margin: 1em
	}
</style>
<script>
	<?php
	require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-search-page.php';
	//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	// TODO Admin enqueues.
	echo file_get_contents( plugin_dir_path( __FILE__ ) . 'js/select2.min.js' );
	//phpcs:enable
	$edit = false;
	if ( isset( $_GET['edit'] ) ) {
		// Editing an existing search page.
		$sp_post_id = sanitize_text_field( wp_unslash( $_GET['edit'] ) );
		check_ajax_referer( 'SS1-Edit-SP-' . $sp_post_id ); // Nonce verification.
		$edit = Site_Search_One_Search_Page::get_search_page( $sp_post_id );
	}

	/**
	 * Get label for given taxonomy name.
	 *
	 * @param string $taxonomy_name the taxonomy name.
	 *
	 * @return string
	 */
	function get_taxonomy_label( $taxonomy_name ) {
		$taxonomy = get_taxonomy( $taxonomy_name );
		$label    = $taxonomy->label;
		if ( null === $label || '' === $label ) {
			return $taxonomy->name;
		} else {
			return $taxonomy->label;
		}
	}



	?>
</script>
<script>


	document.addEventListener('DOMContentLoaded', (event) => {
		//region Get elements
		let buttonSave = document.getElementById('ss1-btn-save');
		let buttonSaveValidation = document.getElementById('save-error');
		let textBoxPageName = document.getElementById('ss1-page-name');
		let textBoxPageNameValidation = document.getElementById('page-name-error');
		let textBoxPostTypeValidation = document.getElementById('post-type-error');
		let checkBoxIndexPostsPagesValidation = document.getElementById('posts-pages-error');
		let radioPagesAll = document.getElementById('radio_pages_all');
		let radioPagesSelected = document.getElementById('radio_pages_selected');
		let radioPagesExclude = document.getElementById('radio_pages_exclude');
		let radioPagesByCategory = document.getElementById('radio_pages_by_category');
		let radioPostTypesAll = document.getElementById('ss1-radio-post-types-all');
		let radioPostTypesSelected = document.getElementById('ss1-radio-post-types-selected');
		let radioButtonAllCategories = document.getElementById('ss1-radio-all-categories');
		let radioButtonSelectedCategories = document.getElementById('ss1-radio-selected-categories');
		let radioButtonsCategoryValidation = document.getElementById('categories-error');
		let radioMediaTaxonomiesAll = document.getElementById('ss1-radio-all-media');
		let radioMediaTaxonomiesSelected = document.getElementById('ss1-radio-selected-media-categories');
		let spinnerSaving = document.getElementById('saving-spinner');

			//endregion
		//region If Premium Plugin not installed, hide premium sections
		<?php
		if ( try_require_premium_functions() !== true ) {
		?>
		document.getElementById('premium-media').style.display = "none";
		<?php
		}
		?>
		//endregion

			//region Helper functions
			String.prototype.isNullOrWhitespace = function () {
				return (this.length === 0 || !this.trim());
			};
			//endregion
			//region Register event listeners
			textBoxPageName.addEventListener('blur', validatePageName);
			textBoxPageName.addEventListener('change', validatePageName);
			buttonSave.addEventListener('click', onClickSave);

			//endregion
			function onClickSave() {
				console.info('Clicked save');
				textBoxPostTypeValidation.innerText = '';
				//region 1. Get values
				let ixAllCategories = radioButtonAllCategories.checked;
				let specialCasePages = [];
				if (!radioPagesAll.checked) {
					let selected_pages = document.querySelectorAll('#special-case-pages > li');
					for (let i = 0; i < selected_pages.length; i++)
					{
						let selected_page = selected_pages[i];
						specialCasePages.push(
							parseInt(selected_page.getAttribute('data-id'))
						);
					}
				}
				let ixPages = (radioPagesAll.checked || radioPagesExclude.checked || radioPagesByCategory.checked);

				let pageCategories = null;
				if (radioPagesByCategory.checked) {
					// Pages are filtered by category. Non-standard behaviour in WP but can be enabled w/ plugins..
					pageCategories = getCheckedPageCategories();
				}

				let post_types = null;
				if (radioPostTypesSelected.checked) {
					let selected_post_types = document.querySelectorAll('.post-type-checkbox:checked');
					post_types = [];
					for (let i = 0; i < selected_post_types.length; i++) {
						let selected_post_type = selected_post_types[i];
						post_types.push(selected_post_type.getAttribute('data-type-name'));
					}
					// if (post_types.length === 0) {
					//     textBoxPostTypeValidation.innerText = 'Must select at least one type';
					//     return;
					// }
				}

				let media_term_ids = null;
				if (document.getElementById('ss1-radio-selected-media-categories').checked) {
					media_term_ids = getCheckedMediaTaxonomies();
				}

				let attached_media_only = document.getElementById('ss1-attached-media-only').checked;
				let media_mime_types = [];
				let selected_mime_type_checkboxes = document.querySelectorAll('.media-mime-checkbox:checked');
				for (let i = 0; i < selected_mime_type_checkboxes.length; i++) {
					let selected_mime_type_checkbox = selected_mime_type_checkboxes[i];
					media_mime_types.push(selected_mime_type_checkbox.getAttribute('data-mime-type'));
				}
				//endregion
				//region 2. Validate values

				let pageNameValid = validatePageName();
				if (!pageNameValid) return;
				let pageName = textBoxPageName.value;

				//region 3. If valid, save
				// a. Disable all controls whilst sending the request and show the spinner
				setAllControlsEnabled(false);
				buttonSaveValidation.innerText = '';
				spinnerSaving.classList.add('is-active');
				buttonSave.innerText = ('<?php esc_html_e( 'Saving...', 'site-search-one' ); ?>');
				// b. Build request
				let categories = [];
				let taxonomies = [];
				let ixPosts = true;
				if (!ixAllCategories) {
					categories = getCheckedCategories();
					taxonomies = getCheckedTaxonomies();
					if (categories.length === 0 && taxonomies.length === 0) ixPosts = false;
				}
				let options = {};
				<?php
				if ( false === $edit ) {
					?>
				options = {
					"new_search_page": {
						"name": pageName,
						"index_pages": ixPages,
						"pages": specialCasePages,
						"index_posts": ixPosts,
						"categories": categories,
						"taxonomies": taxonomies,
						"post_types": post_types,
						"page_categories": pageCategories,
						"media_term_ids" : media_term_ids,
						"attached_media_only": attached_media_only,
						"media_mime_types": media_mime_types
					}
				};
					<?php
				} else {
					// Editing an existing page.
					?>
				options = {
					"edit_search_page": {
						"post_id": <?php echo esc_js( intval( $edit->get_post_id() ) ); ?>,
						"name": pageName,
						"index_pages": ixPages,
						"pages": specialCasePages,
						"index_posts": ixPosts,
						"categories": categories,
						"taxonomies": taxonomies,
						"post_types": post_types,
						"page_categories"       : pageCategories,
						"media_term_ids"        : media_term_ids,
						"attached_media_only"   : attached_media_only,
						"media_mime_types"      : media_mime_types
					}
				};
					<?php
				}
				?>

			// c. Send Request
			jQuery.ajax({
				type: "POST",
				url: '<?php echo( esc_url( rest_url( 'ss1_client/v1/options' ) ) ); ?>',
				data: JSON.stringify(options),
				contentType: "application/json",
				timeout: 30000, // 10 seconds
				success: function () {
					spinnerSaving.classList.remove('is-active');
					buttonSave.innerText = 'Saved';
					// Take the user back to the list of search pages
					window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=' . trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) ) ) . 'view-search-pages.php'; ?>';
				}, error: function () {
					spinnerSaving.classList.remove('is-active');
					buttonSave.innerText = 'Save';
					setAllControlsEnabled(true);
					buttonSaveValidation.innerText = 'Something went wrong - Try again?';
				}
			});
			//endregion
		}

		function setAllControlsEnabled(enabled) {
			buttonSave.disabled = !enabled;
			textBoxPageName.disabled = !enabled;
			radioButtonAllCategories.disabled = !enabled;
			radioButtonSelectedCategories.disabled = !enabled;
			let categoryCheckboxes = document.querySelectorAll('input.category-checkbox');
			for (let i = 0; i < categoryCheckboxes.length; ++i) {
				categoryCheckboxes[i].disabled = !enabled;
			}
		}

		function validatePageName() {

			let name = textBoxPageName.value;
			if (name.isNullOrWhitespace()) {
				textBoxPageNameValidation.innerText = '<?php esc_html_e( 'Please enter a name', 'site-search-one' ); ?>';
				return false;
			}
			textBoxPageNameValidation.innerText = '';
			return true;
		}

		function validateCategories() {
			let isAllCategories = radioButtonAllCategories.checked;
			if (isAllCategories) {
				radioButtonsCategoryValidation.innerText = '';
				return true;
			} else {
				// Index selected categories - Check that at least one category is checked.
				let checkedCategories = getCheckedCategories();
				if (checkedCategories.length > 0) {
					radioButtonsCategoryValidation.innerText = '';
					return true;
				} else {
					radioButtonsCategoryValidation.innerText = '<?php esc_html_e( 'Please select at least one category', 'site-search-one' ); ?>';
					return false;
				}
			}
		}

		function getCheckedCategories() {
			let checkedCategories = [];
			let checkedCategoryElements = document.querySelectorAll('input.category-checkbox:checked');
			for (let i = 0; i < checkedCategoryElements.length; ++i) {
				let categoryId = checkedCategoryElements[i].dataset.catId;
				checkedCategories.push(categoryId);
			}
			return checkedCategories;
		}

		function getCheckedPageCategories() {
			let checkedCategories = [];
			let checkedCategoryElemenents = document.querySelectorAll('input.page-category-checkbox:checked');
			for (let i =0; i < checkedCategoryElemenents.length; ++i) {
				let catId = checkedCategoryElemenents[i].dataset.catId;
				checkedCategories.push(catId);
			}
			return checkedCategories;
		}

		function getCheckedTaxonomies() {
			let checkedTaxonomies = [];
			let checkedTaxonomyElements = document.querySelectorAll('input.taxonomy-checkbox:checked');
			for (let i = 0; i < checkedTaxonomyElements.length; i++) {
				let taxonomyId = checkedTaxonomyElements[i].dataset.termId;
				checkedTaxonomies.push(taxonomyId);
			}
			return checkedTaxonomies;
		}

		function getCheckedMediaTaxonomies() {
			let checkedTaxonomies = [];
			let checkedTaxonomyElements = document.querySelectorAll('input.media-taxonomy-checkbox:checked');
			for (let i = 0; i < checkedTaxonomyElements.length; i++) {
				let taxonomyId = checkedTaxonomyElements[i].dataset.termId;
				checkedTaxonomies.push(taxonomyId);
			}
			return checkedTaxonomies;
		}

	});
</script>
<script>
	(function ($) {
		$(document).ready(function() {

			function decodeHtmlEntities(str)
			{
				return str.replace(/&#(\d+);/g, function(match, dec) {
					return String.fromCharCode(dec);
				});
			}
			$('#btn-mime-toggle').click(function () {
				if ($(this).text() === 'Show') {
					$('#mime-list').show();
					$(this).text('Hide');
				} else {
					$('#mime-list').hide();
					$(this).text('Show');
				}
			});
			$('#btn-mime-uncheck-all').click(function () {
				$('.media-mime-checkbox').each(function() {
					$(this).prop('checked', false);
				});
			});
			$('#page-select').select2({
				ajax: {
					placeholder: "<?php esc_js( __( 'Add a page...', 'site-search-one' ) ); ?>",
					transport: function (params, success, failure) {
						console.info('Transport params', params);
						let page = params.data.page || 1;
						let query = "";
						if (params.data.term != null && params.data.term !== "") {
							query = "&search=" + encodeURIComponent(params.data.term);
						}
						$.ajax({
							url: '<?php echo( esc_url( rest_url( 'wp/v2/pages' ) ) ); ?>?_fields=id,title&page=' + page + query,
							success: function (data, textStatus, xhr) {
								let response = {
									wp_results: data,
									textStatus: textStatus,
									xhr: xhr,
									page: page
								};
								success(response);
							},
							error: function (xhr, textStatus, errorThrown) {
								failure();
							}
						});
					},
					processResults: function (data) {
						console.info('Processing Results:', data);
						let xhr = data.xhr;
						let page = data.page;
						let pagination_data = {
							totalResults: xhr.getResponseHeader('X-WP-Total'),
							totalPages: xhr.getResponseHeader('X-WP-TotalPages')
						};

						let select2Results = [];
						for (let i = 0; i < data.wp_results.length; i++)
						{
							let wp_result   = data.wp_results[i];
							let page_id     = wp_result.id;

							let page_title  = decodeHtmlEntities(wp_result.title.rendered);
							let select2Result = {
								id: page_id,
								text: page_title
							};
							select2Results.push(select2Result);
						}
						return {
							results: select2Results,
							pagination : {
								more: page < pagination_data.totalPages
							}
						};
					}
				}
			});
			$('#page-select').on('select2:select',function(e) {
				let data = e.params.data;
				let page_id = data.id;
				let page_name = data.text;
				console.info('Selected', page_name);
				add_special_case_page(page_id, page_name);
				$('#page-select').val(null).trigger('change');
			});
			$('input[type=radio]').change(function() {
				if ($('#ss1-radio-all-categories').is(":checked")) {
					$('.category-checkbox').each(function() {
						$(this).prop('checked', true);
						$(this).prop('disabled', true);
					});
					$('.taxonomy-checkbox').each(function() {
						$(this).prop('checked', true);
						$(this).prop('disabled', true);
					});
				} else
				{
					let allChecked = true;
					$('.category-checkbox').each(function () {
						if (!$(this).is(":checked")) allChecked = false;
					});
					$('.taxonomy-checkbox').each(function () {
						if (!$(this).is(":checked")) allChecked = false;
					});
					if (allChecked) {
						// Chances are we've deselected the 'all-categories' radio button
						$('.category-checkbox').each(function () {
							$(this).prop('checked', false);
						});
						$('.taxonomy-checkbox').each(function () {
							$(this).prop('checked', false);
						})
					}
					$('.category-checkbox').each(function () {
						$(this).prop('disabled', false);
					});
					$('.taxonomy-checkbox').each(function () {
						$(this).prop('disabled', false);
					});
				}
				if ($('#ss1-radio-post-types-all').is(":checked")) {
					$('.post-type-checkbox').each(function () {
						$(this).prop('disabled', true);
						$(this).prop('checked', true);
					});
				}
				else {
					let allChecked = true;
					$('.post-type-checkbox').each(function () {
						if (!$(this).is(":checked")) allChecked = false;
						$(this).prop('disabled', false);
					});
					if (allChecked) {
						$('.post-type-checkbox').each(function () {
							$(this).prop('checked', false);
						});
					}
				}
				if ($('#radio_pages_by_category').is(':not(:checked)'))
				{
					$('.page-category-checkbox').each(function () {
						$(this).prop('disabled', true);
						$(this).prop('checked', true);
					});
				} else {
					let allChecked = true;
					$('.page-category-checkbox').each(function () {
						if (!$(this).is(":checked")) allChecked = false;
						$(this).prop('disabled', false);
					});
					if (allChecked) {
						$('.page-category-checkbox').each(function () {
							$(this).prop('checked', false);
						});
					}
				}
				if ($('#ss1-radio-all-media').is(":checked")) {
					$('.media-taxonomy-checkbox').each(function() {
						$(this).prop('disabled', true);
						$(this).prop('checked', true);
					});
				} else {
					let allChecked = true;
					$('.media-taxonomy-checkbox').each(function() {
						if (!$(this).is(":checked")) allChecked = false;
						$(this).prop('disabled', false);
					});
					if (allChecked) {
						$('.media-taxonomy-checkbox').each(function() {
							$(this).prop('checked', false);
						});
					}
				}
			});

			<?php
			if ( false !== $edit ) {
				$special_cases = $edit->get_special_pages();
				foreach ( $special_cases as $special_case_post_id ) {
					$name = get_the_title( $special_case_post_id );
					?>
						add_special_case_page(<?php echo esc_html( intval( $special_case_post_id ) ); ?>,"<?php echo esc_html( $name ); ?>");
						<?php
				}
			}
			?>

			function add_special_case_page(id, name) {
				if (id !== null && name !== null) {
					let item = "<li data-id='" + id + "' style='min-height: 3em;'><span style='line-height:2.5; max-width: 18em; display: inline-block; text-overflow: ellipsis; overflow: hidden; max-height: 2em; white-space: nowrap;'>" + name + "</span><button class='button' style='float:right'>Remove</button></li>";
					$('#special-case-pages').append(item);
					$('#special-case-pages').find('li[data-id="' + id + '"]').find('button').click(function (e) {
						$(this).parent().remove();
					});
				}
			}
		});
	})(jQuery);
</script>
<div class="wrap ss1-admin">
	<?php
	if ( false === $edit ) {
		$doc_title = __( 'New Search Page', 'site-search-one' );
	} else {
		/* translators: Title of search page to edit */
		$doc_title = sprintf( __( 'Edit Search Page - %s', 'site-search-one' ), get_the_title( $sp_post_id ) );
	}
	?>
	<h1 class="wp-heading-inline"><?php echo esc_html( $doc_title ); ?></h1>
	<hr class="wp-header-end">
	<table class="form-table">
		<tbody>
		<tr>
			<th scope="row">
				<label for="ss1-page-name"><?php esc_html_e( 'Page Title', 'site-search-one' ); ?></label>
			</th>
			<td>
				<p id="page-name-error" class="ss1-validation-error"></p>
				<?php
					$page_name = '';
				if ( false !== $edit ) {
					$page_name = get_the_title( $edit->get_post_id() );
				}
				?>
				<input type="text" class="regular-text" id="ss1-page-name" aria-describedby="page-name-desc"  maxlength="60" value="<?php echo esc_attr( $page_name ); ?>">
<!--                <p id="page-name-desc" class="description">-->
<!--                    Enter a page name.-->
<!--                </p>-->
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="ss1-page-name"><?php esc_html_e( 'Pages', 'site-search-one' ); ?></label>
			</th>
			<td>
				<?php
				$all_pages     = ' checked';
				$include_pages = '';
				$exclude_pages = '';
				$by_category   = '';
				if ( false !== $edit ) {
					$special_cases = $edit->get_special_pages();
					if ( count( $special_cases ) > 0 || ! $edit->does_index_all_pages() ) {
						// There are special case pages or the search page does not index all pages..

						if ( $edit->are_pages_filtered_by_category() ) {
							$by_category   = ' checked';
							$all_pages     = '';
							$include_pages = '';
							$exclude_pages = '';
						} else {
							if ( $edit->are_special_case_pages_exclude() ) {
								$by_category   = '';
								$all_pages     = '';
								$include_pages = '';
								$exclude_pages = ' checked';
							} else {
								$by_category   = '';
								$all_pages     = '';
								$include_pages = ' checked';
								$exclude_pages = '';
							}
						}
					}
				}
				?>
				<fieldset>
					<label>
						<input name="radio_pages" class="ss1-radio-pages" type="radio" id="radio_pages_all" value="1"<?php echo esc_attr( $all_pages ); ?>>
						<span id="text_radio_pages_all"><?php esc_html_e( 'Index all pages', 'site-search-one' ); ?></span>
					</label>
					<br>
					<label>
						<input name="radio_pages" class="ss1-radio-pages" type="radio" id="radio_pages_selected" value="1"<?php echo esc_attr( $include_pages ); ?>>
						<span id="text_radio_filters_selected"><?php esc_html_e( 'Include:', 'site-search-one' ); ?></span>
						<br>
					</label>
					<label>
						<input name="radio_pages" class="ss1-radio-pages" type="radio" id="radio_pages_exclude" value="1"<?php echo esc_attr( $exclude_pages ); ?>>
						<span id="text_radio_filters_selected"><?php esc_html_e( 'Exclude:', 'site-search-one' ); ?></span>
						<br>
					</label>
					<div class="postbox" id="postbox-special-case-pages" style="max-width: 350px">
						<select id="page-select" name="page" style="width: 80%; margin: 1em">
							<option><?php esc_html_e( 'Add Page...', 'site-search-one' ); ?></option>
						</select>
						<div class="inside" style="min-height:20px">
							<ul id="special-case-pages" class="menu">
							</ul>
						</div>
					</div>
					<?php
						// Check if the 'categories' taxonomy has been assigned to pages
						// This is not standard WordPress behaviour, but we support it in
						// case users want to index pages by category.
						// Because this is nonstandard, I am hiding the categories option
						// for pages unless it has been enabled to avoid confusing users
						// with a default WordPress installation.
						$hidden          = 'ss1-hidden';
						$page_taxonomies = get_object_taxonomies( 'page' );
					foreach ( $page_taxonomies as $page_taxonomy ) {
						if ( 'category' === $page_taxonomy ) {
							$hidden = 'ss1-shown';
						}
					}
						// Also, if the user has already set categories for pages on this
						// search page and since disabled the non-standard WordPress behaviour
						// don't hide the option so the user can change it..
					if ( false !== $edit ) {
						if ( $edit->are_pages_filtered_by_category() ) {
							$hidden = 'ss1-shown';
						}
					}
					?>
					<label class="<?php echo esc_attr( $hidden ); ?>">
						<input name="radio_pages" class="ss1-radio-pages" type="radio" id="radio_pages_by_category" value="1"<?php echo esc_attr( $by_category ); ?>>
						<span id="text_radio_pages_all"><?php esc_html_e( 'By category:', 'site-search-one' ); ?></span>
					</label>
					<div class="postbox ss1-cat-select <?php echo esc_attr( $hidden ); ?>" data-post-include="Categories" style="max-width:350px">
						<div class="inside">
							<h4><?php esc_html_e( 'Categories', 'site-search-one' ); ?></h4>
							<?php
							// Retrieve array of Categories alphabetical.
							$disabled = '';
							if ( '' === $by_category ) {
								$disabled = ' disabled';
							}
							// For every category, add a Checkbox.
							print_category_checkboxes( 'page-category-checkbox', $disabled, 0, $edit );
							?>
						</div>
					</div>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label><?php esc_html_e( 'Posts', 'site-search-one' ); ?></label>
			</th>
			<td>
				<p id="categories-error" class="ss1-validation-error"></p>
				<fieldset>
					<?php
					$all_posts = ' checked';
					$selected  = '';
					if ( false !== $edit ) {
						if ( ! $edit->does_index_all_posts() ) {
							$all_posts = '';
							$selected  = ' checked';
						}
					}
					$disabled = '';
					if ( ' checked' === $all_posts ) {
						$disabled = ' disabled';
					}

					?>
					<label>
						<input name="index_posts" type="radio" id="ss1-radio-all-categories" value="1" <?php echo esc_attr( $all_posts ); ?>>
						<span id="text_radio_all_categories"><?php esc_html_e( 'Index all posts', 'site-search-one' ); ?></span>
					</label>
					<br>
					<label for="index_posts">
						<input name="index_posts" type="radio" id="ss1-radio-selected-categories" value="1" <?php echo esc_attr( $selected ); ?>>
						<span id="text_radio_selected_categories"><?php esc_html_e( 'Include:', 'site-search-one' ); ?></span>
						<br>
					</label>
					<div class="postbox ss1-cat-select" data-post-include="Categories" style="max-width:350px">
						<div class="inside">
							<h4><?php esc_html_e( 'Categories', 'site-search-one' ); ?></h4>
							<?php
							print_category_checkboxes( 'category-checkbox', $disabled, 0, $edit );
							?>
						</div>
					</div>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label><?php esc_html_e( 'Post types', 'site-search-one' ); ?></label>
			</th>
			<td>
				<p id="post-type-error" class="ss1-validation-error"></p>
				<fieldset>
					<?php
					$all_types      = ' checked';
					$selected_types = '';
					if ( false !== $edit ) {
						if ( $edit->does_filter_by_post_types() ) {
							$selected_types = ' checked';
							$all_types      = '';
						}
					}
					?>
					<label for="ss1-radio-post-types-all">
						<input name="post_types" type="radio" id="ss1-radio-post-types-all" value="1" <?php echo esc_attr( $all_types ); ?>>
						<span><?php esc_html_e( 'All post types', 'site-search-one' ); ?></span>
					</label>
					<br>
					<label for="ss1-radio-post-types-selected">
						<input name="post_types" type="radio" id="ss1-radio-post-types-selected" value="1" <?php echo esc_attr( $selected_types ); ?>>
						<span><?php esc_html_e( 'Selected post types:', 'site-search-one' ); ?></span>
					</label>
					<div class="postbox" id="postbox-post-types" style="max-width: 350px">
						<div class="inside">
							<?php
							$checked  = ' checked';
							$disabled = ' disabled';
							$label    = 'Posts';
							if ( false !== $edit ) {
								if ( $edit->does_filter_by_post_types() ) {
									$disabled = '';
									$checked  = '';
									if ( $edit->is_indexed_post_type( 'post' ) ) {
										$checked = ' checked';
									}
								}
							}
							?>
							<label>
								<input type="checkbox" class="post-type-checkbox" data-type-name="post" <?php echo esc_attr( $checked ); ?> <?php echo esc_attr( $disabled ); ?>>
								<span><?php esc_html_e( 'Posts', 'site-search-one' ); ?></span>
							</label>
							<br>
							<?php
							$post_type_args = array(
								'public'   => true,
								'_builtin' => false,
							);
							$post_types     = get_post_types( $post_type_args, 'object' );
							foreach ( $post_types as $wp_post_type ) {
								$label = $wp_post_type->label;
								if ( substr( $label, 0, strlen( 'Site Search ONE' ) ) === 'Site Search ONE' ) {
									continue;
								}
								if ( substr( $label, 0, strlen( 'Site Search One' ) ) === 'Site Search One' ) {
									continue;
								}
								$name           = $wp_post_type->name;
								$checked        = ' checked';
								$disabled       = ' disabled';
								$selected_types = array();
								if ( false !== $edit ) {
									if ( $edit->does_filter_by_post_types() ) {
										$disabled = '';
										$checked  = '';
										if ( $edit->is_indexed_post_type( $name ) ) {
											$checked = ' checked';
										}
									}
								}
								?>
								<label>
									<input type="checkbox" class="post-type-checkbox" data-type-name="<?php echo esc_attr( $name ); ?>"<?php echo esc_attr( $checked ); ?> <?php echo esc_attr( $disabled ); ?>>
									<span><?php echo esc_html( $label ); ?></span>
								</label>
								<br>
								<?php
							}
							?>
						</div>
					</div>
				</fieldset>
			</td>
		</tr>
		<span id="premium-media">
		<!-- TODO Only show if Premium -->
		<tr>
			<th scope="row">
				<label><?php esc_html_e( 'Media', 'site-search-one' ); ?></label>
			</th>
			<td>
				<p id="categories-error" class="ss1-validation-error"></p>
				<fieldset>
					<?php
					$all_posts = ' checked';
					$selected  = '';
					if ( false !== $edit ) {
						if ( $edit->is_media_filtered_on_terms() ) {
							$all_posts = '';
							$selected  = ' checked';
						}
					}
					$disabled = '';
					if ( ' checked' === $all_posts ) {
						$disabled = ' disabled';
					}
					$attached_only = ' checked';
					if ( false !== $edit && ! $edit->get_index_attached_media_only() ) {
						$attached_only = '';
					}
					?>
					<label>
						<input name="attached_media_only" type="checkbox" id="ss1-attached-media-only" <?php echo esc_attr( $attached_only ); ?>>
						<span id="text_attached_media_only"><?php esc_html_e( 'Attached media only', 'site-search-one' ); ?></span>
					</label>
					<br>
					<label>
						<input name="index_media" type="radio" id="ss1-radio-all-media" value="1" <?php echo esc_attr( $all_posts ); ?>>
						<span id="text_radio_all_categories"><?php esc_html_e( 'Index all media', 'site-search-one' ); ?></span>
					</label>
					<br>
					<label for="index_media">
						<input name="index_media" type="radio" id="ss1-radio-selected-media-categories" value="1" <?php echo esc_attr( $selected ); ?>>
						<span id="text_radio_selected_media_categories"><?php esc_html_e( 'Include:', 'site-search-one' ); ?></span>
						<br>
					</label>
					<div class="postbox ss1-cat-select" data-post-include="Categories" style="max-width:350px">
						<div class="inside">
							<?php
							$disabled = ' disabled';
							if ( false !== $edit && $edit->is_media_filtered_on_terms() ) {
								$disabled = '';
							}
							print_taxonomy_checkboxes( 'media-taxonomy-checkbox', 'attachment', $disabled, $edit );
							?>
						</div>
					</div>
					<div class="postbox ss1-cat-select" style="max-width: 350px">
						<div class="inside">
							<button id="btn-mime-toggle" class="button button-secondary" style="float: right"><?php esc_html_e( 'Show', 'site-search-one' ); ?></button>
							<h4><?php esc_html_e( 'Mime-types', 'site-search-one' ); ?></h4>
							<span id="mime-list" style="display: none">
							<button id="btn-mime-uncheck-all" class="button button-secondary" style="float: right"><?php esc_html_e( 'Uncheck all', 'site-search-one' ); ?></button>
							<?php
							print_mimetype_checkboxes( 'media-mime-checkbox', '', $edit );
							?>
							</span>
						</div>
					</div>
				</fieldset>
			</td>
		</tr>
		</span>
<!--        <tr>-->
<!--            <th scope="row">-->
<!--                <label for="include_tags">Included tags</label>-->
<!--            </th>-->
<!--            <td>-->
<!--                <fieldset>-->
<!--                    <input type="text" id="include_tags" aria-describedby="include-tags-desc">-->
<!--                </fieldset>-->
<!--            </td>-->
<!--        </tr>-->
<!--        <tr>-->
<!--            <th scope="row">-->
<!--                <label for="exclude_tags">Excluded tags</label>-->
<!--            </th>-->
<!--            <td>-->
<!--                <fieldset>-->
<!--                    <input type="text" id="exclude_tags" aria-describedby="exclude-tags-desc">-->
<!--                </fieldset>-->
<!--            </td>-->
<!--        </tr>-->
		</tbody>
	</table>
	<br>
	<span id="saving-spinner" class="spinner"></span>
	<p id="save-error" class="ss1-validation-error"></p>
	<button class="button button-primary" id="ss1-btn-save"><?php esc_html_e( 'Save', 'site-search-one' ); ?></button>
</div>
