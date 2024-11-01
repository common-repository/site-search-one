/**
 * Script used for scrolling the hit viewer to the active hit.
 *
 * @package           Site_Search_One
 */

window.addEventListener( "message", recieveMessage, false );

let element;

function recieveMessage(event)
{
	let data = event.data;
	if (data.action === 'scrollTo') {
		if (element != null) {
			element.classList.remove( 'active_hit' );
		}
		element = document.getElementById( data.element );
		if (element) {
			element.classList.add( 'active_hit' );
			element.scrollIntoView();
		}
	}
}
