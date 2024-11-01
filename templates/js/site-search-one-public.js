/**
 * Public facing script that is always placed in pages. Handles search bars and also performs the cron workaround.
 *
 * @package           Site_Search_One
 */

(function( $ ) {
	'use strict';

	/**
	 * All of the code for your public-facing JavaScript source
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
		'load' ,
		function() {

			$( document ).on(
				'keydown',
				'.ss1-searchbar',
				function(ev) {
					if (ev.key === 'Enter') {
						submit_query( this );
						// Avoid form submit.
						return false;
					}
				}
			);

			function submit_query(elem) {
				let value = $( elem ).val();
				let url   = $( elem ).data( 'url' );
				try {
					url = url + "&query=" + encodeURIComponent( value );
				} finally {
					window.location.href = url;
				}
			}

			let ss1_cron_hack_url = php_vars.ss1_rest_cron_hack;
			let ss1_cron_wanted   = php_vars.ss1_is_cron_wanted;
			/**
			 * Work around for cases where WP-Cron does not execute.. can even be caused when WP Cron is not disabled
			 * This is a fire and forget method. It just gets the process started. We don't care if the request times out
			 * Just that it hits the server.
			 * TODO Figure out why WP-Cron will fail to execute under some server installations
			 */
			function cronHack(checkHidden = true) {
				if (document.hidden) {
					setTimeout( cronHack, 1000 );
					return;
				}
				$.ajax(
					{
						type: "GET",
						url: ss1_cron_hack_url,
						timeout: 30000,
						success: function(data) {
							if (data.want_another_thread) {
								cronHack( false );
							} else {
								// Do nada.
							}
						},
						error: function(data) {
							// Do nada.
						}
					}
				);
			}
			if (ss1_cron_wanted) {
				cronHack();
			}
		}
	);

})( jQuery );
