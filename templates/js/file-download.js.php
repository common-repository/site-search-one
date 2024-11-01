<?php
/**
 * Script for downloading files from hitviewer.
 * TODO Convert this to use php_vars so as to remove the need for this to be a PDF script.
 *
 * @package           Site_Search_One
 */

?>
$(document).ready(function() {
   /*
   Hook into when the download button is clicked.
	*/
   let downloadBtn = $('#btn-download');
   downloadBtn.click(function () {
	  let fUUID = downloadBtn.attr('data-fileUUID');
	  $.ajax({
		  type: "GET",
		  url: '<?php echo( esc_url( rest_url( 'sc1_client/v1/download_tokens' ) ) ); ?>',
		  timeout: 10000,
		  data: {
			  fileUUID: fUUID
		  },
		  success: function (data, textStatus, xhr) {
			  top.location.href = "<?php echo esc_url( get_transient( 'ss1-endpoint-url' ) ); ?>/Files?Token=" + data + "&FileUUID=" + fUUID;
		  },
		  error: function (data, textStatus, xhr) {
			alert('Something went wrong. Check connection and try again?');
		  }
	  });
   });
	/*
	 FileSize js
	 2018 Jason Mulligan <jason.mulligan@avoidwork.com>
	 @version 3.6.1
	*/
		"use strict";!function(e){var i=/^(b|B)$/,t={iec:{bits:["b","Kib","Mib","Gib","Tib","Pib","Eib","Zib","Yib"],bytes:["B","KiB","MiB","GiB","TiB","PiB","EiB","ZiB","YiB"]},jedec:{bits:["b","Kb","Mb","Gb","Tb","Pb","Eb","Zb","Yb"],bytes:["B","KB","MB","GB","TB","PB","EB","ZB","YB"]}},o={iec:["","kibi","mebi","gibi","tebi","pebi","exbi","zebi","yobi"],jedec:["","kilo","mega","giga","tera","peta","exa","zetta","yotta"]};function b(e){var b,n,r,a,s,f,d,u,l,B,c,p,y,g=arguments.length>1&&void 0!==arguments[1]?arguments[1]:{},m=[],v=0,x=void 0,h=void 0;if(isNaN(e))throw new Error("Invalid arguments");return n=!0===g.bits,l=!0===g.unix,b=g.base||2,u=void 0!==g.round?g.round:l?1:2,B=void 0!==g.separator&&g.separator||"",c=void 0!==g.spacer?g.spacer:l?"":" ",y=g.symbols||g.suffixes||{},p=2===b&&g.standard||"jedec",d=g.output||"string",a=!0===g.fullform,s=g.fullforms instanceof Array?g.fullforms:[],x=void 0!==g.exponent?g.exponent:-1,f=(h=Number(e))<0,r=b>2?1e3:1024,f&&(h=-h),(-1===x||isNaN(x))&&(x=Math.floor(Math.log(h)/Math.log(r)))<0&&(x=0),x>8&&(x=8),0===h?(m[0]=0,m[1]=l?"":t[p][n?"bits":"bytes"][x]):(v=h/(2===b?Math.pow(2,10*x):Math.pow(1e3,x)),n&&(v*=8)>=r&&x<8&&(v/=r,x++),m[0]=Number(v.toFixed(x>0?u:0)),m[1]=10===b&&1===x?n?"kb":"kB":t[p][n?"bits":"bytes"][x],l&&(m[1]="jedec"===p?m[1].charAt(0):x>0?m[1].replace(/B$/,""):m[1],i.test(m[1])&&(m[0]=Math.floor(m[0]),m[1]=""))),f&&(m[0]=-m[0]),m[1]=y[m[1]]||m[1],"array"===d?m:"exponent"===d?x:"object"===d?{value:m[0],suffix:m[1],symbol:m[1]}:(a&&(m[1]=s[x]?s[x]:o[p][x]+(n?"bit":"byte")+(1===m[0]?"":"s")),B.length>0&&(m[0]=m[0].toString().replace(".",B)),m.join(c))}b.partial=function(e){return function(i){return b(i,e)}},"undefined"!=typeof exports?module.exports=b:"function"==typeof define&&define.amd?define(function(){return b}):e.filesize=b}("undefined"!=typeof window?window:global);

	/*
	Hook into when the Properties button is clicked
	 */
	let propertiesBtn = $('#btn-properties');
	propertiesBtn.click(function() {
	   let fUUID = downloadBtn.attr('data-fileUUID');
	   $.ajax({
		   type: "GET",
		   url: '<?php echo( esc_url( rest_url( 'sc1_client/v1/file_properties' ) ) ); ?>',
		   timeout: 10000,
		   data: {
			   fileUUID: fUUID
		   },
		   success: function (data, textStatus, xhr) {
			   let name         = data.Name;
			   let size         = filesize(data.Size);
			   let uploadedBy   = data.UploadedBy;
			   let propertiesModal = $('#filePropsModal');
			   propertiesModal.modal('show');
			   propertiesModal.find('.modal-title').html(name);
			   let propertiesList = propertiesModal.find('.list-group');
			   propertiesList.html(''); // Clear existing.
			   propertiesList.append('<li class="list-group-item d-flex d-inline justify-content-between"><span>Document Size</span> <span>' + size +'</span></li>');
			   propertiesList.append('<li class="list-group-item d-flex d-inline justify-content-between"><span>Uploaded By</span> <span>' + uploadedBy +'</span></li>');
			   propertiesList.append('<li class="list-group-item d-flex d-inline justify-content-between"><span>UUID</span> <span>' + fUUID +'</span></li>');
			   //alert(name + " size " + size + " uploaded by " + uploadedBy);
		   },
		   error: function (data,textStatus, xhr) {
			   alert('Something went wrong. Check connection and try again?');
		   }
	   })
	});
});
