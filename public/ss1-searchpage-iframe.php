<?php
/**
 * The containing iframes for Search Engine Results Pages.
 * Contains iFrame for SERP, Document Hit viewer and PDF Viewer.
 * Rendered out when ss1-searchpage shortcode is used.
 *
 * @package Site_Search_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- Minified iframe resizer -->
<script type="application/javascript">
!function(a){"use strict";function b(a,b,c){"addEventListener"in window?a.addEventListener(b,c,!1):"attachEvent"in window&&a.attachEvent("on"+b,c)}function c(a,b,c){"removeEventListener"in window?a.removeEventListener(b,c,!1):"detachEvent"in window&&a.detachEvent("on"+b,c)}function d(){var a,b=["moz","webkit","o","ms"];for(a=0;a<b.length&&!O;a+=1)O=window[b[a]+"RequestAnimationFrame"];O||h("setup","RequestAnimationFrame not supported")}function e(a){var b="Host page: "+a;return window.top!==window.self&&(b=window.parentIFrame&&window.parentIFrame.getId?window.parentIFrame.getId()+": "+a:"Nested host page: "+a),b}function f(a){return L+"["+e(a)+"]"}function g(a){return Q[a]?Q[a].log:H}function h(a,b){k("log",a,b,g(a))}function i(a,b){k("info",a,b,g(a))}function j(a,b){k("warn",a,b,!0)}function k(a,b,c,d){!0===d&&"object"==typeof window.console&&console[a](f(b),c)}function l(a){function d(){function a(){s(U),p(V),I("resizedCallback",U)}f("Height"),f("Width"),t(a,U,"init")}function e(){var a=T.substr(M).split(":");return{iframe:Q[a[0]]&&Q[a[0]].iframe,id:a[0],height:a[1],width:a[2],type:a[3]}}function f(a){var b=Number(Q[V]["max"+a]),c=Number(Q[V]["min"+a]),d=a.toLowerCase(),e=Number(U[d]);h(V,"Checking "+d+" is in range "+c+"-"+b),e<c&&(e=c,h(V,"Set "+d+" to min value")),e>b&&(e=b,h(V,"Set "+d+" to max value")),U[d]=""+e}function g(){function b(){function a(){var a=0,b=!1;for(h(V,"Checking connection is from allowed list of origins: "+d);a<d.length;a++)if(d[a]===c){b=!0;break}return b}function b(){var a=Q[V]&&Q[V].remoteHost;return h(V,"Checking connection is from: "+a),c===a}return d.constructor===Array?a():b()}var c=a.origin,d=Q[V]&&Q[V].checkOrigin;if(d&&""+c!="null"&&!b())throw new Error("Unexpected message received from: "+c+" for "+U.iframe.id+". Message was: "+a.data+". This error can be disabled by setting the checkOrigin: false option or by providing of array of trusted domains.");return!0}function k(){return L===(""+T).substr(0,M)&&T.substr(M).split(":")[0]in Q}function l(){var a=U.type in{true:1,false:1,undefined:1};return a&&h(V,"Ignoring init message from meta parent page"),a}function w(a){return T.substr(T.indexOf(":")+K+a)}function x(a){h(V,"MessageCallback passed: {iframe: "+U.iframe.id+", message: "+a+"}"),I("messageCallback",{iframe:U.iframe,message:JSON.parse(a)}),h(V,"--")}function z(){var a=document.body.getBoundingClientRect(),b=U.iframe.getBoundingClientRect();return JSON.stringify({iframeHeight:b.height,iframeWidth:b.width,clientHeight:Math.max(document.documentElement.clientHeight,window.innerHeight||0),clientWidth:Math.max(document.documentElement.clientWidth,window.innerWidth||0),offsetTop:parseInt(b.top-a.top,10),offsetLeft:parseInt(b.left-a.left,10),scrollTop:window.pageYOffset,scrollLeft:window.pageXOffset})}function A(a,b){function c(){u("Send Page Info","pageInfo:"+z(),a,b)}y(c,32,b)}function B(){function a(a,b){function c(){Q[f]?A(Q[f].iframe,f):d()}["scroll","resize"].forEach(function(d){h(f,a+d+" listener for sendPageInfo"),b(window,d,c)})}function d(){a("Remove ",c)}function e(){a("Add ",b)}var f=V;e(),Q[f]&&(Q[f].stopPageInfo=d)}function C(){Q[V]&&Q[V].stopPageInfo&&(Q[V].stopPageInfo(),delete Q[V].stopPageInfo)}function D(){var a=!0;return null===U.iframe&&(j(V,"IFrame ("+U.id+") not found"),a=!1),a}function E(a){var b=a.getBoundingClientRect();return o(V),{x:Math.floor(Number(b.left)+Number(N.x)),y:Math.floor(Number(b.top)+Number(N.y))}}function F(a){function b(){N=f,G(),h(V,"--")}function c(){return{x:Number(U.width)+e.x,y:Number(U.height)+e.y}}function d(){window.parentIFrame?window.parentIFrame["scrollTo"+(a?"Offset":"")](f.x,f.y):j(V,"Unable to scroll to requested position, window.parentIFrame not found")}var e=a?E(U.iframe):{x:0,y:0},f=c();h(V,"Reposition requested from iFrame (offset x:"+e.x+" y:"+e.y+")"),window.top!==window.self?d():b()}function G(){!1!==I("scrollCallback",N)?p(V):q()}function H(a){function b(){var a=E(f);h(V,"Moving to in page link (#"+d+") at x: "+a.x+" y: "+a.y),N={x:a.x,y:a.y},G(),h(V,"--")}function c(){window.parentIFrame?window.parentIFrame.moveToAnchor(d):h(V,"In page link #"+d+" not found and window.parentIFrame not found")}var d=a.split("#")[1]||"",e=decodeURIComponent(d),f=document.getElementById(e)||document.getElementsByName(e)[0];f?b():window.top!==window.self?c():h(V,"In page link #"+d+" not found")}function I(a,b){return m(V,a,b)}function J(){switch(Q[V]&&Q[V].firstRun&&R(),U.type){case"close":Q[V].closeRequestCallback?m(V,"closeRequestCallback",Q[V].iframe):n(U.iframe);break;case"message":x(w(6));break;case"scrollTo":F(!1);break;case"scrollToOffset":F(!0);break;case"pageInfo":A(Q[V]&&Q[V].iframe,V),B();break;case"pageInfoStop":C();break;case"inPageLink":H(w(9));break;case"reset":r(U);break;case"init":d(),I("initCallback",U.iframe);break;default:d()}}function O(a){var b=!0;return Q[a]||(b=!1,j(U.type+" No settings for "+a+". Message was: "+T)),b}function P(){for(var a in Q)u("iFrame requested init",v(a),document.getElementById(a),a)}function R(){Q[V]&&(Q[V].firstRun=!1)}var T=a.data,U={},V=null;"[iFrameResizerChild]Ready"===T?P():k()?(U=e(),V=S=U.id,Q[V]&&(Q[V].loaded=!0),!l()&&O(V)&&(h(V,"Received: "+T),D()&&g()&&J())):i(V,"Ignored: "+T)}function m(a,b,c){var d=null,e=null;if(Q[a]){if("function"!=typeof(d=Q[a][b]))throw new TypeError(b+" on iFrame["+a+"] is not a function");e=d(c)}return e}function n(a){var b=a.id;h(b,"Removing iFrame: "+b),a.parentNode&&a.parentNode.removeChild(a),m(b,"closedCallback",b),h(b,"--"),delete Q[b]}function o(b){null===N&&(N={x:window.pageXOffset!==a?window.pageXOffset:document.documentElement.scrollLeft,y:window.pageYOffset!==a?window.pageYOffset:document.documentElement.scrollTop},h(b,"Get page position: "+N.x+","+N.y))}function p(a){null!==N&&(window.scrollTo(N.x,N.y),h(a,"Set page position: "+N.x+","+N.y),q())}function q(){N=null}function r(a){function b(){s(a),u("reset","reset",a.iframe,a.id)}h(a.id,"Size reset requested by "+("init"===a.type?"host page":"iFrame")),o(a.id),t(b,a,"reset")}function s(a){function b(b){a.iframe.style[b]=a[b]+"px",h(a.id,"IFrame ("+e+") "+b+" set to "+a[b]+"px")}function c(b){I||"0"!==a[b]||(I=!0,h(e,"Hidden iFrame detected, creating visibility listener"),z())}function d(a){b(a),c(a)}var e=a.iframe.id;Q[e]&&(Q[e].sizeHeight&&d("height"),Q[e].sizeWidth&&d("width"))}function t(a,b,c){c!==b.type&&O?(h(b.id,"Requesting animation frame"),O(a)):a()}function u(a,b,c,d,e){function f(){var e=Q[d]&&Q[d].targetOrigin;h(d,"["+a+"] Sending msg to iframe["+d+"] ("+b+") targetOrigin: "+e),c.contentWindow.postMessage(L+b,e)}function g(){j(d,"["+a+"] IFrame("+d+") not found")}function i(){c&&"contentWindow"in c&&null!==c.contentWindow?f():g()}function k(){function a(){!Q[d]||Q[d].loaded||l||(l=!0,j(d,"IFrame has not responded within "+Q[d].warningTimeout/1e3+" seconds. Check iFrameResizer.contentWindow.js has been loaded in iFrame. This message can be ingored if everything is working, or you can set the warningTimeout option to a higher value or zero to suppress this warning."))}e&&Q[d]&&Q[d].warningTimeout&&(Q[d].msgTimeout=setTimeout(a,Q[d].warningTimeout))}var l=!1;d=d||c.id,Q[d]&&(i(),k())}function v(a){return a+":"+Q[a].bodyMarginV1+":"+Q[a].sizeWidth+":"+Q[a].log+":"+Q[a].interval+":"+Q[a].enablePublicMethods+":"+Q[a].autoResize+":"+Q[a].bodyMargin+":"+Q[a].heightCalculationMethod+":"+Q[a].bodyBackground+":"+Q[a].bodyPadding+":"+Q[a].tolerance+":"+Q[a].inPageLinks+":"+Q[a].resizeFrom+":"+Q[a].widthCalculationMethod}function w(c,d){function e(){function a(a){1/0!==Q[x][a]&&0!==Q[x][a]&&(c.style[a]=Q[x][a]+"px",h(x,"Set "+a+" = "+Q[x][a]+"px"))}function b(a){if(Q[x]["min"+a]>Q[x]["max"+a])throw new Error("Value for min"+a+" can not be greater than max"+a)}b("Height"),b("Width"),a("maxHeight"),a("minHeight"),a("maxWidth"),a("minWidth")}function f(){var a=d&&d.id||T.id+G++;return null!==document.getElementById(a)&&(a+=G++),a}function g(a){return S=a,""===a&&(c.id=a=f(),H=(d||{}).log,S=a,h(a,"Added missing iframe ID: "+a+" ("+c.src+")")),a}function i(){switch(h(x,"IFrame scrolling "+(Q[x]&&Q[x].scrolling?"enabled":"disabled")+" for "+x),c.style.overflow=!1===(Q[x]&&Q[x].scrolling)?"hidden":"auto",Q[x]&&Q[x].scrolling){case!0:c.scrolling="yes";break;case!1:c.scrolling="no";break;default:c.scrolling=Q[x]?Q[x].scrolling:"no"}}function k(){"number"!=typeof(Q[x]&&Q[x].bodyMargin)&&"0"!==(Q[x]&&Q[x].bodyMargin)||(Q[x].bodyMarginV1=Q[x].bodyMargin,Q[x].bodyMargin=Q[x].bodyMargin+"px")}function l(){var a=Q[x]&&Q[x].firstRun,b=Q[x]&&Q[x].heightCalculationMethod in P;!a&&b&&r({iframe:c,height:0,width:0,type:"init"})}function m(){Function.prototype.bind&&Q[x]&&(Q[x].iframe.iFrameResizer={close:n.bind(null,Q[x].iframe),resize:u.bind(null,"Window resize","resize",Q[x].iframe),moveToAnchor:function(a){u("Move to anchor","moveToAnchor:"+a,Q[x].iframe,x)},sendMessage:function(a){a=JSON.stringify(a),u("Send Message","message:"+a,Q[x].iframe,x)}})}function o(d){function e(){u("iFrame.onload",d,c,a,!0),l()}b(c,"load",e),u("init",d,c,a,!0)}function p(a){if("object"!=typeof a)throw new TypeError("Options is not an object")}function q(a){for(var b in T)T.hasOwnProperty(b)&&(Q[x][b]=a.hasOwnProperty(b)?a[b]:T[b])}function s(a){return""===a||"file://"===a?"*":a}function t(a){a=a||{},Q[x]={firstRun:!0,iframe:c,remoteHost:c.src.split("/").slice(0,3).join("/")},p(a),q(a),Q[x]&&(Q[x].targetOrigin=!0===Q[x].checkOrigin?s(Q[x].remoteHost):"*")}function w(){return x in Q&&"iFrameResizer"in c}var x=g(c.id);w()?j(x,"Ignored iFrame, already setup."):(t(d),i(),e(),k(),o(v(x)),m())}function x(a,b){null===R&&(R=setTimeout(function(){R=null,a()},b))}function y(a,b,c){U[c]||(U[c]=setTimeout(function(){U[c]=null,a()},b))}function z(){function a(){function a(a){function b(b){return"0px"===(Q[a]&&Q[a].iframe.style[b])}function c(a){return null!==a.offsetParent}Q[a]&&c(Q[a].iframe)&&(b("height")||b("width"))&&u("Visibility change","resize",Q[a].iframe,a)}for(var b in Q)a(b)}function b(b){h("window","Mutation observed: "+b[0].target+" "+b[0].type),x(a,16)}function c(){var a=document.querySelector("body"),c={attributes:!0,attributeOldValue:!1,characterData:!0,characterDataOldValue:!1,childList:!0,subtree:!0};new d(b).observe(a,c)}var d=window.MutationObserver||window.WebKitMutationObserver;d&&c()}function A(a){function b(){C("Window "+a,"resize")}h("window","Trigger event: "+a),x(b,16)}function B(){function a(){C("Tab Visable","resize")}"hidden"!==document.visibilityState&&(h("document","Trigger event: Visiblity change"),x(a,16))}function C(a,b){function c(a){return Q[a]&&"parent"===Q[a].resizeFrom&&Q[a].autoResize&&!Q[a].firstRun}for(var d in Q)c(d)&&u(a,b,document.getElementById(d),d)}function D(){b(window,"message",l),b(window,"resize",function(){A("resize")}),b(document,"visibilitychange",B),b(document,"-webkit-visibilitychange",B),b(window,"focusin",function(){A("focus")}),b(window,"focus",function(){A("focus")})}function E(){function b(a,b){function c(){if(!b.tagName)throw new TypeError("Object is not a valid DOM element");if("IFRAME"!==b.tagName.toUpperCase())throw new TypeError("Expected <IFRAME> tag, found <"+b.tagName+">")}b&&(c(),w(b,a),e.push(b))}function c(a){a&&a.enablePublicMethods&&j("enablePublicMethods option has been removed, public methods are now always available in the iFrame")}var e;return d(),D(),function(d,f){switch(e=[],c(d),typeof f){case"undefined":case"string":Array.prototype.forEach.call(document.querySelectorAll(f||"iframe"),b.bind(a,d));break;case"object":b(d,f);break;default:throw new TypeError("Unexpected data type ("+typeof f+")")}return e}}function F(a){a.fn?a.fn.iFrameResize||(a.fn.iFrameResize=function(a){function b(b,c){w(c,a)}return this.filter("iframe").each(b).end()}):i("","Unable to bind to jQuery, it is not fully loaded.")}if("undefined"!=typeof window){var G=0,H=!1,I=!1,J="message",K=J.length,L="[iFrameSizer]",M=L.length,N=null,O=window.requestAnimationFrame,P={max:1,scroll:1,bodyScroll:1,documentElementScroll:1},Q={},R=null,S="Host Page",T={autoResize:!0,bodyBackground:null,bodyMargin:null,bodyMarginV1:8,bodyPadding:null,checkOrigin:!0,inPageLinks:!1,enablePublicMethods:!0,heightCalculationMethod:"bodyOffset",id:"iFrameResizer",interval:32,log:!1,maxHeight:1/0,maxWidth:1/0,minHeight:0,minWidth:0,resizeFrom:"parent",scrolling:!1,sizeHeight:!0,sizeWidth:!1,warningTimeout:5e3,tolerance:0,widthCalculationMethod:"scroll",closedCallback:function(){},initCallback:function(){},messageCallback:function(){j("MessageCallback function not defined")},resizedCallback:function(){},scrollCallback:function(){return!0}},U={};window.jQuery&&F(window.jQuery),"function"==typeof define&&define.amd?define([],E):"object"==typeof module&&"object"==typeof module.exports?module.exports=E():window.iFrameResize=window.iFrameResize||E()}}();
</script>
<style>
	.iframe-container {
		border: 0;
		min-width: 100%;
		background-color: white;
	}
	#ss1-search-page, .ss1-search-bar-iframe {
		width: 100%;
		border: 0;
		margin: auto;
	}
	#ss1-hitviewer-overlay.ss1-overlay,
	#ss1-pdf-hitviewer-overlay.ss1-overlay {
		position: fixed;
		display: none;
		width: 100%;
		height: 100%;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: rgba(0.5,0.5,0.5,0.5);
		z-index: 9999999999;
		cursor: pointer;
		max-height: unset;
		min-height: unset;
		max-width: unset;
		min-width: unset;
	}
	#ss1-hitviewer, #ss1-pdf-hitviewer {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		width: 80%;
		height: 80%;
		margin: auto;
		max-width: 912px;
		border: 0;
	}
	@media(max-width: 768px) {
		#ss1-hitviewer {
			width: 100%;
			height: 100%;
		}
	}
</style>
<?php
$the_post_id     = get_the_ID();
$_GET['post_id'] = strval( $the_post_id );
// Nonce's expire after 12h. Sometimes depending on the users cache configuration, if a user has left the search page
// Open for over 12h in a browser and since tabbed out, the browser will reload from cache the page as it was served,
// Including the out of date nonce, which in turn will cause the search page to fail to load.
// If > 11h has elapsed, we'll simply refresh the page to prevent this occurring.
$date = date_create();
// For testing purposes, we'll set this to 60s before expiry.
// $date->sub(date_interval_create_from_date_string('11 hours + 59 minutes'));.
$timestamp = date_timestamp_get( $date );
?>
<!-- The 'scrolling' attribute is deprecated - But recognized by browsers, no good replacement so far.. -->
<!--suppress HtmlDeprecatedAttribute -->
<?php

$query = null;
if ( isset( $_GET['query'] ) ) {
	$query         = sanitize_text_field( wp_unslash( $_GET['query'] ) );
	$query         = wp_unslash( $query );
	$_GET['query'] = $query;
} elseif ( isset( $_GET['s'] ) ) {
	// This is being called as a result of user using WordPress Search.
	// WordPress uses the 's' parameter which we sometimes use when the user overrides the default
	// search form...
	$query         = sanitize_text_field( wp_unslash( $_GET['s'] ) );
	$the_post_id   = intval( get_transient( 'ss1-searchform-override' ) );
	$_GET['query'] = $query;
}

require_once plugin_dir_path( __FILE__ ) . '../admin/class-site-search-one-admin.php';
$serp_page = Site_Search_One_Admin::get_serp_page();
Site_Search_One_Debugging::log( 'SERP Page ID ' . $serp_page . ' post type ' . get_post_type( $serp_page ) );
$hitviewer_page = Site_Search_One_Admin::get_hitviewer_page();
if ( is_wp_error( $serp_page ) ) {
	Site_Search_One_Debugging::log( 'SS1-ERROR Failed to get SERP Page:' );
	Site_Search_One_Debugging::log( $serp_page );
}

$serp_page_link = get_post_permalink( $serp_page );
Site_Search_One_Debugging::log( 'SERP Page Permalink: ' . $serp_page_link );
$serp_page_link = add_query_arg( 'post_id', $the_post_id, $serp_page_link );
if ( isset( $_GET['query'] ) ) {
	$query = sanitize_text_field( wp_unslash( $_GET['query'] ) );

	$serp_page_link = add_query_arg( 'query', $query, $serp_page_link );
}
if ( isset( $_GET['restore'] ) ) {
	$serp_page_link = add_query_arg( 'restore', '1', $serp_page_link );
}
$nonce          = wp_create_nonce( 'view-search-page-' . $the_post_id );
$serp_page_link = add_query_arg( '_wpnonce', $nonce, $serp_page_link );

$hitviewer_page_link = get_post_permalink( $hitviewer_page );

?>
<div id="ss1-hitviewer-overlay" class="ss1-overlay">
	<iframe id="ss1-hitviewer" src="<?php echo esc_url( $hitviewer_page_link ); ?>" scrolling="no"></iframe>
</div>
<div id="ss1-pdf-hitviewer-overlay" class="ss1-overlay">
	<iframe id="ss1-pdf-hitviewer" scrolling="no"></iframe>
</div>
<div class="iframe-container">
	<?php
	Site_Search_One_Debugging::log( 'SS1-INFO iFrame SERP page link being set to ' . $serp_page_link );
	?>
	<iframe id="ss1-search-page" src="<?php echo esc_url( $serp_page_link ); ?>" style="opacity: 0;" scrolling="no"></iframe>
</div>
<script>
	let hv_shown_fields = false;
	let searchReq;
	let document_css = false;
	iFrameResize({
		log: false,
		heightCalculationMethod: 'lowestElement',
		checkOrigin: false // Since the src is not set to a remote site, the origin errors out if this is true
	}, '#ss1-search-page');
	jQuery(document).ready(function($) {
		$('#ss1-search-page').on('load',function() {
			if (window.wpDarkMode)
			{
				console.info('wpdarkmode installed', window.wpDarkMode);
				let wpDarkModeEnabled = localStorage.getItem('wp_dark_mode_active');
				document.getElementById('ss1-search-page').contentWindow.postMessage({
					'type': 'set_dark_mode',
					'enabled': parseInt(wpDarkModeEnabled),
				},"*");
			}
				// In some scenarios, this may not be set.. Fall back to checking if the root html element
				// Contains the class 'wp-dark-mode-active'
			if ($(document.documentElement).hasClass('wp-dark-mode-active')
			 || $(document.documentElement).attr('data-wp-dark-mode-active') === 'true'
			) {
				document.getElementById('ss1-search-page').contentWindow.postMessage({
					'type': 'set_dark_mode',
					'enabled': 1,
				},"*");
			}
			document.getElementById('ss1-search-page').style.opacity = "1";
		});
		setTimeout( function() {
			// After 4s even if the iframe load event didn't fire, show the iframe. May lead to whiteflash
			// On slow network conditions, but also shows progress. For some reason load event may fail to
			// fire as well.
			document.getElementById('ss1-search-page').style.opacity = "1";
		}, 4000);
		window.setInterval( function() {

			let darkMode = $(document.documentElement).hasClass('wp-dark-mode-active') || $(document.documentElement).attr('data-wp-dark-mode-active') === 'true';
			document.getElementById('ss1-search-page').contentWindow.postMessage({
				'type': 'set_dark_mode',
				'enabled': darkMode,
			},"*");
		}, 1000);
		window.addEventListener("message",(event)=> {

			if (event.data) {
				if (event.data.action === 'SS1-Scroll-To-Top-Of-SearchPage') {

					let iframeOffset 	= $('#ss1-search-page').offset().top;
					let adminBarHeight = 0;
					if ( $('#wpadminbar').length ) {
						// admin bar on page. This can overlap and hide the result count.
						adminBarHeight 	= $('#wpadminbar').height();
					}
					let resultsOffset   = event.data.resultsOffset;

					let totalOffset = iframeOffset + resultsOffset - adminBarHeight;

					window.scrollTo({
						top: totalOffset,
						behavior: 'instant'
					});
				}
				if (event.data.action === 'SS1-HV-Overlay-Show') {
					// Show the hitviewer.
					searchReq = event.data.query;
					hv_shown_fields  = event.data.shown_fields;
					$('#ss1-hitviewer-overlay')[0].style.display = "block";
					if ('document_css' in event.data) document_css = event.data.document_css;
					$('#ss1-hitviewer').removeAttr("style"); // Workaround for some WordPress installs hiding iframe..
					document.getElementById('ss1-hitviewer').contentWindow.postMessage({
						'action': 'SS1-HV-Set-SearchReq',
						'searchReq': searchReq,
						'hv_shown_fields': hv_shown_fields,
						'document_css' : document_css
					},"*");

				} else if (event.data.action === 'SS1-HV-Overlay-Hide') {
					$('#ss1-hitviewer-overlay')[0].style.display = "none";
					$('#ss1-pdf-hitviewer-overlay')[0].style.display = "none";
				}
				if (event.data.action === 'SS1-PDF-HV-Overlay-Show') {
					console.info('PDF Viewer Requested');
					$('#ss1-pdf-hitviewer-overlay')[0].style.display = "block";
					$('#ss1-pdf-hitviewer')
						.removeAttr('style')
						.attr('src', 'about:blank')
						.contents().find("body").html('');
					$('#ss1-pdf-hitviewer')
						.attr('src',event.data.src);
					// Workaround for some WordPress installs hiding iframe..
				}
				if (event.data.action === 'SS1-HV-Get-SearchReq') {
					console.info('SearchReq Requested. Giving to Hitviewer...', searchReq);
					document.getElementById('ss1-hitviewer').contentWindow.postMessage({
						'action' : 'SS1-HV-Set-SearchReq',
						'searchReq': searchReq,
						'hv_shown_fields': hv_shown_fields,
						'document_css' : document_css
					},"*");
				}
				if (event.data.action === 'SS1-Navigate-To') {
					window.location.href = event.data.url;
				}
			}
		});
		//region Support for WP Dark Mode Plugin
		window.addEventListener("wp_dark_mode", function(e) {
		   console.info('wp_dark_mode ev', e);
		   document.getElementById('ss1-search-page').contentWindow.postMessage({
			   'type': 'set_dark_mode',
			   'enabled': e.detail.active,
		   },"*");
		});

		//endregion
		$('#ss1-hitviewer-overlay, #ss1-pdf-hitviewer-overlay').click(function() {
		   $(this)[0].style.display = 'none';
		});
	});
</script>
