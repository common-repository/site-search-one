/**
 * SearchCloudOne Token management.
 *
 * @package Site_Search_One
 */

(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	$( window ).on(
		'load',
		function() {
			let current_queued_tasks   = 0;
			let displayed_queued_tasks = 0;
			let ui_refresh_task        = setInterval( update_ui, 1000 );
			let paused                 = php_vars.ss1_paused;

			function update_ui() {
				if (document.hidden) {
					// The user is currently tabbed out. Don't refresh the page until the user tabs back in.
					return;
				}
				// Every second, update the UI with progress.
				$( '#sync-settings-span' ).removeClass( 'hidden' );
				if (paused) {
					shown = true;
					ss1Bar.html( "" );
					//phpcs:disable WordPress.WhiteSpace.OperatorSpacing
					//phpcs:disable Generic.Formatting.MultipleStatementAlignment.IncorrectWarning
					let toAppend = `
					<div>
						<p><strong>Site Search ONE</strong></p>
						<p>${ss1_paused_msg}</p>
					</div>
					<div>
						<a class='button' href='#' id='btn-ss1-resume-sync' style='float:right'>
						${btn_resume_txt}
						</a>
					</div>
					`;
					//phpcs:enable
					ss1Bar.append( toAppend );
					ss1Bar.removeClass( 'hidden' );
					ss1Bar.removeClass( 'notice-info' );
					ss1Bar.addClass( 'notice-warning' );
					$( '#search-pages-sync-settings' ).html( ss1_paused_msg );
					$( '#btn-ss1-sync-ctrl' ).html( btn_resume_txt );
					$( '#btn-ss1-sync-ctrl' ).off( 'click' );
					$( '#btn-ss1-resume-sync, #btn-ss1-sync-ctrl' ).on(
						'click',
						function() {
							clearInterval( ui_refresh_task );
							// Unpause..
							$( '#btn-ss1-resume-sync, #btn-ss1-sync-ctrl' ).html( '<span class="spinner is-active"></span>' );
							$.ajax(
								{
									type: "POST",
									url: ss1_options_url,
									data: JSON.stringify(
										{
											set_queue_paused: false
										}
									),
								contentType: 'application/json',
								timeout: 30000,
								success: function(data) {
									paused = false;
									update_ui();
									ui_refresh_task = setInterval( update_ui, 1000 );
								}, error: function() {
									alert( 'Something went wrong - Try again?' );
									ui_refresh_task = setInterval( update_ui, 1000 );
									update_ui();
								}
								}
							);
						}
					);
					return;
				} else {
					ss1Bar.addClass( 'notice-info' );
					ss1Bar.removeClass( 'notice-warning' );
				}
				console.info( 'current_queued_tasks', current_queued_tasks );
				if (current_queued_tasks > 0) {
					if (current_queued_tasks < displayed_queued_tasks) {
						displayed_queued_tasks--;
					} else if (current_queued_tasks - 10 > (displayed_queued_tasks)) {
						displayed_queued_tasks += 10;
					} else if (current_queued_tasks > displayed_queued_tasks) {
						displayed_queued_tasks += 1;
					}
				} else {
					displayed_queued_tasks = 0;
				}

				$( '#search-pages-sync-settings' ).html( ss1_pause_msg );
				$( '#btn-ss1-sync-ctrl' ).html( btn_pause_txt );
				if (displayed_queued_tasks > 0) {
					shown = true;
					ss1Bar.html( "" );
					let msg = ss1_syncing_msg.replace( '%s',displayed_queued_tasks.toString() );
					//phpcs:disable WordPress.WhiteSpace.OperatorSpacing
					//phpcs:disable Generic.Formatting.MultipleStatementAlignment.IncorrectWarning
					let toAppend = `
					<div>
						<p><strong>Site Search ONE</strong></p>
						<p>${msg}</p>
					</div>
					<div>
						<a class='button' href='#' id='btn-ss1-pause-sync' style='float:right'>
						${btn_pause_txt}
						</a>
					</div>
					`;
					//phpcs:enable

					ss1Bar.append( toAppend );
					ss1Bar.removeClass( 'hidden' );
				} else {
					if (shown) {
						ss1Bar.html( "" );
						//phpcs:disable WordPress.WhiteSpace.OperatorSpacing
						//phpcs:disable Generic.Formatting.MultipleStatementAlignment.IncorrectWarning
						let toAppend = `
						<div>
							<p><strong>Site Search ONE</strong></p>
							<p>${ss1_sync_complete_msg}</p>
						</div>
						`;
						//phpcs:enable
						ss1Bar.append( toAppend );
					}
				}
				$( '#btn-ss1-sync-ctrl' ).off( 'click' );
				$( '#btn-ss1-pause-sync, #btn-ss1-sync-ctrl' ).on(
					'click',
					function() {
						clearInterval( ui_refresh_task );
						// Pause..
						$( this ).html( '<span class="spinner is-active"></span>' );
						$.ajax(
							{
								type: "POST",
								url: ss1_options_url,
								data: JSON.stringify(
									{
										set_queue_paused: true
									}
								),
							contentType: 'application/json',
							timeout: 30000,
							success: function(data) {
								paused = true;
								update_ui();
								ui_refresh_task = setInterval( update_ui, 1000 );
							}, error: function() {
								alert( 'Something went wrong - Try again?' );
								ui_refresh_task = setInterval( update_ui, 1000 );
								update_ui();
							}
							}
						);
					}
				);
			}

			let ss1Bar = $( "#ss1-ongoing-dsp" );

			let shown                        = false;
			displayed_queued_tasks           = php_vars.ss1_remaining;
			let ss1_ongoing_tasks_url        = php_vars.ss1_rest_ongoing_url; // This is set in class-site-search-one-admin.php enqueue_scripts.
			let ss1_cron_hack_url            = php_vars.ss1_rest_cron_hack;
			let ss1_options_url              = php_vars.ss1_rest_options;
			let ss1_syncing_msg              = php_vars.ss1_syncing_msg;
			let ss1_sync_complete_msg        = php_vars.ss1_sync_complete_msg;
			let ss1_paused_msg               = php_vars.ss1_paused_msg;
			let ss1_pause_msg                = php_vars.ss1_pause_msg;
			let btn_pause_txt                = php_vars.ss1_btn_pause_txt;
			let btn_resume_txt               = php_vars.ss1_btn_resume_txt;
			let ss1DisableLongRunningThreads = php_vars.ss1_disableLongRunningThreads;
			console.debug( 'SS1 - Ongoing tasks url:', ss1_ongoing_tasks_url );

			update_ui();

			function checkSyncQueue() {
				if (document.hidden) {
					setTimeout( checkSyncQueue, 1000 );
					return; // Don't check the sync queue whilst tabbed out/page not current tab.
				}
				// console.debug('SS1 - Checking Sync Queue...');
				// region 1. Attempt to retrieve ongoing tasks.
				let queuedUploads = 0;
				$.ajax(
					{
						type: "GET",
						url: ss1_ongoing_tasks_url,
						success: function(data) {
							// console.debug('SS1: Retrieved Queue info', data);.
							paused        = data.paused;
							queuedUploads = parseInt( data.num_queued_uploads );
							if (queuedUploads === 0) {
								displayed_queued_tasks = 0;
							}
							if (displayed_queued_tasks > (queuedUploads + 30)) {
								displayed_queued_tasks = queuedUploads + 30; // don't let displayed queued uploads get too far behind.
							}
							if (displayed_queued_tasks < (queuedUploads - 100)) {
								if (current_queued_tasks === 0) {
									displayed_queued_tasks = queuedUploads;
								} else {
									displayed_queued_tasks = queuedUploads - 100;
								}
							}
							current_queued_tasks = queuedUploads;
							if (current_queued_tasks === 0) {
								setTimeout( checkSyncQueue, 1000 * 30 );
							} else {
								setTimeout( checkSyncQueue, 1000 * 10 );
							}

						},
						error: function(data) {
							// Don't update the UI, just try again in 5 seconds.
							console.error( 'SS1: Failed to retrieve Queue info', data );
							setTimeout( checkSyncQueue, 5000 )
						}
					}
				);
				// endregion.
			}

			let cronNumber = 0;

			/**
			 * Work around for cases where WP-Cron does not execute.. can even be caused when WP Cron is not disabled
			 * This is a fire and forget method. It just gets the process started. We don't care if the request times out
			 * Just that it hits the server.
			 * TODO Figure out why WP-Cron will fail to execute under some server installations
			 */
			function cronHack(checkHidden = true) {
				if (checkHidden && document.hidden) {
					setTimeout( cronHack, 1000 );
					return;
				}
				// Used as a cache buster and also to help prioritize certain tasks in certain conditions.
				let urlArgs = "?cronNumber=" + cronNumber;
				$.ajax(
					{
						type: "GET",
						url: ss1_cron_hack_url + urlArgs,
						timeout: 30000,
						success: function(data) {
							if (data.want_another_thread) {
								// console.info('Another thread wanted');.
								cronHack( false );
								++cronNumber;
							} else {
								let timeout = 30 * 1000;
								setTimeout( cronHack, timeout );
							}
						},
						error: function(data) {
							setTimeout( cronHack, 10 * 1000 );
						}
					}
				);
			}

			checkSyncQueue();
			setTimeout( cronHack, 200 );
		}
	);

})( jQuery );
