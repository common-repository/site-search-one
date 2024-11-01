<?php
/**
 * Page for viewing the list of Search Pages configured by the user.
 *
 * @package Site_Search_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


add_action( 'in_admin_footer', 'my_admin_footer_function' );
/**
 * Print the build number in the admin footer.
 */
function my_admin_footer_function() {
	echo '<p>Build ' . esc_html( SITE_SEARCH_ONE_BUILD ) . '</p>';
}

?>
<style>
	<?php
	echo esc_html( file_get_contents( plugin_dir_path( __FILE__ ) . 'css/site-search-one-admin.css' ) );
	?>
</style>
<div class="wrap ss1-admin">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Search Pages', 'site-search-one' ); ?></h1>
	<?php
	$create_url = admin_url( 'admin.php?page=' . trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) ) . 'view-new-search-page.php';
	?>
	<a href="<?php echo esc_url( $create_url ); ?>" class="page-title-action">
		<?php esc_html_e( 'Add New', 'site-search-one' ); ?>
	</a>
	<hr class="wp-header-end">
	<br>
	<table class="wp-list-table widefat fixed striped pages ss1-splist" style="max-width: 600px">
		<thead>
			<tr>
				<th class="manage-column column-primary column-name" scope="col" style="width: 80%">
					<?php esc_html_e( 'Pages', 'site-search-one' ); ?>
				</th>
				<th class="manage-column column-ss1-default-cb" scope="col">
					<?php esc_html_e( 'Default', 'site-search-one' ); ?>
				</th>
			</tr>
		</thead>
		<tbody>
		<?php
		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-search-page.php';
		$search_pages = Site_Search_One_Search_Page::get_all_search_pages();
		if ( is_wp_error( $search_pages ) ) {
			$search_pages = array();
			?>
			<tr>
				<th>
					<p>Error fetching search pages. Try refreshing the page.</p>
				</th>
			</tr>
			<?php
		}
		foreach ( $search_pages as $search_page ) {
			$sp_post_id = $search_page->get_post_id();
			$page_name  = get_the_title( $sp_post_id );
			?>
			<tr>
				<th>
					<div>
						<p><?php echo esc_html( $page_name ); ?></p>
						<?php
						$edit_nonce    = wp_create_nonce( 'SS1-Edit-SP-' . $sp_post_id );
						$options_nonce = wp_create_nonce( 'SS1-Opts-SP-' . $sp_post_id );
						// Note - This is echoing generated HTML.
						// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
						echo $search_page->get_description_html();
						// phpcs:enable
						?>
						<p class="sp-index-status ss1-ix-status-box" data-ix-uuid="<?php echo esc_attr( $search_page->get_sc1_ix_uuid() ); ?>">
							<?php esc_html_e( 'Loading...', 'site-search-one' ); ?>
						</p>
					</div>
					<div class="ss1-buttons-row" style="float:right; margin-left: 0.5em">
						<button class="button sp-delete-btn" data-post-id="<?php echo esc_attr( $sp_post_id ); ?>" data-post-name="<?php echo esc_attr( htmlspecialchars( $page_name, ENT_QUOTES ) ); ?>">
							<?php esc_html_e( 'Delete', 'site-search-one' ); ?>
						</button>
						<button class="button sp-recreate-btn" data-post-id="<?php echo esc_attr( $sp_post_id ); ?>">
							<?php esc_html_e( 'Rebuild', 'site-search-one' ); ?>
						</button>
						<button class="button sp-compress-btn" data-ix-uuid="<?php echo esc_attr( $search_page->get_sc1_ix_uuid() ); ?>">
							<?php esc_html_e( 'Compress', 'site-search-one' ); ?>
						</button>
						<button class="button sp-scan-btn" data-post-id="<?php echo esc_attr( $sp_post_id ); ?>">
							<?php esc_html_e( 'Update', 'site-search-one' ); ?>
						</button>
						<button class="button sp-options-btn" data-post-id="<?php echo esc_attr( $sp_post_id ); ?>" data-nonce="<?php echo esc_attr( $options_nonce ); ?>">
							<?php esc_html_e( 'Options', 'site-search-one' ); ?>
						</button>
						<button class="button sp-edit-btn" data-post-id="<?php echo esc_attr( $sp_post_id ); ?>" data-nonce="<?php echo esc_attr( $edit_nonce ); ?>">
							<?php esc_html_e( 'Edit', 'site-search-one' ); ?>
						</button>
					</div>
				</th>
				<th>
					<?php
					$checked = '';
					if ( get_transient( 'ss1-searchform-override' ) !== false ) {
						if ( intval( $sp_post_id ) === intval( get_transient( 'ss1-searchform-override' ) ) ) {
							$checked = ' checked';
						}
					}
					?>
					<input class="ss1-checkbox-default" type="checkbox" value="<?php echo esc_attr( $sp_post_id ); ?>" <?php echo esc_attr( $checked ); ?>>
				</th>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
	<br><br><br>
	<h3>
		<?php esc_html_e( 'Other Sites', 'site-search-one' ); ?>
	</h3>

	<table id="table-other-site-indexes" class="wp-list-table widefat fixed striped pages" style="max-width: 600px">
		<thead>
			<tr>
				<th class="manage-column column-primary column-name" scope="col" style="width: 80%">
					<?php esc_html_e( 'Index', 'site-search-one' ); ?>
				</th>
				<th>

				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th>
					<?php esc_html_e( 'Loading...', 'site-search-one' ); ?>
				</th>
				<th>
					<span id="ss1-indexes-loading-spinner" class="spinner is-active"></span>
				</th>
			</tr>
		</tbody>
	</table>
	<br><br><br>
	<span id="sync-settings-span" class="hidden">
	<h3 id="search-pages-sync-settings">
		<?php esc_html_e( 'Sync', 'site-search-one' ); ?>
	</h3>
	<button id="btn-ss1-sync-ctrl" class="button">
		<span class="spinner is-active"></span>
	</button>
	</span>
	<br><br>
	<h3>
		<?php esc_html_e( 'Reset Plugin', 'site-search-one' ); ?>
	</h3>
	<p>
		<?php esc_html_e( 'All of your search pages will be lost. You will need to configure the plugin again.' ); ?>
	</p>
	<button id="btn-sc1-reset" class="button"><?php esc_html_e( 'Reset', 'site-search-one' ); ?></button>
	<script type="application/javascript">

		/*
		On first load, load the status of all indexes, to retrieve indexes from other sites. This is slower.
		Afterwards, just load the displayed indexes..
		 */
		let isFirstLoad = true;

		function refreshIndexStatus() {
			if (document.hidden) {
				// The user is currently tabbed out. Don't refresh the page until the user tabs back in.
				setTimeout(refreshIndexStatus, 2000);
				return;
			}
			let indexes = [];
			jQuery('.ss1-ix-status-box').each(function() {
				indexes.push(jQuery(this).attr('data-ix-uuid'));
			});
			<?php
			require_once plugin_dir_path( __FILE__ ) . 'class-sc1-index-manager.php';
			$api_key = SC1_Index_Manager::get_sc1_api_key();
			?>
			let apiKey = "<?php echo esc_js( $api_key ); ?>";
			let endpoint = "<?php echo esc_js( get_transient( 'ss1-endpoint-url' ) ); ?>/IndexManager";
			let data = {
					'APIKey' : apiKey,
					'Action' :'ListIndexes',
					'FilterToIndexes' : indexes,
					'IncludeMetaSpec' : false,
					'IncludeRecycleCount' : false,
					'IncludeNotices' : false,
					'IncludeIndexInfo' : true
			};
			if (isFirstLoad) {
				delete data.FilterToIndexes;
			}
			jQuery.ajax({
				type: 'post',
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				url: endpoint,
				data: JSON.stringify(data),
				success: function(data, textStatus, xhr) {
					if (isFirstLoad) {
						let table_body = jQuery('#table-other-site-indexes').find('tbody');
						table_body.html('');
					}
					let seconds     = 10;
					let numWithPending = 0;
					try {
						let Indexes = data.Indexes;
						let numOtherSites = 0;
						for (let i = 0; i < Indexes.length; i++) {
							let Index = Indexes[i];
							let ixUUID = Index.IndexUUID;

							let elem = jQuery('p[data-ix-uuid="' + ixUUID + '"');
							if (elem.length) {
								// One of our search pages
								let obsoleteCount = Index.ObsoleteCount;
								let fragmentation = Index.Fragmentation;
								let pendingIndex = Index.Pending;
								elem.attr('data-obsolete', '' + obsoleteCount);
								elem.attr('data-fragmentation', '' + fragmentation);
								elem.attr('data-pending', '' + pendingIndex);
							} else {
								// It's from another site.
								let ix_name = Index.Name;
								ix_name = ix_name.replace("SS1 Search Page - ","");
								let table_row = jQuery('<tr>')
									.append('<th>' + ix_name + '</th><th><button class="button ix-del-btn" data-ix-uuid="' + ixUUID + '"><?php esc_html_e( 'Delete', 'site-search-one' ); ?></button></th>');
								let table_body = jQuery('#table-other-site-indexes').find('tbody');
								table_body.append(table_row);
								numOtherSites++;
							}

						}
						if (isFirstLoad) {
							if (numOtherSites === 0)
							{
								let table_row = jQuery('<tr>')
									.append('<th><?php esc_html_e( 'None found', 'site-search-one' ); ?></th><th></th>');
								let table_body = jQuery('#table-other-site-indexes').find('tbody');
								table_body.append(table_row);
							}
							jQuery('.ix-del-btn').click(function() {
							let btn = jQuery(this);
							let ix_uuid = btn.attr('data-ix-uuid');
							let ix_name = btn.closest('tr').first('th').text();
							<?php
								/* translators: The index name to delete */
								$delete_str = __( 'Delete %s? This cannot be undone.', 'site-search-one' );
							?>
							let confirmed = confirm(
								"<?php echo esc_html( $delete_str ); ?>".replace('%s', ix_name)
							);
							if (confirmed) {
								btn.html('<span class="spinner is-active"></span>');
								let params = {
									delete_ext_ix : {
										ix_uuid : ix_uuid
									}
								};
								jQuery('.ix-del-btn').prop('disabled', true);
								jQuery.ajax({
									type: 'post',
									contentType: 'application/json; charset=utf-8',
									dataType: 'json',
									url: '<?php echo( esc_url( rest_url( 'ss1_client/v1/options' ) ) ); ?>',
									data: JSON.stringify(params),
									success: function(data) {
										if (data.success === true) {
											btn.closest('tr').remove();
											jQuery('.ix-del-btn').prop('disabled', false);
										} else {
											alert('<?php esc_html_e( 'Something went wrong. Check your connection and try again.', 'site-search-one' ); ?>');
											location.reload();
										}
									},
									error: function() {
										alert('<?php esc_html_e( 'Something went wrong. Check your connection and try again.', 'site-search-one' ); ?>');
										location.reload();
									}
								});
							}
							});
						}
					} finally {
						if (numWithPending  > 0) seconds = 60;
						setTimeout(refreshIndexStatus, 1000 * seconds);
						isFirstLoad = false;
					}
				},
				error: function() {
					setTimeout(refreshIndexStatus, 1000 * 10);
				}
			});
		}

		refreshIndexStatus();

		function ui_tick() {
			// Loop through status's for all indexes and update displayed values such as pending, obsolete count etc.
			jQuery('.sp-index-status').each(function() {
				if (jQuery(this)[0].hasAttribute('data-obsolete')) {
					// Finished loading.
					let obsoleteCount = parseInt(jQuery(this).attr('data-obsolete'));
					let fragmentation = parseInt(jQuery(this).attr('data-fragmentation'));
					let pending       = parseInt(jQuery(this).attr('data-pending'));
					let htmlStatus 	  =
						"<?php esc_html_e( 'Obsolete Documents:', 'site-search-one' ); ?>"
						+ " "
						+ obsoleteCount
						+ "<br><?php esc_html_e( 'Fragmentation:', 'site-search-one' ); ?>"
						+ " "
						+ fragmentation;
					if (pending > 0) {
						// Pending is a special case. We don't display true pending item count, but rather display a
						// value that ticks towards true spending and remains within 512 items, to give the appearance
						// of activity.
						let dspPending = 0;
						if (jQuery(this)[0].hasAttribute('data-dsp-pending')) {
							// Already displaying a pending value
							dspPending = parseInt(jQuery(this).attr('data-dsp-pending'));
						}
						if (dspPending === 0) {
							dspPending = pending; // Jump to true value.
						}
						if (dspPending > pending) {
							// Tick downwards, rate depending on how close we are to true value.
							if (dspPending > pending + 512) {
								dspPending = pending + 512;
							}
							if (dspPending > pending + 400) {
								dspPending -= 4;
							}
							if ((dspPending > pending + 300) && (dspPending <= pending + 400))
							{
								dspPending -= 3;
							}
							if ((dspPending > pending + 200) && (dspPending <= pending + 300))
							{
								dspPending -= 2;
							}
							if ((dspPending > pending ) && (dspPending <= pending + 300))
							{
								dspPending -= 1;
							}
						}
						else if (dspPending < pending) {
							// Tick upwards.
							if (dspPending < pending - 512) {
								dspPending = pending - 512;
							}
							if (dspPending >= pending - 100) {
								dspPending += 1;
							}
							if (dspPending < pending - 200 && dspPending >= pending - 300) {
								dspPending += 2;
							}
							if (dspPending < pending - 300) {
								dspPending += 4;
							}
						}
						jQuery(this).attr('data-dsp-pending', '' + dspPending);
						<?php
						/* translators: The number of documents pending */
						$indexing_str = __( 'Indexing %s documents', 'site-search-one' );
						?>
						htmlStatus = htmlStatus + "<br><?php echo esc_html( $indexing_str ); ?>".replace('%s',dspPending + "");
					}
					jQuery(this).html(htmlStatus);
				}
			})
		}

		setInterval(ui_tick, 1000);

		jQuery('.sp-scan-btn').click(function() {
			let post_id = jQuery(this).attr('data-post-id');
			jQuery(this).innerText = "<?php esc_html_e( 'Please wait...', 'site-search-one' ); ?>";
			jQuery(this).prop('disabled', true);
			let params = {
				scan_search_page : {
					post_id : post_id
				}
			};
			jQuery.ajax({
				type: 'post',
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				url: '<?php echo( esc_url( rest_url( 'ss1_client/v1/options' ) ) ); ?>',
				data: JSON.stringify(params),
				success: function() {
					location.reload();
				},
				error: function() {
					alert('<?php esc_html_e( 'Something went wrong. Check your connection and try again.', 'site-search-one' ); ?>');
					location.reload();
				}
			});

		});

		jQuery('.sp-edit-btn').click(function() {
			let post_id = jQuery(this).attr('data-post-id');
			let nonce = jQuery(this).attr('data-nonce');
			window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=' . trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) ) . 'view-new-search-page.php' ); ?>' + '&edit=' + post_id + "&_wpnonce=" + nonce;
		});

		jQuery('.sp-recreate-btn').click(function() {
			let post_id = jQuery(this).attr('data-post-id');
			let confirmed = confirm(
				'<?php esc_html_e( 'Rebuild index?', 'site-search-one' ); ?>'
				+ "\n\n"
				+ '<?php esc_html_e( 'Notice: Search results will be unavailable until indexing complete', 'site-search-one' ); ?>'
			);
			if (confirmed) {
				let params = {
					recreate_search_page : {
						post_id : post_id
					}
				};
				jQuery(this).innerText = "<?php esc_html_e( 'Please wait...', 'site-search-one' ); ?>";
				jQuery(this).prop('disabled', true);
				jQuery.ajax({
					type: 'post',
					contentType: 'application/json; charset=utf-8',
					dataType: 'json',
					url: '<?php echo( esc_url( rest_url( 'ss1_client/v1/options' ) ) ); ?>',
					data: JSON.stringify(params),
					success: function() {
						location.reload();
					},
					error: function() {
						alert('<?php esc_html_e( 'Something went wrong. Check your connection and try again.', 'site-search-one' ); ?>');
						location.reload();
					}
				});
			}
		});

		jQuery('.sp-compress-btn').click(function() {
				let confirmed = confirm(
					'<?php esc_html_e( 'Compress Index?', 'site-search-one' ); ?>'
					+ "\n\n"
					+ '<?php esc_html_e( 'Depending on the size of the Index, the operation may take a while', 'site-search-one' ); ?>'
				);
				if (confirmed) {
					jQuery(this).innerText = "<?php esc_html_e( 'Please wait...', 'site-search-one' ); ?>";
					<?php
					require_once plugin_dir_path( __FILE__ ) . 'class-sc1-index-manager.php';
					$api_key = SC1_Index_Manager::get_sc1_api_key();
					?>
					let apiKey = "<?php echo esc_js( $api_key ); ?>";
					let endpoint = "<?php echo esc_js( get_transient( 'ss1-endpoint-url' ) ); ?>/IndexManager";
					let data = {
						'APIKey': apiKey,
						'Action': 'ScheduleCompression',
						'IndexUUID': jQuery(this).attr('data-ix-uuid')
					};
					jQuery.ajax({
						type: 'post',
						contentType: 'application/json; charset=utf-8',
						dataType: 'json',
						url: endpoint,
						data: JSON.stringify(data),
						success: function () {
							alert('<?php esc_html_e( 'Compression started.', 'site-search-one' ); ?>');
							location.reload();
						},
						error: function () {
							alert('<?php esc_html_e( 'Something went wrong. Check your connection and try again.', 'site-search-one' ); ?>');
						}
					});
				}
		});

		jQuery('.sp-options-btn').click(function() {
			let post_id = jQuery(this).attr('data-post-id');
			let nonce   = jQuery(this).attr('data-nonce');
			let url = '<?php echo esc_js( admin_url( 'admin.php?page=' . trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) ) . 'view-customise-search-page.php' ); ?>&_wpnonce=' + nonce;
			window.location.href = url + "&post_id=" + post_id;
		});

		let default_post_id = -1;
		<?php
			// This is necessary because browser tries to be smart on page reload and leaves old checkbox
			// checked.. So just do it all with javascript..
		if ( get_transient( 'ss1-searchform-override' ) !== false ) {
			?>
				default_post_id = <?php echo esc_js( get_transient( 'ss1-searchform-override' ) ); ?>
				<?php
		}
		?>

		jQuery('.ss1-checkbox-default').each(function() {
			let cb = jQuery(this);
			cb[0].checked = parseInt(default_post_id) === parseInt(cb.val());
		});

		jQuery('.ss1-checkbox-default').change(function() {

			let parent = jQuery(this).parent();
			parent.append('<span class="spinner is-active"></span>');

			let changed_post_id = jQuery(this).val();
			let checked = jQuery(this).prop('checked');
			let post_id = -1;
			if (checked) post_id = changed_post_id;
			let params = {
				set_override_default_search : {
					post_id : post_id
				}
			};

			jQuery.ajax({
				type: 'post',
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				url: '<?php echo( esc_js( rest_url( 'ss1_client/v1/options' ) ) ); ?>',
				data: JSON.stringify(params),
				success: function() {
					location.reload();
				},
				error: function() {
					alert('<?php esc_html_e( 'Something went wrong. Check your connection and try again.', 'site-search-one' ); ?>');
					location.reload();
				}
			});

		});

		jQuery('.sp-delete-btn').click(function() {

			let post_id     = jQuery(this).attr('data-post-id');
			let post_name   = jQuery(this).attr('data-post-name');
			<?php
			// translators: The search page name to delete.
			$dialog_title = esc_html__( 'Delete %s? This cannot be undone.', 'site-search-one' );
			?>
			let confirmed = confirm("<?php echo esc_html( $dialog_title ); ?>".replace('%s', post_name)
			);
			if (confirmed) {
				jQuery(this).innerText = '<?php esc_html_e( 'Deleting...', 'site-search-one' ); ?>';
				jQuery(this).prop('disabled', true);
				let params = {
					delete_search_page : {
						post_id : post_id
					}
				};
				jQuery.ajax({
					type: 'post',
					contentType: 'application/json; charset=utf-8',
					dataType: 'json',
					url: '<?php echo( esc_js( rest_url( 'ss1_client/v1/options' ) ) ); ?>',
					data: JSON.stringify(params),
					success: function() {
						location.reload();
					},
					error: function() {
						alert('<?php esc_html_e( 'Something went wrong. Check your connection and try again.', 'site-search-one' ); ?>');
						location.reload();
					}
				})
			}
		});
		jQuery('#btn-sc1-reset').click(function() {
			let confirmed = confirm('<?php esc_html_e( 'Are you sure you want to reset the plugin?', 'site-search-one' ); ?>');
			if (confirmed) {
				let params = {};
				params.resetPlugin = true;
				jQuery.ajax({
					type: 'post',
					contentType: 'application/json; charset=utf-8',
					dataType: 'json',
					url: '<?php echo( esc_js( rest_url( 'ss1_client/v1/options' ) ) ); ?>',
					data: JSON.stringify(params),
					success: function() {
						alert('<?php esc_html_e( 'Plugin was Reset', 'site-search-one' ); ?>');
						window.location.href = '<?php echo esc_js( get_admin_url() ); ?>';
					},
					error: function() {
						alert('<?php esc_html_e( 'Something went wrong. Check your connection and try again.', 'site-search-one' ); ?>');
					}
				});
			}
		});
	</script>
</div>
