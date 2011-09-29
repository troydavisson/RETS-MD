
// setup the interface
jQuery(document).ready(function() {

	jQuery("#peek-link, #format-switch").live("click", function() {
		showPeekData(jQuery(this));
		return false;
	});


	jQuery("#object-window").live("click", function() {

		showLoading();

		var rets_resource = jQuery(this).attr("data-resource");

		jQuery.get(this_page, { action: 'objects', r_resource: rets_resource }, function(data) {
			jQuery.modal.close();

			setTimeout(function () {
				jQuery.modal("<div id='ajax-content'>" + data + "</div>", { overlayClose: true, minWidth:550 });
			}, 100);

		});
		return false;
	});


	jQuery(".lookup-window").live("click", function() {

		showLoading();

		var rets_resource = jQuery(this).attr("data-resource");
		var rets_lookupname = jQuery(this).attr("data-lookupname");

		jQuery.get(this_page, { action: 'lookup', r_resource: rets_resource, r_lookupname: rets_lookupname }, function(data) {
			jQuery.modal.close();

			setTimeout(function () {
				jQuery.modal("<div id='ajax-content'>" + data + "</div>", { overlayClose: true, minWidth:550, maxHeight:550 });
			}, 100);

		});
		return false;
	});


	jQuery("#resource-class-selector").live("change", function() {
		var selected_pair = "";

		jQuery("#resource-class-selector option:selected").each(function() {
			selected_pair = jQuery(this).val();
		});

		var selected_parts = selected_pair.split("|");
		var rets_resource = selected_parts[0];
		var rets_class = selected_parts[1];

		showMetadataDetails(rets_resource, rets_class);
		return false;
	});


	jQuery(".resource-class-link").live("click", function() {
		var rets_resource = jQuery(this).attr("data-resource");
		var rets_class = jQuery(this).attr("data-class");

		showMetadataDetails(rets_resource, rets_class);
		return false;
	});

	jQuery("#extra-link").live("click", function() {
		jQuery(".extra").each(function() {
			jQuery(this).show('slow');
		});
		jQuery(".extra-link-row").each(function() {
			jQuery(this).hide();
		});

		return false;

	});


});



function showMetadataDetails(rets_resource, rets_class) {

	jQuery("#md-details-content").html("<p align='center'><b>Waiting on RETS server...</b></p><p align='center'><img src='"+ this_media +"ajax-loader-blue.gif' /></p>");
	jQuery(document).scrollTo( jQuery('#md-details') , 800);

	jQuery.get(this_page, { action: 'mddetails', r_resource: rets_resource, r_class: rets_class }, function(data) {
		jQuery("#md-details-content").html(data);
		jQuery(document).scrollTo( jQuery('#md-details') , 800);
	});

}


function showPeekData(elem) {

	showLoading();

	var rets_resource = jQuery(elem).attr("data-resource");
	var rets_class = jQuery(elem).attr("data-class");
	var rets_format = jQuery(elem).attr("data-format");

	jQuery.get(this_page, { action: 'peek', r_resource: rets_resource, r_class: rets_class, r_format: rets_format }, function(data) {
		jQuery.modal.close();

		setTimeout(function () {
			jQuery.modal("<div id='ajax-content'>" + data + "</div>", { overlayClose: true, maxWidth:"90%" });
		}, 100);

	});

}

function showLoading() {

	jQuery.modal.close();

	setTimeout(function () {
		jQuery.modal("<div id='modal-content'><p align='center'><b>Waiting on RETS server...</b></p><p align='center'><img src='"+ this_media +"ajax-loader.gif' /></p></div>", { overlayClose: true, minWidth: 500, minHeight: 100 });
	}, 100);
}
