/**
 * Used for hit navigation in hit viewer.
 *
 * @package           Site_Search_One
 */

var num_hits;
var current_hit;
var current_element;

var frame = window.document.querySelector( 'iframe' ).contentWindow;



frame.onload = function() {

	num_hits        = frame.document.getElementsByClassName( "dts_hit" ).length;
	current_element = frame.document.getElementsByClassName( "dts_hit" )[0];
	$( '#hit_count' ).html( num_hits );

	current_hit = 1;

	update_display();
	setTimeout(
		function() {
			update_display();
		},
		1000
	);

	$( '.nav-link' ).tooltip( {trigger : 'hover'} );
	if (num_hits > 0) {
		$( '#hit-nav' ).css( {'display':'flex'} );
	}
}

function update_display()
{
	let data    = {};
	data.action = 'scrollTo';
	let element = current_hit;
	if (current_hit === num_hits) {
		element = '_last';
	}
	data.element = 'hit_' + element;
	$( '#tb_hitnum' ).val( current_hit );
	document.getElementById( 'docFrame' ).contentWindow.postMessage( data,"*" );
}

$( '#tb_hitnum' ).on(
	'keypress',
	function (e) {
		if (e.which === 13) {

			current_hit = $( '#tb_hitnum' ).val();
			update_display();
		}
	}
);


$( '#btnFirst' ).click(
	function() {
		current_hit = 1;
		update_display();
	}
);

$( '#btnLast' ).click(
	function() {
		current_hit = num_hits;
		update_display();
	}
);

$( '#btnNext' ).click(
	function() {
		current_hit++;
		if (current_hit < 1) {
			current_hit = 1;
		}
		if (current_hit >= num_hits) {
			current_hit = num_hits;
		}
		update_display();
	}
);

$( '#btnPrev' ).click(
	function() {
		current_hit--;
		if (current_hit < 1) {
			current_hit = 1;
		}
		if (current_hit >= num_hits) {
			current_hit = num_hits;
		}
		update_display();
	}
);

$( '#btnPrint' ).click(
	function() {
		document.getElementById( 'docFrame' ).contentWindow.print();
	}
);


let cssId = 'myCss';  // you could encode the css path itself to generate id..
if ( ! document.getElementById( cssId )) {
	let head   = frame.document.getElementsByTagName( 'head' )[0];
	let link   = frame.document.createElement( 'link' );
	link.id    = cssId;
	link.rel   = 'stylesheet';
	link.type  = 'text/css';
	link.href  = 'stylesheet.css';
	link.media = 'all';
	head.appendChild( link );
}
