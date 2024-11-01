<?php
/**
 * Hitviewer Styler.
 *
 * @package    Site_Search_One
 */

?>

<script>
	let style_elem = document.createElement('link');
	style_elem.setAttribute('rel','stylesheet');
	style_elem.setAttribute('type','text/css');
	style_elem.setAttribute('href', '<?php echo( esc_attr( plugins_url( '../templates/css/hit_viewer.css', __FILE__ ) ) ); ?>');

	{
		let contentWindow = window.document.querySelector('iframe').contentWindow;
		contentWindow.addEventListener("load", function() {
			console.info('Adding Stylesheet to iframe');
			contentWindow.document.head.appendChild(style_elem);
			console.info('Stylesheet added');
			setTimeout(function() {
				console.info('Displaying Iframe');
				$('iframe').animate({'top' : 0});
				$('#loading-container').remove();
			}, 1000);
		}, true);
	}
</script>
