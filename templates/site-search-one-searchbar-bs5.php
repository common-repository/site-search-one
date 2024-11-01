<?php
/**
 * Template Name: Site Search ONE - Search Widget
 * Template Post Type: ss1_widget
 *
 * @package Site_Search_One
 */

/* @noinspection DuplicatedCode */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! isset( $_GET['post_id'] ) ) {
	exit;
}
$sp_post_id = intval( sanitize_text_field( wp_unslash( $_GET['post_id'] ) ) );
$nonce_valid = check_ajax_referer( 'SS1-Searchbar-' . $sp_post_id , false, false);

if ( $nonce_valid === false ) {
	if ( get_post_status( $sp_post_id ) !== 'publish' )
	{
		die( esc_html( __( 'Invalid Nonce', 'site-search-one' ) ) );
	}
}
require_once plugin_dir_path( __FILE__ ) . '../admin/class-site-search-one-search-page.php';
$current_search_page = Site_Search_One_Search_Page::get_search_page( $sp_post_id );
if ( ! $current_search_page ) {
	return;
}
$display_opts       = $current_search_page->get_display_opts();
$also_search_pages  = $current_search_page->get_also_shown_searchpages();
$all_search_pages   = array();
$all_search_pages[] = $current_search_page;

$all_search_pages = array_merge( $all_search_pages, $also_search_pages );

/**
 * Sort search pages on display name.
 *
 * @param Site_Search_One_Search_Page $search_page_a Search Page A.
 * @param Site_Search_One_Search_Page $search_page_b Search Page B.
 */
function ss1_diplayname_sort( $search_page_a, $search_page_b ) {
	$name_a = strtolower( $search_page_a->get_ix_name() );
	$name_b = strtolower( $search_page_b->get_ix_name() );
	if ( $name_a === $name_b ) {
		return 0;
	}
	return ( $name_a > $name_b ) ? +1 : -1;
}
usort( $all_search_pages, 'ss1_diplayname_sort' );
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Search Page</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- Print Scripts -->
	<?php
	$bg_color      = false;
	$txt_color     = false;
	$border_radius = false;
	if ( isset( $_GET['bgcolor'] ) ) {
		$bg_color = sanitize_hex_color( wp_unslash( $_GET['bgcolor'] ) );
	}
	if ( isset( $_GET['txtcolor'] ) ) {
		$txt_color = sanitize_hex_color( wp_unslash( $_GET['txtcolor'] ) );
	}
	if ( isset( $_GET['borderRadius'] ) ) {
		$border_radius = intval( sanitize_text_field( wp_unslash( $_GET['borderRadius'] ) ) );
	}
	$custom_styles = '';
	if ( false !== $bg_color ) {
		$custom_styles .= "\nbody { background-color:$bg_color !important; }";
	}
	if ( false !== $txt_color ) {
		$custom_styles .= "\n#stemming-synonyms-container label { color:$txt_color !important; }";
		$custom_styles .= "\n#search-type-container label { color:$txt_color !important; }";
	}
	if ( false !== $border_radius ) {
		$custom_styles .= "\n#search-bar.input-group > * { border-radius:$border_radius !important; }";
		$custom_styles .= "\n#search-bar-mobile.input-group > * { border-radius:$border_radius !important; }";
	}
	if ( isset( $_GET['hideSynonymsAndStemming'] ) ) {
		$custom_styles .= "\n#stemming-synonyms-container { display: none !important; }";
	}
	$placeholder_text = esc_attr__( 'Search', 'site-search-one' );
	if ( isset( $_GET['placeholderTxt'] ) ) {
		$placeholder_text = esc_attr( sanitize_text_field( wp_unslash( $_GET['placeholderTxt'] ) ) );
	}
	$plugin_ver = SITE_SEARCH_ONE_VERSION;
	wp_enqueue_script( 'site-search-one-widget_jquery', includes_url( 'js/jquery/jquery.min.js' ), array(), $plugin_ver, false );
	wp_enqueue_script( 'site-search-one-widget_bootstrap-datepicker', plugins_url( 'js/bootstrap-datepicker.min.js', __FILE__ ), array(), $plugin_ver, false );
	wp_enqueue_style( 'site-search-one-widget_bootstrap-datepicker', plugins_url( '/css/bootstrap-datepicker.min.css', __FILE__ ), array(), $plugin_ver );
	wp_enqueue_style( 'site-search-one-widget_search', plugins_url( 'css/search-bs5.css', __FILE__ ), array(), $plugin_ver );
	wp_enqueue_script( 'site-search-one-widget_moment', includes_url( 'js/dist/vendor/moment.js' ), array(), $plugin_ver, false );
	wp_enqueue_script( 'site-search-one-widget_bootstrap', plugins_url( 'js/bootstrap/5.2.3/bootstrap.min.js', __FILE__ ), array(), $plugin_ver, false );
	wp_enqueue_style( 'site-search-one-widget_bootstrap', plugins_url( 'css/bootstrap/5.2.3/bootstrap.min.css', __FILE__ ), array(), $plugin_ver );
	wp_enqueue_style( 'site-search-one-widget_bootstrap-icons', plugins_url( 'css/bootstrap-icons-1.10.2/bootstrap-icons.css', __FILE__ ), array(), $plugin_ver );
	wp_print_head_scripts();
	if ( property_exists( $display_opts, 'custom_css' ) && gettype( $display_opts->custom_css ) === 'string' ) {
		wp_add_inline_style( 'site-search-one-serp_search', $display_opts->custom_css );
	}
	$table_name = $wpdb->prefix . 'ss1_globals';
	$query      = "SELECT value FROM $table_name WHERE setting = 'global_css_widgets'";
	// phpcs:disable WordPress.DB.DirectDatabaseQuery
	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
	$global_css = $wpdb->get_var( $query );
	// phpcs:enable
	wp_enqueue_style( 'site-search-one-widget_global-css', false, array(), $plugin_ver );
	if ( $global_css ) {
		wp_add_inline_style( 'site-search-one-widget_search', $global_css );
	}
	if ( $custom_styles ) {
		wp_add_inline_style( 'site-search-one-widget_search', $custom_styles );
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
		<div id="container-search-options" class="mx-2 mb-4">
		<div id="search-bar" class="input-group">
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
					placeholder="<?php echo esc_attr( $placeholder_text ); ?>"
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
		<div id="query-options-container"
			class="m-2">
			<div id="stemming-synonyms-container" class="d-flex justify-content-center">
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
	<!--suppress CommaExpressionJS -->
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
		(function($) {
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

			$(document).ready(function() {
				//region Set available search types...
				$('input[name="search-type"]').each(function() {
					$(this).parent().addClass('hidden');
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
				$('input[name="search-type"][value="<?php echo esc_attr( $search_type ); ?>"]').parent().removeClass('hidden');
						<?php
					}
				} // Else all radios should be hidden
				?>
				$('input[name="search-type"][value="<?php echo esc_attr( $def_search_type ); ?>"]').prop('checked', true);
				//endregion

				//region Execute code each time window size changes
				let windowWidth = $(window).width();
				setLayoutMobile(windowWidth < 512);
				$(window).on('resize', function() {
					if ($(this).width() !== windowWidth) {
						windowWidth = $(this).width();
						setLayoutMobile(windowWidth < 512)
					}
				});
				//endregion
				update_synonyms_stemming_checkbox_dsp();
				<?php
				if ( isset( $_GET['restore'] ) ) {
					?>
				let query_to_restore = JSON.parse(sessionStorage.getItem('ss1-search-restore'));

				restore_query(query_to_restore);
					<?php
				} else {
					?>
				populate_filter_items_html();
					<?php
				}
				?>
			});

			let restoring = false;

			function restore_query(query)
			{

				restoring = true;

				$('#textbox-query').val(query.values.query);
				if ($('#select-index').length) {
					// Multiple Categories. Restore the index.
					let option_to_select = $('#select-index').find('option[data-ix-uuid="' + query.values.ix_uuid + '"]');
					$('#select-index').val(option_to_select.attr('value')).change();
				}
				// These checkboxes must be changed AFTER select-index changes, due to select-index change event
				// Setting the checkboxes check state to their default state
				$('#check-box-stemming').prop('checked', query.values.stemming);
				$('#check-box-synonyms').prop('checked', query.values.synonyms);
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
					apply_filters(false);
					restoring = false;
				});

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

			function populate_filter_items_html(callback = false) {
				if (all_fields == null)
				{
					// Filters not yet loaded
					load_fields(callback);
					return;
				}
				clear_filter_items_html();
				let filter_items = get_all_displayed_filter_items(get_current_search_page_ix_uuid());
				console.info('SS1-DEBUG New filter items:', filter_items);
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
					console.info('Filter to load', $(this));
					console.info($(this).data('filter-name'));
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
					loadFilterSelectOptions(selectOptionsToLoad, callback);
				} else {
					console.info('All select options were already cached');
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

			function get_current_search_page_ix_uuid() {
				if ($('#select-index').length) {
					// There is more than one search page
					return $('#select-index').find(':selected').data('ix-uuid');
				} else {
					return '<?php echo esc_html( $current_search_page->get_sc1_ix_uuid() ); ?>';
				}
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

				// // Datepicker is absolute positioned when open and does not count towards normal page height
				// // This function is needed to determine the page height incl. Absolute positioned elements.
				// // SO 16928101
				// function getPageHeight() {
				//     function getUpdatedHeight(element, originalMaxHeight) {
				//         var top = element.offset().top;
				//         if(typeof(top)!='undefined'){
				//             var height = element.outerHeight();
				//             return Math.max(originalMaxHeight, top+height);
				//         } else {
				//             return originalMaxHeight;
				//         }
				//     }
				//
				//     var maxhrel = 0;
				//     if( ! $.browser.msie) {
				//         maxhrel = $("html").outerHeight(); //get the page height
				//     } else {
				//         // in IE and chrome, the outerHeight of the html and body tags seem to be more like the window height
				//         $('body').children(":not(script)").each(function(){ //get all body children
				//             maxhrel=getUpdatedHeight($(this), maxhrel);
				//         });
				//     }
				//
				//     var atotoffset=0;  // absolute element offset position from the top
				//     $.each($('body *:not(script)'),function(){   //get all elements
				//         if ($(this).css('position') == 'absolute'){ // absolute?
				//             atotoffset=getUpdatedHeight($(this), atotoffset);
				//         }
				//     });
				//
				//     return Math.max(maxhrel, atotoffset);
				// }

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

			/**
			 * Null when not yet loaded. Must call load_filters
			 Anime Avatars (Incl. Heads) are required at Kokoro Academy. See https://kokoro.academy/kb/campus/appearance-guidelines/             */
			let all_fields = null;

			/**
			 * List of field names that are multiple-choice drop down select fields.
			 * These fields should only be displayed in the filters panel
			 * and not on the facets panel, despite being enumerable.
			 */
			let multi_choice_fields = [];

			/**
			 * Get the current values of search bar form controls
			 * @returns {{synonyms: (*|define.amd.jQuery), query: (*|define.amd.jQuery|string), stemming: (*|define.amd.jQuery), filters: *[]}}
			 */
			function get_searchbar_values()
			{
				let txt = $('#textbox-query').val();
				let filters = [];
				$('#filter-panel').find('.filter-item').each(function() {
					let checked = $(this).find('.filter-checkbox').prop('checked');
					if (checked) {
						let dataType = $(this).attr('data-filter-datatype');
						let fieldName = $(this).attr('data-filter-name');
						switch(dataType) {
							case 'DateTime':
							case 'Date':
								let dateFrom = $(this).find('.sc1-date-from').val();
								let dateTo = $(this).find('.sc1-date-to').val();
								filters.push([fieldName, dateFrom, dateTo]);
								break;
							case 'Multi-Choice':
								let selected_option = $(this).find('.filter-multi-choice').val();
								filters.push([fieldName, selected_option]);
								break;
							default:
								let filterTxt = $(this).find('.filter-textbox').val();
								filters.push([fieldName, filterTxt]);
								break;
						}
					}
				});
				let ix_uuid     = get_current_search_page_ix_uuid();
				let stemming    = null;
				let synonyms    = null;
				<?php
				if ( ! isset( $_GET['hideSynonymsAndStemming'] ) ) {
					?>
				stemming    = $('#check-box-stemming').prop('checked');
				synonyms    = $('#check-box-synonyms').prop('checked');
					<?php
				}
				?>
				let searchType  = $('input[name="search-type"]:checked').val();
				return {
					'query'     : txt,
					'ix_uuid'   : ix_uuid,
					'filters'   : filters,
					'stemming'  : stemming,
					'synonyms'  : synonyms,
					'searchType': searchType
				}
			}

			$('#btn-filter-apply').click(function() {
				apply_filters(true);
			});

			$('#check-box-stemming').change(function() {
				emit_searchbar_update_event();
			});

			$('#check-box-synonyms').change(function() {
				emit_searchbar_update_event();
			});

			$('input[name="search-type"]').change(function() {
				emit_searchbar_update_event();
			});

			function apply_filters(emit) {
				let filterCount = get_filter_count();
				let badge = $('#filterCountBadge');

				if (filterCount > 0)    $(badge).css('display','inline-block').html(filterCount);
				else                    $(badge).css('display','none').html('');
				current_page = 1;
				if (emit) emit_searchbar_update_event();
				update_clear_filters_btn_vis();
			}

			function clear_filters()
			{
				$('.filter-checkbox').prop('checked',false).trigger('change');
			}

			$('#btn-filter-clear').click(function() {
				clear_filters();
			});

			$("#textbox-query").keypress(function( event ) {
				if (event.keyCode === 13) {
					// Pressed ENTER on search box
					emit_searchbar_update_event(true);
				}
			});
			$("#btn-search").click(function() {
				// Clicked Search Magnifying Glass
				emit_searchbar_update_event(true);
			});

			let isFirstEmittedEvent = true;
			function emit_searchbar_update_event(text_submission = false)
			{
				let searchbar_values = get_searchbar_values();
				window.parent.postMessage({
					'type' : 'searchbar_update',
					'text_submission': text_submission,
					'values': searchbar_values,
					'first_submission': isFirstEmittedEvent
				}, "*");
				isFirstEmittedEvent = false;
			}

			window.addEventListener("message", (event) => {
				let data = event.data;
				if (data
					&& typeof data === 'object'
					&& 'type' in data
					&& data.type)
				{
					switch(data.type)
					{
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


			function set_searchbar_values(values)
			{
				// TODO
			}

			// Used by both clear filters button and when user changes category
			function clear_filters()
			{
				$('.filter-checkbox').prop('checked',false).trigger('change');
			}

			function getMinutesBetweenDates(startDate, endDate) {
				let diff = endDate.getTime() - startDate.getTime();
				return (diff / 60000);
			}

			// If a filter is active, the button becomes enabled.
			function update_clear_filters_btn_vis()
			{
				let filters_count = get_filter_count();
				if (filters_count > 0) $('#btn-filter-clear').removeClass('disabled');
				else                   $('#btn-filter-clear').addClass('disabled');
			}

			function get_filter_count() {
				return $('.filter-checkbox:checked').length;
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

			function get_indexes_to_search() {
				if ($('#select-index').length) {
					// There is more than one search page
					let uuid = $('#select-index').find(':selected').data('ix-uuid');
					if (uuid === 'all') {
						let indexes = [];
						let primary_ix_uuid = '<?php echo esc_js( $current_search_page->get_sc1_ix_uuid() ); ?>';
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
					let uuid = '<?php echo esc_js( $current_search_page->get_sc1_ix_uuid() ); // TODO Error handling. ?>';
					let id = parseInt('<?php echo esc_js( $current_search_page->get_sc1_ix_id() ); ?>');
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
					console.info('Cached Field Spec', cached_spec);
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
									console.info('extraParams["SS1-DisplayName"]',
										extraParams["SS1-DisplayName"]);
									field.dspName = extraParams["SS1-DisplayName"];
									console.info('field.dspName', field.dspName)
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
											console.info(fieldSpec.DisplayName, ' has display property: _' + dsp + '_');
										} else {
											console.info(fieldSpec.DisplayName, ' does not have display property: ', dsp);
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


			function isSC1StoredFieldATaxonomy(storedField) {
				if (storedField.hasOwnProperty('ExtraParams')
					&& storedField.ExtraParams != null)
				{
					let extraParams = JSON.parse(storedField.ExtraParams);
					if (
						extraParams.hasOwnProperty('SS1-Display')
						&& extraParams["SS1-Display"] === "Taxonomy"
					) return true;
				}
				return false;
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
					return ['<?php echo esc_js( $current_search_page->get_sc1_ix_uuid() ); ?>'];
				}
			}

			function should_display_in_filters_dropdown(ix_uuid, fieldName) {
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
					$opts          = $search_page->get_display_opts();
					$ix_uuid       = $search_page->get_sc1_ix_uuid();
					$sp_dsp_opts[] = array(
						'ix_uuid' => $ix_uuid,
						'opts'    => $opts,
					);
				}
				?>
				let sp_dsp_opts = <?php echo wp_json_encode( $sp_dsp_opts ); ?>;
				console.info('sp_dsp_opts_searchpage', sp_dsp_opts, "retreiving", ix_uuid, ' field ', fieldName);
				for (let x = 0; x < sp_dsp_opts.length; x++) {
					let page_opts = sp_dsp_opts[x];
					if (page_opts.ix_uuid === ix_uuid) {
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
				console.trace();
				return false;
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
					// User chose a different search page...
					clear_filter_items_html();
					populate_filter_items_html();
					update_synonyms_stemming_checkbox_dsp();
					facets = [];
					current_page = 1;
					emit_searchbar_update_event();
				});
			}
		})(jQuery);
	</script>
	<?php
	wp_print_footer_scripts();
	?>
</body>
