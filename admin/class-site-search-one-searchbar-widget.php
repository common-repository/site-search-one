<?php
/**
 * The class for the SiteSearchONE WP_Widget
 *
 * @package Site_Search_One
 */

/**
 * The class for the SiteSearchONE WP_Widget
 *
 * @package Site_Search_One
 */
class Site_Search_One_Searchbar_Widget extends WP_Widget {

	/**
	 * Widget Constructor
	 */
	public function __construct() {
		parent::__construct(
			'ss1_searchbar_widget',
			__( 'Site Search ONE - Search bar', 'site-search-one' )
		);
		add_action( 'admin_footer-widgets.php', array( $this, 'print_scripts' ), 9999 );
	}

	/**
	 * Add necessary styles & scripts
	 *
	 * @param string $hook_suffix the hook suffix.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( 'widgets.php' !== $hook_suffix ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}

	/**
	 * SiteSearchONE creates a page with custom post type ss1_widget, it is used to display a custom template.
	 *
	 * @return int|WP_Error
	 */
	private function get_widget_page() {
		$posts = get_posts(
			array(
				'post_type' => 'ss1_widget',
			)
		);
		if ( count( $posts ) === 0 ) {
			$inserted = wp_insert_post(
				array(
					'post_type'    => 'ss1_widget',
					'post_title'   => 'SS1 Search Widget',
					'post_content' => 'ignored',
					'post_status'  => 'publish',
				)
			);
			if ( is_wp_error( $inserted ) ) {
				return $inserted;
			}
			if ( 0 === $inserted ) {
				return new WP_Error( 'failed_create_widget', 'Failed to Create SS1 Widget' );
			}
			// flush_rewrite_rules is fix for 404 on immediately opening this page..
			// https://wordpress.stackexchange.com/questions/202859/custom-post-type-pages-are-not-found .
			// Acknowledge this function is expensive, but it only runs once on widget post type creation.
			//phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
			flush_rewrite_rules( false );
			//phpcs:enable
			return $inserted;
		} else {
			return $posts[0]->ID;
		}
	}


	/**
	 * Print scripts.
	 *
	 * @since 1.0
	 */
	public function print_scripts() {
		?>
		<script>
			( function( $ ){
				function initColorPicker( widget ) {
					widget.find( '.color-picker' ).wpColorPicker( {
						change: _.throttle( function() { // For Customizer
							$(this).trigger( 'change' );
						}, 3000 )
					});
				}

				function onFormUpdate( event, widget ) {
					initColorPicker( widget );
				}

				$( document ).on( 'widget-added widget-updated', onFormUpdate );

				$( document ).ready( function() {
					$( '#widgets-right .widget:has(.color-picker)' ).each( function () {
						initColorPicker( $( this ) );
					} );
				} );
			}( jQuery ) );
		</script>
		<?php
	}

	/* @noinspection PhpUndefinedVariableInspection */
	/**
	 * Widget callback
	 *
	 * @param array $args Widget args.
	 * @param array $instance Instance.
	 */
	public function widget( $args, $instance ) {

		$title                  = apply_filters( 'widget_title', $instance['title'] );
		$post_id                = $instance['post_id'];
		$hide_synonyms_stemming = '';
		if ( isset( $instance['hideSynonymsAndStemming'] ) && $instance['hideSynonymsAndStemming'] ) {
			$hide_synonyms_stemming = '&hideSynonymsAndStemming';
		}
		$bg_color = '';
		if ( isset( $instance['bgcolor'] ) ) {
			$bg_color = '&bgcolor=' . rawurlencode( $instance['bgcolor'] );
		}
		$txt_color = '';
		if ( isset( $instance['txtcolor'] ) ) {
			$txt_color = '&txtcolor=' . rawurlencode( $instance['txtcolor'] );
		}
		$placeholder_txt = '';
		if ( isset( $instance['placeholderTxt'] ) ) {
			$placeholder_txt = '&placeholderTxt=' . rawurlencode( $instance['placeholderTxt'] );
		}

		$border_radius = '';
		if ( isset( $instance['borderRadius'] ) ) {
			$border_radius = '&borderRadius=' . $instance['borderRadius'];
		}

		$restore = '';
		//phpcs:disable WordPress.Security.NonceVerification
		if ( isset( $_GET['restore'] ) ) {
			$restore = '&restore=1';
		}
		//phpcs:enable
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		// TODO WordPress's own documentation recommends rendering widgets w/o escapes, doesn't make sense to escape?
		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}
		// phpcs:enable
		?>
		<div class="ss1-searchbar-widget">
		<?php
		$widget_page = $this->get_widget_page();
		if ( is_wp_error( $widget_page ) ) {
			Site_Search_One_Debugging::log( 'SS1-ERROR Failed to get Widget Page:' );
			Site_Search_One_Debugging::log( $widget_page );
		}
		$page_link       = get_post_permalink( $widget_page );
		$src_url         = add_query_arg( 'post_id', $post_id, $page_link );
		$nonce           = wp_create_nonce( 'SS1-Searchbar-' . $post_id );
		$src_url         = add_query_arg( '_wpnonce', $nonce, $src_url );
		$current_post_id = get_the_ID();
		if ( intval( $current_post_id ) === intval( $post_id ) ) {
			$this->echo_iframes_search_bridge_script( $post_id );
		} else {
			$this->echo_navigate_on_search_script( $post_id );
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
				.ss1-search-bar-iframe {
					width: 100%;
					max-width: 1024px;
					border: 0;
					margin: auto;
				}
			</style>
			<iframe style="opacity: 0;" class="ss1-search-bar-iframe" src="<?php echo esc_url( $src_url . $restore . $hide_synonyms_stemming . $bg_color . $txt_color . $placeholder_txt . $border_radius ); ?>"></iframe>
			<script>
				iFrameResize({
					log: false,
					heightCalculationMethod: 'lowestElement'
				}, '.ss1-search-bar-iframe');
				let jIframe = jQuery('.ss1-search-bar-iframe');
				jIframe.on('load', function() {
					console.info('iFrame loaded.');
					let searchbar_css = false;
					<?php
					if ( isset( $instance['custom_css'] ) ) {
						?>
					searchbar_css = <?php echo wp_json_encode( $instance['custom_css'] ); ?>;
						<?php
					}
					?>
					if (searchbar_css) {
						//console.info('Custom Document CSS Set', searchbar_css);
						let custom_css_elem = jQuery('<style>');
						custom_css_elem.html(searchbar_css);
						let doc = jIframe[0].contentWindow.document;
						doc.head.appendChild(custom_css_elem[0]);
					}

					if (window.wpDarkMode)
					{
						let wpDarkModeEnabled = localStorage.getItem('wp_dark_mode_active');
						jIframe[0].contentWindow.postMessage({
							'type': 'set_dark_mode',
							'enabled': parseInt(wpDarkModeEnabled),
						},"*");
					}
					// In some scenarios, window.wpDarkMode may not be set.. Fall back to checking if the root html element
					// Contains the class 'wp-dark-mode-active'
					if (jQuery(document.documentElement).hasClass('wp-dark-mode-active')
						|| jQuery(document.documentElement).attr('data-wp-dark-mode-active') === 'true'

					) {
						console.info('document has wp-dark-mode-active class. Telling iframe to go dark.');
						jIframe[0].contentWindow.postMessage({
							'type': 'set_dark_mode',
							'enabled': 1,
						},"*");
					} else {
						console.info(document.documentElement, 'does not have wp-dark-mode-active class.' );
					}

					window.setInterval( function() {
						let darkMode = jQuery(document.documentElement).hasClass('wp-dark-mode-active') || jQuery(document.documentElement).attr('data-wp-dark-mode-active') === 'true';
						jIframe[0].contentWindow.postMessage({
							'type': 'set_dark_mode',
							'enabled': darkMode,
						},"*");
					}, 1000);

					jIframe[0].style.opacity = "1";
				});

				//region Support for WP Dark Mode Plugin
				window.addEventListener("wp_dark_mode", function(e) {
					console.info('wp_dark_mode ev', e);
					jIframe[0].contentWindow.postMessage({
						'type': 'set_dark_mode',
						'enabled': e.detail.active,
					},"*");
				});

				//endregion
			</script>
		</div>

		<?php
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['after_widget'];
		// phpcs:enable
	}

	/**
	 * Echo script that causes navigation when user submits a search on search bar widget and navigation required.
	 *
	 * @param int $post_id the search page post id.
	 */
	private function echo_navigate_on_search_script( $post_id ) {
		$permalink = get_permalink( $post_id );
		?>
		<script>
			window.addEventListener("message", (event) => {
				let data = event.data;
				if (data
					&& typeof data === 'object'
					&& 'type' in data
					&& data.type
					&& data.type === 'searchbar_update')
				{
					// Store the search parameters into localStorage for restoration on search page
					sessionStorage.setItem('ss1-search-restore', JSON.stringify(data));
					console.info('Received a searchbar update', data);
					if (data.text_submission) {
						// Navigate to the page containing the search results container
						let post_id = <?php echo esc_html( $post_id ); ?>;
						console.info('Navigating to post id ' + post_id);
						let search_page_url = "<?php echo esc_url( $permalink ); ?>";
						console.info('Post id url is ' + search_page_url);
						window.location.href = search_page_url + "?restore=1";
					}
				}
			});
		</script>
		<?php
	}

	/**
	 * Echo the script that is used to pass searchbar updates from widget into the search pages.
	 *
	 * @param int $post_id Search page post id.
	 */
	private function echo_iframes_search_bridge_script( $post_id ) {
		?>
		<script>
			let received_first_submission = false;
			window.addEventListener("message", (event) => {
				let data = event.data;
				if (data
					&& typeof data === 'object'
					&& 'type' in data
					&& data.type
					&& data.type === 'searchbar_update')
				{
					if (data.first_submission === true) {
						// Fix for issue where search widget got redrawn due to theme (TW)
						if (received_first_submission) {
							return;
						} else {
							received_first_submission = true;
						}
					}
					console.info('Received a searchbar update', data);
					let iframe = document.querySelector('#ss1-search-page');
					iframe.contentWindow.postMessage(data, "*");
				}
			});
		</script>
		<?php
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {

		require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-search-page.php';
		$pages = Site_Search_One_Search_Page::get_all_search_pages();
		if ( is_wp_error( $pages ) === false && count( $pages ) > 0 ) {
			$first_post_id = $pages[0]->get_post_id();
		} else {
			$first_post_id = -1;
		}

		$defaults = array(
			'title'                   => 'New Search Bar',
			'bgcolor'                 => '#fff',
			'txtcolor'                => '#000',
			'hideSynonymsAndStemming' => false,
			'post_id'                 => $first_post_id,
			'placeholderTxt'          => 'Search',
			'borderRadius'            => 4,
		);

		$instance = wp_parse_args( (array) $instance, $defaults );

		if ( isset( $instance['bgcolor'] ) ) {
			$bgcolor = $instance['bgcolor'];
		} else {
			$instance['bgcolor'] = '#fff';
		}

		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = __( 'New title', 'text_domain' );
		}
		$selected_post_id = false;
		if ( isset( $instance['post_id'] ) ) {
			$selected_post_id = $instance['post_id'];
		}
		$hide_synonyms_stemming = false;
		if ( isset( $instance['hideSynonymsAndStemming'] ) ) {
			$hide_synonyms_stemming = $instance['hideSynonymsAndStemming'];
		}

		if ( $hide_synonyms_stemming ) {
			$hide_synonyms_stemming = ' checked=checked';
		} else {
			$hide_synonyms_stemming = '';
		}
		$custom_css = '';
		if ( isset( $instance['custom_css'] ) ) {
			$custom_css = $instance['custom_css'];
		}
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'site-search-one' ); ?>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_name( 'post_id' ) ); ?>"><?php esc_html_e( 'Search page:', 'site-search-one' ); ?>
				<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'post_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'post_id' ) ); ?>">
					<?php
					require_once plugin_dir_path( __FILE__ ) . 'class-site-search-one-search-page.php';
					$search_pages = Site_Search_One_Search_Page::get_all_search_pages();
					if ( is_wp_error( $search_pages ) ) {
						$search_pages = array();
					}
					foreach ( $search_pages as $search_page ) {
						$post_id   = $search_page->get_post_id();
						$page_name = get_the_title( $post_id );
						$selected  = '';
						if ( $selected_post_id && intval( $selected_post_id ) === intval( $post_id ) ) {
							$selected = ' selected="selected"';
						}
						?>
						<option value="<?php echo esc_attr( $post_id ); ?>"<?php echo esc_attr( $selected ); ?>>
							<?php echo esc_html( $page_name ); ?>
						</option>
						<?php
					}
					?>
				</select>
			</label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_name( 'hideSynonymsAndStemming' ) ); ?>">
				<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'hideSynonymsAndStemming' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'hideSynonymsAndStemming' ) ); ?>"<?php echo esc_attr( $hide_synonyms_stemming ); ?>>
				<span><?php esc_html_e( 'Hide Stemming and Synonyms Checkboxes', 'site-search-one' ); ?></span>
			</label>
		</p>
		<p>
			<label style="vertical-align: top;" for="<?php echo esc_attr( $this->get_field_id( 'bgcolor' ) ); ?>"><?php esc_html_e( 'Background Color:', 'site-search-one' ); ?></label><br>
			<input class="widefat color-picker" id="<?php echo esc_attr( $this->get_field_id( 'bgcolor' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'bgcolor' ) ); ?>" value="<?php echo esc_attr( $instance['bgcolor'] ); ?>" type="text" />
		</p>
		<p>
			<label style="vertical-align: top;" for="<?php echo esc_attr( $this->get_field_id( 'txtcolor' ) ); ?>"><?php esc_html_e( 'Text Color:', 'site-search-one' ); ?></label><br>
			<input class="widefat color-picker" id="<?php echo esc_attr( $this->get_field_id( 'txtcolor' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'txtcolor' ) ); ?>" value="<?php echo esc_attr( $instance['txtcolor'] ); ?>" type="text" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_name( 'placeholderTxt' ) ); ?>"><?php esc_html_e( 'Placeholder Text:', 'site-search-one' ); ?>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'placeholderTxt' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'placeholderTxt' ) ); ?>" type="text" value="<?php echo esc_attr( $instance['placeholderTxt'] ); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_name( 'borderRadius' ) ); ?>"><?php esc_html_e( 'Border Radius:', 'site-search-one' ); ?>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'borderRadius' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'borderRadius' ) ); ?>" type="number" max="20" min="0" value="<?php echo esc_attr( $instance['borderRadius'] ); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_name( 'custom_css' ) ); ?>"><?php esc_html_e( 'Custom CSS:', 'site-search-one' ); ?>
				<textarea spellcheck="false" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'custom_css' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'custom_css' ) ); ?>" type="text" cols="30" rows="10"><?php echo esc_textarea( $custom_css ); ?></textarea>
			</label>
		</p>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                            = array();
		$instance['title']                   = ( ! empty( $new_instance['title'] ) ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
		$instance['post_id']                 = $new_instance['post_id'];
		$instance['hideSynonymsAndStemming'] = $new_instance['hideSynonymsAndStemming'];
		$instance['bgcolor']                 = sanitize_hex_color( $new_instance['bgcolor'] );
		$instance['txtcolor']                = sanitize_hex_color( $new_instance['txtcolor'] );
		$instance['placeholderTxt']          = $new_instance['placeholderTxt'];
		$instance['borderRadius']            = $new_instance['borderRadius'];
		$instance['custom_css']              = $new_instance['custom_css'];
		return $instance;
	}
}
