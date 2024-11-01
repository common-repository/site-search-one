<?php
/**
 * Template Name: Site Search ONE - Hitviewer
 * Template Post Type: ss1_hitviewer
 *
 * @package Site_Search_One
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // This can no longer be called directly, must instead call page through REST route.
}
header( 'Cache-Control: max-age=86400' ); // Browser should cache this page for 24 hours in seconds.
?>
<!DOCTYPE html>
<html lang="en" style="height: 100%">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title></title>
	<?php
	$plugin_ver = SITE_SEARCH_ONE_VERSION;
	wp_enqueue_script( 'site-search-one-hitviewer_jquery', includes_url( 'js/jquery/jquery.min.js' ), array(), $plugin_ver, false );
	wp_enqueue_script( 'site-search-one-hitviewer_popper', plugins_url( '../templates/js/popper.min.js', __FILE__ ), array(), $plugin_ver, false );
	wp_enqueue_script( 'site-search-one-hitviewer_bootstrap', plugins_url( '../templates/js/bootstrap/4.6.2/bootstrap.min.js', __FILE__ ), array(), $plugin_ver, false );
	wp_enqueue_style( 'site-search-one-hitviewer_bootstrap', plugins_url( '../templates/css/bootstrap/4.6.2/bootstrap.min.css', __FILE__ ), array(), $plugin_ver );
	wp_enqueue_style( 'site-search-one-hitviewer_fa', plugins_url( '../templates/css/fa_all.css', __FILE__ ), array(), $plugin_ver );
	wp_print_head_scripts();
	wp_print_styles();
	?>
	<style>
		#docFrame {
			transition: all .5s;
			top: 100%;
			display:block;
			border:none;
			position: absolute;
			width: 100%;
			height: 100%;
		}

		.nav-hidden-btn {
			color: rgb(246, 246, 246) !important;
		}

	</style>
</head>
<body style="margin: 0; height: 100%; background-color: gray">
<nav class="navbar navbar-expand navbar-light" style="background-color: rgb(246, 246, 246)">
	<div class="navbar-collapse collapse">
		<div id="hit-nav" class="navbar-nav d-none" style="display: none">
			<a id="btnFirst" data-toggle="tooltip" title="First Hit" class="nav-item nav-link m-1" href="#">
				<i class="fas fa-angle-double-left"></i>
			</a>
			<a id="btnPrev" data-toggle="tooltip" title="Previous Hit" class="nav-item nav-link m-1" href="#">
				<i class="fas fa-angle-left"></i>
			</a>
			<span class="nav-item">
				  <div class="form-inline">
					Hit
					<input id="tb_hitnum" type="text" class="form-control m-1" style="width:50px" placeholder="#" value="1">
					/<span id="hit_count"></span>
				  </div>
				</span>
			<a id="btnNext" data-toggle="tooltip" title="Next Hit" class="nav-item nav-link m-1" href="#">
				<i class="fas fa-angle-right"></i>
			</a>
			<a id="btnLast" data-toggle="tooltip" title="Last Hit" class="nav-item nav-link m-1" href="#">
				<i class="fas fa-angle-double-right"></i>
			</a>
		</div>
	</div>
	<div class="navbar-nav" style="float:right;">
		<a id="btnPrint" href="#" data-toggle="tooltip" title="Print Document" class="nav-item nav-link m-1 nav-hidden-btn"><i class="fas fa-print"></i></a>
		<a id="btnClose" href="#" data-toggle="tooltip" title="Close" class="nav-item nav-link m-1"><i class="fas fa-times"></i></a>
	</div>
</nav>
<div id="loading-container" style="width: 20px; height: 20px; position: absolute; top: 50%; left: 50%;">
	<i class="fas fa-spinner fa-pulse"></i>
</div>
<div id="iframe-container" style="position:absolute; left: 0px; right: 0px; top: 65px; bottom: 0px; overflow: hidden">
<!--    <iframe id="docFrame" width="100%" height="100%" style="display:block; border:none; position: absolute;">-->
</div>
<!-- Modal Dialog for File Properties -->
<div id="filePropsModal" class="modal" tabindex="-1" role="dialog">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Properties</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<ul class="list-group list-group-flush"><!-- File Properties go here --></ul>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
<script>
	(function($) {
		$(document).ready(function () {
		   let hv_fields_hidden = false;
		   let hv_shown_fields  = 'all';
		   let document_css     = false;
		   const urlSearchParams = new URLSearchParams(window.location.search);
		   const endPointURL    = "<?php echo esc_js( get_transient( 'ss1-endpoint-url' ) ); ?>";
		   let   searchReq;
		   $('#btnClose').click(function() {
			   window.parent.postMessage({
				   'action' : 'SS1-HV-Overlay-Hide'
			   },"*");
		   });
		   // Listen for SearchReq data
		   window.addEventListener("message",(event)=> {
			   if (event.data) {
				   if (event.data.action === 'SS1-HV-Set-SearchReq') {
					   console.info('setting searchReq', event.data);
					   //region Hide the hovertext if it's already shown
					   let close_hovertext_id = $('#btnClose').attr('aria-describedby');
					   if (close_hovertext_id) {
						   $('#' + close_hovertext_id).hide();
					   }
					   //endregion
					   searchReq = event.data.searchReq;
					   hv_fields_hidden = event.data.hv_fields_hidden;
					   hv_shown_fields  = event.data.hv_shown_fields;
					   if ('document_css' in event.data) document_css = event.data.document_css;
					   if (searchReq) tryLoadDocument();
				   }
			   }
		   });
		   // Request document to load..
		   window.parent.postMessage({
			   'action' : 'SS1-HV-Get-SearchReq'
		   }, "*");


		   function tryLoadDocument() {
			   $('#loading-container').show();
			   console.debug('Now Loading...', searchReq, endPointURL);
			   $('#iframe-container').html('');
			   $('#btnPrint').addClass('nav-hidden-btn');
			   $.ajax({
				   type: "POST",
				   url: endPointURL + "/Search",
				   data: JSON.stringify(searchReq),
				   dataType: 'json',
				   contentType: 'application/json',
				   success: function (data) {
						console.info('Successfully retrieved document. Writing into a new iFrame');
						$('<iframe id="docFrame"></iframe>').appendTo('#iframe-container');

						let doc = $('#docFrame')[0].contentWindow.document;
						doc.open();
						doc.write(data.HitViewer.Html);
						doc.close();
						//region Setup hit navigation
					   //region Add iFrame scroller to iframe
					   let iframeScrollerURL = '<?php echo esc_js( plugins_url( '../templates/js/iframe-scroller.js', __FILE__ ) ); ?>';

					   let script = document.createElement('script');
					   script.type = "text/javascript";
					   script.src = iframeScrollerURL;
					   doc.head.appendChild(script);
					   //endregion
					   num_hits = doc.getElementsByClassName("dts_hit").length;
					   current_element = doc.getElementsByClassName("dts_hit")[0];
					   $('#hit_count').html(num_hits);
					   current_hit = 1;

					   if (hv_fields_hidden) {
						   console.info('hiding fields..');
						   $('#docFrame').contents().find('.dts-field-table').hide();
					   }
					   if (hv_shown_fields !== 'all') {
						   console.info('setting shown fields', hv_shown_fields);
						   set_shown_fields(hv_shown_fields);
					   }
					   update_display();
					   setTimeout(function() {
						   update_display();
					   }, 1000);

					   $('.nav-link').tooltip({trigger : 'hover'});
					   if (num_hits > 0) {
						   $('#hit-nav').css({'display':'flex'});
					   }
						//endregion
						//region Style the iframe contents..
						let style_elem = document.createElement('link');
						style_elem.setAttribute('rel','stylesheet');
						style_elem.setAttribute('type','text/css');
						style_elem.setAttribute('href', '<?php echo esc_attr( plugins_url( '../templates/css/hit_viewer.css', __FILE__ ) ); ?>');
						doc.head.appendChild(style_elem);
						console.info('Displaying Iframe');
						$('iframe').animate({'top' : 0});
						$('#loading-container').hide();
						setTimeout(function() {
							$('#btnPrint').removeClass('nav-hidden-btn');
							$('#hit-nav').removeClass('d-none');
						});
					   //endregion.
					   //region Also any custom user styles.
					   if (document_css) {
						   //console.info('Custom Document CSS Set', document_css);
						   let custom_css_elem = $('<style>');
						   custom_css_elem.html(document_css);
						   doc.head.appendChild(custom_css_elem[0]);
					   }
					   //endregion.
				   },
				   error: function (xhr, status, error) {
						if (parseInt(xhr.status) === 440) {
							console.info('The Session has Expired. Requesting a new Token...');
							refresh_session();
						} else {
							console.error('Failed to load Search Results', xhr.status)
						}
				   }
			   });
		   }

			/**
			 * @param shown_fields[]
			 */
		   function set_shown_fields(shown_fields) {
			   let frameContents = $('#docFrame').contents();
			   let fieldTables = frameContents.find('.dts-field-table');
			   if (fieldTables.length) {
				   fieldTables.find('.dts-field-table-name-cell').each(function(index, item) {
					   let field_name = $(item).text();

					   let shown = false;
					   for (let i = 0; i < shown_fields.length; i++) {
						   if (field_name  === shown_fields[i] + ":") {
							   console.info('"' + field_name + '","' + shown_fields[i] + '"');
							   shown = true;
							   break;
						   }
					   }
					   console.info('field_name "' + field_name + '" shown', shown);
					   if (!shown) {
						   $(item).parent().hide();
					   }
				   });
			   }
		   }

			function refresh_session() {
				let tokens_url = '<?php echo esc_js( rest_url( 'ss1_client/v1/tokens' ) ); ?>';
				$.ajax({
					type: 'GET',
					url: tokens_url,
					timeout: 30000,
					success: function(data, textStatus, xhr) {
						console.info('Got token', data);
						searchReq.Token = data;
						tryLoadDocument();
					},
					error: function(xhr, textSTatus, error) {
						console.info('Failed to obtain new token', xhr);
						$('#info-display').html('<i><?php esc_html_e( 'Something went wrong. Check connection and try again.', 'site-search-one' ); ?></i>');
					}
				})
			}

			//region Hit viewer navigation
			let num_hits;
			let current_hit;
			let current_element;


			function update_display()
			{
				let data = {};
				data.action = 'scrollTo';
				let element = current_hit;
				if (current_hit === num_hits && num_hits > 1)
				{
					element = '_last';
				}
				data.element = 'hit_' + element;
				$('#tb_hitnum').val(current_hit);
				document.getElementById('docFrame').contentWindow.postMessage(data,"*");
			}

			$('#tb_hitnum').on('keypress', function (e) {
				if(e.which === 13){

					current_hit = $('#tb_hitnum').val();
					update_display();
				}
			});

			$('#btnFirst').click(function() {
				current_hit = 1;
				update_display();
			});

			$('#btnLast').click(function() {
				current_hit = num_hits ;
				update_display();
			});

			$('#btnNext').click(function() {
				current_hit++;
				if (current_hit < 1) current_hit = 1;
				if (current_hit >= num_hits) current_hit = num_hits ;
				update_display();
			});

			$('#btnPrev').click(function() {
				current_hit--;
				if (current_hit < 1) current_hit = 1;
				if (current_hit >= num_hits) current_hit = num_hits ;
				update_display();
			});

			$('#btnPrint').click(function() {
				document.getElementById('docFrame').contentWindow.print();
			});
		});
	})(jQuery);
</script>
</body>
<?php
wp_print_footer_scripts();
?>
</html>
