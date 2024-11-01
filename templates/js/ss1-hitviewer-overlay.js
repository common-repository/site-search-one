/**
 * Script for the hitviewer overlay. Shows and hides the overlay when window receives appropriate message.
 *
 * @package           Site_Search_One
 */

//phpcs:disable PEAR.Functions.FunctionCallSignature.Indent
jQuery( document ).ready(
	function($) {
		window.addEventListener(
			"message",
			(event) => {
				if (event.data) {
					if (event.data.action === 'SS1-HV-Overlay-Show') {
						// Show the hitviewer.
						$( '#ss1-hitviewer-overlay' )[0].style.display = "block";
					} else if (event.data.action === 'SS1-HV-Overlay-Hide') {
						$( '#ss1-hitviewer-overlay' )[0].style.display = "none";
					}
				}
			}
		);
	}
);
